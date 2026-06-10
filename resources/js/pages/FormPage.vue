<template>
<div v-if="loading" class="text-center py-16 text-gray-400" role="status" aria-live="polite">
    <p class="text-lg">Chargement…</p>
</div>

<div v-else>
    <!-- En-tête compact -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 sm:px-6 py-4 mb-5">
        <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white leading-tight truncate">{{ edl.adresse }}</h1>
        <p class="text-base sm:text-lg font-semibold text-gray-500 dark:text-gray-400 mt-0.5">{{ edl.ville }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold"
                :class="edl.type === 'entrant' ? 'badge-entrant' : 'badge-sortant'">
                {{ edl.type_label }}
            </span>
            <span v-if="edl.locataire_full_name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                👤 {{ edl.locataire_full_name }}
            </span>
            <span v-if="edl.agent_name" class="text-sm text-gray-500 dark:text-gray-400">
                🔧 {{ edl.agent_name }}
            </span>
            <span class="text-sm text-gray-400 dark:text-gray-500 ml-auto">📅 {{ today }}</span>
        </div>
    </div>

    <!-- Navigation mobile (< md) : sélecteur d'étape -->
    <div class="md:hidden bg-white dark:bg-gray-800 rounded-t-xl border border-gray-200 dark:border-gray-700 shadow-sm px-3 py-2 flex items-center gap-2">
        <button @click="sidebarOpen = !sidebarOpen"
            class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300 font-semibold text-sm px-3 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 min-h-[44px]"
            :aria-expanded="sidebarOpen" aria-controls="mobile-steps">
            <span class="text-lg">{{ steps[currentStep]?.icon }}</span>
            <span class="truncate max-w-[160px]">{{ steps[currentStep]?.title }}</span>
            <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">{{ currentStep + 1 }}/{{ steps.length }}</span>
            <span class="text-gray-400">{{ sidebarOpen ? '▲' : '▼' }}</span>
        </button>
        <span v-if="saveOk" class="text-green-600 font-semibold text-xs flex items-center gap-1">✓ Sauvegardé</span>
        <button @click="saveSurvey" class="ml-auto text-sm text-gray-500 hover:text-indigo-600 px-2 py-2 min-h-[44px]" aria-label="Sauvegarder">
            💾
        </button>
    </div>

    <!-- Drawer mobile des étapes -->
    <div v-show="sidebarOpen" id="mobile-steps"
        class="md:hidden bg-white dark:bg-gray-800 border-x border-b border-gray-200 dark:border-gray-700 shadow-lg px-2 py-2 max-h-72 overflow-y-auto z-40">
        <nav class="space-y-1" role="navigation" aria-label="Étapes du formulaire">
            <button v-for="(step, i) in steps" :key="i"
                @click="goToStep(i); sidebarOpen = false"
                class="w-full text-left flex items-center gap-2 px-3 py-2.5 rounded-xl font-medium text-sm transition-all"
                :class="currentStep === i ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-300'"
                :aria-current="currentStep === i ? 'step' : null">
                <span class="text-lg shrink-0" aria-hidden="true">{{ step.icon }}</span>
                <span class="truncate">{{ step.title }}</span>
                <span v-if="stepFilled[i]" class="ml-auto shrink-0 text-green-500 text-sm" aria-label="Étape remplie">✓</span>
            </button>
        </nav>
        <button @click="goToSignature"
            class="mt-2 w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm">
            ✍️ Passer à la signature
        </button>
    </div>

    <!-- Layout sidebar (md+) + contenu -->
    <div class="flex rounded-b-xl md:rounded-xl border border-gray-200 shadow-sm overflow-hidden md:mt-0">

        <!-- Sidebar desktop -->
        <aside class="hidden md:flex w-56 lg:w-60 shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 sticky overflow-y-auto flex-col"
            style="top:56px; max-height:calc(100vh - 56px);" aria-label="Navigation des étapes">
            <!-- Titre sidebar -->
            <div class="px-4 pt-4 pb-2">
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-400 dark:text-indigo-500">Étapes</p>
            </div>
            <nav class="flex-1 px-2 pb-2 space-y-0.5" role="navigation">
                <button v-for="(step, i) in steps" :key="i"
                    @click="goToStep(i)"
                    class="w-full text-left flex items-center gap-2 px-3 py-2.5 rounded-xl font-medium text-sm transition-all"
                    :class="currentStep === i ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-300'"
                    :aria-current="currentStep === i ? 'step' : null">
                    <span class="text-base shrink-0" aria-hidden="true">{{ step.icon }}</span>
                    <span class="truncate leading-tight">{{ step.title }}</span>
                    <span v-if="stepFilled[i]" class="ml-auto shrink-0 text-green-500 text-xs font-bold" aria-label="Étape remplie">✓</span>
                </button>
            </nav>
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0">
                <button @click="goToSignature"
                    class="flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm min-h-[44px] shadow-sm">
                    ✍️ Signer
                </button>
            </div>
        </aside>

        <!-- Contenu principal -->
        <div class="flex-1 min-w-0 px-4 sm:px-6 py-5 bg-slate-50/60 dark:bg-gray-900/40">

            <!-- Barre sauvegarde desktop -->
            <div class="hidden md:flex items-center gap-3 mb-5">
                <button @click="saveSurvey"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2.5 rounded-xl text-sm shadow-sm transition"
                    aria-label="Sauvegarder le formulaire">
                    💾 Sauvegarder
                </button>
                <span v-if="saveOk" role="status" aria-live="polite" class="flex items-center gap-1 text-green-700 font-semibold text-sm">✓ Sauvegardé !</span>
            </div>

            <!-- PANELS PAR ÉTAPE -->
            <div v-for="(step, i) in steps" :key="i" v-show="currentStep === i">

                <h2 class="text-xl sm:text-2xl font-extrabold text-gray-800 dark:text-white mb-4">{{ step.icon }} {{ step.title }}</h2>

                <!-- TYPE : COMPTEURS -->
                <template v-if="step.type === 'compteurs'">
                    <div class="space-y-5">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-4">📏 Relevés de compteurs</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div v-for="(label, key) in compteurFields" :key="key">
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ label }}</label>
                                    <input type="number" v-model="formData[key]" step="0.001" min="0"
                                        @input="onFieldChange"
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl px-4 py-3 text-base focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400">
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-4">🔑 Clés remises</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div v-for="(label, key) in clesFields" :key="key">
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ label }}</label>
                                    <input type="number" v-model="formData[key]" min="0"
                                        @input="onFieldChange"
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl px-4 py-3 text-base focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400">
                                </div>
                            </div>
                        </div>
                        <!-- Photos Compteurs & Clés -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                            <h3 class="text-base font-bold text-gray-700 dark:text-gray-200 mb-3">Photos</h3>
                            <div class="flex gap-2">
                                <button type="button" @click="openPhotoUpload(step.key)"
                                    class="flex-1 flex items-center justify-center gap-2 bg-indigo-50 hover:bg-indigo-100
                                           text-indigo-700 font-semibold rounded-xl border border-indigo-200
                                           py-3 px-4 text-sm transition min-h-[48px]">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Galerie</span>
                                </button>
                                <button type="button" @click="openCameraCapture(step.key)"
                                    class="flex-1 flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100
                                           text-slate-700 font-semibold rounded-xl border border-slate-200
                                           py-3 px-4 text-sm transition min-h-[48px]">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>Prendre une photo</span>
                                </button>
                            </div>
                            <div v-if="(photos[step.key] || []).length" class="grid grid-cols-3 gap-2 mt-3">
                                <div v-for="photo in (photos[step.key] || [])" :key="photo.id" class="relative group">
                                    <img :src="photo.url" :alt="step.key"
                                        class="rounded-xl object-cover w-full border border-gray-200 dark:border-gray-700 shadow-sm" style="height:90px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- TYPE : PIÈCE (état + observations + photos) -->
                <template v-else-if="step.type === 'room'">
                    <div class="space-y-4">
                        <div v-for="element in step.elements" :key="element"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3">{{ element }}</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                                <label v-for="opt in step.etat" :key="opt.value" class="cursor-pointer">
                                    <input type="radio"
                                        :name="`${step.key}_${slugify(element)}_etat`"
                                        :value="opt.value"
                                        v-model="formData[`${step.key}_${slugify(element)}_etat`]"
                                        @change="onFieldChange"
                                        class="sr-only">
                                    <div class="etat-card rounded-xl border-2 border-gray-200 px-2 py-3 text-center text-sm font-semibold text-gray-500 transition-all select-none">
                                        {{ opt.label }}
                                    </div>
                                </label>
                            </div>
                            <textarea v-model="formData[`${step.key}_${slugify(element)}_obs`]"
                                @input="onFieldChange"
                                rows="2" placeholder="Observations…"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500 rounded-xl px-4 py-3 text-base focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 resize-none">
                            </textarea>
                        </div>

                        <!-- Photos de la pièce -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                            <h3 class="text-base font-bold text-gray-700 dark:text-gray-200 mb-3">Photos</h3>
                            <div class="flex gap-2">
                                <!-- Galerie / fichier -->
                                <button type="button" @click="openPhotoUpload(step.key)"
                                    class="flex-1 flex items-center justify-center gap-2 bg-indigo-50 hover:bg-indigo-100
                                           text-indigo-700 font-semibold rounded-xl border border-indigo-200
                                           py-3 px-4 text-sm transition min-h-[48px]">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Galerie</span>
                                </button>
                                <!-- Appareil photo -->
                                <button type="button" @click="openCameraCapture(step.key)"
                                    class="flex-1 flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100
                                           text-slate-700 font-semibold rounded-xl border border-slate-200
                                           py-3 px-4 text-sm transition min-h-[48px]">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>Prendre une photo</span>
                                </button>
                            </div>
                            <!-- Prévisualisation -->
                            <div v-if="(photos[step.key] || []).length" class="grid grid-cols-3 gap-2 mt-3">
                                <div v-for="photo in (photos[step.key] || [])" :key="photo.id" class="relative group">
                                    <img :src="photo.url" :alt="step.key"
                                        class="rounded-xl object-cover w-full border border-gray-200 dark:border-gray-700 shadow-sm" style="height:90px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- TYPE : INVENTAIRE SIMPLE -->
                <template v-else-if="step.type === 'inventory'">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-indigo-50 dark:bg-indigo-900/30 border-b border-indigo-100 dark:border-indigo-800">
                                    <th class="text-left px-4 py-3 text-sm font-bold text-indigo-700 dark:text-indigo-300">Article</th>
                                    <th class="px-3 py-3 text-sm font-bold text-indigo-700 dark:text-indigo-300 text-center w-20">Nb</th>
                                    <th v-if="step.withDimension" class="px-3 py-3 text-sm font-bold text-indigo-700 dark:text-indigo-300 text-center w-28">Dimension</th>
                                    <th class="px-4 py-3 text-sm font-bold text-indigo-700 dark:text-indigo-300 text-left">Observations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in step.items" :key="item"
                                    class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3.5 text-base font-medium text-gray-800 dark:text-gray-200">{{ item }}</td>
                                    <td class="px-3 py-3.5 text-center">
                                        <input type="number" min="0"
                                            v-model="formData[`${step.key}_${slugify(item)}_nb`]"
                                            @input="onFieldChange"
                                            class="w-16 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-2 py-2 text-base text-center focus:ring-2 focus:ring-indigo-300">
                                    </td>
                                    <td v-if="step.withDimension" class="px-3 py-3.5">
                                        <input type="text" placeholder="ex: 140×190"
                                            v-model="formData[`${step.key}_${slugify(item)}_dim`]"
                                            @input="onFieldChange"
                                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-indigo-300">
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <input type="text" placeholder="Observations…"
                                            v-model="formData[`${step.key}_${slugify(item)}_obs`]"
                                            @input="onFieldChange"
                                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 text-base focus:ring-2 focus:ring-indigo-300">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <!-- TYPE : INVENTAIRE MULTI-SECTIONS -->
                <template v-else-if="step.type === 'inventory_multi'">
                    <div class="space-y-5">
                        <div v-for="section in step.sections" :key="section.groupKey"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="bg-indigo-50 dark:bg-indigo-900/30 border-b border-indigo-100 dark:border-indigo-800 px-5 py-3">
                                <h3 class="text-base font-bold text-indigo-700 dark:text-indigo-300">{{ section.title }}</h3>
                            </div>
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                        <th class="text-left px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-400">Article</th>
                                        <th class="px-3 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-400 text-center w-20">Nb</th>
                                        <th class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-400 text-left">Observations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in section.items" :key="item"
                                        class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 text-base font-medium text-gray-800 dark:text-gray-200">{{ item }}</td>
                                        <td class="px-3 py-3 text-center">
                                            <input type="number" min="0"
                                                v-model="formData[`${section.groupKey}_${slugify(item)}_nb`]"
                                                @input="onFieldChange"
                                                class="w-16 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-2 py-2 text-base text-center focus:ring-2 focus:ring-indigo-300">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" placeholder="Observations…"
                                                v-model="formData[`${section.groupKey}_${slugify(item)}_obs`]"
                                                @input="onFieldChange"
                                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 text-base focus:ring-2 focus:ring-indigo-300">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- TYPE : SYNTHÈSE (champs OUI/NON) -->
                <template v-else-if="step.type === 'synthese'">
                    <div class="space-y-4">
                        <div v-for="field in step.fields" :key="field.key"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                            <p class="text-base font-bold text-gray-800 dark:text-white mb-1">{{ field.label }}</p>
                            <p v-if="field.description" class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ field.description }}</p>
                            <div class="flex gap-3">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" :name="field.key" value="oui"
                                        v-model="formData[field.key]" @change="onFieldChange" class="sr-only">
                                    <div class="rounded-xl border-2 px-4 py-3 text-center text-base font-bold transition-all select-none"
                                        :class="formData[field.key] === 'oui'
                                            ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'
                                            : 'border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-green-300'">
                                        ✅ OUI
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" :name="field.key" value="non"
                                        v-model="formData[field.key]" @change="onFieldChange" class="sr-only">
                                    <div class="rounded-xl border-2 px-4 py-3 text-center text-base font-bold transition-all select-none"
                                        :class="formData[field.key] === 'non'
                                            ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'
                                            : 'border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-red-300'">
                                        ❌ NON
                                    </div>
                                </label>
                            </div>
                        </div>
                        <!-- Observations générales -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                            <label class="block text-base font-bold text-gray-800 dark:text-white mb-2">📝 Observations générales</label>
                            <textarea v-model="formData['synthese_obs']" @input="onFieldChange"
                                rows="4" placeholder="Remarques, commentaires de clôture…"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500 rounded-xl px-4 py-3 text-base focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 resize-none">
                            </textarea>
                        </div>
                    </div>
                </template>

                <!-- Navigation Précédent / Suivant -->
                <div class="flex justify-between items-center mt-8 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <button @click="goToStep(i - 1)"
                        :class="i === 0 ? 'invisible' : ''"
                        class="flex items-center gap-2 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold px-6 py-3.5 rounded-xl text-base hover:border-indigo-400 hover:text-indigo-700 dark:hover:border-indigo-500 dark:hover:text-indigo-400 transition-all">
                        ← Précédent
                    </button>
                    <span class="text-sm font-semibold text-gray-400 dark:text-gray-500">
                        Étape {{ i + 1 }} / {{ steps.length }}
                    </span>
                    <button v-if="i < steps.length - 1" @click="goToStep(i + 1)"
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold px-6 py-3.5 rounded-xl text-base transition-all shadow-sm">
                        Suivant →
                    </button>
                    <button v-else @click="goToSignature"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3.5 rounded-xl text-base shadow-sm">
                        ✍️ Passer à la signature
                    </button>
                </div>

            </div><!-- /panel -->

        </div><!-- /main -->
    </div><!-- /flex -->
</div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { steps, slugify } from '../data/steps.js'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const edl = ref({})
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
    return steps.map((step) => {
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
    if (n < 0 || n >= steps.length) return
    // Sauvegarder immédiatement avant de changer d'étape
    await saveSurvey()
    currentStep.value = n
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

function onFieldChange() {
    scheduleSave()
}

function scheduleSave() {
    clearTimeout(saveTimer)
    saveTimer = setTimeout(saveSurvey, 3000)
}

async function goToSignature() {
    await saveSurvey()
    router.push({ name: 'signature', params: { id: route.params.id } })
}

async function saveSurvey() {
    clearTimeout(saveTimer)
    try {
        await axios.post(`/api/edls/${route.params.id}/survey`, {
            survey_data: JSON.stringify(formData),
        })
        saveOk.value = true
        setTimeout(() => { saveOk.value = false }, 3000)
    } catch (e) {
        console.warn('Sauvegarde échouée', e)
    }
}

// ── Photos ────────────────────────────────────────────
function openPhotoUpload(room) {
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    input.multiple = true
    input.onchange = (e) => handlePhotoUpload(e, room)
    input.click()
}

function openCameraCapture(room) {
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    input.capture = 'environment' // caméra arrière sur mobile
    input.onchange = (e) => handlePhotoUpload(e, room)
    input.click()
}

async function handlePhotoUpload(event, room) {
    const files = event.target.files
    if (!files || files.length === 0) return

    for (const file of files) {
        const fd = new FormData()
        fd.append('photo', file)
        fd.append('question_key', room)
        fd.append('room', room)

        try {
            const { data } = await axios.post(`/api/edls/${route.params.id}/photos`, fd)
            if (data.success) {
                if (!photos[room]) photos[room] = []
                photos[room].push({ id: data.photo_id, url: data.url })
            }
        } catch (e) {
            console.error('Upload photo échoué', e)
        }
    }
}

async function loadExistingPhotos() {
    try {
        const { data } = await axios.get(`/api/edls/${route.params.id}/photos`)
        data.forEach(p => {
            if (!photos[p.room]) photos[p.room] = []
            photos[p.room].push({ id: p.id, url: p.url })
        })
    } catch (e) {}
}

// ── Init ──────────────────────────────────────────────
onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/edls/${route.params.id}`)
        edl.value = data

        // Restaurer formData depuis survey_data
        if (data.survey_data) {
            Object.assign(formData, data.survey_data)
        }

        await loadExistingPhotos()
    } catch (e) {
        console.error('Erreur chargement EDL', e)
    } finally {
        loading.value = false
    }
})

onBeforeUnmount(() => {
    clearTimeout(saveTimer)
})
</script>
