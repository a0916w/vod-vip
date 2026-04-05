<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  apiLatestVideos, apiRecommendedVideos, apiCategories,
  apiBatchCheckFavorites, type Video, type Category,
} from '@/api'
import { useAuthStore } from '@/stores/auth'
import VideoCard from '@/components/VideoCard.vue'

const auth = useAuthStore()
const router = useRouter()

const latestVideos = ref<Video[]>([])
const recommendedVideos = ref<Video[]>([])
const categories = ref<Category[]>([])
const favoritedIds = ref<Set<number>>(new Set())
const keyword = ref('')

function search() {
  if (keyword.value.trim()) {
    router.push({ path: '/search', query: { q: keyword.value.trim() } })
  }
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

onMounted(loadHome)
</script>

<template>
  <div class="space-y-10">
    <!-- 搜索栏 -->
    <div class="mx-auto max-w-xl">
      <div class="relative">
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

    <!-- 最新更新 -->
    <section>
      <div class="mb-4 flex items-center justify-between">
        <h2 class="flex items-center gap-2 text-lg font-bold">
          <span class="h-5 w-1 rounded-full bg-amber-500"></span>
          最新更新
        </h2>
        <RouterLink to="/browse?cat=all" class="text-sm text-amber-400 transition hover:text-amber-300">查看全部 →</RouterLink>
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
        <RouterLink to="/browse?cat=all" class="text-sm text-amber-400 transition hover:text-amber-300">查看全部 →</RouterLink>
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
        <RouterLink
          to="/browse"
          class="group rounded-xl border border-gray-800 bg-gray-900 px-4 py-5 text-center transition hover:border-amber-500/50 hover:bg-amber-500/5"
        >
          <div class="text-base font-medium text-white group-hover:text-amber-400">全部</div>
          <div class="mt-1 text-xs text-gray-500">所有视频</div>
        </RouterLink>
        <RouterLink
          v-for="cat in categories" :key="cat.id"
          :to="`/browse?cat=${cat.id}`"
          class="group rounded-xl border border-gray-800 bg-gray-900 px-4 py-5 text-center transition hover:border-amber-500/50 hover:bg-amber-500/5"
        >
          <div class="text-base font-medium text-white group-hover:text-amber-400">{{ cat.name }}</div>
          <div class="mt-1 text-xs text-gray-500">{{ cat.videos_count ?? 0 }} 部</div>
        </RouterLink>
      </div>
    </section>
  </div>
</template>
