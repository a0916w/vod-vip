<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { apiVideos, apiCategories, apiBatchCheckFavorites, type Video, type Category } from '@/api'
import { useAuthStore } from '@/stores/auth'
import VideoCard from '@/components/VideoCard.vue'

const auth = useAuthStore()

const videos = ref<Video[]>([])
const categories = ref<Category[]>([])
const activeCategory = ref<number | null>(null)
const keyword = ref('')
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const favoritedIds = ref<Set<number>>(new Set())

async function loadVideos(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page, per_page: 12 }
    if (activeCategory.value) params.category_id = activeCategory.value
    if (keyword.value.trim()) params.keyword = keyword.value.trim()

    const { data } = await apiVideos(params)
    videos.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page

    if (auth.isLoggedIn && data.data.length > 0) {
      const ids = data.data.map((v) => v.id)
      const { data: favData } = await apiBatchCheckFavorites(ids)
      favoritedIds.value = new Set(favData.favorited_ids)
    }
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  const { data } = await apiCategories()
  categories.value = data
}

function selectCategory(id: number | null) {
  activeCategory.value = id
  loadVideos(1)
}

function search() {
  loadVideos(1)
}

watch(keyword, (val) => {
  if (!val.trim()) loadVideos(1)
})

onMounted(() => {
  loadCategories()
  loadVideos()
})
</script>

<template>
  <div>
    <!-- 搜索栏 -->
    <div class="mb-6 flex items-center gap-4">
      <div class="relative flex-1">
        <input
          v-model="keyword"
          @keyup.enter="search"
          type="text"
          placeholder="搜索视频..."
          class="w-full rounded-full border border-gray-700 bg-gray-900 px-5 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
        />
        <button
          @click="search"
          class="absolute right-1 top-1 rounded-full bg-amber-500 px-4 py-1.5 text-sm font-medium text-black transition hover:bg-amber-400"
        >
          搜索
        </button>
      </div>
    </div>

    <!-- 分类标签 -->
    <div class="mb-6 flex flex-wrap gap-2">
      <button
        @click="selectCategory(null)"
        :class="[
          'rounded-full px-4 py-1.5 text-sm transition',
          activeCategory === null ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
        ]"
      >
        全部
      </button>
      <button
        v-for="cat in categories"
        :key="cat.id"
        @click="selectCategory(cat.id)"
        :class="[
          'rounded-full px-4 py-1.5 text-sm transition',
          activeCategory === cat.id ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
        ]"
      >
        {{ cat.name }}
        <span v-if="cat.videos_count" class="ml-1 text-xs opacity-60">{{ cat.videos_count }}</span>
      </button>
    </div>

    <!-- 视频网格 -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else-if="videos.length === 0" class="py-20 text-center text-gray-500">
      暂无视频
    </div>

    <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
      <VideoCard
        v-for="video in videos"
        :key="video.id"
        :video="video"
        :favorited="favoritedIds.has(video.id)"
        @favorite-changed="(id, fav) => fav ? favoritedIds.add(id) : favoritedIds.delete(id)"
      />
    </div>

    <!-- 分页 -->
    <div v-if="lastPage > 1" class="mt-8 flex items-center justify-center gap-2">
      <button
        v-for="page in lastPage"
        :key="page"
        @click="loadVideos(page)"
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
