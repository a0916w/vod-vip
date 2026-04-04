<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
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
const hasSelected = ref(false)

const activeCategoryName = computed(() => {
  if (activeCategory.value === null) return '全部'
  return categories.value.find(c => c.id === activeCategory.value)?.name ?? ''
})

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
      if (favData.favorited_ids) favData.favorited_ids.forEach((id: number) => favoritedIds.value.add(id))
    }
  } finally {
    loading.value = false
  }
}

function selectCategory(id: number | null) {
  activeCategory.value = id
  hasSelected.value = true
  router.replace({ query: id ? { cat: String(id) } : {} })
  loadVideos(1)
}

function backToCategories() {
  hasSelected.value = false
  activeCategory.value = null
  videos.value = []
  router.replace({ query: {} })
}

function search() {
  hasSelected.value = true
  loadVideos(1)
}

onMounted(async () => {
  const { data } = await apiCategories()
  categories.value = data

  const catQuery = route.query.cat
  const qQuery = route.query.q

  if (catQuery === 'all') {
    activeCategory.value = null
    hasSelected.value = true
    loadVideos()
  } else if (catQuery) {
    activeCategory.value = Number(catQuery)
    hasSelected.value = true
    loadVideos()
  } else if (qQuery) {
    keyword.value = String(qQuery)
    hasSelected.value = true
    loadVideos()
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">分类浏览</h1>
      <RouterLink to="/" class="text-sm text-gray-500 transition hover:text-white">← 返回首页</RouterLink>
    </div>

    <!-- 未选分类：展示分类卡片 -->
    <template v-if="!hasSelected">
      <div class="grid grid-cols-3 gap-3 md:grid-cols-6">
        <button
          @click="selectCategory(null)"
          class="group rounded-xl border border-gray-800 bg-gray-900 px-4 py-5 text-center transition hover:border-amber-500/50 hover:bg-amber-500/5"
        >
          <div class="text-base font-medium text-white group-hover:text-amber-400">全部</div>
          <div class="mt-1 text-xs text-gray-500">所有视频</div>
        </button>
        <button
          v-for="cat in categories" :key="cat.id"
          @click="selectCategory(cat.id)"
          class="group rounded-xl border border-gray-800 bg-gray-900 px-4 py-5 text-center transition hover:border-amber-500/50 hover:bg-amber-500/5"
        >
          <div class="text-base font-medium text-white group-hover:text-amber-400">{{ cat.name }}</div>
          <div class="mt-1 text-xs text-gray-500">{{ cat.videos_count ?? 0 }} 部</div>
        </button>
      </div>
    </template>

    <!-- 已选分类：搜索 + 分类标签 + 视频列表 -->
    <template v-else>
      <div class="flex flex-wrap gap-2">
        <button
          @click="selectCategory(null)"
          :class="['rounded-full px-4 py-2 text-sm transition', activeCategory === null ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700']"
        >全部</button>
        <button
          v-for="cat in categories" :key="cat.id"
          @click="selectCategory(cat.id)"
          :class="['rounded-full px-4 py-2 text-sm transition', activeCategory === cat.id ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700']"
        >{{ cat.name }}</button>
      </div>

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

      <div v-if="lastPage > 1" class="flex justify-center gap-2">
        <button
          v-for="p in lastPage" :key="p"
          @click="loadVideos(p)"
          :class="['h-9 w-9 rounded-lg text-sm transition', p === currentPage ? 'bg-amber-500 font-bold text-black' : 'bg-gray-800 text-gray-400 hover:bg-gray-700']"
        >{{ p }}</button>
      </div>
    </template>
  </div>
</template>
