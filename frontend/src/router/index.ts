import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/browse',
      name: 'browse',
      component: () => import('@/views/BrowseView.vue'),
    },
    {
      path: '/search',
      name: 'search',
      component: () => import('@/views/SearchView.vue'),
    },
    {
      path: '/video/:id',
      name: 'video',
      component: () => import('@/views/VideoView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
    },
    {
      path: '/favorites',
      name: 'favorites',
      component: () => import('@/views/FavoritesView.vue'),
      meta: { requiresAuth: true, requiresVip: true },
    },
    {
      path: '/vip',
      name: 'vip',
      component: () => import('@/views/VipView.vue'),
    },
    {
      path: '/admin',
      component: () => import('@/views/admin/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        { path: '', name: 'admin-dashboard', component: () => import('@/views/admin/DashboardView.vue') },
        { path: 'videos', name: 'admin-videos', component: () => import('@/views/admin/VideosManage.vue') },
        { path: 'categories', name: 'admin-categories', component: () => import('@/views/admin/CategoriesManage.vue') },
        { path: 'users', name: 'admin-users', component: () => import('@/views/admin/UsersManage.vue') },
        { path: 'orders', name: 'admin-orders', component: () => import('@/views/admin/OrdersManage.vue') },
        { path: 'media', name: 'admin-media', component: () => import('@/views/admin/MediaManage.vue') },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  if (to.meta.requiresAuth && !localStorage.getItem('token')) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresVip || to.meta.requiresAdmin) {
    const { useAuthStore } = await import('@/stores/auth')
    const auth = useAuthStore()
    await auth.waitUntilReady()
    if (to.meta.requiresVip && !auth.isVip) return { path: '/vip' }
    if (to.meta.requiresAdmin && !auth.isAdmin) return { path: '/' }
  }
})

export default router
