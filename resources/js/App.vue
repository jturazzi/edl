<template>
    <div v-if="!isGuestRoute" class="min-h-screen flex flex-col app-bg text-gray-900">

        <!-- Barre de navigation -->
        <header class="bg-white/90 backdrop-blur-md border-b border-gray-200/80 sticky top-0 z-50 shadow-sm" role="banner">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-14">

                    <!-- Logo + nom -->
                    <router-link to="/" class="flex items-center gap-2.5 min-w-0 group" aria-label="Accueil">
                        <svg class="w-5 h-5 shrink-0 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-sm sm:text-base font-bold text-gray-900 truncate leading-tight">
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

                        <router-link to="/info" class="nav-pill" aria-label="Administration">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Administration</span>
                        </router-link>

                        <div class="w-px h-5 bg-gray-200 mx-1"></div>

                        <!-- Avatar utilisateur -->
                        <div v-if="user" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 text-sm font-medium text-gray-700 border border-gray-200">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                {{ (user.full_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <span class="truncate max-w-[120px]">{{ user.full_name }}</span>
                        </div>

                        <!-- Déconnexion -->
                        <button @click="logout"
                            class="ml-1 flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-600 font-medium px-2.5 py-2 rounded-lg hover:bg-red-50 transition min-h-[44px]"
                            aria-label="Se déconnecter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                            </svg>
                            <span>Déconnexion</span>
                        </button>
                    </nav>

                    <!-- Contrôles mobile (< md) -->
                    <div class="flex md:hidden items-center gap-1">
                        <!-- Hamburger -->
                        <button @click="navOpen = !navOpen"
                            class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-600 hover:bg-gray-100 transition"
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
            <div v-if="navOpen" class="md:hidden border-t border-gray-100 bg-white/95 backdrop-blur-md px-4 pb-4 pt-2 space-y-1">
                <!-- Utilisateur -->
                <div v-if="user" class="flex items-center gap-3 px-3 py-2.5 mb-2 bg-gradient-to-r from-indigo-50 to-violet-50 rounded-xl border border-indigo-100/80">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center text-sm font-bold shrink-0">
                        {{ (user.full_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ user.full_name }}</p>
                        <p class="text-xs text-indigo-500">Connecté</p>
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

                <router-link to="/info" class="nav-pill w-full" @click="navOpen = false">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Administration</span>
                </router-link>

                <div class="h-px bg-gray-100 my-1"></div>

                <!-- Déconnexion -->
                <button @click="logout(); navOpen = false"
                    class="nav-pill w-full text-left text-red-500 hover:text-red-600 hover:bg-red-50">
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
        <footer class="border-t border-gray-200/80 bg-white/80 backdrop-blur-sm py-5 px-4 sm:px-6 mt-auto">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-400">
                <div class="inline-flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-indigo-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="font-semibold text-gray-500">{{ appName }}</span>
                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                    <span class="font-mono">{{ appVersion }}</span>
                </div>

                <a href="https://github.com/jturazzi/edl" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5C5.73.5.5 5.73.5 12c0 5.08 3.29 9.39 7.86 10.91.58.1.79-.25.79-.56 0-.28-.01-1.02-.02-2-3.2.7-3.88-1.54-3.88-1.54-.52-1.33-1.28-1.68-1.28-1.68-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.75 2.68 1.25 3.34.95.1-.74.4-1.25.72-1.54-2.56-.29-5.25-1.28-5.25-5.71 0-1.26.45-2.29 1.18-3.09-.12-.29-.51-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.79 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.24 2.76.12 3.05.74.8 1.18 1.83 1.18 3.09 0 4.44-2.7 5.42-5.27 5.7.42.36.78 1.07.78 2.16 0 1.56-.01 2.82-.01 3.2 0 .31.21.67.8.56A10.51 10.51 0 0 0 23.5 12C23.5 5.73 18.27.5 12 .5Z"/></svg>
                    <span class="font-medium">GitHub</span>
                </a>
            </div>
        </footer>
    </div>

    <!-- Page login -->
    <main v-else class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-100 via-indigo-50/40 to-white px-4">
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
const appLogo = window.__APP_LOGO__ || ''
const appVersion = APP_VERSION

const user = ref(null)
provide('user', user)

const isGuestRoute = computed(() => route.meta.guest === true)

// Mobile nav
const navOpen = ref(false)

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
