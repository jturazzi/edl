<template>
<div class="max-w-2xl">
    <PageHeader title="Nouvel état des lieux" subtitle="Renseignez les informations du logement pour démarrer." />

    <form @submit.prevent="submit" class="space-y-5">

        <q-card flat bordered>
            <q-card-section><SectionTitle icon="mdi-map-marker" title="Adresse du logement" /></q-card-section>
            <q-card-section class="pt-0">
                <div class="grid gap-4">
                    <AddressField v-model="form.adresse" :error="!!errors.adresse" :error-message="errors.adresse?.[0]" @select="(a) => { form.ville = a.ville }" />
                    <q-input outlined stack-label bg-color="white" label="Code postal et ville *" type="text" required placeholder="ex : 75002 Paris" :error="!!errors.ville" :error-message="errors.ville?.[0]" v-model="form.ville" hide-bottom-space class="" />
                </div>
            </q-card-section>
        </q-card>

        <q-card flat bordered>
            <q-card-section><SectionTitle icon="mdi-account-wrench" title="Technicien" /></q-card-section>
            <q-card-section class="pt-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <q-input outlined bg-color="white" label="Prénom *" type="text" required :error="!!errors.technicien_prenom" :error-message="errors.technicien_prenom?.[0]" v-model="form.technicien_prenom" hide-bottom-space class="" />
                    <q-input outlined bg-color="white" label="Nom *" type="text" required :error="!!errors.technicien_nom" :error-message="errors.technicien_nom?.[0]" v-model="form.technicien_nom" hide-bottom-space class="" />
                    <q-input outlined bg-color="white" label="Adresse e-mail *" type="email" required :error="!!errors.technicien_email" :error-message="errors.technicien_email?.[0]" v-model="form.technicien_email" hide-bottom-space class="sm:col-span-2" />
                </div>
            </q-card-section>
        </q-card>

        <q-card flat bordered>
            <q-card-section><SectionTitle icon="mdi-account" title="Locataire" hint="optionnel" /></q-card-section>
            <q-card-section class="pt-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <q-input outlined bg-color="white" label="Prénom" type="text" :model-value="form.locataire_prenom" @update:model-value="formatPrenom" hide-bottom-space class="" />
                    <q-input outlined bg-color="white" label="Nom" type="text" :model-value="form.locataire_nom" @update:model-value="formatNom" hide-bottom-space class="" />
                    <q-input outlined bg-color="white" label="Email" type="email" v-model="form.locataire_email" hide-bottom-space class="sm:col-span-2" />
                </div>
            </q-card-section>
        </q-card>


        <q-card flat>
            <q-card-section><SectionTitle icon="mdi-floor-plan" title="Logement" hint="pièces à inspecter" /></q-card-section>
            <q-card-section class="pt-0 space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2" role="radiogroup" aria-label="Modèle de logement">
                    <button v-for="t in TEMPLATES" :key="t.id" type="button" role="radio" :aria-checked="templateId === t.id"
                        class="rounded-lg border px-3 py-2.5 text-left transition"
                        :class="templateId === t.id ? 'border-primary bg-blue-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
                        @click="templateId = t.id">
                        <span class="block text-body2 font-semibold" :class="templateId === t.id ? 'text-primary' : 'text-slate-900'">{{ t.label }}</span>
                        <span class="block text-caption text-grey-7">{{ t.description }}</span>
                    </button>
                </div>

                <q-toggle v-model="meuble" color="primary" :disable="templateId === 'complet'"
                    label="Logement meublé (inventaire : vaisselle, literie, mobilier…)" />

                <q-expansion-item dense expand-separator icon="mdi-tune-variant" :label="`Personnaliser les pièces (${form.steps.length - 2} sélectionnées)`"
                    class="rounded-lg border border-slate-200">
                    <div class="flex flex-wrap gap-2 p-3">
                        <button v-for="step in selectableSteps" :key="step.key" type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-body2 font-medium transition"
                            :class="form.steps.includes(step.key) ? 'border-primary bg-blue-50 text-primary' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                            :aria-pressed="form.steps.includes(step.key)" @click="toggleStep(step.key)">
                            <q-icon :name="stepIcon(step.key)" size="18px" />
                            {{ step.title }}
                        </button>
                    </div>
                </q-expansion-item>
            </q-card-section>
        </q-card>

        <q-card flat bordered>
            <q-card-section><SectionTitle icon="mdi-clipboard-text" title="Type d'état des lieux" /></q-card-section>
            <q-card-section class="pt-0">
                <div class="grid grid-cols-2 gap-4" role="radiogroup">
                    <button v-for="t in types" :key="t.value" type="button" role="radio" :aria-checked="form.type === t.value"
                        class="flex flex-col items-center gap-2 rounded-xl border-2 p-4 sm:p-6 text-center cursor-pointer transition bg-white"
                        :class="form.type === t.value ? t.active : 'border-slate-200 hover:bg-slate-50'"
                        @click="form.type = t.value">
                        <q-icon :name="t.icon" size="36px" :class="t.color" />
                        <span class="text-subtitle1 font-semibold">{{ t.label }}</span>
                        <span class="text-caption text-grey-7">{{ t.desc }}</span>
                    </button>
                </div>
                <p v-if="errors.type" class="text-negative text-body2 mt-3 mb-0">{{ errors.type[0] }}</p>
            </q-card-section>
        </q-card>

        <q-btn type="submit" unelevated no-caps size="lg" color="primary" class="w-full" :loading="submitting"
            icon-right="mdi-arrow-right" :label="submitting ? 'Création en cours…' : 'Commencer l\'état des lieux'" />
    </form>
</div>
</template>

<script setup>
import { ref, reactive, inject, watch } from 'vue'
import { steps as ALL_STEPS } from '@/data/steps.js'
import { TEMPLATES, templateSteps } from '@/data/templates.js'
import { stepIcon } from '@/data/stepIcons.js'
import { useRouter } from 'vue-router'
import axios from 'axios'
import PageHeader from '@/components/PageHeader.vue'
import AddressField from '@/components/AddressField.vue'
import SectionTitle from '@/components/SectionTitle.vue'

const router = useRouter()

const form = reactive({
    adresse: '',
    ville: '',
    technicien_prenom: '',
    technicien_nom: '',
    technicien_email: '',
    type: '',
    steps: ALL_STEPS.map((step) => step.key),
    locataire_nom: '',
    locataire_prenom: '',
    locataire_email: '',
})

const user = inject('user', ref(null))

// Pré-remplissage avec l'utilisateur connecté
function prefillTechnicien(u) {
    if (!u) return
    form.technicien_prenom ||= u.firstname || ''
    form.technicien_nom ||= u.lastname || ''
    form.technicien_email ||= u.email || ''
}
watch(user, prefillTechnicien, { immediate: true })

// ── Modèle de logement → pièces à inspecter ───────────
const ALL_KEYS = ALL_STEPS.map((step) => step.key)
const selectableSteps = ALL_STEPS.filter((step) => !['compteurs', 'synthese'].includes(step.key))
const templateId = ref('complet')
const meuble = ref(true)

watch([templateId, meuble], () => {
    if (templateId.value === null) return // sélection personnalisée : on ne l'écrase pas
    form.steps = templateSteps(TEMPLATES.find((t) => t.id === templateId.value), meuble.value, ALL_KEYS)
})

function toggleStep(key) {
    templateId.value = null
    form.steps = form.steps.includes(key) ? form.steps.filter((k) => k !== key) : [...form.steps, key]
}

const types = [
    { value: 'entrant', label: 'Entrant', desc: 'Remise des clés au locataire', icon: 'mdi-key-variant', color: 'text-positive', active: 'border-green-500 bg-green-50' },
    { value: 'sortant', label: 'Sortant', desc: 'Rendu des clés par le locataire', icon: 'mdi-logout', color: 'text-warning', active: 'border-amber-500 bg-amber-50' },
]

const errors = ref({})
const submitting = ref(false)

function formatPrenom(v) {
    v = String(v ?? '')
    // Majuscule sur la première lettre de chaque mot (gère les prénoms composés)
    form.locataire_prenom = v.replace(/(^|[\s-])([a-zà-ÿ])/g, (_, sep, c) => sep + c.toUpperCase())
}

function formatNom(v) {
    form.locataire_nom = String(v ?? '').toUpperCase()
}

// ── Soumission ───────────────────────────────────────
async function submit() {
    errors.value = {}
    if (!form.adresse) {
        errors.value = { adresse: ["L'adresse est obligatoire."] }
        return
    }
    if (!form.ville) {
        errors.value = { ville: ['Le code postal et la ville sont obligatoires.'] }
        return
    }
    if (!form.type) {
        errors.value = { type: ["Veuillez choisir le type d'état des lieux."] }
        return
    }
    submitting.value = true
    errors.value = {}

    try {
        const { data } = await axios.post('/api/edls', form)
        router.push({ name: 'survey', params: { id: data.id } })
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {}
        } else if (!e.response) {
            errors.value = { adresse: ['Impossible de joindre le serveur. Vérifiez la connexion et réessayez.'] }
        }
    } finally {
        submitting.value = false
    }
}
</script>
