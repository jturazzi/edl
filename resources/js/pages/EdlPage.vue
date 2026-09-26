<template>
<div v-if="loading" class="flex justify-center py-16" role="status" aria-live="polite">
    <q-spinner color="primary" size="40px" />
</div>

<div v-else class="max-w-2xl space-y-5">
    <q-banner rounded class="bg-green-1 text-green-9">
        <template #avatar><q-icon name="mdi-check-circle" color="positive" /></template>
        <div class="font-bold">EDL validé avec succès</div>
        <div class="text-body2">Le PDF a été généré et enregistré.</div>
    </q-banner>
    <q-banner v-if="edl.archived_at" dense rounded class="bg-amber-1 text-amber-10">
        <template #avatar><q-icon name="mdi-archive-outline" color="warning" /></template>
        Cet état des lieux est archivé : il n'apparaît plus dans l'historique ni sur le tableau de bord.
    </q-banner>

    <!-- Récapitulatif -->
    <q-card flat bordered>
        <q-card-section><SectionTitle icon="mdi-file-document-outline" title="Récapitulatif" /></q-card-section>
        <q-card-section class="pt-0">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-body2 m-0">
                <dt class="text-grey-7">N° EDL</dt>
                <dd class="font-medium font-mono m-0">{{ edl.numero }}</dd>

                <dt class="text-grey-7">Adresse</dt>
                <dd class="font-medium m-0">{{ edl.adresse }}, {{ edl.ville }}</dd>

                <dt class="text-grey-7">Type</dt>
                <dd class="m-0"><AppBadge :tone="edl.type === 'entrant' ? 'green' : 'amber'" dot>{{ edl.type_label }}</AppBadge></dd>

                <template v-if="edl.locataire_full_name">
                    <dt class="text-grey-7">Locataire</dt>
                    <dd class="font-medium m-0">{{ edl.locataire_full_name }}</dd>
                </template>

                <dt class="text-grey-7">Date</dt>
                <dd class="font-medium m-0">{{ formatDate(edl.date_edl) }}</dd>

                <dt class="text-grey-7">Réalisé par</dt>
                <dd class="font-medium m-0">{{ edl.agent_name || 'Non renseigné' }}</dd>
            </dl>
        </q-card-section>
    </q-card>

    <!-- Actions PDF -->
    <div class="grid grid-cols-2 gap-3">
        <q-btn :href="`/edl/${edl.id}/pdf/view`" target="_blank" unelevated no-caps size="lg" color="primary" icon="mdi-eye" label="Lire EDL" />
        <q-btn :href="`/edl/${edl.id}/pdf`" unelevated no-caps size="lg" color="grey-3" text-color="grey-9" icon="mdi-download" label="Télécharger EDL" />
    </div>

    <!-- Intégrité du PDF -->
    <q-card flat bordered>
        <q-card-section class="flex flex-wrap items-center gap-3">
            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><q-icon name="mdi-shield-check-outline" size="22px" /></span>
            <div class="min-w-0 flex-1">
                <p class="m-0 font-medium">Intégrité du document</p>
                <p class="m-0 text-caption text-grey-7">
                    Signé le {{ formatDate(edl.signed_at) }}
                    <template v-if="edl.pdf_hash"> · empreinte SHA-256 <span class="font-mono break-all" :title="edl.pdf_hash">{{ edl.pdf_hash.slice(0, 16) }}…</span></template>
                </p>
                <p v-if="integrity" class="m-0 mt-1 text-body2" role="status" aria-live="polite"
                    :class="integrity.valid === true ? 'text-positive' : integrity.valid === false ? 'text-negative' : 'text-grey-8'">
                    <q-icon :name="integrity.valid === true ? 'mdi-check-circle' : integrity.valid === false ? 'mdi-alert-circle' : 'mdi-help-circle-outline'" />
                    {{ integrityText }}
                </p>
            </div>
            <q-btn outline no-caps color="primary" icon="mdi-shield-search" label="Vérifier" :loading="integrityLoading" @click="checkIntegrity" />
        </q-card-section>
    </q-card>

    <!-- Actions secondaires -->
    <div class="flex flex-wrap gap-3">
        <q-btn v-if="edl.type === 'sortant' && edl.entrant_id" class="flex-1" unelevated no-caps color="secondary" icon="mdi-compare-horizontal"
            label="Comparer avec l'entrant" :to="{ name: 'comparison', params: { id: edl.id } }" />
        <q-btn class="flex-1" outline no-caps color="primary" icon="mdi-plus-circle-outline" label="Nouvel EDL" to="/nouveau" />
        <q-btn class="flex-1" outline no-caps color="primary" icon="mdi-home-clock-outline" label="Historique du logement" :to="{ name: 'logement', query: { adresse: edl.adresse, ville: edl.ville ?? '' } }" />
        <q-btn class="flex-1" outline no-caps color="primary" icon="mdi-history" label="Historique" to="/historique" />
        <q-btn outline no-caps color="primary" icon="mdi-content-copy" label="Dupliquer" @click="showDuplicate = true" />
        <q-btn v-if="user?.is_admin && !edl.archived_at" outline no-caps color="grey-8" icon="mdi-archive-outline" label="Archiver" :loading="archiveLoading" @click="setArchived(true)" />
        <q-btn v-if="user?.is_admin && edl.archived_at" outline no-caps color="grey-8" icon="mdi-archive-arrow-up-outline" label="Désarchiver" :loading="archiveLoading" @click="setArchived(false)" />
        <q-btn v-if="user?.is_admin" outline no-caps color="negative" icon="mdi-delete-outline" label="Supprimer" @click="showDeleteModal = true" />
    </div>

    <DuplicateDialog :edl="showDuplicate ? edl : null" @close="showDuplicate = false" />

    <!-- Modale suppression -->
    <q-dialog v-model="showDeleteModal">
        <q-card style="width: 24rem; max-width: 92vw">
            <q-card-section>
                <div class="text-h6">Supprimer cet EDL ?</div>
                <p class="text-body2 mt-2 mb-0">{{ edl.adresse }}</p>
                <p class="text-body2 text-negative font-medium mt-1 mb-0">Cette action est irréversible.</p>
            </q-card-section>
            <q-card-actions align="right">
                <q-btn flat no-caps label="Annuler" v-close-popup />
                <q-btn unelevated no-caps color="negative" icon="mdi-delete" :loading="deleteLoading"
                    :label="deleteLoading ? 'Suppression…' : 'Supprimer'" @click="confirmDelete" />
            </q-card-actions>
        </q-card>
    </q-dialog>

    <!-- Envoi par email -->
    <q-card flat bordered>
        <q-card-section>
            <SectionTitle icon="mdi-email-outline" title="Envoyer le PDF par email" />
            <p class="text-body2 text-grey-7 mt-2 mb-0">Sélectionnez les destinataires ou ajoutez une adresse email.</p>
        </q-card-section>
        <q-card-section class="pt-0 space-y-5">
            <div class="flex flex-col">
                <q-checkbox v-if="edl.locataire_email" v-model="emailRecipients" :val="edl.locataire_email" color="primary">
                    <span class="text-body2">
                        <span class="font-medium">{{ edl.locataire_full_name || 'Locataire' }}</span>
                        <span class="text-grey-7"> – {{ edl.locataire_email }}</span>
                    </span>
                </q-checkbox>
                <q-checkbox v-if="agentEmail" v-model="emailRecipients" :val="agentEmail" color="primary">
                    <span class="text-body2">
                        <span class="font-medium">{{ edl.agent_name || 'Agent' }}</span>
                        <span class="text-grey-7"> – {{ agentEmail }}</span>
                    </span>
                </q-checkbox>
            </div>

            <div class="flex gap-2">
                <q-input class="flex-1" outlined dense type="email" v-model="customEmail" placeholder="Autre adresse email…"
                    @keyup.enter="addCustomEmail" />
                <q-btn unelevated no-caps color="secondary" icon="mdi-plus" label="Ajouter" @click="addCustomEmail" />
            </div>

            <div v-if="customEmails.length" class="flex flex-wrap gap-2">
                <q-chip v-for="(email, i) in customEmails" :key="email" removable color="blue-1" text-color="primary"
                    @remove="removeCustomEmail(i)">{{ email }}</q-chip>
            </div>

            <q-btn class="w-full" unelevated no-caps size="lg" color="positive" icon="mdi-send" :loading="emailSending"
                :disable="allRecipients.length === 0"
                :label="emailSending ? 'Envoi en cours…' : `Envoyer à ${allRecipients.length} destinataire(s)`"
                @click="sendEmails" />

            <q-banner v-if="emailSuccess" rounded class="bg-green-1 text-green-9">
                <template #avatar><q-icon name="mdi-check-circle" color="positive" /></template>{{ emailSuccess }}
            </q-banner>
            <q-banner v-if="emailError" rounded class="bg-red-1 text-negative">
                <template #avatar><q-icon name="mdi-close-circle" color="negative" /></template>{{ emailError }}
            </q-banner>
        </q-card-section>
    </q-card>
</div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import AppBadge from '@/components/AppBadge.vue'
import SectionTitle from '@/components/SectionTitle.vue'
import DuplicateDialog from '@/components/DuplicateDialog.vue'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const edl = ref({})
const user = inject('user')

// Intégrité du PDF (empreinte enregistrée à la validation)
const integrity = ref(null)
const integrityLoading = ref(false)
const integrityText = computed(() => {
    if (!integrity.value) return ''
    if (!integrity.value.file) return 'Le fichier PDF est introuvable sur le serveur.'
    if (integrity.value.valid === true) return 'Le PDF est identique à celui généré à la validation.'
    if (integrity.value.valid === false) return 'Attention : le PDF ne correspond plus à celui généré à la validation.'
    return "Ce PDF a été généré avant l'enregistrement des empreintes : vérification impossible."
})

async function checkIntegrity() {
    integrityLoading.value = true
    try {
        integrity.value = (await axios.get(`/api/edls/${route.params.id}/integrity`)).data
    } catch (e) {
        integrity.value = { file: true, valid: null }
        console.error('Erreur vérification intégrité', e)
    } finally {
        integrityLoading.value = false
    }
}

// Duplication / archivage
const showDuplicate = ref(false)
const archiveLoading = ref(false)

async function setArchived(archived) {
    archiveLoading.value = true
    try {
        if (archived) await axios.post(`/api/edls/${route.params.id}/archive`)
        else await axios.delete(`/api/edls/${route.params.id}/archive`)
        edl.value = { ...edl.value, archived_at: archived ? new Date().toISOString() : null }
    } catch (e) {
        console.error('Erreur archivage', e)
    } finally {
        archiveLoading.value = false
    }
}

// Suppression
const showDeleteModal = ref(false)
const deleteLoading = ref(false)

async function confirmDelete() {
    deleteLoading.value = true
    try {
        await axios.delete(`/api/edls/${route.params.id}`)
        router.push({ name: 'history' })
    } catch (e) {
        console.error('Erreur suppression', e)
    } finally {
        deleteLoading.value = false
        showDeleteModal.value = false
    }
}

// Email state
const emailRecipients = ref([])
const customEmail = ref('')
const customEmails = ref([])
const emailSending = ref(false)
const emailSuccess = ref('')
const emailError = ref('')

const agentEmail = computed(() => user.value?.email || null)

const allRecipients = computed(() => {
    return [...new Set([...emailRecipients.value, ...customEmails.value])]
})

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function addCustomEmail() {
    const email = customEmail.value.trim()
    if (!email) return
    // Validation basique
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return
    if (!customEmails.value.includes(email) && !emailRecipients.value.includes(email)) {
        customEmails.value.push(email)
    }
    customEmail.value = ''
}

function removeCustomEmail(index) {
    customEmails.value.splice(index, 1)
}

async function sendEmails() {
    if (allRecipients.value.length === 0) return
    emailSending.value = true
    emailSuccess.value = ''
    emailError.value = ''

    try {
        const { data } = await axios.post(`/api/edls/${route.params.id}/send-email`, {
            recipients: allRecipients.value,
        })
        emailSuccess.value = data.message
    } catch (e) {
        emailError.value = e.response?.data?.message || 'Erreur lors de l\'envoi de l\'email.'
    } finally {
        emailSending.value = false
    }
}

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/edls/${route.params.id}`)
        edl.value = data

        // Pré-cocher le locataire s'il a un email
        if (data.locataire_email) {
            emailRecipients.value.push(data.locataire_email)
        }
    } catch (e) {
        console.error('Erreur chargement EDL', e)
    } finally {
        loading.value = false
    }
})
</script>
