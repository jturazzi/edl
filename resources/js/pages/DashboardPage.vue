<template>
<div>
    <PageHeader :title="`Bonjour ${firstName}`" :subtitle="subtitle">
        <q-input v-model="search" outlined dense clearable placeholder="Rechercher (adresse, ville, locataire…)" class="gt-xs" style="width: 20rem"
            aria-label="Rechercher un état des lieux" @keyup.enter="goSearch" @clear="search = ''">
            <template #prepend><q-icon name="mdi-magnify" /></template>
        </q-input>
        <q-btn unelevated no-caps color="primary" icon="mdi-plus" label="Nouvel EDL" to="/nouveau" />
    </PageHeader>

    <!-- Chargement -->
    <div v-if="loading" class="space-y-5" role="status" aria-live="polite" aria-label="Chargement…">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <q-card v-for="i in 4" :key="i" flat><q-card-section><q-skeleton type="text" width="60%" /><q-skeleton type="text" width="30%" height="2rem" /></q-card-section></q-card>
        </div>
        <q-card flat><q-card-section class="space-y-3"><q-skeleton type="text" v-for="i in 4" :key="i" /></q-card-section></q-card>
    </div>

    <q-banner v-else-if="error" rounded class="bg-red-1 text-negative">
        <template #avatar><q-icon name="mdi-alert-circle-outline" color="negative" /></template>
        {{ error }}
    </q-banner>

    <div v-else class="space-y-5">
        <!-- Rappel : EDL en cours sans activité -->
        <q-banner v-if="(data.stats.en_retard ?? 0) > 0" rounded class="bg-amber-1 text-amber-10" role="status">
            <template #avatar><q-icon name="mdi-clock-alert-outline" color="warning" /></template>
            <strong>{{ data.stats.en_retard }}</strong> état{{ data.stats.en_retard > 1 ? 's' : '' }} des lieux en cours
            sans activité depuis plus de {{ staleDays }} jours : à terminer ou à supprimer.
        </q-banner>

        <!-- Chiffres clés -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <q-card v-for="m in metrics" :key="m.label" flat>
                <q-card-section class="flex items-center gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg" :class="m.tone"><q-icon :name="m.icon" size="22px" /></span>
                    <div>
                        <p class="m-0 text-caption text-grey-7">{{ m.label }}</p>
                        <p class="m-0 text-h5 leading-tight">{{ m.value }}</p>
                    </div>
                </q-card-section>
            </q-card>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <!-- À reprendre -->
            <q-card flat class="overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 class="m-0 text-subtitle1 font-semibold">À reprendre</h2>
                    <span class="text-caption text-grey-7">{{ data.stats.en_cours }} en cours</span>
                </div>
                <div v-if="data.en_cours.length === 0" class="flex flex-col items-center px-6 py-10 text-center">
                    <span class="inline-flex size-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><q-icon name="mdi-check-all" size="24px" /></span>
                    <p class="mt-3 mb-0 font-medium">Rien en attente</p>
                    <p class="mt-1 mb-0 text-body2 text-grey-7">Tous vos états des lieux sont terminés.</p>
                </div>
                <ul v-else class="m-0 list-none p-0 divide-y divide-slate-100">
                    <li v-for="edl in data.en_cours" :key="edl.id" class="flex items-center gap-3 px-4 py-3">
                        <router-link :to="{ name: 'survey', params: { id: edl.id } }" class="min-w-0 flex-1 no-underline" style="color: inherit">
                            <p class="m-0 truncate font-medium text-slate-900">{{ edl.adresse }}</p>
                            <p class="m-0 truncate text-xs text-slate-500">
                                <span class="font-mono">{{ edl.numero }}</span> · {{ edl.ville }} · {{ since(edl.updated_at) }}<span v-if="isAdmin"> · {{ edl.agent_name }}</span>
                            </p>
                        </router-link>
                        <AppBadge v-if="edl.en_retard" tone="red" icon="mdi-clock-alert-outline">En retard</AppBadge>
                        <AppBadge :tone="edl.type === 'entrant' ? 'green' : 'amber'" dot>{{ edl.type === 'entrant' ? 'Entrant' : 'Sortant' }}</AppBadge>
                        <q-btn unelevated dense no-caps color="primary" icon-right="mdi-arrow-right" label="Reprendre" class="px-3 whitespace-nowrap"
                            :to="{ name: 'survey', params: { id: edl.id } }" />
                    </li>
                </ul>
            </q-card>

            <!-- Derniers terminés -->
            <q-card flat class="overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 class="m-0 text-subtitle1 font-semibold">Derniers terminés</h2>
                    <q-btn flat dense no-caps color="primary" label="Tout voir" icon-right="mdi-arrow-right" class="px-2" :to="{ name: 'history' }" />
                </div>
                <div v-if="data.recents.length === 0" class="flex flex-col items-center px-6 py-10 text-center">
                    <span class="inline-flex size-11 items-center justify-center rounded-full bg-slate-100 text-slate-500"><q-icon name="mdi-clipboard-text-off-outline" size="24px" /></span>
                    <p class="mt-3 mb-0 font-medium">Aucun EDL terminé</p>
                    <p class="mt-1 mb-0 text-body2 text-grey-7">Les états des lieux validés apparaîtront ici.</p>
                </div>
                <ul v-else class="m-0 list-none p-0 divide-y divide-slate-100">
                    <li v-for="edl in data.recents" :key="edl.id" class="flex items-center gap-3 px-4 py-3">
                        <router-link :to="{ name: 'confirmation', params: { id: edl.id } }" class="min-w-0 flex-1 no-underline" style="color: inherit">
                            <p class="m-0 truncate font-medium text-slate-900">{{ edl.adresse }}</p>
                            <p class="m-0 truncate text-xs text-slate-500">
                                <span class="font-mono">{{ edl.numero }}</span> · {{ edl.ville }} · {{ since(edl.updated_at) }} · {{ edl.agent_name }}
                            </p>
                        </router-link>
                        <AppBadge :tone="edl.type === 'entrant' ? 'green' : 'amber'" dot>{{ edl.type === 'entrant' ? 'Entrant' : 'Sortant' }}</AppBadge>
                        <q-btn flat round dense color="grey-8" icon="mdi-download-outline" :href="`/edl/${edl.id}/pdf`" target="_blank" aria-label="Télécharger le PDF" />
                    </li>
                </ul>
            </q-card>
        </div>
    </div>
</div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import PageHeader from '@/components/PageHeader.vue'
import AppBadge from '@/components/AppBadge.vue'

const router = useRouter()
const user = inject('user', ref(null))
const search = ref('')
const staleDays = 3

function goSearch() {
    const q = (search.value || '').trim()
    router.push({ name: 'history', query: q ? { q } : {} })
}

const loading = ref(true)
const error = ref('')
const data = ref({ stats: {}, en_cours: [], recents: [] })

const firstName = computed(() => user.value?.firstname || user.value?.name?.split(' ')[0] || '')
const isAdmin = computed(() => !!user.value?.is_admin)
const subtitle = computed(() => {
    const today = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })
    return today.charAt(0).toUpperCase() + today.slice(1)
})

const metrics = computed(() => {
    const s = data.value.stats
    return [
        { label: 'En cours', value: s.en_cours ?? 0, icon: 'mdi-clock-outline', tone: 'bg-amber-50 text-amber-600' },
        { label: 'Terminés ce mois', value: s.termines_mois ?? 0, icon: 'mdi-check-circle-outline', tone: 'bg-emerald-50 text-emerald-600' },
        { label: 'Entrants ce mois', value: s.entrants_mois ?? 0, icon: 'mdi-key-variant', tone: 'bg-blue-50 text-blue-600' },
        { label: 'Sortants ce mois', value: s.sortants_mois ?? 0, icon: 'mdi-logout', tone: 'bg-slate-100 text-slate-600' },
    ]
})

// « il y a 3 h », « hier », sinon la date
function since(iso) {
    if (!iso) return ''
    const d = new Date(iso)
    const mins = Math.floor((Date.now() - d) / 60000)
    if (mins < 1) return "à l'instant"
    if (mins < 60) return `il y a ${mins} min`
    const hours = Math.floor(mins / 60)
    if (hours < 24) return `il y a ${hours} h`
    const days = Math.floor(hours / 24)
    if (days === 1) return 'hier'
    if (days < 7) return `il y a ${days} j`
    return d.toLocaleDateString('fr-FR')
}

onMounted(async () => {
    try {
        const res = await axios.get('/api/dashboard')
        data.value = res.data
    } catch (e) {
        error.value = 'Impossible de charger le tableau de bord.'
    } finally {
        loading.value = false
    }
})
</script>
