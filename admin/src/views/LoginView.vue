<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const account = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(account.value, password.value)
    router.push('/')
  } catch (err: any) {
    error.value = err.message || err.response?.data?.message || '登录失败'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-950">
    <div class="w-full max-w-sm rounded-2xl border border-gray-800 bg-gray-900 p-8">
      <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-lg font-black text-black">A</div>
        <h1 class="text-xl font-bold text-white">后台管理登录</h1>
        <p class="mt-1 text-sm text-gray-500">仅限管理员账号</p>
      </div>

      <div v-if="error" class="mb-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ error }}</div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="mb-1 block text-sm text-gray-400">用户名</label>
          <input
            v-model="account"
            type="text"
            required
            autofocus
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-500"
            placeholder="请输入管理员用户名"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-400">密码</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-500"
            placeholder="请输入密码"
          />
        </div>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-gradient-to-r from-amber-400 to-orange-500 py-2.5 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25 disabled:opacity-50"
        >
          {{ loading ? '登录中...' : '登录' }}
        </button>
      </form>
    </div>
  </div>
</template>
