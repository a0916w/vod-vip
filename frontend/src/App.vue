<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { apiMarquees, apiSiteSettings, type MarqueeItem, type SiteSettings } from '@/api'

const auth = useAuthStore()
const route = useRoute()
const mobileMenuOpen = ref(false)
const marqueeItems = ref<MarqueeItem[]>([])
const logoImageFailed = ref(false)
const siteSettings = ref<SiteSettings>({
  brand_badge: 'VOD',
  site_name: 'VIP 影院',
  footer_text: 'VOD-VIP. All rights reserved.',
  browser_title: 'VOD-VIP 影院',
  home_seo_title: 'VOD-VIP 影院 - 精选高清视频点播平台',
  logo_image_url: '',
  favicon_url: '/favicon.ico',
  vip_trial_seconds: 30,
  search_hint_text: '',
  search_hint_color: '#f8fafc',
  search_hint_font_size: 14,
  search_hint_font_weight: 'normal',
  search_hint_tail_color: '#f59e0b',
  search_hint_tail_font_size: 14,
  search_hint_tail_font_weight: 'bold',
})
const marqueeVisible = computed(() => marqueeItems.value.length > 0)
const marqueeText = computed(() => marqueeItems.value.map((item) => item.content).join('   ·   '))
const showLogoImage = computed(() => !!siteSettings.value.logo_image_url && !logoImageFailed.value)

watch(() => route.path, () => { mobileMenuOpen.value = false })
watch(() => siteSettings.value.logo_image_url, () => { logoImageFailed.value = false })

function pageTitle() {
  const browserTitle = siteSettings.value.browser_title || siteSettings.value.site_name
  const homeTitle = siteSettings.value.home_seo_title || browserTitle

  if (route.path === '/') return homeTitle

  const routeTitles: Record<string, string> = {
    '/browse': '分类浏览',
    '/search': '搜索',
    '/login': '登录',
    '/register': '注册',
    '/account': '个人中心',
    '/account/orders': '购买记录',
    '/account/favorites': '收藏记录',
    '/favorites': '我的收藏',
    '/vip': 'VIP 会员',
  }

  if (route.path.startsWith('/video/')) return `视频详情 - ${browserTitle}`

  const current = routeTitles[route.path]
  return current ? `${current} - ${browserTitle}` : browserTitle
}

function syncDocumentHead() {
  document.title = pageTitle()

  const href = siteSettings.value.favicon_url || '/favicon.ico'
  let link = document.querySelector("link[rel='icon']") as HTMLLinkElement | null
  if (!link) {
    link = document.createElement('link')
    link.rel = 'icon'
    document.head.appendChild(link)
  }
  link.href = href
}

async function loadMarquees() {
  try {
    const { data } = await apiMarquees()
    marqueeItems.value = data
  } catch {
    marqueeItems.value = []
  }
}

async function loadSiteSettings() {
  try {
    const { data } = await apiSiteSettings()
    siteSettings.value = data
  } catch {
    siteSettings.value = {
      brand_badge: 'VOD',
      site_name: 'VIP 影院',
      footer_text: 'VOD-VIP. All rights reserved.',
      browser_title: 'VOD-VIP 影院',
      home_seo_title: 'VOD-VIP 影院 - 精选高清视频点播平台',
      logo_image_url: '',
      favicon_url: '/favicon.ico',
      vip_trial_seconds: 30,
      search_hint_text: '',
      search_hint_color: '#f8fafc',
      search_hint_font_size: 14,
      search_hint_font_weight: 'normal',
      search_hint_tail_color: '#f59e0b',
      search_hint_tail_font_size: 14,
      search_hint_tail_font_weight: 'bold',
    }
  }

  syncDocumentHead()
}

onMounted(() => {
  auth.fetchUser()
  loadMarquees()
  loadSiteSettings()
})

watch(
  () => [
    route.path,
    siteSettings.value.browser_title,
    siteSettings.value.home_seo_title,
    siteSettings.value.favicon_url,
    siteSettings.value.site_name,
  ],
  () => {
    syncDocumentHead()
  },
)
</script>

<template>
  <div class="min-h-screen text-white">
    <div class="flex min-h-screen w-full flex-col">
    <!-- 顶部导航 -->
    <nav class="sticky top-0 z-[1000] bg-white/[0.09] backdrop-blur-xl shadow-[0_8px_30px_rgba(15,23,42,0.12)]">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
        <!-- 左侧：Logo + 导航 -->
        <div class="flex items-center gap-6">
          <RouterLink to="/" class="flex items-center gap-2 text-lg font-bold tracking-tight">
            <img
              v-if="showLogoImage"
              :src="siteSettings.logo_image_url"
              :alt="siteSettings.site_name"
              class="h-8 w-8 object-contain"
              @error="logoImageFailed = true"
            />
            <span v-else class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-2 py-0.5 text-xs font-black text-black">{{ siteSettings.brand_badge }}</span>
            {{ siteSettings.site_name }}
          </RouterLink>
          <div class="hidden items-center gap-1 md:flex">
            <RouterLink to="/" class="rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">首页</RouterLink>
            <RouterLink v-if="auth.isLoggedIn && auth.isVip" to="/favorites" class="rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white">我的收藏</RouterLink>
          </div>
        </div>

        <!-- 右侧：搜索 + 用户 + 汉堡 -->
        <div class="flex items-center gap-3">
          <RouterLink to="/search" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white/5 hover:text-white" title="搜索">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
          </RouterLink>
          <template v-if="auth.isLoggedIn">
            <RouterLink to="/account" class="hidden items-center gap-2 rounded-full px-2 py-1 transition hover:bg-white/5 md:flex">
              <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-xs font-bold text-black">
                {{ auth.user?.nickname?.charAt(0) }}
              </div>
              <span v-if="auth.isVip" class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-400">VIP</span>
            </RouterLink>
            <button @click="auth.logout()" class="hidden rounded-lg px-2.5 py-1.5 text-xs text-slate-300/85 transition hover:bg-white/5 hover:text-red-300 md:block">退出</button>
          </template>
          <template v-else>
            <RouterLink to="/login" class="hidden rounded-lg px-3 py-1.5 text-sm text-gray-300 transition hover:bg-white/5 hover:text-white md:block">登录</RouterLink>
            <RouterLink to="/register" class="hidden rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-4 py-1.5 text-sm font-medium text-black transition hover:shadow-lg hover:shadow-amber-500/20 md:block">注册</RouterLink>
          </template>
          <!-- 移动端汉堡菜单 -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-white/5 hover:text-white md:hidden">
            <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- 移动端菜单面板 -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="mobileMenuOpen" class="absolute inset-x-0 top-full bg-[#1e2d42] shadow-2xl md:hidden">
          <div class="mx-auto max-w-7xl space-y-1 px-4 py-3">
            <RouterLink to="/" class="block rounded-lg px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/8 hover:text-white">首页</RouterLink>
            <RouterLink to="/browse" class="block rounded-lg px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/8 hover:text-white">分类浏览</RouterLink>
            <RouterLink to="/search" class="block rounded-lg px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/8 hover:text-white">搜索</RouterLink>
            <template v-if="auth.isLoggedIn">
              <RouterLink to="/account" class="block rounded-lg px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/8 hover:text-white">个人中心</RouterLink>
              <RouterLink v-if="auth.isVip" to="/favorites" class="block rounded-lg px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/8 hover:text-white">我的收藏</RouterLink>
              <button @click="auth.logout(); mobileMenuOpen = false" class="block w-full rounded-lg px-3 py-2.5 text-left text-sm text-red-300 transition hover:bg-white/8">退出登录</button>
            </template>
            <template v-else>
              <RouterLink to="/login" class="block rounded-lg px-3 py-2.5 text-sm text-gray-200 transition hover:bg-white/8 hover:text-white">登录</RouterLink>
              <RouterLink to="/register" class="block rounded-lg px-3 py-2.5 text-sm text-amber-400 transition hover:bg-white/8">注册</RouterLink>
            </template>
          </div>
        </div>
      </Transition>
    </nav>

    <div
      v-if="marqueeVisible"
      class="w-full overflow-hidden bg-[#ff9800] px-2 py-1.5"
    >
      <div class="mx-auto max-w-7xl overflow-hidden">
      <div class="marquee-track">
        <span class="marquee-content text-xs font-medium text-amber-50/95">
          {{ marqueeText }}
        </span>
        <span class="marquee-content text-xs font-medium text-amber-50/95" aria-hidden="true">
          {{ marqueeText }}
        </span>
      </div>
      </div>
    </div>

    <!-- 内容区 -->
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-5 sm:px-5 sm:py-7">
      <RouterView />
    </main>

    <!-- Footer -->
    <footer class="bg-white/[0.07]">
      <div class="mx-auto max-w-7xl px-4 py-6">
        <div class="grid grid-cols-1 items-center gap-4 md:grid-cols-10 md:gap-6">
          <div class="flex items-center gap-2 md:col-span-3">
            <img
              v-if="showLogoImage"
              :src="siteSettings.logo_image_url"
              :alt="siteSettings.site_name"
              class="h-6 w-6 object-contain"
              @error="logoImageFailed = true"
            />
            <span v-else class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-1.5 py-0.5 text-[10px] font-black text-black">{{ siteSettings.brand_badge }}</span>
            <span class="text-sm font-medium text-gray-400">{{ siteSettings.site_name }}</span>
          </div>
          <div class="text-xs leading-5 text-slate-300/70 md:col-span-7 md:text-right">
            &copy; {{ new Date().getFullYear() }} {{ siteSettings.footer_text }}
          </div>
        </div>
      </div>
    </footer>
    </div>
  </div>
</template>
