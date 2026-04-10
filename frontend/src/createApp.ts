import { createApp as _createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { createAppRouter } from './router'

export function createApp() {
  const app = _createApp(App)
  const pinia = createPinia()
  const router = createAppRouter()
  app.use(pinia)
  app.use(router)
  return { app, router, pinia }
}
