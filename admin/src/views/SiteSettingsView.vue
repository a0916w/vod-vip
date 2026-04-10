<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { apiSiteSettings, apiUpdateSiteSettings, type SiteSettings } from '@/api'

const loading = ref(false)
const saving = ref(false)
const telegramBot = ref<SiteSettings['telegram_bot']>(null)
const form = ref<SiteSettings>({
  brand_badge: '',
  site_name: '',
  footer_text: '',
  browser_title: '',
  home_seo_title: '',
  logo_image_url: '',
  favicon_url: '',
  vip_trial_seconds: 30,
  search_hint_text: '',
  search_hint_color: '#f8fafc',
  search_hint_font_size: 14,
  search_hint_font_weight: 'normal',
  search_hint_tail_color: '#f59e0b',
  search_hint_tail_font_size: 14,
  search_hint_tail_font_weight: 'bold',
  hls_base_url: '',
  telegram_webhook_url: '',
})

async function load() {
  loading.value = true
  try {
    const { data } = await apiSiteSettings()
    telegramBot.value = data.telegram_bot ?? null
    form.value = {
      ...form.value,
      ...data,
      telegram_bot: undefined,
    }
  } catch (err: any) {
    alert(err.response?.data?.message || '站点设置加载失败')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const { data } = await apiUpdateSiteSettings(form.value)
    telegramBot.value = data.telegram_bot ?? null
    form.value = {
      ...form.value,
      ...data,
      telegram_bot: undefined,
    }
    alert('站点设置已保存')
  } catch (err: any) {
    alert(err.response?.data?.message || '保存失败')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold">站点设置</h1>
      <p class="mt-1 text-sm text-gray-500">配置前端标题、Logo、favicon 和底部版权文案。</p>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-amber-500"></div>
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
      <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <form @submit.prevent="save" class="space-y-4">
          <div>
            <label class="mb-2 block text-sm text-gray-400">徽标文字</label>
            <input
              v-model="form.brand_badge"
              maxlength="20"
              placeholder="例如：VOD"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">站点名称</label>
            <input
              v-model="form.site_name"
              maxlength="50"
              placeholder="例如：VIP 影院"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">底部版权文案</label>
            <input
              v-model="form.footer_text"
              maxlength="120"
              placeholder="例如：VOD-VIP. All rights reserved."
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">浏览器标签页标题</label>
            <input
              v-model="form.browser_title"
              maxlength="80"
              placeholder="例如：VOD-VIP 影院"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">首页 SEO 标题</label>
            <input
              v-model="form.home_seo_title"
              maxlength="120"
              placeholder="例如：VOD-VIP 影院 - 精选高清视频点播平台"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">Logo 图片地址</label>
            <input
              v-model="form.logo_image_url"
              maxlength="500"
              placeholder="例如：https://example.com/logo.png"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">favicon 网站图标</label>
            <input
              v-model="form.favicon_url"
              maxlength="500"
              placeholder="例如：https://example.com/favicon.ico"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">HLS 播放域名</label>
            <input
              v-model="form.hls_base_url"
              maxlength="500"
              placeholder="例如：https://play.example.com/hls/"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
            <p class="mt-1 text-xs text-gray-500">HLS 视频流的 CDN/播放域名前缀。留空则使用本站 /storage/ 路径。示例填入后，hls/30/index.m3u8 会拼接为 https://play.example.com/hls/hls/30/index.m3u8</p>
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">Telegram Webhook 地址</label>
            <input
              v-model="form.telegram_webhook_url"
              maxlength="500"
              placeholder="例如：https://api.example.com/api/telegram/webhook"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
            <p class="mt-1 text-xs text-gray-500">用于记录当前 Telegram Bot 应使用的 webhook 回调地址，方便在后台统一查看和维护。</p>
          </div>

          <div class="rounded-xl border border-gray-800 bg-gray-800/50 p-4">
            <div class="mb-3 text-sm text-amber-300">Telegram Bot 信息</div>
            <div v-if="telegramBot" class="space-y-2 text-sm text-gray-300">
              <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-800 bg-gray-900/60 px-3 py-2">
                <span class="text-gray-400">Bot ID</span>
                <span class="font-medium text-white">{{ telegramBot.id }}</span>
              </div>
              <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-800 bg-gray-900/60 px-3 py-2">
                <span class="text-gray-400">Bot 名称</span>
                <span class="font-medium text-white">{{ telegramBot.name || '未获取到' }}</span>
              </div>
              <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-800 bg-gray-900/60 px-3 py-2">
                <span class="text-gray-400">Bot 用户名</span>
                <span class="font-medium text-white">{{ telegramBot.username ? `@${telegramBot.username}` : '未获取到' }}</span>
              </div>
              <div class="rounded-lg border border-gray-800 bg-gray-900/60 px-3 py-2">
                <div class="mb-1 text-gray-400">Webhook 配置</div>
                <div class="break-all text-xs text-white">{{ telegramBot.webhook_url || '未配置' }}</div>
              </div>
            </div>
            <div v-else class="rounded-lg border border-dashed border-gray-700 px-3 py-4 text-sm text-gray-500">
              当前未配置 Telegram Bot Token
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">VIP 试看秒数</label>
            <input
              v-model.number="form.vip_trial_seconds"
              type="number"
              min="1"
              max="600"
              placeholder="例如：30"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
            <p class="mt-1 text-xs text-gray-500">非 VIP 用户观看 VIP 视频时的免费试看时长（秒）。</p>
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">搜索提示文案（首页搜索框上方）</label>
            <input
              v-model="form.search_hint_text"
              maxlength="120"
              placeholder="例如：每日更新精选内容，点击搜索立即观看"
              class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm text-gray-400">搜索提示文案颜色</label>
            <div class="flex items-center gap-3">
              <input
                v-model="form.search_hint_color"
                type="color"
                class="h-10 w-14 cursor-pointer rounded border border-gray-700 bg-gray-800 p-1"
              />
              <input
                v-model="form.search_hint_color"
                maxlength="7"
                placeholder="#f8fafc"
                class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
              />
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-2 block text-sm text-gray-400">搜索提示字号（px）</label>
              <input
                v-model.number="form.search_hint_font_size"
                type="number"
                min="10"
                max="48"
                class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
              />
            </div>
            <div>
              <label class="mb-2 block text-sm text-gray-400">搜索提示字重</label>
              <select
                v-model="form.search_hint_font_weight"
                class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
              >
                <option value="normal">常规</option>
                <option value="bold">粗体</option>
              </select>
            </div>
          </div>

          <div class="rounded-xl border border-gray-800 bg-gray-800/50 p-4">
            <div class="mb-3 text-sm text-amber-300">最后四个字（单独控制）</div>
            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <label class="mb-2 block text-sm text-gray-400">最后四字颜色</label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.search_hint_tail_color"
                    type="color"
                    class="h-10 w-14 cursor-pointer rounded border border-gray-700 bg-gray-800 p-1"
                  />
                  <input
                    v-model="form.search_hint_tail_color"
                    maxlength="7"
                    placeholder="#f59e0b"
                    class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
                  />
                </div>
              </div>
              <div>
                <label class="mb-2 block text-sm text-gray-400">最后四字字号（px）</label>
                <input
                  v-model.number="form.search_hint_tail_font_size"
                  type="number"
                  min="10"
                  max="48"
                  class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
                />
              </div>
            </div>
            <div class="mt-4">
              <label class="mb-2 block text-sm text-gray-400">最后四字字重</label>
              <select
                v-model="form.search_hint_tail_font_weight"
                class="w-full rounded-xl border border-gray-700 bg-gray-800 px-4 py-3 text-sm text-white outline-none focus:border-amber-500"
              >
                <option value="normal">常规</option>
                <option value="bold">粗体</option>
              </select>
            </div>
          </div>

          <button
            type="submit"
            :disabled="saving"
            class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-black disabled:opacity-50"
          >
            {{ saving ? '保存中...' : '保存设置' }}
          </button>
        </form>
      </div>

      <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <h2 class="text-lg font-bold">预览</h2>
        <div class="mt-4 rounded-2xl border border-white/10 bg-slate-900/80 p-4">
          <div class="flex items-center gap-2 text-lg font-bold text-white">
            <img
              v-if="form.logo_image_url"
              :src="form.logo_image_url"
              alt="logo"
              class="h-8 w-8 rounded object-contain bg-white/5 p-1"
            />
            <span v-else class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-2 py-0.5 text-xs font-black text-black">
              {{ form.brand_badge || 'VOD' }}
            </span>
            <span>{{ form.site_name || 'VIP 影院' }}</span>
          </div>
          <div class="mt-10 flex items-center gap-2 border-t border-white/10 pt-4 text-sm text-gray-400">
            <img
              v-if="form.logo_image_url"
              :src="form.logo_image_url"
              alt="logo"
              class="h-6 w-6 rounded object-contain bg-white/5 p-1"
            />
            <span v-else class="rounded bg-gradient-to-r from-amber-400 to-orange-500 px-1.5 py-0.5 text-[10px] font-black text-black">
              {{ form.brand_badge || 'VOD' }}
            </span>
            <span>{{ form.site_name || 'VIP 影院' }}</span>
          </div>
          <div class="mt-3 text-xs text-slate-300/70">&copy; 2026 {{ form.footer_text || 'VOD-VIP. All rights reserved.' }}</div>
          <div class="mt-6 border-t border-white/10 pt-4 text-xs text-slate-300/80">
            <div>浏览器标题：{{ form.browser_title || 'VOD-VIP 影院' }}</div>
            <div class="mt-1">首页 SEO 标题：{{ form.home_seo_title || 'VOD-VIP 影院 - 精选高清视频点播平台' }}</div>
            <div class="mt-1 truncate">favicon：{{ form.favicon_url || '/favicon.ico' }}</div>
            <div class="mt-1">VIP 试看秒数：{{ form.vip_trial_seconds || 30 }} 秒</div>
            <div class="mt-1">
              搜索提示：
              <span :style="{ color: form.search_hint_color || '#f8fafc', fontSize: `${form.search_hint_font_size || 14}px`, fontWeight: form.search_hint_font_weight || 'normal' }">
                {{ form.search_hint_text || '（未设置）' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
