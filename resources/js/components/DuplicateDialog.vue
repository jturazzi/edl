<script setup>
import { ref, reactive, inject, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import * as Sentry from '@sentry/vue'

// Duplique un EDL pour le même logement (nouveau locataire, nouvelle visite…).
// Reprend l'adresse et les pièces ; les réponses sont copiées sur demande ; ni photos ni signatures.
const props = defineProps({ edl: { type: Object, default: null } })
const emit = defineEmits(['close'])

const router = useRouter()
const user = inject('user', ref(null))

const form = reactive({ type: 'entrant', keep_tenant: false, keep_survey: true, technicien_prenom: '', technicien_nom: '', technicien_email: '' })
const loading = ref(false)
const error = ref('')

watch(() => props.edl, (edl) => {
    if (!edl) return
    error.value = ''
    // Un sortant sert de base au prochain entrant ; un entrant, à un nouveau sortant
    form.type = edl.type === 'sortant' ? 'entrant' : 'sortant'
    form.keep_tenant = form.type === 'sortant'
    form.keep_survey = true
    form.technicien_prenom = user.value?.firstname || ''
    form.technicien_nom = user.value?.lastname || ''
    form.technicien_email = user.value?.email || ''
})

// Le locataire est repris par défaut pour un sortant, vidé pour un nouvel entrant
watch(() => form.type, (type) => { form.keep_tenant = type === 'sortant' })

async function submit() {
    loading.value = true
    error.value = ''
    try {
        const { data } = await axios.post(`/api/edls/${props.edl.id}/duplicate`, form)
        emit('close')
        router.push({ name: 'survey', params: { id: data.id } })
    } catch (e) {
        error.value = e.response?.data?.message || 'Erreur lors de la duplication.'
        if (!e.response || e.response.status >= 500) Sentry.captureException(e)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <q-dialog :model-value="!!edl" @update:model-value="(v) => { if (!v) emit('close') }">
        <q-card style="width: 30rem; max-width: 94vw">
            <form v-if="edl" @submit.prevent="submit">
                <q-card-section>
                    <div class="text-h6">Dupliquer cet état des lieux</div>
                    <p class="text-body2 text-grey-7 mt-2 mb-0">
                        <span class="font-medium text-grey-9">{{ edl.adresse }}</span>, {{ edl.ville }}<br>
                        Crée un nouvel EDL pour le même logement, avec les mêmes pièces.
                    </p>
                </q-card-section>
                <q-card-section class="pt-0 space-y-3">
                    <q-btn-toggle v-model="form.type" no-caps unelevated dense toggle-color="primary" color="white" text-color="grey-8"
                        class="border border-slate-300 rounded-borders" aria-label="Type du nouvel EDL"
                        :options="[{ label: 'Entrant (nouveau locataire)', value: 'entrant' }, { label: 'Sortant', value: 'sortant' }]" />
                    <div class="flex flex-col">
                        <q-checkbox v-model="form.keep_survey" dense color="primary" class="py-1"
                            label="Reprendre les réponses saisies (photos et signatures ne sont jamais copiées)" />
                        <q-checkbox v-model="form.keep_tenant" dense color="primary" class="py-1" label="Conserver le locataire" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <q-input outlined dense label="Prénom du technicien *" v-model="form.technicien_prenom" required />
                        <q-input outlined dense label="Nom du technicien *" v-model="form.technicien_nom" required />
                        <q-input class="sm:col-span-2" outlined dense type="email" label="E-mail du technicien *" v-model="form.technicien_email" required />
                    </div>
                    <p v-if="error" class="text-body2 text-negative m-0">{{ error }}</p>
                </q-card-section>
                <q-card-actions align="right">
                    <q-btn flat no-caps type="button" label="Annuler" @click="emit('close')" />
                    <q-btn unelevated no-caps type="submit" color="primary" icon="mdi-content-copy" :loading="loading"
                        :label="loading ? 'Création…' : 'Dupliquer'" />
                </q-card-actions>
            </form>
        </q-card>
    </q-dialog>
</template>
