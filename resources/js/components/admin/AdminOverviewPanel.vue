<script setup>
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faBoxOpen, faRotate, faUserPlus, faWallet } from '@fortawesome/free-solid-svg-icons';
import { useStorefront } from '../../composables/useStorefront';

const props = defineProps({
    dashboard: { type: Object, default: null },
    isLoading: { type: Boolean, default: false },
});

const emit = defineEmits(['refresh', 'navigate']);
const { icons, locale } = useStorefront();

const text = computed(() => locale.value === 'bn' ? {
    products: 'পণ্য',
    newUsers: 'নতুন ইউজার',
    newOrders: 'নতুন অর্ডার',
    totalProfit: 'মোট রেভিনিউ',
    drafts: 'ড্রাফট',
    thisMonth: 'এই মাসে',
    totalCount: 'মোট',
    pending: 'পেন্ডিং',
    today: 'আজ',
    productSales: 'পণ্য বিক্রি',
    recentBuyers: 'সাম্প্রতিক বায়ার',
    recentOrders: 'সাম্প্রতিক অর্ডার',
    invoiceSummary: 'ইনভয়েস সামারি',
    paidInvoices: 'পেইড',
    unpaidInvoices: 'আনপেইড',
    stockAlerts: 'স্টক অ্যালার্ট',
    empty: 'এখনও ডাটা নেই।',
    order: 'অর্ডার',
    customer: 'কাস্টমার',
    status: 'স্ট্যাটাস',
    payment: 'পেমেন্ট',
    total: 'মোট',
} : {
    products: 'Products',
    newUsers: 'New Users',
    newOrders: 'New Orders',
    totalProfit: 'Total Profit',
    drafts: 'drafts',
    thisMonth: 'this month',
    totalCount: 'total',
    pending: 'pending',
    today: 'today',
    productSales: 'Product Sales',
    recentBuyers: 'Recent Buyers',
    recentOrders: 'Recent Orders',
    invoiceSummary: 'Invoice Summary',
    paidInvoices: 'paid',
    unpaidInvoices: 'unpaid',
    stockAlerts: 'Stock Alerts',
    empty: 'No data yet.',
    order: 'Order',
    customer: 'Customer',
    status: 'Status',
    payment: 'Payment',
    total: 'Total',
});

const stats = computed(() => props.dashboard?.stats ?? {});
const recentOrders = computed(() => props.dashboard?.recent_orders ?? []);
const recentBuyers = computed(() => props.dashboard?.recent_buyers ?? []);
const stockAlerts = computed(() => props.dashboard?.stock_alerts ?? []);
const paymentMix = computed(() => props.dashboard?.payment_mix ?? {});
const salesTrend = computed(() => props.dashboard?.sales_trend ?? []);

const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short' }).format(new Date(value)) : '';
const buyerInitial = (name) => String(name || 'A').charAt(0).toUpperCase();

const metricCards = computed(() => [
    {
        id: 'products',
        label: text.value.products,
        value: '+' + formatNumber(stats.value.products_active),
        hint: formatNumber(stats.value.products_draft) + ' ' + text.value.drafts,
        icon: faBoxOpen,
        tone: 'from-wine to-wine-deep',
        panel: 'products',
    },
    {
        id: 'users',
        label: text.value.newUsers,
        value: formatNumber(stats.value.customers_month),
        hint: formatNumber(stats.value.customers_total) + ' ' + text.value.totalCount,
        icon: faUserPlus,
        tone: 'from-ink to-[#1f1f1f]',
        panel: 'customers',
    },
    {
        id: 'orders',
        label: text.value.newOrders,
        value: formatNumber(stats.value.orders_month),
        hint: formatNumber(stats.value.orders_pending) + ' ' + text.value.pending,
        icon: icons.bag,
        tone: 'from-wine-soft to-wine',
        panel: 'orders',
    },
    {
        id: 'profit',
        label: text.value.totalProfit,
        value: formatPrice(stats.value.revenue_total),
        hint: formatPrice(stats.value.revenue_today) + ' ' + text.value.today,
        icon: faWallet,
        tone: 'from-[#1a1a1a] to-wine-deep',
        panel: 'payments',
    },
]);

const salesChart = computed(() => {
    const series = salesTrend.value;
    const width = 640;
    const height = 220;
    const padX = 12;
    const padY = 16;
    const max = Math.max(...series.map((item) => Number(item.total || 0)), 1);
    const points = series.map((item, index) => {
        const x = padX + (series.length <= 1 ? 0 : (index / (series.length - 1)) * (width - padX * 2));
        const y = height - padY - (Number(item.total || 0) / max) * (height - padY * 2);

        return { ...item, x, y };
    });
    const line = points.map((point, index) => `${index === 0 ? 'M' : 'L'}${point.x.toFixed(1)} ${point.y.toFixed(1)}`).join(' ');
    const last = points[points.length - 1];
    const first = points[0];
    const area = points.length ? `${line} L${last.x.toFixed(1)} ${height} L${first.x.toFixed(1)} ${height} Z` : '';

    return { points, line, area, width, height };
});

const orderBars = computed(() => {
    const series = salesTrend.value;
    const max = Math.max(...series.map((item) => Number(item.orders_count || 0)), 1);

    return series.map((item) => ({
        ...item,
        height: Math.max(8, (Number(item.orders_count || 0) / max) * 100),
    }));
});
</script>

<template>
    <div class="space-y-4">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <button
                v-for="card in metricCards"
                :key="card.id"
                class="flex items-center gap-4 rounded-[1.15rem] bg-gradient-to-br p-4 text-left text-white shadow-[0_18px_32px_-22px_rgb(0_0_0_/_0.55)]"
                :class="card.tone"
                type="button"
                @click="emit('navigate', card.panel)"
            >
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/15 text-lg">
                    <FontAwesomeIcon :icon="card.icon" />
                </span>
                <span class="min-w-0">
                    <span class="block text-[11px] font-semibold text-white/75">{{ card.label }}</span>
                    <span class="mt-1 block truncate text-2xl font-semibold">{{ card.value }}</span>
                    <span class="mt-0.5 block truncate text-[11px] font-semibold text-white/60">{{ card.hint }}</span>
                </span>
            </button>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(280px,0.7fr)]">
            <article class="rounded-[1.2rem] border border-black/8 bg-white p-4 dark:border-white/10 dark:bg-white/8 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink dark:text-white">{{ text.productSales }}</h2>
                    <button class="grid size-8 place-items-center rounded-full border border-black/10 text-ink/55 transition hover:text-wine dark:border-white/10 dark:text-white/55" type="button" :disabled="isLoading" @click="emit('refresh')">
                        <FontAwesomeIcon :icon="faRotate" />
                    </button>
                </div>
                <div v-if="salesChart.points.length" class="h-52">
                    <svg class="h-full w-full" :viewBox="`0 0 ${salesChart.width} ${salesChart.height}`" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="admin-sales-fill" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#8f2638" stop-opacity="0.38" />
                                <stop offset="100%" stop-color="#8f2638" stop-opacity="0.02" />
                            </linearGradient>
                        </defs>
                        <path :d="salesChart.area" fill="url(#admin-sales-fill)"></path>
                        <path :d="salesChart.line" fill="none" stroke="#8f2638" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></path>
                    </svg>
                </div>
                <p v-else class="grid h-52 place-items-center text-sm font-semibold text-ink/45 dark:text-white/45">{{ text.empty }}</p>
                <div class="mt-2 grid grid-cols-12 gap-1 text-center text-[10px] font-semibold text-ink/45 dark:text-white/45">
                    <span v-for="point in salesChart.points" :key="point.key">{{ point.label }}</span>
                </div>
            </article>

            <article class="rounded-[1.2rem] border border-black/8 bg-white p-4 dark:border-white/10 dark:bg-white/8 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink dark:text-white">{{ text.recentBuyers }}</h2>
                    <button class="text-[11px] font-semibold text-wine" type="button" @click="emit('navigate', 'customers')">{{ text.invoiceSummary }}</button>
                </div>
                <div class="space-y-3">
                    <button
                        v-for="buyer in recentBuyers"
                        :key="buyer.id"
                        class="flex w-full items-center gap-3 rounded-2xl p-1 text-left transition hover:bg-ink/4 dark:hover:bg-white/8"
                        type="button"
                        @click="emit('navigate', 'customers')"
                    >
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-wine text-sm font-semibold text-white">{{ buyerInitial(buyer.name) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-ink dark:text-white">{{ buyer.name }}</span>
                            <span class="mt-0.5 inline-flex rounded-full bg-wine/10 px-2 py-0.5 text-[10px] font-semibold uppercase text-wine">{{ buyer.status }}</span>
                        </span>
                        <span class="shrink-0 text-sm font-semibold text-ink dark:text-white">{{ formatPrice(buyer.grand_total) }}</span>
                    </button>
                    <p v-if="recentBuyers.length === 0" class="py-8 text-center text-sm font-semibold text-ink/45 dark:text-white/45">{{ text.empty }}</p>
                </div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(280px,0.7fr)]">
            <article class="overflow-hidden rounded-[1.2rem] border border-black/8 bg-white dark:border-white/10 dark:bg-white/8">
                <div class="flex flex-col gap-2 border-b border-black/8 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-white/10">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink dark:text-white">{{ text.recentOrders }}</h2>
                        <p class="mt-1 text-xs font-semibold text-ink/50 dark:text-white/50">
                            {{ text.paidInvoices }} {{ formatNumber(paymentMix.paid) }}, {{ text.unpaidInvoices }} {{ formatNumber(paymentMix.unpaid) }}.
                        </p>
                    </div>
                    <button class="inline-flex items-center gap-2 text-sm font-semibold text-wine" type="button" @click="emit('navigate', 'payments')">
                        {{ text.invoiceSummary }}
                        <FontAwesomeIcon :icon="icons.arrowRight" />
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="bg-ink/[0.03] text-[11px] font-semibold uppercase text-ink/50 dark:bg-white/5 dark:text-white/50">
                            <tr>
                                <th class="px-5 py-3">{{ text.order }}</th>
                                <th class="px-5 py-3">{{ text.customer }}</th>
                                <th class="px-5 py-3">{{ text.status }}</th>
                                <th class="px-5 py-3">{{ text.payment }}</th>
                                <th class="px-5 py-3 text-right">{{ text.total }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/6 dark:divide-white/10">
                            <tr v-for="order in recentOrders" :key="order.id" class="font-semibold">
                                <td class="px-5 py-4">
                                    <p class="text-ink dark:text-white">{{ order.order_number }}</p>
                                    <p class="text-[11px] text-ink/45 dark:text-white/45">{{ formatDate(order.created_at) }}</p>
                                </td>
                                <td class="px-5 py-4 text-ink dark:text-white">{{ order.customer_name }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-wine/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-wine">{{ order.status }}</span></td>
                                <td class="px-5 py-4 uppercase text-ink/70 dark:text-white/70">{{ order.payment_status }}</td>
                                <td class="px-5 py-4 text-right text-ink dark:text-white">{{ formatPrice(order.grand_total) }}</td>
                            </tr>
                            <tr v-if="recentOrders.length === 0">
                                <td class="px-5 py-8 text-center text-sm font-semibold text-ink/45 dark:text-white/45" colspan="5">{{ text.empty }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="grid gap-4">
                <article class="rounded-[1.2rem] border border-black/8 bg-white p-4 dark:border-white/10 dark:bg-white/8 sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink dark:text-white">{{ text.newOrders }}</h2>
                        <button class="text-[11px] font-semibold text-wine" type="button" @click="emit('navigate', 'orders')">{{ text.invoiceSummary }}</button>
                    </div>
                    <div class="flex h-36 items-end gap-1.5">
                        <div v-for="bar in orderBars" :key="bar.key" class="flex min-w-0 flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t-md bg-wine/80" :style="{ height: bar.height + '%' }"></div>
                            <span class="text-[9px] font-semibold text-ink/40 dark:text-white/40">{{ bar.label.charAt(0) }}</span>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.2rem] border border-black/8 bg-white p-4 dark:border-white/10 dark:bg-white/8 sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink dark:text-white">{{ text.stockAlerts }}</h2>
                        <button class="text-[11px] font-semibold text-wine" type="button" @click="emit('navigate', 'inventory')">{{ formatNumber(stats.low_stock_variants) }}</button>
                    </div>
                    <div class="space-y-2">
                        <button
                            v-for="item in stockAlerts.slice(0, 3)"
                            :key="item.id"
                            class="flex w-full items-center justify-between gap-3 rounded-2xl bg-ink/4 px-3 py-2 text-left dark:bg-white/8"
                            type="button"
                            @click="emit('navigate', 'inventory')"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-ink dark:text-white">{{ item.product_name }}</span>
                                <span class="truncate text-[11px] font-semibold text-ink/45 dark:text-white/45">{{ item.sku }}</span>
                            </span>
                            <span class="rounded-full bg-wine/10 px-2.5 py-1 text-xs font-semibold text-wine">{{ item.stock_quantity }}</span>
                        </button>
                        <p v-if="stockAlerts.length === 0" class="py-3 text-sm font-semibold text-ink/45 dark:text-white/45">{{ text.empty }}</p>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
