import { computed, onMounted, ref } from 'vue';
import { customerLoginUrl, useAuth } from './useAuth';

const wishlistItems = ref([]);
const isLoadingWishlist = ref(false);
const wishlistError = ref(null);
const togglingProductIds = ref(new Set());
const hasLoadedWishlist = ref(false);

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers ?? {}),
        },
    });

    const payload = response.status === 204 ? null : await response.json();

    if (!response.ok) {
        throw new Error(payload?.message ?? 'Wishlist request failed.');
    }

    return payload;
};

const itemForProduct = (productId) => wishlistItems.value.find((item) => item.product?.id === productId || item.product_id === productId) ?? null;

export function useWishlist() {
    const { isLoggedIn, loadUser } = useAuth();

    const loadWishlist = async () => {
        await loadUser();

        if (!isLoggedIn.value) {
            wishlistItems.value = [];
            hasLoadedWishlist.value = false;
            return;
        }

        if (isLoadingWishlist.value || hasLoadedWishlist.value) {
            return;
        }

        isLoadingWishlist.value = true;
        wishlistError.value = null;

        try {
            const payload = await request('/api/customer/wishlist?per_page=50');
            wishlistItems.value = Array.isArray(payload.data) ? payload.data : [];
            hasLoadedWishlist.value = true;
        } catch (error) {
            wishlistError.value = error.message;
        } finally {
            isLoadingWishlist.value = false;
        }
    };

    const isWishlisted = (product) => computed(() => Boolean(product?.id && itemForProduct(product.id)));
    const isToggling = (product) => computed(() => Boolean(product?.id && togglingProductIds.value.has(product.id)));

    const toggleWishlist = async (product, source = 'storefront') => {
        if (!product?.id) {
            return null;
        }

        await loadUser();

        if (!isLoggedIn.value) {
            window.location.href = customerLoginUrl('/customer/dashboard?panel=wishlist');
            return null;
        }

        await loadWishlist();

        const nextToggling = new Set(togglingProductIds.value);
        nextToggling.add(product.id);
        togglingProductIds.value = nextToggling;
        wishlistError.value = null;

        try {
            const existingItem = itemForProduct(product.id);

            if (existingItem) {
                await request('/api/customer/wishlist/' + existingItem.id, { method: 'DELETE' });
                wishlistItems.value = wishlistItems.value.filter((item) => item.id !== existingItem.id);
                return null;
            }

            const payload = await request('/api/customer/wishlist', {
                method: 'POST',
                body: JSON.stringify({
                    product_id: product.id,
                    product_variant_id: product.product_variant_id ?? null,
                    source,
                    context: {
                        page: window.location.pathname,
                        slug: product.slug ?? null,
                    },
                }),
            });
            wishlistItems.value = [payload.data, ...wishlistItems.value.filter((item) => item.product?.id !== product.id)];
            hasLoadedWishlist.value = true;

            return payload.data;
        } catch (error) {
            wishlistError.value = error.message;
            return null;
        } finally {
            const clearedToggling = new Set(togglingProductIds.value);
            clearedToggling.delete(product.id);
            togglingProductIds.value = clearedToggling;
        }
    };

    onMounted(loadWishlist);

    return {
        isLoadingWishlist,
        isToggling,
        isWishlisted,
        loadWishlist,
        toggleWishlist,
        wishlistCount: computed(() => wishlistItems.value.length),
        wishlistError,
        wishlistItems: computed(() => wishlistItems.value),
    };
}
