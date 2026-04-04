<script setup lang="ts">
const navItems = [
  { to: '/admin', label: '仪表盘', icon: '📊' },
  { to: '/admin/videos', label: '视频管理', icon: '🎬' },
  { to: '/admin/categories', label: '分类管理', icon: '📁' },
  { to: '/admin/users', label: '用户管理', icon: '👥' },
  { to: '/admin/orders', label: '订单管理', icon: '💰' },
  { to: '/admin/media', label: '媒体资源', icon: '📥' },
]
</script>

<template>
  <div class="flex min-h-[calc(100vh-57px)]">
    <!-- 侧边栏 -->
    <aside class="w-56 shrink-0 border-r border-gray-800 bg-gray-900/50">
      <div class="p-4">
        <h2 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-500">后台管理</h2>
        <nav class="space-y-1">
          <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            :class="[
              'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition',
              $route.path === item.to
                ? 'bg-amber-500/10 text-amber-400 font-medium'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white'
            ]"
          >
            <span>{{ item.icon }}</span>
            {{ item.label }}
          </RouterLink>
        </nav>
      </div>
      <div class="mt-auto border-t border-gray-800 p-4">
        <RouterLink to="/" class="flex items-center gap-2 text-sm text-gray-500 transition hover:text-white">
          ← 返回前台
        </RouterLink>
      </div>
    </aside>

    <!-- 内容区 -->
    <div class="flex-1 p-6">
      <RouterView />
    </div>
  </div>
</template>
