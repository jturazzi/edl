<template>
<div class="max-w-2xl mx-auto fade-in-up">
    <!-- En-tête -->
    <div class="mb-7 sm:mb-9 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-200 dark:shadow-indigo-900/40 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Nouvel état des lieux</h1>
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">Renseignez les informations du logement pour démarrer.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-5">

        <!-- Adresse du logement -->
        <div class="card p-5 sm:p-6">
            <h2 class="flex items-center gap-2.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-5">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-indigo-100 dark:bg-indigo-900/40" aria-hidden="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
                Adresse du logement
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Adresse <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.adresse" required
                        placeholder="ex : 12 Rue de la Paix, Bât B Apt 3"
                        class="field-input"
                        :class="{ '!border-red-400 !ring-red-300/30': errors.adresse }">
                    <p v-if="errors.adresse" class="text-red-500 text-xs mt-1.5 flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ errors.adresse[0] }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Ville <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" v-model="villeSearch" autocomplete="off"
                            @input="onVilleInput" @focus="onVilleInput"
                            placeholder="Rechercher une commune…"
                            class="field-input"
                            :class="{ '!border-red-400 !ring-red-300/30': errors.ville }">
                        <ul v-if="villeResults.length > 0"
                            class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 max-h-60 overflow-y-auto">
                            <li v-for="c in villeResults" :key="c.nom"
                                @click="selectVille(c)"
                                class="px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer text-sm text-gray-700 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700/60 last:border-0 transition-colors">
                                <span class="text-gray-400 text-xs font-mono mr-1.5">{{ c.cp }}</span>
                                <span class="font-semibold">{{ c.nom }}</span>
                            </li>
                        </ul>
                    </div>
                    <p v-if="errors.ville" class="text-red-500 text-xs mt-1.5 flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ errors.ville[0] }}</p>
                </div>
            </div>
        </div>

        <!-- Informations locataire -->
        <div class="card p-5 sm:p-6">
            <h2 class="flex items-center gap-2.5 text-xs font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest mb-5">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-violet-100 dark:bg-violet-900/40" aria-hidden="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                Locataire
                <span class="font-normal text-gray-400 dark:text-gray-500 normal-case text-xs tracking-normal ml-0.5">(optionnel)</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Prénom</label>
                    <input type="text" v-model="form.locataire_prenom"
                        @input="formatPrenom"
                        class="field-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nom</label>
                    <input type="text" v-model="form.locataire_nom"
                        @input="formatNom"
                        class="field-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <input type="email" v-model="form.locataire_email"
                        class="field-input">
                </div>
            </div>
        </div>

        <!-- Catégorie -->
        <div class="card p-5 sm:p-6">
            <h2 class="flex items-center gap-2.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-4">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-900/40" aria-hidden="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </span>
                Catégorie
                <span class="font-normal text-gray-400 dark:text-gray-500 normal-case text-xs tracking-normal ml-0.5">(optionnel)</span>
            </h2>

            <div v-if="categories.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic">
                Aucune catégorie créée — <router-link to="/admin/info" class="text-indigo-500 hover:underline">en créer une</router-link>
            </div>
            <div v-else class="flex flex-wrap gap-2">
                <button type="button"
                    v-for="cat in categories" :key="cat.id"
                    @click="form.category_id = form.category_id === cat.id ? null : cat.id"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-semibold border-2 transition-all"
                    :style="form.category_id === cat.id ? `background:${cat.color}22; border-color:${cat.color}; color:${cat.color}` : 'border-color:#e5e7eb; color:#6b7280; background:transparent'"
                >
                    <span class="inline-block w-2 h-2 rounded-full" :style="`background:${cat.color}`"></span>
                    {{ cat.name }}
                </button>
            </div>
        </div>

        <!-- Type d'EDL -->
        <div>
            <h2 class="flex items-center gap-2.5 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-widest mb-4">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-gray-100 dark:bg-gray-700" aria-hidden="true">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
                Type d'état des lieux
            </h2>
            <div class="grid grid-cols-2 gap-4">
                <label class="cursor-pointer flex flex-col">
                    <input type="radio" v-model="form.type" value="entrant" class="sr-only peer" required>
                    <div class="flex-1 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 peer-checked:ring-2 peer-checked:ring-emerald-400/60 peer-checked:shadow-emerald-100 dark:peer-checked:shadow-none
                                border-2 border-gray-200 dark:border-gray-600 rounded-2xl p-4 sm:p-6 text-center bg-white dark:bg-gray-800 shadow-sm transition-all hover:border-emerald-300 hover:shadow-md active:scale-[.98] flex flex-col items-center justify-center">
                        <span class="text-4xl sm:text-5xl block mb-2 sm:mb-3" aria-hidden="true">🔑</span>
                        <p class="font-bold text-gray-900 dark:text-white text-base sm:text-xl">Entrant</p>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Remise des clés au locataire</p>
                    </div>
                </label>
                <label class="cursor-pointer flex flex-col">
                    <input type="radio" v-model="form.type" value="sortant" class="sr-only peer">
                    <div class="flex-1 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 peer-checked:ring-2 peer-checked:ring-amber-400/60
                                border-2 border-gray-200 dark:border-gray-600 rounded-2xl p-4 sm:p-6 text-center bg-white dark:bg-gray-800 shadow-sm transition-all hover:border-amber-300 hover:shadow-md active:scale-[.98] flex flex-col items-center justify-center">
                        <span class="text-4xl sm:text-5xl block mb-2 sm:mb-3" aria-hidden="true">🚪</span>
                        <p class="font-bold text-gray-900 dark:text-white text-base sm:text-xl">Sortant</p>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Rendu des clés par le locataire</p>
                    </div>
                </label>
            </div>
            <p v-if="errors.type" class="text-red-500 text-xs mt-2 flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ errors.type[0] }}</p>
        </div>

        <button type="submit" :disabled="submitting"
            class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 active:from-indigo-800 active:to-violet-800
                   disabled:from-gray-300 disabled:to-gray-300 disabled:cursor-not-allowed
                   text-white font-bold py-4 rounded-2xl shadow-md shadow-indigo-200 dark:shadow-indigo-900/30 transition-all duration-150 text-base tracking-wide">
            <span v-if="submitting" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Création en cours…
            </span>
            <span v-else>Commencer l'état des lieux →</span>
        </button>
    </form>
</div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const form = reactive({
    adresse: '',
    ville: '',
    type: '',
    locataire_nom: '',
    locataire_prenom: '',
    locataire_email: '',
    category_id: null,
})

const categories = ref([])

const errors = ref({})
const submitting = ref(false)

// ── Recherche commune ────────────────────────────────
const villeSearch = ref('')
const villeResults = ref([])
const communes = ref([])

function formatPrenom(e) {
    const v = e.target.value
    // Majuscule sur la première lettre de chaque mot (gère les prénoms composés)
    form.locataire_prenom = v.replace(/(^|[\s-])([a-zà-ÿ])/g, (_, sep, c) => sep + c.toUpperCase())
}

function formatNom(e) {
    form.locataire_nom = e.target.value.toUpperCase()
}

function normalize(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
}

onMounted(async () => {
    try {
        const [geoRes, catRes] = await Promise.all([
            fetch(`https://geo.api.gouv.fr/departements/${window.__APP_DEPARTEMENT__}/communes?fields=nom,codesPostaux&order=nom`),
            axios.get('/api/categories'),
        ])
        const geoData = await geoRes.json()
        communes.value = geoData.map(c => ({
            nom: c.nom,
            cp: (c.codesPostaux && c.codesPostaux[0]) || '',
            search: normalize(c.nom),
        }))
        categories.value = catRes.data
    } catch {
        // silently fail
    }
})

function onVilleInput() {
    form.ville = '' // Reset selection when typing
    const query = normalize(villeSearch.value.trim())
    if (query.length < 1) {
        villeResults.value = []
        return
    }
    villeResults.value = communes.value
        .filter(c => c.search.includes(query))
        .slice(0, 30)
}

function selectVille(c) {
    const fullValue = c.cp + ' ' + c.nom
    villeSearch.value = fullValue
    form.ville = fullValue
    villeResults.value = []
}

// ── Soumission ───────────────────────────────────────
async function submit() {
    errors.value = {}
    if (!form.adresse) {
        errors.value = { adresse: ["L'adresse est obligatoire."] }
        return
    }
    if (!form.ville) {
        errors.value = { ville: ['Veuillez sélectionner une commune dans la liste.'] }
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
        }
    } finally {
        submitting.value = false
    }
}
</script>
