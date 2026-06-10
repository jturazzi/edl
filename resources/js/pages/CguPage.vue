<template>
<div class="max-w-3xl mx-auto">

    <!-- En-tête -->
    <div class="mb-8">
        <router-link to="/" class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </router-link>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">Conditions Générales d'Utilisation</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Dernière mise à jour : mars 2026</p>
    </div>

    <!-- Contenu -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm divide-y divide-gray-100 dark:divide-gray-700">

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">1. Objet</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation de l'application
                <strong>{{ appName }}</strong>, un outil interne pour la réalisation et la gestion des états des lieux de
                logements.
            </p>
        </section>

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">2. Accès et authentification</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-3">
                L'accès à l'application est réservé exclusivement aux agents autorisés de {{ appName }}, authentifiés
                via le compte Microsoft 365 de l'organisation. Toute tentative d'accès non autorisée est strictement
                interdite.
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                L'utilisateur est responsable de la confidentialité de ses identifiants de connexion et s'engage à
                signaler immédiatement tout accès non autorisé à son compte.
            </p>
        </section>

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">3. Données personnelles</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-3">
                L'application traite des données à caractère personnel dans le cadre des états des lieux (noms des
                locataires, adresses, photographies des logements). Ces traitements sont effectués conformément au
                Règlement Général sur la Protection des Données (RGPD — UE 2016/679) et à la loi Informatique et
                Libertés.
            </p>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1.5 list-disc list-inside leading-relaxed">
                <li><strong>Responsable du traitement :</strong> {{ appName }}</li>
                <li><strong>Finalité :</strong> gestion locative et suivi de l'état des logements</li>
                <li><strong>Durée de conservation :</strong> conformément aux obligations légales en matière de bail</li>
                <li><strong>Droits :</strong> accès, rectification, effacement, portabilité — contacter l'association via les canaux habituels</li>
            </ul>
        </section>

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">4. Photographies</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                Les photographies prises et téléversées via l'application sont rattachées à l'état des lieux
                correspondant et stockées sur les serveurs sécurisés de {{ appName }}. Elles ne sont accessibles
                qu'aux agents autorisés et ne doivent pas contenir d'images de personnes physiques sans leur
                consentement explicite.
            </p>
        </section>

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">5. Responsabilités</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-3">
                L'utilisateur s'engage à utiliser l'application de manière loyale, conformément à sa destination et
                aux politiques internes de {{ appName }}. Il est seul responsable des informations saisies et des
                constats effectués.
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ appName }} s'engage à maintenir la disponibilité de l'application dans la mesure du possible,
                sans pouvoir garantir une disponibilité continue. Elle ne saurait être tenue responsable des
                pertes de données dues à des défaillances techniques imprévues.
            </p>
        </section>

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">6. Confidentialité</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                Toutes les informations traitées via l'application sont strictement confidentielles. Les agents
                s'engagent à ne pas divulguer, reproduire ou transmettre les données auxquelles ils ont accès
                dans le cadre de leurs fonctions à des tiers non autorisés.
            </p>
        </section>

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">7. Modifications</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ appName }} se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs
                seront informés de toute modification substantielle. La poursuite de l'utilisation de l'application
                après notification vaut acceptation des nouvelles conditions.
            </p>
        </section>

        <section class="p-6 sm:p-8">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">8. Droit applicable</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                Les présentes CGU sont soumises au droit français. En cas de litige, les parties s'engagent à
                rechercher une solution amiable avant tout recours judiciaire. À défaut, les tribunaux compétents
                du ressort du siège de {{ appName }} seront seuls habilités à connaître du différend.
            </p>
        </section>

        <!-- Contact -->
        <div class="p-6 sm:p-8 bg-indigo-50 dark:bg-indigo-900/20 rounded-b-2xl">
            <p class="text-sm text-indigo-700 dark:text-indigo-300 font-medium">
                Pour toute question relative à ces CGU ou au traitement de vos données, contactez {{ appName }}
                par les canaux internes habituels.
            </p>
        </div>
    </div>

</div>
</template>

<script setup>
const appName = window.__APP_NAME__ || 'État des lieux'
</script>
