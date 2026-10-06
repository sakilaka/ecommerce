<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCart } from '../../composables/useCart';
import { useStorefront } from '../../composables/useStorefront';
import { useWishlist } from '../../composables/useWishlist';
import { useSeo } from '../../composables/useSeo';
import PremiumSelect from './PremiumSelect.vue';

const { categoryCollageImage, icons, locale } = useStorefront();
const { addProduct, isAdding } = useCart();
const { isToggling, isWishlisted, toggleWishlist, wishlistError } = useWishlist();
const { setSeo } = useSeo();

const catalogCopy = {
    en: {
        eyebrow: 'Storefront Catalog',
        title: 'Shop real products with clear filters.',
        subtitle: 'Browse active inventory by category, brand, budget, keyword, and freshness.',
        search: 'Search products',
        searchPlaceholder: 'Search saree, panjabi, phone, skincare...',
        category: 'Category',
        brand: 'Brand',
        maxBudget: 'Max budget',
        sort: 'Sort',
        allCategories: 'All categories',
        allBrands: 'All brands',
        apply: 'Apply Filters',
        clear: 'Clear',
        showing: 'Showing',
        products: 'products',
        loading: 'Loading products...',
        empty: 'No products matched these filters.',
        addToCart: 'Add to cart',
        cart: 'Cart',
        wishlist: 'Wishlist',
        filters: 'Filters',
        refine: 'Refine the collection',
        prev: 'Previous',
        next: 'Next',
        page: 'Page',
        budgetOptions: [
            { label: 'Any budget', value: '' },
            { label: 'Under ৳499', value: '499' },
            { label: 'Under ৳999', value: '999' },
            { label: 'Under ৳1,999', value: '1999' },
            { label: 'Under ৳4,999', value: '4999' },
            { label: 'Under ৳10,000', value: '10000' },
        ],
        sorts: [
            { label: 'Latest', value: 'latest' },
            { label: 'Featured', value: 'featured' },
            { label: 'Price: Low to High', value: 'price_low' },
            { label: 'Price: High to Low', value: 'price_high' },
        ],
    },
    bn: {
        eyebrow: 'স্টোরফ্রন্ট ক্যাটালগ',
        title: 'Clear filter দিয়ে real product কিনুন।',
        subtitle: 'Category, brand, budget, keyword এবং latest stock অনুযায়ী active inventory browse করুন।',
        search: 'পণ্য খুঁজুন',
        searchPlaceholder: 'Saree, panjabi, phone, skincare খুঁজুন...',
        category: 'ক্যাটাগরি',
        brand: 'ব্র্যান্ড',
        maxBudget: 'সর্বোচ্চ বাজেট',
        sort: 'সাজান',
        allCategories: 'সব ক্যাটাগরি',
        allBrands: 'সব ব্র্যান্ড',
        apply: 'Filter Apply',
        clear: 'Clear',
        showing: 'দেখানো হচ্ছে',
        products: 'টি পণ্য',
        loading: 'Products loading...',
        empty: 'এই filter এ কোনো product পাওয়া যায়নি।',
        addToCart: 'Cart এ যোগ করুন',
        cart: 'কার্ট',
        wishlist: 'উইশলিস্ট',
        filters: 'Filters',
        refine: 'কালেকশন refine করুন',
        prev: 'আগের',
        next: 'পরের',
        page: 'পেজ',
        budgetOptions: [
            { label: 'যেকোনো budget', value: '' },
            { label: '৳৪৯৯ এর নিচে', value: '499' },
            { label: '৳৯৯৯ এর নিচে', value: '999' },
            { label: '৳১,৯৯৯ এর নিচে', value: '1999' },
            { label: '৳৪,৯৯৯ এর নিচে', value: '4999' },
            { label: '৳১০,০০০ এর নিচে', value: '10000' },
        ],
        sorts: [
            { label: 'Latest', value: 'latest' },
            { label: 'Featured', value: 'featured' },
            { label: 'দাম: কম থেকে বেশি', value: 'price_low' },
            { label: 'দাম: বেশি থেকে কম', value: 'price_high' },
        ],
    },
};

const copy = computed(() => catalogCopy[locale.value]);
const products = ref([]);
const categories = ref([]);
const brands = ref([]);
const isLoading = ref(false);
const error = ref(null);
const meta = ref(null);
const filters = reactive({
    q: '',
    category: '',
    brand: '',
    price_max: '',
    sort: 'latest',
    page: 1,
});

const fallbackPositions = ['0% 0%', '33.333% 0%', '66.666% 0%', '100% 0%', '0% 100%', '33.333% 100%', '66.666% 100%', '100% 100%'];

const formatTaka = (amount) => {
    if (amount === null || amount === undefined) {
        return '';
    }

    return `৳${Math.round(Number(amount) / 100).toLocaleString('en-BD')}`;
};

const productUrl = (product) => product?.slug ? `/products/${product.slug}` : '/products';

const imageStyle = (product, index) => ({
    backgroundImage: `url(${product.primary_image?.path || categoryCollageImage})`,
    backgroundPosition: product.image_position || fallbackPositions[index % fallbackPositions.length],
    backgroundSize: product.primary_image?.path ? 'cover' : '400% 200%',
});

const queryParams = computed(() => {
    const params = new URLSearchParams();
    params.set('per_page', '12');
    params.set('sort', filters.sort || 'latest');

    ['q', 'category', 'brand', 'price_max', 'page'].forEach((key) => {
        if (filters[key]) {
            params.set(key, filters[key]);
        }
    });

    return params;
});

const selectedCategory = computed(() => categories.value.find((category) => category.slug === filters.category) || null);
const selectedBrand = computed(() => brands.value.find((brand) => brand.slug === filters.brand) || null);
const selectedBudget = computed(() => copy.value.budgetOptions.find((option) => option.value === filters.price_max) || null);
const currentPage = computed(() => meta.value?.current_page ?? filters.page ?? 1);
const lastPage = computed(() => meta.value?.last_page ?? 1);

const activeFilterChips = computed(() => {
    const chips = [];

    if (filters.q) {
        chips.push({ key: 'q', label: filters.q });
    }

    if (selectedCategory.value) {
        chips.push({ key: 'category', label: selectedCategory.value.name });
    }

    if (selectedBrand.value) {
        chips.push({ key: 'brand', label: selectedBrand.value.name });
    }

    if (filters.price_max && selectedBudget.value) {
        chips.push({ key: 'price_max', label: selectedBudget.value.label });
    }

    return chips;
});

const productBadge = (product) => product.badge || (product.discount_percent ? `${product.discount_percent}% OFF` : null);
const productCategoryLabel = (product) => product.category?.name || product.brand?.name || '';

const categoryOptions = computed(() => [
    { label: copy.value.allCategories, value: '' },
    ...categories.value.map((category) => ({ label: category.name, value: category.slug })),
]);

const brandOptions = computed(() => [
    { label: copy.value.allBrands, value: '' },
    ...brands.value.map((brand) => ({ label: brand.name, value: brand.slug })),
]);

const pageSeo = computed(() => {
    const titleParts = [selectedCategory.value?.seo?.title || selectedCategory.value?.name, selectedBrand.value?.seo?.title || selectedBrand.value?.name, filters.q ? `Search: ${filters.q}` : null].filter(Boolean);
    const title = titleParts.length ? `${titleParts.join(' - ')} | Aurelia` : 'Products in Bangladesh | Aurelia';
    const description = selectedCategory.value?.seo?.description
        || selectedBrand.value?.seo?.description
        || 'Browse authentic electronics, fashion, beauty, home, grocery, and lifestyle products with nationwide Bangladesh delivery.';

    return { title, description };
});

const applyPageSeo = () => {
    setSeo({
        ...pageSeo.value,
        canonical: window.location.origin + window.location.pathname + window.location.search,
    });
};

const updateBrowserUrl = () => {
    const params = new URLSearchParams(queryParams.value);
    params.delete('per_page');
    const query = params.toString();
    window.history.replaceState({}, '', `/products${query ? `?${query}` : ''}`);
};

const requestJson = async (url) => {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error('Catalog request failed.');
    }

    return response.json();
};

const loadFilters = async () => {
    const [categoryPayload, brandPayload] = await Promise.all([
        requestJson('/api/storefront/categories'),
        requestJson('/api/storefront/brands'),
    ]);

    categories.value = Array.isArray(categoryPayload.data) ? categoryPayload.data : [];
    brands.value = Array.isArray(brandPayload.data) ? brandPayload.data : [];
};

const loadProducts = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const payload = await requestJson(`/api/storefront/products?${queryParams.value.toString()}`);
        products.value = Array.isArray(payload.data) ? payload.data : [];
        meta.value = payload.meta ?? null;
        updateBrowserUrl();
        applyPageSeo();
    } catch (catalogError) {
        error.value = catalogError.message;
    } finally {
        isLoading.value = false;
    }
};

const applyFilters = () => {
    filters.page = 1;
    loadProducts();
};

const clearFilters = () => {
    filters.q = '';
    filters.category = '';
    filters.brand = '';
    filters.price_max = '';
    filters.sort = 'latest';
    filters.page = 1;
    loadProducts();
};

const removeFilterChip = (key) => {
    filters[key] = '';
    applyFilters();
};

const goToPage = (page) => {
    const nextPage = Math.min(Math.max(page, 1), lastPage.value);

    if (nextPage === currentPage.value) {
        return;
    }

    filters.page = nextPage;
    loadProducts();
};

const loadFromUrl = () => {
    const params = new URLSearchParams(window.location.search);
    filters.q = params.get('q') ?? '';
    filters.category = params.get('category') ?? '';
    filters.brand = params.get('brand') ?? '';
    filters.price_max = params.get('price_max') ?? '';
    filters.sort = params.get('sort') ?? 'latest';
    filters.page = Number(params.get('page') ?? 1);
};

onMounted(async () => {
    loadFromUrl();
    await loadFilters();
    await loadProducts();
});
</script>

<template>
    <section class="bg-white py-8 transition-colors dark:bg-[#111111] sm:py-10 lg:py-14">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-12">
            <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[300px_minmax(0,1fr)]">
                <aside class="relative z-20 h-max overflow-visible rounded-[1.6rem] bg-ink p-5 text-white shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)] ring-1 ring-white/10 sm:p-6 lg:sticky lg:top-36">
                    <span class="pointer-events-none absolute inset-x-0 top-0 h-0.5 rounded-t-[1.6rem] bg-wine"></span>
                    <div class="pointer-events-none absolute -right-10 -top-10 size-28 rounded-full bg-wine/25 blur-2xl"></div>

                    <div class="relative flex items-start justify-between gap-3">
                        <div>
                            <p class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-wine">
                                <FontAwesomeIcon :icon="icons.grip" />
                                {{ copy.filters }}
                            </p>
                            <p class="mt-2 text-sm text-white/55">{{ copy.refine }}</p>
                        </div>
                        <button class="text-[11px] font-semibold uppercase tracking-wide text-white/55 transition hover:text-white" type="button" @click="clearFilters">{{ copy.clear }}</button>
                    </div>

                    <form class="relative mt-6 grid gap-4" @submit.prevent="applyFilters">
                        <label class="grid gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-white/45">{{ copy.search }}</span>
                            <span class="flex h-11 items-center gap-3 rounded-full bg-white/8 px-3.5 ring-1 ring-white/10">
                                <FontAwesomeIcon :icon="icons.search" class="text-wine" />
                                <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-medium text-white outline-none placeholder:text-white/35" type="search" :placeholder="copy.searchPlaceholder">
                            </span>
                        </label>

                        <label class="grid gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-white/45">{{ copy.category }}</span>
                            <PremiumSelect v-model="filters.category" :options="categoryOptions" />
                        </label>

                        <label class="grid gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-white/45">{{ copy.brand }}</span>
                            <PremiumSelect v-model="filters.brand" :options="brandOptions" />
                        </label>

                        <label class="grid gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-white/45">{{ copy.maxBudget }}</span>
                            <PremiumSelect v-model="filters.price_max" :options="copy.budgetOptions" />
                        </label>

                        <label class="grid gap-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-white/45">{{ copy.sort }}</span>
                            <PremiumSelect v-model="filters.sort" :options="copy.sorts" />
                        </label>

                        <button class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-wine px-5 text-[12px] font-semibold uppercase tracking-wide text-white transition hover:bg-white hover:text-ink" type="submit">
                            {{ copy.apply }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </button>
                    </form>
                </aside>

                <div>
                    <div class="relative overflow-hidden rounded-[1.6rem] bg-ink p-5 text-white shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.45)] sm:p-7">
                        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_88%_18%,rgb(143_38_56_/_0.42),transparent_42%)]"></div>
                        <div class="relative">
                            <p class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3.5 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-wine">
                                <FontAwesomeIcon :icon="icons.store" />
                                {{ copy.eyebrow }}
                            </p>
                            <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h1 class="max-w-3xl text-3xl font-semibold leading-tight sm:text-4xl lg:text-[2.75rem]">{{ copy.title }}</h1>
                                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/60 sm:text-base">{{ copy.subtitle }}</p>
                                </div>
                                <p class="inline-flex h-10 shrink-0 items-center rounded-full bg-wine px-4 text-[12px] font-semibold text-white">
                                    {{ copy.showing }} {{ meta?.total ?? products.length }} {{ copy.products }}
                                </p>
                            </div>

                            <div v-if="activeFilterChips.length" class="mt-5 flex flex-wrap gap-2">
                                <button
                                    v-for="chip in activeFilterChips"
                                    :key="chip.key"
                                    class="inline-flex h-8 items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 text-[11px] font-medium text-white/80 transition hover:border-wine hover:text-white"
                                    type="button"
                                    @click="removeFilterChip(chip.key)"
                                >
                                    {{ chip.label }}
                                    <FontAwesomeIcon :icon="icons.xmark" class="text-[9px] text-white/50" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <p v-if="wishlistError" class="mt-5 rounded-2xl border border-wine/20 bg-wine/8 px-4 py-3 text-sm font-semibold text-wine">{{ wishlistError }}</p>
                    <p v-if="error" class="mt-5 rounded-2xl border border-wine/20 bg-wine/8 px-4 py-3 text-sm font-semibold text-wine">{{ error }}</p>
                    <p v-else-if="isLoading" class="mt-5 rounded-2xl bg-ink/5 px-4 py-3 text-sm font-semibold text-ink/60 dark:bg-white/8 dark:text-white/60">{{ copy.loading }}</p>
                    <p v-else-if="!products.length" class="mt-5 rounded-2xl bg-ink/5 px-4 py-3 text-sm font-semibold text-ink/60 dark:bg-white/8 dark:text-white/60">{{ copy.empty }}</p>

                    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3">
                        <article
                            v-for="(product, index) in products"
                            :key="product.id"
                            class="group flex flex-col overflow-hidden rounded-[1.6rem] bg-[#141414] shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)] animate-card-in transition duration-300 hover:-translate-y-1"
                            :style="{ animationDelay: `${(index % 4) * 70}ms` }"
                        >
                            <div class="relative isolate overflow-hidden px-3 pb-2 pt-8">
                                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_40%,rgb(143_38_56_/_0.55),rgb(20_20_20)_68%)]"></div>
                                <button
                                    class="absolute right-3 top-3 z-10 grid size-8 place-items-center rounded-full bg-white/95 text-ink shadow-md transition hover:bg-wine hover:text-white disabled:opacity-50"
                                    :class="isWishlisted(product).value ? 'bg-wine text-white' : ''"
                                    type="button"
                                    :aria-label="copy.wishlist"
                                    :disabled="isToggling(product).value"
                                    @click="toggleWishlist(product, 'catalog_grid')"
                                >
                                    <FontAwesomeIcon :icon="icons.heart" class="text-xs" />
                                </button>
                                <span
                                    v-if="productBadge(product)"
                                    class="absolute left-3 top-3 z-10 max-w-[calc(100%-3.5rem)] truncate rounded-full bg-wine px-2.5 py-1 text-[10px] font-semibold text-white"
                                >
                                    {{ productBadge(product) }}
                                </span>
                                <div class="relative mx-auto mt-2 aspect-square w-[78%] overflow-hidden rounded-2xl">
                                    <div class="absolute inset-0 bg-no-repeat transition duration-500 group-hover:scale-105" :style="imageStyle(product, index)"></div>
                                </div>
                            </div>

                            <div class="mt-auto border-t border-white/8 px-3.5 pb-3.5 pt-3">
                                <p v-if="productCategoryLabel(product)" class="truncate text-[10px] font-semibold uppercase tracking-wide text-wine">
                                    {{ productCategoryLabel(product) }}
                                </p>
                                <a :href="productUrl(product)" class="mt-1.5 block line-clamp-2 min-h-[2.5rem] text-[13px] font-semibold leading-5 text-white transition hover:text-wine">
                                    {{ product.name }}
                                </a>
                                <div class="mt-2 flex items-center gap-1.5">
                                    <div class="flex items-center gap-px text-[9px] text-wine">
                                        <FontAwesomeIcon v-for="star in 5" :key="star" :icon="icons.star" />
                                    </div>
                                    <p v-if="product.rating" class="text-[10px] text-white/55">
                                        {{ product.rating }}
                                        <span v-if="product.reviews">({{ product.reviews }})</span>
                                    </p>
                                </div>
                                <div class="mt-3 flex items-end justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[15px] font-semibold tracking-tight text-white">{{ formatTaka(product.price) }}</p>
                                        <p v-if="product.compare_at_price" class="text-[11px] text-white/35 line-through">{{ formatTaka(product.compare_at_price) }}</p>
                                    </div>
                                    <p v-if="product.sold" class="mb-0.5 truncate text-[10px] font-medium text-white/45">{{ product.sold }}</p>
                                </div>
                                <button
                                    class="mt-3 inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-full bg-wine text-[11px] font-semibold uppercase tracking-wide text-white transition hover:bg-white hover:text-ink disabled:opacity-50"
                                    type="button"
                                    :aria-label="copy.addToCart"
                                    :disabled="isAdding(product).value"
                                    @click="addProduct(product)"
                                >
                                    <FontAwesomeIcon :icon="icons.cart" class="text-[10px]" />
                                    {{ copy.cart }}
                                </button>
                            </div>
                        </article>
                    </div>

                    <div v-if="lastPage > 1" class="mt-6 flex items-center justify-between gap-3">
                        <button
                            class="inline-flex h-10 items-center rounded-full border border-ink/10 px-4 text-[12px] font-semibold text-ink transition hover:border-wine hover:text-wine disabled:opacity-40 dark:border-white/10 dark:text-white"
                            type="button"
                            :disabled="currentPage <= 1 || isLoading"
                            @click="goToPage(currentPage - 1)"
                        >
                            {{ copy.prev }}
                        </button>
                        <p class="text-[12px] font-semibold text-ink/55 dark:text-white/55">{{ copy.page }} {{ currentPage }} / {{ lastPage }}</p>
                        <button
                            class="inline-flex h-10 items-center rounded-full border border-ink/10 px-4 text-[12px] font-semibold text-ink transition hover:border-wine hover:text-wine disabled:opacity-40 dark:border-white/10 dark:text-white"
                            type="button"
                            :disabled="currentPage >= lastPage || isLoading"
                            @click="goToPage(currentPage + 1)"
                        >
                            {{ copy.next }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
