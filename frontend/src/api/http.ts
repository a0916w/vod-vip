import axios from 'axios'
import { decryptPayload } from './crypto'

const isClient = typeof window !== 'undefined'

const http = axios.create({
  baseURL: '/api',
  timeout: 15000,
  headers: { Accept: 'application/json' },
})

http.interceptors.request.use((config) => {
  if (isClient) {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
  }
  return config
})

http.interceptors.response.use(
  async (res) => {
    if (isClient && res.data && typeof res.data === 'object' && '_e' in res.data) {
      res.data = await decryptPayload(res.data._e as string)
    }
    return res
  },
  async (err) => {
    if (isClient && err.response?.status === 401) {
      const { useAuthStore } = await import('@/stores/auth')
      const auth = useAuthStore()
      auth.logout()
      const { useRouter } = await import('vue-router')
      try { useRouter().push('/login') } catch { window.location.href = '/login' }
    }
    return Promise.reject(err)
  },
)

export default http
