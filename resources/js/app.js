import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import { Quasar, Dark } from 'quasar'
import { applyTheme, storedTheme } from './lib/theme.js'
import iconSet from 'quasar/icon-set/mdi-v7'
import '../../node_modules/@quasar/extras/exports/mdi-v7/mdi-v7.css'
import * as Sentry from '@sentry/vue'
import { APP_VERSION } from './version.js'

export { APP_VERSION }

// Configure axios pour les cookies CSRF
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

const app = createApp(App)

// Suivi des erreurs (actif seulement si un DSN est configuré côté serveur)
if (window.__SENTRY_DSN__) {
    Sentry.init({
        app,
        dsn: window.__SENTRY_DSN__,
        environment: window.__SENTRY_ENV__ || undefined,
        release: APP_VERSION,
        // Erreurs réseau attendues (coupure de connexion) : inutiles à remonter
        ignoreErrors: ['Network Error', 'Failed to fetch', 'ERR_NETWORK', 'ResizeObserver loop'],
        beforeSend(event, hint) {
            const err = hint?.originalException
            // Rejets de validation / conflits : on garde le statut et l'URL pour comprendre le contexte
            if (err?.isAxiosError && err.response) {
                event.extra = { ...event.extra, status: err.response.status, url: err.config?.url, response: err.response.data }
                event.fingerprint = ['axios', String(err.response.status), err.config?.url?.replace(/\d+/g, ':id')]
            }
            return event
        },
    })
}
app.use(router)
app.use(Quasar, {
    plugins: { Dark },
    iconSet,
    config: {
        brand: {
            primary: '#2563eb',
            secondary: '#0891b2',
            accent: '#fbbf24',
            positive: '#16a34a',
            negative: '#ef4444',
            info: '#0284c7',
            warning: '#f59e0b',
        },
    },
})
applyTheme(storedTheme())

app.mount('#app')

// Nettoyage de l'ancien mode hors-ligne (file d'attente locale supprimée)
try { indexedDB?.deleteDatabase('edl-offline') } catch { /* stockage indisponible */ }
