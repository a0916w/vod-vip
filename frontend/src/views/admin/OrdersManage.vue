<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiAdminOrders, type Order } from '@/api'

const orders = ref<Order[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await apiAdminOrders({ page })
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

    <div class="overflow-hidden rounded-xl border border-gray-800">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
          <th class="px-4 py-3">订单号</th><th class="px-4 py-3">用户</th><th class="px-4 py-3">套餐</th><th class="px-4 py-3">金额</th><th class="px-4 py-3">状态</th><th class="px-4 py-3">时间</th>
        </tr></thead>
        <tbody>
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

    <div v-if="lastPage > 1" class="mt-4 flex gap-1">
      <button v-for="p in lastPage" :key="p" @click="load(p)" :class="['h-8 w-8 rounded text-xs', p === currentPage ? 'bg-amber-500 text-black' : 'bg-gray-800 text-gray-400']">{{ p }}</button>
    </div>
  </div>
</template>
