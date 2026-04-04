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
  <div class="min-h-screen bg-gray-950 text-white">
    <!-- 顶部导航 -->
    <nav class="sticky top-0 z-50 border-b border-gray-800 bg-gray-950/90 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
        <RouterLink to="/" class="flex items-center gap-2 text-xl font-bold">
          <span class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-2 py-0.5 text-sm text-black">VOD</span>
          <span>VIP 影院</span>
        </RouterLink>

        <div class="flex items-center gap-4">
          <RouterLink to="/" class="text-sm text-gray-300 transition hover:text-white">首页</RouterLink>

          <template v-if="auth.isLoggedIn">
            <RouterLink to="/favorites" class="text-sm text-gray-300 transition hover:text-white">我的收藏</RouterLink>
            <RouterLink v-if="auth.isAdmin" to="/admin" class="text-sm text-red-400 transition hover:text-red-300">后台管理</RouterLink>
            <RouterLink to="/vip" class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-4 py-1.5 text-sm font-medium text-black transition hover:shadow-lg hover:shadow-amber-500/25">
              {{ auth.isVip ? 'VIP 会员' : '开通 VIP' }}
            </RouterLink>
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-400">{{ auth.user?.nickname }}</span>
              <button @click="auth.logout()" class="text-sm text-gray-500 transition hover:text-red-400">退出</button>
            </div>
          </template>
          <template v-else>
            <RouterLink to="/login" class="text-sm text-gray-300 transition hover:text-white">登录</RouterLink>
            <RouterLink to="/register" class="rounded-full bg-white/10 px-4 py-1.5 text-sm transition hover:bg-white/20">注册</RouterLink>
          </template>
        </div>
      </div>
    </nav>

    <!-- 内容区 -->
    <main class="mx-auto max-w-7xl px-4 py-6">
      <RouterView />
    </main>

    <!-- 底部 -->
    <footer class="border-t border-gray-800 py-8 text-center text-sm text-gray-600">
      © 2024 VOD-VIP 影院 · All rights reserved
    </footer>
  </div>
</template>
