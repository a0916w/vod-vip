<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  apiLatestVideos, apiRecommendedVideos, apiCategories,
  apiBatchCheckFavorites, apiSiteSettings, type Video, type Category,
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
const loading = ref(true)
const error = ref(false)
const searchHintText = ref('')
const searchHintColor = ref('#f8fafc')
const searchHintFontSize = ref(14)
const searchHintFontWeight = ref<'normal' | 'bold'>('normal')
const searchHintTailColor = ref('#f59e0b')
const searchHintTailFontSize = ref(14)
const searchHintTailFontWeight = ref<'normal' | 'bold'>('bold')

const hintHeadText = computed(() => {
  const chars = Array.from(searchHintText.value || '')
  if (chars.length <= 4) return ''
  return chars.slice(0, chars.length - 4).join('')
})

const hintTailText = computed(() => {
  const chars = Array.from(searchHintText.value || '')
  if (chars.length <= 4) return chars.join('')
  return chars.slice(-4).join('')
})

function search() {
  if (keyword.value.trim()) {
    router.push({ path: '/search', query: { q: keyword.value.trim() } })
  }
}

async function loadHome() {
  loading.value = true
  error.value = false
  try {
    const [latestRes, recRes, catRes, settingsRes] = await Promise.all([
      apiLatestVideos(),
      apiRecommendedVideos(),
      apiCategories(),
      apiSiteSettings(),
    ])
    latestVideos.value = latestRes.data
    recommendedVideos.value = recRes.data
    categories.value = catRes.data
    searchHintText.value = settingsRes.data.search_hint_text || ''
    searchHintColor.value = settingsRes.data.search_hint_color || '#f8fafc'
    searchHintFontSize.value = Number(settingsRes.data.search_hint_font_size || 14)
    searchHintFontWeight.value = settingsRes.data.search_hint_font_weight || 'normal'
    searchHintTailColor.value = settingsRes.data.search_hint_tail_color || '#f59e0b'
    searchHintTailFontSize.value = Number(settingsRes.data.search_hint_tail_font_size || 14)
    searchHintTailFontWeight.value = settingsRes.data.search_hint_tail_font_weight || 'bold'

    if (auth.isLoggedIn) {
      try {
        const allIds = [...latestRes.data, ...recRes.data].map((v) => v.id)
        const unique = [...new Set(allIds)]
        if (unique.length > 0) {
          const { data } = await apiBatchCheckFavorites(unique)
          favoritedIds.value = new Set(data.favorited_ids)
        }
      } catch {
        // ignore — favorites check failure should not break the page
      }
    }
  } catch (e) {
    error.value = true
    console.error('Failed to load homepage data', e)
  } finally {
    loading.value = false
  }
}

onMounted(loadHome)
</script>

<template>
  <div class="space-y-7 sm:space-y-10">
    <!-- 搜索栏 -->
    <div class="mx-auto max-w-xl">
      <p
        v-if="searchHintText"
        class="mb-2 text-center text-xs sm:text-sm"
        :style="{ color: searchHintColor, fontSize: `${searchHintFontSize}px`, fontWeight: searchHintFontWeight }"
      >
        <span>{{ hintHeadText }}</span>
        <span
          v-if="hintTailText"
          :style="{ color: searchHintTailColor, fontSize: `${searchHintTailFontSize}px`, fontWeight: searchHintTailFontWeight }"
        >
          {{ hintTailText }}
        </span>
      </p>
      <div class="relative">
        <input
          v-model="keyword"
          @keyup.enter="search"
          type="text"
          placeholder="搜索你想看的视频..."
          class="w-full rounded-full border border-white/20 bg-[#1a2940]/58 px-5 py-2.5 text-sm text-white placeholder:text-slate-300/65 outline-none transition focus:border-amber-400 focus:ring-1 focus:ring-amber-400"
        />
        <button
          @click="search"
          class="absolute right-1 top-1 rounded-full bg-amber-500 px-4 py-1.5 text-sm font-medium text-black transition hover:bg-amber-400"
        >
          搜索
        </button>
      </div>
    </div>

    <!-- 全局加载 -->
    <div v-if="loading" class="flex justify-center py-20">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <!-- 全局错误 -->
    <div v-else-if="error" class="py-20 text-center">
      <div class="mb-4 text-4xl text-gray-700">:(</div>
      <p class="mb-4 text-gray-500">加载失败，请稍后重试</p>
      <button @click="loadHome" class="rounded-full bg-amber-500 px-6 py-2 text-sm font-medium text-black transition hover:bg-amber-400">重新加载</button>
    </div>

    <template v-else>
      <!-- 最新更新 -->
      <section>
        <div class="mb-4 flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-lg font-bold">
            <span class="h-5 w-1 rounded-full bg-amber-500"></span>
            最新更新
          </h2>
          <RouterLink to="/browse?cat=all" class="text-sm text-amber-400 transition hover:text-amber-300">查看全部 →</RouterLink>
        </div>
        <div v-if="latestVideos.length === 0" class="py-12 text-center text-gray-500">暂无视频</div>
        <div v-else class="grid grid-cols-2 gap-2.5 sm:gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
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
        <div v-if="recommendedVideos.length === 0" class="py-12 text-center text-gray-500">暂无推荐</div>
        <div v-else class="grid grid-cols-2 gap-2.5 sm:gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
          <VideoCard v-for="v in recommendedVideos" :key="'rec-' + v.id" :video="v" :favorited="favoritedIds.has(v.id)" @favorite-changed="(id, fav) => fav ? favoritedIds.add(id) : favoritedIds.delete(id)" />
        </div>
      </section>
    </template>

    <!-- 分类快捷入口 -->
    <section v-if="categories.length > 0">
      <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
        <span class="h-5 w-1 rounded-full bg-blue-500"></span>
        分类浏览
      </h2>
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
        <RouterLink
          to="/browse"
          class="group rounded-2xl bg-white/[0.08] px-4 py-5 text-center shadow-[0_10px_30px_rgba(15,23,42,0.18)] transition hover:bg-white/[0.14]"
        >
          <div class="text-base font-semibold text-white group-hover:text-amber-300">全部</div>
          <div class="mt-1 text-xs text-slate-300/80">所有视频</div>
        </RouterLink>
        <RouterLink
          v-for="cat in categories" :key="cat.id"
          :to="`/browse?cat=${cat.id}`"
          class="group rounded-2xl bg-white/[0.08] px-4 py-5 text-center shadow-[0_10px_30px_rgba(15,23,42,0.18)] transition hover:bg-white/[0.14]"
        >
          <div class="text-base font-semibold text-white group-hover:text-amber-300">{{ cat.name }}</div>
          <div class="mt-1 text-xs text-slate-300/80">{{ cat.videos_count ?? 0 }} 部</div>
        </RouterLink>
      </div>
    </section>
  </div>
</template>
