<template>
<div v-if="loading" class="flex justify-center py-16" role="status" aria-live="polite">
    <q-spinner color="primary" size="40px" />
</div>

<q-banner v-else-if="loadError" rounded class="bg-red-1 text-negative max-w-xl">
    <template #avatar><q-icon name="mdi-wifi-off" color="negative" /></template>
    {{ loadError }}
</q-banner>

<div v-else>
    <q-banner v-if="photoError" dense rounded class="mb-4 bg-red-1 text-negative" role="alert">
        {{ photoError }}
        <template #action><q-btn flat dense no-caps label="Fermer" @click="photoError = ''" /></template>
    </q-banner>

    <!-- En-tête -->
    <q-card flat bordered class="mb-5">
        <q-card-section>
            <h1 class="text-h5 font-bold m-0 truncate">{{ edl.adresse }}</h1>
            <p class="text-body1 text-grey-7 mt-0 mb-2">{{ edl.ville }}</p>
            <div class="flex flex-wrap items-center gap-2 text-body2">
                <AppBadge mono>{{ edl.numero }}</AppBadge>
                <AppBadge :tone="edl.type === 'entrant' ? 'green' : 'amber'" dot>{{ edl.type_label }}</AppBadge>
                <span v-if="edl.locataire_full_name" class="inline-flex items-center gap-1.5"><q-icon name="mdi-account-outline" color="primary" size="20px" /> {{ edl.locataire_full_name }}</span>
                <span v-if="edl.agent_name" class="inline-flex items-center gap-1.5 text-grey-7"><q-icon name="mdi-wrench-outline" color="primary" size="20px" /> {{ edl.agent_name }}</span>
                <q-btn v-if="edl.entrant_id" outline dense no-caps color="primary" icon="mdi-compare-horizontal" label="Comparer avec l'entrant" class="px-3 md:ml-auto"
                    :to="{ name: 'comparison', params: { id: edl.id } }" />
                <span class="inline-flex items-center gap-1.5 text-grey-7" :class="edl.entrant_id ? '' : 'md:ml-auto'"><q-icon name="mdi-calendar-outline" color="primary" size="20px" /> {{ today }}</span>
            </div>
        </q-card-section>
    </q-card>

    <!-- Navigation mobile (< md) : sélecteur d'étape -->
    <div class="lt-md rounded-t-xl border border-slate-200 bg-white px-3 py-2 flex items-center gap-2">
        <q-btn unelevated no-caps color="blue-1" text-color="primary" :icon="stepIcon(visibleSteps[currentStep])" @click="sidebarOpen = !sidebarOpen"
            :aria-expanded="sidebarOpen" aria-controls="mobile-steps">
            <span class="truncate max-w-[160px] ml-2">{{ visibleSteps[currentStep]?.title }}</span>
            <span class="text-caption opacity-70 ml-2">{{ currentStep + 1 }}/{{ visibleSteps.length }}</span>
            <q-icon :name="sidebarOpen ? 'mdi-chevron-up' : 'mdi-chevron-down'" class="ml-1" />
        </q-btn>
        <span v-if="saveError" class="text-caption text-negative inline-flex items-center gap-1"><q-icon name="mdi-alert-circle-outline" /> Échec</span>
        <span v-else-if="saveOk" class="text-caption text-positive inline-flex items-center gap-1"><q-icon name="mdi-check" /> Sauvegardé</span>
        <q-btn class="ml-auto" flat round dense icon="mdi-content-save-outline" aria-label="Sauvegarder" @click="saveSurvey" />
    </div>

    <!-- Liste mobile des étapes -->
    <div v-show="sidebarOpen" id="mobile-steps" class="lt-md bg-white border-x border-b border-slate-200 px-2 py-2 max-h-72 overflow-y-auto">
        <q-list aria-label="Étapes du formulaire">
            <q-item v-for="(step, i) in visibleSteps" :key="i" clickable v-ripple :active="currentStep === i" active-class="bg-primary text-white"
                :aria-current="currentStep === i ? 'step' : null" class="rounded-lg" @click="goToStep(i); sidebarOpen = false">
                <q-item-section avatar><q-icon :name="stepIcon(step)" /></q-item-section>
                <q-item-section>{{ step.title }}</q-item-section>
                <q-item-section v-if="stepFilled[i]" side><q-icon name="mdi-check-circle" :color="currentStep === i ? 'white' : 'positive'" /></q-item-section>
            </q-item>
        </q-list>
        <q-btn class="w-full mt-2" flat no-caps color="grey-8" icon="mdi-tune-variant" label="Modifier les pièces" @click="openStepsDialog" />
        <q-btn class="w-full mt-1" unelevated no-caps color="positive" icon="mdi-draw-pen" label="Passer à la signature" @click="goToSignature" />
    </div>

    <!-- Layout sidebar (md+) + contenu -->
    <div class="flex rounded-b-xl md:rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">

        <!-- Sidebar desktop -->
        <aside class="gt-sm flex w-56 lg:w-60 shrink-0 border-r border-slate-200 sticky overflow-y-auto flex-col"
            style="top:0; max-height:100vh;" aria-label="Navigation des étapes">
            <p class="px-4 pt-4 pb-2 m-0 text-overline text-grey-6">Étapes</p>
            <q-list class="flex-1 px-2 pb-2">
                <q-item v-for="(step, i) in visibleSteps" :key="i" clickable v-ripple dense :active="currentStep === i" active-class="bg-primary text-white"
                    :aria-current="currentStep === i ? 'step' : null" class="rounded-lg" @click="goToStep(i)">
                    <q-item-section avatar style="min-width: 36px"><q-icon :name="stepIcon(step)" size="22px" /></q-item-section>
                    <q-item-section><span class="truncate">{{ step.title }}</span></q-item-section>
                    <q-item-section v-if="stepFilled[i]" side><q-icon name="mdi-check-circle" size="18px" :color="currentStep === i ? 'white' : 'positive'" /></q-item-section>
                </q-item>
            </q-list>
            <div class="p-3 border-t border-slate-200 shrink-0 space-y-2">
                <q-btn class="w-full" flat dense no-caps color="grey-8" icon="mdi-tune-variant" label="Modifier les pièces" @click="openStepsDialog" />
                <q-btn class="w-full" unelevated no-caps color="positive" icon="mdi-draw-pen" label="Signer" @click="goToSignature" />
            </div>
        </aside>

        <!-- Contenu principal -->
        <div class="flex-1 min-w-0 px-4 sm:px-6 py-5 bg-blue-50/60">

            <!-- Barre sauvegarde desktop -->
            <div class="gt-sm flex items-center gap-3 mb-5">
                <q-btn outline no-caps color="primary" icon="mdi-content-save-outline" label="Sauvegarder" aria-label="Sauvegarder le formulaire" @click="saveSurvey" />
                <span v-if="saveError" role="status" aria-live="polite" class="inline-flex items-center gap-1 text-body2 text-negative"><q-icon name="mdi-alert-circle-outline" /> Sauvegarde impossible, vérifiez la connexion</span>
                <span v-else-if="saveOk" role="status" aria-live="polite" class="inline-flex items-center gap-1 text-body2 text-positive"><q-icon name="mdi-check" /> Sauvegardé</span>
            </div>

            <!-- PANELS PAR ÉTAPE -->
            <div v-for="(step, i) in visibleSteps" :key="i" v-show="currentStep === i">

                <h2 class="flex items-center gap-3 text-h5 font-bold m-0 mb-4">
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-primary text-white"><q-icon :name="stepIcon(step)" size="26px" /></span>
                    {{ step.title }}
                </h2>

                <!-- COMPTEURS & CLÉS -->
                <template v-if="step.type === 'compteurs'">
                    <div class="space-y-5">
                        <q-card flat bordered>
                            <q-card-section><SectionTitle icon="mdi-gauge" title="Relevés de compteurs" /></q-card-section>
                            <q-card-section class="pt-0 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <q-input v-for="(label, key) in compteurFields" :key="key" outlined dense :label="label" type="number"
                                    v-model="formData[key]" step="0.001" min="0" @update:model-value="onFieldChange" />
                            </q-card-section>
                        </q-card>
                        <q-card flat bordered>
                            <q-card-section><SectionTitle icon="mdi-key-variant" title="Clés remises" /></q-card-section>
                            <q-card-section class="pt-0 grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <q-input v-for="(label, key) in clesFields" :key="key" outlined dense :label="label" type="number"
                                    v-model="formData[key]" min="0" @update:model-value="onFieldChange" />
                            </q-card-section>
                        </q-card>
                        <PhotoPicker :photos="generalPhotos(step)" :alt="step.title" :title="`Photos - ${step.title}`"
                            @gallery="openPhotoUpload(step.key)" @camera="openCameraCapture(step.key)"
                            @delete="deletePhoto($event, step.key)" @caption="(p, t) => captionPhoto(p, t, step.key)" />
                    </div>
                </template>

                <!-- PIÈCE (état + observations + photos) -->
                <template v-else-if="step.type === 'room'">
                    <div class="space-y-4">
                        <q-card v-for="element in step.elements" :key="element" flat bordered>
                            <q-card-section class="space-y-3">
                                <h3 class="text-subtitle1 font-bold m-0">{{ element }}</h3>
                                <ChoiceGroup :options="step.etat" :label="`État : ${element}`"
                                    :model-value="formData[`${step.key}_${slugify(element)}_etat`]"
                                    @update:model-value="setField(`${step.key}_${slugify(element)}_etat`, $event)" />
                                <q-input outlined bg-color="white" type="textarea" autogrow v-model="formData[`${step.key}_${slugify(element)}_obs`]" placeholder="Observations…" :aria-label="`Observations : ${element}`" @update:model-value="onFieldChange" />
                                <div class="flex flex-wrap items-center gap-2">
                                    <q-btn flat dense no-caps color="primary" icon="mdi-camera" :aria-label="`Prendre une photo : ${element}`" label="Photo" class="px-2" @click="openCameraCapture(step.key, elementKey(step, element))" />
                                    <q-btn flat dense no-caps color="grey-8" icon="mdi-image" :aria-label="`Ajouter une photo depuis la galerie : ${element}`" label="Galerie" class="px-2" @click="openPhotoUpload(step.key, elementKey(step, element))" />
                                    <span v-if="elementPhotos(step, element).length" class="text-caption text-grey-7">{{ elementPhotos(step, element).length }} photo(s)</span>
                                </div>
                                <PhotoGallery :photos="elementPhotos(step, element)" :alt="element" cols="grid-cols-4 sm:grid-cols-6"
                                    @delete="deletePhoto($event, step.key)" @caption="(p, t) => captionPhoto(p, t, step.key)" />
                            </q-card-section>
                        </q-card>
                        <PhotoPicker :photos="generalPhotos(step)" :alt="step.title" :title="`Photos - ${step.title}`"
                            @gallery="openPhotoUpload(step.key)" @camera="openCameraCapture(step.key)"
                            @delete="deletePhoto($event, step.key)" @caption="(p, t) => captionPhoto(p, t, step.key)" />
                    </div>
                </template>

                <!-- CHECKLIST (fonctionnement + observations) -->
                <template v-else-if="step.type === 'checklist'">
                    <div class="space-y-4">
                        <q-card v-for="item in step.items" :key="item" flat bordered>
                            <q-card-section class="space-y-3">
                                <h3 class="text-subtitle1 font-bold m-0">{{ item }}</h3>
                                <ChoiceGroup :options="step.options" :label="`Fonctionnement : ${item}`"
                                    :model-value="formData[`${step.key}_${slugify(item)}_fonctionnement`]"
                                    @update:model-value="setField(`${step.key}_${slugify(item)}_fonctionnement`, $event)" />
                                <q-input outlined bg-color="white" type="textarea" autogrow v-model="formData[`${step.key}_${slugify(item)}_obs`]" placeholder="Observations…" :aria-label="`Observations : ${item}`" @update:model-value="onFieldChange" />
                                <div class="flex flex-wrap items-center gap-2">
                                    <q-btn flat dense no-caps color="primary" icon="mdi-camera" :aria-label="`Prendre une photo : ${item}`" label="Photo" class="px-2" @click="openCameraCapture(step.key, elementKey(step, item))" />
                                    <q-btn flat dense no-caps color="grey-8" icon="mdi-image" :aria-label="`Ajouter une photo depuis la galerie : ${item}`" label="Galerie" class="px-2" @click="openPhotoUpload(step.key, elementKey(step, item))" />
                                    <span v-if="elementPhotos(step, item).length" class="text-caption text-grey-7">{{ elementPhotos(step, item).length }} photo(s)</span>
                                </div>
                                <PhotoGallery :photos="elementPhotos(step, item)" :alt="item" cols="grid-cols-4 sm:grid-cols-6"
                                    @delete="deletePhoto($event, step.key)" @caption="(p, t) => captionPhoto(p, t, step.key)" />
                            </q-card-section>
                        </q-card>
                        <PhotoPicker :photos="generalPhotos(step)" :alt="step.title" :title="`Photos - ${step.title}`"
                            @gallery="openPhotoUpload(step.key)" @camera="openCameraCapture(step.key)"
                            @delete="deletePhoto($event, step.key)" @caption="(p, t) => captionPhoto(p, t, step.key)" />
                    </div>
                </template>

                <!-- INVENTAIRE SIMPLE -->
                <template v-else-if="step.type === 'inventory'">
                    <q-markup-table flat bordered>
                        <thead>
                            <tr class="bg-blue-50 text-primary">
                                <th class="text-left">Article</th>
                                <th class="text-center" style="width: 6rem">Nb</th>
                                <th v-if="step.withDimension" class="text-center" style="width: 8rem">Dimension</th>
                                <th class="text-left">Observations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in step.items" :key="item">
                                <td class="text-left font-medium">{{ item }}</td>
                                <td><q-input outlined dense type="number" min="0" input-class="text-center" style="width: 5rem"
                                    v-model="formData[`${step.key}_${slugify(item)}_nb`]" @update:model-value="onFieldChange" /></td>
                                <td v-if="step.withDimension"><q-input outlined dense placeholder="ex: 140×190"
                                    v-model="formData[`${step.key}_${slugify(item)}_dim`]" @update:model-value="onFieldChange" /></td>
                                <td><q-input outlined dense placeholder="Observations…"
                                    v-model="formData[`${step.key}_${slugify(item)}_obs`]" @update:model-value="onFieldChange" /></td>
                            </tr>
                        </tbody>
                    </q-markup-table>
                </template>

                <!-- INVENTAIRE MULTI-SECTIONS -->
                <template v-else-if="step.type === 'inventory_multi'">
                    <div class="space-y-5">
                        <q-card v-for="section in step.sections" :key="section.groupKey" flat bordered>
                            <div class="px-4 py-3 bg-blue-50 text-primary text-subtitle1 font-bold">{{ section.title }}</div>
                            <q-markup-table flat>
                                <thead>
                                    <tr>
                                        <th class="text-left">Article</th>
                                        <th class="text-center" style="width: 6rem">Nb</th>
                                        <th class="text-left">Observations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in section.items" :key="item">
                                        <td class="text-left font-medium">{{ item }}</td>
                                        <td><q-input outlined dense type="number" min="0" input-class="text-center" style="width: 5rem"
                                            v-model="formData[`${section.groupKey}_${slugify(item)}_nb`]" @update:model-value="onFieldChange" /></td>
                                        <td><q-input outlined dense placeholder="Observations…"
                                            v-model="formData[`${section.groupKey}_${slugify(item)}_obs`]" @update:model-value="onFieldChange" /></td>
                                    </tr>
                                </tbody>
                            </q-markup-table>
                        </q-card>
                    </div>
                </template>

                <!-- SYNTHÈSE -->
                <template v-else-if="step.type === 'synthese'">
                    <div class="space-y-4">
                        <q-card v-for="field in step.fields" :key="field.key" flat bordered>
                            <q-card-section class="space-y-3">
                                <div>
                                    <h3 class="text-subtitle1 font-bold m-0">{{ field.label }}</h3>
                                    <p v-if="field.description" class="text-body2 text-grey-7 mt-1 mb-0">{{ field.description }}</p>
                                </div>
                                <ChoiceGroup :options="ouiNon" :label="field.label" :model-value="formData[field.key]"
                                    @update:model-value="setField(field.key, $event)" />
                            </q-card-section>
                        </q-card>
                        <q-card flat bordered>
                            <q-card-section><SectionTitle icon="mdi-text-box-edit-outline" title="Observations générales" size="text-subtitle1" /></q-card-section>
                            <q-card-section class="pt-0">
                                <q-input outlined bg-color="white" type="textarea" autogrow v-model="formData['synthese_obs']"
                                    placeholder="Remarques, commentaires de clôture…" @update:model-value="onFieldChange" />
                            </q-card-section>
                        </q-card>
                    </div>
                </template>

                <!-- Précédent / Suivant -->
                <q-separator class="mt-8 mb-5" />
                <div class="flex justify-between items-center">
                    <q-btn outline no-caps color="grey-8" icon="mdi-chevron-left" label="Précédent" :class="i === 0 ? 'invisible' : ''" @click="goToStep(i - 1)" />
                    <span class="text-body2 text-grey-7">Étape {{ i + 1 }} / {{ visibleSteps.length }}</span>
                    <q-btn v-if="i < visibleSteps.length - 1" unelevated no-caps color="primary" icon-right="mdi-chevron-right" label="Suivant" @click="goToStep(i + 1)" />
                    <q-btn v-else unelevated no-caps color="positive" icon="mdi-draw-pen" label="Passer à la signature" @click="goToSignature" />
                </div>

            </div>
        </div>
    </div>
    <!-- Conflit : l'EDL a été modifié depuis un autre appareil -->
    <q-dialog :model-value="conflict !== null" persistent>
        <q-card style="width: 30rem; max-width: 94vw">
            <q-card-section>
                <div class="flex items-center gap-2 text-h6"><q-icon name="mdi-sync-alert" color="warning" />Modifié depuis un autre appareil</div>
                <p class="mt-2 mb-0 text-body2 text-grey-8">
                    Cet état des lieux a été modifié ailleurs pendant que vous le remplissiez
                    <template v-if="conflict">({{ conflict.diffs }} réponse{{ conflict.diffs > 1 ? 's' : '' }} diffère{{ conflict.diffs > 1 ? 'nt' : '' }})</template>.
                    Choisissez la version à conserver.
                </p>
            </q-card-section>
            <q-card-section class="pt-0 space-y-2">
                <q-btn class="w-full" unelevated no-caps color="primary" icon="mdi-call-merge" label="Fusionner les deux (recommandé)" @click="mergeBoth" />
                <p class="m-0 text-caption text-grey-7">Garde l'autre version et y ajoute les champs que vous avez remplis ici.</p>
                <q-btn class="w-full" outline no-caps color="grey-8" icon="mdi-cellphone-check" label="Garder ma version" @click="keepMine" />
                <q-btn class="w-full" outline no-caps color="grey-8" icon="mdi-cloud-download-outline" label="Prendre la version de l'autre appareil" @click="takeServer" />
            </q-card-section>
        </q-card>
    </q-dialog>

    <!-- Choix des pièces à inspecter -->
    <q-dialog v-model="stepsDialog">
        <q-card style="width: 30rem; max-width: 94vw">
            <q-card-section>
                <div class="text-h6">Pièces à inspecter</div>
                <p class="mt-1 mb-0 text-body2 text-grey-7">Décochez les pièces qui n'existent pas dans ce logement. Les compteurs et la synthèse sont toujours présents.</p>
            </q-card-section>
            <q-card-section class="pt-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-2">
                    <q-checkbox v-for="step in selectableSteps" :key="step.key" v-model="stepsDraft" :val="step.key" color="primary" dense class="py-1.5">
                        <span class="inline-flex items-center gap-1.5 text-body2"><q-icon :name="iconFor(step.key)" size="18px" color="grey-7" />{{ step.title }}</span>
                    </q-checkbox>
                </div>
                <p v-if="hiddenWithData.length" class="mt-3 mb-0 text-caption text-amber-9">
                    <q-icon name="mdi-alert-outline" /> {{ hiddenWithData.join(', ') }} : des données ont déjà été saisies, elles resteront dans le document.
                </p>
            </q-card-section>
            <q-card-actions align="between">
                <q-btn flat no-caps color="grey-8" label="Tout cocher" @click="stepsDraft = allSteps.map((s) => s.key)" />
                <div>
                    <q-btn flat no-caps label="Annuler" v-close-popup />
                    <q-btn unelevated no-caps color="primary" label="Enregistrer" :loading="stepsSaving" @click="saveSteps" />
                </div>
            </q-card-actions>
        </q-card>
    </q-dialog>
</div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import * as Sentry from '@sentry/vue'
import AppBadge from '@/components/AppBadge.vue'
import PhotoPicker from '@/components/PhotoPicker.vue'
import PhotoGallery from '@/components/PhotoGallery.vue'
import SectionTitle from '@/components/SectionTitle.vue'
import ChoiceGroup from '@/components/ChoiceGroup.vue'
import { steps as allSteps, slugify } from '../data/steps.js'
import { stepIcon as iconFor } from '@/data/stepIcons.js'
import { shrinkImage } from '@/lib/image.js'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const edl = ref({})
const edlId = route.params.id // fixe : la route change déjà quand la sauvegarde de sortie part

// Étapes affichées : toutes, ou la sélection faite à la création (modèle de logement)
const visibleSteps = computed(() => (edl.value.steps ? allSteps.filter((s) => edl.value.steps.includes(s.key)) : allSteps))
const selectableSteps = allSteps.filter((s) => !['compteurs', 'synthese'].includes(s.key))
const currentStep = ref(0)
const formData = reactive({})
const photos = reactive({})
const saveOk = ref(false)
const sidebarOpen = ref(false)
let saveTimer = null

const today = new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })

const compteurFields = {
    compteur_eau: 'Eau (m³)',
    compteur_gaz: 'Gaz (m³)',
    compteur_electricite: 'Électricité (kWh)',
}
const clesFields = {
    cles_porte_allee: 'Porte allée',
    cles_porte_appart: 'Porte appartement',
    cles_verrou_haut: 'Verrou haut',
    cles_verrou_bas: 'Verrou bas',
    cles_local_commun: 'Local commun',
    cles_bal: 'Boîte aux lettres',
    cles_total: 'Total clés remises',
}

// Détecte si une étape a des données renseignées
const stepFilled = computed(() => {
    return visibleSteps.value.map((step) => {
        if (step.type === 'compteurs') {
            return Object.keys(compteurFields).concat(Object.keys(clesFields))
                .some(k => formData[k] !== undefined && formData[k] !== '' && formData[k] !== null)
        }
        if (step.type === 'room') {
            return step.elements.some(el => {
                const key = `${step.key}_${slugify(el)}_etat`
                return formData[key] !== undefined && formData[key] !== '' && formData[key] !== null
            })
        }
        if (step.type === 'checklist') {
            return step.items.some(item => {
                const key = `${step.key}_${slugify(item)}_fonctionnement`
                return formData[key] !== undefined && formData[key] !== '' && formData[key] !== null
            })
        }
        if (step.type === 'inventory') {
            return step.items.some(item => {
                const key = `${step.key}_${slugify(item)}_nb`
                return formData[key] !== undefined && formData[key] !== '' && formData[key] !== null
            })
        }
        if (step.type === 'inventory_multi') {
            return step.sections.some(sec =>
                sec.items.some(item => {
                    const key = `${sec.groupKey}_${slugify(item)}_nb`
                    return formData[key] !== undefined && formData[key] !== '' && formData[key] !== null
                })
            )
        }
        if (step.type === 'synthese') {
            return step.fields.some(f => formData[f.key] !== undefined && formData[f.key] !== '')
        }
        return false
    })
})

async function goToStep(n) {
    if (n < 0 || n >= visibleSteps.value.length) return
    // Sauvegarder immédiatement avant de changer d'étape
    await saveSurvey()
    currentStep.value = n
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const stepIcon = (step) => iconFor(step?.key)

const ouiNon = [
    { value: 'oui', label: 'OUI' },
    { value: 'non', label: 'NON' },
]

function setField(key, value) {
    formData[key] = value
    onFieldChange()
}

function onFieldChange() {
    scheduleSave()
}

let dirty = false
function scheduleSave() {
    clearTimeout(saveTimer)
    if (conflict.value) return
    dirty = true
    saveTimer = setTimeout(saveSurvey, 3000)
}

async function goToSignature() {
    await saveSurvey()
    if (conflict.value) return // une version plus récente existe sur le serveur : à trancher d'abord
    router.push({ name: 'signature', params: { id: route.params.id } })
}

const saveError = ref(false)
const photoError = ref('')
const loadError = ref('')

// Révision des réponses connue du serveur : sert à détecter une modification faite depuis un autre appareil
const serverRev = ref(null)
const conflict = ref(null) // { data, rev, diffs } quand le serveur a une version plus récente que celle de cet appareil

const isBlank = (v) => v === undefined || v === null || v === '' || (Array.isArray(v) && v.length === 0)
const sameValue = (a, b) => (isBlank(a) && isBlank(b)) || JSON.stringify(a) === JSON.stringify(b)
const countDiffs = (a, b) => [...new Set([...Object.keys(a), ...Object.keys(b)])].filter((k) => !sameValue(a[k], b[k])).length

function replaceFormData(data) {
    for (const key of Object.keys(formData)) delete formData[key]
    Object.assign(formData, data)
}

function openConflict(serverData, rev) {
    const mine = JSON.parse(JSON.stringify(formData))
    const theirs = serverData ?? {}
    conflict.value = { data: theirs, rev, diffs: countDiffs(mine, theirs) }
}

// Les sauvegardes sont exécutées une par une : deux envois simultanés se croiseraient avec la révision du serveur
let saveQueue = Promise.resolve()
function saveSurvey(options = {}) {
    clearTimeout(saveTimer)
    const run = saveQueue.then(() => doSaveSurvey(options))
    saveQueue = run.catch(() => {})
    return run
}

async function doSaveSurvey({ force = false } = {}) {
    clearTimeout(saveTimer)
    if (conflict.value && !force) return
    dirty = false

    const survey = JSON.parse(JSON.stringify(formData))
    const payload = { survey_data: JSON.stringify(survey) }
    if (force) {
        payload.base_rev = conflict.value.rev
        payload.force = true
    } else if (serverRev.value !== null) {
        payload.base_rev = serverRev.value
    }

    try {
        const { data } = await axios.post(`/api/edls/${edlId}/survey`, payload)
        serverRev.value = data.survey_rev
        conflict.value = null
        saveError.value = false
        saveOk.value = true
        setTimeout(() => { saveOk.value = false }, 3000)
    } catch (e) {
        if (e.response?.status === 409) {
            const server = e.response.data
            if (countDiffs(survey, server.survey_data ?? {}) === 0) {
                // Même contenu des deux côtés (ex. envoi en double) : rien à trancher
                serverRev.value = server.survey_rev
            } else {
                openConflict(server.survey_data, server.survey_rev)
            }
            return
        }
        saveError.value = true
        if (e.response) {
            console.warn('Sauvegarde échouée', e.response.status, e.response.data)
            Sentry.captureException(e)
        }
    }
}

/** Conflit : garder les réponses de cet appareil (elles remplacent celles du serveur). */
const keepMine = () => saveSurvey({ force: true })

/** Conflit : abandonner les réponses de cet appareil au profit de celles du serveur. */
async function takeServer() {
    replaceFormData(conflict.value.data)
    serverRev.value = conflict.value.rev
    conflict.value = null
}

/** Conflit : réponses du serveur complétées par celles saisies ici (les champs remplis ici priment). */
async function mergeBoth() {
    const mine = JSON.parse(JSON.stringify(formData))
    const merged = { ...conflict.value.data }
    for (const [key, value] of Object.entries(mine)) {
        if (!isBlank(value)) merged[key] = value
    }
    replaceFormData(merged)
    await saveSurvey({ force: true })
}

// ── Choix des pièces ──────────────────────────────────
const stepsDialog = ref(false)
const stepsDraft = ref([])
const stepsSaving = ref(false)

function openStepsDialog() {
    stepsDraft.value = visibleSteps.value.map((s) => s.key)
    stepsDialog.value = true
}

// Pièces décochées alors que des données y ont été saisies
const hiddenWithData = computed(() =>
    selectableSteps
        .filter((s) => !stepsDraft.value.includes(s.key) && visibleSteps.value.some((v) => v.key === s.key))
        .filter((s) => stepHasData(s))
        .map((s) => s.title)
)

function stepHasData(step) {
    const prefix = step.type === 'inventory_multi' ? step.sections.map((sec) => `${sec.groupKey}_`) : [`${step.key}_`]
    return Object.entries(formData).some(([k, v]) => prefix.some((p) => k.startsWith(p)) && v !== '' && v !== null && v !== undefined)
}

async function saveSteps() {
    stepsSaving.value = true
    try {
        const { data } = await axios.patch(`/api/edls/${route.params.id}/steps`, { steps: stepsDraft.value })
        edl.value.steps = data.steps
        if (currentStep.value >= visibleSteps.value.length) currentStep.value = 0
        stepsDialog.value = false
    } catch (e) {
        console.error('Mise à jour des pièces échouée', e)
    } finally {
        stepsSaving.value = false
    }
}

// ── Photos ────────────────────────────────────────────
// Chaque photo a une pièce (`room` = clé de l'étape) et une `question_key` :
// la clé de l'étape pour une photo générale, `{étape}_{élément}` pour une photo d'élément.
const elementKey = (step, item) => `${step.key}_${slugify(item)}`
const generalPhotos = (step) => (photos[step.key] || []).filter((p) => !p.question_key || p.question_key === step.key)
const elementPhotos = (step, item) => (photos[step.key] || []).filter((p) => p.question_key === elementKey(step, item))

function pickFile(room, questionKey, { camera = false, multiple = false } = {}) {
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    if (camera) input.capture = 'environment' // caméra arrière sur mobile
    if (multiple) input.multiple = true
    input.onchange = (e) => handlePhotoUpload(e, room, questionKey)
    input.click()
}

function openPhotoUpload(room, questionKey = room) {
    pickFile(room, questionKey, { multiple: true })
}

function openCameraCapture(room, questionKey = room) {
    pickFile(room, questionKey, { camera: true })
}

async function handlePhotoUpload(event, room, questionKey = room) {
    const files = event.target.files
    if (!files || files.length === 0) return

    for (const original of Array.from(files)) {
        await uploadPhoto(await shrinkImage(original), room, questionKey)
    }
}

/** Envoi d'une photo au serveur. */
async function uploadPhoto(file, room, questionKey) {
    const fd = new FormData()
    fd.append('photo', file)
    fd.append('question_key', questionKey)
    fd.append('room', room)

    try {
        const { data } = await axios.post(`/api/edls/${route.params.id}/photos`, fd)
        if (data.success) {
            if (!photos[room]) photos[room] = []
            photos[room].push({ id: data.photo_id, url: data.url, question_key: data.question_key, caption: data.caption })
        }
    } catch (e) {
        console.error('Upload photo échoué', e)
        photoError.value = e.response?.data?.message || "La photo n'a pas pu être envoyée. Vérifiez la connexion et réessayez."
        if (e.response) Sentry.captureException(e)
    }
}

async function deletePhoto(photo, room) {
    try {
        await axios.delete(`/api/photos/${photo.id}`)
        photos[room] = (photos[room] || []).filter((p) => p.id !== photo.id)
    } catch (e) {
        console.error('Suppression photo échouée', e)
    }
}

async function captionPhoto(photo, caption, room) {
    const target = (photos[room] || []).find((p) => p.id === photo.id)

    try {
        const { data } = await axios.patch(`/api/photos/${photo.id}`, { caption })
        if (target) target.caption = data.caption
    } catch (e) {
        console.error('Mise à jour de la légende échouée', e)
    }
}

async function loadExistingPhotos() {
    for (const key of Object.keys(photos)) delete photos[key]

    try {
        const { data } = await axios.get(`/api/edls/${route.params.id}/photos`)
        data.forEach(p => {
            if (!photos[p.room]) photos[p.room] = []
            photos[p.room].push({ id: p.id, url: p.url, question_key: p.question_key, caption: p.caption })
        })
    } catch (e) {}

}

// ── Init ──────────────────────────────────────────────
onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/edls/${route.params.id}`)
        edl.value = data

        // Un EDL signé n'est plus modifiable : on affiche son récapitulatif
        if (data.status === 'complete') {
            router.replace({ name: 'confirmation', params: { id: edlId } })
            return
        }

        // Un EDL en cours n'est modifiable que par son auteur (ou un administrateur)
        if (!data.can_edit) {
            loadError.value = `Cet état des lieux est en cours de saisie par ${data.agent_name || 'un autre technicien'} : vous pouvez le consulter une fois validé.`
            return
        }

        serverRev.value = data.survey_rev ?? 0

        // Restaurer formData depuis survey_data
        if (data.survey_data) {
            Object.assign(formData, data.survey_data)
        }

        await loadExistingPhotos()
    } catch (e) {
        console.error('Erreur chargement EDL', e)
        loadError.value = "Impossible de charger cet état des lieux. Vérifiez la connexion et rechargez la page."
    } finally {
        loading.value = false
    }
})

onBeforeUnmount(() => {
    clearTimeout(saveTimer)
    if (dirty && !conflict.value) saveSurvey() // saisie des dernières secondes : envoyée avant de quitter la page
})
</script>
