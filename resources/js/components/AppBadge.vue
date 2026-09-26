<script setup>
import { computed } from 'vue'

// Badge « SaaS » : fond doux, texte contrasté, pastille de couleur ou icône
const props = defineProps({
    tone: { type: String, default: 'slate' }, // green | amber | blue | red | slate
    icon: { type: String, default: '' },
    dot: { type: Boolean, default: false },
    mono: { type: Boolean, default: false },
})

const TONES = {
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    amber: 'bg-amber-50 text-amber-800 ring-amber-200',
    blue:  'bg-blue-50 text-blue-700 ring-blue-200',
    red:   'bg-red-50 text-red-700 ring-red-200',
    slate: 'bg-slate-100 text-slate-700 ring-slate-200',
}
const DOTS = { green: 'bg-emerald-500', amber: 'bg-amber-500', blue: 'bg-blue-500', red: 'bg-red-500', slate: 'bg-slate-400' }
const cls = computed(() => TONES[props.tone] || TONES.slate)
const dotCls = computed(() => DOTS[props.tone] || DOTS.slate)
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap"
        :class="[cls, mono ? 'font-mono' : '']">
        <span v-if="dot" class="size-1.5 rounded-full" :class="dotCls"></span>
        <q-icon v-else-if="icon" :name="icon" size="14px" />
        <slot />
    </span>
</template>
