<template>
<div v-if="loading" class="text-center py-16 text-gray-400 dark:text-gray-500" role="status" aria-live="polite">
    <p class="text-lg">Chargement…</p>
</div>

<div v-else class="max-w-2xl mx-auto">
    <!-- Récap -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 sm:p-6 mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4 sm:mb-5">✍️ Signature du document</h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-4 text-sm sm:text-base">
            <div class="text-gray-500 dark:text-gray-400 font-medium">Adresse</div>
            <div class="font-semibold text-gray-800 dark:text-gray-100">{{ edl.adresse }}, {{ edl.ville }}</div>
            <div class="text-gray-500 dark:text-gray-400 font-medium">Type</div>
            <div>
                <span class="inline-flex items-center rounded-full px-4 py-1 text-sm font-semibold"
                    :class="edl.type === 'entrant' ? 'badge-entrant' : 'badge-sortant'">
                    {{ edl.type_label }}
                </span>
            </div>
            <template v-if="edl.locataire_full_name">
                <div class="text-gray-500 dark:text-gray-400 font-medium">Locataire</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ edl.locataire_full_name }}</div>
            </template>
            <div class="text-gray-500 dark:text-gray-400 font-medium">Date</div>
            <div class="font-semibold text-gray-800 dark:text-gray-100">{{ today }}</div>
        </div>
    </div>

    <!-- Zone de signature -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-700 dark:text-gray-200">Signer ci-dessous</h2>
            <button @click="clearSignature" type="button"
                class="inline-flex items-center gap-2 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300
                       border border-red-200 dark:border-red-800 hover:border-red-400 dark:hover:border-red-600 rounded-xl px-4 py-2.5
                       font-semibold transition active:scale-95 text-sm">
                🗑 Effacer
            </button>
        </div>
        <div ref="canvasWrapper" class="relative w-full" style="height: 280px;">
            <canvas ref="canvasEl"
                class="absolute inset-0 w-full h-full signature-canvas"
                style="touch-action:none; border:3px dashed #a5b4fc; border-radius:1rem; cursor:crosshair;">
            </canvas>
        </div>
        <p class="mt-3 text-sm text-gray-400 dark:text-gray-500 text-center">
            Signez avec votre doigt ou le stylet dans le cadre ci-dessus
        </p>
    </div>

    <!-- Mentions légales -->
    <div class="bg-slate-50 dark:bg-gray-700/50 rounded-xl border border-slate-200 dark:border-gray-600 px-5 py-4 mb-6 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
        <p>
            En signant ce document, j'atteste avoir vérifié l'état du logement situé
            <strong>{{ edl.adresse }}, {{ edl.ville }}</strong> et confirme les constats consignés
            dans le présent état des lieux <strong>{{ edl.type_label }}</strong>
            en date du <strong>{{ today }}</strong>.
        </p>
    </div>

    <!-- Bouton validation -->
    <button @click="submitSignature" :disabled="!hasSigned || submitting"
        class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800
               disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:cursor-not-allowed
               text-white font-bold py-4 sm:py-5 rounded-2xl shadow-md transition text-lg sm:text-xl tracking-wide">
        {{ submitting ? 'Traitement en cours…' : '✅ Valider et générer le PDF' }}
    </button>
    <p v-if="showEmptyMsg" class="text-center text-red-500 dark:text-red-400 text-sm mt-3">
        Veuillez apposer votre signature avant de valider.
    </p>

    <!-- Loader -->
    <div v-if="submitting" class="mt-8 text-center text-indigo-600 dark:text-indigo-400">
        <svg class="animate-spin h-10 w-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <p class="text-base font-semibold">Génération du PDF en cours…</p>
    </div>
</div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SignaturePad from 'signature_pad'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const edl = ref({})
const hasSigned = ref(false)
const submitting = ref(false)
const showEmptyMsg = ref(false)

const canvasWrapper = ref(null)
const canvasEl = ref(null)
let pad = null
let resizeObserver = null
let darkObserver = null

const today = new Date().toLocaleDateString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
})

// ── Couleurs selon le mode ───────────────────────────
const isDark = ref(document.documentElement.classList.contains('dark'))

const padBg  = () => isDark.value ? '#1e293b' : '#f8fafc'
const padPen = () => isDark.value ? '#ffffff' : '#1e1b4b'

/** Applique les nouvelles couleurs sans perdre les traits dessinés */
function applyPadColors() {
    if (!pad) return
    const savedData = pad.toData()
    pad.backgroundColor = padBg()
    pad.penColor        = padPen()
    pad.clear()
    if (savedData && savedData.length > 0) {
        // Recolorise chaque groupe de traits avec la nouvelle couleur d'encre
        const recolored = savedData.map(group => ({ ...group, color: padPen() }))
        pad.fromData(recolored)
    }
    hasSigned.value = !pad.isEmpty()
}

function resizeCanvas() {
    if (!canvasWrapper.value || !canvasEl.value) return
    const savedData = pad ? pad.toData() : []
    const ratio = Math.max(window.devicePixelRatio || 1, 1)
    const rect = canvasWrapper.value.getBoundingClientRect()
    canvasEl.value.width  = rect.width  * ratio
    canvasEl.value.height = rect.height * ratio
    canvasEl.value.getContext('2d').scale(ratio, ratio)
    if (pad) {
        pad.backgroundColor = padBg()
        pad.clear()
        if (savedData && savedData.length > 0) {
            pad.fromData(savedData)
        }
    }
    hasSigned.value = pad ? !pad.isEmpty() : false
}

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/edls/${route.params.id}`)
        edl.value = data
    } catch (e) {
        console.error('Erreur chargement EDL', e)
    } finally {
        loading.value = false
    }

    await nextTick()

    if (canvasEl.value) {
        pad = new SignaturePad(canvasEl.value, {
            backgroundColor: padBg(),
            penColor: padPen(),
            minWidth: 2,
            maxWidth: 4,
        })

        resizeCanvas()

        resizeObserver = new ResizeObserver(() => resizeCanvas())
        resizeObserver.observe(canvasWrapper.value)

        // Observer les changements de mode sombre sur <html>
        darkObserver = new MutationObserver(() => {
            const nowDark = document.documentElement.classList.contains('dark')
            if (nowDark !== isDark.value) {
                isDark.value = nowDark
                applyPadColors()
            }
        })
        darkObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

        pad.addEventListener('endStroke', () => {
            hasSigned.value = !pad.isEmpty()
        })
    }
})

onBeforeUnmount(() => {
    if (resizeObserver) resizeObserver.disconnect()
    if (darkObserver)   darkObserver.disconnect()
})

function clearSignature() {
    if (pad) pad.clear()
    hasSigned.value = false
}

async function submitSignature() {
    if (!pad || pad.isEmpty()) {
        showEmptyMsg.value = true
        return
    }

    showEmptyMsg.value = false
    submitting.value = true

    try {
        const signatureData = pad.toDataURL('image/png')
        await axios.post(`/api/edls/${route.params.id}/finalize`, {
            signature: signatureData,
        })
        router.push({ name: 'confirmation', params: { id: route.params.id } })
    } catch (e) {
        console.error('Erreur finalisation', e)
        submitting.value = false
    }
}
</script>
