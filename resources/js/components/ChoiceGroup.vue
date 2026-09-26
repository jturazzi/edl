<script setup>
import { computed } from 'vue'

// Groupe de choix exclusifs affichés comme des boutons colorés (état, fonctionnement, oui/non…)
const props = defineProps({
    modelValue: { type: [String, Number], default: undefined },
    options: { type: Array, required: true }, // [{ value, label }]
    label: { type: String, default: '' }, // nom accessible du groupe (élément concerné)
})
const emit = defineEmits(['update:modelValue'])

// Icône et couleur (sélectionné) associées à la valeur
const TONES = {
    bon:     { icon: 'mdi-check-circle',   color: 'positive' },
    oui:     { icon: 'mdi-check-circle',   color: 'positive' },
    usure:   { icon: 'mdi-alert',          color: 'warning' },
    mauvais: { icon: 'mdi-close-circle',   color: 'negative' },
    non:     { icon: 'mdi-close-circle',   color: 'negative' },
}

const items = computed(() => props.options.map((o) => {
    const tone = TONES[o.value] || { icon: undefined, color: 'primary' }
    return { label: o.label, value: o.value, icon: tone.icon, toggleColor: tone.color, toggleTextColor: 'white' }
}))
</script>

<template>
    <div role="group" :aria-label="label || undefined">
        <q-btn-toggle :model-value="modelValue" :options="items" spread no-caps unelevated
            color="white" text-color="grey-9" class="border border-slate-300 rounded-borders"
            @update:model-value="emit('update:modelValue', $event)" />
    </div>
</template>
