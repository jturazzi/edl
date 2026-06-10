<template>
<div class="fade-in-up">
    <!-- En-tête page -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Historique des EDL</h1>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Tous les états des lieux enregistrés.</p>
        </div>
        <router-link to="/"
            class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700
                   text-white font-semibold py-2.5 px-4 rounded-xl text-sm shadow-sm shadow-indigo-200 dark:shadow-none transition min-h-[44px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvel EDL
        </router-link>
    </div>

    <!-- Filtres catégories -->
    <div v-if="!loading && categories.length > 0" class="mb-5 flex flex-wrap gap-2">
        <button @click="filterCategory(null)"
            :class="[
                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-semibold border-2 transition-all',
                activeCategoryId === null
                    ? 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 border-gray-900 dark:border-gray-100'
                    : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-400'
            ]">Tous</button>
        <button v-for="cat in categories" :key="cat.id"
            @click="filterCategory(cat.id)"
            :class="[
                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-semibold border-2 transition-all',
            ]"
            :style="activeCategoryId === cat.id
                ? `background:${cat.color}22; border-color:${cat.color}; color:${cat.color}`
                : 'border-color:#e5e7eb; color:#6b7280; background:transparent'"
        >
            <span class="inline-block w-2 h-2 rounded-full" :style="`background:${cat.color}`"></span>
            {{ cat.name }}
        </button>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" role="status" aria-live="polite" aria-label="Chargement…">
        <div v-for="i in 3" :key="i" class="card rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700/60">
                <div class="flex gap-2 mb-3">
                    <div class="h-5 w-16 bg-gray-200 dark:bg-gray-700 rounded-full animate-pulse"></div>
                    <div class="h-5 w-14 bg-gray-100 dark:bg-gray-700/60 rounded-full animate-pulse"></div>
                </div>
                <div class="h-4 w-3/4 bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse mb-2"></div>
                <div class="h-3 w-1/2 bg-gray-100 dark:bg-gray-700/60 rounded-lg animate-pulse"></div>
            </div>
            <div class="p-4 space-y-2.5">
                <div class="h-3 w-2/3 bg-gray-100 dark:bg-gray-700/60 rounded-lg animate-pulse"></div>
                <div class="h-3 w-1/2 bg-gray-100 dark:bg-gray-700/60 rounded-lg animate-pulse"></div>
                <div class="h-3 w-2/3 bg-gray-100 dark:bg-gray-700/60 rounded-lg animate-pulse"></div>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700/60">
                <div class="h-9 bg-gray-100 dark:bg-gray-700/60 rounded-xl animate-pulse"></div>
            </div>
        </div>
    </div>

    <!-- Vide -->
    <div v-else-if="edls.length === 0" class="card rounded-2xl px-6 py-16 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-base font-semibold text-gray-600 dark:text-gray-400 mb-1">Aucun EDL enregistré</p>
        <p class="text-sm text-gray-400 dark:text-gray-500 mb-5">Créez votre premier état des lieux.</p>
        <router-link to="/" class="btn-primary inline-flex">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Commencer
        </router-link>
    </div>

    <!-- Grille de cartes -->
    <template v-else>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="edl in edls" :key="edl.id"
                class="card card-hover flex flex-col rounded-2xl overflow-hidden">

                <!-- En-tête carte -->
                <div class="p-4 border-b border-gray-100/80 dark:border-gray-700/50">
                    <div class="flex items-start justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="edl.type === 'entrant' ? 'badge-entrant' : 'badge-sortant'">
                                {{ edl.type === 'entrant' ? '🔑 Entrant' : '🚪 Sortant' }}
                            </span>
                            <span v-if="edl.category" class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold border"
                                :style="`background:${edl.category.color}18; border-color:${edl.category.color}55; color:${edl.category.color}`">
                                <span class="inline-block w-1.5 h-1.5 rounded-full" :style="`background:${edl.category.color}`"></span>
                                {{ edl.category.name }}
                            </span>
                        </div>
                        <span v-if="edl.status === 'complete'"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold badge-complet shrink-0">
                            ✓ Terminé
                        </span>
                        <span v-else
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold shrink-0
                                bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 ring-1 ring-amber-300/80 dark:ring-amber-700/60">
                            En cours
                        </span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-snug truncate">{{ edl.adresse }}</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ edl.ville }}</p>
                </div>

                <!-- Corps -->
                <div class="px-4 py-3 flex-1 space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="truncate">{{ locataireFullName(edl) || 'Locataire non renseigné' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-500">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ formatDate(edl.date_edl || edl.created_at) }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-500">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="truncate">{{ edl.user ? (edl.user.firstname || '') + ' ' + (edl.user.lastname || '') : 'Non renseigné' }}</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-4 py-3 border-t border-gray-100/80 dark:border-gray-700/50 flex gap-2">
                    <template v-if="edl.status === 'en_cours'">
                        <router-link :to="{ name: 'survey', params: { id: edl.id } }"
                            class="flex-1 text-center bg-indigo-600 hover:bg-indigo-700
                                   text-white font-semibold py-2.5 rounded-xl text-sm transition min-h-[44px] flex items-center justify-center gap-1.5 shadow-sm shadow-indigo-200 dark:shadow-none">
                            Reprendre
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </router-link>
                        <button @click="askDelete(edl)"
                            class="btn-danger"
                            aria-label="Supprimer l'EDL">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </template>
                    <template v-else>
                        <router-link :to="{ name: 'confirmation', params: { id: edl.id } }"
                            class="flex-1 text-center bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300
                                   font-semibold py-2.5 rounded-xl text-sm transition min-h-[44px] flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Voir
                        </router-link>
                        <a :href="`/edl/${edl.id}/pdf`" target="_blank"
                            class="text-gray-400 dark:text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20
                                   py-2.5 px-3 rounded-xl text-sm transition min-h-[44px] flex items-center justify-center"
                            aria-label="Télécharger le PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </a>
                        <button @click="askDelete(edl)"
                            class="btn-danger"
                            aria-label="Supprimer l'EDL">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="mt-8 flex justify-center gap-1.5">
            <button v-for="page in pagination.lastPage" :key="page"
                @click="loadPage(page)"
                :class="[
                    'px-4 py-2 rounded-xl text-sm font-semibold transition min-h-[40px]',
                    page === pagination.currentPage
                        ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200'
                        : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                ]">
                {{ page }}
            </button>
        </div>
    </template>

    <!-- Modale suppression -->
    <Teleport to="body">
        <div v-if="edlToDelete" class="fixed inset-0 z-50 flex items-center justify-center px-4"
            @click.self="edlToDelete = null">
            <!-- Fond -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="edlToDelete = null"></div>
            <!-- Carte -->
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl shadow-black/20 max-w-sm w-full p-6 fade-in-up">
                <div class="flex items-start gap-4 mb-5">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Supprimer cet EDL ?</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ edlToDelete.adresse }}</span>, {{ edlToDelete.ville }}
                        </p>
                        <p class="mt-1 text-xs text-red-500 dark:text-red-400 font-medium">Cette action est irréversible.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="edlToDelete = null"
                        class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Annuler
                    </button>
                    <button @click="confirmDelete" :disabled="deleteLoading"
                        class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800
                               text-white font-bold text-sm transition disabled:opacity-60">
                        {{ deleteLoading ? 'Suppression…' : 'Supprimer' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const edls = ref([])
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
})
const categories = ref([])
const activeCategoryId = ref(null)

// Suppression
const edlToDelete = ref(null)
const deleteLoading = ref(false)

function askDelete(edl) {
    edlToDelete.value = edl
}

async function confirmDelete() {
    if (!edlToDelete.value) return
    deleteLoading.value = true
    try {
        await axios.delete(`/api/edls/${edlToDelete.value.id}`)
        edls.value = edls.value.filter(e => e.id !== edlToDelete.value.id)
        edlToDelete.value = null
    } catch (e) {
        console.error('Erreur suppression EDL', e)
    } finally {
        deleteLoading.value = false
    }
}

async function loadPage(page = 1) {
    loading.value = true
    try {
        const params = { page }
        if (activeCategoryId.value !== null) params.category_id = activeCategoryId.value
        const { data } = await axios.get('/api/edls', { params })
        edls.value = data.data
        pagination.currentPage = data.current_page
        pagination.lastPage = data.last_page
    } catch (e) {
        console.error('Erreur chargement EDLs', e)
    } finally {
        loading.value = false
    }
}

function filterCategory(id) {
    activeCategoryId.value = id
    loadPage(1)
}

function locataireFullName(edl) {
    return [edl.locataire_prenom, edl.locataire_nom].filter(Boolean).join(' ').trim()
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(async () => {
    const [, catRes] = await Promise.allSettled([
        loadPage(1),
        axios.get('/api/categories'),
    ])
    if (catRes.status === 'fulfilled') categories.value = catRes.value.data
})
</script>
