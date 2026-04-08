<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import Artplayer from 'artplayer'
import Hls from 'hls.js'
import {
  apiVideos, apiVideoDetail, apiCreateVideo, apiUpdateVideo,
  apiDeleteVideo, apiRetranscodeVideo, apiCategories,
  type Video, type Category,
} from '@/api'
import Pagination from '@/components/Pagination.vue'

const videos = ref<Video[]>([])
const categories = ref<Category[]>([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const keyword = ref('')

const showModal = ref(false)
const editingId = ref<number | null>(null)
const form = ref({ title: '', cover_url: '', video_url: '', preview_url: '', is_vip: false, category_id: 0, description: '', duration: 0 })
const saving = ref(false)
const error = ref('')

const showPlayer = ref(false)
const playerRef = ref<HTMLDivElement>()
let artPlayer: Artplayer | null = null
let hlsInstance: Hls | null = null

async function load(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page }
    if (keyword.value) params.keyword = keyword.value
    const { data } = await apiVideos(params)
    videos.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
  } finally { loading.value = false }
}

async function loadCategories() {
  const { data } = await apiCategories()
  categories.value = data
}

function openCreate() {
  editingId.value = null
  form.value = { title: '', cover_url: '', video_url: '', preview_url: '', is_vip: false, category_id: categories.value[0]?.id ?? 0, description: '', duration: 0 }
  showModal.value = true
}

function openEdit(v: Video) {
  editingId.value = v.id
  form.value = { title: v.title, cover_url: v.cover_url, video_url: v.video_url ?? '', preview_url: v.preview_url ?? '', is_vip: v.is_vip, category_id: v.category_id, description: v.description ?? '', duration: v.duration }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editingId.value) {
      await apiUpdateVideo(editingId.value, { ...form.value })
    } else {
      await apiCreateVideo({ ...form.value })
    }
    showModal.value = false
    load(currentPage.value)
  } catch (err: any) {
    alert(err.response?.data?.message || '保存失败')
  } finally { saving.value = false }
}

async function remove(id: number) {
  if (!confirm('确定删除？')) return
  try {
    await apiDeleteVideo(id)
    load(currentPage.value)
  } catch (err: any) {
    alert(err.response?.data?.message || '删除失败')
  }
}

async function retranscode(id: number) {
  if (!confirm('确定重新转码？')) return
  try {
    await apiRetranscodeVideo(id)
    alert('已加入转码队列')
    load(currentPage.value)
  } catch (err: any) {
    alert(err.response?.data?.message || '转码请求失败')
  }
}

function destroyPlayer() {
  hlsInstance?.destroy()
  hlsInstance = null
  artPlayer?.destroy()
  artPlayer = null
}

async function preview(id: number) {
  destroyPlayer()
  showPlayer.value = true
  await nextTick()
  if (!playerRef.value) return

  let data
  try {
    const res = await apiVideoDetail(id)
    data = res.data
  } catch (err: any) {
    alert(err.response?.data?.message || '获取视频信息失败')
    showPlayer.value = false
    return
  }
  if (!data.play_url) { alert('该视频暂无可播放地址'); showPlayer.value = false; return }

  const opts: Record<string, unknown> = {
    container: playerRef.value,
    url: data.play_url,
    autoplay: true,
    fullscreen: true,
    theme: '#f59e0b',
    volume: 0.5,
  }

  if (data.play_type === 'hls') {
    opts.type = 'm3u8'
    opts.customType = {
      m3u8: (videoEl: HTMLVideoElement, url: string) => {
        if (Hls.isSupported()) {
          hlsInstance = new Hls({
            xhrSetup: (xhr: XMLHttpRequest, reqUrl: string) => {
              if (reqUrl.includes('/key') || reqUrl.includes('enc.key')) {
                if (data.key_url) xhr.open('GET', data.key_url, true)
              }
            },
          })
          hlsInstance.loadSource(url)
          hlsInstance.attachMedia(videoEl)
        } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
          videoEl.src = url
        }
      },
    }
  } else {
    opts.type = 'mp4'
  }

  artPlayer = new Artplayer(opts as unknown as ConstructorParameters<typeof Artplayer>[0])
}

function closePlayer() {
  destroyPlayer()
  showPlayer.value = false
}

function statusLabel(s: string | null): string {
  if (!s) return 'MP4'
  const map: Record<string, string> = { pending: '排队中', processing: '转码中', done: 'HLS', failed: '失败' }
  return map[s] ?? s
}

function statusColor(s: string | null): string {
  if (!s) return 'text-gray-500'
  const map: Record<string, string> = { pending: 'text-yellow-400', processing: 'text-blue-400', done: 'text-green-400', failed: 'text-red-400' }
  return map[s] ?? 'text-gray-500'
}

onMounted(() => { loadCategories(); load() })
onUnmounted(() => destroyPlayer())
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-2xl font-bold">视频管理</h1>
      <button @click="openCreate" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black hover:bg-amber-400">+ 新增视频</button>
    </div>

    <div class="mb-4 flex gap-2">
      <input v-model="keyword" @keyup.enter="load(1)" placeholder="搜索标题..." class="rounded-lg border border-gray-700 bg-gray-900 px-4 py-2 text-sm text-white outline-none focus:border-amber-500" />
      <button @click="load(1)" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">搜索</button>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-gray-800">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-800 bg-gray-900/50 text-left text-gray-400">
          <th class="px-4 py-3">ID</th><th class="px-4 py-3">标题</th><th class="px-4 py-3">分类</th><th class="px-4 py-3">VIP</th><th class="px-4 py-3">格式</th><th class="px-4 py-3">播放</th><th class="px-4 py-3">操作</th>
        </tr></thead>
        <tbody>
          <tr v-if="videos.length === 0"><td colspan="7" class="px-4 py-12 text-center text-gray-500">暂无视频</td></tr>
          <tr v-for="v in videos" :key="v.id" class="border-b border-gray-800/50 hover:bg-gray-900/30">
            <td class="px-4 py-3 text-gray-500">{{ v.id }}</td>
            <td class="px-4 py-3">{{ v.title }}</td>
            <td class="px-4 py-3 text-gray-400">{{ v.category?.name }}</td>
            <td class="px-4 py-3"><span :class="v.is_vip ? 'text-amber-400' : 'text-gray-600'">{{ v.is_vip ? 'VIP' : '免费' }}</span></td>
            <td class="px-4 py-3"><span :class="statusColor(v.transcode_status)">{{ statusLabel(v.transcode_status) }}</span></td>
            <td class="px-4 py-3 text-gray-500">{{ v.view_count }}</td>
            <td class="px-4 py-3 space-x-2">
              <button @click="preview(v.id)" class="text-green-400 hover:underline">预览</button>
              <button @click="openEdit(v)" class="text-blue-400 hover:underline">编辑</button>
              <button v-if="v.video_url" @click="retranscode(v.id)" class="text-yellow-400 hover:underline">转码</button>
              <button @click="remove(v.id)" class="text-red-400 hover:underline">删除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading" class="mt-4">
      <Pagination :current-page="currentPage" :last-page="lastPage" @change="load" />
    </div>

    <!-- 视频预览弹窗 -->
    <Teleport to="body">
      <div v-if="showPlayer" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" @click.self="closePlayer">
        <div class="w-full max-w-3xl">
          <div class="mb-2 flex justify-end">
            <button @click="closePlayer" class="rounded bg-gray-800 px-3 py-1 text-sm text-gray-400 hover:bg-gray-700">关闭</button>
          </div>
          <div ref="playerRef" class="aspect-video w-full rounded-xl bg-black"></div>
        </div>
      </div>
    </Teleport>

    <!-- 编辑/新增弹窗 -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showModal = false">
        <div class="w-full max-w-lg rounded-2xl bg-gray-900 p-6">
          <h3 class="mb-4 text-lg font-bold">{{ editingId ? '编辑视频' : '新增视频' }}</h3>
          <form @submit.prevent="save" class="space-y-3">
            <input v-model="form.title" placeholder="标题" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <input v-model="form.video_url" placeholder="视频相对路径 (如 telegram/videos/xxx.mp4)" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <input v-model="form.cover_url" placeholder="封面链接" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <input v-model="form.preview_url" placeholder="试看链接（可选）" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <div class="flex gap-3">
              <select v-model="form.category_id" class="flex-1 rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none">
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <label class="flex items-center gap-2 text-sm text-gray-300">
                <input type="checkbox" v-model="form.is_vip" class="accent-amber-500" /> VIP 专属
              </label>
            </div>
            <input v-model.number="form.duration" type="number" placeholder="时长(秒)" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500" />
            <textarea v-model="form.description" placeholder="简介" rows="2" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-500"></textarea>
            <div class="flex justify-end gap-2">
              <button type="button" @click="showModal = false" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-gray-400">取消</button>
              <button type="submit" :disabled="saving" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black disabled:opacity-50">{{ saving ? '保存中...' : '保存' }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
