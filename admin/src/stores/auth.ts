import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiLogin, apiMe, apiLogout, type User } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('admin_token'))
  const ready = ref(false)

  const isLoggedIn = computed(() => !!token.value)

  function setAuth(u: User, t: string) {
    user.value = u
    token.value = t
    localStorage.setItem('admin_token', t)
  }

  async function login(account: string, password: string) {
    const { data } = await apiLogin({ account, password })
    setAuth(data.user, data.token)
    const me = await apiMe()
    if (!me.data.is_admin) {
      await apiLogout().catch(() => {})
      logout()
      throw new Error('该账号没有管理员权限')
    }
    user.value = me.data.user
  }

  async function fetchUser() {
    if (!token.value) { ready.value = true; return }
    try {
      const { data } = await apiMe()
      if (!data.is_admin) {
        logout()
        return
      }
      user.value = data.user
    } catch {
      logout()
    } finally {
      ready.value = true
    }
  }

  function logout() {
    if (token.value) apiLogout().catch(() => {})
    user.value = null
    token.value = null
    localStorage.removeItem('admin_token')
  }

  return { user, token, ready, isLoggedIn, login, fetchUser, logout }
})
