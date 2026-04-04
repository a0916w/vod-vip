<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiVipPlans, apiCreateOrder, apiMyOrders, type VipPlan, type Order } from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const plans = ref<Record<string, VipPlan>>({})
const orders = ref<Order[]>([])
const selectedPlan = ref('monthly')
const paymentMethod = ref('wechat')
const loading = ref(false)
const showPayModal = ref(false)
const paymentInfo = ref<Record<string, unknown> | null>(null)

async function loadPlans() {
  const { data } = await apiVipPlans()
  plans.value = data
}

async function loadOrders() {
  const { data } = await apiMyOrders()
  orders.value = data.data
}

async function handlePurchase() {
  if (!auth.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: '/vip' } })
    return
  }
  loading.value = true
  try {
    const { data } = await apiCreateOrder({
      plan: selectedPlan.value,
      payment_method: paymentMethod.value,
    })
    paymentInfo.value = data.payment_params
    showPayModal.value = true
    loadOrders()
  } finally {
    loading.value = false
  }
}

function statusText(status: number): string {
  const map: Record<number, string> = { 0: '待支付', 1: '已支付', 2: '已取消' }
  return map[status] ?? '未知'
}

function statusColor(status: number): string {
  const map: Record<number, string> = {
    0: 'text-yellow-400',
    1: 'text-green-400',
    2: 'text-gray-500',
  }
  return map[status] ?? 'text-gray-400'
}

onMounted(() => {
  loadPlans()
  if (auth.isLoggedIn) loadOrders()
})
</script>

<template>
  <div class="space-y-8">
    <!-- VIP 状态 -->
    <div class="rounded-2xl bg-gradient-to-br from-amber-500/20 to-orange-500/10 border border-amber-500/30 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold">
            <template v-if="auth.isVip">🌟 您是 VIP 会员</template>
            <template v-else>升级为 VIP 会员</template>
          </h2>
          <p class="mt-1 text-sm text-gray-400">
            <template v-if="auth.isVip">
              会员到期时间：{{ auth.user?.vip_expired_at?.slice(0, 10) }}
            </template>
            <template v-else>
              解锁全部视频资源，享受无限观看
            </template>
          </p>
        </div>
        <div class="text-5xl">👑</div>
      </div>
    </div>

    <!-- 套餐选择 -->
    <div>
      <h3 class="mb-4 text-lg font-bold">选择套餐</h3>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <button
          v-for="(plan, key) in plans"
          :key="key"
          @click="selectedPlan = key as string"
          :class="[
            'relative rounded-xl border-2 p-5 text-left transition',
            selectedPlan === key
              ? 'border-amber-500 bg-amber-500/10'
              : 'border-gray-700 bg-gray-900 hover:border-gray-600'
          ]"
        >
          <div class="text-sm font-medium text-gray-300">{{ plan.name }}</div>
          <div class="mt-1 text-2xl font-bold text-white">
            ¥{{ plan.price }}
          </div>
          <div class="mt-1 text-xs text-gray-500">{{ plan.months }} 个月</div>
          <div
            v-if="selectedPlan === key"
            class="absolute right-3 top-3 h-5 w-5 rounded-full bg-amber-500 text-center text-xs leading-5 text-black"
          >
            ✓
          </div>
        </button>
      </div>
    </div>

    <!-- 支付方式 -->
    <div>
      <h3 class="mb-4 text-lg font-bold">支付方式</h3>
      <div class="flex gap-4">
        <button
          @click="paymentMethod = 'wechat'"
          :class="[
            'flex items-center gap-2 rounded-xl border-2 px-6 py-3 transition',
            paymentMethod === 'wechat' ? 'border-green-500 bg-green-500/10' : 'border-gray-700 bg-gray-900'
          ]"
        >
          <span class="text-xl">💬</span>
          <span class="text-sm">微信支付</span>
        </button>
        <button
          @click="paymentMethod = 'alipay'"
          :class="[
            'flex items-center gap-2 rounded-xl border-2 px-6 py-3 transition',
            paymentMethod === 'alipay' ? 'border-blue-500 bg-blue-500/10' : 'border-gray-700 bg-gray-900'
          ]"
        >
          <span class="text-xl">🔵</span>
          <span class="text-sm">支付宝</span>
        </button>
      </div>
    </div>

    <!-- 购买按钮 -->
    <button
      @click="handlePurchase"
      :disabled="loading"
      class="w-full rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 py-3 text-center text-lg font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25 disabled:opacity-50 sm:w-auto sm:px-12"
    >
      {{ loading ? '处理中...' : '立即开通' }}
    </button>

    <!-- 支付弹窗（模拟） -->
    <Teleport to="body">
      <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showPayModal = false">
        <div class="rounded-2xl bg-gray-900 p-8 text-center shadow-2xl">
          <h3 class="mb-4 text-xl font-bold">扫码支付</h3>
          <div class="mx-auto mb-4 flex h-48 w-48 items-center justify-center rounded-xl bg-white">
            <span class="text-6xl">📱</span>
          </div>
          <p class="text-sm text-gray-400">请使用 {{ paymentMethod === 'wechat' ? '微信' : '支付宝' }} 扫描二维码完成支付</p>
          <p class="mt-2 text-lg font-bold text-amber-400">¥{{ plans[selectedPlan]?.price }}</p>
          <button @click="showPayModal = false" class="mt-4 text-sm text-gray-500 hover:text-white">关闭</button>
        </div>
      </div>
    </Teleport>

    <!-- 订单记录 -->
    <div v-if="orders.length">
      <h3 class="mb-4 text-lg font-bold">订单记录</h3>
      <div class="overflow-hidden rounded-xl border border-gray-800">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
              <th class="px-4 py-3">订单号</th>
              <th class="px-4 py-3">套餐</th>
              <th class="px-4 py-3">金额</th>
              <th class="px-4 py-3">状态</th>
              <th class="px-4 py-3">时间</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id" class="border-b border-gray-800/50">
              <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ order.order_no }}</td>
              <td class="px-4 py-3">{{ order.plan_name }}</td>
              <td class="px-4 py-3 text-amber-400">¥{{ order.amount }}</td>
              <td class="px-4 py-3" :class="statusColor(order.status)">{{ statusText(order.status) }}</td>
              <td class="px-4 py-3 text-gray-500">{{ order.created_at?.slice(0, 16) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
