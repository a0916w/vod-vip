<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const mobileMenuOpen = ref(false)

watch(() => route.path, () => { mobileMenuOpen.value = false })

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
          </div>
        </div>

        <!-- 右侧：搜索 + 用户 + 汉堡 -->
        <div class="flex items-center gap-3">
          <RouterLink to="/search" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white/5 hover:text-white" title="搜索">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
          </RouterLink>
          <template v-if="auth.isLoggedIn">
            <div class="hidden items-center gap-2 md:flex">
              <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-xs font-bold text-black">
                {{ auth.user?.nickname?.charAt(0) }}
              </div>
              <span class="text-sm text-gray-300">{{ auth.user?.nickname }}</span>
              <span v-if="auth.isVip" class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-400">VIP</span>
            </div>
            <button @click="auth.logout()" class="hidden rounded-lg px-2.5 py-1.5 text-xs text-gray-500 transition hover:bg-white/5 hover:text-red-400 md:block">退出</button>
          </template>
          <template v-else>
            <RouterLink to="/login" class="hidden rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white md:block">登录</RouterLink>
            <RouterLink to="/register" class="hidden rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-4 py-1.5 text-sm font-medium text-black transition hover:shadow-lg hover:shadow-amber-500/20 md:block">注册</RouterLink>
          </template>
          <!-- 移动端汉堡菜单 -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white/5 hover:text-white md:hidden">
            <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- 移动端菜单面板 -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="mobileMenuOpen" class="border-t border-gray-800/60 bg-gray-950/95 backdrop-blur-lg md:hidden">
          <div class="mx-auto max-w-7xl space-y-1 px-4 py-3">
            <RouterLink to="/" class="block rounded-lg px-3 py-2.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">首页</RouterLink>
            <RouterLink to="/browse" class="block rounded-lg px-3 py-2.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">分类浏览</RouterLink>
            <RouterLink to="/search" class="block rounded-lg px-3 py-2.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">搜索</RouterLink>
            <template v-if="auth.isLoggedIn">
              <RouterLink v-if="auth.isVip" to="/favorites" class="block rounded-lg px-3 py-2.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">我的收藏</RouterLink>
              <RouterLink to="/vip" class="block rounded-lg px-3 py-2.5 text-sm text-amber-400 transition hover:bg-white/5">VIP 会员</RouterLink>
              <div class="my-2 border-t border-gray-800/60"></div>
              <div class="flex items-center gap-2 px-3 py-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-xs font-bold text-black">{{ auth.user?.nickname?.charAt(0) }}</div>
                <span class="text-sm text-gray-300">{{ auth.user?.nickname }}</span>
                <span v-if="auth.isVip" class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-400">VIP</span>
              </div>
              <button @click="auth.logout(); mobileMenuOpen = false" class="block w-full rounded-lg px-3 py-2.5 text-left text-sm text-red-400 transition hover:bg-white/5">退出登录</button>
            </template>
            <template v-else>
              <div class="my-2 border-t border-gray-800/60"></div>
              <RouterLink to="/login" class="block rounded-lg px-3 py-2.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">登录</RouterLink>
              <RouterLink to="/register" class="block rounded-lg px-3 py-2.5 text-sm text-amber-400 transition hover:bg-white/5">注册</RouterLink>
            </template>
          </div>
        </div>
      </Transition>
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
          <div class="text-xs text-gray-700">&copy; {{ new Date().getFullYear() }} VOD-VIP. All rights reserved.</div>
        </div>
      </div>
    </footer>
  </div>
</template>
