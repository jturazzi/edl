<script setup>
// Actions d'une ligne / carte de l'historique
const props = defineProps({
    edl: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: true }, // auteur de l'EDL ou administrateur
})
defineEmits(['sortant', 'delete', 'duplicate', 'archive', 'unarchive'])

// Suppression et archivage : administrateurs uniquement
const canDelete = () => props.isAdmin
</script>

<template>
    <div class="flex items-center justify-end gap-1">
        <template v-if="edl.status === 'en_cours' && !canEdit">
            <span class="whitespace-nowrap text-caption text-grey-7">En cours · {{ edl.agent_name }}</span>
        </template>
        <template v-else-if="edl.status === 'en_cours'">
            <q-btn unelevated dense no-caps color="primary" icon-right="mdi-arrow-right" label="Reprendre" class="px-3 whitespace-nowrap no-wrap"
                :to="{ name: 'survey', params: { id: edl.id } }" />
        </template>
        <template v-else>
            <q-btn flat round dense color="grey-8" icon="mdi-eye-outline" aria-label="Voir l'EDL"
                :to="{ name: 'confirmation', params: { id: edl.id } }"><q-tooltip>Voir</q-tooltip></q-btn>
            <q-btn v-if="edl.type === 'sortant' && edl.entrant_id" flat round dense color="primary" icon="mdi-compare-horizontal" aria-label="Comparer avec l'entrant"
                :to="{ name: 'comparison', params: { id: edl.id } }"><q-tooltip>Comparer avec l'entrant</q-tooltip></q-btn>
            <q-btn v-if="edl.type === 'entrant'" flat round dense color="amber-9" icon="mdi-logout" aria-label="Créer le sortant"
                @click="$emit('sortant', edl)"><q-tooltip>Créer l'état des lieux sortant</q-tooltip></q-btn>
            <q-btn flat round dense color="grey-8" icon="mdi-download-outline" :href="`/edl/${edl.id}/pdf`" target="_blank"
                aria-label="Télécharger le PDF"><q-tooltip>Télécharger le PDF</q-tooltip></q-btn>
        </template>
        <q-btn flat round dense color="grey-7" icon="mdi-dots-vertical" aria-label="Autres actions">
            <q-menu auto-close anchor="bottom right" self="top right">
                <q-list dense style="min-width: 11rem">
                    <q-item clickable @click="$emit('duplicate', edl)">
                        <q-item-section avatar class="min-w-0"><q-icon name="mdi-content-copy" size="20px" /></q-item-section>
                        <q-item-section>Dupliquer</q-item-section>
                    </q-item>
                    <template v-if="isAdmin">
                        <q-item v-if="edl.archived_at" clickable @click="$emit('unarchive', edl)">
                            <q-item-section avatar class="min-w-0"><q-icon name="mdi-archive-arrow-up-outline" size="20px" /></q-item-section>
                            <q-item-section>Désarchiver</q-item-section>
                        </q-item>
                        <q-item v-else clickable @click="$emit('archive', edl)">
                            <q-item-section avatar class="min-w-0"><q-icon name="mdi-archive-outline" size="20px" /></q-item-section>
                            <q-item-section>Archiver</q-item-section>
                        </q-item>
                    </template>
                    <q-item v-if="canDelete()" clickable class="text-negative" @click="$emit('delete', edl)">
                        <q-item-section avatar class="min-w-0"><q-icon name="mdi-delete-outline" size="20px" /></q-item-section>
                        <q-item-section>Supprimer</q-item-section>
                    </q-item>
                </q-list>
            </q-menu>
        </q-btn>
    </div>
</template>
