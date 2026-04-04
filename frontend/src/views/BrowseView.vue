<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiVideos, apiCategories, apiBatchCheckFavorites, type Video, type Category } from '@/api'
import { useAuthStore } from '@/stores/auth'
import VideoCard from '@/components/VideoCard.vue'

const route = useRoute()
const router = useRouter()
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
      data.favorited_ids?.forEach?.((id: number) => favoritedIds.value.add(id))
      if (favData.favorited_ids) favData.favorited_ids.forEach((id: number) => favoritedIds.value.add(id))
    }
  } finally {
    loading.value = false
  }
}

function selectCategory(id: number | null) {
  activeCategory.value = id
  router.replace({ query: id ? { cat: String(id) } : {} })
  loadVideos(1)
}

function search() {
  loadVideos(1)
}

onMounted(async () => {
  const { data } = await apiCategories()
  categories.value = data

  const catQuery = route.query.cat
  if (catQuery) activeCategory.value = Number(catQuery)

  const qQuery = route.query.q
  if (qQuery) keyword.value = String(qQuery)

  loadVideos()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">分类浏览</h1>
      <RouterLink to="/" class="text-sm text-gray-500 transition hover:text-white">← 返回首页</RouterLink>
    </div>

    <!-- 搜索 -->
    <div class="flex items-center gap-3">
      <div class="relative flex-1">
        <input
          v-model="keyword"
          @keyup.enter="search"
          type="text"
          placeholder="搜索视频..."
          class="w-full rounded-full border border-gray-700 bg-gray-900 px-5 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
        />
        <button @click="search" class="absolute right-1 top-1 rounded-full bg-amber-500 px-4 py-1.5 text-sm font-medium text-black transition hover:bg-amber-400">搜索</button>
      </div>
    </div>

    <!-- 分类标签 -->
    <div class="flex flex-wrap gap-2">
      <button
        @click="selectCategory(null)"
        :class="['rounded-full px-4 py-2 text-sm transition', activeCategory === null ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700']"
      >
        全部
      </button>
      <button
        v-for="cat in categories" :key="cat.id"
        @click="selectCategory(cat.id)"
        :class="['rounded-full px-4 py-2 text-sm transition', activeCategory === cat.id ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700']"
      >
        {{ cat.name }}
        <span v-if="cat.videos_count" class="ml-1 text-xs opacity-60">{{ cat.videos_count }}</span>
      </button>
    </div>

    <!-- 视频列表 -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>
    <div v-else-if="videos.length === 0" class="py-16 text-center text-gray-500">暂无视频</div>
    <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
      <VideoCard
        v-for="v in videos" :key="v.id"
        :video="v"
        :favorited="favoritedIds.has(v.id)"
        @favorite-changed="(id, fav) => fav ? favoritedIds.add(id) : favoritedIds.delete(id)"
      />
    </div>

    <!-- 分页 -->
    <div v-if="lastPage > 1" class="flex justify-center gap-2">
      <button
        v-for="p in lastPage" :key="p"
        @click="loadVideos(p)"
        :class="['h-9 w-9 rounded-lg text-sm transition', p === currentPage ? 'bg-amber-500 font-bold text-black' : 'bg-gray-800 text-gray-400 hover:bg-gray-700']"
      >{{ p }}</button>
    </div>
  </div>
</template>
