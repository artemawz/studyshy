import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { useAuth } from './composables/useAuth'

const app = createApp(App)

app.use(router)

const { initAuth } = useAuth()
initAuth().finally(() => {
  app.mount('#app')
})
