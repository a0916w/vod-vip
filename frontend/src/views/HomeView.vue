<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import {
  apiVideos, apiCategories, apiLatestVideos, apiRecommendedVideos,
  apiBatchCheckFavorites, type Video, type Category,
} from '@/api'
import { useAuthStore } from '@/stores/auth'
import VideoCard from '@/components/VideoCard.vue'

const auth = useAuthStore()

const latestVideos = ref<Video[]>([])
const recommendedVideos = ref<Video[]>([])
const videos = ref<Video[]>([])
const categories = ref<Category[]>([])
const activeCategory = ref<number | null>(null)
const keyword = ref('')
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const favoritedIds = ref<Set<number>>(new Set())
const showBrowse = ref(false)

async function checkFavorites(list: Video[]) {
  if (!auth.isLoggedIn || list.length === 0) return
  const ids = list.map((v) => v.id)
  const { data } = await apiBatchCheckFavorites(ids)
  data.favorited_ids.forEach((id: number) => favoritedIds.value.add(id))
}

async function loadHome() {
  const [latestRes, recRes, catRes] = await Promise.all([
    apiLatestVideos(),
    apiRecommendedVideos(),
    apiCategories(),
  ])
  latestVideos.value = latestRes.data
  recommendedVideos.value = recRes.data
  categories.value = catRes.data

  if (auth.isLoggedIn) {
    const allIds = [...latestRes.data, ...recRes.data].map((v) => v.id)
    const unique = [...new Set(allIds)]
    if (unique.length > 0) {
      const { data } = await apiBatchCheckFavorites(unique)
      favoritedIds.value = new Set(data.favorited_ids)
    }
  }
}

async function loadVideos(page = 1) {
  loading.value = true
  showBrowse.value = true
  try {
    const params: Record<string, unknown> = { page, per_page: 12 }
    if (activeCategory.value) params.category_id = activeCategory.value
    if (keyword.value.trim()) params.keyword = keyword.value.trim()

    const { data } = await apiVideos(params)
    videos.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page

    await checkFavorites(data.data)
  } finally {
    loading.value = false
  }
}

function selectCategory(id: number | null) {
  activeCategory.value = id
  loadVideos(1)
}

function search() {
  loadVideos(1)
}

watch(keyword, (val) => {
  if (!val.trim() && showBrowse.value) loadVideos(1)
})

onMounted(loadHome)
</script>

<template>
  <div class="space-y-10">
    <!-- 搜索栏 -->
    <div class="flex items-center gap-3">
      <div class="relative flex-1">
        <input
          v-model="keyword"
          @keyup.enter="search"
          type="text"
          placeholder="搜索你想看的视频..."
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

    <!-- 搜索/浏览模式 -->
    <template v-if="showBrowse">
      <div>
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-bold">浏览全部</h2>
          <button @click="showBrowse = false; videos = []" class="text-sm text-gray-500 hover:text-white">返回首页</button>
        </div>

        <div class="mb-5 flex flex-wrap gap-2">
          <button
            @click="selectCategory(null)"
            :class="['rounded-full px-4 py-1.5 text-sm transition', activeCategory === null ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700']"
          >全部</button>
          <button
            v-for="cat in categories" :key="cat.id"
            @click="selectCategory(cat.id)"
            :class="['rounded-full px-4 py-1.5 text-sm transition', activeCategory === cat.id ? 'bg-amber-500 text-black font-medium' : 'bg-gray-800 text-gray-300 hover:bg-gray-700']"
          >{{ cat.name }}</button>
        </div>

        <div v-if="loading" class="flex justify-center py-16">
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
        </div>
        <div v-else-if="videos.length === 0" class="py-16 text-center text-gray-500">暂无视频</div>
        <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
          <VideoCard v-for="v in videos" :key="v.id" :video="v" :favorited="favoritedIds.has(v.id)" @favorite-changed="(id, fav) => fav ? favoritedIds.add(id) : favoritedIds.delete(id)" />
        </div>

        <div v-if="lastPage > 1" class="mt-8 flex justify-center gap-2">
          <button v-for="p in lastPage" :key="p" @click="loadVideos(p)" :class="['h-9 w-9 rounded-lg text-sm transition', p === currentPage ? 'bg-amber-500 font-bold text-black' : 'bg-gray-800 text-gray-400 hover:bg-gray-700']">{{ p }}</button>
        </div>
      </div>
    </template>

    <!-- 首页内容 -->
    <template v-else>
      <!-- 最新更新 -->
      <section>
        <div class="mb-4 flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-lg font-bold">
            <span class="h-5 w-1 rounded-full bg-amber-500"></span>
            最新更新
          </h2>
          <button @click="loadVideos(1)" class="text-sm text-amber-400 transition hover:text-amber-300">查看全部 →</button>
        </div>
        <div v-if="latestVideos.length === 0" class="flex justify-center py-12">
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
        </div>
        <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
          <VideoCard v-for="v in latestVideos" :key="v.id" :video="v" :favorited="favoritedIds.has(v.id)" @favorite-changed="(id, fav) => fav ? favoritedIds.add(id) : favoritedIds.delete(id)" />
        </div>
      </section>

      <!-- 为你推荐 -->
      <section>
        <div class="mb-4 flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-lg font-bold">
            <span class="h-5 w-1 rounded-full bg-rose-500"></span>
            为你推荐
          </h2>
          <button @click="loadVideos(1)" class="text-sm text-amber-400 transition hover:text-amber-300">查看全部 →</button>
        </div>
        <div v-if="recommendedVideos.length === 0" class="flex justify-center py-12">
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
        </div>
        <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
          <VideoCard v-for="v in recommendedVideos" :key="'rec-' + v.id" :video="v" :favorited="favoritedIds.has(v.id)" @favorite-changed="(id, fav) => fav ? favoritedIds.add(id) : favoritedIds.delete(id)" />
        </div>
      </section>

      <!-- 分类快捷入口 -->
      <section v-if="categories.length > 0">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
          <span class="h-5 w-1 rounded-full bg-blue-500"></span>
          分类浏览
        </h2>
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
      </section>
    </template>
  </div>
</template>
