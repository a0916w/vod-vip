<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFavorites, type Video } from '@/api'
import VideoCard from '@/components/VideoCard.vue'

const videos = ref<Video[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

async function loadFavorites(page = 1) {
  loading.value = true
  try {
    const { data } = await apiFavorites({ page, per_page: 12 })
    videos.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
    total.value = data.total
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

    <div v-else-if="videos.length === 0" class="py-20 text-center">
      <div class="mb-4 text-5xl">🤍</div>
      <p class="text-gray-500">还没有收藏任何视频</p>
      <RouterLink to="/" class="mt-4 inline-block text-sm text-amber-400 hover:underline">去发现好片</RouterLink>
    </div>

    <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
      <VideoCard v-for="video in videos" :key="video.id" :video="video" />
    </div>

    <div v-if="lastPage > 1" class="mt-8 flex items-center justify-center gap-2">
      <button
        v-for="page in lastPage"
        :key="page"
        @click="loadFavorites(page)"
        :class="[
          'h-9 w-9 rounded-lg text-sm transition',
          page === currentPage ? 'bg-amber-500 font-bold text-black' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'
        ]"
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>
