import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiLogin, apiRegister, apiLogout, apiMe, type User } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const isVip = ref(false)
  const isAdmin = ref(false)

  const isLoggedIn = computed(() => !!token.value)

  function setAuth(u: User, t: string) {
    user.value = u
    token.value = t
    localStorage.setItem('token', t)
  }

  async function login(email: string, password: string) {
    const { data } = await apiLogin({ email, password })
    setAuth(data.user, data.token)
    await fetchUser()
  }

  async function register(nickname: string, email: string, password: string, password_confirmation: string) {
    const { data } = await apiRegister({ nickname, email, password, password_confirmation })
    setAuth(data.user, data.token)
    await fetchUser()
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const { data } = await apiMe()
      user.value = data.user
      isVip.value = data.is_vip
      isAdmin.value = data.is_admin ?? false
    } catch {
      logout()
    }
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

  return { user, token, isLoggedIn, isVip, isAdmin, login, register, fetchUser, logout }
})
