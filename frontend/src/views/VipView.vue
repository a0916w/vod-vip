<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { apiCreateOrder, apiMyOrders, apiVipPlans, type Order, type VipPlan } from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const plans = ref<Record<string, VipPlan>>({})
const orders = ref<Order[]>([])
const selectedPlan = ref('monthly')
const paymentMethod = ref('wechat')
const loading = ref(false)
const showPayModal = ref(false)
const paymentInfo = ref<Record<string, unknown> | null>(null)
const pageError = ref('')

const preferredPlanOrder = ['monthly', 'quarterly', 'yearly']

const planEntries = computed(() => {
  return Object.entries(plans.value)
    .sort(([keyA], [keyB]) => {
      const idxA = preferredPlanOrder.indexOf(keyA)
      const idxB = preferredPlanOrder.indexOf(keyB)
      const orderA = idxA === -1 ? 99 : idxA
      const orderB = idxB === -1 ? 99 : idxB
      return orderA - orderB
    })
})

const selectedPlanInfo = computed(() => plans.value[selectedPlan.value])

async function loadPlans() {
  try {
    const { data } = await apiVipPlans()
    plans.value = data

    if (!plans.value[selectedPlan.value]) {
      const firstKey = Object.keys(plans.value)[0]
      if (firstKey) selectedPlan.value = firstKey
    }
  } catch {
    pageError.value = '套餐加载失败，请刷新重试'
  }
}

async function loadOrders() {
  try {
    const { data } = await apiMyOrders()
    orders.value = data.data
  } catch {
    // 订单仅为辅助信息，忽略错误
  }
}

async function handlePurchase() {
  if (!selectedPlanInfo.value) {
    pageError.value = '请选择套餐后再开通'
    return
  }

  loading.value = true
  pageError.value = ''

  try {
    const { data } = await apiCreateOrder({
      plan: selectedPlan.value,
      payment_method: paymentMethod.value,
    })
    paymentInfo.value = data.payment_params
    showPayModal.value = true
    loadOrders()
  } catch {
    pageError.value = '下单失败，请稍后重试'
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
    0: 'text-amber-300',
    1: 'text-emerald-300',
    2: 'text-slate-400',
  }
  return map[status] ?? 'text-slate-300'
}

onMounted(() => {
  loadPlans()
  loadOrders()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <RouterLink
        to="/account"
        class="inline-flex items-center gap-2 rounded-full border border-white/14 bg-white/[0.06] px-4 py-2 text-sm text-slate-200 transition hover:border-amber-300/50 hover:bg-white/[0.1]"
      >
        <span aria-hidden="true">←</span>
        返回个人中心
      </RouterLink>
      <div class="hidden text-xs text-slate-300/70 sm:block">会员服务中心</div>
    </div>

    <div class="rounded-3xl border border-amber-300/25 bg-gradient-to-r from-[#332113]/65 via-[#2a2536]/58 to-[#1d2f44]/62 p-6 sm:p-7">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl tracking-tight sm:text-3xl">
            <template v-if="auth.isVip">VIP 会员已开通</template>
            <template v-else>升级 VIP 会员</template>
          </h1>
          <p class="mt-2 text-sm text-slate-200/85">
            <template v-if="auth.isVip">
              当前会员到期：{{ auth.user?.vip_expired_at?.slice(0, 10) }}
            </template>
            <template v-else>
              解锁全部高清视频、极速线路与专属内容。
            </template>
          </p>
        </div>
        <div class="text-4xl sm:text-5xl">👑</div>
      </div>
    </div>

    <div v-if="pageError" class="rounded-2xl border border-red-400/30 bg-red-500/10 px-5 py-4 text-sm text-red-300">
      {{ pageError }}
    </div>

    <section class="rounded-3xl border border-white/12 bg-white/[0.06] p-5 sm:p-6">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg">选择套餐</h2>
        <span v-if="selectedPlanInfo" class="text-sm text-amber-300">已选：{{ selectedPlanInfo.name }}</span>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <button
          v-for="[key, plan] in planEntries"
          :key="key"
          @click="selectedPlan = key"
          :class="[
            'relative rounded-2xl border p-5 text-left transition duration-200',
            selectedPlan === key
              ? 'border-amber-400/80 bg-amber-400/12 shadow-[0_10px_30px_rgba(251,191,36,0.15)]'
              : 'border-white/10 bg-[#1a2940]/45 hover:border-white/25 hover:bg-[#1a2940]/60'
          ]"
        >
          <div class="text-sm text-slate-200/85">{{ plan.name }}</div>
          <div class="mt-2 text-3xl leading-none text-white">¥{{ plan.price }}</div>
          <div class="mt-2 text-xs text-slate-300/70">{{ plan.months }} 个月</div>
          <div
            v-if="selectedPlan === key"
            class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-xs text-black"
          >
            ✓
          </div>
        </button>
      </div>

      <div class="mt-6">
        <h3 class="mb-3 text-base">支付方式</h3>
        <div class="flex flex-wrap gap-3">
          <button
            @click="paymentMethod = 'wechat'"
            :class="[
              'inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm transition',
              paymentMethod === 'wechat'
                ? 'border-emerald-400/80 bg-emerald-500/12 text-emerald-200'
                : 'border-white/14 bg-white/[0.04] text-slate-200 hover:bg-white/[0.09]'
            ]"
          >
            <span>💬</span>
            微信支付
          </button>
          <button
            @click="paymentMethod = 'alipay'"
            :class="[
              'inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm transition',
              paymentMethod === 'alipay'
                ? 'border-sky-400/80 bg-sky-500/12 text-sky-200'
                : 'border-white/14 bg-white/[0.04] text-slate-200 hover:bg-white/[0.09]'
            ]"
          >
            <span>🔵</span>
            支付宝
          </button>
        </div>
      </div>

      <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-300/80">
          应付金额：
          <span class="text-lg text-amber-300">¥{{ selectedPlanInfo?.price ?? '--' }}</span>
        </p>
        <button
          @click="handlePurchase"
          :disabled="loading || !selectedPlanInfo"
          class="inline-flex min-w-[170px] items-center justify-center rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 px-7 py-3 text-base text-black transition hover:shadow-lg hover:shadow-amber-500/30 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {{ loading ? '处理中...' : '立即开通' }}
        </button>
      </div>
    </section>

    <section v-if="orders.length" class="rounded-3xl border border-white/12 bg-white/[0.05] p-5 sm:p-6">
      <h3 class="mb-4 text-lg">订单记录</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[680px] text-sm">
          <thead>
            <tr class="border-b border-white/10 text-left text-slate-300/80">
              <th class="px-3 py-3">订单号</th>
              <th class="px-3 py-3">套餐</th>
              <th class="px-3 py-3">金额</th>
              <th class="px-3 py-3">状态</th>
              <th class="px-3 py-3">时间</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id" class="border-b border-white/8 last:border-0">
              <td class="px-3 py-3 font-mono text-xs text-slate-300/65">{{ order.order_no }}</td>
              <td class="px-3 py-3 text-slate-100">{{ order.plan_name }}</td>
              <td class="px-3 py-3 text-amber-300">¥{{ order.amount }}</td>
              <td class="px-3 py-3" :class="statusColor(order.status)">{{ statusText(order.status) }}</td>
              <td class="px-3 py-3 text-slate-300/60">{{ order.created_at?.slice(0, 16) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <Teleport to="body">
      <div
        v-if="showPayModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
        @click.self="showPayModal = false"
      >
        <div class="w-full max-w-md rounded-3xl border border-white/12 bg-[#152136]/96 p-6 text-center shadow-[0_20px_60px_rgba(2,6,23,0.45)]">
          <h3 class="text-xl">扫码支付</h3>
          <p class="mt-2 text-sm text-slate-300/80">请使用 {{ paymentMethod === 'wechat' ? '微信' : '支付宝' }} 扫码完成支付</p>

          <div class="mx-auto my-5 flex h-52 w-52 items-center justify-center rounded-2xl bg-white text-6xl">
            📱
          </div>

          <p class="text-sm text-slate-300/75">订单金额</p>
          <p class="mt-1 text-2xl text-amber-300">¥{{ selectedPlanInfo?.price }}</p>

          <div class="mt-5 flex justify-center gap-3">
            <button
              @click="showPayModal = false"
              class="rounded-lg border border-white/14 bg-white/5 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10"
            >
              关闭
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
