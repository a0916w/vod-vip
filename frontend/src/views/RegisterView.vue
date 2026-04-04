<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const nickname = ref('')
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''
  if (!nickname.value.trim()) {
    error.value = '请输入昵称'
    return
  }
  loading.value = true
  try {
    await auth.register(nickname.value.trim())
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
  <div class="mx-auto mt-20 max-w-md">
    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-8">
      <h2 class="mb-2 text-center text-2xl font-bold">一键注册</h2>
      <p class="mb-6 text-center text-sm text-gray-500">输入昵称即可快速注册</p>

      <div v-if="error" class="mb-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ error }}</div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <input
            v-model="nickname"
            type="text"
            required
            autofocus
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none transition focus:border-amber-500"
            placeholder="请输入昵称"
          />
        </div>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-gradient-to-r from-amber-400 to-orange-500 py-3 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25 disabled:opacity-50"
        >
          {{ loading ? '注册中...' : '立即注册' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-500">
        已有账号？
        <RouterLink to="/login" class="text-amber-400 hover:underline">去登录</RouterLink>
      </p>
    </div>
  </div>
</template>
