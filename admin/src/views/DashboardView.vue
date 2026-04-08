<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiDashboard } from '@/api'

const data = ref<any>(null)
const loading = ref(true)
const error = ref(false)

async function loadDashboard() {
  loading.value = true
  error.value = false
  try {
    const res = await apiDashboard()
    data.value = res.data
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div>
    <h1 class="mb-6 text-2xl font-bold">仪表盘</h1>

    <div v-if="loading" class="flex justify-center py-20">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else-if="error" class="py-16 text-center">
      <p class="mb-4 text-gray-500">仪表盘数据加载失败</p>
      <button @click="loadDashboard" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black hover:bg-amber-400">重试</button>
    </div>

    <template v-else-if="data">
      <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
          <div class="text-sm text-gray-500">总用户</div>
          <div class="mt-1 text-3xl font-bold">{{ data.stats.total_users }}</div>
          <div class="mt-1 text-xs text-amber-400">VIP {{ data.stats.vip_users }}</div>
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
          <div class="text-sm text-gray-500">视频总数</div>
          <div class="mt-1 text-3xl font-bold">{{ data.stats.total_videos }}</div>
          <div class="mt-1 text-xs text-amber-400">VIP 专属 {{ data.stats.vip_videos }}</div>
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
          <div class="text-sm text-gray-500">总收入</div>
          <div class="mt-1 text-3xl font-bold text-green-400">¥{{ data.stats.revenue }}</div>
          <div class="mt-1 text-xs text-gray-500">{{ data.stats.paid_orders }} 笔订单</div>
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
          <div class="text-sm text-gray-500">采集资源</div>
          <div class="mt-1 text-3xl font-bold">{{ data.stats.total_media }}</div>
          <div class="mt-1 text-xs text-yellow-400">{{ data.stats.pending_media }} 待处理</div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
          <h3 class="mb-3 font-bold">最近注册用户</h3>
          <div class="space-y-2">
            <div v-for="u in data.recent_users" :key="u.id" class="flex items-center justify-between text-sm">
              <span>{{ u.nickname }} <span class="text-gray-600">{{ u.email }}</span></span>
              <span :class="u.vip_level >= 1 ? 'text-amber-400' : 'text-gray-600'" class="text-xs">
                {{ u.vip_level >= 1 ? 'VIP' : '普通' }}
              </span>
            </div>
          </div>
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
          <h3 class="mb-3 font-bold">最近订单</h3>
          <div v-if="data.recent_orders.length === 0" class="text-sm text-gray-600">暂无订单</div>
          <div v-else class="space-y-2">
            <div v-for="o in data.recent_orders" :key="o.id" class="flex items-center justify-between text-sm">
              <span>{{ o.user?.nickname }} · {{ o.plan_name }}</span>
              <span class="text-green-400">¥{{ o.amount }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
