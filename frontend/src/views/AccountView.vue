<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { apiFavorites, apiMyOrders } from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const ordersTotal = ref(0)
const favoritesTotal = ref(0)
const loading = ref(true)

const vipLabel = computed(() => auth.isVip ? 'VIP 会员' : '普通用户')
const vipExpiry = computed(() => auth.user?.vip_expired_at?.slice(0, 10) ?? '')

async function loadAccountData() {
  loading.value = true

  const tasks: Promise<void>[] = []

  tasks.push(
    apiMyOrders({ page: 1, per_page: 5 } as Record<string, unknown>)
      .then(({ data }) => {
        ordersTotal.value = data.total
      })
      .catch(() => {
        ordersTotal.value = 0
      }),
  )

  if (auth.isVip) {
    tasks.push(
      apiFavorites({ page: 1, per_page: 4 })
        .then(({ data }) => {
          favoritesTotal.value = data.total
        })
        .catch(() => {
          favoritesTotal.value = 0
        }),
    )
  }

  await Promise.all(tasks)
  loading.value = false
}

onMounted(async () => {
  await auth.waitUntilReady()
  await loadAccountData()
})
</script>

<template>
  <div class="space-y-8">
    <section class="rounded-[28px] bg-white/[0.08] p-6 shadow-[0_18px_60px_rgba(15,23,42,0.14)] sm:p-8">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
          <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-2xl font-black text-black">
            {{ auth.user?.nickname?.charAt(0) }}
          </div>
          <div>
            <h1 class="text-3xl font-bold">{{ auth.user?.nickname }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-300/90">
              <span class="rounded-full bg-white/10 px-3 py-1">{{ vipLabel }}</span>
              <span v-if="auth.isVip && vipExpiry">到期时间：{{ vipExpiry }}</span>
              <RouterLink
                to="/vip"
                class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-3 py-1 text-xs font-semibold text-black transition hover:opacity-90"
              >
                去充值
              </RouterLink>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div class="rounded-2xl bg-white/[0.08] px-4 py-3 text-center">
            <div class="text-xs text-slate-300/80">购买记录</div>
            <div class="mt-1 text-2xl font-bold">{{ ordersTotal }}</div>
          </div>
          <div class="rounded-2xl bg-white/[0.08] px-4 py-3 text-center">
            <div class="text-xs text-slate-300/80">收藏记录</div>
            <div class="mt-1 text-2xl font-bold">{{ auth.isVip ? favoritesTotal : '-' }}</div>
          </div>
          <div class="rounded-2xl bg-white/[0.08] px-4 py-3 text-center">
            <div class="text-xs text-slate-300/80">账号类型</div>
            <div class="mt-1 text-lg font-bold text-amber-300">{{ vipLabel }}</div>
          </div>
        </div>
      </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <RouterLink
        to="/vip"
        class="rounded-[24px] border border-amber-300/35 bg-gradient-to-r from-amber-500/14 to-orange-500/14 p-5 transition hover:border-amber-300/60 hover:from-amber-500/20 hover:to-orange-500/20"
      >
        <div class="text-sm text-amber-200/90">充值中心</div>
        <div class="mt-2 text-xl font-bold text-amber-200">{{ auth.isVip ? '会员续费' : '开通 VIP' }}</div>
        <div class="mt-2 text-sm text-amber-100/80">快速充值开通，解锁完整视频内容和会员权益。</div>
      </RouterLink>

      <RouterLink
        to="/account/orders"
        class="rounded-[24px] bg-white/[0.08] p-5 transition hover:bg-white/[0.12]"
      >
        <div class="text-sm text-slate-300/80">订单中心</div>
        <div class="mt-2 text-xl font-bold">购买记录</div>
        <div class="mt-2 text-sm text-slate-300/70">进入独立订单页，查看开通记录和订单状态。</div>
      </RouterLink>

      <RouterLink
        to="/account/favorites"
        class="rounded-[24px] bg-white/[0.08] p-5 transition hover:bg-white/[0.12]"
      >
        <div class="text-sm text-slate-300/80">收藏中心</div>
        <div class="mt-2 text-xl font-bold">收藏记录</div>
        <div class="mt-2 text-sm text-slate-300/70">进入独立收藏页，查看已收藏的视频内容。</div>
      </RouterLink>

      <RouterLink
        to="/browse"
        class="rounded-[24px] bg-white/[0.08] p-5 transition hover:bg-white/[0.12]"
      >
        <div class="text-sm text-slate-300/80">继续探索</div>
        <div class="mt-2 text-xl font-bold">去找更多视频</div>
        <div class="mt-2 text-sm text-slate-300/70">浏览分类、搜索和推荐内容。</div>
      </RouterLink>
    </section>
  </div>
</template>
