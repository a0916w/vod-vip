<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { Video } from '@/api'
import { apiToggleFavorite } from '@/api'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  video: Video
  favorited?: boolean
}>()

const emit = defineEmits<{
  favoriteChanged: [videoId: number, isFavorited: boolean]
}>()

const auth = useAuthStore()
const router = useRouter()
const isFav = ref(props.favorited ?? false)
const favLoading = ref(false)

watch(() => props.favorited, (val) => {
  isFav.value = val ?? false
})

async function toggleFav(e: Event) {
  e.preventDefault()
  e.stopPropagation()

  if (!auth.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: `/video/${props.video.id}` } })
    return
  }
  if (!auth.isVip) {
    router.push('/vip')
    return
  }

  favLoading.value = true
  try {
    const { data } = await apiToggleFavorite(props.video.id)
    isFav.value = data.is_favorited
    emit('favoriteChanged', props.video.id, data.is_favorited)
  } catch (err: any) {
    if (err.response?.status === 403) router.push('/vip')
  } finally {
    favLoading.value = false
  }
}

function formatDuration(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  if (h > 0) return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  return `${m}:${String(s).padStart(2, '0')}`
}

function formatViews(count: number): string {
  if (count >= 10000) return `${(count / 10000).toFixed(1)}万`
  return String(count)
}
</script>

<template>
  <RouterLink :to="`/video/${video.id}`" class="group block overflow-hidden rounded-lg bg-gray-900 transition hover:ring-2 hover:ring-amber-400/50">
    <div class="relative aspect-video overflow-hidden">
      <img
        :src="video.cover_url"
        :alt="video.title"
        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
        loading="lazy"
      />
      <!-- VIP 标签 -->
      <span
        v-if="video.is_vip"
        class="absolute left-2 top-2 rounded bg-gradient-to-r from-amber-400 to-orange-500 px-2 py-0.5 text-xs font-bold text-black shadow"
      >
        VIP
      </span>
      <!-- 收藏按钮 -->
      <button
        @click="toggleFav"
        :disabled="favLoading"
        class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-base backdrop-blur-sm transition hover:bg-black/70"
        :title="isFav ? '取消收藏' : '收藏'"
      >
        {{ isFav ? '❤️' : '🤍' }}
      </button>
      <!-- 时长 -->
      <span class="absolute bottom-2 right-2 rounded bg-black/70 px-1.5 py-0.5 text-xs text-white">
        {{ formatDuration(video.duration) }}
      </span>
    </div>
    <div class="p-3">
      <h3 class="truncate text-sm font-medium text-white group-hover:text-amber-400">{{ video.title }}</h3>
      <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
        <span>{{ video.category?.name }}</span>
        <span>{{ formatViews(video.view_count) }} 次播放</span>
      </div>
    </div>
  </RouterLink>
</template>
