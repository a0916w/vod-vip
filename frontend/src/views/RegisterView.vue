<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const nickname = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''
  if (password.value !== passwordConfirmation.value) {
    error.value = '两次密码不一致'
    return
  }
  loading.value = true
  try {
    await auth.register(nickname.value.trim(), password.value, passwordConfirmation.value)
    router.push('/')
  } catch (err: any) {
    const data = err.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join('；')
    } else {
      error.value = data?.message || '注册失败，请重试'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="mx-auto mt-16 max-w-md">
    <div class="rounded-2xl border border-white/12 bg-white/[0.07] p-8">
      <h2 class="mb-2 text-center text-2xl font-bold">注册账号</h2>
      <p class="mb-6 text-center text-sm text-gray-500">设置你的用户名和密码</p>

      <div v-if="error" class="mb-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ error }}</div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="mb-1 block text-sm text-gray-400">用户名</label>
          <input
            v-model="nickname"
            type="text"
            required
            autofocus
            class="w-full rounded-lg border border-white/16 bg-[#1a2940]/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-400"
            placeholder="请输入用户名"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-400">密码</label>
          <input
            v-model="password"
            type="password"
            required
            minlength="6"
            class="w-full rounded-lg border border-white/16 bg-[#1a2940]/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-400"
            placeholder="至少 6 位密码"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-400">确认密码</label>
          <input
            v-model="passwordConfirmation"
            type="password"
            required
            class="w-full rounded-lg border border-white/16 bg-[#1a2940]/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-400"
            placeholder="再次输入密码"
          />
        </div>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-gradient-to-r from-amber-400 to-orange-500 py-2.5 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25 disabled:opacity-50"
        >
          {{ loading ? '注册中...' : '注册' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-500">
        已有账号？
        <RouterLink to="/login" class="text-amber-400 hover:underline">去登录</RouterLink>
      </p>
    </div>
  </div>
</template>
