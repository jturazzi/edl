<template>
    <a href="#main" class="skip-link" @click.prevent="focusMain">Aller au contenu principal</a>
    <q-layout v-if="!isGuestRoute" view="lHh LpR lFf">
        <!-- Barre du haut (mobile / tablette) -->
        <q-header class="app-topbar lt-md" role="banner">
            <q-toolbar class="px-4">
                <router-link to="/" class="flex items-center gap-2.5 min-w-0 no-underline" style="color: inherit" aria-label="Accueil">
                    <span class="inline-flex size-8 items-center justify-center rounded-lg bg-primary text-white"><q-icon name="mdi-home-city-outline" size="20px" /></span>
                    <span class="font-semibold truncate">{{ appName }}</span>
                </router-link>
                <q-space />
                <q-btn flat round dense color="grey-8" icon="mdi-menu" aria-label="Menu" @click="navOpen = true" />
            </q-toolbar>
        </q-header>

        <!-- Barre latérale -->
        <q-drawer v-model="navOpen" show-if-above side="left" :width="248" :breakpoint="1024" class="app-sidebar" role="navigation" aria-label="Navigation principale">
            <div class="flex h-full flex-col p-3">
                <router-link to="/" class="flex items-center gap-2.5 px-2 py-3 no-underline" style="color: inherit" aria-label="Accueil" @click="closeNav">
                    <span class="inline-flex size-9 items-center justify-center rounded-lg bg-primary text-white"><q-icon name="mdi-home-city-outline" size="22px" /></span>
                    <span class="font-semibold leading-tight">{{ appName }}</span>
                </router-link>

                <p class="mt-4 mb-1 px-3 text-overline text-grey-6">Menu</p>
                <nav class="flex flex-col gap-0.5">
                    <router-link v-for="link in links" :key="link.to" :to="link.to" class="side-link"
                        :class="{ 'is-active': isActive(link.to) }" @click="closeNav">
                        <q-icon :name="link.icon" />
                        {{ link.label }}
                    </router-link>
                </nav>

                <q-space />

                <div v-if="user" class="mt-4 flex items-center gap-2.5 rounded-lg border border-slate-200 p-2.5">
                    <q-avatar size="34px" color="blue-1" text-color="primary" class="font-semibold">{{ initial }}</q-avatar>
                    <div class="min-w-0 flex-1">
                        <p class="m-0 truncate text-body2 font-medium">{{ user.full_name }}</p>
                    </div>
                    <q-btn flat round dense size="sm" color="grey-7" icon="mdi-logout" aria-label="Se déconnecter" @click="logout(); closeNav()">
                        <q-tooltip>Se déconnecter</q-tooltip>
                    </q-btn>
                </div>
                <div class="mt-3 flex items-center justify-between px-1 text-caption text-grey-6">
                    <span class="font-mono">{{ appVersion }}</span>
                    <q-btn flat dense round size="sm" color="grey-7" :icon="themeIcon" :aria-label="`Thème : ${themeLabel}. Changer de thème`" @click="nextTheme">
                        <q-tooltip>Thème : {{ themeLabel }}</q-tooltip>
                    </q-btn>
                    <a href="https://github.com/jturazzi/edl" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 text-grey-6 no-underline hover:text-primary">
                        <q-icon name="mdi-github" size="16px" /> GitHub
                    </a>
                </div>
            </div>
        </q-drawer>

        <q-page-container>
            <q-page class="flex flex-col">
                <main id="main" ref="mainEl" tabindex="-1" class="flex-1 mx-auto w-full max-w-6xl px-4 sm:px-8 py-6 sm:py-8">
                    <router-view :key="$route.fullPath" />
                </main>
            </q-page>
        </q-page-container>
    </q-layout>

    <!-- Page login -->
    <main v-else id="main" ref="mainEl" tabindex="-1" class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
        <router-view :key="$route.fullPath" />
    </main>
</template>

<script setup>
import { ref, computed, onMounted, provide, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import axios from 'axios'
import { APP_VERSION } from './version.js'
import { THEMES, storedTheme, saveTheme } from './lib/theme.js'

const $q = useQuasar()

// Accessibilité : lien d'évitement, titre de page et focus ramené sur le contenu à chaque changement de page
const mainEl = ref(null)
const focusMain = () => { mainEl.value?.focus({ preventScroll: false }) }

const route = useRoute()
const router = useRouter()

router.afterEach((to, from) => {
    document.title = `${to.meta.title ? to.meta.title + ' · ' : ''}${window.__APP_NAME__ || 'État des lieux'}`
    // Pas de déplacement du focus au chargement initial ni quand seule la recherche/les filtres changent
    if (from.name && to.path !== from.path) nextTick(focusMain)
})

const appName = window.__APP_NAME__ || 'État des lieux'
const appLogo = window.__APP_LOGO__ || ''
const appVersion = APP_VERSION

// Thème clair / sombre / automatique (réglage de l'appareil)
const theme = ref(storedTheme())
const THEME_META = {
    auto: { icon: 'mdi-theme-light-dark', label: 'automatique' },
    light: { icon: 'mdi-white-balance-sunny', label: 'clair' },
    dark: { icon: 'mdi-weather-night', label: 'sombre' },
}
const themeIcon = computed(() => THEME_META[theme.value].icon)
const themeLabel = computed(() => THEME_META[theme.value].label)
const nextTheme = () => {
    theme.value = THEMES[(THEMES.indexOf(theme.value) + 1) % THEMES.length]
    saveTheme(theme.value)
}

const user = ref(null)
provide('user', user)

const isActive = (to) => (to === '/' ? route.path === '/' : route.path.startsWith(to))

const allLinks = [
    { to: '/', label: 'Tableau de bord', icon: 'mdi-view-dashboard-outline' },
    { to: '/nouveau', label: 'Nouvel EDL', icon: 'mdi-plus-box-outline' },
    { to: '/historique', label: 'Historique', icon: 'mdi-history' },
    { to: '/info', label: 'Administration', icon: 'mdi-shield-account-outline', admin: true },
]
// La page Administration n'est proposée qu'aux administrateurs
const links = computed(() => allLinks.filter((l) => !l.admin || user.value?.is_admin))
const initial = computed(() => (user.value?.full_name || '?').charAt(0).toUpperCase())

const isGuestRoute = computed(() => route.meta.guest === true)

// Mobile nav
const navOpen = ref(false)
const closeNav = () => { if ($q.screen.lt.md) navOpen.value = false }

onMounted(async () => {
    if (isGuestRoute.value) return

    try {
        const { data } = await axios.get('/api/user')
        user.value = data
    } catch (e) {
        router.push({ name: 'login' })
    }
})

const logout = async () => {
    try {
        await axios.post('/logout')
    } catch (e) {}
    router.push({ name: 'login' })
}
</script>
