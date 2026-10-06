<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const error = ref(null);
const report = ref(null);
const filters = reactive({ period: '30' });

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'রিপোর্টস',
    title: 'সেলস ও অপারেশন অ্যানালিটিক্স',
    subtitle: 'রেভিনিউ, অর্ডার, পেমেন্ট, টপ পণ্য, কুপন পারফরম্যান্স এবং স্টক রিস্ক এক জায়গায়।',
    days7: 'শেষ ৭ দিন',
    days30: 'শেষ ৩০ দিন',
    days90: 'শেষ ৯০ দিন',
    revenue: 'রেভিনিউ',
    orders: 'অর্ডার',
    aov: 'Average Order',
    deliveryRate: 'Delivery Rate',
    newCustomers: 'নতুন কাস্টমার',
    activeProducts: 'অ্যাক্টিভ পণ্য',
    lowStock: 'লো স্টক',
    previous: 'আগের পিরিয়ড',
    salesTrend: 'সেলস ট্রেন্ড',
    orderStatus: 'অর্ডার স্ট্যাটাস',
    paymentStatus: 'পেমেন্ট স্ট্যাটাস',
    topProducts: 'টপ পণ্য',
    stockRisk: 'স্টক রিস্ক',
    couponPerformance: 'কুপন পারফরম্যান্স',
    marketingInsights: 'মার্কেটিং ইনসাইটস',
    leadSources: 'Lead Sources',
    leadInterests: 'Lead Interests',
    popularSearches: 'Popular Searches',
    campaignSections: 'Active Campaign Sections',
    runningCoupons: 'Running Coupons',
    couponOrders: 'Coupon Orders',
    couponDiscount: 'Coupon Discount',
    newLeads: 'New Leads',
    activeLeads: 'Active Leads',
    campaignLive: 'Campaign Live',
    clicks: 'Clicks',
    units: 'ইউনিট',
    discount: 'ডিসকাউন্ট',
    empty: 'এই পিরিয়ডে ডাটা নেই।',
    unavailable: 'রিপোর্ট লোড করা যায়নি।',
} : {
    eyebrow: 'Reports',
    title: 'Sales & Operations Analytics',
    subtitle: 'Track revenue, orders, payments, top products, coupon performance, and stock risk from one admin view.',
    days7: 'Last 7 Days',
    days30: 'Last 30 Days',
    days90: 'Last 90 Days',
    revenue: 'Revenue',
    orders: 'Orders',
    aov: 'Average Order',
    deliveryRate: 'Delivery Rate',
    newCustomers: 'New Customers',
    activeProducts: 'Active Products',
    lowStock: 'Low Stock',
    previous: 'previous period',
    salesTrend: 'Sales Trend',
    orderStatus: 'Order Status',
    paymentStatus: 'Payment Status',
    topProducts: 'Top Products',
    stockRisk: 'Stock Risk',
    couponPerformance: 'Coupon Performance',
    marketingInsights: 'Marketing Insights',
    leadSources: 'Lead Sources',
    leadInterests: 'Lead Interests',
    popularSearches: 'Popular Searches',
    campaignSections: 'Active Campaign Sections',
    runningCoupons: 'Running Coupons',
    couponOrders: 'Coupon Orders',
    couponDiscount: 'Coupon Discount',
    newLeads: 'New Leads',
    activeLeads: 'Active Leads',
    campaignLive: 'Campaign Live',
    clicks: 'Clicks',
    units: 'Units',
    discount: 'Discount',
    empty: 'No data for this period.',
    unavailable: 'Could not load reports.',
});

const periodOptions = computed(() => [
    { value: '7', label: text.value.days7 },
    { value: '30', label: text.value.days30 },
    { value: '90', label: text.value.days90 },
]);

const summary = computed(() => report.value?.summary ?? {});
const salesTrend = computed(() => report.value?.sales_trend ?? []);
const orderStatus = computed(() => report.value?.order_status ?? []);
const paymentStatus = computed(() => report.value?.payment_status ?? []);
const topProducts = computed(() => report.value?.top_products ?? []);
const stockRisk = computed(() => report.value?.stock_risk ?? []);
const couponPerformance = computed(() => report.value?.coupon_performance ?? []);
const marketingInsights = computed(() => report.value?.marketing_insights ?? {});
const marketingSummary = computed(() => marketingInsights.value?.summary ?? {});
const leadSources = computed(() => marketingInsights.value?.lead_sources ?? []);
const leadInterests = computed(() => marketingInsights.value?.lead_interests ?? []);
const popularSearches = computed(() => marketingInsights.value?.popular_searches ?? []);
const campaignSections = computed(() => marketingInsights.value?.campaign_sections ?? []);
const maxRevenue = computed(() => Math.max(1, ...salesTrend.value.map((day) => Number(day.revenue || 0))));
const maxOrderStatus = computed(() => Math.max(1, ...orderStatus.value.map((item) => Number(item.total || 0))));
const maxPaymentStatus = computed(() => Math.max(1, ...paymentStatus.value.map((item) => Number(item.total || 0))));
const maxProductRevenue = computed(() => Math.max(1, ...topProducts.value.map((item) => Number(item.revenue || 0))));
const maxLeadSource = computed(() => Math.max(1, ...leadSources.value.map((item) => Number(item.total || 0))));
const maxLeadInterest = computed(() => Math.max(1, ...leadInterests.value.map((item) => Number(item.total || 0))));
const maxPopularSearch = computed(() => Math.max(1, ...popularSearches.value.map((item) => Number(item.click_count || 0))));

const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatPercent = (amount) => Number(amount || 0).toLocaleString('en-BD') + '%';
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short' }).format(new Date(value)) : '';
const barWidth = (value, max) => ({ width: Math.max(4, Math.round((Number(value || 0) / max) * 100)) + '%' });
const labelForSearch = (search) => locale.value === 'bn' ? (search.label_bn || search.label_en) : search.label_en;

const metricCards = computed(() => [
    { label: text.value.revenue, value: formatPrice(summary.value.revenue), sub: formatPercent(summary.value.revenue_change_percent) + ' ' + text.value.previous, icon: icons.chart, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
    { label: text.value.orders, value: formatNumber(summary.value.orders), sub: formatNumber(summary.value.delivered_orders) + ' delivered', icon: icons.bag, tone: 'bg-[#8f2638] text-white' },
    { label: text.value.aov, value: formatPrice(summary.value.average_order_value), sub: text.value.deliveryRate + ' ' + formatPercent(summary.value.delivery_rate_percent), icon: icons.award, tone: 'bg-[#9f243a] text-white' },
    { label: text.value.newCustomers, value: formatNumber(summary.value.new_customers), sub: text.value.activeProducts + ' ' + formatNumber(summary.value.active_products), icon: icons.users, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
]);

const marketingCards = computed(() => [
    { label: text.value.runningCoupons, value: formatNumber(marketingSummary.value.running_coupons), sub: formatNumber(marketingSummary.value.active_coupons) + ' active', icon: icons.fire, tone: 'bg-[#8f2638] text-white' },
    { label: text.value.couponOrders, value: formatNumber(marketingSummary.value.coupon_orders), sub: formatPercent(marketingSummary.value.coupon_order_rate_percent) + ' of orders', icon: icons.tags, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
    { label: text.value.couponDiscount, value: formatPrice(marketingSummary.value.coupon_discount_total), sub: text.value.discount, icon: icons.gift, tone: 'bg-[#9f243a] text-white' },
    { label: text.value.newLeads, value: formatNumber(marketingSummary.value.new_leads), sub: text.value.activeLeads + ' ' + formatNumber(marketingSummary.value.active_leads), icon: icons.heart, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
]);

const loadReports = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams({ period: filters.period });
        const response = await fetch('/api/admin/reports?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const payload = await response.json();
        report.value = payload.data;
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isLoading.value = false;
    }
};

watch(() => filters.period, () => loadReports());
onMounted(() => loadReports());
</script>

<template>
    <section class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#9f243a]">{{ text.eyebrow }}</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                    <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                </div>
                <select v-model="filters.period" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10 xl:w-48">
                    <option v-for="option in periodOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#9f243a]">{{ error }}</div>

        <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="card in metricCards" :key="card.label" class="rounded-lg p-5 shadow-lg shadow-black/5 ring-1 ring-black/5" :class="card.tone">
                <div class="flex items-center justify-between gap-3 text-xs font-black uppercase opacity-75"><span>{{ card.label }}</span><FontAwesomeIcon :icon="card.icon" /></div>
                <p class="mt-4 text-2xl font-black">{{ card.value }}</p>
                <p class="mt-1 text-xs font-bold opacity-70">{{ card.sub }}</p>
            </article>
        </div>

        <div class="grid gap-5 px-5 pb-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,0.8fr)]">
            <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <div class="flex items-center justify-between gap-3"><h3 class="text-lg font-black">{{ text.salesTrend }}</h3><span v-if="isLoading" class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">Loading...</span></div>
                <div class="mt-5 flex h-72 items-end gap-1 overflow-hidden rounded-lg bg-white p-3 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10">
                    <div v-for="day in salesTrend" :key="day.date" class="flex min-w-2 flex-1 flex-col items-center justify-end gap-2">
                        <div class="w-full rounded-t bg-[#8f2638] dark:bg-[#9f243a]" :style="{ height: Math.max(4, Math.round((Number(day.revenue || 0) / maxRevenue) * 220)) + 'px' }"></div>
                        <span class="hidden text-[10px] font-black text-[#4b5563] dark:text-[#d1d5db] sm:block">{{ formatDate(day.date) }}</span>
                    </div>
                </div>
            </section>

            <div class="grid gap-5">
                <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <h3 class="text-lg font-black">{{ text.orderStatus }}</h3>
                    <div class="mt-4 space-y-3"><div v-for="item in orderStatus" :key="item.status"><div class="mb-1 flex justify-between text-xs font-black uppercase"><span>{{ item.status }}</span><span>{{ formatNumber(item.total) }}</span></div><div class="h-2 rounded-full bg-black/8 dark:bg-white/10"><div class="h-2 rounded-full bg-[#8f2638] dark:bg-[#9f243a]" :style="barWidth(item.total, maxOrderStatus)"></div></div></div><p v-if="orderStatus.length === 0" class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p></div>
                </section>
                <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <h3 class="text-lg font-black">{{ text.paymentStatus }}</h3>
                    <div class="mt-4 space-y-3"><div v-for="item in paymentStatus" :key="item.status"><div class="mb-1 flex justify-between text-xs font-black uppercase"><span>{{ item.status }}</span><span>{{ formatNumber(item.total) }}</span></div><div class="h-2 rounded-full bg-black/8 dark:bg-white/10"><div class="h-2 rounded-full bg-[#9f243a]" :style="barWidth(item.total, maxPaymentStatus)"></div></div></div><p v-if="paymentStatus.length === 0" class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p></div>
                </section>
            </div>
        </div>

        <div class="px-5 pb-5">
            <section class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 ring-1 ring-black/10 dark:bg-white/10 dark:ring-white/10">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase text-[#9f243a]">{{ text.marketingInsights }}</p><h3 class="mt-1 text-xl font-black">{{ text.marketingInsights }}</h3></div><span class="text-xs font-bold text-white/60">{{ text.campaignLive }}: {{ formatNumber(marketingSummary.active_campaign_sections) }}</span></div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="card in marketingCards" :key="card.label" class="rounded-lg p-4 shadow-lg shadow-black/10 ring-1 ring-white/10" :class="card.tone"><div class="flex items-center justify-between gap-3 text-xs font-black uppercase opacity-75"><span>{{ card.label }}</span><FontAwesomeIcon :icon="card.icon" /></div><p class="mt-3 text-2xl font-black">{{ card.value }}</p><p class="mt-1 text-xs font-bold opacity-70">{{ card.sub }}</p></article>
                </div>
                <div class="mt-5 grid gap-4 xl:grid-cols-4">
                    <section class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><h4 class="text-sm font-black">{{ text.leadSources }}</h4><div class="mt-4 space-y-3"><div v-for="item in leadSources" :key="item.source"><div class="mb-1 flex justify-between text-xs font-black uppercase text-white/72"><span>{{ item.source }}</span><span>{{ formatNumber(item.total) }}</span></div><div class="h-2 rounded-full bg-white/10"><div class="h-2 rounded-full bg-[#9f243a]" :style="barWidth(item.total, maxLeadSource)"></div></div></div><p v-if="leadSources.length === 0" class="text-sm font-semibold text-white/60">{{ text.empty }}</p></div></section>
                    <section class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><h4 class="text-sm font-black">{{ text.leadInterests }}</h4><div class="mt-4 space-y-3"><div v-for="item in leadInterests" :key="item.interest"><div class="mb-1 flex justify-between text-xs font-black uppercase text-white/72"><span>{{ item.interest }}</span><span>{{ formatNumber(item.total) }}</span></div><div class="h-2 rounded-full bg-white/10"><div class="h-2 rounded-full bg-[#9f243a]" :style="barWidth(item.total, maxLeadInterest)"></div></div></div><p v-if="leadInterests.length === 0" class="text-sm font-semibold text-white/60">{{ text.empty }}</p></div></section>
                    <section class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><h4 class="text-sm font-black">{{ text.popularSearches }}</h4><div class="mt-4 space-y-2"><article v-for="search in popularSearches" :key="search.id" class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><div class="flex items-center justify-between gap-3"><p class="truncate text-sm font-black">{{ labelForSearch(search) }}</p><span class="text-xs font-black text-[#9f243a]">{{ formatNumber(search.click_count) }}</span></div><div class="mt-2 h-1.5 rounded-full bg-white/10"><div class="h-1.5 rounded-full bg-[#9f243a]" :style="barWidth(search.click_count, maxPopularSearch)"></div></div></article><p v-if="popularSearches.length === 0" class="text-sm font-semibold text-white/60">{{ text.empty }}</p></div></section>
                    <section class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><h4 class="text-sm font-black">{{ text.campaignSections }}</h4><div class="mt-4 space-y-2"><article v-for="section in campaignSections" :key="section.id" class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><div class="flex items-center justify-between gap-3"><p class="truncate text-sm font-black">{{ section.title || section.key }}</p><span class="text-[10px] font-black uppercase text-[#9f243a]">#{{ section.sort_order }}</span></div><p class="mt-1 text-xs font-bold text-white/55">{{ section.type }}</p></article><p v-if="campaignSections.length === 0" class="text-sm font-semibold text-white/60">{{ text.empty }}</p></div></section>
                </div>
            </section>
        </div>

        <div class="grid gap-5 px-5 pb-5 xl:grid-cols-3">
            <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <h3 class="text-lg font-black">{{ text.topProducts }}</h3>
                <div class="mt-4 space-y-3"><article v-for="product in topProducts" :key="product.product_id" class="rounded-lg bg-white p-3 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><div class="flex items-start justify-between gap-3"><p class="font-black">{{ product.product_name }}</p><p class="shrink-0 font-black">{{ formatPrice(product.revenue) }}</p></div><div class="mt-3 h-2 rounded-full bg-black/8 dark:bg-white/10"><div class="h-2 rounded-full bg-[#8f2638] dark:bg-[#9f243a]" :style="barWidth(product.revenue, maxProductRevenue)"></div></div><p class="mt-2 text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ formatNumber(product.units_sold) }} {{ text.units }}</p></article><p v-if="topProducts.length === 0" class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p></div>
            </section>
            <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <h3 class="text-lg font-black">{{ text.stockRisk }}</h3>
                <div class="mt-4 space-y-2"><article v-for="variant in stockRisk" :key="variant.id" class="flex items-center justify-between gap-3 rounded-lg bg-white p-3 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><div class="min-w-0"><p class="truncate font-black">{{ variant.product_name }}</p><p class="truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ variant.variant_name }} · {{ variant.sku }}</p></div><span class="rounded-full bg-[#8f2638]/10 px-2.5 py-1 text-xs font-black text-[#8f2638] ring-1 ring-[#8f2638]/20 dark:text-[#ffb4c1]">{{ formatNumber(variant.stock_quantity) }}</span></article><p v-if="stockRisk.length === 0" class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p></div>
            </section>
            <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <h3 class="text-lg font-black">{{ text.couponPerformance }}</h3>
                <div class="mt-4 space-y-2"><article v-for="coupon in couponPerformance" :key="coupon.id" class="rounded-lg bg-white p-3 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><div class="flex items-center justify-between gap-3"><p class="font-black">{{ coupon.code }}</p><span class="rounded-full bg-[#9f243a]/12 px-2 py-0.5 text-[10px] font-black uppercase text-[#8f2638] dark:text-[#9f243a]">{{ coupon.type }}</span></div><div class="mt-2 flex items-center justify-between text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]"><span>{{ formatNumber(coupon.orders_count) }} {{ text.orders }}</span><span>{{ formatPrice(coupon.discount_total) }} {{ text.discount }}</span></div></article><p v-if="couponPerformance.length === 0" class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p></div>
            </section>
        </div>
    </section>
</template>
