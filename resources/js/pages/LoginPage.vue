<template>
<q-card class="w-full max-w-md mx-auto" style="border-radius: 16px">
    <q-card-section class="text-center pt-8 pb-2">
        <img :src="appLogo" :alt="appName" class="h-14 sm:h-20 w-auto mx-auto mb-3">
        <h1 class="text-h5 font-bold m-0">{{ appName }}</h1>
        <p class="text-body2 text-grey-7 mt-1 mb-0">Gestion des états des lieux</p>
    </q-card-section>

    <q-card-section class="space-y-4 pb-8">
        <q-banner v-if="error" rounded class="bg-red-1 text-negative">
            <template #avatar><q-icon name="mdi-alert-outline" color="negative" /></template>
            {{ error }}
        </q-banner>

        <q-btn href="/auth/microsoft" unelevated no-caps size="lg" color="grey-10" class="w-full">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 21 21" class="shrink-0 mr-3">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            <span class="text-body2 sm:text-body1">Se connecter avec Microsoft 365</span>
        </q-btn>
    </q-card-section>
</q-card>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const appName = window.__APP_NAME__ || 'État des lieux'
const appLogo = window.__APP_LOGO__ || '/images/logo.png'
const error = computed(() => route.query.error || null)
</script>
