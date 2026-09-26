<script setup>
import SectionTitle from './SectionTitle.vue'
import PhotoGallery from './PhotoGallery.vue'

defineProps({
    photos: { type: Array, default: () => [] },
    alt: { type: String, default: '' },
    title: { type: String, default: 'Photos' },
})
defineEmits(['gallery', 'camera', 'delete', 'caption'])
</script>

<template>
    <q-card flat>
        <q-card-section>
            <SectionTitle icon="mdi-image-multiple" :title="title" size="text-subtitle1" />
        </q-card-section>
        <q-card-section class="pt-0 space-y-3">
            <div class="flex gap-2">
                <q-btn class="flex-1" outline color="grey-8" icon="mdi-image" label="Galerie" no-caps @click="$emit('gallery')" />
                <q-btn class="flex-1" outline color="primary" icon="mdi-camera" label="Prendre une photo" no-caps @click="$emit('camera')" />
            </div>
            <PhotoGallery :photos="photos" :alt="alt" @delete="$emit('delete', $event)" @caption="(p, t) => $emit('caption', p, t)" />
        </q-card-section>
    </q-card>
</template>
