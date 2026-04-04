import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/',
      component: () => import('@/views/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        { path: '', name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
        { path: 'videos', name: 'videos', component: () => import('@/views/VideosManage.vue') },
        { path: 'categories', name: 'categories', component: () => import('@/views/CategoriesManage.vue') },
        { path: 'users', name: 'users', component: () => import('@/views/UsersManage.vue') },
        { path: 'orders', name: 'orders', component: () => import('@/views/OrdersManage.vue') },
        { path: 'media', name: 'media', component: () => import('@/views/MediaManage.vue') },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const token = localStorage.getItem('admin_token')
  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }
  if (to.name === 'login' && token) {
    const { useAuthStore } = await import('@/stores/auth')
    const auth = useAuthStore()
    if (!auth.ready) await auth.fetchUser()
    if (auth.isLoggedIn) return { path: '/' }
  }
  if (to.meta.requiresAuth && token) {
    const { useAuthStore } = await import('@/stores/auth')
    const auth = useAuthStore()
    if (!auth.ready) await auth.fetchUser()
    if (!auth.isLoggedIn) return { name: 'login' }
  }
})

export default router
