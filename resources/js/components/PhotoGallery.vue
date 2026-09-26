<script setup>
import { ref, computed, watch } from 'vue'

// Miniatures de photos + visionneuse (agrandissement, navigation, légende, suppression)
const props = defineProps({
    photos: { type: Array, default: () => [] },
    editable: { type: Boolean, default: true },
    alt: { type: String, default: 'Photo' },
    cols: { type: String, default: 'grid-cols-3 sm:grid-cols-4' },
})
const emit = defineEmits(['delete', 'caption'])

const index = ref(null)
const draft = ref('')
const confirmDelete = ref(false)

const open = computed({
    get: () => index.value !== null,
    set: (v) => { if (!v) close() },
})
const current = computed(() => (index.value === null ? null : props.photos[index.value] ?? null))

watch(current, (p) => {
    draft.value = p?.caption ?? ''
    confirmDelete.value = false
}, { immediate: true })

// Si la photo affichée disparaît (suppression), on ferme ou on se recale
watch(() => props.photos.length, (n) => {
    if (index.value !== null && index.value >= n) index.value = n ? n - 1 : null
})

function show(i) { index.value = i }
function close() { saveCaption(); index.value = null }
function move(step) {
    saveCaption()
    const n = props.photos.length
    index.value = (index.value + step + n) % n
}
function saveCaption() {
    const p = current.value
    if (!p || !props.editable) return
    if ((p.caption ?? '') !== draft.value.trim()) emit('caption', p, draft.value.trim())
}
function remove() {
    if (!confirmDelete.value) { confirmDelete.value = true; return }
    const p = current.value
    confirmDelete.value = false
    emit('delete', p)
}
</script>

<template>
    <div v-if="photos.length" class="grid gap-2" :class="cols">
        <button v-for="(photo, i) in photos" :key="photo.id" type="button"
            class="group relative overflow-hidden rounded-lg border border-slate-200 bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            :aria-label="`Agrandir la photo ${i + 1}`" @click="show(i)">
            <img :src="photo.url" :alt="photo.caption || alt" class="h-24 w-full object-cover transition group-hover:scale-105">
            <span v-if="photo.caption" class="absolute inset-x-0 bottom-0 truncate bg-black/55 px-1.5 py-0.5 text-left text-[11px] text-white">{{ photo.caption }}</span>
        </button>
    </div>

    <q-dialog v-model="open" maximized :transition-duration="0">
        <div v-if="current" class="flex h-full w-full flex-col bg-slate-950 text-white" tabindex="0"
            @keydown.left.prevent="move(-1)" @keydown.right.prevent="move(1)">
            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-body2 text-slate-300">Photo {{ index + 1 }} / {{ photos.length }}</span>
                <q-btn flat round dense color="white" icon="mdi-close" aria-label="Fermer" @click="close" />
            </div>

            <div class="relative flex min-h-0 flex-1 items-center justify-center px-2 sm:px-14">
                <q-btn v-if="photos.length > 1" class="absolute left-1 sm:left-3" round flat color="white" icon="mdi-chevron-left" aria-label="Photo précédente" @click="move(-1)" />
                <img :src="current.url" :alt="current.caption || alt" class="max-h-full max-w-full rounded object-contain">
                <q-btn v-if="photos.length > 1" class="absolute right-1 sm:right-3" round flat color="white" icon="mdi-chevron-right" aria-label="Photo suivante" @click="move(1)" />
            </div>

            <div class="mx-auto flex w-full max-w-2xl items-center gap-2 px-4 py-4">
                <template v-if="editable">
                    <q-input v-model="draft" dark outlined dense class="photo-caption flex-1" input-class="text-white" maxlength="255" placeholder="Ajouter une légende…"
                        @keyup.enter="saveCaption" @blur="saveCaption" />
                    <q-btn unelevated no-caps :color="confirmDelete ? 'negative' : 'grey-9'" :text-color="confirmDelete ? 'white' : 'red-3'"
                        icon="mdi-delete-outline" :label="confirmDelete ? 'Confirmer' : 'Supprimer'" @click="remove" />
                </template>
                <p v-else class="m-0 flex-1 text-body2 text-slate-300">{{ current.caption }}</p>
            </div>
        </div>
    </q-dialog>
</template>
