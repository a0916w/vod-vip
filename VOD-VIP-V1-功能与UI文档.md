# VOD-VIP 系统 — V1 功能与 UI 规格文档

> **版本**: 1.0  
> **日期**: 2026-04-10  
> **适用范围**: 前端（用户站）、后台管理面板、后端 API、基础设施

---

## 目录

1. [系统概览](#1-系统概览)
2. [技术栈](#2-技术栈)
3. [全局设计规范](#3-全局设计规范)
4. [前端（用户站）](#4-前端用户站)
5. [后台管理面板](#5-后台管理面板)
6. [后端 API](#6-后端-api)
7. [数据库模型](#7-数据库模型)
8. [安全机制](#8-安全机制)
9. [视频采集与转码](#9-视频采集与转码)
10. [站点可配置项](#10-站点可配置项)
11. [部署与基础设施](#11-部署与基础设施)

---

## 1. 系统概览

VOD-VIP 是一个视频点播会员平台，核心逻辑为「内容付费」：

- **访客** 可浏览视频列表、查看详情，VIP 视频可试看（默认 30 秒）
- **普通用户** 可播放免费视频、试看 VIP 视频
- **VIP 会员** 可播放全部视频，使用收藏功能
- **管理员** 通过后台管理面板管理全站内容

视频通过 Telegram Bot 采集后自动转码为 HLS AES-128 加密流播放。

---

## 2. 技术栈

| 层 | 技术 |
|---|---|
| 后端 | PHP 8.2+, Laravel, MySQL 8.0 |
| 前端（用户站） | Vue 3, TypeScript, Vite, Tailwind CSS 4 |
| 后台管理面板 | Vue 3, TypeScript, Vite, Tailwind CSS |
| 播放器 | Artplayer + hls.js（懒加载） |
| 转码 | FFmpeg（HLS AES-128 加密，1 秒分片） |
| 视频采集 | Telegram Bot API（支持 Local Server） |
| 部署 | Nginx（纯 SPA 静态托管）+ PHP-FPM + systemd |
| 认证 | Laravel Sanctum（Bearer Token） |

---

## 3. 全局设计规范

### 3.1 配色方案

| 用途 | 色值 | Tailwind |
|---|---|---|
| 主强调色 | 琥珀→橙渐变 | `from-amber-400 to-orange-500` |
| 按钮/高亮 | 琥珀色 | `amber-500` |
| VIP 标识 | 琥珀→橙渐变 | `from-amber-400 to-orange-500` |
| 页面背景 | 深蓝渐变 | `#0f1b2d → #15253d` 多层径向渐变 |
| 卡片/表面 | 半透明白 | `bg-white/[0.07]` ~ `bg-white/[0.12]` |
| 文字主色 | 浅灰白 | `#f8fafc`（`slate-50`） |
| 文字次级 | 灰色 | `text-gray-400` / `text-gray-500` |
| 错误/删除 | 红色 | `red-400` / `red-500` |
| 收藏已选 | 红色 | `border-red-500/40 bg-red-500/10 text-red-400` |

### 3.2 字体

```
Inter, -apple-system, PingFang SC, Microsoft YaHei, sans-serif
```

通过 Google Fonts 加载 Inter（400/500/600/700/900 权重）。

### 3.3 圆角

| 元素 | 圆角 |
|---|---|
| 大卡片 | `rounded-2xl` / `rounded-[24px]` / `rounded-[28px]` |
| 按钮 | `rounded-full` |
| 小卡片 | `rounded-md sm:rounded-lg` |
| 播放器（桌面端） | `rounded-xl` |

### 3.4 响应式断点

采用 Tailwind 默认断点：`sm:640px` / `md:768px` / `lg:1024px` / `xl:1280px`。

### 3.5 加载状态

统一使用琥珀色旋转圆环：`h-8 w-8 border-2 border-gray-600 border-t-amber-500 animate-spin`。

### 3.6 内容容器

主内容区：`max-w-7xl mx-auto px-4 py-5 sm:px-5 sm:py-7`。

---

## 4. 前端（用户站）

### 4.1 应用壳层（App.vue）

#### 导航栏

- **定位**: `sticky top-0 z-[1000]`
- **背景**: `bg-white/[0.09] backdrop-blur-xl shadow-[0_8px_30px_rgba(15,23,42,0.12)]`
- **Logo**: 优先使用站点配置的 `logo_image_url`，否则显示 `brand_badge` 文字（琥珀渐变徽标）+ `site_name`
- **桌面端导航**（`md:flex`）:
  - 「首页」链接
  - 「我的收藏」（仅登录且 VIP 用户可见）
  - 搜索图标 → `/search`
  - 用户头像（昵称首字）+ VIP 小标 → `/account`
  - 「退出」按钮
  - 未登录时显示「登录」「注册」按钮
- **移动端导航**（`md:hidden`）:
  - 汉堡按钮切换菜单
  - 菜单以 **overlay 浮层** 展开（不推挤内容）：`absolute inset-x-0 top-full`
  - 背景: `bg-[#1e2d42]`（明亮不透明）
  - 文字: `text-gray-200`
  - 包含：首页、我的收藏、搜索、账户、退出登录
  - 退出登录按钮与其他菜单项之间**无分隔线**
  - 动画：200ms 淡入下移 / 150ms 淡出上移

#### 跑马灯

- 当 `marquees` 有数据时显示
- 全宽橙色横条 `bg-[#ff9800]`
- 双份文字无限滚动，动画 28s 线性

#### 页脚

- 背景: `bg-white/[0.07]`
- 布局: `md:grid-cols-10`，左侧 Logo + 链接，右侧版权
- 版权文字: 来自站点配置 `footer_text`

#### 全局行为

- `apiSiteSettings` 加载站点配置后动态设置 `document.title` 和 `favicon`
- 路由变化时更新页面标题
- `auth.fetchUser()` 恢复登录状态

---

### 4.2 页面详情

#### 4.2.1 首页（`/`，HomeView）

**功能**:
- 搜索区：可配置的提示文案（最后 4 字高亮样式可自定义颜色/字号/字重）
- 最新更新视频网格
- 为你推荐视频网格
- 分类入口网格

**UI**:
- 搜索框: `max-w-xl`，圆角 `rounded-full`，背景 `bg-[#1a2940]/58`，右侧琥珀搜索按钮
- 区块标题: 左侧竖条（琥珀/玫瑰/蓝色）
- 视频网格: `grid-cols-2 md:grid-cols-3 lg:grid-cols-4`

**API**: `apiLatestVideos`、`apiRecommendedVideos`、`apiCategories`、`apiSiteSettings`、`apiBatchCheckFavorites`

---

#### 4.2.2 分类浏览（`/browse`，BrowseView）

**功能**:
- 分类选择卡片网格
- 选中分类后显示视频列表 + 分页
- URL 查询参数: `?cat=<id|all>&q=<keyword>`

**UI**:
- 未选分类: 显示分类卡片 `rounded-2xl bg-white/[0.08]`
- 分类 pill 激活态: `bg-amber-400 text-black shadow-[0_10px_30px_rgba(251,191,36,0.35)]`
- 视频网格: `grid-cols-2 md:grid-cols-3 lg:grid-cols-4`
- 分页组件（`Pagination`）

**API**: `apiCategories`、`apiVideos`（分页 12 条/页）

---

#### 4.2.3 搜索（`/search`，SearchView）

**功能**:
- 关键词搜索，URL 参数 `?q=`
- 搜索结果网格 + 总数 + 分页
- 未搜索时显示提示文字

**UI**:
- 搜索框: 圆角输入框 + 右侧按钮
- 结果显示: 灰色总数提示 + VideoCard 网格

**API**: `apiVideos`（keyword + 分页）

---

#### 4.2.4 视频详情（`/video/:id`，VideoView）

**功能**:
- 视频播放器（Artplayer + HLS.js 懒加载）
- 转码状态提示（pending/processing 时显示）
- VIP 试看机制（到达试看秒数后暂停并弹出 VipOverlay）
- 试看进度条标记（琥珀色）
- 收藏功能（需登录 + VIP）
- 推荐视频网格

**播放器**:
- 主题色: `#f59e0b`（琥珀）
- 功能: 画中画、全屏、网页全屏、倍速播放、设置、翻转、宽高比
- HLS: 通过 `xhrSetup` 拦截 `http://placeholder/key` 请求，替换为 API 签名密钥 URL
- Safari: 原生 HLS 支持回退

**布局**:
- 播放器区域: 移动端全宽（负外边距 `-mx-4`，宽度 `calc(100%+2rem)`），桌面端正常宽度 + `rounded-xl`
- 标题: `text-base font-medium sm:text-lg`，VIP 标识琥珀渐变小标
- 元信息: `text-xs text-gray-500`（分类、播放次数、时长）
- 收藏按钮: 圆角 pill `rounded-full`，已收藏红色态 / 未收藏灰色态
- 推荐视频: `grid-cols-2 md:grid-cols-3 lg:grid-cols-4`

**VIP 试看逻辑**:
1. 非 VIP 用户观看 VIP 视频时，视频正常播放
2. 播放到 `vip_trial_seconds`（默认 30 秒）后自动暂停
3. 弹出 VipOverlay 全屏模态弹窗
4. 进度条上显示试看时长标记（琥珀色竖线 + 文字）

**API**: `apiVideoDetail`、`apiVideos`（推荐）、`apiToggleFavorite`

---

#### 4.2.5 登录（`/login`，LoginView）

**功能**:
- 账号密码登录
- 一键快捷注册（生成随机账号密码，弹窗显示并可复制）

**UI**:
- 居中卡片 `max-w-md mt-16`
- 卡片: `rounded-2xl border border-white/12 bg-white/[0.07]`
- 主按钮: 琥珀→橙渐变
- 快捷注册区: `border border-amber-500/50 bg-amber-500/10`
- 成功弹窗: Teleport 到 body 的全屏模态

**API**: `auth.login`、`auth.quickRegister`

---

#### 4.2.6 注册（`/register`，RegisterView）

**功能**: 昵称 + 密码 + 确认密码注册

**UI**: 与登录页一致的卡片样式

**API**: `auth.register`

---

#### 4.2.7 个人中心（`/account`，AccountView）

**功能**:
- 用户信息展示（头像首字、昵称、VIP 状态/到期时间）
- 三列统计（购买数、收藏数、账号类型）
- 四张入口卡片（充值、订单记录、我的收藏、浏览历史）

**UI**:
- 主区: `rounded-[28px] bg-white/[0.08]`
- 网格: `md:grid-cols-2 xl:grid-cols-4`
- 充值卡片: 琥珀渐变边框高亮

**路由守卫**: 需登录

---

#### 4.2.8 订单记录（`/account/orders`，OrderHistoryView）

**功能**: 订单列表卡片，显示状态/金额

**UI**:
- 状态 pill: 彩色标签
- 金额: 琥珀色
- 订单行: `rounded-[24px]`

**路由守卫**: 需登录

---

#### 4.2.9 收藏记录（`/account/favorites`，FavoriteHistoryView）

**功能**: 非 VIP 时引导开通，否则显示收藏视频列表 + 分页

**路由守卫**: 需登录

---

#### 4.2.10 我的收藏（`/favorites`，FavoritesView）

**功能**: 收藏视频网格 + 总数 + 分页

**路由守卫**: 需登录 + VIP（非 VIP 重定向到 `/vip`）

---

#### 4.2.11 VIP 充值（`/vip`，VipView）

**功能**:
- VIP 套餐选择（月度/季度/年度）
- 支付方式选择（微信/支付宝）
- 创建订单
- 历史订单表格
- 支付二维码模态弹窗

**UI**:
- 头区渐变: `from-[#332113]/65 via-[#2a2536]/58 to-[#1d2f44]/62`
- 套餐选中: `border-amber-400/80 bg-amber-400/12`
- 微信: 绿色态；支付宝: 蓝色态

**路由守卫**: 需登录

**API**: `apiVipPlans`、`apiCreateOrder`、`apiMyOrders`

---

#### 4.2.12 404 页面（`/:pathMatch(.*)*`，NotFoundView）

**功能**: 404 提示 + 回首页按钮

**UI**: `text-7xl font-black text-gray-800`，琥珀渐变按钮

---

### 4.3 公共组件

#### VideoCard

- 封面图（hover 放大 `scale-105`）+ VIP 角标 + 收藏浮动按钮 + 时长标签
- 标题 + 分类/播放次数
- 边框: `border-white/24 bg-[#1a2940]/60`
- hover: `border-amber-300/80 ring-1`
- Props: `video: Video`、`favorited?: boolean`
- Events: `favoriteChanged(videoId, isFavorited)`

#### VipOverlay

- **全屏模态弹窗**（Teleport 到 body）
- 遮罩: `fixed inset-0 z-[9999] bg-black/60 backdrop-blur-sm`
- 卡片: `bg-[#0f1729]`，圆角 `rounded-2xl`
- 内容: 锁图标 🔒、标题「VIP 专属内容」、说明、试看秒数显示
- **关闭按钮**: 右上角 X 按钮
- **点击遮罩关闭**: `@click.self="emit('close')"`
- 未登录时显示「去登录」+ 「立即开通 VIP」两个按钮
- 已登录时只显示「立即开通 VIP」
- Props: `trialSeconds?: number`
- Events: `close`

#### Pagination

- 上一页 / 智能页码（≤7 全显，否则省略号折叠）/ 下一页
- 当前页: `bg-amber-500 font-bold text-black`
- 其他页: `bg-gray-800`
- `lastPage <= 1` 时不渲染
- Props: `currentPage`、`lastPage`
- Events: `change(page)`

---

### 4.4 状态管理（Pinia）

#### auth store

| 状态 | 说明 |
|---|---|
| `user` | 当前用户对象 |
| `token` | Bearer token（同步 `localStorage['token']`） |
| `isVip` | 是否 VIP |
| `isLoggedIn` | 是否已登录（计算属性） |
| `userLoaded` | 用户数据是否已加载 |

**方法**: `login`、`register`、`quickRegister`、`fetchUser`（防并发单飞）、`waitUntilReady`、`logout`

---

### 4.5 API 层

- 基础: Axios，`baseURL: '/api'`，`timeout: 15000`
- 请求拦截: 自动附加 `Authorization: Bearer <token>`
- 响应拦截: 检测加密响应 `{ _e: string }`，使用 Web Crypto API（AES-CBC）解密
- 401 处理: 自动登出并跳转登录页

---

## 5. 后台管理面板

### 5.1 壳层（AdminLayout）

- **侧边栏**: 固定 `w-56`，`bg-gray-900/50`，`border-r border-gray-800`
- **Logo**: 渐变方块 "A" + 「VOD-VIP 后台」
- **导航项**: 仪表盘、视频、分类、用户、订单、站点设置、浮动文字、媒体资源
- **当前路由高亮**: `bg-amber-500/10 text-amber-400`
- **底部**: 用户昵称首字头像 + 退出按钮
- **主内容区**: `ml-56 flex-1 p-6`

### 5.2 登录（`/login`）

- 居中卡片，账号 + 密码登录
- 仅允许 `is_admin` 用户登录，否则报错
- Token 存储在 `localStorage['admin_token']`

### 5.3 仪表盘（`/`）

**数据卡片**（4 列网格）:
- 总用户 + VIP 数
- 视频总数 + VIP 视频数
- 总收入 + 订单笔数
- 采集资源总数 + 待处理数

**列表区块**（2 列）:
- 最近注册用户（昵称、邮箱、VIP 标签）
- 最近订单（用户、套餐、金额）

### 5.4 视频管理（`/videos`）

**功能**: CRUD + 搜索 + 预览播放 + 重新转码

**表格列**: ID、预览图（失败用 SVG 占位）、标题、分类、VIP/免费、转码状态（中文 + 颜色标签）、播放次数、操作

**表单字段**:
- `title`（必填）
- `video_url`（必填）
- `cover_url`（必填，实时预览）
- `preview_url`（可选）
- `category_id`（下拉选择）
- `is_vip`（布尔）
- `duration`（数字）
- `description`（文本域）

**操作**: 预览（Artplayer 播放弹窗）、编辑、重新转码（有 video_url 时）、删除

### 5.5 分类管理（`/categories`）

**功能**: CRUD（无分页，一次加载全部）

**表格列**: ID、名称、Slug、视频数、操作

**表单字段**: `name`（必填）、`slug`（必填，唯一）、`sort_order`（数字）

**约束**: 有关联视频的分类不可删除

### 5.6 用户管理（`/users`）

**功能**: 分页列表 + 搜索 + 切换 VIP + 切换管理员 + 删除

**表格列**: ID、昵称、创建时间、上次登录、VIP（可切换）、管理员（可切换）、操作

**操作**: 点击切换 VIP/管理员状态、删除（不可删除自己）

### 5.7 订单管理（`/orders`）

**功能**: 只读分页列表

**表格列**: 订单号、用户昵称、套餐、金额、状态（待支付/已支付/已取消）、时间

### 5.8 站点设置（`/site-settings`）

**功能**: 单页表单 + 右侧实时预览

**字段详见 [第 10 节](#10-站点可配置项)**

### 5.9 浮动文字/跑马灯管理（`/marquees`）

**功能**: CRUD，上方表单 + 下方列表

**表单字段**: `content`（文本域，255 字）、`sort_order`（数字）、`is_active`（开关）

**表格列**: 排序、内容（截断）、状态（启用/停用）、操作

### 5.10 媒体资源管理（`/media`）

**功能**: Telegram 采集媒体分页列表 + 同步到视频库 + 删除

**表格列**: ID、类型（video/image/document 着色）、文件名、大小、来源用户名、状态（待下载/已同步/待同步）、操作

**同步弹窗表单**: `title`、`category_id`（下拉）、`is_vip`（布尔）

**同步条件**: 类型为 video、未同步、非「待下载」状态

### 5.11 404 页面

大号 404 文字 + 「返回仪表盘」链接

---

## 6. 后端 API

### 6.1 公开端点（无需认证）

| 方法 | 路径 | 说明 |
|---|---|---|
| POST | `/api/register` | 用户注册（nickname + password + password_confirmation） |
| POST | `/api/quick-register` | 快捷注册（自动生成随机昵称密码） |
| POST | `/api/login` | 登录（account + password），返回 token |
| GET | `/api/categories` | 分类列表（含 videos_count），按 sort_order 排序 |
| GET | `/api/marquees` | 活跃跑马灯列表 |
| GET | `/api/site-settings` | 站点公开配置键值对 |
| GET | `/api/videos` | 视频分页（支持 category_id/is_vip/keyword/per_page 筛选） |
| GET | `/api/videos/latest` | 最新 8 条视频 |
| GET | `/api/videos/recommended` | 随机推荐 8 条视频 |
| GET | `/api/videos/{id}` | 视频详情（含播放地址、权限判断、试看秒数） |
| GET | `/api/vip/plans` | VIP 套餐列表（月度/季度/年度） |
| POST | `/api/payment/callback` | 第三方支付回调 |
| GET | `/api/hls/key/{videoId}` | HLS 解密密钥（签名校验，二进制返回） |
| POST | `/api/telegram/webhook` | Telegram Bot Webhook |

### 6.2 需登录端点（`auth:sanctum`）

| 方法 | 路径 | 说明 |
|---|---|---|
| POST | `/api/logout` | 登出（删除当前 token） |
| GET | `/api/me` | 当前用户信息 + VIP 状态 + 管理员标识 |
| POST | `/api/vip/order` | 创建 VIP 订单（plan + payment_method） |
| GET | `/api/vip/orders` | 我的订单列表（分页 10 条/页） |
| POST | `/api/favorites/{videoId}` | 切换收藏状态（需 VIP，否则 403） |
| GET | `/api/favorites` | 收藏列表（分页，含分类关联） |
| GET | `/api/favorites/check/{videoId}` | 检查单个视频收藏状态 |
| POST | `/api/favorites/batch-check` | 批量检查收藏状态 |
| GET | `/api/media` | 媒体资源列表 |
| GET | `/api/media/{id}` | 媒体资源详情 |
| POST | `/api/media/{id}/sync` | 同步媒体到视频库 |
| DELETE | `/api/media/{id}` | 删除媒体资源 |

### 6.3 管理端点（`auth:sanctum` + `admin`，前缀 `/api/admin`）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/admin/dashboard` | 仪表盘统计数据 |
| GET/POST | `/admin/videos` | 视频列表 / 新增 |
| GET/PUT/DELETE | `/admin/videos/{id}` | 视频详情 / 更新 / 删除 |
| POST | `/admin/videos/{id}/retranscode` | 重新转码 |
| GET/POST | `/admin/categories` | 分类列表 / 新增 |
| PUT/DELETE | `/admin/categories/{id}` | 更新 / 删除分类 |
| GET | `/admin/users` | 用户列表（支持 keyword/vip_only 筛选） |
| PUT/DELETE | `/admin/users/{id}` | 更新 / 删除用户 |
| GET | `/admin/orders` | 订单列表（支持 status/keyword 筛选） |
| GET/PUT | `/admin/site-settings` | 获取 / 更新站点设置 |
| GET/POST | `/admin/marquees` | 跑马灯列表 / 新增 |
| PUT/DELETE | `/admin/marquees/{id}` | 更新 / 删除跑马灯 |
| GET/POST/DELETE | `/admin/media` | 媒体列表 / 同步 / 删除 |

### 6.4 视频详情 API 响应结构

```json
{
  "id": 30,
  "title": "视频标题",
  "cover_url": "https://domain/storage/covers/xxx.jpg",
  "is_vip": true,
  "can_play_full": false,
  "play_url": "https://domain/storage/hls/30/index.m3u8",
  "play_type": "hls",
  "key_url": "https://domain/api/hls/key/30?expires=xxx&signature=xxx",
  "transcode_status": "done",
  "preview_url": null,
  "description": "视频描述",
  "duration": 120,
  "view_count": 100,
  "category": { "id": 1, "name": "分类名" },
  "vip_required_message": "开通 VIP 观看完整版",
  "vip_trial_seconds": 30,
  "is_favorited": false,
  "created_at": "2026-04-01T00:00:00.000000Z"
}
```

### 6.5 VIP 权限判定逻辑

1. 获取视频详情请求
2. 判断 `is_vip` 和用户 VIP 状态
3. `can_play_full = !is_vip || user.isVip()`
4. 完全权限: 返回完整 `play_url`
5. 无完全权限: 优先返回 `preview_url`，无则返回 HLS/MP4 原始地址（前端负责试看时间控制）
6. HLS 密钥: 完全权限或需要试看密钥时返回签名 `key_url`
7. `play_type`: HLS 已转码返回 `hls`，否则 `mp4`

### 6.6 HLS 域名覆盖

当后台配置了 `hls_base_url` 时，HLS 视频的 `play_url` 使用该域名拼接（用于 CDN 加速），其余媒体仍使用默认 `MEDIA_BASE_URL`。

---

## 7. 数据库模型

### 7.1 users 表

| 字段 | 类型 | 说明 |
|---|---|---|
| id | int | 主键 |
| nickname | varchar | 用户昵称 |
| email | varchar, nullable, unique | 邮箱 |
| phone | varchar, nullable | 手机号 |
| password | varchar | 加密密码（bcrypt） |
| avatar | varchar, nullable | 头像 URL |
| vip_level | int | 0=普通, 1=VIP |
| vip_expired_at | datetime, nullable | VIP 到期时间 |
| last_login_at | datetime, nullable | 上次登录时间 |
| is_admin | boolean | 管理员标识 |

**关联**: `orders` (HasMany)、`favorites` (BelongsToMany Video)

**方法**: `isVip()` — `vip_level >= 1 && vip_expired_at > now()`

### 7.2 videos 表

| 字段 | 类型 | 说明 |
|---|---|---|
| id | int | 主键 |
| title | varchar | 视频标题 |
| cover_url | varchar | 封面图路径 |
| video_url | varchar | 原始 MP4 相对路径 |
| hls_path | varchar, nullable | HLS m3u8 相对路径 |
| hls_key | varchar(32), nullable, hidden | AES-128 密钥（hex） |
| transcode_status | varchar(20), nullable | pending / processing / done / failed |
| preview_url | varchar, nullable | 试看片段路径 |
| is_vip | tinyint | 0=免费, 1=VIP |
| category_id | int, FK | 分类 ID |
| description | text, nullable | 视频描述 |
| duration | int | 时长（秒） |
| view_count | int | 播放次数 |

### 7.3 categories 表

| 字段 | 类型 | 说明 |
|---|---|---|
| id | int | 主键 |
| name | varchar | 分类名称 |
| slug | varchar, unique | 分类别名 |
| sort_order | int | 排序权重 |

### 7.4 orders 表

| 字段 | 类型 | 说明 |
|---|---|---|
| id | int | 主键 |
| order_no | varchar | 订单号 |
| user_id | int, FK | 用户 ID |
| plan_name | varchar | 套餐名（monthly/quarterly/yearly） |
| months | int | 月数 |
| amount | decimal | 金额 |
| status | tinyint | 0=待支付, 1=已支付, 2=已取消 |
| payment_method | varchar | wechat / alipay |
| transaction_id | varchar, nullable | 第三方交易号 |
| paid_at | datetime, nullable | 支付时间 |

### 7.5 favorites 表

| 字段 | 类型 | 说明 |
|---|---|---|
| user_id | int, FK | 用户 ID |
| video_id | int, FK | 视频 ID |
| created_at | datetime | 收藏时间 |

**唯一约束**: (user_id, video_id)

### 7.6 media_resources 表

| 字段 | 类型 | 说明 |
|---|---|---|
| id | int | 主键 |
| telegram_file_id | varchar | Telegram 文件唯一 ID |
| file_type | enum | image / video / document |
| file_name | varchar | 原始文件名 |
| local_path | varchar | 本地存储相对路径 |
| file_size | bigint | 文件大小（字节） |
| caption | text, nullable | Telegram 附文 |
| from_user_id | bigint, nullable | 发送者 Telegram ID |
| from_username | varchar, nullable | 发送者用户名 |
| synced_to_video | boolean | 是否已同步到视频表 |

### 7.7 site_settings 表

| 字段 | 类型 | 说明 |
|---|---|---|
| key | varchar, unique | 配置键名 |
| value | text | 配置值 |

### 7.8 marquee_items 表

| 字段 | 类型 | 说明 |
|---|---|---|
| content | text | 公告内容 |
| is_active | boolean | 是否启用 |
| sort_order | int | 排序权重 |

---

## 8. 安全机制

### 8.1 API 响应加密

- **算法**: AES-256-CBC
- **密钥**: `.env` 中 `API_ENCRYPT_KEY`（64 位 hex = 32 字节）
- **格式**: `{ "_e": base64(IV + ciphertext) }`
- **前端**: Web Crypto API 解密
- **排除路径**: `/api/admin/*`、`/api/telegram/*`、`/api/payment/*`、`/api/hls/*`、`/api/site-settings`
- 若密钥未配置则自动关闭加密，原样返回 JSON

### 8.2 HLS 视频加密

- **算法**: AES-128（FFmpeg 内置 HLS 加密）
- **密钥**: 每个视频独立 16 字节随机密钥，数据库存储 hex
- **密钥获取**: 签名 URL `/api/hls/key/{id}?expires={ts}&signature={hmac}`
- **签名算法**: `HMAC-SHA256("{videoId}:{expires}", HLS_KEY_SECRET)`
- **有效期**: 默认 6 小时
- **m3u8 中 URI**: `http://placeholder/key`（前端 hls.js 拦截替换为签名 URL）
- `hls_key` 字段设为 `$hidden`，不会泄露到 API 响应

### 8.3 认证

- **方式**: Laravel Sanctum Bearer Token
- **登录逻辑**: 删除旧 token、创建新 token、更新 `last_login_at`
- **401 处理**: 前端自动登出并跳转登录页

### 8.4 管理员权限

- `AdminOnly` 中间件验证 `is_admin` 字段
- 管理端 API 全部挂载该中间件

---

## 9. 视频采集与转码

### 9.1 Telegram Bot 采集流程

1. 用户发送视频/图片/文档到 Bot
2. Webhook 校验（可选 secret token）
3. 白名单校验 `TELEGRAM_ALLOWED_USERS`
4. 超大文件（>20MB 标准 API / >2GB Local Server）: 创建 `pending_large_file` 记录
5. 正常文件: `getFile` → 下载到 `storage/app/public/telegram/videos/`
6. 写入 `media_resources` 表
7. 视频类型自动触发 `autoSyncToVideo()`:
   - 创建 `Video` 记录（分类默认为 `tg` slug 或 ID 1）
   - 派发 `TranscodeVideoJob`
8. Bot 回复入库成功 + 转码已排队

### 9.2 HLS 转码参数

```
ffmpeg -y -i input.mp4
  -c:v libx264 -preset fast -crf 23
  -force_key_frames 'expr:gte(t,n_forced*1)'
  -sc_threshold 0
  -c:a aac -b:a 128k
  -hls_time 1
  -hls_list_size 0
  -hls_key_info_file key_info.txt
  -hls_segment_filename seg_%04d.ts
  -f hls index.m3u8
```

- **分片时长**: 1 秒（`-hls_time 1`）
- **强制关键帧**: 每 1 秒（`-force_key_frames`）
- **禁用场景切换检测**: `-sc_threshold 0`
- **输出目录**: `storage/app/public/hls/{video_id}/`
- **队列**: `ShouldQueue`，`tries=1`，`timeout=3600`
- **完成后**: 清理临时密钥文件，更新数据库状态，Telegram 通知

### 9.3 转码状态流转

```
pending → processing → done
                     → failed
```

---

## 10. 站点可配置项

以下配置项通过后台管理面板「站点设置」页面管理：

| 配置键 | 默认值 | 说明 |
|---|---|---|
| `brand_badge` | `VOD` | Logo 左侧徽标文字（≤20 字） |
| `site_name` | `VIP 影院` | 站点名称（≤50 字） |
| `footer_text` | `VOD-VIP. All rights reserved.` | 页脚版权文字（≤120 字） |
| `browser_title` | `VOD-VIP 影院` | 浏览器标签标题（≤80 字） |
| `home_seo_title` | `VOD-VIP 影院 - 精选高清视频点播平台` | 首页 SEO 标题（≤120 字） |
| `logo_image_url` | 空 | Logo 图片 URL（≤500 字） |
| `favicon_url` | `/favicon.ico` | Favicon URL（≤500 字） |
| `hls_base_url` | 空 | HLS 播放域名（CDN，≤500 字） |
| `vip_trial_seconds` | `30` | VIP 视频试看秒数（1–600） |
| `search_hint_text` | 空 | 首页搜索提示文案（≤120 字） |
| `search_hint_color` | `#f8fafc` | 提示文案颜色 |
| `search_hint_font_size` | `14` | 提示文案字号（10–48） |
| `search_hint_font_weight` | `normal` | 提示文案字重（normal/bold） |
| `search_hint_tail_color` | `#f59e0b` | 文案尾部 4 字高亮颜色 |
| `search_hint_tail_font_size` | `14` | 尾部高亮字号（10–48） |
| `search_hint_tail_font_weight` | `bold` | 尾部高亮字重（normal/bold） |

---

## 11. 部署与基础设施

### 11.1 服务器架构

- **操作系统**: Ubuntu 22.04 LTS
- **Web 服务器**: Nginx（SSL，SPA 静态托管，反向代理 PHP-FPM）
- **PHP**: PHP-FPM 8.2+
- **数据库**: MySQL 8.0

### 11.2 Nginx 配置要点

- **前端**: SPA 模式，`try_files $uri /index.html`
- **API**: 反向代理到 PHP-FPM（`127.0.0.1:8000`）
- **静态文件**: `/storage/` alias 到 Laravel 存储目录
- **MP4 优化**: `mp4` 模块启用伪流媒体
- **Gzip**: 启用全类型压缩（JS/CSS/JSON/M3U8 等）
- **后台管理**: 独立端口 9000

### 11.3 systemd 服务

| 服务 | 说明 |
|---|---|
| Laravel 后端 | PHP-FPM / `php artisan serve` |
| 队列 Worker | `php artisan queue:work --sleep=3 --tries=3` |

### 11.4 部署流程

```bash
# 1. 代码同步
git pull

# 2. 后端
cd backend && composer install --no-dev
php artisan migrate --force
php artisan config:clear && php artisan route:clear && php artisan cache:clear

# 3. 前端构建
cd frontend && npm install && npm run build
# 将 dist/ 复制到 Nginx 静态目录

# 4. 后台构建
cd admin && npm install && npm run build
# 将 dist/ 复制到后台 Nginx 目录

# 5. 重启服务
systemctl restart vodvip-queue
```

---

## 版本历史

| 版本 | 日期 | 说明 |
|---|---|---|
| V1.0 | 2026-04-10 | 初始版本，记录所有现有功能与 UI 规格 |
