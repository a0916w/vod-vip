import { createRouter, createWebHistory, type Router } from 'vue-router'

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/HomeView.vue') },
  { path: '/browse', name: 'browse', component: () => import('@/views/BrowseView.vue') },
  { path: '/search', name: 'search', component: () => import('@/views/SearchView.vue') },
  { path: '/video/:id', name: 'video', component: () => import('@/views/VideoView.vue') },
  { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue') },
  { path: '/register', name: 'register', component: () => import('@/views/RegisterView.vue') },
  { path: '/account', name: 'account', component: () => import('@/views/AccountView.vue'), meta: { requiresAuth: true } },
  { path: '/account/orders', name: 'account-orders', component: () => import('@/views/OrderHistoryView.vue'), meta: { requiresAuth: true } },
  { path: '/account/favorites', name: 'account-favorites', component: () => import('@/views/FavoriteHistoryView.vue'), meta: { requiresAuth: true } },
  { path: '/favorites', name: 'favorites', component: () => import('@/views/FavoritesView.vue'), meta: { requiresAuth: true, requiresVip: true } },
  { path: '/vip', name: 'vip', component: () => import('@/views/VipView.vue'), meta: { requiresAuth: true } },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue') },
]

export function createAppRouter(): Router {
  const router = createRouter({
    history: createWebHistory(),
    routes,
  })

  router.beforeEach(async (to) => {
    if (to.meta.requiresAuth && !localStorage.getItem('token')) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }

    if (to.meta.requiresVip) {
      const { useAuthStore } = await import('@/stores/auth')
      const auth = useAuthStore()
      await auth.waitUntilReady()
      if (!auth.isVip) return { path: '/vip' }
    }
  })

  return router
}
