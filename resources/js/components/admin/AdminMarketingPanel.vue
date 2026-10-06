<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const coupons = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 12 });
const filters = reactive({ q: '', status: '', type: '', page: 1 });
const isLoading = ref(false);
const isSaving = ref(false);
const isFormOpen = ref(false);
const editingId = ref(null);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
let searchTimer = null;

const emptyForm = () => ({
    code: '',
    type: 'percent',
    value: '',
    minimum_order_amount: 0,
    maximum_discount_amount: '',
    usage_limit: '',
    used_count: 0,
    per_customer_limit: 1,
    starts_at: '',
    ends_at: '',
    is_active: true,
});

const form = reactive(emptyForm());

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'মার্কেটিং কন্ট্রোল',
    title: 'কুপন ও অফার ম্যানেজমেন্ট',
    subtitle: 'চেকআউটে ব্যবহারযোগ্য কুপন, ডিসকাউন্ট রুল, সময়সীমা এবং ব্যবহার সীমা নিয়ন্ত্রণ করুন।',
    add: 'নতুন কুপন',
    edit: 'কুপন আপডেট',
    search: 'কুপন কোড খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    running: 'চলছে',
    active: 'অ্যাক্টিভ',
    inactive: 'ইনঅ্যাক্টিভ',
    expired: 'শেষ',
    allTypes: 'সব টাইপ',
    percent: 'পারসেন্ট',
    fixed: 'ফিক্সড',
    coupon: 'কুপন',
    discount: 'ডিসকাউন্ট',
    minOrder: 'মিন অর্ডার',
    maxDiscount: 'ম্যাক্স ছাড়',
    usage: 'ব্যবহার',
    orders: 'অর্ডার',
    revenue: 'ডিসকাউন্ট দেওয়া',
    period: 'সময়',
    status: 'স্ট্যাটাস',
    save: 'সেভ করুন',
    saving: 'সেভ হচ্ছে...',
    close: 'বন্ধ করুন',
    code: 'কোড',
    type: 'টাইপ',
    value: 'ভ্যালু',
    usageLimit: 'Usage limit',
    usedCount: 'Used count',
    perCustomer: 'Per customer limit',
    startsAt: 'শুরু',
    endsAt: 'শেষ',
    unlimited: 'Unlimited',
    empty: 'এই ফিল্টারে কোনো কুপন পাওয়া যায়নি।',
    unavailable: 'কুপন লোড/সেভ করা যায়নি।',
    showing: 'দেখানো হচ্ছে',
    of: 'মোট',
    previous: 'আগের',
    next: 'পরের',
} : {
    eyebrow: 'Marketing Control',
    title: 'Coupon & Offer Management',
    subtitle: 'Control checkout-ready coupons, discount rules, campaign timing, usage limits, and active status.',
    add: 'Add Coupon',
    edit: 'Update Coupon',
    search: 'Search coupon code',
    allStatus: 'All Status',
    running: 'Running',
    active: 'Active',
    inactive: 'Inactive',
    expired: 'Expired',
    allTypes: 'All Types',
    percent: 'Percent',
    fixed: 'Fixed',
    coupon: 'Coupon',
    discount: 'Discount',
    minOrder: 'Min Order',
    maxDiscount: 'Max Discount',
    usage: 'Usage',
    orders: 'Orders',
    revenue: 'Discount Given',
    period: 'Period',
    status: 'Status',
    save: 'Save',
    saving: 'Saving...',
    close: 'Close',
    code: 'Code',
    type: 'Type',
    value: 'Value',
    usageLimit: 'Usage limit',
    usedCount: 'Used count',
    perCustomer: 'Per customer limit',
    startsAt: 'Starts at',
    endsAt: 'Ends at',
    unlimited: 'Unlimited',
    empty: 'No coupons found for this filter.',
    unavailable: 'Could not load or save coupons.',
    showing: 'Showing',
    of: 'of',
    previous: 'Previous',
    next: 'Next',
});

const statusOptions = computed(() => [
    { value: '', label: text.value.allStatus },
    { value: 'running', label: text.value.running },
    { value: 'active', label: text.value.active },
    { value: 'inactive', label: text.value.inactive },
    { value: 'expired', label: text.value.expired },
]);

const typeOptions = computed(() => [
    { value: '', label: text.value.allTypes },
    { value: 'percent', label: text.value.percent },
    { value: 'fixed', label: text.value.fixed },
]);

const formTypeOptions = computed(() => typeOptions.value.filter((option) => option.value));
const modalTitle = computed(() => editingId.value ? text.value.edit : text.value.add);

const stats = computed(() => {
    const running = coupons.value.filter((coupon) => coupon.is_running).length;
    const active = coupons.value.filter((coupon) => coupon.is_active).length;
    const orders = coupons.value.reduce((sum, coupon) => sum + Number(coupon.orders_count || 0), 0);
    const discount = coupons.value.reduce((sum, coupon) => sum + Number(coupon.orders_sum_discount_total || 0), 0);

    return [
        { label: text.value.running, value: formatNumber(running), icon: icons.fire, tone: 'bg-[#8f2638] text-white' },
        { label: text.value.active, value: formatNumber(active), icon: icons.tags, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
        { label: text.value.orders, value: formatNumber(orders), icon: icons.bag, tone: 'bg-[#9f243a] text-white' },
        { label: text.value.revenue, value: formatPrice(discount), icon: icons.chart, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
    ];
});

const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value)) : text.value.unlimited;
const toTaka = (amount) => amount === null || amount === undefined || amount === '' ? '' : Number(amount) / 100;
const toMinor = (amount) => amount === '' || amount === null || amount === undefined ? null : Math.round(Number(amount) * 100);
const isoToInput = (value) => value ? String(value).slice(0, 16) : '';
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';

const couponValue = (coupon) => coupon.type === 'percent' ? coupon.value + '%' : formatPrice(coupon.value);
const usageLabel = (coupon) => coupon.usage_limit ? coupon.used_count + ' / ' + coupon.usage_limit : coupon.used_count + ' / ' + text.value.unlimited;
const statusLabel = (coupon) => {
    if (!coupon.is_active) return text.value.inactive;
    if (coupon.is_running) return text.value.running;
    return text.value.active;
};
const statusClass = (coupon) => {
    if (!coupon.is_active) return 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300';
    if (coupon.is_running) return 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300';
    return 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]';
};

const resetForm = () => {
    Object.assign(form, emptyForm());
    editingId.value = null;
    fieldErrors.value = {};
    formError.value = null;
};

const fillForm = (coupon) => {
    resetForm();
    editingId.value = coupon.id;
    form.code = coupon.code;
    form.type = coupon.type;
    form.value = coupon.type === 'fixed' ? toTaka(coupon.value) : coupon.value;
    form.minimum_order_amount = toTaka(coupon.minimum_order_amount) || 0;
    form.maximum_discount_amount = toTaka(coupon.maximum_discount_amount);
    form.usage_limit = coupon.usage_limit ?? '';
    form.used_count = coupon.used_count ?? 0;
    form.per_customer_limit = coupon.per_customer_limit ?? '';
    form.starts_at = isoToInput(coupon.starts_at);
    form.ends_at = isoToInput(coupon.ends_at);
    form.is_active = Boolean(coupon.is_active);
};

const payload = () => ({
    code: String(form.code).trim().toUpperCase(),
    type: form.type,
    value: form.type === 'fixed' ? toMinor(form.value) : Number(form.value),
    minimum_order_amount: toMinor(form.minimum_order_amount) ?? 0,
    maximum_discount_amount: toMinor(form.maximum_discount_amount),
    usage_limit: form.usage_limit === '' ? null : Number(form.usage_limit),
    used_count: form.used_count === '' ? 0 : Number(form.used_count),
    per_customer_limit: form.per_customer_limit === '' ? null : Number(form.per_customer_limit),
    starts_at: form.starts_at || null,
    ends_at: form.ends_at || null,
    is_active: form.is_active,
});

const loadCoupons = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);
    if (filters.type) params.set('type', filters.type);

    try {
        const response = await fetch('/api/admin/coupons?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const data = await response.json();
        coupons.value = data.data ?? [];
        meta.value = data.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadCoupons(1), 350);
};

const openCreate = () => {
    resetForm();
    isFormOpen.value = true;
};

const openEdit = (coupon) => {
    fillForm(coupon);
    isFormOpen.value = true;
};

const saveCoupon = async () => {
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch(editingId.value ? '/api/admin/coupons/' + editingId.value : '/api/admin/coupons', {
            method: editingId.value ? 'PATCH' : 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify(payload()),
        });
        const data = await response.json();
        if (!response.ok) {
            fieldErrors.value = data.errors ?? {};
            throw new Error('Save failed');
        }
        isFormOpen.value = false;
        await loadCoupons(editingId.value ? filters.page : 1);
    } catch (requestError) {
        formError.value = text.value.unavailable;
    } finally {
        isSaving.value = false;
    }
};

watch(() => [filters.status, filters.type], () => loadCoupons(1));
onMounted(() => loadCoupons());
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
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#171512] px-4 text-sm font-black text-white shadow-lg shadow-black/12 dark:bg-[#9f243a] dark:text-white" type="button" @click="openCreate"><FontAwesomeIcon :icon="icons.plus" />{{ text.add }}</button>
            </div>
            <div class="mt-5 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="card in stats" :key="card.label" class="rounded-lg p-4 shadow-lg shadow-black/5 ring-1 ring-black/5" :class="card.tone"><div class="flex items-center justify-between gap-2 text-[10px] font-black uppercase opacity-75"><span>{{ card.label }}</span><FontAwesomeIcon :icon="card.icon" /></div><p class="mt-2 text-xl font-black">{{ card.value }}</p></article>
            </div>
            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch"></label>
                <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                <select v-model="filters.type" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

        <div class="grid gap-3 p-5 lg:grid-cols-2 xl:grid-cols-3">
            <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
            <article v-else-if="coupons.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
            <template v-else>
                <article v-for="coupon in coupons" :key="coupon.id" class="overflow-hidden rounded-lg bg-[#ffffff] shadow-lg shadow-black/5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <div class="border-b border-black/8 p-4 dark:border-white/10">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.coupon }}</p><h3 class="mt-1 text-2xl font-black tracking-normal">{{ coupon.code }}</h3></div><span class="rounded-full px-2.5 py-1 text-xs font-black ring-1" :class="statusClass(coupon)">{{ statusLabel(coupon) }}</span></div>
                        <div class="mt-4 flex items-end justify-between gap-3"><div><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.discount }}</p><p class="mt-1 text-3xl font-black text-[#8f2638] dark:text-[#ff8aa0]">{{ couponValue(coupon) }}</p></div><button class="grid size-11 place-items-center rounded-lg bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white" type="button" @click="openEdit(coupon)"><FontAwesomeIcon :icon="icons.wand" /></button></div>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-black/8 text-sm dark:bg-white/10">
                        <div class="bg-white p-3 dark:bg-[#111111]"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.minOrder }}</p><p class="mt-1 font-black">{{ formatPrice(coupon.minimum_order_amount) }}</p></div>
                        <div class="bg-white p-3 dark:bg-[#111111]"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.maxDiscount }}</p><p class="mt-1 font-black">{{ coupon.maximum_discount_amount ? formatPrice(coupon.maximum_discount_amount) : text.unlimited }}</p></div>
                        <div class="bg-white p-3 dark:bg-[#111111]"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.usage }}</p><p class="mt-1 font-black">{{ usageLabel(coupon) }}</p></div>
                        <div class="bg-white p-3 dark:bg-[#111111]"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.orders }}</p><p class="mt-1 font-black">{{ formatNumber(coupon.orders_count) }}</p></div>
                    </div>
                    <div class="p-4 text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ formatDate(coupon.starts_at) }} - {{ formatDate(coupon.ends_at) }}</div>
                </article>
            </template>
        </div>

        <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.showing }} {{ formatNumber(coupons.length) }} {{ text.of }} {{ formatNumber(meta.total) }}</p><div class="flex gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page <= 1 || isLoading" @click="loadCoupons(meta.current_page - 1)">{{ text.previous }}</button><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page >= meta.last_page || isLoading" @click="loadCoupons(meta.current_page + 1)">{{ text.next }}</button></div></div>
    </section>

    <div v-if="isFormOpen" class="fixed inset-0 z-50 overflow-y-auto bg-black/45 p-3 backdrop-blur-sm sm:p-6">
        <form class="mx-auto max-w-3xl rounded-lg bg-[#ffffff] shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10" @submit.prevent="saveCoupon">
            <div class="flex items-center justify-between gap-3 border-b border-black/8 p-5 dark:border-white/10"><div><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p><h3 class="mt-1 text-xl font-black">{{ modalTitle }}</h3></div><button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="isFormOpen = false"><FontAwesomeIcon :icon="icons.xmark" /></button></div>
            <div class="grid gap-4 p-5">
                <div v-if="formError" class="rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
                <div class="grid gap-3 sm:grid-cols-3"><label class="grid gap-1 text-sm font-bold"><span>{{ text.code }}</span><input v-model="form.code" class="h-11 rounded-lg border-0 bg-white px-3 font-black uppercase outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" required><small v-if="firstError('code')" class="text-[#8f2638]">{{ firstError('code') }}</small></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.type }}</span><select v-model="form.type" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"><option v-for="option in formTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.value }}</span><input v-model="form.value" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="1" required><small v-if="firstError('value')" class="text-[#8f2638]">{{ firstError('value') }}</small></label></div>
                <div class="grid gap-3 sm:grid-cols-3"><label class="grid gap-1 text-sm font-bold"><span>{{ text.minOrder }}</span><input v-model="form.minimum_order_amount" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.maxDiscount }}</span><input v-model="form.maximum_discount_amount" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="1"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.perCustomer }}</span><input v-model="form.per_customer_limit" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="1"></label></div>
                <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.usageLimit }}</span><input v-model="form.usage_limit" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="1"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.usedCount }}</span><input v-model="form.used_count" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"></label></div>
                <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.startsAt }}</span><input v-model="form.starts_at" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="datetime-local"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.endsAt }}</span><input v-model="form.ends_at" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="datetime-local"><small v-if="firstError('ends_at')" class="text-[#8f2638]">{{ firstError('ends_at') }}</small></label></div>
                <label class="flex items-center gap-2 text-sm font-black"><input v-model="form.is_active" class="size-4 accent-[#8f2638]" type="checkbox">{{ text.active }}</label>
            </div>
            <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:justify-end"><button class="h-11 rounded-lg border border-black/10 px-5 text-sm font-black dark:border-white/10" type="button" @click="isFormOpen = false">{{ text.close }}</button><button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button></div>
        </form>
    </div>
</template>
