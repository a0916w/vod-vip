<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

onMounted(() => {
  auth.fetchUser()
})
</script>

<template>
  <div class="flex min-h-screen flex-col bg-gray-950 text-white">
    <!-- 顶部导航 -->
    <nav class="sticky top-0 z-50 border-b border-gray-800/60 bg-gray-950/80 backdrop-blur-lg">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
        <!-- 左侧：Logo + 导航 -->
        <div class="flex items-center gap-6">
          <RouterLink to="/" class="flex items-center gap-2 text-lg font-bold tracking-tight">
            <span class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-2 py-0.5 text-xs font-black text-black">VOD</span>
            VIP 影院
          </RouterLink>
          <div class="hidden items-center gap-1 md:flex">
            <RouterLink to="/" class="rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">首页</RouterLink>
            <RouterLink v-if="auth.isLoggedIn && auth.isVip" to="/favorites" class="rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">我的收藏</RouterLink>
            <RouterLink to="/vip" class="rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">VIP 会员</RouterLink>
          </div>
        </div>

        <!-- 右侧：用户 -->
        <div class="flex items-center gap-3">
          <template v-if="auth.isLoggedIn">
            <div class="flex items-center gap-2">
              <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-xs font-bold text-black">
                {{ auth.user?.nickname?.charAt(0) }}
              </div>
              <span class="hidden text-sm text-gray-300 md:inline">{{ auth.user?.nickname }}</span>
              <span v-if="auth.isVip" class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-400">VIP</span>
            </div>
            <button @click="auth.logout()" class="rounded-lg px-2.5 py-1.5 text-xs text-gray-500 transition hover:bg-white/5 hover:text-red-400">退出</button>
          </template>
          <template v-else>
            <RouterLink to="/login" class="rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">登录</RouterLink>
            <RouterLink to="/register" class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-4 py-1.5 text-sm font-medium text-black transition hover:shadow-lg hover:shadow-amber-500/20">注册</RouterLink>
          </template>
        </div>
      </div>
    </nav>

    <!-- 内容区 -->
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-800/60 bg-gray-950">
      <div class="mx-auto max-w-7xl px-4 py-8">
        <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
          <div class="flex items-center gap-2">
            <span class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-1.5 py-0.5 text-[10px] font-black text-black">VOD</span>
            <span class="text-sm font-medium text-gray-400">VIP 影院</span>
          </div>
          <div class="flex items-center gap-6 text-xs text-gray-600">
            <RouterLink to="/" class="transition hover:text-gray-400">首页</RouterLink>
            <RouterLink to="/vip" class="transition hover:text-gray-400">VIP 套餐</RouterLink>
            <RouterLink v-if="auth.isLoggedIn && auth.isVip" to="/favorites" class="transition hover:text-gray-400">我的收藏</RouterLink>
          </div>
          <div class="text-xs text-gray-700">© 2024 VOD-VIP. All rights reserved.</div>
        </div>
      </div>
    </footer>
  </div>
</template>
