import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import express from 'express'
import { createProxyMiddleware } from 'http-proxy-middleware'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const isProduction = process.env.NODE_ENV === 'production'
const port = process.env.PORT || 5173
const apiTarget = process.env.API_URL || 'http://127.0.0.1:8000'

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
    app.use(sirv(path.resolve(__dirname, 'dist/client'), { gzip: true }))
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

      const { html: appHtml } = await render(url)
      const html = template.replace('<!--ssr-outlet-->', appHtml)

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
