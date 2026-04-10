<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const props = defineProps<{
  trialSeconds?: number
}>()
const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="emit('close')">
      <div class="relative mx-4 w-full max-w-sm rounded-2xl border border-white/14 bg-[#0f1729] p-8 text-center shadow-[0_20px_60px_rgba(2,6,23,0.8)]">
        <button @click="emit('close')" class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition hover:bg-white/10 hover:text-white">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="mb-4 text-5xl">🔒</div>
        <h3 class="mb-2 text-xl font-bold text-white">VIP 专属内容</h3>
        <p class="mb-1 text-sm text-gray-400">开通 VIP 即可解锁完整视频</p>
        <p v-if="props.trialSeconds" class="mb-6 text-xs text-amber-300/90">已试看 {{ props.trialSeconds }} 秒</p>
        <p v-else class="mb-6 text-xs text-gray-500">试看结束</p>
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
  </Teleport>
</template>
