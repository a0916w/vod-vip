<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Artplayer from 'artplayer'
import Hls from 'hls.js'
import { apiVideoDetail, apiToggleFavorite, apiVideos, type VideoDetail, type Video } from '@/api'
import { useAuthStore } from '@/stores/auth'
import VipOverlay from '@/components/VipOverlay.vue'
import VideoCard from '@/components/VideoCard.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const video = ref<VideoDetail | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const showVipOverlay = ref(false)
const isFavorited = ref(false)
const favLoading = ref(false)
const relatedVideos = ref<Video[]>([])

async function toggleFavorite() {
  if (!video.value) return
  if (!auth.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  if (!auth.isVip) {
    router.push('/vip')
    return
  }
  favLoading.value = true
  try {
    const { data } = await apiToggleFavorite(video.value.id)
    isFavorited.value = data.is_favorited
  } catch (err: any) {
    if (err.response?.status === 403) router.push('/vip')
  } finally {
    favLoading.value = false
  }
}

let player: Artplayer | null = null
let hls: Hls | null = null
const playerRef = ref<HTMLDivElement>()

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

function destroyPlayer() {
  hls?.destroy()
  hls = null
  player?.destroy()
  player = null
  showVipOverlay.value = false
}

async function loadVideo() {
  destroyPlayer()
  loading.value = true
  error.value = null
  relatedVideos.value = []
  try {
    const { data } = await apiVideoDetail(Number(route.params.id))
    video.value = data
    isFavorited.value = data.is_favorited
    loading.value = false

    await nextTick()
    initPlayer(data)
    loadRelated(data)
  } catch (e: any) {
    loading.value = false
    error.value = e.response?.status === 404 ? '视频不存在或已被删除' : '加载失败，请稍后重试'
  }
}

function initPlayer(data: VideoDetail) {
  if (!playerRef.value || !data.play_url) return

  const opts: Record<string, unknown> = {
    container: playerRef.value,
    url: data.play_url,
    poster: data.cover_url,
    autoplay: false,
    pip: true,
    fullscreen: true,
    fullscreenWeb: true,
    theme: '#f59e0b',
    volume: 0.7,
    muted: false,
    playbackRate: true,
    setting: true,
    flip: true,
    aspectRatio: true,
  }

  if (data.play_type === 'hls') {
    opts.type = 'm3u8'
    opts.customType = {
      m3u8: (videoEl: HTMLVideoElement, url: string) => {
        if (Hls.isSupported()) {
          hls = new Hls({
            xhrSetup: (xhr: XMLHttpRequest, reqUrl: string) => {
              if (reqUrl.includes('/key') || reqUrl.includes('enc.key') || reqUrl.includes('key.bin')) {
                if (data.key_url) {
                  xhr.open('GET', data.key_url, true)
                }
              }
            },
          })
          hls.loadSource(url)
          hls.attachMedia(videoEl)
        } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
          videoEl.src = url
        }
      },
    }
  } else {
    opts.type = 'mp4'
  }

  player = new Artplayer(opts as ConstructorParameters<typeof Artplayer>[0])

  if (data.is_vip && !data.can_play_full) {
    player.on('video:timeupdate', () => {
      if (player && player.currentTime >= 60) {
        player.pause()
        showVipOverlay.value = true
      }
    })
  }
}

async function loadRelated(current: VideoDetail) {
  try {
    const params: Record<string, unknown> = { per_page: 8 }
    if (current.category?.id) params.category_id = current.category.id
    const { data } = await apiVideos(params)
    relatedVideos.value = data.data.filter(v => v.id !== current.id).slice(0, 8)
  } catch {
    // non-critical
  }
}

watch(() => route.params.id, () => {
  loadVideo()
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

onMounted(() => {
  loadVideo()
})

onUnmounted(() => {
  destroyPlayer()
})
</script>

<template>
  <div>
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else-if="error" class="py-20 text-center">
      <div class="mb-4 text-4xl text-gray-700">:(</div>
      <p class="mb-4 text-gray-500">{{ error }}</p>
      <div class="flex items-center justify-center gap-3">
        <button @click="loadVideo" class="rounded-full bg-amber-500 px-6 py-2 text-sm font-medium text-black transition hover:bg-amber-400">重试</button>
        <RouterLink to="/" class="rounded-full border border-gray-700 px-6 py-2 text-sm text-gray-400 transition hover:border-gray-500 hover:text-white">返回首页</RouterLink>
      </div>
    </div>

    <div v-else-if="video" class="space-y-6">
      <!-- 转码中提示 -->
      <div
        v-if="video.transcode_status && video.transcode_status !== 'done'"
        class="rounded-xl border border-amber-500/30 bg-amber-500/5 px-5 py-4 text-center"
      >
        <template v-if="video.transcode_status === 'pending' || video.transcode_status === 'processing'">
          <div class="mb-2 flex items-center justify-center gap-2">
            <div class="h-4 w-4 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div>
            <span class="font-medium text-amber-400">视频转码中，请稍后刷新...</span>
          </div>
          <p class="text-xs text-gray-500">转码完成后即可播放加密 HLS 流</p>
        </template>
        <template v-else-if="video.transcode_status === 'failed'">
          <span class="font-medium text-red-400">视频转码失败</span>
        </template>
      </div>

      <!-- 播放器区域 -->
      <div class="relative aspect-video w-full overflow-hidden rounded-xl bg-black">
        <div ref="playerRef" class="h-full w-full"></div>
        <VipOverlay v-if="showVipOverlay || !video.play_url" />
      </div>

      <!-- 视频信息 -->
      <div class="space-y-4">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl font-bold">{{ video.title }}</h1>
              <span
                v-if="video.is_vip"
                class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-2 py-0.5 text-xs font-bold text-black"
              >
                VIP
              </span>
            </div>
            <div class="mt-2 flex items-center gap-4 text-sm text-gray-400">
              <span v-if="video.category">{{ video.category.name }}</span>
              <span>{{ formatViews(video.view_count) }} 次播放</span>
              <span>{{ formatDuration(video.duration) }}</span>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-3">
            <button
              @click="toggleFavorite"
              :disabled="favLoading"
              :class="[
                'flex items-center gap-1.5 rounded-full border px-4 py-2 text-sm transition',
                isFavorited
                  ? 'border-red-500/50 bg-red-500/10 text-red-400'
                  : 'border-gray-700 bg-gray-800 text-gray-400 hover:border-gray-500 hover:text-white'
              ]"
            >
              <span class="text-base">{{ isFavorited ? '❤️' : '🤍' }}</span>
              {{ isFavorited ? '已收藏' : '收藏' }}
            </button>

            <RouterLink
              v-if="video.is_vip && !video.can_play_full"
              to="/vip"
              class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-5 py-2 text-sm font-bold text-black transition hover:shadow-lg hover:shadow-amber-500/25"
            >
              开通 VIP 观看完整版
            </RouterLink>
          </div>
        </div>

        <p v-if="video.description" class="text-sm leading-relaxed text-gray-400">{{ video.description }}</p>
      </div>

      <!-- 推荐视频 -->
      <section v-if="relatedVideos.length > 0">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
          <span class="h-5 w-1 rounded-full bg-amber-500"></span>
          推荐视频
        </h2>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
          <VideoCard v-for="v in relatedVideos" :key="v.id" :video="v" />
        </div>
      </section>
    </div>
  </div>
</template>
