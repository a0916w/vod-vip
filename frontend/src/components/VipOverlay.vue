<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="absolute inset-0 z-[100] flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="rounded-2xl bg-gray-900 p-8 text-center shadow-2xl">
      <div class="mb-4 text-5xl">🔒</div>
      <h3 class="mb-2 text-xl font-bold text-white">VIP 专属内容</h3>
      <p class="mb-6 text-sm text-gray-400">开通 VIP 即可解锁完整视频</p>
      <div class="flex justify-center gap-3">
        <RouterLink
          v-if="!auth.isLoggedIn"
          to="/login"
          class="rounded-full bg-white/10 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-white/20"
        >
          去登录
        </RouterLink>
        <RouterLink
          to="/vip"
          class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-6 py-2.5 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25"
        >
          立即开通 VIP
        </RouterLink>
      </div>
    </div>
  </div>
</template>
