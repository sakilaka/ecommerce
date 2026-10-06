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
const products = ref([]);
const categories = ref([]);
const brands = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 12 });
const filters = reactive({ q: '', status: '', page: 1 });
const isFormOpen = ref(false);
const editingId = ref(null);
let searchTimer = null;

const emptyVariant = () => ({
    id: null,
    name: 'Default',
    sku: '',
    price: '',
    compare_at_price: '',
    stock_quantity: 0,
    reserved_quantity: 0,
    low_stock_threshold: 5,
    oversell_allowed: true,
    is_active: true,
});

const emptyImage = () => ({ path: '', alt_text: '', is_primary: false });

const emptyForm = () => ({
    category_id: '',
    brand_id: '',
    name: '',
    slug: '',
    sku: '',
    status: 'draft',
    short_description: '',
    description: '',
    seo_title: '',
    seo_description: '',
    price: '',
    compare_at_price: '',
    cost_price: '',
    discount_percent: '',
    sale_starts_at: '',
    sale_ends_at: '',
    is_featured: false,
    is_refundable: true,
    return_window_days: 7,
    weight_kg: '',
    variants: [emptyVariant()],
    images: [emptyImage()],
});

const form = reactive(emptyForm());

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'ক্যাটালগ ম্যানেজমেন্ট',
    title: 'পণ্য তালিকা',
    subtitle: 'প্রোডাক্ট তৈরি, দাম, স্টক, ক্যাটাগরি এবং প্রকাশনার অবস্থা এক জায়গা থেকে নিয়ন্ত্রণ করুন।',
    search: 'নাম, SKU, ক্যাটাগরি বা ব্র্যান্ড খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    active: 'অ্যাক্টিভ',
    draft: 'ড্রাফট',
    archived: 'আর্কাইভড',
    addProduct: 'নতুন পণ্য',
    editProduct: 'পণ্য আপডেট',
    saveProduct: 'সেভ করুন',
    saving: 'সেভ হচ্ছে...',
    cancel: 'বন্ধ করুন',
    edit: 'এডিট',
    product: 'পণ্য',
    category: 'ক্যাটাগরি',
    brand: 'ব্র্যান্ড',
    price: 'দাম',
    stock: 'স্টক',
    status: 'স্ট্যাটাস',
    variants: 'ভ্যারিয়েন্ট',
    images: 'ইমেজ',
    lowStock: 'লো স্টক',
    refundable: 'রিটার্নযোগ্য',
    notRefundable: 'নন-রিটার্ন',
    official: 'অফিশিয়াল',
    empty: 'এই ফিল্টারে কোনো পণ্য পাওয়া যায়নি।',
    showing: 'দেখানো হচ্ছে',
    of: 'মোট',
    previous: 'আগের',
    next: 'পরের',
    unavailable: 'প্রোডাক্ট তালিকা লোড করা যায়নি।',
    formUnavailable: 'পণ্য সেভ করা যায়নি। তথ্যগুলো আবার চেক করুন।',
    basics: 'বেসিক তথ্য',
    merchandising: 'মার্চেন্ডাইজিং',
    inventory: 'ইনভেন্টরি',
    media: 'মিডিয়া',
    name: 'পণ্যের নাম',
    slug: 'স্লাগ',
    sku: 'SKU',
    shortDescription: 'ছোট বিবরণ',
    description: 'বিস্তারিত বিবরণ',
    seo: 'SEO সেটিংস',
    seoTitle: 'SEO title',
    seoDescription: 'SEO description',
    seoHelp: 'Google search preview এর জন্য 70/180 character এর মধ্যে রাখুন।',
    compareAt: 'আগের দাম',
    costPrice: 'কস্ট প্রাইস',
    discount: 'ডিসকাউন্ট %',
    saleStart: 'সেল শুরু',
    saleEnd: 'সেল শেষ',
    featured: 'Featured product',
    returnWindow: 'রিটার্ন দিন',
    weight: 'ওজন KG',
    variantName: 'ভ্যারিয়েন্ট নাম',
    variantSku: 'ভ্যারিয়েন্ট SKU',
    reserved: 'রিজার্ভড',
    threshold: 'লো স্টক সীমা',
    oversell: 'Oversell allowed',
    activeVariant: 'Active',
    addVariant: 'ভ্যারিয়েন্ট যোগ',
    addImage: 'ইমেজ যোগ',
    imagePath: 'ইমেজ পাথ / URL',
    altText: 'Alt text',
    primary: 'Primary',
    selectCategory: 'ক্যাটাগরি নির্বাচন করুন',
    noBrand: 'ব্র্যান্ড নেই',
} : {
    eyebrow: 'Catalog Management',
    title: 'Product List',
    subtitle: 'Create products, pricing, stock variants, category placement, and publishing status from one admin workspace.',
    search: 'Search name, SKU, category, or brand',
    allStatus: 'All Status',
    active: 'Active',
    draft: 'Draft',
    archived: 'Archived',
    addProduct: 'Add Product',
    editProduct: 'Update Product',
    saveProduct: 'Save Product',
    saving: 'Saving...',
    cancel: 'Close',
    edit: 'Edit',
    product: 'Product',
    category: 'Category',
    brand: 'Brand',
    price: 'Price',
    stock: 'Stock',
    status: 'Status',
    variants: 'Variants',
    images: 'Images',
    lowStock: 'Low stock',
    refundable: 'Refundable',
    notRefundable: 'No return',
    official: 'Official',
    empty: 'No products found for this filter.',
    showing: 'Showing',
    of: 'of',
    previous: 'Previous',
    next: 'Next',
    unavailable: 'Could not load the product list.',
    formUnavailable: 'Could not save this product. Check the fields and try again.',
    basics: 'Basic Details',
    merchandising: 'Merchandising',
    inventory: 'Inventory',
    media: 'Media',
    name: 'Product name',
    slug: 'Slug',
    sku: 'SKU',
    shortDescription: 'Short description',
    description: 'Description',
    seo: 'SEO Settings',
    seoTitle: 'SEO title',
    seoDescription: 'SEO description',
    seoHelp: 'Keep this within 70/180 characters for a clean Google preview.',
    compareAt: 'Compare at',
    costPrice: 'Cost price',
    discount: 'Discount %',
    saleStart: 'Sale starts',
    saleEnd: 'Sale ends',
    featured: 'Featured product',
    returnWindow: 'Return days',
    weight: 'Weight KG',
    variantName: 'Variant name',
    variantSku: 'Variant SKU',
    reserved: 'Reserved',
    threshold: 'Low stock threshold',
    oversell: 'Oversell allowed',
    activeVariant: 'Active',
    addVariant: 'Add Variant',
    addImage: 'Add Image',
    imagePath: 'Image path / URL',
    altText: 'Alt text',
    primary: 'Primary',
    selectCategory: 'Select category',
    noBrand: 'No brand',
});

const statusOptions = computed(() => [
    { value: '', label: text.value.allStatus },
    { value: 'active', label: text.value.active },
    { value: 'draft', label: text.value.draft },
    { value: 'archived', label: text.value.archived },
]);

const formStatusOptions = computed(() => statusOptions.value.filter((option) => option.value));
const modalTitle = computed(() => editingId.value ? text.value.editProduct : text.value.addProduct);

const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const productInitials = (name) => String(name || 'P').split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase();
const toTaka = (amount) => amount === null || amount === undefined || amount === '' ? '' : Number(amount) / 100;
const toMinor = (amount) => amount === '' || amount === null || amount === undefined ? null : Math.round(Number(amount) * 100);
const isoToInput = (value) => value ? String(value).slice(0, 16) : '';

const statusClass = (status) => ({
    active: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300',
    draft: 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]',
    archived: 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300',
}[status] ?? 'bg-black/5 text-[#374151] ring-black/10 dark:bg-white/10 dark:text-white');

const resetForm = () => {
    Object.assign(form, emptyForm());
    fieldErrors.value = {};
    formError.value = null;
};

const loadOptions = async () => {
    const response = await fetch('/api/admin/product-options', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!response.ok) {
        return;
    }
    const payload = await response.json();
    categories.value = payload.categories ?? [];
    brands.value = payload.brands ?? [];
};

const loadProducts = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) {
        params.set('q', filters.q.trim());
    }
    if (filters.status) {
        params.set('status', filters.status);
    }

    try {
        const response = await fetch('/api/admin/products?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Request failed');
        }
        const payload = await response.json();
        products.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadProducts(1), 350);
};

const openCreateForm = async () => {
    resetForm();
    editingId.value = null;
    if (!categories.value.length) {
        await loadOptions();
    }
    isFormOpen.value = true;
};

const fillForm = (product) => {
    resetForm();
    editingId.value = product.id;
    form.category_id = product.category?.id ?? '';
    form.brand_id = product.brand?.id ?? '';
    form.name = product.name ?? '';
    form.slug = product.slug ?? '';
    form.sku = product.sku ?? '';
    form.status = product.status ?? 'draft';
    form.short_description = product.short_description ?? '';
    form.description = product.description ?? '';
    form.seo_title = product.seo_title ?? '';
    form.seo_description = product.seo_description ?? '';
    form.price = toTaka(product.price);
    form.compare_at_price = toTaka(product.compare_at_price);
    form.cost_price = toTaka(product.cost_price);
    form.discount_percent = product.discount_percent ?? '';
    form.sale_starts_at = isoToInput(product.sale_starts_at);
    form.sale_ends_at = isoToInput(product.sale_ends_at);
    form.is_featured = Boolean(product.is_featured);
    form.is_refundable = Boolean(product.is_refundable);
    form.return_window_days = product.return_window_days ?? 7;
    form.weight_kg = product.weight_kg ?? '';
    form.variants = product.variants?.length ? product.variants.map((variant) => ({
        id: variant.id,
        name: variant.name,
        sku: variant.sku,
        price: toTaka(variant.price),
        compare_at_price: toTaka(variant.compare_at_price),
        stock_quantity: variant.stock_quantity,
        reserved_quantity: variant.reserved_quantity,
        low_stock_threshold: variant.low_stock_threshold,
        oversell_allowed: Boolean(variant.oversell_allowed),
        is_active: Boolean(variant.is_active),
    })) : [emptyVariant()];
    form.images = product.images?.length ? product.images.map((image) => ({ path: image.path, alt_text: image.alt_text ?? '', is_primary: Boolean(image.is_primary) })) : [emptyImage()];
};

const openEditForm = async (productId) => {
    formError.value = null;
    fieldErrors.value = {};
    if (!categories.value.length) {
        await loadOptions();
    }
    const response = await fetch('/api/admin/products/' + productId, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!response.ok) {
        formError.value = text.value.formUnavailable;
        return;
    }
    const payload = await response.json();
    fillForm(payload.data);
    isFormOpen.value = true;
};

const addVariant = () => form.variants.push(emptyVariant());
const removeVariant = (index) => {
    if (form.variants.length > 1) {
        form.variants.splice(index, 1);
    }
};
const addImage = () => form.images.push(emptyImage());
const removeImage = (index) => form.images.splice(index, 1);

const payload = () => ({
    category_id: Number(form.category_id),
    brand_id: form.brand_id ? Number(form.brand_id) : null,
    name: form.name,
    slug: form.slug || null,
    sku: form.sku || null,
    status: form.status,
    short_description: form.short_description || null,
    description: form.description || null,
    seo_title: form.seo_title || null,
    seo_description: form.seo_description || null,
    price: toMinor(form.price),
    compare_at_price: toMinor(form.compare_at_price),
    cost_price: toMinor(form.cost_price),
    discount_percent: form.discount_percent === '' ? null : Number(form.discount_percent),
    sale_starts_at: form.sale_starts_at || null,
    sale_ends_at: form.sale_ends_at || null,
    is_featured: form.is_featured,
    is_refundable: form.is_refundable,
    return_window_days: Number(form.return_window_days),
    weight_kg: form.weight_kg === '' ? null : Number(form.weight_kg),
    variants: form.variants.map((variant) => ({
        id: variant.id,
        name: variant.name,
        sku: variant.sku,
        price: toMinor(variant.price),
        compare_at_price: toMinor(variant.compare_at_price),
        stock_quantity: Number(variant.stock_quantity),
        reserved_quantity: Number(variant.reserved_quantity),
        low_stock_threshold: Number(variant.low_stock_threshold),
        oversell_allowed: variant.oversell_allowed,
        is_active: variant.is_active,
    })),
    images: form.images.filter((image) => image.path.trim()).map((image, index) => ({
        path: image.path,
        alt_text: image.alt_text || form.name,
        is_primary: image.is_primary || index === 0,
    })),
});

const saveProduct = async () => {
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch(editingId.value ? '/api/admin/products/' + editingId.value : '/api/admin/products', {
            method: editingId.value ? 'PATCH' : 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify(payload()),
        });
        const data = await response.json();

        if (!response.ok) {
            fieldErrors.value = data.errors ?? {};
            throw new Error('Validation failed');
        }

        isFormOpen.value = false;
        await loadProducts(editingId.value ? filters.page : 1);
    } catch (requestError) {
        formError.value = text.value.formUnavailable;
    } finally {
        isSaving.value = false;
    }
};

const firstFieldError = (name) => fieldErrors.value[name]?.[0] ?? '';

watch(() => filters.status, () => loadProducts(1));
onMounted(async () => {
    await Promise.all([loadOptions(), loadProducts()]);
});
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
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#171512] px-4 text-sm font-black text-white shadow-lg shadow-black/12 dark:bg-[#9f243a] dark:text-white" type="button" @click="openCreateForm">
                    <FontAwesomeIcon :icon="icons.plus" />
                    {{ text.addProduct }}
                </button>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                    <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                </label>
                <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-[#f6f6f6] text-xs font-black uppercase text-[#4b5563] dark:bg-black/20 dark:text-[#d1d5db]">
                    <tr>
                        <th class="px-5 py-3">{{ text.product }}</th>
                        <th class="px-5 py-3">{{ text.category }}</th>
                        <th class="px-5 py-3">{{ text.brand }}</th>
                        <th class="px-5 py-3 text-right">{{ text.price }}</th>
                        <th class="px-5 py-3 text-right">{{ text.stock }}</th>
                        <th class="px-5 py-3">{{ text.status }}</th>
                        <th class="px-5 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/8 dark:divide-white/10">
                    <tr v-if="isLoading"><td class="px-5 py-8 text-center text-sm font-black text-[#4b5563] dark:text-[#d1d5db]" colspan="7">Loading...</td></tr>
                    <tr v-else-if="products.length === 0"><td class="px-5 py-8 text-center text-sm font-black text-[#4b5563] dark:text-[#d1d5db]" colspan="7">{{ text.empty }}</td></tr>
                    <template v-else>
                        <tr v-for="product in products" :key="product.id" class="font-semibold transition hover:bg-[#f6f6f6]/70 dark:hover:bg-white/5">
                            <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] font-serif text-base font-black text-[#ff8aa0] shadow-md shadow-black/10 dark:bg-[#9f243a] dark:text-white">{{ productInitials(product.name) }}</span><span class="min-w-0"><span class="block truncate font-black">{{ product.name }}</span><span class="mt-1 block truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ product.sku }}</span></span></div></td>
                            <td class="px-5 py-4">{{ product.category?.name ?? '-' }}</td>
                            <td class="px-5 py-4"><div class="flex flex-wrap items-center gap-2"><span>{{ product.brand?.name ?? '-' }}</span><span v-if="product.brand?.is_official" class="rounded-full bg-[#9f243a]/16 px-2 py-0.5 text-[10px] font-black uppercase text-[#8f2638] ring-1 ring-[#9f243a]/30 dark:text-[#ff8aa0]">{{ text.official }}</span></div></td>
                            <td class="px-5 py-4 text-right"><p class="font-black">{{ formatPrice(product.price) }}</p><p v-if="product.compare_at_price" class="text-xs font-bold text-[#6b7280] line-through">{{ formatPrice(product.compare_at_price) }}</p></td>
                            <td class="px-5 py-4 text-right"><p class="font-black">{{ formatNumber(product.stock_quantity) }}</p><p class="text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ product.variants_count }} {{ text.variants }}</p><p v-if="product.low_stock_variants > 0" class="text-xs font-black text-[#8f2638] dark:text-[#ff8aa0]">{{ product.low_stock_variants }} {{ text.lowStock }}</p></td>
                            <td class="px-5 py-4"><div class="flex flex-col items-start gap-2"><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="statusClass(product.status)">{{ product.status }}</span><span class="text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ product.is_refundable ? text.refundable : text.notRefundable }}</span></div></td>
                            <td class="px-5 py-4 text-right"><button class="inline-flex h-10 items-center gap-2 rounded-lg border border-black/10 px-3 text-xs font-black transition hover:bg-white dark:border-white/10 dark:hover:bg-white/10" type="button" @click="openEditForm(product.id)"><FontAwesomeIcon :icon="icons.wand" />{{ text.edit }}</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ text.showing }} {{ products.length }} {{ text.of }} {{ formatNumber(meta.total) }}</p>
            <div class="flex items-center gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:cursor-not-allowed disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadProducts(meta.current_page - 1)">{{ text.previous }}</button><span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:cursor-not-allowed disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadProducts(meta.current_page + 1)">{{ text.next }}</button></div>
        </div>
    </section>

    <div v-if="isFormOpen" class="fixed inset-0 z-50 overflow-y-auto bg-black/45 p-3 backdrop-blur-sm sm:p-6">
        <form class="mx-auto max-w-5xl rounded-lg bg-[#ffffff] shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10" @submit.prevent="saveProduct">
            <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-black/8 bg-[#ffffff]/95 p-4 backdrop-blur-xl dark:border-white/10 dark:bg-[#111111]/95 sm:p-5">
                <div><p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p><h3 class="mt-1 text-xl font-black">{{ modalTitle }}</h3></div>
                <button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="isFormOpen = false"><FontAwesomeIcon :icon="icons.xmark" /></button>
            </div>

            <div v-if="formError" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>

            <div class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-5">
                    <section class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.basics }}</h4><div class="mt-4 grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.name }}</span><input v-model="form.name" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" required></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.sku }}</span><input v-model="form.sku" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.category }}</span><select v-model="form.category_id" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" required><option value="">{{ text.selectCategory }}</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.brand }}</span><select v-model="form.brand_id" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><option value="">{{ text.noBrand }}</option><option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option></select></label><label class="grid gap-1 text-sm font-bold sm:col-span-2"><span>{{ text.slug }}</span><input v-model="form.slug" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"></label><label class="grid gap-1 text-sm font-bold sm:col-span-2"><span>{{ text.shortDescription }}</span><textarea v-model="form.short_description" class="min-h-20 rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"></textarea></label><label class="grid gap-1 text-sm font-bold sm:col-span-2"><span>{{ text.description }}</span><textarea v-model="form.description" class="min-h-24 rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"></textarea></label><p v-if="firstFieldError('name')" class="text-sm font-bold text-[#8f2638]">{{ firstFieldError('name') }}</p></div></section>

                    <section class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><div class="flex items-center justify-between gap-3"><div><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.seo }}</h4><p class="mt-1 text-xs font-semibold leading-5 text-[#6b7280] dark:text-[#d1d5db]">{{ text.seoHelp }}</p></div><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /></div><div class="mt-4 grid gap-3"><label class="grid gap-1 text-sm font-bold"><span>{{ text.seoTitle }}</span><input v-model="form.seo_title" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" maxlength="70"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.seoDescription }}</span><textarea v-model="form.seo_description" class="min-h-20 rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" maxlength="180"></textarea></label></div></section>

                    <section class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><div class="flex items-center justify-between gap-3"><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.inventory }}</h4><button class="h-9 rounded-lg bg-[#171512] px-3 text-xs font-black text-white dark:bg-[#9f243a] dark:text-white" type="button" @click="addVariant">{{ text.addVariant }}</button></div><div class="mt-4 space-y-3"><article v-for="(variant, index) in form.variants" :key="index" class="rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><input v-model="variant.name" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" :placeholder="text.variantName" required><input v-model="variant.sku" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" :placeholder="text.variantSku" required><input v-model="variant.price" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" type="number" min="0" step="0.01" :placeholder="text.price"><input v-model="variant.stock_quantity" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" type="number" min="0" :placeholder="text.stock" required><input v-model="variant.reserved_quantity" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" type="number" min="0" :placeholder="text.reserved" required><input v-model="variant.low_stock_threshold" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" type="number" min="0" :placeholder="text.threshold" required><label class="flex h-10 items-center gap-2 text-xs font-black"><input v-model="variant.oversell_allowed" type="checkbox">{{ text.oversell }}</label><label class="flex h-10 items-center justify-between gap-2 text-xs font-black"><span><input v-model="variant.is_active" type="checkbox"> {{ text.activeVariant }}</span><button class="grid size-8 place-items-center rounded-lg bg-white text-[#8f2638] ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" type="button" @click="removeVariant(index)"><FontAwesomeIcon :icon="icons.trash" /></button></label></div></article></div></section>
                </div>

                <div class="space-y-5">
                    <section class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.merchandising }}</h4><div class="mt-4 grid gap-3"><label class="grid gap-1 text-sm font-bold"><span>{{ text.status }}</span><select v-model="form.status" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><option v-for="option in formStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.price }}</span><input v-model="form.price" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" type="number" min="0" step="0.01" required></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.compareAt }}</span><input v-model="form.compare_at_price" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" type="number" min="0" step="0.01"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.discount }}</span><input v-model="form.discount_percent" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" type="number" min="0" max="90"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.returnWindow }}</span><input v-model="form.return_window_days" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" type="number" min="0" max="30"></label><label class="flex items-center gap-2 text-sm font-black"><input v-model="form.is_featured" type="checkbox">{{ text.featured }}</label><label class="flex items-center gap-2 text-sm font-black"><input v-model="form.is_refundable" type="checkbox">{{ text.refundable }}</label></div></section>

                    <section class="rounded-lg bg-white p-4 ring-1 ring-black/6 dark:bg-white/8 dark:ring-white/10"><div class="flex items-center justify-between gap-3"><h4 class="text-sm font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.media }}</h4><button class="h-9 rounded-lg bg-[#f6f6f6] px-3 text-xs font-black ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10" type="button" @click="addImage">{{ text.addImage }}</button></div><div class="mt-4 space-y-3"><div v-for="(image, index) in form.images" :key="index" class="grid gap-2 rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><input v-model="image.path" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" :placeholder="text.imagePath"><input v-model="image.alt_text" class="h-10 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" :placeholder="text.altText"><label class="flex items-center justify-between gap-2 text-xs font-black"><span><input v-model="image.is_primary" type="checkbox"> {{ text.primary }}</span><button class="grid size-8 place-items-center rounded-lg bg-white text-[#8f2638] ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10" type="button" @click="removeImage(index)"><FontAwesomeIcon :icon="icons.trash" /></button></label></div></div></section>
                </div>
            </div>

            <div class="sticky bottom-0 flex flex-col gap-3 border-t border-black/8 bg-[#ffffff]/95 p-4 backdrop-blur-xl dark:border-white/10 dark:bg-[#111111]/95 sm:flex-row sm:justify-end sm:p-5"><button class="h-11 rounded-lg border border-black/10 px-5 text-sm font-black dark:border-white/10" type="button" @click="isFormOpen = false">{{ text.cancel }}</button><button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.saveProduct }}</button></div>
        </form>
    </div>
</template>
