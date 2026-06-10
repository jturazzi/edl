<template>
<div v-if="loading" class="text-center py-16 text-gray-400" role="status" aria-live="polite">
    <p class="text-lg">Chargement…</p>
</div>

<div v-else class="max-w-2xl mx-auto">
    <!-- Bannière succès -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 sm:p-8 mb-5 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-3xl mb-4">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">EDL validé avec succès</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Le PDF a été généré et enregistré.</p>
    </div>

    <!-- Carte récap -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 sm:p-6 mb-5">
        <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mb-4">Récapitulatif</h2>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <dt class="text-gray-500 dark:text-gray-400 font-medium">Adresse</dt>
            <dd class="font-semibold text-gray-900 dark:text-white">{{ edl.adresse }}, {{ edl.ville }}</dd>

            <dt class="text-gray-500 dark:text-gray-400 font-medium">Type</dt>
            <dd>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="edl.type === 'entrant' ? 'badge-entrant' : 'badge-sortant'">
                    {{ edl.type_label }}
                </span>
            </dd>

            <template v-if="edl.locataire_full_name">
                <dt class="text-gray-500 dark:text-gray-400 font-medium">Locataire</dt>
                <dd class="font-semibold text-gray-900 dark:text-white">{{ edl.locataire_full_name }}</dd>
            </template>

            <dt class="text-gray-500 dark:text-gray-400 font-medium">Date</dt>
            <dd class="font-semibold text-gray-900 dark:text-white">{{ formatDate(edl.date_edl) }}</dd>

            <dt class="text-gray-500 dark:text-gray-400 font-medium">Réalisé par</dt>
            <dd class="font-semibold text-gray-900 dark:text-white">{{ edl.agent_name || 'Non renseigné' }}</dd>
        </dl>
    </div>

    <!-- Actions PDF -->
    <div class="grid grid-cols-2 gap-3 mb-5">
        <a :href="`/edl/${edl.id}/pdf/view`" target="_blank"
            class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600
                   text-white font-semibold py-3 px-4 rounded-xl shadow-sm transition min-h-[48px] text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Lire EDL
        </a>
        <a :href="`/edl/${edl.id}/pdf`"
            class="flex items-center justify-center gap-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700
                   text-gray-700 dark:text-gray-300 font-semibold py-3 px-4 rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm transition min-h-[48px] text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Télécharger EDL
        </a>
    </div>

    <!-- Actions secondaires -->
    <div class="flex gap-3 mb-5">
        <router-link to="/"
            class="flex-1 flex items-center justify-center gap-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700
                   text-gray-600 dark:text-gray-300 font-medium py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-600 transition text-sm min-h-[44px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvel EDL
        </router-link>
        <router-link to="/historique"
            class="flex-1 flex items-center justify-center gap-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700
                   text-gray-600 dark:text-gray-300 font-medium py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-600 transition text-sm min-h-[44px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Historique
        </router-link>
        <button @click="showDeleteModal = true"
            class="flex items-center justify-center gap-2 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/20
                   text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 font-medium py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-red-200 dark:hover:border-red-800 transition text-sm min-h-[44px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Supprimer
        </button>
    </div>

    <!-- Modale suppression -->
    <Teleport to="body">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
            @click.self="showDeleteModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6">
                <div class="text-center mb-5">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 mb-3">
                        <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Supprimer cet EDL ?</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ edl.adresse }}</p>
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">Cette action est irréversible.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = false"
                        class="flex-1 py-3 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm">
                        Annuler
                    </button>
                    <button @click="confirmDelete" :disabled="deleteLoading"
                        class="flex-1 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold transition disabled:opacity-60 text-sm">
                        {{ deleteLoading ? 'Suppression…' : 'Supprimer' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Envoi par email -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 sm:p-6 mt-8 text-left">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-1 flex items-center gap-2">
            📧 Envoyer le PDF par email
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Sélectionnez les destinataires ou ajoutez une adresse email.
        </p>

        <!-- Destinataires pré-remplis -->
        <div class="space-y-3 mb-5">
            <label v-if="edl.locataire_email" class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox" v-model="emailRecipients" :value="edl.locataire_email"
                    class="h-5 w-5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                <div>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                        {{ edl.locataire_full_name || 'Locataire' }}
                    </span>
                    <span class="text-gray-400 dark:text-gray-500 text-sm ml-1">– {{ edl.locataire_email }}</span>
                </div>
            </label>
            <label v-if="agentEmail" class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox" v-model="emailRecipients" :value="agentEmail"
                    class="h-5 w-5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                <div>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                        {{ edl.agent_name || 'Agent' }}
                    </span>
                    <span class="text-gray-400 dark:text-gray-500 text-sm ml-1">– {{ agentEmail }}</span>
                </div>
            </label>
        </div>

        <!-- Ajout d'email libre -->
        <div class="flex gap-2 mb-5">
            <input type="email" v-model="customEmail" placeholder="Autre adresse email…"
                @keyup.enter="addCustomEmail"
                class="flex-1 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500 px-4 py-3 text-sm
                       focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            <button @click="addCustomEmail" type="button"
                class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold px-4 py-3 rounded-xl text-sm transition">
                + Ajouter
            </button>
        </div>

        <!-- Emails personnalisés ajoutés -->
        <div v-if="customEmails.length" class="flex flex-wrap gap-2 mb-5">
            <span v-for="(email, i) in customEmails" :key="email"
                class="inline-flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full px-3 py-1 text-sm font-medium">
                {{ email }}
                <button @click="removeCustomEmail(i)" class="text-indigo-400 hover:text-red-500 ml-1">✕</button>
            </span>
        </div>

        <!-- Bouton envoi -->
        <button @click="sendEmails" :disabled="allRecipients.length === 0 || emailSending"
            class="w-full bg-green-600 hover:bg-green-700 active:bg-green-800
                   disabled:bg-gray-300 disabled:cursor-not-allowed
                   text-white font-bold py-3.5 rounded-xl shadow-sm transition text-base">
            {{ emailSending ? 'Envoi en cours…' : `📧 Envoyer à ${allRecipients.length} destinataire(s)` }}
        </button>

        <!-- Résultat -->
        <div v-if="emailSuccess" class="mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl px-4 py-3 text-green-700 dark:text-green-400 text-sm font-medium flex items-center gap-2">
            ✅ {{ emailSuccess }}
        </div>
        <div v-if="emailError" class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3 text-red-700 dark:text-red-400 text-sm font-medium flex items-center gap-2">
            ❌ {{ emailError }}
        </div>
    </div>
</div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const edl = ref({})
const user = inject('user')

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
