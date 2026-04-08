<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiCategories, apiCreateCategory, apiUpdateCategory, apiDeleteCategory, type Category } from '@/api'

const categories = ref<Category[]>([])
const showModal = ref(false)
const editingId = ref<number | null>(null)
const form = ref({ name: '', slug: '', sort_order: 0 })
const saving = ref(false)
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const { data } = await apiCategories()
    categories.value = data
  } catch (err: any) {
    alert(err.response?.data?.message || '加载分类失败')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  form.value = { name: '', slug: '', sort_order: 0 }
  showModal.value = true
}

function openEdit(c: any) {
  editingId.value = c.id
  form.value = { name: c.name, slug: c.slug, sort_order: c.sort_order ?? 0 }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editingId.value) {
      await apiUpdateCategory(editingId.value, { ...form.value })
    } else {
      await apiCreateCategory({ ...form.value })
    }
    showModal.value = false
    load()
  } catch (err: any) {
    alert(err.response?.data?.message || '保存失败')
  } finally { saving.value = false }
}

async function remove(id: number) {
  if (!confirm('确定删除？')) return
  try {
    await apiDeleteCategory(id)
    load()
  } catch (err: any) {
    alert(err.response?.data?.message || '删除失败')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-2xl font-bold">分类管理</h1>
      <button @click="openCreate" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black hover:bg-amber-400">+ 新增分类</button>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-gray-800">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
          <th class="px-4 py-3">ID</th><th class="px-4 py-3">名称</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">视频数</th><th class="px-4 py-3">操作</th>
        </tr></thead>
        <tbody>
          <tr v-if="categories.length === 0"><td colspan="5" class="px-4 py-12 text-center text-gray-500">暂无分类</td></tr>
          <tr v-for="c in categories" :key="c.id" class="border-b border-gray-800/50 hover:bg-gray-900/30">
            <td class="px-4 py-3 text-gray-500">{{ c.id }}</td>
            <td class="px-4 py-3">{{ c.name }}</td>
            <td class="px-4 py-3 text-gray-400">{{ c.slug }}</td>
            <td class="px-4 py-3 text-gray-400">{{ c.videos_count ?? 0 }}</td>
            <td class="px-4 py-3 space-x-2">
              <button @click="openEdit(c)" class="text-blue-400 hover:underline">编辑</button>
              <button @click="remove(c.id)" class="text-red-400 hover:underline">删除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showModal = false">
        <div class="w-full max-w-md rounded-2xl bg-gray-900 p-6">
          <h3 class="mb-4 text-lg font-bold">{{ editingId ? '编辑分类' : '新增分类' }}</h3>
          <form @submit.prevent="save" class="space-y-3">
            <input v-model="form.name" placeholder="分类名称" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <input v-model="form.slug" placeholder="Slug（英文标识）" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <input v-model.number="form.sort_order" type="number" placeholder="排序" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <div class="flex justify-end gap-2">
              <button type="button" @click="showModal = false" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-400">取消</button>
              <button type="submit" :disabled="saving" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black disabled:opacity-50">{{ saving ? '保存中...' : '保存' }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
