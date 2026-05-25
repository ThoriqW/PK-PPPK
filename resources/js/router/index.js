import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    { path: '/', redirect: '/admin' },
    { path: '/admin/login', component: () => import('@/pages/LoginPage.vue'), meta: { public: true } },
    {
        path: '/admin',
        component: () => import('@/layouts/AdminLayout.vue'),
        children: [
            { path: '', component: () => import('@/pages/DashboardPage.vue') },

            { path: 'employees',                component: () => import('@/pages/employees/EmployeeListPage.vue') },
            { path: 'employees/import',         component: () => import('@/pages/employees/EmployeeImportPage.vue') },
            { path: 'employees/:id',            component: () => import('@/pages/employees/EmployeeDetailPage.vue'), props: true },

            { path: 'templates',                component: () => import('@/pages/templates/TemplateListPage.vue') },
            { path: 'templates/new',            component: () => import('@/pages/templates/TemplateEditorPage.vue') },
            { path: 'templates/:id',            component: () => import('@/pages/templates/TemplateEditorPage.vue'), props: true },

            { path: 'numbering',                component: () => import('@/pages/numbering/NumberingConfigPage.vue') },

            { path: 'agreements',               component: () => import('@/pages/agreements/AgreementListPage.vue') },
            { path: 'agreements/new',           component: () => import('@/pages/agreements/AgreementCreatePage.vue') },
            { path: 'agreements/batch-extend',  component: () => import('@/pages/agreements/AgreementBatchExtendPage.vue') },
            { path: 'agreements/:id',           component: () => import('@/pages/agreements/AgreementDetailPage.vue'), props: true },

            { path: 'archive',                  component: () => import('@/pages/archive/ArchivePage.vue') },
            { path: 'audit',                    component: () => import('@/pages/audit/AuditLogPage.vue') },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (!auth.loaded) {
        await auth.loadMe();
    }
    if (!to.meta.public && !auth.isAuthenticated) {
        return { path: '/admin/login', query: { redirect: to.fullPath } };
    }
    if (to.path === '/admin/login' && auth.isAuthenticated) {
        return { path: '/admin' };
    }
});

export default router;
