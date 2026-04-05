import { createSSRApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { createAppRouter } from './router'

export function createApp() {
  const app = createSSRApp(App)
  const pinia = createPinia()
  const router = createAppRouter()
  app.use(pinia)
  app.use(router)
  return { app, router, pinia }
}
