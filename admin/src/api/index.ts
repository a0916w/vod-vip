import axios from 'axios'

const http = axios.create({ baseURL: '/api' })

http.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

http.interceptors.response.use(
  (res) => res,
  (err) => {
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
  is_vip: boolean
  category_id: number
  category?: Category
  description: string | null
  duration: number
  view_count: number
  created_at: string
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
export const apiCreateVideo = (data: Record<string, unknown>) => http.post('/admin/videos', data)
export const apiUpdateVideo = (id: number, data: Record<string, unknown>) => http.put(`/admin/videos/${id}`, data)
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
