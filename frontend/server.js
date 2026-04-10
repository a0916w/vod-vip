import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import express from 'express'
import { createProxyMiddleware } from 'http-proxy-middleware'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const isProduction = process.env.NODE_ENV === 'production'
const port = process.env.PORT || 5173
const apiTarget = process.env.API_URL || 'http://127.0.0.1:8000'
const defaultSiteSettings = {
  browser_title: 'VOD-VIP 影院',
  home_seo_title: 'VOD-VIP 影院 - 精选高清视频点播平台',
  favicon_url: '/favicon.ico',
}

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;')
}

function resolvePageTitle(url, settings) {
  const pathname = new URL(url, 'http://localhost').pathname
  const browserTitle = settings.browser_title || defaultSiteSettings.browser_title
  const homeTitle = settings.home_seo_title || browserTitle

  if (pathname === '/' || pathname === '') return homeTitle

  const routeTitles = {
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

  if (pathname.startsWith('/video/')) return `视频详情 - ${browserTitle}`

  return routeTitles[pathname] ? `${routeTitles[pathname]} - ${browserTitle}` : browserTitle
}

let cachedSettings = null
let cacheTime = 0
const CACHE_TTL = 60_000

async function loadSiteSettings() {
  if (cachedSettings && Date.now() - cacheTime < CACHE_TTL) return cachedSettings
  try {
    const controller = new AbortController()
    const timer = setTimeout(() => controller.abort(), 3000)
    const response = await fetch(`${apiTarget}/api/site-settings`, {
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    })
    clearTimeout(timer)
    if (!response.ok) throw new Error(`Failed: ${response.status}`)
    const data = await response.json()
    cachedSettings = { ...defaultSiteSettings, ...data }
    cacheTime = Date.now()
    return cachedSettings
  } catch {
    return cachedSettings || defaultSiteSettings
  }
}

function injectHead(template, url, settings) {
  const title = escapeHtml(resolvePageTitle(url, settings))
  const favicon = escapeHtml(settings.favicon_url || defaultSiteSettings.favicon_url)

  return template
    .replace(/<title>.*?<\/title>/s, `<title>${title}</title>`)
    .replace(/<link rel="icon" href=".*?" \/>/s, `<link rel="icon" href="${favicon}" />`)
}

async function createServer() {
  const app = express()

  app.use(createProxyMiddleware({ target: apiTarget, changeOrigin: true, pathFilter: '/api' }))

  let vite
  if (!isProduction) {
    const { createServer: createViteServer } = await import('vite')
    vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'custom',
    })
    app.use(vite.middlewares)
  } else {
    const { default: sirv } = await import('sirv')
    const staticMiddleware = sirv(path.resolve(__dirname, 'dist/client'), { gzip: true })
    app.use((req, res, next) => {
      if (req.path === '/') return next()
      return staticMiddleware(req, res, next)
    })
  }

  app.use('*all', async (req, res) => {
    const url = req.originalUrl

    try {
      let template
      let render

      if (!isProduction) {
        template = fs.readFileSync(path.resolve(__dirname, 'index.html'), 'utf-8')
        template = await vite.transformIndexHtml(url, template)
        render = (await vite.ssrLoadModule('/src/entry-server.ts')).render
      } else {
        template = fs.readFileSync(path.resolve(__dirname, 'dist/client/index.html'), 'utf-8')
        render = (await import('./dist/server/entry-server.js')).render
      }

      const siteSettings = await loadSiteSettings()
      const { html: appHtml } = await render(url)
      const html = injectHead(template.replace('<!--ssr-outlet-->', appHtml), url, siteSettings)

      res.status(200).set({ 'Content-Type': 'text/html' }).end(html)
    } catch (e) {
      if (!isProduction) vite.ssrFixStacktrace(e)
      console.error(e)
      res.status(500).end(e.message)
    }
  })

  app.listen(port, () => {
    console.log(`SSR server running at http://localhost:${port}`)
  })
}

createServer()
