<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Token
    |--------------------------------------------------------------------------
    | 从 @BotFather 获取的 Bot Token
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Bot API 地址
    |--------------------------------------------------------------------------
    | 标准 API: https://api.telegram.org （默认，文件下载限 20MB）
    | Local Server: http://127.0.0.1:8081  （自建，文件下载限 2GB）
    |
    | 搭建 Local Server:
    |   docker run -d --name telegram-bot-api \
    |     -p 8081:8081 \
    |     -e TELEGRAM_API_ID=你的api_id \
    |     -e TELEGRAM_API_HASH=你的api_hash \
    |     -v /path/to/data:/var/lib/telegram-bot-api \
    |     aiogram/telegram-bot-api
    |
    | api_id / api_hash 在 https://my.telegram.org 获取
    */
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),

    /*
    |--------------------------------------------------------------------------
    | 是否使用 Local Server 模式
    |--------------------------------------------------------------------------
    | Local Server 下文件直接保存到本地目录，无需通过 HTTP 下载
    | 自动根据 api_base_url 判断（非 api.telegram.org 即为 local）
    */
    'use_local_server' => env('TELEGRAM_USE_LOCAL_SERVER', false),

    /*
    |--------------------------------------------------------------------------
    | 白名单用户 ID
    |--------------------------------------------------------------------------
    | 允许使用 Bot 的 Telegram 用户 ID 列表
    | 为空则不限制（不推荐）
    | 获取方式: 向 @userinfobot 发送消息获取你的 ID
    */
    'allowed_user_ids' => array_filter(
        array_map('intval', explode(',', env('TELEGRAM_ALLOWED_USERS', '')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Webhook 配置
    |--------------------------------------------------------------------------
    */
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', ''),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
];
