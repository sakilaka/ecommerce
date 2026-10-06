<script setup>
import { computed, onMounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCart } from '../../composables/useCart';
import { useStorefront } from '../../composables/useStorefront';
import { useWishlist } from '../../composables/useWishlist';
import { useSeo } from '../../composables/useSeo';

const { categoryCollageImage, icons, locale } = useStorefront();
const { addProduct, isAdding } = useCart();
const { isToggling, isWishlisted, toggleWishlist, wishlistError } = useWishlist();
const { setSeo } = useSeo();

const detailCopy = {
    en: {
        back: 'Back to products',
        official: 'Official / Verified',
        loading: 'Loading product...',
        notFound: 'Product details could not be loaded.',
        sku: 'SKU',
        brand: 'Brand',
        category: 'Category',
        variant: 'Select variant',
        quantity: 'Quantity',
        addToCart: 'Add to Cart',
        buyNow: 'Buy Now',
        description: 'Product Details',
        delivery: 'Delivery & Service',
        nationwide: 'Nationwide delivery',
        nationwideText: 'Available across Bangladesh with district-based delivery charge.',
        cod: 'Cash on Delivery',
        codText: 'COD first checkout flow is ready for eligible zones.',
        returns: 'Return support',
        refundable: 'Refundable within {days} days when eligible.',
        nonRefundable: 'This product is marked non-refundable.',
        stock: 'Stock',
        inStock: 'In stock',
        lowStock: 'Low stock',
        soldOut: 'Sold out',
        oversell: 'Backorder allowed',
        wishlist: 'Wishlist',
        reviews: 'Customer Reviews',
        verifiedPurchase: 'Verified Purchase',
        noReviews: 'No approved reviews yet.',
        reviewSummary: '{count} reviews, {verified} verified',
        tabs: ['Overview', 'Delivery', 'Returns'],
    },
    bn: {
        back: 'Products এ ফিরে যান',
        official: 'Official / Verified',
        loading: 'Product loading...',
        notFound: 'Product details load করা যায়নি।',
        sku: 'SKU',
        brand: 'Brand',
        category: 'Category',
        variant: 'Variant নির্বাচন করুন',
        quantity: 'Quantity',
        addToCart: 'Cart এ যোগ করুন',
        buyNow: 'এখনই কিনুন',
        description: 'Product Details',
        delivery: 'Delivery & Service',
        nationwide: 'সারা দেশে delivery',
        nationwideText: 'Bangladesh এর district-based delivery charge সহ available.',
        cod: 'Cash on Delivery',
        codText: 'Eligible zone এ COD first checkout flow ready.',
        returns: 'Return support',
        refundable: 'Eligible হলে {days} দিনের মধ্যে refundable.',
        nonRefundable: 'এই product non-refundable হিসেবে marked.',
        stock: 'Stock',
        inStock: 'In stock',
        lowStock: 'Low stock',
        soldOut: 'Sold out',
        oversell: 'Backorder allowed',
        wishlist: 'উইশলিস্ট',
        reviews: 'Customer Reviews',
        verifiedPurchase: 'Verified Purchase',
        noReviews: 'এখনও approved review নেই।',
        reviewSummary: '{count} reviews, {verified} verified',
        tabs: ['Overview', 'Delivery', 'Returns'],
    },
};

const copy = computed(() => detailCopy[locale.value]);
const product = ref(null);
const selectedImage = ref(null);
const selectedVariantId = ref(null);
const quantity = ref(1);
const isLoading = ref(false);
const error = ref(null);
const reviews = ref([]);
const reviewSummary = ref({ average_rating: 0, reviews_count: 0, verified_count: 0 });
const reviewsError = ref(null);

const slug = computed(() => decodeURIComponent(window.location.pathname.replace(/^\/products\/?/, '').split('/')[0] || ''));
const variants = computed(() => (product.value?.variants || []).filter((variant) => variant.is_active));
const selectedVariant = computed(() => variants.value.find((variant) => variant.id === selectedVariantId.value) || variants.value[0] || null);
const gallery = computed(() => {
    const images = product.value?.images || [];

    if (images.length) {
        return images;
    }

    if (product.value?.primary_image) {
        return [product.value.primary_image];
    }

    return [{ path: categoryCollageImage, alt_text: product.value?.name || 'Product image' }];
});
const displayImage = computed(() => selectedImage.value || gallery.value[0]);
const currentPrice = computed(() => selectedVariant.value?.price ?? product.value?.price);
const previousPrice = computed(() => selectedVariant.value?.compare_at_price ?? product.value?.compare_at_price);
const availableStock = computed(() => selectedVariant.value?.stock_quantity ?? null);
const isOversellAllowed = computed(() => Boolean(selectedVariant.value?.oversell_allowed));
const canAddToCart = computed(() => product.value && (availableStock.value === null || availableStock.value > 0 || isOversellAllowed.value));
const wishlistProduct = computed(() => product.value ? {
    ...product.value,
    product_variant_id: selectedVariant.value?.id ?? null,
} : null);

const formatTaka = (amount) => {
    if (amount === null || amount === undefined) {
        return '';
    }

    return '৳' + Math.round(Number(amount) / 100).toLocaleString('en-BD');
};

const imageStyle = (image) => ({
    backgroundImage: 'url(' + (image?.path || categoryCollageImage) + ')',
});

const stockLabel = computed(() => {
    if (availableStock.value === null) {
        return copy.value.inStock;
    }

    if (availableStock.value <= 0 && isOversellAllowed.value) {
        return copy.value.oversell;
    }

    if (availableStock.value <= 0) {
        return copy.value.soldOut;
    }

    if (availableStock.value <= (selectedVariant.value?.low_stock_threshold ?? 5)) {
        return copy.value.lowStock;
    }

    return copy.value.inStock;
});

const returnText = computed(() => {
    if (!product.value?.is_refundable) {
        return copy.value.nonRefundable;
    }

    return copy.value.refundable.replace('{days}', product.value.return_window_days || 7);
});

const reviewSummaryText = computed(() => copy.value.reviewSummary
    .replace('{count}', reviewSummary.value.reviews_count || 0)
    .replace('{verified}', reviewSummary.value.verified_count || 0));

const loadReviews = async () => {
    if (!slug.value) {
        return;
    }

    reviewsError.value = null;

    try {
        const response = await fetch('/api/storefront/products/' + slug.value + '/reviews', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(copy.value.notFound);
        }

        const payload = await response.json();
        reviews.value = Array.isArray(payload.data) ? payload.data : [];
        reviewSummary.value = payload.summary ?? reviewSummary.value;
    } catch (reviewError) {
        reviewsError.value = reviewError.message;
    }
};

const loadProduct = async () => {
    if (!slug.value) {
        error.value = copy.value.notFound;
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/storefront/products/' + slug.value, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(copy.value.notFound);
        }

        const payload = await response.json();
        product.value = payload.data;
        selectedImage.value = gallery.value[0];
        selectedVariantId.value = variants.value[0]?.id ?? null;
        const productUrl = window.location.origin + '/products/' + product.value.slug;
        const productImage = product.value?.primary_image?.path || gallery.value[0]?.path || categoryCollageImage;
        const schemaPrice = Number(currentPrice.value || 0) / 100;

        setSeo({
            title: product.value?.seo?.title ? product.value.seo.title + ' | Aurelia' : product.value?.name + ' | Aurelia',
            description: product.value?.seo?.description || product.value?.short_description,
            canonical: productUrl,
            image: productImage,
            type: 'product',
            structuredData: [{
                id: 'aurelia-product-schema',
                data: {
                    '@context': 'https://schema.org',
                    '@type': 'Product',
                    name: product.value.name,
                    description: product.value?.seo?.description || product.value?.short_description || product.value.name,
                    sku: selectedVariant.value?.sku || product.value.sku,
                    image: [productImage].filter(Boolean).map((image) => image.startsWith('http') ? image : window.location.origin + image),
                    brand: product.value?.brand?.name ? { '@type': 'Brand', name: product.value.brand.name } : undefined,
                    category: product.value?.category?.name,
                    aggregateRating: product.value?.rating ? {
                        '@type': 'AggregateRating',
                        ratingValue: product.value.rating,
                        reviewCount: product.value.reviews || 1,
                    } : undefined,
                    offers: {
                        '@type': 'Offer',
                        url: productUrl,
                        priceCurrency: 'BDT',
                        price: schemaPrice.toFixed(2),
                        availability: canAddToCart.value ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        itemCondition: 'https://schema.org/NewCondition',
                    },
                },
            }],
        });
    } catch (productError) {
        error.value = productError.message;
    } finally {
        isLoading.value = false;
    }
};

const addSelectedToCart = async (openAfterAdd = false) => {
    if (!product.value || !canAddToCart.value) {
        return;
    }

    await addProduct({
        ...product.value,
        product_variant_id: selectedVariant.value?.id ?? null,
    }, quantity.value);

    if (openAfterAdd) {
        window.dispatchEvent(new CustomEvent('aurelia:checkout-open'));
    }
};

onMounted(async () => {
    await loadProduct();
    await loadReviews();
});
</script>

<template>
    <section class="bg-[#f6f6f6] py-8 transition-colors dark:bg-[#111111] sm:py-10 lg:py-14">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-12">
            <a href="/products" class="inline-flex h-11 items-center gap-3 rounded-lg bg-white/82 px-4 text-sm font-black text-[#171512] shadow-sm ring-1 ring-black/5 transition hover:text-[#8f2638] dark:bg-white/8 dark:text-white dark:ring-white/10 dark:hover:text-[#ff8aa0]">
                <FontAwesomeIcon :icon="icons.arrowRight" class="rotate-180" />
                {{ copy.back }}
            </a>

            <p v-if="wishlistError" class="mt-6 rounded-lg border border-[#b4233c]/20 bg-[#fff1f3] px-5 py-4 text-sm font-black text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#2b1117] dark:text-[#ff8aa0]">{{ wishlistError }}</p>
            <p v-if="isLoading" class="mt-6 rounded-lg bg-white/76 px-5 py-4 text-sm font-black text-[#374151] ring-1 ring-black/5 dark:bg-white/8 dark:text-[#e5e7eb] dark:ring-white/10">{{ copy.loading }}</p>
            <p v-else-if="error" class="mt-6 rounded-lg border border-[#b4233c]/20 bg-[#fff1f3] px-5 py-4 text-sm font-black text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#2b1117] dark:text-[#ff8aa0]">{{ error }}</p>

            <div v-else-if="product" class="mt-6 grid gap-6 lg:grid-cols-[0.56fr_0.44fr] xl:gap-8">
                <div class="grid gap-4 lg:grid-cols-[5.5rem_1fr]">
                    <div class="order-last flex gap-3 overflow-x-auto [scrollbar-width:none] dark:[color-scheme:dark] lg:order-none lg:flex-col lg:overflow-visible [&::-webkit-scrollbar]:hidden">
                        <button v-for="image in gallery" :key="image.path" class="size-20 shrink-0 overflow-hidden rounded-lg bg-white ring-2 transition dark:bg-white/8" :class="displayImage?.path === image.path ? 'ring-[#8f2638] dark:ring-[#9f243a]' : 'ring-black/5 dark:ring-white/10'" type="button" @click="selectedImage = image">
                            <span class="block size-full bg-cover bg-center" :style="imageStyle(image)"></span>
                        </button>
                    </div>

                    <div class="relative overflow-hidden rounded-lg bg-white shadow-2xl shadow-black/10 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                        <div class="aspect-[4/5] bg-cover bg-center sm:aspect-[5/4] lg:aspect-[4/4.35]" :style="imageStyle(displayImage)"></div>
                        <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                            <span v-if="product.badge" class="rounded-full bg-white/92 px-3 py-1.5 text-xs font-black text-[#8f2638] shadow-sm ring-1 ring-black/5 backdrop-blur dark:bg-[#171512]/88 dark:text-[#ff8aa0] dark:ring-white/10">{{ product.badge }}</span>
                            <span v-if="product.discount_percent" class="rounded-full bg-[#8f2638] px-3 py-1.5 text-xs font-black text-white shadow-sm dark:bg-[#9f243a] dark:text-white">{{ product.discount_percent }}% OFF</span>
                        </div>
                    </div>
                </div>

                <article class="h-max rounded-lg bg-white/86 p-5 shadow-2xl shadow-black/8 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10 sm:p-7 lg:sticky lg:top-36">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-[#f6f6f6] px-3 py-1.5 text-xs font-black uppercase tracking-normal text-[#8f2638] dark:bg-white/10 dark:text-[#ff8aa0]">
                            <FontAwesomeIcon :icon="icons.shield" />
                            {{ product.brand?.is_official ? copy.official : product.category?.name }}
                        </span>
                        <span class="rounded-full bg-[#fff5f6] px-3 py-1.5 text-xs font-black text-[#8f2638] dark:bg-[#9f243a]/15 dark:text-[#ff8aa0]">{{ stockLabel }}</span>
                        <button class="ml-auto inline-flex size-10 items-center justify-center rounded-full bg-[#f6f6f6] text-[#171512] shadow-sm ring-1 ring-black/5 transition hover:bg-[#8f2638] hover:text-white disabled:cursor-not-allowed disabled:opacity-50 dark:bg-black/20 dark:text-white dark:ring-white/10 dark:hover:bg-[#9f243a] dark:hover:text-white" :class="isWishlisted(wishlistProduct).value ? 'bg-[#8f2638] text-white dark:bg-[#9f243a] dark:text-white' : ''" type="button" :aria-label="copy.wishlist" :disabled="isToggling(wishlistProduct).value" @click="toggleWishlist(wishlistProduct, 'product_detail')">
                            <FontAwesomeIcon :icon="icons.heart" />
                        </button>
                    </div>

                    <h1 class="mt-5 font-serif text-4xl leading-[1.08] text-[#171512] dark:text-white sm:text-5xl lg:text-[3.25rem]">{{ product.name }}</h1>
                    <p class="mt-4 text-base leading-8 text-[#374151] dark:text-[#e5e7eb]">{{ product.short_description }}</p>

                    <div class="mt-5 grid grid-cols-3 divide-x divide-black/10 overflow-hidden rounded-lg bg-[#f6f6f6] ring-1 ring-black/5 dark:divide-white/10 dark:bg-black/20 dark:ring-white/10">
                        <div class="p-3">
                            <p class="text-[11px] font-black uppercase text-[#6b7280]">{{ copy.sku }}</p>
                            <p class="mt-1 truncate text-sm font-black">{{ selectedVariant?.sku || product.sku }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-[11px] font-black uppercase text-[#6b7280]">{{ copy.brand }}</p>
                            <p class="mt-1 truncate text-sm font-black">{{ product.brand?.name || 'Aurelia' }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-[11px] font-black uppercase text-[#6b7280]">{{ copy.category }}</p>
                            <p class="mt-1 truncate text-sm font-black">{{ product.category?.name || 'Premium' }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-end gap-3">
                        <p class="text-3xl font-black text-[#8f2638] dark:text-[#ff8aa0]">{{ formatTaka(currentPrice) }}</p>
                        <p v-if="previousPrice" class="pb-1 text-base font-bold text-[#6b7280] line-through dark:text-[#d1d5db]">{{ formatTaka(previousPrice) }}</p>
                    </div>

                    <label v-if="variants.length" class="mt-6 grid gap-2">
                        <span class="text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#e5e7eb]">{{ copy.variant }}</span>
                        <select v-model="selectedVariantId" class="h-12 rounded-lg bg-[#f6f6f6] px-3 text-sm font-bold outline-none ring-1 ring-black/8 dark:bg-black/20 dark:text-white dark:ring-white/10">
                            <option v-for="variant in variants" :key="variant.id" :value="variant.id">{{ variant.name }} - {{ variant.sku }}</option>
                        </select>
                    </label>

                    <div class="mt-5 grid gap-3 sm:grid-cols-[7rem_1fr]">
                        <label class="grid gap-2">
                            <span class="text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#e5e7eb]">{{ copy.quantity }}</span>
                            <input v-model.number="quantity" class="h-12 rounded-lg bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/8 dark:bg-black/20 dark:text-white dark:ring-white/10" type="number" min="1" max="99">
                        </label>
                        <div class="grid content-end gap-3 sm:grid-cols-2">
                            <button class="inline-flex h-12 items-center justify-center gap-3 rounded-lg bg-[#171512] px-5 text-sm font-black text-white shadow-xl shadow-black/15 transition hover:bg-[#27272a] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white/10 dark:text-white dark:hover:bg-white/15" type="button" :disabled="!canAddToCart || isAdding(product).value" @click="addSelectedToCart()">
                                <FontAwesomeIcon :icon="icons.cart" />
                                {{ copy.addToCart }}
                            </button>
                            <button class="inline-flex h-12 items-center justify-center gap-3 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-xl shadow-[#8f2638]/20 transition hover:bg-[#741c2b] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#9f243a] dark:text-white dark:hover:bg-[#861e31]" type="button" :disabled="!canAddToCart || isAdding(product).value" @click="addSelectedToCart(true)">
                                {{ copy.buyNow }}
                                <FontAwesomeIcon :icon="icons.arrowRight" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                        <div class="rounded-lg bg-[#f6f6f6] p-4 dark:bg-black/20">
                            <FontAwesomeIcon :icon="icons.truck" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                            <p class="mt-3 text-sm font-black">{{ copy.nationwide }}</p>
                            <p class="mt-1 text-xs leading-5 text-[#6b7280] dark:text-[#e5e7eb]">{{ copy.nationwideText }}</p>
                        </div>
                        <div class="rounded-lg bg-[#f6f6f6] p-4 dark:bg-black/20">
                            <FontAwesomeIcon :icon="icons.shield" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                            <p class="mt-3 text-sm font-black">{{ copy.cod }}</p>
                            <p class="mt-1 text-xs leading-5 text-[#6b7280] dark:text-[#e5e7eb]">{{ copy.codText }}</p>
                        </div>
                        <div class="rounded-lg bg-[#f6f6f6] p-4 dark:bg-black/20">
                            <FontAwesomeIcon :icon="icons.returns" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                            <p class="mt-3 text-sm font-black">{{ copy.returns }}</p>
                            <p class="mt-1 text-xs leading-5 text-[#6b7280] dark:text-[#e5e7eb]">{{ returnText }}</p>
                        </div>
                    </div>
                </article>
            </div>

            <section v-if="product" class="mt-8 rounded-lg bg-white/78 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10 sm:p-7">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.description }}</p>
                <p class="mt-4 max-w-4xl text-base leading-8 text-[#374151] dark:text-[#e5e7eb]">{{ product.description || product.short_description }}</p>
            </section>

            <section v-if="product" class="mt-6 rounded-lg bg-white/82 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10 sm:p-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.reviews }}</p>
                        <h2 class="mt-3 font-serif text-3xl leading-tight text-[#171512] dark:text-white sm:text-4xl">{{ reviewSummary.average_rating || '0.0' }}/5</h2>
                        <p class="mt-2 text-sm font-semibold text-[#6b7280] dark:text-[#e5e7eb]">{{ reviewSummaryText }}</p>
                    </div>
                    <div class="flex gap-1 text-[#9f243a]">
                        <FontAwesomeIcon v-for="star in 5" :key="star" :icon="icons.star" />
                    </div>
                </div>

                <p v-if="reviewsError" class="mt-5 rounded-lg bg-[#fff1f3] px-4 py-3 text-sm font-black text-[#8f2638] dark:bg-[#2b1117] dark:text-[#ff8aa0]">{{ reviewsError }}</p>
                <p v-else-if="!reviews.length" class="mt-5 rounded-lg bg-[#f6f6f6] px-4 py-3 text-sm font-black text-[#374151] dark:bg-black/20 dark:text-[#e5e7eb]">{{ copy.noReviews }}</p>

                <div v-else class="mt-5 grid gap-3 md:grid-cols-3">
                    <article v-for="review in reviews" :key="review.id" class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-[#171512] dark:text-white">{{ review.customer_name || 'Aurelia Customer' }}</p>
                                <p v-if="review.is_verified_purchase" class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-black text-[#8f2638] dark:bg-white/10 dark:text-[#ff8aa0]">
                                    <FontAwesomeIcon :icon="icons.shield" />
                                    {{ copy.verifiedPurchase }}
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-sm font-black text-[#8f2638] dark:text-[#ff8aa0]">
                                <FontAwesomeIcon :icon="icons.star" />
                                {{ review.rating }}
                            </span>
                        </div>
                        <h3 class="mt-4 line-clamp-1 text-base font-black text-[#171512] dark:text-white">{{ review.title }}</h3>
                        <p class="mt-2 line-clamp-4 text-sm leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ review.body }}</p>
                    </article>
                </div>
            </section>
        </div>
    </section>
</template>
