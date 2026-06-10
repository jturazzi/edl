<template>
<div>
    <!-- Retour -->
    <div class="mb-5">
        <router-link to="/"
            class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour
        </router-link>
    </div>

    <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-6">🖥️ Informations système</h1>

    <!-- Skeleton global tant que les infos système ne sont pas là -->
    <div v-if="loading" class="space-y-4">
        <div v-for="i in 4" :key="i" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="h-4 w-32 rounded-full bg-gray-200 dark:bg-gray-700 animate-pulse"></div>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="h-3 w-full rounded-full bg-gray-100 dark:bg-gray-700/60 animate-pulse"></div>
                <div class="h-3 w-3/4 rounded-full bg-gray-100 dark:bg-gray-700/60 animate-pulse"></div>
                <div class="h-3 w-1/2 rounded-full bg-gray-100 dark:bg-gray-700/60 animate-pulse"></div>
            </div>
        </div>
    </div>

    <div v-else-if="info" class="space-y-5">

        <!-- Application -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="bg-indigo-50 dark:bg-indigo-900/30 border-b border-indigo-100 dark:border-indigo-800 px-5 py-3">
                <h2 class="text-base font-bold text-indigo-700 dark:text-indigo-300">Application</h2>
            </div>
            <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Version</dt>
                    <dd class="text-sm font-bold text-gray-900 dark:text-white col-span-1 sm:col-span-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold">
                            {{ app().version }}
                        </span>
                    </dd>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Version PHP</dt>
                    <dd class="text-sm font-mono text-gray-800 dark:text-gray-200 col-span-1 sm:col-span-2">{{ php().version }}</dd>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Version Laravel</dt>
                    <dd class="text-sm font-mono text-gray-800 dark:text-gray-200 col-span-1 sm:col-span-2">{{ laravel().version }}</dd>
                </div>
            </dl>
        </div>

        <!-- Serveur & Environnement -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800/60 border-b border-gray-100 dark:border-gray-700 px-5 py-3">
                <h2 class="text-base font-bold text-gray-700 dark:text-gray-200">Serveur</h2>
            </div>
            <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Serveur</dt>
                    <dd class="text-sm font-bold text-gray-800 dark:text-gray-200 col-span-1 sm:col-span-2">{{ info.server }}</dd>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Environnement</dt>
                    <dd class="col-span-1 sm:col-span-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                            :class="app().environment === 'production'
                                ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'">
                            {{ app().environment }}
                        </span>
                    </dd>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Base de données</dt>
                    <dd class="text-sm text-gray-800 dark:text-gray-200 col-span-1 sm:col-span-2">
                        <span class="font-bold">{{ db().driver }}</span>
                        <span v-if="db().database" class="text-gray-400 dark:text-gray-500 ml-1">— {{ db().database }}</span>
                    </dd>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Cache</dt>
                    <dd class="text-sm font-mono text-gray-800 dark:text-gray-200 col-span-1 sm:col-span-2">{{ info.cache }}</dd>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Session</dt>
                    <dd class="text-sm font-mono text-gray-800 dark:text-gray-200 col-span-1 sm:col-span-2">{{ info.session }}</dd>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 px-5 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Fuseau horaire</dt>
                    <dd class="text-sm font-mono text-gray-800 dark:text-gray-200 col-span-1 sm:col-span-2">{{ app().timezone }}</dd>
                </div>
            </dl>
        </div>

        <!-- Statistiques -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="bg-green-50 dark:bg-green-900/20 border-b border-green-100 dark:border-green-800 px-5 py-3">
                <h2 class="text-base font-bold text-green-700 dark:text-green-300">Statistiques</h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-gray-100 dark:bg-gray-700">
                <div class="bg-white dark:bg-gray-800 px-5 py-4 text-center">
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ info.stats.edl_total }}</p>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">EDL total</p>
                </div>
                <div class="bg-white dark:bg-gray-800 px-5 py-4 text-center">
                    <p class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ info.stats.edl_entrant }}</p>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">Entrants</p>
                </div>
                <div class="bg-white dark:bg-gray-800 px-5 py-4 text-center">
                    <p class="text-2xl font-extrabold text-violet-600 dark:text-violet-400">{{ info.stats.edl_sortant }}</p>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">Sortants</p>
                </div>
                <div class="bg-white dark:bg-gray-800 px-5 py-4 text-center">
                    <p class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ info.stats.edl_complete }}</p>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">Complétés</p>
                </div>
            </div>
            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                    <span><strong class="text-gray-700 dark:text-gray-300">{{ info.stats.edl_en_cours }}</strong> en cours</span>
                </div>
            </div>
        </div>

        <!-- Catégories -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800 px-5 py-3 flex items-center justify-between">
                <h2 class="text-base font-bold text-emerald-700 dark:text-emerald-300">Catégories</h2>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ categories.length }} catégorie{{ categories.length !== 1 ? 's' : '' }}</span>
            </div>

            <!-- Formulaire ajout -->
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <form @submit.prevent="addCategory" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Nom</label>
                        <input v-model="newCat.name" type="text" placeholder="ex : Résidence du Parc"
                            class="field-input text-sm py-2"
                            :class="{ '!border-red-400': catError }"
                            maxlength="80">
                        <p v-if="catError" class="text-red-500 text-xs mt-1">{{ catError }}</p>
                    </div>
                    <div class="shrink-0">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Couleur</label>
                        <input v-model="newCat.color" type="color"
                            class="h-9 w-14 rounded-xl border border-gray-200 dark:border-gray-600 cursor-pointer p-0.5 bg-white dark:bg-gray-700">
                    </div>
                    <button type="submit" :disabled="catAdding"
                        class="btn-primary py-2 px-4 text-sm shrink-0">
                        <svg v-if="!catAdding" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Ajouter
                    </button>
                </form>
            </div>

            <!-- Liste catégories -->
            <div v-if="categories.length === 0" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                Aucune catégorie pour l'instant.
            </div>
            <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                <li v-for="cat in categories" :key="cat.id"
                    class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-block w-4 h-4 rounded-full shrink-0 border border-white/50 shadow-sm"
                            :style="`background:${cat.color}`"></span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ cat.name }}</span>
                    </div>
                    <button @click="deleteCategory(cat)"
                        class="text-gray-400 dark:text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20
                               p-2 rounded-lg transition"
                        :aria-label="`Supprimer ${cat.name}`">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Journal d'activité -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800/60 border-b border-gray-100 dark:border-gray-700 px-5 py-3 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-700 dark:text-gray-200">Journal d'activité</h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ logs.length }} entrée{{ logs.length !== 1 ? 's' : '' }}</span>
                    <button @click="loadLogs" :disabled="logsLoading"
                        class="text-xs font-semibold text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 transition disabled:opacity-40">
                        ↺ Rafraîchir
                    </button>
                </div>
            </div>

            <div v-if="logsLoading" class="px-5 py-8 text-center text-sm text-gray-400">
                <svg class="w-5 h-5 animate-spin mx-auto mb-2 text-indigo-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Chargement…
            </div>
            <div v-else-if="logs.length === 0" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                Aucune activité enregistrée pour l'instant.
            </div>
            <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700/60 max-h-[540px] overflow-y-auto">
                <li v-for="log in logs" :key="log.id" class="flex items-start gap-3 px-5 py-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-700/20 transition">
                    <!-- Icône action -->
                    <span class="mt-0.5 shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-xl"
                        :class="logIconClass(log.action)">
                        <svg v-if="log.action === 'edl_completed'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else-if="log.action === 'edl_deleted'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <svg v-else-if="log.action === 'category_created'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <!-- Contenu -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                {{ logLabel(log.action) }}
                                <span v-if="log.details?.type"
                                    class="ml-1 text-xs font-medium px-1.5 py-0.5 rounded-full"
                                    :class="log.details.type === 'entrant'
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                                        : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'">
                                    {{ log.details.type }}
                                </span>
                            </p>
                            <time class="text-xs text-gray-400 dark:text-gray-500 shrink-0">{{ formatLogDate(log.created_at) }}</time>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ logDetail(log) }}</p>
                        <p v-if="log.user" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ log.user.name }}</p>
                    </div>
                </li>
            </ul>
        </div>

    </div>

    <div v-else class="text-center py-16 text-red-400">
        <p>Impossible de charger les informations système.</p>
    </div>
</div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const info = ref(null)

// ── Catégories ───────────────────────────────────────
const categories = ref([])
const newCat = reactive({ name: '', color: '#6366f1' })
const catAdding = ref(false)
const catError = ref('')

async function loadCategories() {
    const { data } = await axios.get('/api/categories')
    categories.value = data
}

async function addCategory() {
    catError.value = ''
    if (!newCat.name.trim()) { catError.value = 'Le nom est requis.'; return }
    catAdding.value = true
    try {
        const { data } = await axios.post('/api/categories', { name: newCat.name.trim(), color: newCat.color })
        categories.value.push(data)
        newCat.name = ''
        newCat.color = '#6366f1'
    } catch (e) {
        catError.value = e.response?.data?.errors?.name?.[0] ?? 'Erreur lors de la création.'
    } finally {
        catAdding.value = false
    }
}

async function deleteCategory(cat) {
    if (!confirm(`Supprimer la catégorie "${cat.name}" ?\nLes EDL associés ne seront pas supprimés.`)) return
    try {
        await axios.delete(`/api/categories/${cat.id}`)
        categories.value = categories.value.filter(c => c.id !== cat.id)
    } catch (e) {
        console.error('Erreur suppression catégorie', e)
    }
}

onMounted(() => {
    // Lance les 3 requêtes en parallèle sans bloquer
    axios.get('/api/admin/info')
        .then(r => { info.value = r.data })
        .catch(e => console.error('Erreur admin/info', e))
        .finally(() => { loading.value = false })

    loadCategories()
    loadLogs()
})

// Accesseurs sécurisés pour éviter les erreurs sur propriétés imbriquées
const app    = () => info.value?.app    ?? {}
const php    = () => info.value?.php    ?? {}
const laravel= () => info.value?.laravel?? {}
const db     = () => info.value?.database ?? {}
const stats  = () => info.value?.stats  ?? {}

// ── Journal d'activité ──────────────────────────────
const logs = ref([])
const logsLoading = ref(false)

async function loadLogs() {
    logsLoading.value = true
    try {
        const { data } = await axios.get('/api/logs')
        logs.value = data
    } catch (e) {
        console.error('Erreur chargement logs', e)
    } finally {
        logsLoading.value = false
    }
}

const LOG_LABELS = {
    edl_completed:    'EDL terminé',
    edl_deleted:      'EDL supprimé',
    category_created: 'Catégorie créée',
    category_deleted: 'Catégorie supprimée',
}

function logLabel(action) {
    return LOG_LABELS[action] ?? action
}

function logDetail(log) {
    if (log.entity_type === 'edl' && log.details) {
        const parts = [log.details.adresse, log.details.ville].filter(Boolean)
        if (log.details.locataire) parts.push(`— ${log.details.locataire}`)
        return parts.join(', ') || `EDL #${log.entity_id}`
    }
    if (log.entity_type === 'category' && log.details) {
        return log.details.name ?? `Catégorie #${log.entity_id}`
    }
    return `#${log.entity_id}`
}

const LOG_ICON_CLASSES = {
    edl_completed:    'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    edl_deleted:      'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    category_created: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    category_deleted: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
}

function logIconClass(action) {
    return LOG_ICON_CLASSES[action] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-500'
}

function formatLogDate(iso) {
    const d = new Date(iso)
    const diffMins = Math.floor((Date.now() - d) / 60000)
    if (diffMins < 1)  return 'À l\'instant'
    if (diffMins < 60) return `Il y a ${diffMins} min`
    const diffH = Math.floor(diffMins / 60)
    if (diffH < 24)    return `Il y a ${diffH}h`
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
