import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiLogin, apiRegister, apiQuickRegister, apiLogout, apiMe, type User } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const isVip = ref(false)
  const isAdmin = ref(false)
  const userLoaded = ref(false)
  let fetchPromise: Promise<void> | null = null

  const isLoggedIn = computed(() => !!token.value)

  function setAuth(u: User, t: string) {
    user.value = u
    token.value = t
    localStorage.setItem('token', t)
  }

  async function login(account: string, password: string) {
    const { data } = await apiLogin({ account, password })
    setAuth(data.user, data.token)
    await fetchUser()
  }

  async function register(nickname: string, password: string, password_confirmation: string) {
    const { data } = await apiRegister({ nickname, password, password_confirmation })
    setAuth(data.user, data.token)
    await fetchUser()
  }

  async function quickRegister(): Promise<{ nickname: string; password: string }> {
    const { data } = await apiQuickRegister()
    setAuth(data.user, data.token)
    await fetchUser()
    return { nickname: data.plain_nickname, password: data.plain_password }
  }

  async function fetchUser() {
    if (!token.value) { userLoaded.value = true; return }
    if (fetchPromise) return fetchPromise
    fetchPromise = (async () => {
      try {
        const { data } = await apiMe()
        user.value = data.user
        isVip.value = data.is_vip
        isAdmin.value = data.is_admin ?? false
      } catch {
        logout()
      } finally {
        userLoaded.value = true
        fetchPromise = null
      }
    })()
    return fetchPromise
  }

  async function waitUntilReady() {
    if (userLoaded.value) return
    await fetchUser()
  }

  function logout() {
    if (token.value) {
      apiLogout().catch(() => {})
    }
    user.value = null
    token.value = null
    isVip.value = false
    isAdmin.value = false
    localStorage.removeItem('token')
  }

  return { user, token, isLoggedIn, isVip, isAdmin, userLoaded, login, register, quickRegister, fetchUser, waitUntilReady, logout }
})
