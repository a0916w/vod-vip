# VOD-VIP 视频会员系统

内容付费视频点播平台 — 用户充值 VIP 即可解锁完整视频资源。

## 技术栈

| 层级 | 技术 |
| --- | --- |
| 后端 | PHP 8.2+ · Laravel 13 · Sanctum |
| 前端 | Vue 3 · TypeScript · Tailwind CSS 4 · Artplayer |
| 数据库 | MySQL 8.0 |
| 缓存 | Redis（可选） |

## 项目结构

```
web-ai/
├── backend/          # Laravel 后端
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   # API 控制器
│   │   │   └── Middleware/        # VIP 鉴权中间件
│   │   └── Models/                # Eloquent 模型
│   ├── database/
│   │   ├── migrations/            # 数据表迁移
│   │   └── seeders/               # 测试数据
│   └── routes/api.php             # API 路由
├── frontend/         # Vue 3 前端
│   └── src/
│       ├── api/                   # Axios HTTP 封装
│       ├── components/            # 通用组件
│       ├── stores/                # Pinia 状态管理
│       └── views/                 # 页面视图
└── VOD-VIP-开发需求文档.md
```

## 快速开始

### 1. 后端

```bash
cd backend

# 配置环境变量
cp .env.example .env
# 编辑 .env 设置 DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 安装依赖 & 初始化
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed

# 启动开发服务
php artisan serve
```

### 2. 前端

```bash
cd frontend

npm install
npm run dev
```

前端默认运行在 `http://localhost:5173`，已配置代理将 `/api` 请求转发到后端 `http://127.0.0.1:8000`。

### 3. 测试账号

| 角色 | 邮箱 | 密码 |
| --- | --- | --- |
| VIP 会员 | admin@example.com | password |
| 普通用户 | user@example.com | password |

## API 接口清单

### 公开接口

| 方法 | 路径 | 说明 |
| --- | --- | --- |
| POST | /api/register | 用户注册 |
| POST | /api/login | 用户登录 |
| GET | /api/categories | 分类列表 |
| GET | /api/videos | 视频列表（支持分页、分类、搜索） |
| GET | /api/videos/{id} | 视频详情（含 VIP 权限判断） |
| GET | /api/vip/plans | VIP 套餐列表 |
| POST | /api/payment/callback | 支付回调 |

### 需登录接口（Bearer Token）

| 方法 | 路径 | 说明 |
| --- | --- | --- |
| POST | /api/logout | 退出登录 |
| GET | /api/me | 当前用户信息 |
| POST | /api/vip/order | 创建 VIP 订单 |
| GET | /api/vip/orders | 我的订单列表 |

## 核心业务逻辑

### VIP 鉴权流程

1. 请求视频详情 `/api/videos/{id}`
2. 视频 `is_vip = 0` → 直接返回 `video_url`
3. 视频 `is_vip = 1` → 检查用户 `vip_expired_at > now()`
   - 通过 → 返回 `video_url`，`can_play_full = true`
   - 不通过 → 仅返回 `preview_url`，`can_play_full = false`

### 前端播放器拦截

非 VIP 用户播放 VIP 视频时，播放到第 30 秒自动暂停并弹出 VIP 充值遮罩。

### 续费规则

- 会员期内续费：从原到期时间累加
- 会员过期续费：从当前时间重新计算
