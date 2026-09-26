<script setup>
// En-tête de page : titre, description et actions à droite
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
})
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-h5 m-0">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 mb-0 text-body2 text-grey-7">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.default" class="flex items-center gap-2"><slot /></div>
    </div>
</template>
