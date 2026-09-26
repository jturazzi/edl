<template>
<div v-if="loading" class="flex justify-center py-16" role="status" aria-live="polite">
    <q-spinner color="primary" size="40px" />
</div>

<div v-else class="max-w-3xl space-y-5">
    <PageHeader title="Signatures" subtitle="Le technicien et le locataire signent pour valider le document.">
        <AppBadge :tone="edl.type === 'entrant' ? 'green' : 'amber'" dot>{{ edl.type_label }}</AppBadge>
    </PageHeader>

    <q-card flat>
        <q-card-section>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-body2 m-0">
                <dt class="text-grey-7">Adresse</dt>
                <dd class="font-medium m-0">{{ edl.adresse }}, {{ edl.ville }}</dd>
                <dt class="text-grey-7">Technicien</dt>
                <dd class="font-medium m-0">{{ edl.agent_name }}</dd>
                <template v-if="edl.locataire_full_name">
                    <dt class="text-grey-7">Locataire</dt>
                    <dd class="font-medium m-0">{{ edl.locataire_full_name }}</dd>
                </template>
                <dt class="text-grey-7">Date</dt>
                <dd class="font-medium m-0">{{ today }}</dd>
            </dl>
        </q-card-section>
    </q-card>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Technicien -->
        <q-card flat>
            <q-card-section class="flex items-center justify-between gap-3">
                <SectionTitle icon="mdi-account-wrench" title="Technicien" size="text-subtitle1" />
                <q-btn flat dense no-caps color="grey-8" icon="mdi-eraser" label="Effacer" aria-label="Effacer la signature du technicien" class="px-2" @click="techPad.clear()" />
            </q-card-section>
            <q-card-section class="pt-0">
                <SignatureBox ref="techPad" :label="`technicien ${edl.agent_name || ''}`" @change="techSigned = $event" />
                <p class="mt-2 mb-0 text-caption text-grey-7">{{ edl.agent_name }}</p>
            </q-card-section>
        </q-card>

        <!-- Locataire -->
        <q-card flat>
            <q-card-section class="flex items-center justify-between gap-3">
                <SectionTitle icon="mdi-account" title="Locataire" size="text-subtitle1" />
                <q-btn flat dense no-caps color="grey-8" icon="mdi-eraser" label="Effacer" aria-label="Effacer la signature du locataire" class="px-2" :disable="locataireAbsent" @click="locPad.clear()" />
            </q-card-section>
            <q-card-section class="pt-0">
                <SignatureBox ref="locPad" :label="`locataire ${edl.locataire_full_name || ''}`" :disabled="locataireAbsent" @change="locSigned = $event" />
                <p class="mt-2 mb-0 text-caption text-grey-7">{{ edl.locataire_full_name || 'Locataire non renseigné' }}</p>
                <q-checkbox v-model="locataireAbsent" dense color="primary" class="mt-3"
                    label="Locataire absent ou dans l'impossibilité de signer" />
            </q-card-section>
        </q-card>
    </div>

    <q-banner rounded class="bg-slate-100 text-grey-8 text-body2">
        En signant ce document, les parties attestent avoir vérifié l'état du logement situé
        <strong>{{ edl.adresse }}, {{ edl.ville }}</strong> et confirment les constats consignés
        dans le présent état des lieux <strong>{{ edl.type_label }}</strong>
        en date du <strong>{{ today }}</strong>.
    </q-banner>

    <q-btn class="w-full" unelevated no-caps size="lg" color="positive" icon="mdi-check-circle" :loading="submitting"
        :disable="!canSubmit" :label="submitting ? 'Génération du PDF en cours…' : 'Valider et générer le PDF'"
        @click="submitSignature" />
    <q-banner v-if="syncError" dense rounded class="bg-red-1 text-negative">{{ syncError }}</q-banner>
    <p v-if="showEmptyMsg" class="text-center text-body2 text-negative m-0">
        Le technicien et le locataire (ou la mention « locataire absent ») doivent être renseignés.
    </p>
</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import * as Sentry from '@sentry/vue'
import SectionTitle from '@/components/SectionTitle.vue'
import SignatureBox from '@/components/SignatureBox.vue'
import AppBadge from '@/components/AppBadge.vue'
import PageHeader from '@/components/PageHeader.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const edl = ref({})
const submitting = ref(false)
const showEmptyMsg = ref(false)

const techPad = ref(null)
const locPad = ref(null)
const techSigned = ref(false)
const locSigned = ref(false)
const locataireAbsent = ref(false)

const today = new Date().toLocaleDateString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
})

// Le locataire peut être absent / dans l'impossibilité de signer : sa signature n'est alors pas requise
const syncError = ref('')
const canSubmit = computed(() => techSigned.value && (locataireAbsent.value || locSigned.value))

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/edls/${route.params.id}`)
        edl.value = data
    } catch (e) {
        console.error('Erreur chargement EDL', e)
    } finally {
        loading.value = false
    }
})

async function submitSignature() {
    if (!canSubmit.value) {
        showEmptyMsg.value = true
        return
    }

    showEmptyMsg.value = false
    syncError.value = ''
    submitting.value = true

    try {
        await axios.post(`/api/edls/${route.params.id}/finalize`, {
            signature_technicien: techPad.value.toDataURL(),
            signature: locataireAbsent.value ? null : locPad.value.toDataURL(),
            locataire_absent: locataireAbsent.value,
        })
        router.push({ name: 'confirmation', params: { id: route.params.id } })
    } catch (e) {
        console.error('Erreur finalisation', e)
        const errors = e.response?.data?.errors
        syncError.value = errors
            ? Object.values(errors).flat().join(' ')
            : e.response?.data?.message || 'La validation a échoué. Réessayez.'
        Sentry.captureException(e)
        submitting.value = false
    }
}
</script>
