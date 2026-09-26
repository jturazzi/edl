<script setup>
// Titre de carte : pastille d'icône + titre (+ précision facultative)
defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    size: { type: String, default: 'text-h6' },
})
</script>

<template>
    <div class="flex items-center gap-3">
        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary ring-1 ring-inset ring-blue-100">
            <q-icon :name="icon" size="22px" />
        </span>
        <span :class="size" class="font-semibold">{{ title }}</span>
        <span v-if="hint" class="text-caption text-grey-7">({{ hint }})</span>
        <slot />
    </div>
</template>
