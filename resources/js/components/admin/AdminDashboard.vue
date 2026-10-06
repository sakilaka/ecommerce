<script setup>
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faBell } from '@fortawesome/free-solid-svg-icons';
import { useAuth } from '../../composables/useAuth';
import { useStorefront } from '../../composables/useStorefront';
import AdminOverviewPanel from './AdminOverviewPanel.vue';

const AdminProductsPanel = defineAsyncComponent(() => import('./AdminProductsPanel.vue'));
const AdminCatalogManager = defineAsyncComponent(() => import('./AdminCatalogManager.vue'));
const AdminOrdersPanel = defineAsyncComponent(() => import('./AdminOrdersPanel.vue'));
const AdminPaymentsPanel = defineAsyncComponent(() => import('./AdminPaymentsPanel.vue'));
const AdminInventoryPanel = defineAsyncComponent(() => import('./AdminInventoryPanel.vue'));
const AdminCustomersPanel = defineAsyncComponent(() => import('./AdminCustomersPanel.vue'));
const AdminMarketingPanel = defineAsyncComponent(() => import('./AdminMarketingPanel.vue'));
const AdminPopularSearchesPanel = defineAsyncComponent(() => import('./AdminPopularSearchesPanel.vue'));
const AdminNewsletterLeadsPanel = defineAsyncComponent(() => import('./AdminNewsletterLeadsPanel.vue'));
const AdminReportsPanel = defineAsyncComponent(() => import('./AdminReportsPanel.vue'));
const AdminReturnsPanel = defineAsyncComponent(() => import('./AdminReturnsPanel.vue'));
const AdminReviewsPanel = defineAsyncComponent(() => import('./AdminReviewsPanel.vue'));
const AdminWishlistInsightsPanel = defineAsyncComponent(() => import('./AdminWishlistInsightsPanel.vue'));
const AdminHomepageSectionsPanel = defineAsyncComponent(() => import('./AdminHomepageSectionsPanel.vue'));
const AdminStaffPanel = defineAsyncComponent(() => import('./AdminStaffPanel.vue'));
const AdminDeliveryOperationsPanel = defineAsyncComponent(() => import('./AdminDeliveryOperationsPanel.vue'));
const AdminSettingsPanel = defineAsyncComponent(() => import('./AdminSettingsPanel.vue'));
const AdminAuditLogsPanel = defineAsyncComponent(() => import('./AdminAuditLogsPanel.vue'));

const { copy, icons, locale, localeToggleLabel, themeIcon, themeLabel, toggleLocale, toggleTheme } = useStorefront();
const { loadUser, logout, user } = useAuth();

const isLoading = ref(false);
const error = ref(null);
const dashboard = ref(null);
const activePanel = ref('overview');
const isMobileNavOpen = ref(false);
const isNoticeOpen = ref(false);
const adminSearch = ref('');

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const text = computed(() => locale.value === 'bn' ? {
    app: 'Admin Console',
    search: 'অর্ডার, পণ্য, কাস্টমার খুঁজুন',
    menu: 'মেনু',
    closeMenu: 'মেনু বন্ধ করুন',
    storefront: 'স্টোরফ্রন্ট',
    logout: 'লগআউট',
    overview: 'ওভারভিউ',
    orders: 'অর্ডার',
    payments: 'পেমেন্ট',
    products: 'প্রোডাক্ট',
    inventory: 'ইনভেন্টরি',
    categories: 'ক্যাটাগরি',
    brands: 'ব্র্যান্ড',
    customers: 'কাস্টমার',
    marketing: 'মার্কেটিং',
    popularSearches: 'Popular Searches',
    newsletterLeads: 'Offer Leads',
    reports: 'রিপোর্ট',
    delivery: 'ডেলিভারি',
    returns: 'রিটার্ন',
    reviews: 'রিভিউ',
    wishlist: 'উইশলিস্ট',
    homepage: 'হোমপেজ',
    staff: 'স্টাফ',
    activity: 'অ্যাক্টিভিটি',
    settings: 'সেটিংস',
    groupCommerce: 'Commerce',
    groupCatalog: 'Catalog',
    groupOperations: 'Operations',
    groupGrowth: 'Growth',
    groupSystem: 'System',
    alerts: 'অ্যালার্ট',
    noAlerts: 'কোনো স্টক অ্যালার্ট নেই।',
} : {
    app: 'Admin Console',
    search: 'Search orders, products, customers',
    menu: 'Menu',
    closeMenu: 'Close menu',
    storefront: 'Storefront',
    logout: 'Logout',
    overview: 'Overview',
    orders: 'Orders',
    payments: 'Payments',
    products: 'Products',
    inventory: 'Inventory',
    categories: 'Categories',
    brands: 'Brands',
    customers: 'Customers',
    marketing: 'Marketing',
    popularSearches: 'Popular Searches',
    newsletterLeads: 'Offer Leads',
    reports: 'Reports',
    delivery: 'Delivery',
    returns: 'Returns',
    reviews: 'Reviews',
    wishlist: 'Wishlist',
    homepage: 'Homepage',
    staff: 'Staff',
    activity: 'Activity',
    settings: 'Settings',
    groupCommerce: 'Commerce',
    groupCatalog: 'Catalog',
    groupOperations: 'Operations',
    groupGrowth: 'Growth',
    groupSystem: 'System',
    alerts: 'Alerts',
    noAlerts: 'No stock alerts.',
});

const canAccess = (permission) => {
    const permissions = user.value?.permissions ?? ['*'];

    return permissions.includes('*') || permissions.includes(permission);
};

const navGroups = computed(() => [
    {
        id: 'commerce',
        label: text.value.groupCommerce,
        items: [
            { id: 'overview', label: text.value.overview, icon: icons.house, permission: 'dashboard.view' },
            { id: 'orders', label: text.value.orders, icon: icons.bag, permission: 'orders.manage' },
            { id: 'payments', label: text.value.payments, icon: icons.cart, permission: 'payments.manage' },
            { id: 'customers', label: text.value.customers, icon: icons.users, permission: 'customers.manage' },
        ],
    },
    {
        id: 'catalog',
        label: text.value.groupCatalog,
        items: [
            { id: 'products', label: text.value.products, icon: icons.store, permission: 'catalog.manage' },
            { id: 'categories', label: text.value.categories, icon: icons.grip, permission: 'catalog.manage' },
            { id: 'brands', label: text.value.brands, icon: icons.gem, permission: 'catalog.manage' },
            { id: 'inventory', label: text.value.inventory, icon: icons.truck, permission: 'inventory.manage' },
        ],
    },
    {
        id: 'operations',
        label: text.value.groupOperations,
        items: [
            { id: 'delivery', label: text.value.delivery, icon: icons.truck, permission: 'delivery.manage' },
            { id: 'returns', label: text.value.returns, icon: icons.returns, permission: 'returns.manage' },
            { id: 'reviews', label: text.value.reviews, icon: icons.star, permission: 'reviews.manage' },
            { id: 'wishlist', label: text.value.wishlist, icon: icons.heart, permission: 'wishlist.view' },
        ],
    },
    {
        id: 'growth',
        label: text.value.groupGrowth,
        items: [
            { id: 'homepage', label: text.value.homepage, icon: icons.wand, permission: 'homepage.manage' },
            { id: 'marketing', label: text.value.marketing, icon: icons.tags, permission: 'marketing.manage' },
            { id: 'popular-searches', label: text.value.popularSearches, icon: icons.search, permission: 'marketing.manage' },
            { id: 'newsletter-leads', label: text.value.newsletterLeads, icon: icons.gift, permission: 'marketing.manage' },
            { id: 'reports', label: text.value.reports, icon: icons.chart, permission: 'reports.view' },
        ],
    },
    {
        id: 'system',
        label: text.value.groupSystem,
        items: [
            { id: 'activity', label: text.value.activity, icon: icons.clock, permission: 'activity.view' },
            { id: 'settings', label: text.value.settings, icon: icons.shield, permission: 'settings.manage' },
            { id: 'staff', label: text.value.staff, icon: icons.users, permission: 'staff.manage' },
        ],
    },
].map((group) => ({
    ...group,
    items: group.items.filter((item) => canAccess(item.permission)),
})).filter((group) => group.items.length > 0));

const panelComponents = {
    orders: AdminOrdersPanel,
    payments: AdminPaymentsPanel,
    products: AdminProductsPanel,
    categories: AdminCatalogManager,
    brands: AdminCatalogManager,
    inventory: AdminInventoryPanel,
    customers: AdminCustomersPanel,
    marketing: AdminMarketingPanel,
    'popular-searches': AdminPopularSearchesPanel,
    'newsletter-leads': AdminNewsletterLeadsPanel,
    reports: AdminReportsPanel,
    delivery: AdminDeliveryOperationsPanel,
    returns: AdminReturnsPanel,
    reviews: AdminReviewsPanel,
    wishlist: AdminWishlistInsightsPanel,
    homepage: AdminHomepageSectionsPanel,
    settings: AdminSettingsPanel,
    activity: AdminAuditLogsPanel,
    staff: AdminStaffPanel,
};

const currentPanelComponent = computed(() => panelComponents[activePanel.value] ?? null);
const currentPanelProps = computed(() => activePanel.value === 'categories' ? { type: 'categories' } : activePanel.value === 'brands' ? { type: 'brands' } : {});
const currentPanelTitle = computed(() => navGroups.value.flatMap((group) => group.items).find((item) => item.id === activePanel.value)?.label ?? text.value.overview);
const stockAlerts = computed(() => dashboard.value?.stock_alerts ?? []);
const alertCount = computed(() => Number(dashboard.value?.stats?.low_stock_variants || 0));
const userInitial = computed(() => String(user.value?.name || 'A').charAt(0).toUpperCase());

const selectPanel = (panel) => {
    activePanel.value = panel;
    isMobileNavOpen.value = false;
    isNoticeOpen.value = false;
};

const submitAdminSearch = () => {
    const term = adminSearch.value.trim().toLowerCase();

    if (term === '') {
        return;
    }

    if (term.startsWith('ord') || term.includes('order')) {
        selectPanel('orders');
    } else if (term.includes('@') || term.includes('customer') || term.includes('user')) {
        selectPanel('customers');
    } else if (term.includes('stock') || term.includes('invent')) {
        selectPanel('inventory');
    } else {
        selectPanel('products');
    }
};

const loadDashboard = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/admin/dashboard', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.message ?? 'Dashboard request failed.');
        }

        dashboard.value = payload.data;
    } catch (dashboardError) {
        error.value = dashboardError.message;
    } finally {
        isLoading.value = false;
    }
};

const logoutAdmin = async () => {
    await logout();
    window.location.href = '/';
};

onMounted(async () => {
    await loadUser();
    await loadDashboard();
});
</script>

<template>
    <div class="min-h-screen bg-[#f6f6f6] font-sans text-ink transition-colors dark:bg-[#111111] dark:text-white">
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-60 flex-col bg-[#141414] p-3 text-white lg:flex">
            <a class="mb-4 flex items-center gap-3 rounded-2xl px-2 py-2" href="/admin/dashboard">
                <span class="grid size-10 place-items-center rounded-2xl bg-wine text-lg font-semibold">A</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">{{ copy.brand }}</span>
                    <span class="block text-[11px] font-semibold text-white/50">{{ text.app }}</span>
                </span>
            </a>
            <nav class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                <div v-for="group in navGroups" :key="group.id">
                    <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-white/35">{{ group.label }}</p>
                    <div class="grid gap-0.5">
                        <button
                            v-for="item in group.items"
                            :key="item.id"
                            class="flex h-10 items-center gap-3 rounded-full px-3 text-[13px] font-semibold transition"
                            :class="activePanel === item.id ? 'bg-wine text-white' : 'text-white/65 hover:bg-white/8 hover:text-white'"
                            type="button"
                            @click="selectPanel(item.id)"
                        >
                            <FontAwesomeIcon :icon="item.icon" class="w-3.5" />
                            <span class="truncate">{{ item.label }}</span>
                        </button>
                    </div>
                </div>
            </nav>
        </aside>

        <div v-if="isMobileNavOpen" class="fixed inset-0 z-50 bg-black/45 backdrop-blur-sm lg:hidden" @click="isMobileNavOpen = false"></div>
        <aside v-if="isMobileNavOpen" class="fixed inset-y-0 left-0 z-50 flex w-[min(18rem,calc(100vw-2rem))] flex-col bg-[#141414] p-3 text-white shadow-2xl lg:hidden">
            <div class="mb-4 flex items-center justify-between gap-3">
                <a class="flex min-w-0 items-center gap-3" href="/admin/dashboard">
                    <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-wine text-lg font-semibold">A</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">{{ copy.brand }}</span>
                        <span class="block text-[11px] font-semibold text-white/50">{{ text.app }}</span>
                    </span>
                </a>
                <button class="grid size-10 place-items-center rounded-full bg-white/10" type="button" :aria-label="text.closeMenu" @click="isMobileNavOpen = false">
                    <FontAwesomeIcon :icon="icons.xmark" />
                </button>
            </div>
            <nav class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                <div v-for="group in navGroups" :key="group.id">
                    <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-white/35">{{ group.label }}</p>
                    <div class="grid gap-0.5">
                        <button
                            v-for="item in group.items"
                            :key="item.id"
                            class="flex h-10 items-center gap-3 rounded-full px-3 text-[13px] font-semibold transition"
                            :class="activePanel === item.id ? 'bg-wine text-white' : 'text-white/65 hover:bg-white/8 hover:text-white'"
                            type="button"
                            @click="selectPanel(item.id)"
                        >
                            <FontAwesomeIcon :icon="item.icon" class="w-3.5" />
                            <span class="truncate">{{ item.label }}</span>
                        </button>
                    </div>
                </div>
            </nav>
        </aside>

        <div class="lg:pl-60">
            <header class="sticky top-0 z-30 border-b border-black/8 bg-white/95 px-4 py-3 backdrop-blur-xl dark:border-white/10 dark:bg-[#111111]/95 sm:px-6">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex min-w-0 items-center gap-3 lg:hidden">
                        <button class="grid size-10 place-items-center rounded-full border border-black/10 bg-white dark:border-white/10 dark:bg-white/8" type="button" :aria-label="text.menu" @click="isMobileNavOpen = true">
                            <FontAwesomeIcon :icon="icons.bars" />
                        </button>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ currentPanelTitle }}</p>
                            <p class="truncate text-[11px] font-semibold text-ink/45 dark:text-white/45">{{ text.app }}</p>
                        </div>
                    </div>

                    <div class="hidden items-center gap-2 text-sm font-semibold text-ink dark:text-white lg:flex">
                        <FontAwesomeIcon :icon="icons.chart" class="text-wine" />
                        {{ currentPanelTitle }}
                    </div>

                    <form class="order-last flex h-10 w-full items-center gap-2 rounded-full border border-black/8 bg-[#f6f6f6] px-3 dark:border-white/10 dark:bg-white/8 sm:order-none sm:w-auto sm:min-w-72" @submit.prevent="submitAdminSearch">
                        <FontAwesomeIcon :icon="icons.search" class="text-wine" />
                        <input v-model="adminSearch" class="min-w-0 flex-1 bg-transparent text-sm font-semibold outline-none placeholder:text-ink/40 dark:text-white" type="search" :placeholder="text.search">
                    </form>

                    <div class="ml-auto flex items-center gap-2">
                        <button class="grid size-10 place-items-center rounded-full border border-black/10 bg-white text-xs font-semibold dark:border-white/10 dark:bg-white/8" type="button" :aria-label="localeToggleLabel" @click="toggleLocale">{{ localeToggleLabel }}</button>
                        <button class="grid size-10 place-items-center rounded-full border border-black/10 bg-white dark:border-white/10 dark:bg-white/8" type="button" :aria-label="themeLabel" @click="toggleTheme">
                            <FontAwesomeIcon :icon="themeIcon" />
                        </button>
                        <div class="relative">
                            <button class="relative grid size-10 place-items-center rounded-full border border-black/10 bg-white dark:border-white/10 dark:bg-white/8" type="button" :aria-label="text.alerts" @click="isNoticeOpen = !isNoticeOpen">
                                <FontAwesomeIcon :icon="faBell" />
                                <span v-if="alertCount" class="absolute -right-0.5 -top-0.5 grid min-w-4 place-items-center rounded-full bg-wine px-1 text-[10px] font-semibold leading-4 text-white">{{ alertCount }}</span>
                            </button>
                            <div v-if="isNoticeOpen" class="absolute right-0 mt-2 w-72 rounded-[1.1rem] border border-black/8 bg-white p-3 shadow-2xl dark:border-white/10 dark:bg-[#171717]">
                                <p class="text-xs font-semibold uppercase text-wine">{{ text.alerts }}</p>
                                <button
                                    v-for="item in stockAlerts.slice(0, 4)"
                                    :key="item.id"
                                    class="mt-2 flex w-full items-center justify-between gap-3 rounded-2xl bg-ink/4 px-3 py-2 text-left dark:bg-white/8"
                                    type="button"
                                    @click="selectPanel('inventory')"
                                >
                                    <span class="min-w-0 truncate text-sm font-semibold">{{ item.product_name }}</span>
                                    <span class="text-xs font-semibold text-wine">{{ item.stock_quantity }}</span>
                                </button>
                                <p v-if="stockAlerts.length === 0" class="mt-3 text-sm font-semibold text-ink/45 dark:text-white/45">{{ text.noAlerts }}</p>
                            </div>
                        </div>
                        <a class="hidden h-10 items-center rounded-full border border-black/10 bg-white px-3 text-sm font-semibold dark:border-white/10 dark:bg-white/8 sm:inline-flex" href="/">{{ text.storefront }}</a>
                        <div class="hidden items-center gap-2 rounded-full border border-black/10 bg-white py-1 pl-1 pr-3 dark:border-white/10 dark:bg-white/8 md:flex">
                            <span class="grid size-8 place-items-center rounded-full bg-wine text-xs font-semibold text-white">{{ userInitial }}</span>
                            <span class="max-w-28 truncate text-sm font-semibold">{{ user?.name }}</span>
                        </div>
                        <button class="h-10 rounded-full bg-wine px-4 text-sm font-semibold text-white" type="button" @click="logoutAdmin">{{ text.logout }}</button>
                    </div>
                </div>
            </header>

            <main class="px-4 py-5 sm:px-6">
                <div v-if="error" class="note-wine mb-4">{{ error }}</div>

                <AdminOverviewPanel
                    v-if="activePanel === 'overview'"
                    :dashboard="dashboard"
                    :is-loading="isLoading"
                    @refresh="loadDashboard"
                    @navigate="selectPanel"
                />

                <Suspense v-else-if="currentPanelComponent">
                    <component :is="currentPanelComponent" v-bind="currentPanelProps" />
                    <template #fallback>
                        <section class="rounded-[1.2rem] border border-black/8 bg-white p-6 text-sm font-semibold dark:border-white/10 dark:bg-white/8">Loading...</section>
                    </template>
                </Suspense>
            </main>
        </div>
    </div>
</template>
