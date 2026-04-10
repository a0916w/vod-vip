<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiVideos, apiBatchCheckFavorites, type Video } from '@/api'
import { useAuthStore } from '@/stores/auth'
import VideoCard from '@/components/VideoCard.vue'
import Pagination from '@/components/Pagination.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const keyword = ref('')
const videos = ref<Video[]>([])
const loading = ref(false)
const searched = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const favoritedIds = ref<Set<number>>(new Set())
const error = ref('')

async function doSearch(page = 1) {
  if (!keyword.value.trim()) return
  loading.value = true
  searched.value = true
  error.value = ''
  try {
    const { data } = await apiVideos({ keyword: keyword.value.trim(), page, per_page: 12 })
    videos.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
    total.value = data.total
    favoritedIds.value = new Set()

    router.replace({ query: { q: keyword.value.trim() } })

    if (auth.isLoggedIn && data.data.length > 0) {
      try {
        const ids = data.data.map((v) => v.id)
        const { data: favData } = await apiBatchCheckFavorites(ids)
        if (favData.favorited_ids) favData.favorited_ids.forEach((id: number) => favoritedIds.value.add(id))
      } catch {
        // 收藏状态获取失败不应阻塞搜索结果展示
      }
    }
  } catch {
    error.value = '搜索失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  const q = route.query.q
  if (q) {
    keyword.value = String(q)
    doSearch()
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">搜索</h1>
      <RouterLink to="/" class="text-sm text-gray-500 transition hover:text-white">← 返回首页</RouterLink>
    </div>

    <!-- 搜索框 -->
    <div class="relative">
      <input
        v-model="keyword"
        @keyup.enter="doSearch(1)"
        type="text"
        placeholder="输入视频名称..."
        autofocus
        class="w-full rounded-full border border-white/20 bg-[#1a2940]/58 px-5 py-3 text-sm text-white placeholder:text-slate-300/65 outline-none transition focus:border-amber-400 focus:ring-1 focus:ring-amber-400"
      />
      <button @click="doSearch(1)" class="absolute right-1.5 top-1.5 rounded-full bg-amber-500 px-5 py-1.5 text-sm font-medium text-black transition hover:bg-amber-400">搜索</button>
    </div>

    <!-- 结果 -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else-if="error" class="py-16 text-center">
      <p class="mb-4 text-gray-500">{{ error }}</p>
      <button @click="doSearch(1)" class="rounded-full bg-amber-500 px-6 py-2 text-sm font-medium text-black transition hover:bg-amber-400">重试</button>
    </div>

    <template v-else-if="searched">
      <p class="text-sm text-gray-500">找到 <span class="text-white font-medium">{{ total }}</span> 个结果</p>

      <div v-if="videos.length === 0" class="py-12 text-center text-gray-500">没有找到相关视频，换个关键词试试</div>
      <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
        <VideoCard
          v-for="v in videos" :key="v.id"
          :video="v"
          :favorited="favoritedIds.has(v.id)"
          @favorite-changed="(id, fav) => fav ? favoritedIds.add(id) : favoritedIds.delete(id)"
        />
      </div>

      <Pagination :current-page="currentPage" :last-page="lastPage" @change="doSearch" />
    </template>

    <div v-else class="py-16 text-center text-gray-600">输入关键词开始搜索</div>
  </div>
</template>
