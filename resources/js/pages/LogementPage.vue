<template>
<div class="max-w-3xl">
    <PageHeader :title="adresse || 'Logement'" :subtitle="ville ? `Historique des états des lieux · ${ville}` : 'Historique des états des lieux'">
        <q-btn outline no-caps color="grey-8" icon="mdi-arrow-left" label="Retour" @click="$router.back()" />
    </PageHeader>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-live="polite"><q-spinner color="primary" size="36px" /></div>

    <q-banner v-else-if="error" rounded class="bg-red-1 text-negative">
        <template #avatar><q-icon name="mdi-alert-circle-outline" color="negative" /></template>{{ error }}
    </q-banner>

    <q-card v-else-if="edls.length === 0" flat>
        <div class="flex flex-col items-center px-6 py-12 text-center">
            <span class="inline-flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-500"><q-icon name="mdi-home-search-outline" size="26px" /></span>
            <p class="mt-4 mb-0 font-semibold">Aucun état des lieux pour ce logement</p>
        </div>
    </q-card>

    <!-- Chronologie : du plus récent au plus ancien -->
    <ol v-else class="m-0 list-none space-y-3 p-0" aria-label="États des lieux de ce logement">
        <li v-for="edl in edls" :key="edl.id" class="relative pl-8">
            <span class="absolute left-0 top-4 inline-flex size-5 items-center justify-center rounded-full ring-4 ring-slate-50"
                :class="edl.type === 'entrant' ? 'bg-emerald-500' : 'bg-amber-500'" aria-hidden="true">
                <q-icon :name="edl.type === 'entrant' ? 'mdi-key-variant' : 'mdi-logout'" size="12px" color="white" />
            </span>
            <q-card flat>
                <q-card-section class="flex flex-wrap items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="m-0 font-medium text-slate-900">
                            {{ edl.type === 'entrant' ? 'État des lieux entrant' : 'État des lieux sortant' }}
                            <span class="ml-1 font-mono text-xs text-slate-500">{{ edl.numero }}</span>
                        </p>
                        <p class="m-0 text-body2 text-slate-600">
                            {{ formatDate(edl.signed_at || edl.date_edl) }}
                            · {{ locataire(edl) || 'Locataire non renseigné' }}
                            <span> · {{ edl.agent_name }}</span>
                        </p>
                    </div>
                    <AppBadge v-if="edl.status === 'complete'" tone="blue" dot>Terminé</AppBadge>
                    <AppBadge v-else tone="slate" dot>En cours</AppBadge>
                    <div class="flex items-center gap-1">
                        <span v-if="edl.status === 'en_cours' && !canEdit(edl)" class="text-caption text-grey-7">En cours</span>
                        <q-btn v-else-if="edl.status === 'en_cours'" unelevated dense no-caps color="primary" icon-right="mdi-arrow-right" label="Reprendre" class="px-3"
                            :to="{ name: 'survey', params: { id: edl.id } }" />
                        <template v-else>
                            <q-btn flat round dense color="grey-8" icon="mdi-eye-outline" aria-label="Voir l'EDL" :to="{ name: 'confirmation', params: { id: edl.id } }"><q-tooltip>Voir</q-tooltip></q-btn>
                            <q-btn v-if="edl.type === 'sortant' && edl.entrant_id" flat round dense color="primary" icon="mdi-compare-horizontal" aria-label="Comparer avec l'entrant"
                                :to="{ name: 'comparison', params: { id: edl.id } }"><q-tooltip>Comparer avec l'entrant</q-tooltip></q-btn>
                            <q-btn flat round dense color="grey-8" icon="mdi-download-outline" :href="`/edl/${edl.id}/pdf`" target="_blank" aria-label="Télécharger le PDF"><q-tooltip>Télécharger le PDF</q-tooltip></q-btn>
                        </template>
                    </div>
                </q-card-section>
            </q-card>
        </li>
    </ol>
</div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import PageHeader from '@/components/PageHeader.vue'
import AppBadge from '@/components/AppBadge.vue'

// Tous les EDL d'un même logement (adresse + ville) : entrants, sortants, locataires successifs
const route = useRoute()
const user = inject('user', ref(null))
const isAdmin = computed(() => !!user.value?.is_admin)

const adresse = computed(() => String(route.query.adresse ?? ''))
const ville = computed(() => String(route.query.ville ?? ''))
const loading = ref(true)
const error = ref('')
const edls = ref([])

const canEdit = (edl) => isAdmin.value || edl.user_id === user.value?.id
const locataire = (edl) => [edl.locataire_prenom, edl.locataire_nom].filter(Boolean).join(' ')
const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) : '')

onMounted(async () => {
    if (!adresse.value) {
        error.value = "Aucune adresse indiquée."
        loading.value = false
        return
    }
    try {
        const { data } = await axios.get('/api/logement/edls', { params: { adresse: adresse.value, ville: ville.value } })
        edls.value = data.edls
    } catch (e) {
        error.value = "Impossible de charger l'historique de ce logement."
    } finally {
        loading.value = false
    }
})
</script>
