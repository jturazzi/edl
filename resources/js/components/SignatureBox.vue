<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import SignaturePad from 'signature_pad'

// Zone de signature au doigt / stylet. Expose isEmpty(), toDataURL() et clear().
const props = defineProps({
    disabled: { type: Boolean, default: false },
    label: { type: String, default: 'Signature' }, // qui signe (nom accessible)
})
const emit = defineEmits(['change'])

const wrapper = ref(null)
const signed = ref(false)
const uid = `sig-${Math.random().toString(36).slice(2, 8)}`
const statusText = computed(() => (props.disabled ? 'Signature non requise' : signed.value ? 'Signature saisie' : 'Zone de signature vide'))
const canvas = ref(null)
let pad = null
let observer = null

function resize() {
    if (!wrapper.value || !canvas.value) return
    const saved = pad ? pad.toData() : []
    const ratio = Math.max(window.devicePixelRatio || 1, 1)
    const rect = wrapper.value.getBoundingClientRect()
    canvas.value.width = rect.width * ratio
    canvas.value.height = rect.height * ratio
    canvas.value.getContext('2d').scale(ratio, ratio)
    if (pad) {
        pad.backgroundColor = '#ffffff'
        pad.clear()
        if (saved.length) pad.fromData(saved)
    }
    signed.value = pad ? !pad.isEmpty() : false
    emit('change', signed.value)
}

onMounted(async () => {
    await nextTick()
    pad = new SignaturePad(canvas.value, { backgroundColor: '#ffffff', penColor: '#0f172a', minWidth: 2, maxWidth: 4 })
    resize()
    observer = new ResizeObserver(resize)
    observer.observe(wrapper.value)
    pad.addEventListener('endStroke', () => { signed.value = !pad.isEmpty(); emit('change', signed.value) })
})

onBeforeUnmount(() => observer?.disconnect())

function clear() {
    pad?.clear()
    signed.value = false
    emit('change', false)
}

defineExpose({
    clear,
    isEmpty: () => !pad || pad.isEmpty(),
    toDataURL: () => pad.toDataURL('image/png'),
})
</script>

<template>
    <div ref="wrapper" class="relative w-full" :class="{ 'opacity-40 pointer-events-none': disabled }" style="height: 220px;">
        <canvas ref="canvas" class="absolute inset-0 w-full h-full signature-canvas" role="img"
            :aria-label="`Zone de signature : ${label}. À dessiner au doigt, au stylet ou à la souris.`" :aria-describedby="`${uid}-status`"></canvas>
        <span :id="`${uid}-status`" class="sr-only" role="status" aria-live="polite">{{ statusText }}</span>
    </div>
</template>
