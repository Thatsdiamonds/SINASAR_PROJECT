import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'


import 'vue-toast-notification/dist/theme-bootstrap.css'
import '@/styles/global.css'
import toast from '@/services/toast'

const app = createApp(App)
const pinia = createPinia()

app.config.errorHandler = (err, vm, info) => {
  console.error(err)
}


app.use(router)
app.use(pinia)
app.mount('#app')
