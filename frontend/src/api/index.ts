import http from './http'

export interface User {
  id: number
  nickname: string
  email: string
  phone: string | null
  avatar: string | null
  vip_level: number
  vip_expired_at: string | null
}

export interface Video {
  id: number
  title: string
  cover_url: string
  video_url?: string
  preview_url: string | null
  is_vip: boolean
  category_id: number
  description: string | null
  duration: number
  view_count: number
  category?: Category
  created_at: string
}

export interface VideoDetail {
  id: number
  title: string
  cover_url: string
  is_vip: boolean
  can_play_full: boolean
  play_url: string | null
  preview_url: string | null
  description: string | null
  duration: number
  view_count: number
  category: Category | null
  vip_required_message: string | null
  is_favorited: boolean
  created_at: string
}

export interface Category {
  id: number
  name: string
  slug: string
  videos_count?: number
}

export interface VipPlan {
  name: string
  months: number
  price: number
}

export interface Order {
  id: number
  order_no: string
  plan_name: string
  months: number
  amount: string
  status: number
  payment_method: string | null
  paid_at: string | null
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// Auth
export const apiRegister = (data: { nickname: string; password: string; password_confirmation: string }) =>
  http.post<{ user: User; token: string }>('/register', data)

export const apiQuickRegister = () =>
  http.post<{ user: User; token: string; plain_nickname: string; plain_password: string }>('/quick-register')

export const apiLogin = (data: { account: string; password: string }) =>
  http.post<{ user: User; token: string }>('/login', data)

export const apiLogout = () => http.post('/logout')

export const apiMe = () =>
  http.get<{ user: User; is_vip: boolean; vip_expired_at: string | null }>('/me')

// Videos
export const apiVideos = (params?: Record<string, unknown>) =>
  http.get<Paginated<Video>>('/videos', { params })

export const apiVideoDetail = (id: number) =>
  http.get<VideoDetail>(`/videos/${id}`)

export const apiLatestVideos = () =>
  http.get<Video[]>('/videos/latest')

export const apiRecommendedVideos = () =>
  http.get<Video[]>('/videos/recommended')

// Categories
export const apiCategories = () =>
  http.get<Category[]>('/categories')

// VIP
export const apiVipPlans = () =>
  http.get<Record<string, VipPlan>>('/vip/plans')

export const apiCreateOrder = (data: { plan: string; payment_method: string }) =>
  http.post<{ order: Order; payment_params: Record<string, unknown> }>('/vip/order', data)

export const apiMyOrders = () =>
  http.get<Paginated<Order>>('/vip/orders')

// Favorites
export const apiToggleFavorite = (videoId: number) =>
  http.post<{ is_favorited: boolean; message: string }>(`/favorites/${videoId}`)

export const apiFavorites = (params?: Record<string, unknown>) =>
  http.get<Paginated<Video>>('/favorites', { params })

export const apiCheckFavorite = (videoId: number) =>
  http.get<{ is_favorited: boolean }>(`/favorites/check/${videoId}`)

export const apiBatchCheckFavorites = (videoIds: number[]) =>
  http.post<{ favorited_ids: number[] }>('/favorites/batch-check', { video_ids: videoIds })

// Admin
export const apiAdminDashboard = () => http.get('/admin/dashboard')

export const apiAdminVideos = (params?: Record<string, unknown>) => http.get<Paginated<Video>>('/admin/videos', { params })
export const apiAdminCreateVideo = (data: Record<string, unknown>) => http.post('/admin/videos', data)
export const apiAdminUpdateVideo = (id: number, data: Record<string, unknown>) => http.put(`/admin/videos/${id}`, data)
export const apiAdminDeleteVideo = (id: number) => http.delete(`/admin/videos/${id}`)

export const apiAdminCategories = () => http.get<Category[]>('/admin/categories')
export const apiAdminCreateCategory = (data: Record<string, unknown>) => http.post('/admin/categories', data)
export const apiAdminUpdateCategory = (id: number, data: Record<string, unknown>) => http.put(`/admin/categories/${id}`, data)
export const apiAdminDeleteCategory = (id: number) => http.delete(`/admin/categories/${id}`)

export const apiAdminUsers = (params?: Record<string, unknown>) => http.get<Paginated<User>>('/admin/users', { params })
export const apiAdminUpdateUser = (id: number, data: Record<string, unknown>) => http.put(`/admin/users/${id}`, data)
export const apiAdminDeleteUser = (id: number) => http.delete(`/admin/users/${id}`)

export const apiAdminOrders = (params?: Record<string, unknown>) => http.get<Paginated<Order>>('/admin/orders', { params })

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
export const apiAdminMedia = (params?: Record<string, unknown>) => http.get<Paginated<MediaResource>>('/admin/media', { params })
export const apiAdminSyncMedia = (id: number, data: Record<string, unknown>) => http.post(`/admin/media/${id}/sync`, data)
export const apiAdminDeleteMedia = (id: number) => http.delete(`/admin/media/${id}`)
