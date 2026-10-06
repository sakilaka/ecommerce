import { computed, onMounted, ref } from 'vue';

const products = ref([]);
const isLoading = ref(false);
const error = ref(null);
const hasLoaded = ref(false);

const categoryTabMap = {
    'Home & Living': 'Home',
    Groceries: 'Home',
    Kids: 'Fashion',
};

const formatTaka = (amount) => {
    if (amount === null || amount === undefined) {
        return '';
    }

    return `৳${Math.round(Number(amount) / 100).toLocaleString('en-BD')}`;
};

const arrivalLabel = (publishedAt, fallback = 'New') => {
    if (!publishedAt) {
        return fallback;
    }

    const publishedDate = new Date(publishedAt);
    const differenceInDays = Math.max(0, Math.floor((Date.now() - publishedDate.getTime()) / 86400000));

    if (differenceInDays === 0) {
        return 'Today';
    }

    if (differenceInDays === 1) {
        return '1 day';
    }

    return `${differenceInDays} days`;
};

const mapProduct = (product, fallback = {}) => {
    const categoryName = product.category?.name ?? fallback.category ?? 'Popular';

    return {
        ...fallback,
        id: product.id,
        slug: product.slug,
        name: product.name ?? fallback.name,
        category: categoryTabMap[categoryName] ?? categoryName,
        badge: product.badge ?? fallback.badge ?? 'Featured',
        rating: product.rating ?? fallback.rating ?? '4.8',
        reviews: product.reviews ?? fallback.reviews ?? 'New',
        sold: product.sold ?? fallback.sold ?? '',
        price: formatTaka(product.price) || fallback.price,
        previousPrice: formatTaka(product.compare_at_price) || fallback.previousPrice,
        position: product.image_position ?? fallback.position ?? '0% 0%',
        arrived: arrivalLabel(product.published_at, fallback.arrived),
        image: product.primary_image?.path ?? fallback.image,
    };
};

const loadProducts = async () => {
    if (hasLoaded.value || isLoading.value) {
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/storefront/products?per_page=12&sort=featured', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Catalog request failed');
        }

        const payload = await response.json();
        products.value = Array.isArray(payload.data) ? payload.data : [];
        hasLoaded.value = true;
    } catch (catalogError) {
        error.value = catalogError;
    } finally {
        isLoading.value = false;
    }
};

export function useCatalogProducts() {
    onMounted(loadProducts);

    const mappedProducts = computed(() => products.value.map((product) => mapProduct(product)));

    const mergeWithFallback = (fallbackProducts = [], limit = fallbackProducts.length) => {
        if (!products.value.length) {
            return fallbackProducts.slice(0, limit);
        }

        return products.value.slice(0, limit).map((product, index) => mapProduct(product, fallbackProducts[index] ?? {}));
    };

    return {
        error,
        isLoading,
        loadProducts,
        mapProduct,
        mergeWithFallback,
        products: mappedProducts,
    };
}
