import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '../pages/LoginPage.vue'
import HomePage from '../pages/HomePage.vue'
import FormPage from '../pages/FormPage.vue'
import SignaturePage from '../pages/SignaturePage.vue'
import EdlPage from '../pages/EdlPage.vue'
import HistoryPage from '../pages/HistoryPage.vue'
import CguPage from '../pages/CguPage.vue'
import AdminInfoPage from '../pages/AdminInfoPage.vue'

const routes = [
    { path: '/login',                      name: 'login',         component: LoginPage, meta: { guest: true } },
    { path: '/',                           name: 'home',          component: HomePage },
    { path: '/edl/:id/formulaire',         name: 'survey',        component: FormPage },
    { path: '/edl/:id/signature',          name: 'signature',     component: SignaturePage },
    { path: '/edl/:id',                    name: 'confirmation',  component: EdlPage },
    { path: '/edl/:id/confirmation',       redirect: to => ({ name: 'confirmation', params: { id: to.params.id } }) },
    { path: '/historique',                 name: 'history',       component: HistoryPage },
    { path: '/cgu',                        name: 'cgu',           component: CguPage },
    { path: '/admin/info',                 name: 'admin.info',    component: AdminInfoPage },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

export default router
