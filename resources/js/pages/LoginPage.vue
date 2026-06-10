<template>
<div class="w-full max-w-md mx-auto px-6">

    <div class="bg-white dark:bg-gray-800/90 rounded-2xl border border-gray-200/80 dark:border-gray-700/60 shadow-xl shadow-gray-200/50 dark:shadow-black/30 p-8">

        <!-- Logo + titre -->
        <div class="text-center mb-7">
            <img :src="appLogo" :alt="appName" class="h-20 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ appName }}</h1>
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">Gestion des états des lieux</p>
        </div>

        <!-- Erreur -->
        <div v-if="error"
            class="mb-5 flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-400 rounded-xl px-4 py-3">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <p class="text-sm font-medium">{{ error }}</p>
        </div>

        <div class="h-px bg-gray-100 dark:bg-gray-700 mb-6"></div>

        <a href="/auth/microsoft"
           class="w-full flex items-center justify-center gap-3 bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold py-3.5 px-6 rounded-xl transition-all duration-150 shadow-sm hover:shadow group">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 21 21" class="shrink-0">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            <span>Se connecter avec Microsoft 365</span>
        </a>
    </div>
</div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const appName = window.__APP_NAME__ || 'État des lieux'
const appLogo = window.__APP_LOGO__ || '/images/logo.png'
const error = computed(() => route.query.error || null)
</script>
