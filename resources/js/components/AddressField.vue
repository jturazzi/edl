<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'

// Champ adresse avec suggestions de la Base Adresse Nationale (api-adresse.data.gouv.fr, service public, sans clé).
// La saisie reste libre : si le service est indisponible, on peut taper l'adresse à la main.
// Navigation clavier : ↑ ↓ pour parcourir, Entrée pour choisir, Échap pour fermer.
const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Adresse *' },
    error: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'select'])

const API = 'https://api-adresse.data.gouv.fr/search/'
const uid = `addr-${Math.random().toString(36).slice(2, 8)}`

const results = ref([])
const open = ref(false)
const active = ref(-1)
const loading = ref(false)
const unavailable = ref(false)
let timer = null
let controller = null
let picked = null // libellé qui vient d'être choisi : évite de relancer une recherche dessus

function onInput(value) {
    emit('update:modelValue', value ?? '')
    active.value = -1
    clearTimeout(timer)
    if (!value || value.trim().length < 3 || value === picked) {
        results.value = []
        open.value = false
        return
    }
    timer = setTimeout(() => search(value.trim()), 250)
}

async function search(query) {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        const res = await fetch(`${API}?${new URLSearchParams({ q: query, limit: '6', autocomplete: '1' })}`, { signal: controller.signal })
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const json = await res.json()
        results.value = (json.features ?? []).map((f) => ({
            id: f.properties.id,
            label: f.properties.name,
            detail: [f.properties.postcode, f.properties.city].filter(Boolean).join(' '),
            context: f.properties.context,
            ville: [f.properties.postcode, f.properties.city].filter(Boolean).join(' '),
        }))
        unavailable.value = false
        open.value = results.value.length > 0
    } catch (e) {
        if (e.name === 'AbortError') return
        results.value = []
        open.value = false
        unavailable.value = true
    } finally {
        loading.value = false
    }
}

function choose(item) {
    picked = item.label
    emit('update:modelValue', item.label)
    emit('select', { adresse: item.label, ville: item.ville })
    results.value = []
    open.value = false
    active.value = -1
}

function onKeydown(e) {
    if (!open.value) return
    if (e.key === 'ArrowDown') { e.preventDefault(); active.value = (active.value + 1) % results.value.length }
    else if (e.key === 'ArrowUp') { e.preventDefault(); active.value = (active.value - 1 + results.value.length) % results.value.length }
    else if (e.key === 'Enter' && active.value >= 0) { e.preventDefault(); choose(results.value[active.value]) }
    else if (e.key === 'Escape') { open.value = false }
}

// Le champ est vidé de l'extérieur (réinitialisation du formulaire)
watch(() => props.modelValue, (v) => { if (!v) picked = null })
onBeforeUnmount(() => { clearTimeout(timer); controller?.abort() })
</script>

<template>
    <div class="relative">
        <q-input :model-value="modelValue" outlined stack-label bg-color="white" :label="label" type="text" required autocomplete="off"
            placeholder="ex : 12 Rue de la Paix" :error="error" :error-message="errorMessage" hide-bottom-space
            role="combobox" aria-autocomplete="list" :aria-expanded="open" :aria-controls="`${uid}-list`"
            :aria-activedescendant="active >= 0 ? `${uid}-${active}` : undefined"
            @update:model-value="onInput" @keydown="onKeydown" @blur="() => setTimeout(() => { open = false }, 150)">
            <template #prepend><q-icon name="mdi-map-marker-outline" /></template>
            <template #append><q-spinner v-if="loading" size="18px" color="grey-6" /></template>
        </q-input>

        <ul v-show="open" :id="`${uid}-list`" role="listbox" aria-label="Adresses suggérées"
            class="absolute left-0 right-0 z-20 m-0 mt-1 max-h-72 list-none overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
            <li v-for="(item, i) in results" :key="item.id" :id="`${uid}-${i}`" role="option" :aria-selected="i === active"
                class="cursor-pointer rounded-md px-3 py-2" :class="i === active ? 'bg-blue-50' : 'hover:bg-slate-50'"
                @mousedown.prevent="choose(item)" @mousemove="active = i">
                <span class="block text-body2 font-medium text-slate-900">{{ item.label }}</span>
                <span class="block text-caption text-grey-7">{{ item.detail }}<template v-if="item.context"> · {{ item.context }}</template></span>
            </li>
        </ul>

        <p v-if="unavailable" class="m-0 mt-1 text-caption text-grey-7">Suggestions d'adresse indisponibles : saisissez l'adresse à la main.</p>
        <p class="sr-only" role="status" aria-live="polite">{{ open ? `${results.length} suggestion(s) disponible(s)` : '' }}</p>
    </div>
</template>
