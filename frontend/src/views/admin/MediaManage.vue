<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiAdminMedia, apiAdminSyncMedia, apiAdminDeleteMedia, apiAdminCategories, type MediaResource, type Category } from '@/api'

const items = ref<MediaResource[]>([])
const categories = ref<Category[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)

const showSync = ref(false)
const syncId = ref(0)
const syncForm = ref({ title: '', category_id: 0, is_vip: false })

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await apiAdminMedia({ page })
    items.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
  } finally { loading.value = false }
}

async function loadCategories() {
  const { data } = await apiAdminCategories()
  categories.value = data
}

function openSync(item: MediaResource) {
  syncId.value = item.id
  syncForm.value = { title: item.caption || item.file_name || '', category_id: categories.value[0]?.id ?? 0, is_vip: false }
  showSync.value = true
}

async function doSync() {
  await apiAdminSyncMedia(syncId.value, { ...syncForm.value })
  showSync.value = false
  load(currentPage.value)
}

async function remove(id: number) {
  if (!confirm('确定删除？')) return
  await apiAdminDeleteMedia(id)
  load(currentPage.value)
}

function formatSize(bytes: number) {
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
  if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return bytes + ' B'
}

onMounted(() => { loadCategories(); load() })
</script>

<template>
  <div>
    <h1 class="mb-4 text-2xl font-bold">媒体资源（Telegram 采集）</h1>

    <div class="overflow-hidden rounded-xl border border-gray-800">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
          <th class="px-4 py-3">ID</th><th class="px-4 py-3">类型</th><th class="px-4 py-3">文件名</th><th class="px-4 py-3">大小</th><th class="px-4 py-3">来源</th><th class="px-4 py-3">状态</th><th class="px-4 py-3">操作</th>
        </tr></thead>
        <tbody>
          <tr v-for="m in items" :key="m.id" class="border-b border-gray-800/50 hover:bg-gray-900/30">
            <td class="px-4 py-3 text-gray-500">{{ m.id }}</td>
            <td class="px-4 py-3">
              <span :class="{ 'text-blue-400': m.file_type === 'video', 'text-green-400': m.file_type === 'image', 'text-gray-400': m.file_type === 'document' }">
                {{ m.file_type }}
              </span>
            </td>
            <td class="px-4 py-3 max-w-48 truncate" :title="m.caption || m.file_name || ''">{{ m.caption || m.file_name || '-' }}</td>
            <td class="px-4 py-3 text-gray-400">{{ formatSize(m.file_size) }}</td>
            <td class="px-4 py-3 text-gray-500">{{ m.from_username || '-' }}</td>
            <td class="px-4 py-3">
              <span v-if="m.local_path === 'pending_large_file'" class="text-yellow-400">⏳ 待下载</span>
              <span v-else-if="m.synced_to_video" class="text-green-400">✅ 已同步</span>
              <span v-else class="text-gray-400">待同步</span>
            </td>
            <td class="px-4 py-3 space-x-2">
              <button v-if="m.file_type === 'video' && !m.synced_to_video && m.local_path !== 'pending_large_file'" @click="openSync(m)" class="text-blue-400 hover:underline">同步到视频库</button>
              <button @click="remove(m.id)" class="text-red-400 hover:underline">删除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="lastPage > 1" class="mt-4 flex gap-1">
      <button v-for="p in lastPage" :key="p" @click="load(p)" :class="['h-8 w-8 rounded text-xs', p === currentPage ? 'bg-amber-500 text-black' : 'bg-gray-800 text-gray-400']">{{ p }}</button>
    </div>

    <Teleport to="body">
      <div v-if="showSync" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showSync = false">
        <div class="w-full max-w-md rounded-2xl bg-gray-900 p-6">
          <h3 class="mb-4 text-lg font-bold">同步到视频库</h3>
          <form @submit.prevent="doSync" class="space-y-3">
            <input v-model="syncForm.title" placeholder="视频标题" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <select v-model="syncForm.category_id" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none">
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-gray-300"><input type="checkbox" v-model="syncForm.is_vip" class="accent-amber-500" /> VIP 专属</label>
            <div class="flex justify-end gap-2">
              <button type="button" @click="showSync = false" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-400">取消</button>
              <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black">确认同步</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
