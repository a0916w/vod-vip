<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  apiMarquees,
  apiCreateMarquee,
  apiUpdateMarquee,
  apiDeleteMarquee,
  type MarqueeItem,
} from '@/api'

const items = ref<MarqueeItem[]>([])
const loading = ref(false)
const saving = ref(false)
const editingId = ref<number | null>(null)
const form = ref({
  content: '',
  is_active: true,
  sort_order: 0,
})

async function load() {
  loading.value = true
  try {
    const { data } = await apiMarquees()
    items.value = data
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  form.value = {
    content: '',
    is_active: true,
    sort_order: 0,
  }
}

function openEdit(item: MarqueeItem) {
  editingId.value = item.id
  form.value = {
    content: item.content,
    is_active: item.is_active,
    sort_order: item.sort_order,
  }
}

async function save() {
  saving.value = true
  try {
    if (editingId.value) {
      await apiUpdateMarquee(editingId.value, { ...form.value })
    } else {
      await apiCreateMarquee({ ...form.value })
    }
    resetForm()
    load()
  } catch (err: any) {
    alert(err.response?.data?.message || '保存失败')
  } finally {
    saving.value = false
  }
}

async function remove(id: number) {
  if (!confirm('确定删除这条浮动文字？')) return
  try {
    await apiDeleteMarquee(id)
    if (editingId.value === id) resetForm()
    load()
  } catch (err: any) {
    alert(err.response?.data?.message || '删除失败')
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">浮动文字管理</h1>
        <p class="mt-1 text-sm text-gray-500">前端首页会按排序展示启用中的滚动公告文字。</p>
      </div>
      <button @click="resetForm" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">新建公告</button>
    </div>

    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
      <h2 class="mb-4 text-lg font-bold">{{ editingId ? '编辑浮动文字' : '新增浮动文字' }}</h2>
      <form @submit.prevent="save" class="space-y-4">
        <textarea
          v-model="form.content"
          rows="3"
          maxlength="255"
          placeholder="请输入浮动文字内容"
          class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
        ></textarea>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <input
            v-model.number="form.sort_order"
            type="number"
            min="0"
            placeholder="排序"
            class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500 sm:w-40"
          />
          <label class="flex items-center gap-2 text-sm text-gray-300">
            <input v-model="form.is_active" type="checkbox" class="accent-amber-500" />
            启用显示
          </label>
        </div>
        <div class="flex gap-2">
          <button type="submit" :disabled="saving" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black disabled:opacity-50">
            {{ saving ? '保存中...' : editingId ? '更新' : '创建' }}
          </button>
          <button type="button" @click="resetForm" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">重置</button>
        </div>
      </form>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-gray-800">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
            <th class="px-4 py-3">排序</th>
            <th class="px-4 py-3">内容</th>
            <th class="px-4 py-3">状态</th>
            <th class="px-4 py-3">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="items.length === 0">
            <td colspan="4" class="px-4 py-12 text-center text-gray-500">暂无浮动文字</td>
          </tr>
          <tr v-for="item in items" :key="item.id" class="border-b border-gray-800/50 hover:bg-gray-900/30">
            <td class="px-4 py-3 text-gray-400">{{ item.sort_order }}</td>
            <td class="px-4 py-3">
              <div class="max-w-3xl truncate" :title="item.content">{{ item.content }}</div>
            </td>
            <td class="px-4 py-3">
              <span :class="item.is_active ? 'text-green-400' : 'text-gray-500'">
                {{ item.is_active ? '启用中' : '已停用' }}
              </span>
            </td>
            <td class="space-x-3 px-4 py-3">
              <button @click="openEdit(item)" class="text-blue-400 hover:underline">编辑</button>
              <button @click="remove(item.id)" class="text-red-400 hover:underline">删除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
