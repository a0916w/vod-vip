<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiOrders, type Order } from '@/api'
import Pagination from '@/components/Pagination.vue'

const orders = ref<Order[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await apiOrders({ page })
    orders.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
  } finally { loading.value = false }
}

function statusLabel(s: number) {
  return { 0: '待支付', 1: '已支付', 2: '已取消' }[s] ?? '未知'
}
function statusColor(s: number) {
  return { 0: 'text-yellow-400', 1: 'text-green-400', 2: 'text-gray-500' }[s] ?? 'text-gray-400'
}

onMounted(() => load())
</script>

<template>
  <div>
    <h1 class="mb-4 text-2xl font-bold">订单管理</h1>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-gray-800">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
          <th class="px-4 py-3">订单号</th><th class="px-4 py-3">用户</th><th class="px-4 py-3">套餐</th><th class="px-4 py-3">金额</th><th class="px-4 py-3">状态</th><th class="px-4 py-3">时间</th>
        </tr></thead>
        <tbody>
          <tr v-if="orders.length === 0"><td colspan="6" class="px-4 py-12 text-center text-gray-500">暂无订单</td></tr>
          <tr v-for="o in orders" :key="o.id" class="border-b border-gray-800/50 hover:bg-gray-900/30">
            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ o.order_no }}</td>
            <td class="px-4 py-3">{{ (o as any).user?.nickname ?? '-' }}</td>
            <td class="px-4 py-3">{{ o.plan_name }}</td>
            <td class="px-4 py-3 text-amber-400">¥{{ o.amount }}</td>
            <td class="px-4 py-3" :class="statusColor(o.status)">{{ statusLabel(o.status) }}</td>
            <td class="px-4 py-3 text-gray-500">{{ o.created_at?.slice(0, 16) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading" class="mt-4">
      <Pagination :current-page="currentPage" :last-page="lastPage" @change="load" />
    </div>
  </div>
</template>
