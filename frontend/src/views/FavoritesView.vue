<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFavorites, type Video } from '@/api'
import VideoCard from '@/components/VideoCard.vue'
import Pagination from '@/components/Pagination.vue'

const videos = ref<Video[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const error = ref('')

async function loadFavorites(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await apiFavorites({ page, per_page: 12 })
    videos.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
    total.value = data.total
  } catch {
    error.value = '加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadFavorites()
})
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold">我的收藏</h1>
      <span class="text-sm text-gray-500">共 {{ total }} 部</span>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else-if="error" class="py-20 text-center">
      <p class="mb-4 text-gray-500">{{ error }}</p>
      <button @click="loadFavorites(currentPage)" class="rounded-full bg-amber-500 px-6 py-2 text-sm font-medium text-black transition hover:bg-amber-400">重试</button>
    </div>

    <div v-else-if="videos.length === 0" class="py-20 text-center">
      <div class="mb-4 text-5xl">🤍</div>
      <p class="text-gray-500">还没有收藏任何视频</p>
      <RouterLink to="/" class="mt-4 inline-block text-sm text-amber-400 hover:underline">去发现好片</RouterLink>
    </div>

    <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
      <VideoCard v-for="video in videos" :key="video.id" :video="video" />
    </div>

    <div class="mt-8">
      <Pagination :current-page="currentPage" :last-page="lastPage" @change="loadFavorites" />
    </div>
  </div>
</template>
