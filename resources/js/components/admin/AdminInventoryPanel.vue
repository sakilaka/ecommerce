<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
const variants = ref([]);
const selectedVariant = ref(null);
const isAdjustOpen = ref(false);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
const filters = reactive({ q: '', stock_status: '', active: '', page: 1 });
const adjustment = reactive({ type: 'manual', quantity_delta: 1, note: '' });
let searchTimer = null;

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'ইনভেন্টরি কন্ট্রোল',
    title: 'স্টক ম্যানেজমেন্ট',
    subtitle: 'ভ্যারিয়েন্টভিত্তিক স্টক, রিজার্ভড পরিমাণ, লো-স্টক অ্যালার্ট এবং অ্যাডমিন অ্যাডজাস্টমেন্ট এক জায়গায়।',
    search: 'পণ্য, ভ্যারিয়েন্ট বা SKU খুঁজুন',
    allStock: 'সব স্টক',
    low: 'লো স্টক',
    healthy: 'স্বাস্থ্যকর',
    out: 'স্টক আউট',
    allActive: 'সব অবস্থা',
    active: 'অ্যাক্টিভ',
    inactive: 'ইনঅ্যাক্টিভ',
    variant: 'ভ্যারিয়েন্ট',
    stock: 'স্টক',
    reserved: 'রিজার্ভড',
    available: 'এভেইলেবল',
    threshold: 'থ্রেশহোল্ড',
    category: 'ক্যাটাগরি',
    brand: 'ব্র্যান্ড',
    oversell: 'Oversell',
    adjust: 'স্টক অ্যাডজাস্ট',
    adjusted: 'অ্যাডজাস্টমেন্ট',
    current: 'বর্তমান স্টক',
    projected: 'নতুন স্টক',
    type: 'ধরন',
    quantity: 'পরিমাণ (+/-)',
    note: 'নোট',
    save: 'সেভ অ্যাডজাস্টমেন্ট',
    saving: 'সেভ হচ্ছে...',
    close: 'বন্ধ করুন',
    empty: 'এই ফিল্টারে কোনো স্টক আইটেম পাওয়া যায়নি।',
    unavailable: 'ইনভেন্টরি লোড করা যায়নি।',
    formUnavailable: 'স্টক অ্যাডজাস্ট করা যায়নি। তথ্য আবার চেক করুন।',
    showing: 'দেখানো হচ্ছে',
    of: 'মোট',
    previous: 'আগের',
    next: 'পরের',
    official: 'অফিশিয়াল',
    types: { manual: 'Manual', purchase: 'Purchase', return: 'Return', correction: 'Correction' },
} : {
    eyebrow: 'Inventory Control',
    title: 'Stock Management',
    subtitle: 'Track variant stock, reserved quantity, low-stock signals, and admin adjustments from one clean workspace.',
    search: 'Search product, variant, or SKU',
    allStock: 'All Stock',
    low: 'Low Stock',
    healthy: 'Healthy',
    out: 'Out of Stock',
    allActive: 'All Status',
    active: 'Active',
    inactive: 'Inactive',
    variant: 'Variant',
    stock: 'Stock',
    reserved: 'Reserved',
    available: 'Available',
    threshold: 'Threshold',
    category: 'Category',
    brand: 'Brand',
    oversell: 'Oversell',
    adjust: 'Adjust Stock',
    adjusted: 'Adjustments',
    current: 'Current Stock',
    projected: 'Projected Stock',
    type: 'Type',
    quantity: 'Quantity Delta (+/-)',
    note: 'Note',
    save: 'Save Adjustment',
    saving: 'Saving...',
    close: 'Close',
    empty: 'No inventory items found for this filter.',
    unavailable: 'Could not load inventory.',
    formUnavailable: 'Could not adjust stock. Check the fields and try again.',
    showing: 'Showing',
    of: 'of',
    previous: 'Previous',
    next: 'Next',
    official: 'Official',
    types: { manual: 'Manual', purchase: 'Purchase', return: 'Return', correction: 'Correction' },
});

const stockOptions = computed(() => [
    { value: '', label: text.value.allStock },
    { value: 'low', label: text.value.low },
    { value: 'healthy', label: text.value.healthy },
    { value: 'out', label: text.value.out },
]);

const activeOptions = computed(() => [
    { value: '', label: text.value.allActive },
    { value: '1', label: text.value.active },
    { value: '0', label: text.value.inactive },
]);

const typeOptions = computed(() => [
    { value: 'manual', label: text.value.types.manual },
    { value: 'purchase', label: text.value.types.purchase },
    { value: 'return', label: text.value.types.return },
    { value: 'correction', label: text.value.types.correction },
]);

const stats = computed(() => {
    const total = variants.value.length;
    const low = variants.value.filter((variant) => variant.is_low_stock && variant.stock_quantity > 0).length;
    const out = variants.value.filter((variant) => variant.stock_quantity <= 0).length;
    const available = variants.value.reduce((sum, variant) => sum + Number(variant.available_quantity || 0), 0);

    return [
        { label: text.value.stock, value: formatNumber(available), icon: icons.store, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
        { label: text.value.low, value: formatNumber(low), icon: icons.truck, tone: 'bg-[#8f2638] text-white' },
        { label: text.value.out, value: formatNumber(out), icon: icons.clock, tone: 'bg-[#9f243a] text-white' },
        { label: text.value.variant, value: formatNumber(total), icon: icons.grip, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
    ];
});

const projectedStock = computed(() => selectedVariant.value ? Number(selectedVariant.value.stock_quantity || 0) + Number(adjustment.quantity_delta || 0) : 0);

const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const formatPrice = (amount) => amount ? '৳' + Math.round(Number(amount) / 100).toLocaleString('en-BD') : '-';

const stockClass = (variant) => {
    if (Number(variant.stock_quantity) <= 0) {
        return 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:bg-[#8f2638]/25 dark:text-[#ffb4c1]';
    }

    if (variant.is_low_stock) {
        return 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:bg-[#9f243a]/15 dark:text-[#ff8aa0]';
    }

    return 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300';
};

const loadInventory = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '15', page: String(page) });
    if (filters.q.trim()) {
        params.set('q', filters.q.trim());
    }
    if (filters.stock_status) {
        params.set('stock_status', filters.stock_status);
    }
    if (filters.active !== '') {
        params.set('active', filters.active);
    }

    try {
        const response = await fetch('/api/admin/inventory?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Request failed');
        }
        const payload = await response.json();
        variants.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadInventory(1), 350);
};

const openAdjust = (variant) => {
    selectedVariant.value = variant;
    adjustment.type = 'manual';
    adjustment.quantity_delta = 1;
    adjustment.note = '';
    formError.value = null;
    fieldErrors.value = {};
    isAdjustOpen.value = true;
};

const saveAdjustment = async () => {
    if (!selectedVariant.value) {
        return;
    }

    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch('/api/admin/inventory/' + selectedVariant.value.id + '/adjustments', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({
                type: adjustment.type,
                quantity_delta: Number(adjustment.quantity_delta),
                note: adjustment.note || null,
            }),
        });
        const payload = await response.json();

        if (!response.ok) {
            fieldErrors.value = payload.errors ?? {};
            throw new Error('Validation failed');
        }

        isAdjustOpen.value = false;
        await loadInventory(filters.page);
    } catch (requestError) {
        formError.value = text.value.formUnavailable;
    } finally {
        isSaving.value = false;
    }
};

const firstFieldError = (name) => fieldErrors.value[name]?.[0] ?? '';

watch(() => filters.stock_status, () => loadInventory(1));
watch(() => filters.active, () => loadInventory(1));
onMounted(() => loadInventory());
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
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:w-[560px]">
                    <article v-for="card in stats" :key="card.label" class="rounded-lg p-3 shadow-lg shadow-black/5 ring-1 ring-black/5" :class="card.tone">
                        <div class="flex items-center justify-between gap-2 text-xs font-black uppercase opacity-75"><span>{{ card.label }}</span><FontAwesomeIcon :icon="card.icon" /></div>
                        <p class="mt-2 text-xl font-black">{{ card.value }}</p>
                    </article>
                </div>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                    <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                </label>
                <select v-model="filters.stock_status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                    <option v-for="option in stockOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <select v-model="filters.active" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                    <option v-for="option in activeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

        <div class="hidden overflow-x-auto xl:block">
            <table class="w-full min-w-[1040px] text-left text-sm">
                <thead class="bg-[#f6f6f6] text-xs font-black uppercase text-[#4b5563] dark:bg-black/20 dark:text-[#d1d5db]">
                    <tr>
                        <th class="px-5 py-3">{{ text.variant }}</th>
                        <th class="px-5 py-3">{{ text.category }}</th>
                        <th class="px-5 py-3">{{ text.brand }}</th>
                        <th class="px-5 py-3 text-right">{{ text.stock }}</th>
                        <th class="px-5 py-3 text-right">{{ text.reserved }}</th>
                        <th class="px-5 py-3 text-right">{{ text.available }}</th>
                        <th class="px-5 py-3 text-right">{{ text.threshold }}</th>
                        <th class="px-5 py-3 text-right">{{ text.adjusted }}</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/8 dark:divide-white/10">
                    <tr v-for="variant in variants" :key="variant.id" class="font-semibold">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-[#171512] text-sm font-black text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white">{{ variant.product?.name?.charAt(0) ?? 'P' }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-black">{{ variant.product?.name }}</p>
                                    <p class="truncate text-xs text-[#4b5563] dark:text-[#d1d5db]">{{ variant.name }} · {{ variant.sku }} · {{ formatPrice(variant.price || variant.product?.price) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">{{ variant.product?.category?.name ?? '-' }}</td>
                        <td class="px-5 py-4"><span>{{ variant.product?.brand?.name ?? '-' }}</span><span v-if="variant.product?.brand?.is_official" class="ml-2 rounded-full bg-[#9f243a]/12 px-2 py-0.5 text-[10px] font-black uppercase text-[#8f2638] dark:bg-[#9f243a]/15 dark:text-[#ff8aa0]">{{ text.official }}</span></td>
                        <td class="px-5 py-4 text-right"><span class="rounded-full px-2.5 py-1 text-xs font-black ring-1" :class="stockClass(variant)">{{ formatNumber(variant.stock_quantity) }}</span></td>
                        <td class="px-5 py-4 text-right">{{ formatNumber(variant.reserved_quantity) }}</td>
                        <td class="px-5 py-4 text-right font-black">{{ formatNumber(variant.available_quantity) }}</td>
                        <td class="px-5 py-4 text-right">{{ formatNumber(variant.low_stock_threshold) }}</td>
                        <td class="px-5 py-4 text-right">{{ formatNumber(variant.adjustments_count) }}</td>
                        <td class="px-5 py-4 text-right"><button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#171512] px-3 text-xs font-black text-white shadow-lg shadow-black/10 dark:bg-[#9f243a] dark:text-white" type="button" @click="openAdjust(variant)"><FontAwesomeIcon :icon="icons.plus" />{{ text.adjust }}</button></td>
                    </tr>
                    <tr v-if="variants.length === 0 && !isLoading"><td class="px-5 py-10 text-center text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]" colspan="9">{{ text.empty }}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 p-5 xl:hidden">
            <article v-for="variant in variants" :key="variant.id" class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-base font-black">{{ variant.product?.name }}</p>
                        <p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ variant.name }} · {{ variant.sku }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black ring-1" :class="stockClass(variant)">{{ formatNumber(variant.stock_quantity) }}</span>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-white p-2 dark:bg-white/8"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.reserved }}</p><p class="mt-1 font-black">{{ formatNumber(variant.reserved_quantity) }}</p></div>
                    <div class="rounded-lg bg-white p-2 dark:bg-white/8"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.available }}</p><p class="mt-1 font-black">{{ formatNumber(variant.available_quantity) }}</p></div>
                    <div class="rounded-lg bg-white p-2 dark:bg-white/8"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.threshold }}</p><p class="mt-1 font-black">{{ formatNumber(variant.low_stock_threshold) }}</p></div>
                </div>
                <button class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#171512] px-4 text-sm font-black text-white dark:bg-[#9f243a] dark:text-white" type="button" @click="openAdjust(variant)"><FontAwesomeIcon :icon="icons.plus" />{{ text.adjust }}</button>
            </article>
            <p v-if="variants.length === 0 && !isLoading" class="rounded-lg bg-[#f6f6f6] px-4 py-8 text-center text-sm font-semibold text-[#4b5563] dark:bg-black/20 dark:text-[#d1d5db]">{{ text.empty }}</p>
        </div>

        <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.showing }} {{ formatNumber(variants.length) }} {{ text.of }} {{ formatNumber(meta.total) }}</p>
            <div class="flex gap-2">
                <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page <= 1 || isLoading" @click="loadInventory(meta.current_page - 1)">{{ text.previous }}</button>
                <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page >= meta.last_page || isLoading" @click="loadInventory(meta.current_page + 1)">{{ text.next }}</button>
            </div>
        </div>

        <div v-if="isAdjustOpen" class="fixed inset-0 z-50 grid place-items-center bg-black/55 p-4 backdrop-blur-sm">
            <form class="max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-lg bg-[#f9fafb] p-5 shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10" @submit.prevent="saveAdjustment">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.adjust }}</p>
                        <h3 class="mt-1 text-xl font-black">{{ selectedVariant?.product?.name }}</h3>
                        <p class="mt-1 text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ selectedVariant?.name }} · {{ selectedVariant?.sku }}</p>
                    </div>
                    <button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="isAdjustOpen = false"><FontAwesomeIcon :icon="icons.xmark" /></button>
                </div>

                <div v-if="formError" class="mt-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.current }}</p><p class="mt-1 text-2xl font-black">{{ formatNumber(selectedVariant?.stock_quantity) }}</p></div>
                    <div class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.projected }}</p><p class="mt-1 text-2xl font-black" :class="projectedStock < 0 ? 'text-[#8f2638] dark:text-[#ffb4c1]' : ''">{{ formatNumber(projectedStock) }}</p></div>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-black">{{ text.type }}<select v-model="adjustment.type" class="h-12 rounded-lg border-0 bg-white px-4 text-sm font-bold outline-none ring-1 ring-black/8 dark:bg-white/8 dark:text-white dark:ring-white/10"><option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select><span v-if="firstFieldError('type')" class="text-xs text-[#8f2638]">{{ firstFieldError('type') }}</span></label>
                    <label class="grid gap-2 text-sm font-black">{{ text.quantity }}<input v-model="adjustment.quantity_delta" class="h-12 rounded-lg border-0 bg-white px-4 text-sm font-bold outline-none ring-1 ring-black/8 dark:bg-white/8 dark:text-white dark:ring-white/10" type="number" min="-10000" max="10000" step="1"><span v-if="firstFieldError('quantity_delta')" class="text-xs text-[#8f2638]">{{ firstFieldError('quantity_delta') }}</span></label>
                </div>
                <label class="mt-4 grid gap-2 text-sm font-black">{{ text.note }}<textarea v-model="adjustment.note" class="min-h-28 rounded-lg border-0 bg-white px-4 py-3 text-sm font-bold outline-none ring-1 ring-black/8 dark:bg-white/8 dark:text-white dark:ring-white/10"></textarea><span v-if="firstFieldError('note')" class="text-xs text-[#8f2638]">{{ firstFieldError('note') }}</span></label>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button class="h-11 rounded-lg border border-black/10 px-4 text-sm font-black dark:border-white/10" type="button" @click="isAdjustOpen = false">{{ text.close }}</button>
                    <button class="h-11 rounded-lg bg-[#8f2638] px-4 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-50 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving || projectedStock < 0">{{ isSaving ? text.saving : text.save }}</button>
                </div>
            </form>
        </div>
    </section>
</template>
