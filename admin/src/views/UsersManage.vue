<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiUsers, apiUpdateUser, apiDeleteUser, type User } from '@/api'
import Pagination from '@/components/Pagination.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const users = ref<User[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const keyword = ref('')

function formatDateTime(value: string | null | undefined) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page }
    if (keyword.value) params.keyword = keyword.value
    const { data } = await apiUsers(params)
    users.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
  } finally { loading.value = false }
}

async function toggleVip(u: any) {
  const isVip = u.vip_level >= 1
  try {
    await apiUpdateUser(u.id, {
      vip_level: isVip ? 0 : 1,
      vip_expired_at: isVip ? null : new Date(Date.now() + 365 * 86400000).toISOString().slice(0, 19).replace('T', ' '),
    })
    load(currentPage.value)
  } catch (err: any) {
    alert(err.response?.data?.message || '操作失败')
  }
}

async function toggleAdmin(u: any) {
  try {
    await apiUpdateUser(u.id, { is_admin: !u.is_admin })
    load(currentPage.value)
  } catch (err: any) {
    alert(err.response?.data?.message || '操作失败')
  }
}

async function remove(id: number) {
  if (!confirm('确定删除该用户？')) return
  try {
    await apiDeleteUser(id)
    load(currentPage.value)
  } catch (err: any) {
    alert(err.response?.data?.message || '删除失败')
  }
}

onMounted(() => load())
</script>

<template>
  <div>
    <h1 class="mb-4 text-2xl font-bold">用户管理</h1>

    <div class="mb-4 flex gap-2">
      <input v-model="keyword" @keyup.enter="load(1)" placeholder="搜索昵称/邮箱..." class="rounded-lg border border-gray-700 bg-gray-900 px-4 py-2 text-sm text-white outline-none focus:border-amber-500" />
      <button @click="load(1)" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">搜索</button>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-gray-800">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
          <th class="px-4 py-3">ID</th><th class="px-4 py-3">昵称</th><th class="px-4 py-3">创建时间</th><th class="px-4 py-3">上次登录</th><th class="px-4 py-3">VIP</th><th class="px-4 py-3">管理员</th><th class="px-4 py-3">操作</th>
        </tr></thead>
        <tbody>
          <tr v-if="users.length === 0"><td colspan="7" class="px-4 py-12 text-center text-gray-500">暂无用户</td></tr>
          <tr v-for="u in users" :key="u.id" class="border-b border-gray-800/50 hover:bg-gray-900/30">
            <td class="px-4 py-3 text-gray-500">{{ u.id }}</td>
            <td class="px-4 py-3">{{ u.nickname }}</td>
            <td class="px-4 py-3 text-gray-400">{{ formatDateTime(u.created_at) }}</td>
            <td class="px-4 py-3 text-gray-400">{{ formatDateTime(u.last_login_at) }}</td>
            <td class="px-4 py-3">
              <button @click="toggleVip(u)" :class="u.vip_level >= 1 ? 'text-amber-400' : 'text-gray-600'" class="hover:underline">
                {{ u.vip_level >= 1 ? 'VIP' : '普通' }}
              </button>
            </td>
            <td class="px-4 py-3">
              <button @click="toggleAdmin(u)" :class="u.is_admin ? 'text-red-400' : 'text-gray-600'" class="hover:underline">
                {{ u.is_admin ? '管理员' : '否' }}
              </button>
            </td>
            <td class="px-4 py-3">
              <button v-if="u.id !== auth.user?.id" @click="remove(u.id)" class="text-red-400 hover:underline">删除</button>
              <span v-else class="text-gray-600">当前用户</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading" class="mt-4">
      <Pagination :current-page="currentPage" :last-page="lastPage" @change="load" />
    </div>
  </div>
</template>
