<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const account = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const regNickname = ref('')
const regError = ref('')
const regLoading = ref(false)

const showCredentials = ref(false)
const createdNickname = ref('')
const createdPassword = ref('')
const copied = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(account.value, password.value)
    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (err: any) {
    error.value = err.response?.data?.message || '登录失败，请重试'
  } finally {
    loading.value = false
  }
}

async function handleQuickRegister() {
  regError.value = ''
  if (!regNickname.value.trim()) {
    regError.value = '请输入昵称'
    return
  }
  regLoading.value = true
  try {
    const plainPassword = await auth.register(regNickname.value.trim())
    createdNickname.value = regNickname.value.trim()
    createdPassword.value = plainPassword
    showCredentials.value = true
  } catch (err: any) {
    const data = err.response?.data
    if (data?.errors) {
      regError.value = Object.values(data.errors).flat().join('；')
    } else {
      regError.value = data?.message || '注册失败，请重试'
    }
  } finally {
    regLoading.value = false
  }
}

async function copyCredentials() {
  const text = `用户名：${createdNickname.value}\n密码：${createdPassword.value}`
  try {
    await navigator.clipboard.writeText(text)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    const textarea = document.createElement('textarea')
    textarea.value = text
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }
}

function goHome() {
  showCredentials.value = false
  router.push('/')
}
</script>

<template>
  <div class="mx-auto mt-16 max-w-md space-y-4">
    <!-- 登录 -->
    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-8">
      <h2 class="mb-6 text-center text-2xl font-bold">登录</h2>

      <div v-if="error" class="mb-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ error }}</div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="mb-1 block text-sm text-gray-400">用户名</label>
          <input
            v-model="account"
            type="text"
            required
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-500"
            placeholder="请输入用户名"
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

    <!-- 快捷注册 -->
    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6">
      <div class="mb-4 flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-800"></div>
        <span class="text-sm text-gray-500">没有账号？快捷注册</span>
        <div class="h-px flex-1 bg-gray-800"></div>
      </div>

      <div v-if="regError" class="mb-3 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ regError }}</div>

      <form @submit.prevent="handleQuickRegister" class="flex gap-2">
        <input
          v-model="regNickname"
          type="text"
          required
          class="flex-1 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm text-white outline-none transition focus:border-amber-500"
          placeholder="输入昵称"
        />
        <button
          type="submit"
          :disabled="regLoading"
          class="shrink-0 rounded-lg bg-gradient-to-r from-amber-400 to-orange-500 px-5 py-2.5 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25 disabled:opacity-50"
        >
          {{ regLoading ? '注册中...' : '一键注册' }}
        </button>
      </form>
    </div>
  </div>

  <!-- 注册成功弹窗 -->
  <Teleport to="body">
    <div v-if="showCredentials" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
      <div class="mx-4 w-full max-w-sm rounded-2xl border border-gray-700 bg-gray-900 p-6 shadow-2xl">
        <div class="mb-4 text-center">
          <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-green-500/10 text-3xl">✅</div>
          <h3 class="text-xl font-bold">注册成功！</h3>
          <p class="mt-1 text-sm text-gray-400">请保存以下账号信息，用于下次登录</p>
        </div>

        <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">用户名</span>
            <span class="font-mono font-bold text-white">{{ createdNickname }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">密码</span>
            <span class="font-mono font-bold text-amber-400">{{ createdPassword }}</span>
          </div>
        </div>

        <div class="mt-4 space-y-2">
          <button
            @click="copyCredentials"
            class="w-full rounded-lg border border-gray-700 bg-gray-800 py-2.5 text-sm font-medium text-white transition hover:bg-gray-700"
          >
            {{ copied ? '✅ 已复制' : '📋 复制账号密码' }}
          </button>
          <button
            @click="goHome"
            class="w-full rounded-lg bg-gradient-to-r from-amber-400 to-orange-500 py-2.5 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25"
          >
            我已保存，开始使用
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
