<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const nickname = ref('')
const error = ref('')
const loading = ref(false)

const showCredentials = ref(false)
const createdNickname = ref('')
const createdPassword = ref('')
const copied = ref(false)

async function handleRegister() {
  error.value = ''
  if (!nickname.value.trim()) {
    error.value = '请输入昵称'
    return
  }
  loading.value = true
  try {
    const plainPassword = await auth.register(nickname.value.trim())
    createdNickname.value = nickname.value.trim()
    createdPassword.value = plainPassword
    showCredentials.value = true
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

async function copyCredentials() {
  const text = `昵称：${createdNickname.value}\n密码：${createdPassword.value}`
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
            <span class="text-sm text-gray-400">昵称</span>
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
