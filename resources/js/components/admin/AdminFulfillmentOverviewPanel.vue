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
    eyebrow: 'ফুলফিলমেন্ট রিপোর্ট',
    title: 'ডেলিভারি পারফরম্যান্স ও ঝুঁকি',
    subtitle: 'কুরিয়ার performance, delayed shipment, COD exposure, return pickup এবং attention queue এক জায়গায় দেখুন।',
    days7: 'শেষ ৭ দিন',
    days30: 'শেষ ৩০ দিন',
    days90: 'শেষ ৯০ দিন',
    shipments: 'শিপমেন্ট',
    open: 'ওপেন',
    delivered: 'ডেলিভার্ড',
    delayed: 'ডিলেইড',
    failed: 'Failed',
    returnPickups: 'রিটার্ন পিকআপ',
    codPending: 'COD Pending',
    deliveryRate: 'ডেলিভারি রেট',
    courierPerformance: 'কুরিয়ার পারফরম্যান্স',
    districtLoad: 'জেলা ভিত্তিক লোড',
    dailyTrend: 'ডেইলি ফুলফিলমেন্ট',
    attentionQueue: 'Attention Queue',
    total: 'মোট',
    empty: 'ডাটা নেই।',
    unavailable: 'ফুলফিলমেন্ট রিপোর্ট লোড করা যায়নি।',
    failed_delivery: 'ডেলিভারি failed',
    delivery_delayed: 'ডেলিভারি delayed',
    tracking_missing: 'Tracking missing',
} : {
    eyebrow: 'Fulfillment Report',
    title: 'Delivery performance and risk',
    subtitle: 'Track courier performance, delayed shipments, COD exposure, return pickups, and the admin attention queue in one view.',
    days7: 'Last 7 Days',
    days30: 'Last 30 Days',
    days90: 'Last 90 Days',
    shipments: 'Shipments',
    open: 'Open',
    delivered: 'Delivered',
    delayed: 'Delayed',
    failed: 'Failed',
    returnPickups: 'Return Pickups',
    codPending: 'COD Pending',
    deliveryRate: 'Delivery Rate',
    courierPerformance: 'Courier Performance',
    districtLoad: 'District Load',
    dailyTrend: 'Daily Fulfillment',
    attentionQueue: 'Attention Queue',
    total: 'Total',
    empty: 'No data yet.',
    unavailable: 'Could not load fulfillment report.',
    failed_delivery: 'Delivery failed',
    delivery_delayed: 'Delivery delayed',
    tracking_missing: 'Tracking missing',
});

const periodOptions = computed(() => [
    { value: '7', label: text.value.days7 },
    { value: '30', label: text.value.days30 },
    { value: '90', label: text.value.days90 },
]);

const summary = computed(() => report.value?.summary ?? {});
const dailyFulfillment = computed(() => report.value?.daily_fulfillment ?? []);
const courierPerformance = computed(() => report.value?.courier_performance ?? []);
const districtLoad = computed(() => report.value?.district_load ?? []);
const attentionQueue = computed(() => report.value?.attention_queue ?? []);
const maxDaily = computed(() => Math.max(1, ...dailyFulfillment.value.map((day) => Math.max(Number(day.created || 0), Number(day.delivered || 0)))));
const maxCourier = computed(() => Math.max(1, ...courierPerformance.value.map((item) => Number(item.total || 0))));
const maxDistrict = computed(() => Math.max(1, ...districtLoad.value.map((item) => Number(item.total || 0))));

const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatPercent = (amount) => Number(amount || 0).toLocaleString('en-BD') + '%';
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short' }).format(new Date(value)) : '-';
const barWidth = (value, max) => ({ width: Math.max(4, Math.round((Number(value || 0) / max) * 100)) + '%' });
const reasonLabel = (reason) => text.value[reason] ?? reason;
const reasonTone = (reason) => ({ failed_delivery: 'bg-[#8f2638]/10 text-[#8f2638] dark:bg-red-400/12 dark:text-red-300', delivery_delayed: 'bg-[#9f243a]/12 text-[#8f2638] dark:bg-[#9f243a]/12 dark:text-[#9f243a]', tracking_missing: 'bg-sky-500/10 text-sky-700 dark:bg-sky-400/12 dark:text-sky-300' }[reason] ?? 'bg-black/5 text-[#374151] dark:bg-white/10 dark:text-white');

const metricCards = computed(() => [
    { label: text.value.shipments, value: formatNumber(summary.value.shipments), sub: text.value.open + ' ' + formatNumber(summary.value.open_shipments), icon: icons.truck, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
    { label: text.value.delivered, value: formatNumber(summary.value.delivered_shipments), sub: text.value.deliveryRate + ' ' + formatPercent(summary.value.delivery_rate_percent), icon: icons.shield, tone: 'bg-[#8f2638] text-white' },
    { label: text.value.delayed, value: formatNumber(summary.value.delayed_shipments), sub: text.value.failed + ' ' + formatNumber(summary.value.failed_shipments), icon: icons.clock, tone: 'bg-[#8f2638] text-white' },
    { label: text.value.codPending, value: formatPrice(summary.value.cod_pending), sub: text.value.returnPickups + ' ' + formatNumber(summary.value.return_pickups), icon: icons.cart, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
]);

const loadReport = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams({ period: filters.period });
        const response = await fetch('/api/admin/fulfillment-reports?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const payload = await response.json();
        report.value = payload.data;
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isLoading.value = false;
    }
};

watch(() => filters.period, () => loadReport());
onMounted(() => loadReport());
</script>

<template>
    <section class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#9f243a]">{{ text.eyebrow }}</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                    <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
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

        <div class="grid gap-5 px-5 pb-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(360px,0.8fr)]">
            <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <div class="flex items-center justify-between gap-3"><h3 class="text-lg font-black">{{ text.dailyTrend }}</h3><span v-if="isLoading" class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">Loading...</span></div>
                <div class="mt-5 flex h-64 items-end gap-1 overflow-hidden rounded-lg bg-white p-3 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10">
                    <div v-for="day in dailyFulfillment" :key="day.date" class="flex min-w-2 flex-1 flex-col items-center justify-end gap-1">
                        <div class="grid w-full grid-cols-2 items-end gap-0.5">
                            <span class="block rounded-t bg-[#8f2638]" :style="{ height: Math.max(4, Math.round((Number(day.created || 0) / maxDaily) * 190)) + 'px' }"></span>
                            <span class="block rounded-t bg-[#9f243a] dark:bg-[#9f243a]" :style="{ height: Math.max(4, Math.round((Number(day.delivered || 0) / maxDaily) * 190)) + 'px' }"></span>
                        </div>
                        <span class="hidden text-[10px] font-black text-[#4b5563] dark:text-[#d1d5db] sm:block">{{ formatDate(day.date) }}</span>
                    </div>
                </div>
            </section>

            <section class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
                <h3 class="text-lg font-black">{{ text.attentionQueue }}</h3>
                <div class="mt-4 grid gap-3">
                    <article v-for="shipment in attentionQueue" :key="shipment.id" class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black">{{ shipment.order_number || '#' + shipment.id }}</p>
                                <p class="mt-1 truncate text-xs font-semibold text-white/60">{{ shipment.recipient_name }} · {{ shipment.recipient_phone }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-black uppercase" :class="reasonTone(shipment.reason)">{{ reasonLabel(shipment.reason) }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-bold text-white/62">
                            <span>{{ shipment.status }}</span>
                            <span>{{ shipment.courier_name || 'Unassigned' }}</span>
                            <span>{{ shipment.recipient_district }}</span>
                            <span>{{ formatDate(shipment.estimated_delivery_at) }}</span>
                        </div>
                    </article>
                    <p v-if="attentionQueue.length === 0" class="rounded-lg bg-white/8 p-4 text-sm font-semibold text-white/65 ring-1 ring-white/10">{{ text.empty }}</p>
                </div>
            </section>
        </div>

        <div class="grid gap-5 px-5 pb-5 xl:grid-cols-2">
            <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <h3 class="text-lg font-black">{{ text.courierPerformance }}</h3>
                <div class="mt-4 grid gap-3">
                    <article v-for="courier in courierPerformance" :key="courier.courier" class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10">
                        <div class="flex items-start justify-between gap-3"><p class="font-black">{{ courier.courier }}</p><span class="text-sm font-black">{{ formatNumber(courier.total) }}</span></div>
                        <div class="mt-3 h-2 rounded-full bg-black/8 dark:bg-white/10"><div class="h-2 rounded-full bg-[#8f2638] dark:bg-[#9f243a]" :style="barWidth(courier.total, maxCourier)"></div></div>
                        <div class="mt-3 grid grid-cols-4 gap-2 text-center text-[11px] font-bold text-[#374151] dark:text-[#e5e7eb]"><span>{{ text.delivered }}<b class="block text-sm text-[#171512] dark:text-white">{{ courier.delivered }}</b></span><span>{{ text.failed }}<b class="block text-sm text-[#171512] dark:text-white">{{ courier.failed }}</b></span><span>{{ text.delayed }}<b class="block text-sm text-[#171512] dark:text-white">{{ courier.delayed }}</b></span><span>COD<b class="block text-sm text-[#171512] dark:text-white">{{ formatPrice(courier.cod_amount) }}</b></span></div>
                    </article>
                    <p v-if="courierPerformance.length === 0" class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p>
                </div>
            </section>

            <section class="rounded-lg bg-[#ffffff] p-5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <h3 class="text-lg font-black">{{ text.districtLoad }}</h3>
                <div class="mt-4 grid gap-3">
                    <article v-for="district in districtLoad" :key="district.district" class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10">
                        <div class="flex items-start justify-between gap-3"><p class="font-black">{{ district.district }}</p><span class="text-sm font-black">{{ formatNumber(district.open) }} {{ text.open }}</span></div>
                        <div class="mt-3 h-2 rounded-full bg-black/8 dark:bg-white/10"><div class="h-2 rounded-full bg-[#9f243a]" :style="barWidth(district.total, maxDistrict)"></div></div>
                        <p class="mt-2 text-xs font-bold text-[#374151] dark:text-[#e5e7eb]">{{ text.total }} {{ formatNumber(district.total) }} · {{ text.delayed }} {{ formatNumber(district.delayed) }}</p>
                    </article>
                    <p v-if="districtLoad.length === 0" class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p>
                </div>
            </section>
        </div>
    </section>
</template>
