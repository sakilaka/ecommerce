<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const customers = ref([]);
const selectedCustomer = ref(null);
const isDetailOpen = ref(false);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
const filters = reactive({ q: '', active: '', page: 1 });
let searchTimer = null;

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'কাস্টমার ডেস্ক',
    title: 'কাস্টমার ম্যানেজমেন্ট',
    subtitle: 'কাস্টমার প্রোফাইল, অর্ডার হিস্ট্রি, ডেলিভারি ঠিকানা এবং অ্যাকাউন্ট স্ট্যাটাস দ্রুত দেখুন।',
    search: 'নাম, ইমেইল বা ফোন খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    active: 'অ্যাক্টিভ',
    inactive: 'ইনঅ্যাক্টিভ',
    customer: 'কাস্টমার',
    orders: 'অর্ডার',
    delivered: 'ডেলিভারড',
    spent: 'পেইড স্পেন্ড',
    addresses: 'ঠিকানা',
    joined: 'জয়েন করেছেন',
    status: 'স্ট্যাটাস',
    view: 'দেখুন',
    deactivate: 'ডিঅ্যাক্টিভ করুন',
    activate: 'অ্যাক্টিভ করুন',
    contact: 'কন্টাক্ট',
    recentOrders: 'সাম্প্রতিক অর্ডার',
    savedAddresses: 'সেভ করা ঠিকানা',
    defaultAddress: 'ডিফল্ট',
    close: 'বন্ধ করুন',
    empty: 'এই ফিল্টারে কোনো কাস্টমার পাওয়া যায়নি।',
    unavailable: 'কাস্টমার তালিকা লোড করা যায়নি।',
    saving: 'সেভ হচ্ছে...',
    showing: 'দেখানো হচ্ছে',
    of: 'মোট',
    previous: 'আগের',
    next: 'পরের',
} : {
    eyebrow: 'Customer Desk',
    title: 'Customer Management',
    subtitle: 'Review customer profiles, order history, delivery addresses, and account status from one admin workspace.',
    search: 'Search name, email, or phone',
    allStatus: 'All Status',
    active: 'Active',
    inactive: 'Inactive',
    customer: 'Customer',
    orders: 'Orders',
    delivered: 'Delivered',
    spent: 'Paid Spend',
    addresses: 'Addresses',
    joined: 'Joined',
    status: 'Status',
    view: 'View',
    deactivate: 'Deactivate',
    activate: 'Activate',
    contact: 'Contact',
    recentOrders: 'Recent Orders',
    savedAddresses: 'Saved Addresses',
    defaultAddress: 'Default',
    close: 'Close',
    empty: 'No customers found for this filter.',
    unavailable: 'Could not load customers.',
    saving: 'Saving...',
    showing: 'Showing',
    of: 'of',
    previous: 'Previous',
    next: 'Next',
});

const activeOptions = computed(() => [
    { value: '', label: text.value.allStatus },
    { value: '1', label: text.value.active },
    { value: '0', label: text.value.inactive },
]);

const stats = computed(() => {
    const active = customers.value.filter((customer) => customer.is_active).length;
    const inactive = customers.value.length - active;
    const orders = customers.value.reduce((sum, customer) => sum + Number(customer.orders_count || 0), 0);
    const spend = customers.value.reduce((sum, customer) => sum + Number(customer.paid_total || 0), 0);

    return [
        { label: text.value.active, value: formatNumber(active), icon: icons.users, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
        { label: text.value.inactive, value: formatNumber(inactive), icon: icons.shield, tone: 'bg-[#8f2638] text-white' },
        { label: text.value.orders, value: formatNumber(orders), icon: icons.bag, tone: 'bg-[#9f243a] text-white' },
        { label: text.value.spent, value: formatPrice(spend), icon: icons.chart, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
    ];
});

const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
const initials = (name) => String(name || 'C').split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase();

const statusClass = (customer) => customer.is_active
    ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300'
    : 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:bg-[#8f2638]/25 dark:text-[#ffb4c1]';

const orderStatusClass = (status) => ({
    delivered: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300',
    cancelled: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-[#ffb4c1]',
    returned: 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300',
}[status] ?? 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]');

const loadCustomers = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '15', page: String(page) });
    if (filters.q.trim()) {
        params.set('q', filters.q.trim());
    }
    if (filters.active !== '') {
        params.set('active', filters.active);
    }

    try {
        const response = await fetch('/api/admin/customers?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Request failed');
        }
        const payload = await response.json();
        customers.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadCustomers(1), 350);
};

const openCustomer = async (customer) => {
    error.value = null;
    try {
        const response = await fetch('/api/admin/customers/' + customer.id, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Request failed');
        }
        const payload = await response.json();
        selectedCustomer.value = payload.data;
        isDetailOpen.value = true;
    } catch (requestError) {
        error.value = text.value.unavailable;
    }
};

const setCustomerStatus = async (customer, isActive) => {
    isSaving.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/admin/customers/' + customer.id, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ is_active: isActive }),
        });
        if (!response.ok) {
            throw new Error('Request failed');
        }
        const payload = await response.json();
        customers.value = customers.value.map((item) => item.id === payload.data.id ? payload.data : item);
        if (selectedCustomer.value?.id === payload.data.id) {
            selectedCustomer.value = { ...selectedCustomer.value, ...payload.data };
        }
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isSaving.value = false;
    }
};

watch(() => filters.active, () => loadCustomers(1));
onMounted(() => loadCustomers());
</script>

<template>
    <section class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                    <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:w-[620px]">
                    <article v-for="card in stats" :key="card.label" class="rounded-lg p-3 shadow-lg shadow-black/5 ring-1 ring-black/5" :class="card.tone">
                        <div class="flex items-center justify-between gap-2 text-[10px] font-black uppercase opacity-75"><span>{{ card.label }}</span><FontAwesomeIcon :icon="card.icon" /></div>
                        <p class="mt-2 text-lg font-black">{{ card.value }}</p>
                    </article>
                </div>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_210px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                    <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                </label>
                <select v-model="filters.active" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                    <option v-for="option in activeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

        <div class="hidden overflow-x-auto xl:block">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-[#f6f6f6] text-xs font-black uppercase text-[#4b5563] dark:bg-black/20 dark:text-[#d1d5db]">
                    <tr><th class="px-5 py-3">{{ text.customer }}</th><th class="px-5 py-3">{{ text.contact }}</th><th class="px-5 py-3 text-right">{{ text.orders }}</th><th class="px-5 py-3 text-right">{{ text.spent }}</th><th class="px-5 py-3 text-right">{{ text.addresses }}</th><th class="px-5 py-3">{{ text.joined }}</th><th class="px-5 py-3">{{ text.status }}</th><th class="px-5 py-3 text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-black/8 dark:divide-white/10">
                    <tr v-for="customer in customers" :key="customer.id" class="font-semibold">
                        <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-lg bg-[#171512] text-sm font-black text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white">{{ initials(customer.name) }}</span><div class="min-w-0"><p class="truncate font-black">{{ customer.name }}</p><p class="truncate text-xs text-[#4b5563] dark:text-[#d1d5db]">ID #{{ customer.id }}</p></div></div></td>
                        <td class="px-5 py-4"><p>{{ customer.phone ?? '-' }}</p><p class="text-xs text-[#4b5563] dark:text-[#d1d5db]">{{ customer.email ?? '-' }}</p></td>
                        <td class="px-5 py-4 text-right font-black">{{ formatNumber(customer.orders_count) }}</td>
                        <td class="px-5 py-4 text-right font-black">{{ formatPrice(customer.paid_total) }}</td>
                        <td class="px-5 py-4 text-right">{{ formatNumber(customer.addresses_count) }}</td>
                        <td class="px-5 py-4">{{ formatDate(customer.created_at) }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-black ring-1" :class="statusClass(customer)">{{ customer.is_active ? text.active : text.inactive }}</span></td>
                        <td class="px-5 py-4 text-right"><div class="flex justify-end gap-2"><button class="h-10 rounded-lg border border-black/10 px-3 text-xs font-black dark:border-white/10" type="button" @click="openCustomer(customer)">{{ text.view }}</button><button class="h-10 rounded-lg bg-[#171512] px-3 text-xs font-black text-white disabled:opacity-50 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="isSaving" @click="setCustomerStatus(customer, !customer.is_active)">{{ customer.is_active ? text.deactivate : text.activate }}</button></div></td>
                    </tr>
                    <tr v-if="customers.length === 0 && !isLoading"><td class="px-5 py-10 text-center text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]" colspan="8">{{ text.empty }}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 p-5 xl:hidden">
            <article v-for="customer in customers" :key="customer.id" class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <div class="flex items-start gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-lg bg-[#171512] text-sm font-black text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white">{{ initials(customer.name) }}</span><div class="min-w-0 flex-1"><p class="truncate text-base font-black">{{ customer.name }}</p><p class="truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ customer.phone ?? customer.email }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-black ring-1" :class="statusClass(customer)">{{ customer.is_active ? text.active : text.inactive }}</span></div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-lg bg-white p-2 dark:bg-white/8"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.orders }}</p><p class="mt-1 font-black">{{ formatNumber(customer.orders_count) }}</p></div><div class="rounded-lg bg-white p-2 dark:bg-white/8"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.spent }}</p><p class="mt-1 font-black">{{ formatPrice(customer.paid_total) }}</p></div><div class="rounded-lg bg-white p-2 dark:bg-white/8"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.addresses }}</p><p class="mt-1 font-black">{{ formatNumber(customer.addresses_count) }}</p></div></div>
                <div class="mt-4 grid grid-cols-2 gap-2"><button class="h-11 rounded-lg border border-black/10 bg-white text-sm font-black dark:border-white/10 dark:bg-white/8" type="button" @click="openCustomer(customer)">{{ text.view }}</button><button class="h-11 rounded-lg bg-[#171512] text-sm font-black text-white disabled:opacity-50 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="isSaving" @click="setCustomerStatus(customer, !customer.is_active)">{{ customer.is_active ? text.deactivate : text.activate }}</button></div>
            </article>
            <p v-if="customers.length === 0 && !isLoading" class="rounded-lg bg-[#f6f6f6] px-4 py-8 text-center text-sm font-semibold text-[#4b5563] dark:bg-black/20 dark:text-[#d1d5db]">{{ text.empty }}</p>
        </div>

        <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.showing }} {{ formatNumber(customers.length) }} {{ text.of }} {{ formatNumber(meta.total) }}</p>
            <div class="flex gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page <= 1 || isLoading" @click="loadCustomers(meta.current_page - 1)">{{ text.previous }}</button><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page >= meta.last_page || isLoading" @click="loadCustomers(meta.current_page + 1)">{{ text.next }}</button></div>
        </div>

        <div v-if="isDetailOpen" class="fixed inset-0 z-50 grid place-items-center bg-black/55 p-4 backdrop-blur-sm">
            <section class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-lg bg-[#f9fafb] p-5 shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.customer }}</p><h3 class="mt-1 text-2xl font-black">{{ selectedCustomer?.name }}</h3><p class="mt-1 text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ selectedCustomer?.phone }} · {{ selectedCustomer?.email }}</p></div><button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="isDetailOpen = false"><FontAwesomeIcon :icon="icons.xmark" /></button></div>
                <div class="mt-5 grid gap-3 sm:grid-cols-4"><div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.orders }}</p><p class="mt-1 text-2xl font-black">{{ formatNumber(selectedCustomer?.orders_count) }}</p></div><div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.delivered }}</p><p class="mt-1 text-2xl font-black">{{ formatNumber(selectedCustomer?.delivered_orders_count) }}</p></div><div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.spent }}</p><p class="mt-1 text-2xl font-black">{{ formatPrice(selectedCustomer?.paid_total) }}</p></div><div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.status }}</p><p class="mt-1 text-2xl font-black">{{ selectedCustomer?.is_active ? text.active : text.inactive }}</p></div></div>
                <div class="mt-5 grid gap-5 lg:grid-cols-[1.2fr_0.8fr]"><section class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><h4 class="text-lg font-black">{{ text.recentOrders }}</h4><div class="mt-3 space-y-2"><article v-for="order in selectedCustomer?.orders ?? []" :key="order.id" class="flex items-center justify-between gap-3 rounded-lg bg-[#f6f6f6] p-3 dark:bg-black/20"><div><p class="font-black">{{ order.order_number }}</p><p class="text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ formatDate(order.created_at) }} · {{ order.shipping_district }}</p></div><div class="text-right"><p class="font-black">{{ formatPrice(order.grand_total) }}</p><span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase ring-1" :class="orderStatusClass(order.status)">{{ order.status }}</span></div></article><p v-if="!(selectedCustomer?.orders ?? []).length" class="py-5 text-center text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p></div></section><section class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><h4 class="text-lg font-black">{{ text.savedAddresses }}</h4><div class="mt-3 space-y-2"><article v-for="address in selectedCustomer?.addresses ?? []" :key="address.id" class="rounded-lg bg-[#f6f6f6] p-3 dark:bg-black/20"><div class="flex items-start justify-between gap-2"><p class="font-black">{{ address.label || address.district }}</p><span v-if="address.is_default" class="rounded-full bg-[#9f243a]/12 px-2 py-0.5 text-[10px] font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.defaultAddress }}</span></div><p class="mt-1 text-sm font-semibold text-[#374151] dark:text-[#e5e7eb]">{{ address.address_line }}</p><p class="mt-1 text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ address.area }}, {{ address.district }} · {{ address.phone }}</p></article><p v-if="!(selectedCustomer?.addresses ?? []).length" class="py-5 text-center text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.empty }}</p></div></section></div>
            </section>
        </div>
    </section>
</template>
