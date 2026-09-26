<template>
<div v-if="loading" class="flex justify-center py-16" role="status" aria-live="polite">
    <q-spinner color="primary" size="40px" />
</div>

<div v-else-if="error" class="max-w-xl">
    <PageHeader title="Comparaison indisponible" />
    <q-banner rounded class="bg-red-1 text-negative">
        <template #avatar><q-icon name="mdi-alert-circle-outline" color="negative" /></template>
        {{ error }}
    </q-banner>
    <q-btn class="mt-4" flat no-caps color="primary" icon="mdi-arrow-left" label="Retour" :to="{ name: 'history' }" />
</div>

<div v-else class="space-y-5">
    <PageHeader class="no-print" title="Comparaison entrée / sortie"
        :subtitle="`${data.entrant.numero} (${fmt(data.entrant.date_edl)}) → ${data.sortant.numero} (${fmt(data.sortant.date_edl)})`">
        <q-btn class="no-print" outline no-caps color="grey-8" icon="mdi-arrow-left" label="Retour" @click="$router.back()" />
        <q-btn class="no-print" outline no-caps color="primary" icon="mdi-file-eye-outline" label="Voir l'entrant" :to="{ name: 'confirmation', params: { id: data.entrant.id } }" />
    </PageHeader>

    <!-- Synthèse -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 no-print">
        <q-card v-for="m in metrics" :key="m.key" flat>
            <q-card-section class="flex items-center gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg" :class="m.tone"><q-icon :name="m.icon" size="22px" /></span>
                <div>
                    <p class="m-0 text-caption text-grey-7">{{ m.label }}</p>
                    <p class="m-0 text-h5 leading-tight">{{ data.summary[m.key] }}</p>
                </div>
            </q-card-section>
        </q-card>
    </div>

    <!-- Filtres -->
    <div class="flex flex-wrap gap-1.5 no-print">
        <button v-for="f in filters" :key="f.value" type="button"
            class="rounded-md border px-3 py-1.5 text-body2 font-medium transition"
            :class="filter === f.value ? 'border-primary bg-blue-50 text-primary' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            @click="filter = f.value">
            {{ f.label }}<span v-if="f.count !== null" class="ml-1.5 text-slate-400">{{ f.count }}</span>
        </button>
    </div>

    <q-card v-if="visibleSections.length === 0" flat class="no-print">
        <div class="flex flex-col items-center px-6 py-12 text-center">
            <span class="inline-flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><q-icon name="mdi-check-circle-outline" size="26px" /></span>
            <p class="mt-4 mb-0 font-semibold">Aucune différence à afficher</p>
            <p class="mt-1 mb-0 text-body2 text-grey-7">{{ data.summary.total === 0 ? "L'état du logement est identique à celui de l'entrée." : 'Aucun élément ne correspond à ce filtre.' }}</p>
        </div>
    </q-card>

    <!-- Sections -->
    <q-card v-for="section in visibleSections" :key="section.key" flat class="overflow-hidden no-print">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
            <q-icon :name="stepIcon(section.key)" color="primary" size="20px" />
            <h2 class="m-0 text-subtitle2 font-semibold">{{ section.title }}</h2>
            <span class="text-caption text-grey-7">{{ section.rows.length }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-body2">
                <thead>
                    <tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-2 font-medium">Élément</th>
                        <th class="px-3 py-2 font-medium">Entrée</th>
                        <th class="px-3 py-2 font-medium">Sortie</th>
                        <th class="px-3 py-2 font-medium">Évolution</th>
                        <th class="px-4 py-2 font-medium">Observations</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in section.rows" :key="row.label" class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-2.5 font-medium text-slate-900">{{ row.label }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap" :class="TONE_TEXT[row.before_tone]">{{ row.before }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap font-medium" :class="TONE_TEXT[row.after_tone]">{{ row.after }}</td>
                        <td class="px-3 py-2.5"><AppBadge :tone="CHANGE[row.change].tone" :icon="CHANGE[row.change].icon">{{ CHANGE[row.change].label }}</AppBadge></td>
                        <td class="px-4 py-2.5 text-slate-600">{{ row.note }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </q-card>

    <!-- Bilan chiffré : retenues estimées sur le dépôt de garantie -->
    <q-card flat class="print-area">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
            <q-icon name="mdi-cash-multiple" color="primary" size="20px" />
            <h2 class="m-0 text-subtitle2 font-semibold">Bilan des retenues</h2>
            <span class="text-caption text-grey-7">estimation, à valider avec le propriétaire</span>
        </div>

        <div class="print-only px-4 pt-3 text-body2">
            <p class="m-0"><strong>{{ data.sortant.adresse }}</strong></p>
            <p class="m-0 text-grey-8">
                <template v-if="data.sortant.locataire">Locataire : {{ data.sortant.locataire }} · </template>
                Entrée {{ data.entrant.numero }} ({{ fmt(data.entrant.date_edl) }}) – Sortie {{ data.sortant.numero }} ({{ fmt(data.sortant.date_edl) }})
            </p>
        </div>

        <div class="space-y-2 p-4">
            <p v-if="lines.length === 0" class="m-0 text-body2 text-grey-7">
                Aucune retenue saisie.
                <template v-if="issues.length && data.editable">Reprenez les {{ issues.length }} dégradation(s) / élément(s) manquant(s) constatés puis indiquez un montant.</template>
            </p>

            <div v-for="(line, i) in lines" :key="i" class="flex items-center gap-2">
                <q-input v-model="line.label" class="flex-1" outlined dense placeholder="Libellé" aria-label="Libellé de la retenue" :readonly="!data.editable" />
                <q-input v-model.number="line.amount" class="print-amount" style="width: 8.5rem" outlined dense type="number" min="0" step="0.01" suffix="€"
                    aria-label="Montant de la retenue" input-class="text-right" :readonly="!data.editable" />
                <q-btn v-if="data.editable" class="no-print" flat round dense color="grey-6" icon="mdi-close" aria-label="Supprimer la ligne" @click="lines.splice(i, 1)" />
            </div>

            <div v-if="lines.length" class="flex items-center justify-end gap-3 border-t border-slate-200 pt-3">
                <span class="text-body2 text-grey-7">Total des retenues</span>
                <span class="text-h6">{{ euro(total) }}</span>
            </div>

            <div class="no-print flex flex-wrap items-center gap-2 pt-1">
                <q-btn v-if="data.editable" flat no-caps dense color="primary" icon="mdi-playlist-plus" label="Ajouter les dégradations constatées" class="px-2" :disable="missingIssues.length === 0" @click="addIssues" />
                <q-btn v-if="data.editable" flat no-caps dense color="grey-8" icon="mdi-plus" label="Ajouter une ligne" class="px-2" @click="lines.push({ label: '', amount: 0 })" />
                <q-space />
                <span v-if="saved" class="text-caption text-positive"><q-icon name="mdi-check" /> Enregistré</span>
                <span v-if="retenuesError" class="text-caption text-negative">{{ retenuesError }}</span>
                <q-btn outline no-caps color="grey-8" icon="mdi-printer-outline" label="Imprimer le bilan" @click="printBilan" />
                <q-btn v-if="data.editable" unelevated no-caps color="primary" icon="mdi-content-save-outline" label="Enregistrer" :loading="saving" :disable="!dirty" @click="saveRetenues" />
            </div>
        </div>
    </q-card>
</div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import * as Sentry from '@sentry/vue'
import PageHeader from '@/components/PageHeader.vue'
import AppBadge from '@/components/AppBadge.vue'
import { stepIcon } from '@/data/stepIcons.js'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const data = ref(null)
const filter = ref('all')

const CHANGE = {
    degrade:  { label: 'Dégradé',  tone: 'red',   icon: 'mdi-trending-down' },
    manquant: { label: 'Manquant', tone: 'red',   icon: 'mdi-alert-outline' },
    ameliore: { label: 'Amélioré', tone: 'green', icon: 'mdi-trending-up' },
    modifie:  { label: 'Modifié',  tone: 'slate', icon: 'mdi-pencil-outline' },
    releve:   { label: 'Relevé',   tone: 'blue',  icon: 'mdi-gauge' },
}

const TONE_TEXT = { bon: 'text-emerald-700', usure: 'text-amber-700', mauvais: 'text-red-700' }

const metrics = [
    { key: 'degrade', label: 'Dégradations', icon: 'mdi-trending-down', tone: 'bg-red-50 text-red-600' },
    { key: 'manquant', label: 'Manquants', icon: 'mdi-alert-outline', tone: 'bg-orange-50 text-orange-600' },
    { key: 'ameliore', label: 'Améliorations', icon: 'mdi-trending-up', tone: 'bg-emerald-50 text-emerald-600' },
    { key: 'modifie', label: 'Modifications', icon: 'mdi-pencil-outline', tone: 'bg-slate-100 text-slate-600' },
]

const filters = computed(() => [
    { value: 'all', label: 'Tout', count: null },
    { value: 'degrade', label: 'Dégradations', count: data.value.summary.degrade },
    { value: 'manquant', label: 'Manquants', count: data.value.summary.manquant },
    { value: 'ameliore', label: 'Améliorations', count: data.value.summary.ameliore },
    { value: 'modifie', label: 'Modifications', count: data.value.summary.modifie },
])

const visibleSections = computed(() => {
    if (!data.value) return []
    return data.value.sections
        .map((s) => ({ ...s, rows: filter.value === 'all' ? s.rows : s.rows.filter((r) => r.change === filter.value) }))
        .filter((s) => s.rows.length)
})

// ── Bilan des retenues ────────────────────────────────
const lines = ref([])
const savedSnapshot = ref('[]')
const saving = ref(false)
const saved = ref(false)
const retenuesError = ref('')

const euro = (n) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n || 0)
const cleanLines = () => lines.value
    .map((l) => ({ label: String(l.label ?? '').trim(), amount: Math.round((Number(l.amount) || 0) * 100) / 100 }))
    .filter((l) => l.label !== '')
const total = computed(() => lines.value.reduce((sum, l) => sum + (Number(l.amount) || 0), 0))
const dirty = computed(() => JSON.stringify(cleanLines()) !== savedSnapshot.value)

// Dégradations et éléments manquants constatés, sous forme de libellés de retenue
const issues = computed(() => (data.value?.sections ?? []).flatMap((s) => s.rows
    .filter((r) => r.change === 'degrade' || r.change === 'manquant')
    .map((r) => `${s.title} - ${r.label}${r.change === 'manquant' ? ' (manquant)' : ''}`)))
const missingIssues = computed(() => issues.value.filter((label) => !lines.value.some((l) => l.label === label)))

function addIssues() {
    for (const label of missingIssues.value) lines.value.push({ label, amount: 0 })
}

async function saveRetenues() {
    saving.value = true
    retenuesError.value = ''
    const payload = cleanLines()
    try {
        await axios.put(`/api/edls/${data.value.sortant.id}/retenues`, { retenues: payload })
        lines.value = payload.map((l) => ({ ...l }))
        savedSnapshot.value = JSON.stringify(payload)
        saved.value = true
        setTimeout(() => { saved.value = false }, 3000)
    } catch (e) {
        retenuesError.value = e.response?.data?.message || "Enregistrement impossible."
        Sentry.captureException(e)
    } finally {
        saving.value = false
    }
}

function printBilan() {
    document.body.classList.add('printing-bilan')
    window.print()
}
const afterPrint = () => document.body.classList.remove('printing-bilan')
window.addEventListener('afterprint', afterPrint)
onBeforeUnmount(() => { window.removeEventListener('afterprint', afterPrint); afterPrint() })

const fmt = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR') : '-')

onMounted(async () => {
    try {
        const res = await axios.get(`/api/edls/${route.params.id}/comparison`)
        data.value = res.data
        lines.value = (res.data.retenues ?? []).map((l) => ({ ...l }))
        savedSnapshot.value = JSON.stringify(cleanLines())
    } catch (e) {
        error.value = e.response?.status === 404
            ? "Aucun état des lieux d'entrée n'est associé à ce sortant."
            : e.response?.data?.message || 'Impossible de charger la comparaison.'
    } finally {
        loading.value = false
    }
})
</script>
