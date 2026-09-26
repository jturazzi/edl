<template>
<div>
    <PageHeader title="Administration" subtitle="Statistiques, utilisateurs et journal d'activité." />

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-4">
        <q-card v-for="i in 4" :key="i" flat bordered>
            <q-card-section class="space-y-3">
                <q-skeleton type="text" width="8rem" />
                <q-skeleton type="text" />
                <q-skeleton type="text" width="75%" />
                <q-skeleton type="text" width="50%" />
            </q-card-section>
        </q-card>
    </div>

    <div v-else-if="info" class="space-y-5">

        <!-- Statistiques -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <q-card v-for="m in metrics" :key="m.label" flat>
                <q-card-section class="flex items-center gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg" :class="m.tone"><q-icon :name="m.icon" size="22px" /></span>
                    <div>
                        <p class="m-0 text-caption text-grey-7">{{ m.label }}</p>
                        <p class="m-0 text-h5 leading-tight">{{ m.value }}</p>
                    </div>
                </q-card-section>
            </q-card>
        </div>

        <!-- Utilisateurs et rôles -->
        <q-card flat class="overflow-hidden">
            <q-card-section class="flex items-center justify-between gap-3">
                <SectionTitle icon="mdi-account-key-outline" title="Utilisateurs" />
                <span class="text-caption text-grey-7">{{ users.length }} compte{{ users.length !== 1 ? 's' : '' }}</span>
            </q-card-section>
            <q-banner v-if="userError" dense class="bg-red-1 text-negative mx-4 mb-2 rounded-borders">{{ userError }}</q-banner>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-body2">
                    <thead>
                        <tr class="border-y border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2.5 font-medium">Utilisateur</th>
                            <th class="px-3 py-2.5 font-medium">EDL</th>
                            <th class="px-5 py-2.5 font-medium" style="width: 12rem">Rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id" class="border-b border-slate-100 last:border-0">
                            <td class="px-5 py-2.5">
                                <p class="m-0 font-medium text-slate-900">{{ u.name }}<span v-if="u.id === me?.id" class="ml-1.5 text-xs text-slate-400">(vous)</span></p>
                                <p class="m-0 text-xs text-slate-500">{{ u.email }}</p>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700">{{ u.edls_count }}</td>
                            <td class="px-5 py-2">
                                <q-select :model-value="u.role" :options="ROLE_OPTIONS" emit-value map-options outlined dense options-dense
                                    :loading="savingUserId === u.id" :aria-label="`Rôle de ${u.name}`" @update:model-value="(role) => changeRole(u, role)" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="m-0 border-t border-slate-100 px-5 py-3 text-caption text-grey-7">
                <strong>Administrateur</strong> : accède à tous les états des lieux et à cette page.
                <strong>Technicien</strong> : ne voit que les états des lieux qu'il a créés.
            </p>
        </q-card>

        <!-- Journal d'activité -->
        <q-card flat bordered>
            <q-card-section class="flex items-center justify-between gap-3 flex-wrap">
                <SectionTitle icon="mdi-format-list-bulleted" title="Journal d'activité" />
                <div class="flex items-center gap-3">
                    <span class="text-caption text-grey-7">{{ logs.length }} entrée{{ logs.length !== 1 ? 's' : '' }}</span>
                    <q-btn outline dense no-caps color="grey-8" icon="mdi-refresh" label="Rafraîchir" class="px-3" :disable="logsLoading" @click="loadLogs" />
                </div>
            </q-card-section>
            <q-separator />
            <div v-if="logsLoading" class="py-8 flex justify-center"><q-spinner color="primary" size="32px" /></div>
            <p v-else-if="logs.length === 0" class="py-8 text-center text-body2 text-grey-7 m-0">Aucune activité enregistrée pour l'instant.</p>
            <q-list v-else separator class="max-h-[540px] overflow-y-auto">
                <q-item v-for="log in logs" :key="log.id" class="items-start">
                    <q-item-section avatar top>
                        <span class="inline-flex items-center justify-center size-8 rounded-lg" :class="logTone(log.action)">
                            <q-icon :name="logIcon(log.action)" size="20px" />
                        </span>
                    </q-item-section>
                    <q-item-section>
                        <q-item-label class="font-medium">
                            {{ logLabel(log.action) }}
                            <AppBadge v-if="log.details?.type" :tone="log.details.type === 'entrant' ? 'green' : 'amber'" class="ml-1">{{ log.details.type }}</AppBadge>
                        </q-item-label>
                        <q-item-label caption lines="1">{{ logDetail(log) }}</q-item-label>
                        <q-item-label v-if="log.user" caption>{{ log.user.name }}</q-item-label>
                    </q-item-section>
                    <q-item-section side top>
                        <time class="text-caption text-grey-7">{{ formatLogDate(log.created_at) }}</time>
                    </q-item-section>
                </q-item>
            </q-list>
        </q-card>
    </div>

    <p v-else class="text-center py-16 text-negative">{{ forbidden ? 'Cette page est réservée aux administrateurs.' : 'Impossible de charger les informations système.' }}</p>
</div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import axios from 'axios'
import SectionTitle from '@/components/SectionTitle.vue'
import PageHeader from '@/components/PageHeader.vue'
import AppBadge from '@/components/AppBadge.vue'

const loading = ref(true)
const info = ref(null)
const me = inject('user', ref(null))
const forbidden = ref(false)

const metrics = computed(() => {
    const st = info.value?.stats
    if (!st) return []
    return [
        { label: 'EDL total', value: st.edl_total, icon: 'mdi-clipboard-text-outline', tone: 'bg-slate-100 text-slate-600' },
        { label: 'Entrants', value: st.edl_entrant, icon: 'mdi-key-variant', tone: 'bg-emerald-50 text-emerald-600' },
        { label: 'Sortants', value: st.edl_sortant, icon: 'mdi-logout', tone: 'bg-amber-50 text-amber-600' },
        { label: 'En cours', value: st.edl_en_cours, icon: 'mdi-clock-outline', tone: 'bg-blue-50 text-blue-600' },
    ]
})

onMounted(() => {
    // Lance les requêtes en parallèle sans bloquer
    axios.get('/api/admin/info')
        .then(r => { info.value = r.data })
        .catch(e => { forbidden.value = e.response?.status === 403; console.error('Erreur admin/info', e) })
        .finally(() => { loading.value = false })

    loadLogs()
    loadUsers()
})

// ── Utilisateurs et rôles ───────────────────────────
const ROLE_OPTIONS = [
    { label: 'Administrateur', value: 'admin' },
    { label: 'Technicien', value: 'technicien' },
]
const users = ref([])
const userError = ref('')
const savingUserId = ref(null)

async function loadUsers() {
    try {
        users.value = (await axios.get('/api/admin/users')).data
    } catch (e) {
        console.error('Erreur chargement utilisateurs', e)
    }
}

async function changeRole(user, role) {
    userError.value = ''
    savingUserId.value = user.id
    try {
        await axios.patch(`/api/admin/users/${user.id}`, { role })
        user.role = role
        loadLogs()
    } catch (e) {
        userError.value = e.response?.data?.message || 'Impossible de modifier le rôle.'
    } finally {
        savingUserId.value = null
    }
}

// ── Journal d'activité ──────────────────────────────
const logs = ref([])
const logsLoading = ref(false)

async function loadLogs() {
    logsLoading.value = true
    try {
        const { data } = await axios.get('/api/logs')
        logs.value = data
    } catch (e) {
        console.error('Erreur chargement logs', e)
    } finally {
        logsLoading.value = false
    }
}

const LOG_LABELS = {
    edl_completed:    'EDL terminé',
    edl_deleted:      'EDL supprimé',
    category_created: 'Catégorie créée',
    category_deleted: 'Catégorie supprimée',
    edl_archived:     'EDL archivé',
    edl_unarchived:   'EDL désarchivé',
    edl_duplicated:   'EDL dupliqué',
    user_login:       'Connexion',
    user_logout:      'Déconnexion',
    login_failed:     'Connexion échouée',
    user_role_changed: 'Rôle modifié',
}

function logLabel(action) {
    return LOG_LABELS[action] ?? action
}

function logDetail(log) {
    if (log.entity_type === 'edl' && log.details) {
        const parts = [log.details.adresse, log.details.ville].filter(Boolean)
        if (log.details.locataire) parts.push(`- ${log.details.locataire}`)
        return parts.join(', ') || `EDL #${log.entity_id}`
    }
    if (['user_login', 'user_logout', 'login_failed'].includes(log.action)) {
        const d = log.details ?? {}
        return [d.email, d.ip, d.error].filter(Boolean).join(' · ') || `Utilisateur #${log.entity_id ?? '?'}`
    }
    if (log.entity_type === 'user' && log.details) {
        const role = (r) => (r === 'admin' ? 'administrateur' : 'technicien')
        return `${log.details.name ?? 'Utilisateur #' + log.entity_id} : ${role(log.details.from)} → ${role(log.details.to)}`
    }
    if (log.entity_type === 'category' && log.details) {
        return log.details.name ?? `Catégorie #${log.entity_id}`
    }
    return `#${log.entity_id}`
}

const LOG_ICONS = {
    edl_completed: 'mdi-check-circle-outline',
    edl_deleted: 'mdi-delete-outline',
    category_created: 'mdi-tag-outline',
    edl_archived: 'mdi-archive-outline',
    edl_unarchived: 'mdi-archive-arrow-up-outline',
    edl_duplicated: 'mdi-content-copy',
    user_login: 'mdi-login',
    user_logout: 'mdi-logout',
    login_failed: 'mdi-shield-alert-outline',
    user_role_changed: 'mdi-account-key-outline',
}
const LOG_TONES = {
    edl_completed: 'bg-green-100 text-green-700',
    edl_deleted: 'bg-red-100 text-red-700',
    category_created: 'bg-sky-100 text-sky-700',
    edl_archived: 'bg-amber-100 text-amber-700',
    edl_unarchived: 'bg-amber-100 text-amber-700',
    edl_duplicated: 'bg-sky-100 text-sky-700',
    user_login: 'bg-slate-100 text-slate-600',
    user_logout: 'bg-slate-100 text-slate-600',
    login_failed: 'bg-red-100 text-red-700',
    user_role_changed: 'bg-cyan-100 text-cyan-700',
}

function logIcon(action) {
    return LOG_ICONS[action] ?? 'mdi-close-circle-outline'
}

function logTone(action) {
    return LOG_TONES[action] ?? 'bg-slate-100 text-slate-500'
}

function formatLogDate(iso) {
    const d = new Date(iso)
    const diffMins = Math.floor((Date.now() - d) / 60000)
    if (diffMins < 1)  return 'À l\'instant'
    if (diffMins < 60) return `Il y a ${diffMins} min`
    const diffH = Math.floor(diffMins / 60)
    if (diffH < 24)    return `Il y a ${diffH}h`
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
