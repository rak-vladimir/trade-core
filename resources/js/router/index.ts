import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),

    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: () => import('@/pages/DashboardPage.vue'),
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: () => import('@/pages/NotFoundPage.vue'),
        },
    ],
});

export default router;
