import { renderToString } from 'vue/server-renderer'
import { createApp } from './createApp'

export async function render(url: string) {
  const { app, router } = createApp()

  router.push(url)
  await router.isReady()

  const html = await renderToString(app)
  return { html }
}
