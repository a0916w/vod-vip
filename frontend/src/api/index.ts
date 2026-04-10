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
  play_type: 'hls' | 'mp4'
  key_url: string | null
  transcode_status: string | null
  preview_url: string | null
  description: string | null
  duration: number
  view_count: number
  category: Category | null
  vip_required_message: string | null
  vip_trial_seconds: number
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

export interface MarqueeItem {
  id: number
  content: string
  sort_order: number
}

export interface SiteSettings {
  brand_badge: string
  site_name: string
  footer_text: string
  browser_title: string
  home_seo_title: string
  logo_image_url: string
  favicon_url: string
  vip_trial_seconds: number
  search_hint_text: string
  search_hint_color: string
  search_hint_font_size: number
  search_hint_font_weight: 'normal' | 'bold'
  search_hint_tail_color: string
  search_hint_tail_font_size: number
  search_hint_tail_font_weight: 'normal' | 'bold'
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

export const apiMarquees = () =>
  http.get<MarqueeItem[]>('/marquees')

export const apiSiteSettings = () =>
  http.get<SiteSettings>('/site-settings')

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

