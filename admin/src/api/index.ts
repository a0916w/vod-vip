import axios from 'axios'

const API_KEY_HEX = '59faf81527de015abf93ea00493b659d650b045966e24b234c873d54c1073751'

let _cryptoKey: CryptoKey | null = null

async function getCryptoKey(): Promise<CryptoKey> {
  if (_cryptoKey) return _cryptoKey
  const raw = new Uint8Array(API_KEY_HEX.length / 2)
  for (let i = 0; i < API_KEY_HEX.length; i += 2)
    raw[i / 2] = parseInt(API_KEY_HEX.substring(i, i + 2), 16)
  _cryptoKey = await crypto.subtle.importKey('raw', raw, { name: 'AES-CBC' }, false, ['decrypt'])
  return _cryptoKey
}

async function decryptPayload(encoded: string): Promise<unknown> {
  const bytes = Uint8Array.from(atob(encoded), (c) => c.charCodeAt(0))
  const iv = bytes.slice(0, 16)
  const ct = bytes.slice(16)
  const key = await getCryptoKey()
  const dec = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, key, ct)
  return JSON.parse(new TextDecoder().decode(dec))
}

function isEncrypted(data: unknown): data is { _e: string } {
  return !!data && typeof data === 'object' && '_e' in (data as Record<string, unknown>)
}

const http = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

http.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

http.interceptors.response.use(
  async (res) => {
    if (isEncrypted(res.data)) {
      try { res.data = await decryptPayload(res.data._e) } catch { /* pass */ }
    }
    return res
  },
  async (err) => {
    if (isEncrypted(err.response?.data)) {
      try { err.response.data = await decryptPayload(err.response.data._e) } catch { /* pass */ }
    }
    if (err.response?.status === 401) {
      localStorage.removeItem('admin_token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  },
)

export interface User {
  id: number
  nickname: string
  email: string | null
  vip_level: number
  vip_expired_at: string | null
  is_admin: boolean
  created_at: string
}

export interface Video {
  id: number
  title: string
  cover_url: string
  video_url: string | null
  preview_url: string | null
  hls_path: string | null
  transcode_status: string | null
  is_vip: boolean
  category_id: number
  category?: Category
  description: string | null
  duration: number
  view_count: number
  created_at: string
}

export interface VideoPlayInfo {
  play_url: string | null
  play_type: 'hls' | 'mp4'
  key_url: string | null
}

export interface Category {
  id: number
  name: string
  slug: string
  videos_count?: number
}

export interface Order {
  id: number
  order_no: string
  user_id: number
  plan_name: string
  amount: string
  status: number
  created_at: string
  user?: User
}

export interface MediaResource {
  id: number
  telegram_file_id: string
  file_type: string
  file_name: string | null
  local_path: string
  file_size: number
  caption: string | null
  from_username: string | null
  synced_to_video: boolean
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

// Auth
export const apiLogin = (data: { account: string; password: string }) =>
  http.post<{ user: User; token: string }>('/login', data)

export const apiMe = () =>
  http.get<{ user: User; is_vip: boolean; is_admin: boolean; vip_expired_at: string | null }>('/me')

export const apiLogout = () => http.post('/logout')

// Dashboard
export const apiDashboard = () => http.get('/admin/dashboard')

// Videos
export const apiVideos = (params?: Record<string, unknown>) => http.get<Paginated<Video>>('/admin/videos', { params })
export const apiVideoDetail = (id: number) => http.get<Video & VideoPlayInfo>(`/admin/videos/${id}`)
export const apiCreateVideo = (data: Record<string, unknown>) => http.post('/admin/videos', data)
export const apiUpdateVideo = (id: number, data: Record<string, unknown>) => http.put(`/admin/videos/${id}`, data)
export const apiRetranscodeVideo = (id: number) => http.post(`/admin/videos/${id}/retranscode`)
export const apiDeleteVideo = (id: number) => http.delete(`/admin/videos/${id}`)

// Categories
export const apiCategories = () => http.get<Category[]>('/admin/categories')
export const apiCreateCategory = (data: Record<string, unknown>) => http.post('/admin/categories', data)
export const apiUpdateCategory = (id: number, data: Record<string, unknown>) => http.put(`/admin/categories/${id}`, data)
export const apiDeleteCategory = (id: number) => http.delete(`/admin/categories/${id}`)

// Users
export const apiUsers = (params?: Record<string, unknown>) => http.get<Paginated<User>>('/admin/users', { params })
export const apiUpdateUser = (id: number, data: Record<string, unknown>) => http.put(`/admin/users/${id}`, data)
export const apiDeleteUser = (id: number) => http.delete(`/admin/users/${id}`)

// Orders
export const apiOrders = (params?: Record<string, unknown>) => http.get<Paginated<Order>>('/admin/orders', { params })

// Media
export const apiMedia = (params?: Record<string, unknown>) => http.get<Paginated<MediaResource>>('/admin/media', { params })
export const apiSyncMedia = (id: number, data: Record<string, unknown>) => http.post(`/admin/media/${id}/sync`, data)
export const apiDeleteMedia = (id: number) => http.delete(`/admin/media/${id}`)
