import { createRouter, createWebHistory } from 'vue-router'

// Pages les plus utilisées : dans le bundle principal ; les autres se chargent à la demande
import DashboardPage from '../pages/DashboardPage.vue'
import FormPage from '../pages/FormPage.vue'

const routes = [
    { path: '/login',                      name: 'login',         component: () => import('../pages/LoginPage.vue'), meta: { title: 'Connexion', guest: true } },
    { path: '/',                           name: 'home',          component: DashboardPage, meta: { title: 'Tableau de bord' } },
    { path: '/nouveau',                    name: 'new',           component: () => import('../pages/HomePage.vue'), meta: { title: 'Nouvel état des lieux' } },
    { path: '/edl/:id/formulaire',         name: 'survey',        component: FormPage, meta: { title: 'Formulaire' } },
    { path: '/edl/:id/signature',          name: 'signature',     component: () => import('../pages/SignaturePage.vue'), meta: { title: 'Signature' } },
    { path: '/edl/:id/comparaison',        name: 'comparison',    component: () => import('../pages/ComparisonPage.vue'), meta: { title: 'Comparaison entrée / sortie' } },
    { path: '/edl/:id',                    name: 'confirmation',  component: () => import('../pages/EdlPage.vue'), meta: { title: 'État des lieux validé' } },
    { path: '/edl/:id/confirmation',       redirect: to => ({ name: 'confirmation', params: { id: to.params.id } }), meta: { title: 'État des lieux validé' } },
    { path: '/logement',                   name: 'logement',      component: () => import('../pages/LogementPage.vue'), meta: { title: 'Historique du logement' } },
    { path: '/historique',                 name: 'history',       component: () => import('../pages/HistoryPage.vue'), meta: { title: 'Historique' } },
    { path: '/info',                       name: 'admin.info',    component: () => import('../pages/AdminInfoPage.vue'), meta: { title: 'Administration' } },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        return savedPosition || { top: 0 }
    },
})

export default router
