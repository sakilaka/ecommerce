<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const orders = ref([]);
const selectedOrder = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', status: '', payment_status: '', payment_method: '', page: 1 });
const form = reactive({ status: 'pending', payment_status: 'unpaid', payment_method: 'cod' });
let searchTimer = null;

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'অর্ডার অপারেশন', title: 'অর্ডার ম্যানেজমেন্ট', subtitle: 'নতুন অর্ডার, COD, পেমেন্ট অবস্থা এবং ডেলিভারি ধাপ দ্রুত নিয়ন্ত্রণ করুন।', search: 'অর্ডার নম্বর, নাম, ফোন বা ইমেইল খুঁজুন', allStatus: 'সব অর্ডার', allPayment: 'সব পেমেন্ট', allMethod: 'সব মেথড', order: 'অর্ডার', customer: 'কাস্টমার', area: 'এলাকা', payment: 'পেমেন্ট', total: 'টোটাল', status: 'স্ট্যাটাস', action: 'দেখুন', save: 'আপডেট করুন', saving: 'আপডেট হচ্ছে...', close: 'বন্ধ করুন', items: 'আইটেম', summary: 'সামারি', subtotal: 'সাবটোটাল', discount: 'ডিসকাউন্ট', shipping: 'ডেলিভারি', grandTotal: 'গ্র্যান্ড টোটাল', note: 'কাস্টমার নোট', empty: 'কোনো অর্ডার পাওয়া যায়নি।', failed: 'অর্ডার লোড/আপডেট করা যায়নি।'
} : {
    eyebrow: 'Order Operations', title: 'Order Management', subtitle: 'Control new orders, COD flow, payment status, and delivery stages from one focused workspace.', search: 'Search order number, name, phone, or email', allStatus: 'All Orders', allPayment: 'All Payments', allMethod: 'All Methods', order: 'Order', customer: 'Customer', area: 'Area', payment: 'Payment', total: 'Total', status: 'Status', action: 'View', save: 'Update Order', saving: 'Updating...', close: 'Close', items: 'Items', summary: 'Summary', subtotal: 'Subtotal', discount: 'Discount', shipping: 'Delivery', grandTotal: 'Grand Total', note: 'Customer Note', empty: 'No orders found.', failed: 'Could not load or update orders.'
});

const orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
const paymentStatuses = ['pending', 'unpaid', 'paid', 'failed', 'refunded'];
const paymentMethods = ['cod', 'sslcommerz', 'bkash', 'nagad', 'card'];
const statusOptions = computed(() => [{ value: '', label: text.value.allStatus }, ...orderStatuses.map((value) => ({ value, label: value }))]);
const paymentStatusOptions = computed(() => [{ value: '', label: text.value.allPayment }, ...paymentStatuses.map((value) => ({ value, label: value }))]);
const paymentMethodOptions = computed(() => [{ value: '', label: text.value.allMethod }, ...paymentMethods.map((value) => ({ value, label: value }))]);

const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const badgeClass = (status) => ({ pending: 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]', confirmed: 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:text-sky-300', processing: 'bg-indigo-500/10 text-indigo-700 ring-indigo-500/20 dark:text-indigo-300', shipped: 'bg-purple-500/10 text-purple-700 ring-purple-500/20 dark:text-purple-300', delivered: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300', cancelled: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-red-300', returned: 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300' }[status] ?? 'bg-black/5 text-[#374151] ring-black/10 dark:bg-white/10 dark:text-white');

const loadOrders = async (page = filters.page) => {
    isLoading.value = true; error.value = null; filters.page = page;
    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    ['q', 'status', 'payment_status', 'payment_method'].forEach((key) => { if (String(filters[key]).trim()) params.set(key, String(filters[key]).trim()); });
    try {
        const response = await fetch('/api/admin/orders?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const payload = await response.json(); orders.value = payload.data ?? []; meta.value = payload.meta ?? meta.value;
    } catch (requestError) { error.value = text.value.failed; } finally { isLoading.value = false; }
};

const queueSearch = () => { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(() => loadOrders(1), 350); };
const openOrder = async (id) => {
    error.value = null;
    try {
        const response = await fetch('/api/admin/orders/' + id, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const payload = await response.json(); selectedOrder.value = payload.data; Object.assign(form, { status: selectedOrder.value.status, payment_status: selectedOrder.value.payment_status, payment_method: selectedOrder.value.payment_method });
    } catch (requestError) { error.value = text.value.failed; }
};
const updateOrder = async () => {
    if (!selectedOrder.value) return; isSaving.value = true; error.value = null;
    try {
        const response = await fetch('/api/admin/orders/' + selectedOrder.value.id, { method: 'PATCH', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, credentials: 'same-origin', body: JSON.stringify(form) });
        if (!response.ok) throw new Error('Request failed');
        const payload = await response.json(); selectedOrder.value = payload.data; await loadOrders(filters.page);
    } catch (requestError) { error.value = text.value.failed; } finally { isSaving.value = false; }
};

watch(() => [filters.status, filters.payment_status, filters.payment_method], () => loadOrders(1));
onMounted(() => loadOrders());
</script>

<template>
    <section class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
            <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
            <div class="mt-2 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between"><div><h2 class="text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2><p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p></div></div>
            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_170px_170px_170px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch"></label>
                <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                <select v-model="filters.payment_status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in paymentStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                <select v-model="filters.payment_method" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in paymentMethodOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
            </div>
        </div>
        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>
        <div class="overflow-x-auto"><table class="w-full min-w-[980px] text-left text-sm"><thead class="bg-[#f6f6f6] text-xs font-black uppercase text-[#4b5563] dark:bg-black/20 dark:text-[#d1d5db]"><tr><th class="px-5 py-3">{{ text.order }}</th><th class="px-5 py-3">{{ text.customer }}</th><th class="px-5 py-3">{{ text.area }}</th><th class="px-5 py-3">{{ text.payment }}</th><th class="px-5 py-3 text-right">{{ text.total }}</th><th class="px-5 py-3">{{ text.status }}</th><th class="px-5 py-3 text-right"></th></tr></thead><tbody class="divide-y divide-black/8 dark:divide-white/10"><tr v-if="isLoading"><td colspan="7" class="px-5 py-8 text-center font-black text-[#4b5563] dark:text-[#d1d5db]">Loading...</td></tr><tr v-else-if="orders.length === 0"><td colspan="7" class="px-5 py-8 text-center font-black text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</td></tr><template v-else><tr v-for="order in orders" :key="order.id" class="font-semibold transition hover:bg-[#f6f6f6]/70 dark:hover:bg-white/5"><td class="px-5 py-4"><p class="font-black">{{ order.order_number }}</p><p class="text-xs text-[#4b5563] dark:text-[#d1d5db]">{{ formatDate(order.created_at) }}</p></td><td class="px-5 py-4"><p class="font-black">{{ order.customer_name }}</p><p class="text-xs text-[#4b5563] dark:text-[#d1d5db]">{{ order.customer_phone }}</p></td><td class="px-5 py-4">{{ order.shipping_district }}<span v-if="order.shipping_area">, {{ order.shipping_area }}</span></td><td class="px-5 py-4"><p class="font-black uppercase">{{ order.payment_method }}</p><p class="text-xs uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ order.payment_status }}</p></td><td class="px-5 py-4 text-right font-black">{{ formatPrice(order.grand_total) }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="badgeClass(order.status)">{{ order.status }}</span></td><td class="px-5 py-4 text-right"><button class="inline-flex h-10 items-center gap-2 rounded-lg border border-black/10 px-3 text-xs font-black transition hover:bg-white dark:border-white/10 dark:hover:bg-white/10" type="button" @click="openOrder(order.id)"><FontAwesomeIcon :icon="icons.wand" />{{ text.action }}</button></td></tr></template></tbody></table></div>
        <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ orders.length }} / {{ meta.total }}</p><div class="flex items-center gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadOrders(meta.current_page - 1)">Prev</button><span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadOrders(meta.current_page + 1)">Next</button></div></div>
    </section>

    <div v-if="selectedOrder" class="fixed inset-0 z-50 overflow-y-auto bg-black/45 p-3 backdrop-blur-sm sm:p-6">
        <section class="mx-auto max-w-5xl rounded-lg bg-[#ffffff] shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10">
            <div class="flex items-start justify-between gap-3 border-b border-black/8 p-5 dark:border-white/10"><div><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ selectedOrder.order_number }}</p><h3 class="mt-1 text-xl font-black">{{ selectedOrder.customer_name }}</h3><p class="mt-1 text-sm font-semibold text-[#374151] dark:text-[#e5e7eb]">{{ selectedOrder.customer_phone }} · {{ selectedOrder.shipping_district }}<span v-if="selectedOrder.shipping_area">, {{ selectedOrder.shipping_area }}</span></p></div><button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="selectedOrder = null"><FontAwesomeIcon :icon="icons.xmark" /></button></div>
            <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-4"><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.items }}</h4><article v-for="item in selectedOrder.items" :key="item.id" class="flex gap-3 rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><span class="grid size-11 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0]"><FontAwesomeIcon :icon="icons.bag" /></span><div class="min-w-0 flex-1"><p class="font-black">{{ item.product_name }}</p><p class="mt-1 text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ item.variant_name || item.sku }} · x{{ item.quantity }}</p></div><p class="font-black">{{ formatPrice(item.line_total) }}</p></article><div v-if="selectedOrder.customer_note" class="rounded-lg bg-[#f6f6f6] p-4 text-sm font-semibold dark:bg-black/20"><p class="font-black">{{ text.note }}</p><p class="mt-1 text-[#374151] dark:text-[#e5e7eb]">{{ selectedOrder.customer_note }}</p></div></div>
                <aside class="space-y-4"><div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.status }}</h4><div class="mt-4 grid gap-3"><select v-model="form.status" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="status in orderStatuses" :key="status" :value="status">{{ status }}</option></select><select v-model="form.payment_status" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="status in paymentStatuses" :key="status" :value="status">{{ status }}</option></select><select v-model="form.payment_method" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="method in paymentMethods" :key="method" :value="method">{{ method }}</option></select><button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="isSaving" @click="updateOrder">{{ isSaving ? text.saving : text.save }}</button></div></div><div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.summary }}</h4><dl class="mt-4 space-y-2 text-sm font-bold"><div class="flex justify-between"><dt>{{ text.subtotal }}</dt><dd>{{ formatPrice(selectedOrder.subtotal) }}</dd></div><div class="flex justify-between"><dt>{{ text.discount }}</dt><dd>{{ formatPrice(selectedOrder.discount_total) }}</dd></div><div class="flex justify-between"><dt>{{ text.shipping }}</dt><dd>{{ formatPrice(selectedOrder.shipping_total) }}</dd></div><div class="flex justify-between border-t border-black/8 pt-3 text-base font-black dark:border-white/10"><dt>{{ text.grandTotal }}</dt><dd>{{ formatPrice(selectedOrder.grand_total) }}</dd></div></dl></div></aside>
            </div>
        </section>
    </div>
</template>
