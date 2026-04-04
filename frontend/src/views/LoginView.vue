<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (err: any) {
    error.value = err.response?.data?.message || '登录失败，请重试'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="mx-auto mt-20 max-w-md">
    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-8">
      <h2 class="mb-6 text-center text-2xl font-bold">登录</h2>

      <div v-if="error" class="mb-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ error }}</div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="mb-1 block text-sm text-gray-400">邮箱</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-500"
            placeholder="请输入邮箱"
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

      <p class="mt-4 text-center text-sm text-gray-500">
        还没有账号？
        <RouterLink to="/register" class="text-amber-400 hover:underline">立即注册</RouterLink>
      </p>

      <!-- 测试账号提示 -->
      <div class="mt-6 rounded-lg bg-gray-800/50 p-3 text-xs text-gray-500">
        <p class="font-medium text-gray-400">测试账号：</p>
        <p>VIP：admin@example.com / password</p>
        <p>普通：user@example.com / password</p>
      </div>
    </div>
  </div>
</template>
