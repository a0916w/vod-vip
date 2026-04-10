<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const navItems = [
  { to: '/', label: '仪表盘', icon: '📊' },
  { to: '/videos', label: '视频管理', icon: '🎬' },
  { to: '/categories', label: '分类管理', icon: '📁' },
  { to: '/users', label: '用户管理', icon: '👥' },
  { to: '/orders', label: '订单管理', icon: '💰' },
  { to: '/site-settings', label: '站点设置', icon: '⚙️' },
  { to: '/marquees', label: '浮动文字', icon: '📢' },
  { to: '/media', label: '媒体资源', icon: '📥' },
]

onMounted(async () => {
  if (!auth.ready) await auth.fetchUser()
})

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex min-h-screen bg-gray-950 text-white">
    <!-- 侧边栏 -->
    <aside class="fixed inset-y-0 left-0 z-40 w-56 border-r border-gray-800 bg-gray-900/50">
      <div class="flex h-14 items-center gap-2 border-b border-gray-800 px-4">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 text-xs font-black text-black">A</div>
        <span class="font-bold">VOD-VIP 后台</span>
      </div>
      <nav class="space-y-1 p-3">
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
      <div class="absolute bottom-0 left-0 right-0 border-t border-gray-800 p-3">
        <div class="mb-2 flex items-center gap-2 px-3 text-sm text-gray-400">
          <div class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-500/20 text-xs font-bold text-amber-400">
            {{ auth.user?.nickname?.charAt(0) }}
          </div>
          <span class="truncate">{{ auth.user?.nickname }}</span>
        </div>
        <button @click="handleLogout" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-500 transition hover:bg-gray-800 hover:text-red-400">
          退出登录
        </button>
      </div>
    </aside>

    <!-- 内容区 -->
    <main class="ml-56 flex-1 p-6">
      <RouterView />
    </main>
  </div>
</template>
