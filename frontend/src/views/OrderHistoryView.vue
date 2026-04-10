<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { apiMyOrders, type Order } from '@/api'

const orders = ref<Order[]>([])
const loading = ref(false)
const error = ref('')

function statusText(status: number) {
  const map: Record<number, string> = {
    0: '待支付',
    1: '已支付',
    2: '已取消',
  }
  return map[status] ?? '未知'
}

function statusClass(status: number) {
  const map: Record<number, string> = {
    0: 'text-yellow-300 bg-yellow-400/10',
    1: 'text-emerald-300 bg-emerald-400/10',
    2: 'text-slate-300 bg-white/10',
  }
  return map[status] ?? 'text-slate-300 bg-white/10'
}

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

async function loadOrders() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await apiMyOrders()
    orders.value = data.data
  } catch {
    error.value = '购买记录加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

onMounted(loadOrders)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">购买记录</h1>
        <p class="mt-1 text-sm text-slate-300/70">查看你的开通记录和订单状态。</p>
      </div>
      <RouterLink to="/account" class="text-sm text-slate-300/80 hover:text-white">返回个人中心</RouterLink>
    </div>

    <div v-if="loading" class="py-16 text-center text-slate-300/80">加载中...</div>
    <div v-else-if="error" class="py-16 text-center">
      <p class="mb-4 text-slate-300/80">{{ error }}</p>
      <button @click="loadOrders" class="rounded-full bg-amber-500 px-6 py-2 text-sm font-medium text-black transition hover:bg-amber-400">重试</button>
    </div>
    <div v-else-if="orders.length === 0" class="rounded-[28px] bg-white/[0.08] px-6 py-16 text-center">
      <p class="text-slate-300/80">暂无购买记录</p>
    </div>
    <div v-else class="space-y-3">
      <div
        v-for="order in orders"
        :key="order.id"
        class="flex flex-col gap-3 rounded-[24px] bg-white/[0.08] px-5 py-4 md:flex-row md:items-center md:justify-between"
      >
        <div>
          <div class="text-base font-semibold">{{ order.plan_name }}</div>
          <div class="mt-1 text-xs text-slate-300/70">{{ order.order_no }}</div>
          <div class="mt-2 text-xs text-slate-300/60">{{ formatDateTime(order.created_at) }}</div>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-lg font-bold text-amber-300">¥{{ order.amount }}</span>
          <span :class="['rounded-full px-3 py-1 text-xs font-medium', statusClass(order.status)]">
            {{ statusText(order.status) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
