# VOD-VIP 系统架构文档

## 1. 系统概览

VOD-VIP 是一个视频点播会员系统，采用前后端分离 + SSR 架构，视频通过 Telegram Bot 采集后自动转码为 HLS AES-128 加密流播放。

### 1.1 技术栈

| 层 | 技术 |
|---|---|
| 后端 | PHP 8.2+, Laravel 13, MySQL 8.0, Redis |
| 前端 | Vue 3, TypeScript, Vite SSR, Tailwind CSS 4 |
| 后台 | Vue 3, TypeScript, Vite, Tailwind CSS |
| 播放器 | Artplayer + hls.js |
| 转码 | FFmpeg (HLS AES-128) |
| 采集 | Telegram Bot API (支持 Local Server) |
| 部署 | Nginx + systemd |

### 1.2 项目结构

```
web-ai/
├── backend/          # Laravel 后端
├── frontend/         # Vue 3 SSR 前端
├── admin/            # Vue 3 后台管理面板
```

---

## 2. 视频采集 → 转码 → 播放 完整流程

### 2.1 采集入库

```
用户发视频到 Telegram Bot
        │
        ▼
TelegramBotService::processMedia()
        │
        ├─ 1. 防重复检查 (telegram_file_id)
        ├─ 2. 调用 Telegram API getFile 获取文件路径
        ├─ 3. 下载文件到 storage/app/public/telegram/videos/{timestamp}_{random}.mp4
        ├─ 4. 写入 media_resources 表
        │       local_path = "telegram/videos/xxx.mp4"
        │       synced_to_video = false
        │
        ├─ 5. autoSyncToVideo() [视频类型自动触发]
        │       ├─ 创建 Video 记录
        │       │     video_url = "telegram/videos/xxx.mp4" (纯相对路径)
        │       │     transcode_status = "pending"
        │       │     category_id = 「采集」(tg) 分类
        │       ├─ media_resources.synced_to_video = true
        │       └─ dispatch TranscodeVideoJob
        │
        └─ 6. Bot 回复入库成功 + 转码已排队
```

### 2.2 HLS 转码 (异步队列)

```
TranscodeVideoJob (queue:work 执行)
        │
        ├─ 更新 transcode_status = "processing"
        │
        ├─ 输入源判断:
        │       ├─ 远程 URL (http/https) → 下载到 downloads/{id}/original.mp4，更新 video_url
        │       └─ 本地路径 → 直接使用
        │
        ├─ 生成 AES-128 密钥 (16 字节随机) + IV (16 字节随机)
        │
        ├─ FFmpeg 执行:
        │       ffmpeg -y -i input.mp4
        │         -c:v libx264 -preset fast -crf 23
        │         -c:a aac -b:a 128k
        │         -hls_time 10 -hls_list_size 0
        │         -hls_key_info_file key_info.txt
        │         -hls_segment_filename seg_%04d.ts
        │         -f hls index.m3u8
        │
        │   输出目录: storage/app/public/hls/{video_id}/
        │   输出文件: index.m3u8 + seg_xxxx.ts (多个分片)
        │   m3u8 中 key URI = "http://placeholder/key" (由前端 hls.js 拦截替换)
        │
        ├─ 清理临时文件 (enc.key, key_info.txt)
        │
        ├─ 成功:
        │       ├─ 更新 Video: hls_path, hls_key(hex), transcode_status = "done"
        │       └─ Bot 通知上传者: "✅ HLS 转码完成"
        │
        └─ 失败:
                ├─ 更新 transcode_status = "failed"
                └─ Bot 通知上传者: "❌ HLS 转码失败"
```

### 2.3 前端播放

```
用户访问 /video/{id}
        │
        ▼
GET /api/videos/{id}  →  VideoController::show()
        │
        ├─ 判断 hls_path 存在 && transcode_status === "done"
        │       ├─ 是 → play_type = "hls"
        │       │       play_url = MEDIA_BASE_URL/hls/{id}/index.m3u8
        │       │       key_url = /api/hls/key/{id}?expires={ts}&signature={hmac}
        │       └─ 否 → play_type = "mp4"
        │               play_url = MEDIA_BASE_URL/telegram/videos/xxx.mp4
        │
        ├─ VIP 权限检查:
        │       ├─ 非 VIP 且 is_vip=1 → 仅返回 preview_url
        │       └─ 其他 → 返回完整 play_url
        │
        ├─ API 响应经 EncryptApiResponse 中间件 AES-256-CBC 加密
        │
        ▼
前端 Axios 拦截器 → crypto.ts 解密 → VideoView.vue
        │
        ├─ play_type === "hls":
        │       ├─ Artplayer + hls.js 初始化
        │       ├─ hls.js xhrSetup 拦截 key 请求:
        │       │       "http://placeholder/key" → 替换为 key_url (签名URL)
        │       ├─ 浏览器: 请求 m3u8 → 请求 .ts 分片 → 请求 key → AES 解密 → 播放
        │       └─ 原生 Safari: 直接用 <video> src (不走 hls.js)
        │
        └─ play_type === "mp4":
                └─ Artplayer 直接播放
```

---

## 3. 安全机制

### 3.1 API 响应加密

所有公开 API (`/api/*`) 响应通过 `EncryptApiResponse` 中间件加密：

- 算法: AES-256-CBC
- 密钥: `.env` 中 `API_ENCRYPT_KEY` (64 位 hex = 32 字节)
- 格式: `{ "_e": base64(IV + ciphertext) }`
- 前端: Web Crypto API 解密
- 排除路径: `/api/admin/*`, `/api/telegram/*`, `/api/payment/*`, `/api/hls/*`

### 3.2 HLS 加密

- 算法: AES-128 (FFmpeg 内置 HLS 加密)
- 密钥: 每个视频独立的 16 字节随机密钥，存储在 `videos.hls_key` (hex)
- 密钥获取: 签名 URL `/api/hls/key/{id}?expires={ts}&signature={hmac}`
- 签名算法: HMAC-SHA256，密钥为 `.env` 中 `HLS_KEY_SECRET`
- 有效期: 默认 6 小时
- `videos.hls_key` 字段设为 `$hidden`，不会出现在 API 响应中

### 3.3 反向代理

- `bootstrap/app.php` 配置 `trustProxies(at: '*')` 确保 `url()` 在 Nginx 代理后正确生成 HTTPS 地址

---

## 4. 数据库关键表

### 4.1 videos 表

| 字段 | 类型 | 说明 |
|---|---|---|
| `id` | int | 主键 |
| `title` | varchar | 视频标题 |
| `cover_url` | varchar | 封面图（相对路径或外部 URL） |
| `video_url` | varchar | 原始 MP4 相对路径 (如 `telegram/videos/xxx.mp4`) |
| `hls_path` | varchar, nullable | HLS m3u8 相对路径 (如 `hls/27/index.m3u8`) |
| `hls_key` | varchar(32), nullable, hidden | AES-128 密钥 (hex) |
| `transcode_status` | varchar(20), nullable | `pending` / `processing` / `done` / `failed` |
| `preview_url` | varchar, nullable | 试看片段路径 |
| `is_vip` | tinyint | 0=免费, 1=VIP |
| `category_id` | int | 分类 ID |
| `duration` | int | 时长（秒） |
| `view_count` | int | 播放次数 |

### 4.2 media_resources 表

| 字段 | 类型 | 说明 |
|---|---|---|
| `id` | int | 主键 |
| `telegram_file_id` | varchar | Telegram 文件唯一 ID |
| `file_type` | enum | `image` / `video` / `document` |
| `file_name` | varchar | 原始文件名 |
| `local_path` | varchar | 本地存储相对路径 |
| `file_size` | bigint | 文件大小（字节） |
| `caption` | text | Telegram 消息附文 |
| `from_user_id` | bigint | 发送者 Telegram ID |
| `synced_to_video` | boolean | 是否已同步到 videos 表 |

---

## 5. 环境配置 (.env)

### 5.1 后端关键配置

```env
# 数据库
DB_HOST=38.180.107.252
DB_DATABASE=vodvip
DB_USERNAME=vodvip
DB_PASSWORD=VodVip2026!

# 队列 (转码任务用)
QUEUE_CONNECTION=database

# 视频文件访问域名 (拼接相对路径用)
MEDIA_BASE_URL=https://38.180.107.252/storage

# HLS 密钥签名密钥
HLS_KEY_SECRET=k9Xm2pQ7vLwR4tBn8sYzA3hJ6dFcG0eU

# API 响应加密密钥 (64位hex = 32字节)
API_ENCRYPT_KEY=59faf81527de015abf93ea00493b659d650b045966e24b234c873d54c1073751

# Telegram Bot
TELEGRAM_BOT_TOKEN=xxx
TELEGRAM_ALLOWED_USERS=xxx
TELEGRAM_USE_LOCAL_SERVER=true
TELEGRAM_API_BASE_URL=http://127.0.0.1:8081
```

### 5.2 前端配置

```env
# 解密密钥（与后端 API_ENCRYPT_KEY 一致）
VITE_API_ENCRYPT_KEY=59faf81527de015abf93ea00493b659d650b045966e24b234c873d54c1073751
```

---

## 6. Nginx 配置

```nginx
# 主站 (HTTPS 443)
server {
    listen 443 ssl;
    server_name 38.180.107.252;

    # API 直连后端
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # 静态文件 (视频/HLS/图片)
    location /storage/ {
        alias /var/www/vodvip-storage/public/;
    }

    # SSR 前端
    location / {
        proxy_pass http://127.0.0.1:5173;
    }
}

# 后台管理 (端口 9000)
server {
    listen 9000;
    root /var/www/vodvip-admin;
    location /api/ { proxy_pass http://127.0.0.1:8000; }
    location / { try_files $uri /index.html; }
}
```

---

## 7. systemd 服务

| 服务名 | 作用 | 命令 |
|---|---|---|
| `vodvip-backend` | Laravel 后端 | `php artisan serve` |
| `vodvip-frontend` | SSR 前端 (Express + Vue) | `NODE_ENV=production node server.js` |
| `vodvip-queue` | 队列 Worker (转码任务) | `php artisan queue:work --sleep=3 --tries=3` |

---

## 8. 常用运维命令

```bash
# 批量转码所有未转码视频
php artisan videos:transcode

# 强制重新转码所有视频
php artisan videos:transcode --force

# 启动队列处理转码任务
php artisan queue:work

# 重启服务
systemctl restart vodvip-backend
systemctl restart vodvip-frontend
systemctl restart vodvip-queue

# 清除配置缓存
php artisan config:clear && php artisan config:cache

# 查看转码日志
tail -f storage/logs/laravel.log | grep Transcode
```
