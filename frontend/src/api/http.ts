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

function isEncrypted(data: unknown): data is { _e: string } {
  return !!data && typeof data === 'object' && '_e' in (data as Record<string, unknown>)
}

http.interceptors.response.use(
  async (res) => {
    if (isClient && isEncrypted(res.data)) {
      try {
        res.data = await decryptPayload(res.data._e)
      } catch {
        // key missing or decrypt failed — return raw data
      }
    }
    return res
  },
  async (err) => {
    if (isClient && isEncrypted(err.response?.data)) {
      try {
        err.response.data = await decryptPayload(err.response.data._e)
      } catch {
        // decrypt failed — keep encrypted body
      }
    }

    if (isClient && err.response?.status === 401) {
      const { useAuthStore } = await import('@/stores/auth')
      const auth = useAuthStore()
      auth.logout()
      try {
        const { useRouter } = await import('vue-router')
        useRouter().push('/login')
      } catch {
        window.location.href = '/login'
      }
    }
    return Promise.reject(err)
  },
)

export default http
