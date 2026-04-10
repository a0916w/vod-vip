<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { apiFavorites, type Video } from '@/api'
import { useAuthStore } from '@/stores/auth'
import VideoCard from '@/components/VideoCard.vue'
import Pagination from '@/components/Pagination.vue'

const auth = useAuthStore()
const videos = ref<Video[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const error = ref('')

async function loadFavorites(page = 1) {
  if (!auth.isVip) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await apiFavorites({ page, per_page: 12 })
    videos.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
    total.value = data.total
  } catch {
    error.value = '收藏记录加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await auth.waitUntilReady()
  if (auth.isVip) loadFavorites()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">收藏记录</h1>
        <p class="mt-1 text-sm text-slate-300/70">查看你已经收藏的视频。</p>
      </div>
      <RouterLink to="/account" class="text-sm text-slate-300/80 hover:text-white">返回个人中心</RouterLink>
    </div>

    <div v-if="!auth.isVip" class="rounded-[28px] bg-white/[0.08] px-6 py-16 text-center">
      <p class="text-slate-300/85">开通 VIP 后可查看和管理收藏记录。</p>
      <RouterLink to="/vip" class="mt-4 inline-flex rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-5 py-2 text-sm font-bold text-black">
        去开通 VIP
      </RouterLink>
    </div>

    <template v-else>
      <div v-if="loading" class="py-16 text-center text-slate-300/80">加载中...</div>
      <div v-else-if="error" class="py-16 text-center">
        <p class="mb-4 text-slate-300/80">{{ error }}</p>
        <button @click="loadFavorites(currentPage)" class="rounded-full bg-amber-500 px-6 py-2 text-sm font-medium text-black transition hover:bg-amber-400">重试</button>
      </div>
      <div v-else-if="videos.length === 0" class="rounded-[28px] bg-white/[0.08] px-6 py-16 text-center">
        <p class="text-slate-300/80">还没有收藏任何视频</p>
        <RouterLink to="/" class="mt-4 inline-flex text-sm text-amber-300 hover:text-amber-200">去发现好片</RouterLink>
      </div>
      <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
        <VideoCard v-for="video in videos" :key="video.id" :video="video" :favorited="true" />
      </div>

      <div v-if="videos.length > 0" class="mt-8">
        <Pagination :current-page="currentPage" :last-page="lastPage" @change="loadFavorites" />
      </div>
    </template>
  </div>
</template>
