import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import './bootstrap'
import { APP_VERSION } from './version.js'

export { APP_VERSION }

// Configure axios pour les cookies CSRF
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

const app = createApp(App)
app.use(router)
app.mount('#app')
