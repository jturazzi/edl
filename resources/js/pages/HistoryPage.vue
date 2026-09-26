<template>
<div>
    <PageHeader title="Historique" subtitle="Tous les états des lieux enregistrés.">
        <q-btn unelevated no-caps color="primary" icon="mdi-plus" label="Nouvel EDL" to="/nouveau" />
    </PageHeader>

    <!-- Barre d'outils : recherche + filtres -->
    <div class="mb-4 space-y-3">
        <div class="flex flex-wrap items-center gap-3">
            <q-input v-model="search" type="search" outlined dense clearable class="min-w-[260px] flex-1" style="max-width: 460px"
                placeholder="Rechercher un EDL, une adresse, un locataire, une date…" aria-label="Rechercher un EDL">
                <template #prepend><q-icon name="mdi-magnify" /></template>
            </q-input>
            <q-btn outline no-caps color="grey-8" icon="mdi-download-outline" label="Exporter en CSV" :href="exportUrl" aria-label="Exporter la liste filtrée en CSV" />
        </div>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <q-btn-toggle v-model="filters.type" no-caps unelevated dense toggle-color="primary" color="white" text-color="grey-8"
                class="border border-slate-300 rounded-borders" aria-label="Filtrer par type"
                :options="[{ label: 'Tous', value: '' }, { label: 'Entrants', value: 'entrant' }, { label: 'Sortants', value: 'sortant' }]" />
            <q-btn-toggle v-model="filters.status" no-caps unelevated dense toggle-color="primary" color="white" text-color="grey-8"
                class="border border-slate-300 rounded-borders" aria-label="Filtrer par statut"
                :options="[{ label: 'Tous statuts', value: '' }, { label: 'En cours', value: 'en_cours' }, { label: 'Terminés', value: 'complete' }]" />
            <q-select v-if="techniciens.length > 1" v-model="filters.technicien" :options="techniciens" option-value="email" option-label="name"
                emit-value map-options outlined dense clearable bg-color="white" label="Technicien" style="min-width: 190px" />
            <q-input v-model="filters.from" type="date" outlined dense bg-color="white" label="Du" stack-label style="width: 160px" />
            <q-input v-model="filters.to" type="date" outlined dense bg-color="white" label="Au" stack-label style="width: 160px" />
            <q-toggle v-if="isAdmin" v-model="filters.archived" dense color="primary" label="Archivés" />
            <q-btn v-if="hasFilters" flat dense no-caps color="primary" icon="mdi-filter-remove-outline" label="Réinitialiser" class="px-2" @click="resetFilters" />
        </div>
    </div>

    <!-- Chargement -->
    <q-card v-if="loading" flat role="status" aria-live="polite" aria-label="Chargement…">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-0">
            <q-skeleton type="text" width="5rem" />
            <q-skeleton type="text" class="flex-1" />
            <q-skeleton type="text" width="6rem" />
            <q-skeleton type="text" width="4rem" />
        </div>
    </q-card>

    <!-- Vide -->
    <q-card v-else-if="edls.length === 0" flat>
        <div class="flex flex-col items-center px-6 py-14 text-center">
            <span class="inline-flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-500"><q-icon name="mdi-clipboard-text-off-outline" size="26px" /></span>
            <p class="mt-4 mb-0 font-semibold">{{ isFiltered ? 'Aucun résultat' : 'Aucun EDL enregistré' }}</p>
            <p class="mt-1 mb-4 text-body2 text-grey-7">{{ isFiltered ? 'Essayez avec d\'autres termes ou retirez des filtres.' : 'Créez votre premier état des lieux.' }}</p>
            <q-btn v-if="!isFiltered" unelevated no-caps color="primary" icon="mdi-plus" label="Nouvel EDL" to="/nouveau" />
        </div>
    </q-card>

    <template v-else>
        <!-- Tableau (desktop) -->
        <q-card flat class="gt-sm overflow-hidden">
            <table class="w-full text-left text-body2">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3 font-medium"><button type="button" class="inline-flex cursor-pointer items-center gap-1 border-0 bg-transparent p-0 font-medium uppercase tracking-wide text-inherit hover:text-slate-800" @click="toggleSort('adresse')">Logement<q-icon v-if="sort.key === 'adresse'" :name="sort.dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'" size="14px" /></button></th>
                        <th class="px-3 py-3 font-medium">Locataire</th>
                        <th class="px-3 py-3 font-medium"><button type="button" class="inline-flex cursor-pointer items-center gap-1 border-0 bg-transparent p-0 font-medium uppercase tracking-wide text-inherit hover:text-slate-800" @click="toggleSort('type')">Type<q-icon v-if="sort.key === 'type'" :name="sort.dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'" size="14px" /></button></th>
                        <th class="px-3 py-3 font-medium"><button type="button" class="inline-flex cursor-pointer items-center gap-1 border-0 bg-transparent p-0 font-medium uppercase tracking-wide text-inherit hover:text-slate-800" @click="toggleSort('status')">Statut<q-icon v-if="sort.key === 'status'" :name="sort.dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'" size="14px" /></button></th>
                        <th class="px-3 py-3 font-medium"><button type="button" class="inline-flex cursor-pointer items-center gap-1 border-0 bg-transparent p-0 font-medium uppercase tracking-wide text-inherit hover:text-slate-800" @click="toggleSort('date_edl')">Date<q-icon v-if="sort.key === 'date_edl'" :name="sort.dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'" size="14px" /></button></th>
                        <th class="px-3 py-3 font-medium">Technicien</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="edl in edls" :key="edl.id" class="border-b border-slate-100 last:border-0 hover:bg-slate-50/70">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <router-link :to="logementLink(edl)" class="font-medium text-slate-900 no-underline hover:underline" title="Historique de ce logement">{{ edl.adresse }}</router-link>
                                <AppBadge v-if="edl.archived_at" tone="amber" icon="mdi-archive-outline">Archivé</AppBadge>
                            </div>
                            <div class="text-xs text-slate-500"><span class="font-mono">{{ edl.numero }}</span> · {{ edl.ville }}</div>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap text-slate-700">{{ locataireFullName(edl) || '-' }}</td>
                        <td class="px-3 py-3"><AppBadge :tone="edl.type === 'entrant' ? 'green' : 'amber'" dot>{{ edl.type === 'entrant' ? 'Entrant' : 'Sortant' }}</AppBadge></td>
                        <td class="px-3 py-3">
                            <AppBadge v-if="edl.status === 'complete'" tone="blue" dot>Terminé</AppBadge>
                            <AppBadge v-else tone="slate" dot>En cours</AppBadge>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap text-slate-700">{{ formatDate(edl.date_edl || edl.created_at) }}</td>
                        <td class="px-3 py-3 whitespace-nowrap text-slate-700">{{ edl.agent_name }}</td>
                        <td class="px-5 py-2 whitespace-nowrap"><EdlActions :edl="edl" @sortant="askSortant" @delete="askDelete" @duplicate="edlToDuplicate = $event" @archive="setArchived($event, true)" @unarchive="setArchived($event, false)" :is-admin="isAdmin" :can-edit="canEdit(edl)" /></td>
                    </tr>
                </tbody>
            </table>
        </q-card>

        <!-- Cartes (mobile / tablette) -->
        <div class="lt-md space-y-3">
            <q-card v-for="edl in edls" :key="edl.id" flat>
                <q-card-section class="space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <router-link :to="logementLink(edl)" class="block truncate font-medium text-slate-900 no-underline">{{ edl.adresse }}</router-link>
                            <p class="m-0 text-xs text-slate-500"><span class="font-mono">{{ edl.numero }}</span> · {{ edl.ville }}</p>
                        </div>
                        <AppBadge v-if="edl.status === 'complete'" tone="blue" dot>Terminé</AppBadge>
                        <AppBadge v-else tone="slate" dot>En cours</AppBadge>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <AppBadge :tone="edl.type === 'entrant' ? 'green' : 'amber'" dot>{{ edl.type === 'entrant' ? 'Entrant' : 'Sortant' }}</AppBadge>
                        <span>{{ locataireFullName(edl) || 'Locataire non renseigné' }}</span>
                        <span class="text-slate-400">·</span>
                        <span>{{ formatDate(edl.date_edl || edl.created_at) }}</span>
                    </div>
                </q-card-section>
                <q-separator />
                <q-card-section class="py-2"><EdlActions :edl="edl" @sortant="askSortant" @delete="askDelete" @duplicate="edlToDuplicate = $event" @archive="setArchived($event, true)" @unarchive="setArchived($event, false)" :is-admin="isAdmin" :can-edit="canEdit(edl)" /></q-card-section>
            </q-card>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="mt-6 flex justify-center">
            <q-pagination :model-value="pagination.currentPage" :max="pagination.lastPage" :max-pages="7"
                direction-links boundary-links color="primary" flat active-design="unelevated" active-color="primary" @update:model-value="loadPage" />
        </div>
    </template>

    <!-- Modale création sortant -->
    <q-dialog :model-value="!!edlForSortant" @update:model-value="(v) => { if (!v) edlForSortant = null }">
        <q-card style="width: 28rem; max-width: 92vw">
            <form v-if="edlForSortant" @submit.prevent="createSortant">
                <q-card-section>
                    <div class="text-h6">Créer l'état des lieux sortant</div>
                    <p class="text-body2 text-grey-7 mt-2 mb-0">
                        <span class="font-medium text-grey-9">{{ edlForSortant.adresse }}</span>, {{ edlForSortant.ville }}<br>
                        Toutes les informations de l'entrant sont reprises. Indiquez le technicien en charge.
                    </p>
                </q-card-section>
                <q-card-section class="pt-0 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <q-input outlined dense label="Prénom *" v-model="tech.technicien_prenom" required />
                    <q-input outlined dense label="Nom *" v-model="tech.technicien_nom" required />
                    <q-input class="sm:col-span-2" outlined dense type="email" label="Adresse e-mail *" v-model="tech.technicien_email" required />
                    <p v-if="sortantError" class="sm:col-span-2 text-body2 text-negative m-0">{{ sortantError }}</p>
                </q-card-section>
                <q-card-actions align="right">
                    <q-btn flat no-caps type="button" label="Annuler" @click="edlForSortant = null" />
                    <q-btn unelevated no-caps type="submit" color="primary" :loading="sortantLoading"
                        :label="sortantLoading ? 'Création…' : 'Créer le sortant'" />
                </q-card-actions>
            </form>
        </q-card>
    </q-dialog>

    <DuplicateDialog :edl="edlToDuplicate" @close="edlToDuplicate = null" />

    <!-- Modale suppression -->
    <q-dialog :model-value="!!edlToDelete" @update:model-value="(v) => { if (!v) edlToDelete = null }">
        <q-card style="width: 24rem; max-width: 92vw">
            <q-card-section v-if="edlToDelete">
                <div class="text-h6">Supprimer cet EDL ?</div>
                <p class="text-body2 mt-2 mb-0"><span class="font-medium">{{ edlToDelete.adresse }}</span>, {{ edlToDelete.ville }}</p>
                <p class="text-body2 text-negative font-medium mt-1 mb-0">Cette action est irréversible.</p>
            </q-card-section>
            <q-card-actions align="right">
                <q-btn flat no-caps label="Annuler" @click="edlToDelete = null" />
                <q-btn unelevated no-caps color="negative" icon="mdi-delete" :loading="deleteLoading"
                    :label="deleteLoading ? 'Suppression…' : 'Supprimer'" @click="confirmDelete" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, inject } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import AppBadge from '@/components/AppBadge.vue'
import PageHeader from '@/components/PageHeader.vue'
import EdlActions from '@/components/EdlActions.vue'
import DuplicateDialog from '@/components/DuplicateDialog.vue'

const router = useRouter()
const route = useRoute()
const user = inject('user', ref(null))
const isAdmin = computed(() => !!user.value?.is_admin)
// On ne reprend que ses propres EDL (les administrateurs reprennent tous les EDL)
const canEdit = (edl) => isAdmin.value || edl.user_id === user.value?.id

const loading = ref(true)
const edls = ref([])
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
})
const search = ref(typeof route.query.q === 'string' ? route.query.q : '')

// ── Filtres, tri, export ───────────────────────────────
const filters = reactive({ type: '', status: '', technicien: null, from: '', to: '', archived: false })
const sort = reactive({ key: null, dir: 'desc' })
const techniciens = ref([])

const hasFilters = computed(() => Object.values(filters).some((v) => v) || sort.key !== null)
const isFiltered = computed(() => hasFilters.value || search.value.trim() !== '')

// Paramètres communs à la liste et à l'export (sans pagination)
function queryParams() {
    const params = {}
    if (search.value.trim()) params.q = search.value.trim()
    for (const key of ['type', 'status', 'technicien', 'from', 'to']) {
        if (filters[key]) params[key] = filters[key]
    }
    if (filters.archived) params.archived = 1
    if (sort.key) { params.sort = sort.key; params.dir = sort.dir }
    return params
}

const exportUrl = computed(() => '/api/edls/export?' + new URLSearchParams(queryParams()).toString())

function toggleSort(key) {
    if (sort.key !== key) { sort.key = key; sort.dir = 'asc' }
    else if (sort.dir === 'asc') sort.dir = 'desc'
    else { sort.key = null; sort.dir = 'desc' }
}

function resetFilters() {
    Object.assign(filters, { type: '', status: '', technicien: null, from: '', to: '', archived: false })
    sort.key = null
}

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => loadPage(1), 300)
})
// Filtres et tri : rechargement de la première page (une seule requête même si plusieurs valeurs changent)
watch([filters, sort], () => loadPage(1), { deep: true })

// Création d'un sortant depuis un entrant
const edlForSortant = ref(null)
const sortantLoading = ref(false)
const sortantError = ref('')
const tech = reactive({ technicien_prenom: '', technicien_nom: '', technicien_email: '' })

function askSortant(edl) {
    tech.technicien_prenom = user.value?.firstname || ''
    tech.technicien_nom = user.value?.lastname || ''
    tech.technicien_email = user.value?.email || ''
    sortantError.value = ''
    edlForSortant.value = edl
}

async function createSortant() {
    sortantLoading.value = true
    sortantError.value = ''
    try {
        const { data } = await axios.post(`/api/edls/${edlForSortant.value.id}/sortant`, tech)
        router.push({ name: 'survey', params: { id: data.id } })
    } catch (e) {
        sortantError.value = e.response?.data?.message || 'Erreur lors de la création.'
    } finally {
        sortantLoading.value = false
    }
}

// Duplication
const edlToDuplicate = ref(null)

// Archivage (administrateurs) : l'EDL quitte la liste courante
async function setArchived(edl, archived) {
    try {
        if (archived) await axios.post(`/api/edls/${edl.id}/archive`)
        else await axios.delete(`/api/edls/${edl.id}/archive`)
        edls.value = edls.value.filter((e) => e.id !== edl.id)
    } catch (e) {
        console.error('Erreur archivage EDL', e)
    }
}

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
        const { data } = await axios.get('/api/edls', { params: { page, ...queryParams() } })
        edls.value = data.data
        pagination.currentPage = data.current_page
        pagination.lastPage = data.last_page
    } catch (e) {
        console.error('Erreur chargement EDLs', e)
    } finally {
        loading.value = false
    }
}

const logementLink = (edl) => ({ name: 'logement', query: { adresse: edl.adresse, ville: edl.ville ?? '' } })

function locataireFullName(edl) {
    return [edl.locataire_prenom, edl.locataire_nom].filter(Boolean).join(' ').trim()
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(async () => {
    loadPage(1)
    try {
        techniciens.value = (await axios.get('/api/edls/filters')).data.techniciens
    } catch { /* les filtres restent utilisables sans la liste des techniciens */ }
})
</script>
