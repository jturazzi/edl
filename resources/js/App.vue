<template>
    <div v-if="!isGuestRoute" class="min-h-screen flex flex-col app-bg text-gray-900 dark:text-gray-100">

        <!-- Barre de navigation -->
        <header class="bg-white/90 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-700/60 sticky top-0 z-50 shadow-sm" role="banner">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-14">

                    <!-- Logo + nom -->
                    <router-link to="/" class="flex items-center gap-2.5 min-w-0 group" aria-label="Accueil">
                        <span class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate leading-tight">
                            {{ appName }}
                        </span>
                    </router-link>

                    <!-- Navigation desktop (md+) -->
                    <nav class="hidden md:flex items-center gap-1" role="navigation" aria-label="Navigation principale">
                        <router-link to="/" class="nav-pill" aria-label="Nouvel EDL">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Nouvel EDL</span>
                        </router-link>

                        <router-link to="/historique" class="nav-pill" aria-label="Historique">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span>Historique</span>
                        </router-link>

                        <router-link to="/admin/info" class="nav-pill" aria-label="Informations système">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Système</span>
                        </router-link>

                        <div class="w-px h-5 bg-gray-200 dark:bg-gray-600 mx-1"></div>

                        <!-- Avatar utilisateur -->
                        <div v-if="user" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                {{ (user.full_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <span class="truncate max-w-[120px]">{{ user.full_name }}</span>
                        </div>

                        <!-- Dark mode toggle -->
                        <button @click="toggleDarkMode"
                            class="ml-1 flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :aria-label="darkMode ? 'Passer en mode clair' : 'Passer en mode sombre'">
                            <!-- Soleil (mode sombre actif) -->
                            <svg v-if="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                            </svg>
                            <!-- Lune (mode clair actif) -->
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                            </svg>
                        </button>

                        <!-- Déconnexion -->
                        <button @click="logout"
                            class="ml-1 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 font-medium px-2.5 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition min-h-[44px]"
                            aria-label="Se déconnecter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                            </svg>
                            <span>Déconnexion</span>
                        </button>
                    </nav>

                    <!-- Contrôles mobile (< md) -->
                    <div class="flex md:hidden items-center gap-1">
                        <!-- Dark mode toggle mobile -->
                        <button @click="toggleDarkMode"
                            class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :aria-label="darkMode ? 'Mode clair' : 'Mode sombre'">
                            <svg v-if="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                            </svg>
                        </button>

                        <!-- Hamburger -->
                        <button @click="navOpen = !navOpen"
                            class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :aria-expanded="navOpen" aria-label="Menu">
                            <svg v-if="!navOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Drawer mobile -->
            <div v-if="navOpen" class="md:hidden border-t border-gray-100 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md px-4 pb-4 pt-2 space-y-1">
                <!-- Utilisateur -->
                <div v-if="user" class="flex items-center gap-3 px-3 py-2.5 mb-2 bg-gradient-to-r from-indigo-50 to-violet-50 dark:from-indigo-950/40 dark:to-violet-950/30 rounded-xl border border-indigo-100/80 dark:border-indigo-900/30">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center text-sm font-bold shrink-0">
                        {{ (user.full_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ user.full_name }}</p>
                        <p class="text-xs text-indigo-500 dark:text-indigo-400">Connecté</p>
                    </div>
                </div>

                <!-- Liens -->
                <router-link to="/" class="nav-pill w-full" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Nouvel EDL</span>
                </router-link>

                <router-link to="/historique" class="nav-pill w-full" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Historique</span>
                </router-link>

                <router-link to="/admin/info" class="nav-pill w-full" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Informations système</span>
                </router-link>

                <div class="h-px bg-gray-100 dark:bg-gray-700 my-1"></div>

                <!-- Déconnexion -->
                <button @click="logout(); navOpen = false"
                    class="nav-pill w-full text-left text-red-500 hover:text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                    </svg>
                    <span>Se déconnecter</span>
                </button>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 py-6 sm:py-8">
            <router-view v-slot="{ Component }">
                <Transition name="page" mode="out-in">
                    <component :is="Component" :key="$route.fullPath" />
                </Transition>
            </router-view>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200/80 dark:border-gray-700/60 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm py-4 px-4 sm:px-6 mt-auto">
            <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-gray-400 dark:text-gray-500">
                <span class="font-medium">{{ appName }} <span class="text-indigo-400 dark:text-indigo-500">{{ appVersion }}</span></span>
                <div class="flex items-center gap-4">
                    <router-link to="/cgu" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">CGU</router-link>
                    <span>© {{ currentYear }} {{ appName }}</span>
                </div>
            </div>
        </footer>
    </div>

    <!-- Page login -->
    <main v-else class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-100 via-indigo-50/40 to-white dark:from-gray-950 dark:via-indigo-950/20 dark:to-gray-900 px-4">
        <router-view v-slot="{ Component }">
            <Transition name="page" mode="out-in">
                <component :is="Component" :key="$route.fullPath" />
            </Transition>
        </router-view>
    </main>
</template>

<script setup>
import { ref, computed, onMounted, provide } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { APP_VERSION } from './version.js'

const route = useRoute()
const router = useRouter()

const appName = window.__APP_NAME__ || 'État des lieux'
const appLogo = window.__APP_LOGO__ || '/images/favicon.png'
const appVersion = APP_VERSION
const currentYear = new Date().getFullYear()

const user = ref(null)
provide('user', user)

const isGuestRoute = computed(() => route.meta.guest === true)

// Dark mode
const darkMode = ref(false)
const toggleDarkMode = () => {
    darkMode.value = !darkMode.value
    document.documentElement.classList.toggle('dark', darkMode.value)
    localStorage.setItem('darkMode', darkMode.value ? '1' : '0')
}

// Mobile nav
const navOpen = ref(false)

onMounted(async () => {
    // Restaurer le mode sombre depuis localStorage
    const saved = localStorage.getItem('darkMode')
    if (saved === '1') {
        darkMode.value = true
        document.documentElement.classList.add('dark')
    }

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
