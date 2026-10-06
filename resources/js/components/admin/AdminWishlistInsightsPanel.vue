<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const error = ref(null);
const products = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', page: 1 });
let searchTimer = null;

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'কাস্টমার ইনটেন্ট',
    title: 'উইশলিস্ট ইনসাইটস',
    subtitle: 'কোন পণ্য customer বেশি save করছে সেটা দেখুন, যাতে merchandising, stock planning এবং recommendation আরও smart হয়।',
    search: 'পণ্য, SKU, ক্যাটাগরি বা ব্র্যান্ড খুঁজুন',
    product: 'Product',
    category: 'Category',
    brand: 'Brand',
    saves: 'Wishlist Saves',
    customers: 'Unique Customers',
    latest: 'Latest Save',
    price: 'Price',
    empty: 'এখনও কোনো wishlist signal পাওয়া যায়নি।',
    failed: 'Wishlist insights লোড করা যায়নি।',
    previous: 'আগের',
    next: 'পরের',
    showing: 'দেখানো হচ্ছে',
    of: 'মোট',
    topSignal: 'Top intent signal',
    merchandising: 'Merchandising use',
    merchandisingText: 'High-save products can power recommended items, campaign picks, reorder planning, and back-in-stock decisions.',
} : {
    eyebrow: 'Customer Intent',
    title: 'Wishlist Insights',
    subtitle: 'See which products customers save most so merchandising, stock planning, and recommendations become smarter.',
    search: 'Search product, SKU, category, or brand',
    product: 'Product',
    category: 'Category',
    brand: 'Brand',
    saves: 'Wishlist Saves',
    customers: 'Unique Customers',
    latest: 'Latest Save',
    price: 'Price',
    empty: 'No wishlist signals yet.',
    failed: 'Could not load wishlist insights.',
    previous: 'Previous',
    next: 'Next',
    showing: 'Showing',
    of: 'of',
    topSignal: 'Top intent signal',
    merchandising: 'Merchandising use',
    merchandisingText: 'High-save products can power recommended items, campaign picks, reorder planning, and back-in-stock decisions.',
});

const formatNumber = (value) => Number(value || 0).toLocaleString('en-BD');
const formatPrice = (value) => '৳' + Math.round(Number(value || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const topProduct = computed(() => products.value[0] ?? null);
const totalSaves = computed(() => products.value.reduce((sum, product) => sum + Number(product.wishlist_count || 0), 0));
const totalCustomers = computed(() => products.value.reduce((sum, product) => sum + Number(product.unique_customers || 0), 0));

const loadInsights = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());

    try {
        const response = await fetch('/api/admin/wishlist-insights?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        products.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadInsights(1), 350);
};

onMounted(() => loadInsights());
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:min-w-72">
                        <div class="rounded-lg bg-[#171512] p-4 text-white dark:bg-[#9f243a] dark:text-white"><p class="text-[10px] font-black uppercase opacity-70">{{ text.saves }}</p><p class="mt-1 text-2xl font-black">{{ formatNumber(totalSaves) }}</p></div>
                        <div class="rounded-lg bg-[#8f2638] p-4 text-white"><p class="text-[10px] font-black uppercase opacity-75">{{ text.customers }}</p><p class="mt-1 text-2xl font-black">{{ formatNumber(totalCustomers) }}</p></div>
                    </div>
                </div>
                <label class="mt-5 flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                    <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                </label>
            </div>

            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="products.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                    <article v-for="product in products" :key="product.id" class="rounded-lg bg-[#ffffff] p-4 shadow-lg shadow-black/5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 gap-3">
                                <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.heart" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ product.sku }} · {{ product.category?.name || text.category }}</p>
                                    <h3 class="mt-1 truncate text-base font-black">{{ product.name }}</h3>
                                    <p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ product.brand?.name || text.brand }} · {{ text.latest }} {{ formatDate(product.latest_wishlisted_at) }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center sm:min-w-80">
                                <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.saves }}</p><p class="mt-1 font-black">{{ formatNumber(product.wishlist_count) }}</p></div>
                                <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.customers }}</p><p class="mt-1 font-black">{{ formatNumber(product.unique_customers) }}</p></div>
                                <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-black/6 dark:bg-white/10 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.price }}</p><p class="mt-1 font-black">{{ formatPrice(product.price) }}</p></div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ text.showing }} {{ products.length }} {{ text.of }} {{ meta.total }}</p>
                <div class="flex items-center gap-2">
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadInsights(meta.current_page - 1)">{{ text.previous }}</button>
                    <span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadInsights(meta.current_page + 1)">{{ text.next }}</button>
                </div>
            </div>
        </div>

        <aside class="grid content-start gap-5">
            <section class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
                <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.topSignal }}</p>
                <div v-if="topProduct" class="mt-4 grid gap-4">
                    <span class="grid size-14 place-items-center rounded-lg bg-white/10 text-2xl text-[#ff8aa0] ring-1 ring-white/10"><FontAwesomeIcon :icon="icons.heart" /></span>
                    <div>
                        <h3 class="text-2xl font-black leading-tight">{{ topProduct.name }}</h3>
                        <p class="mt-2 text-sm font-semibold text-white/68">{{ topProduct.category?.name || '-' }} · {{ topProduct.brand?.name || '-' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.saves }}</p><p class="mt-1 text-xl font-black">{{ formatNumber(topProduct.wishlist_count) }}</p></div>
                        <div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.customers }}</p><p class="mt-1 text-xl font-black">{{ formatNumber(topProduct.unique_customers) }}</p></div>
                    </div>
                </div>
                <p v-else class="mt-4 rounded-lg bg-white/8 p-4 text-sm font-bold text-white/70 ring-1 ring-white/10">{{ text.empty }}</p>
            </section>
            <section class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.merchandising }}</p>
                <p class="mt-3 text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.merchandisingText }}</p>
            </section>
        </aside>
    </section>
</template>
