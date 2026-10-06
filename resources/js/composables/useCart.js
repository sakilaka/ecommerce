import { computed, onMounted, ref } from 'vue';

const CART_SESSION_KEY = 'aurelia_cart_session_id';

const cart = ref(null);
const deliveryZones = ref([]);
const paymentMethods = ref([]);
const order = ref(null);
const couponPreview = ref(null);
const isCartOpen = ref(false);
const isLoading = ref(false);
const isMutating = ref(false);
const isCheckingOut = ref(false);
const isApplyingCoupon = ref(false);
const isSubmittingPaymentProof = ref(false);
const addingProductIds = ref(new Set());
const error = ref(null);
const checkoutError = ref(null);
const paymentRedirectUrl = ref(null);
const hasLoaded = ref(false);
const hasLoadedDeliveryZones = ref(false);
const hasLoadedPaymentMethods = ref(false);

const storedSessionId = () => window.localStorage.getItem(CART_SESSION_KEY);
const clearSessionId = () => window.localStorage.removeItem(CART_SESSION_KEY);
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const rememberSessionId = (sessionId) => {
    if (sessionId) {
        window.localStorage.setItem(CART_SESSION_KEY, sessionId);
    }
};

const request = async (url, options = {}) => {
    const sessionId = storedSessionId();
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(sessionId ? { 'X-Cart-Session': sessionId } : {}),
            ...(options.headers ?? {}),
        },
    });

    const payload = response.status === 204 ? null : await response.json();

    if (!response.ok) {
        throw new Error(payload?.message ?? 'Cart request failed.');
    }

    if (payload?.data?.cart_session_id) {
        rememberSessionId(payload.data.cart_session_id);
    }

    return payload;
};

const syncCart = async () => {
    if (isLoading.value) {
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        const payload = await request('/api/storefront/cart');
        cart.value = payload.data;
        hasLoaded.value = true;
    } catch (cartError) {
        error.value = cartError.message;
    } finally {
        isLoading.value = false;
    }
};

const loadDeliveryZones = async () => {
    if (hasLoadedDeliveryZones.value) {
        return;
    }

    try {
        const payload = await request('/api/storefront/delivery-zones');
        deliveryZones.value = Array.isArray(payload.data) ? payload.data : [];
        hasLoadedDeliveryZones.value = true;
    } catch (zoneError) {
        checkoutError.value = zoneError.message;
    }
};

const loadPaymentMethods = async () => {
    if (hasLoadedPaymentMethods.value) {
        return;
    }

    try {
        const payload = await request('/api/storefront/payment-methods');
        paymentMethods.value = Array.isArray(payload.data) ? payload.data : [];
        hasLoadedPaymentMethods.value = true;
    } catch (paymentError) {
        checkoutError.value = paymentError.message;
    }
};

const addProduct = async (product, quantity = 1) => {
    if (!product?.id || addingProductIds.value.has(product.id)) {
        return;
    }

    addingProductIds.value = new Set([...addingProductIds.value, product.id]);
    isMutating.value = true;
    error.value = null;

    try {
        const payload = await request('/api/storefront/cart/items', {
            method: 'POST',
            body: JSON.stringify({
                product_id: product.id,
                product_variant_id: product.product_variant_id ?? null,
                quantity,
            }),
        });
        cart.value = payload.data;
        isCartOpen.value = true;
    } catch (cartError) {
        error.value = cartError.message;
    } finally {
        const nextAddingIds = new Set(addingProductIds.value);
        nextAddingIds.delete(product.id);
        addingProductIds.value = nextAddingIds;
        isMutating.value = false;
    }
};

const updateItem = async (item, quantity) => {
    if (!item?.id || quantity < 1) {
        return;
    }

    isMutating.value = true;
    error.value = null;

    try {
        const payload = await request(`/api/storefront/cart/items/${item.id}`, {
            method: 'PATCH',
            body: JSON.stringify({ quantity }),
        });
        cart.value = payload.data;
    } catch (cartError) {
        error.value = cartError.message;
    } finally {
        isMutating.value = false;
    }
};

const removeItem = async (item) => {
    if (!item?.id) {
        return;
    }

    isMutating.value = true;
    error.value = null;

    try {
        const payload = await request(`/api/storefront/cart/items/${item.id}`, {
            method: 'DELETE',
        });
        cart.value = payload.data;
    } catch (cartError) {
        error.value = cartError.message;
    } finally {
        isMutating.value = false;
    }
};

const applyCoupon = async (couponCode) => {
    if (!couponCode) {
        couponPreview.value = null;
        checkoutError.value = null;
        return null;
    }

    isApplyingCoupon.value = true;
    checkoutError.value = null;

    try {
        const payload = await request('/storefront/coupon-preview', {
            method: 'POST',
            body: JSON.stringify({ coupon_code: couponCode }),
        });
        couponPreview.value = payload.data;

        return payload.data;
    } catch (couponError) {
        couponPreview.value = null;
        checkoutError.value = couponError.message;
        return null;
    } finally {
        isApplyingCoupon.value = false;
    }
};

const clearCoupon = () => {
    couponPreview.value = null;
    checkoutError.value = null;
};

const initiatePayment = async (placedOrder, checkout) => {
    const payment = placedOrder?.payments?.[0] ?? null;

    if (!payment?.id || checkout.paymentMethod !== 'sslcommerz') {
        return null;
    }

    const payload = await request(`/api/storefront/payments/${payment.id}/initiate`, {
        method: 'POST',
        body: JSON.stringify({
            order_number: placedOrder.order_number,
            customer_phone: checkout.phone,
        }),
    });

    paymentRedirectUrl.value = payload.data?.redirect_url ?? null;

    if (paymentRedirectUrl.value) {
        window.location.href = paymentRedirectUrl.value;
    }

    return payload.data ?? null;
};

const placeOrder = async (checkout) => {
    isCheckingOut.value = true;
    checkoutError.value = null;

    try {
        const payload = await request('/storefront/checkout', {
            method: 'POST',
            body: JSON.stringify({
                payment_method: checkout.paymentMethod || 'cod',
                customer_address_id: checkout.customerAddressId || null,
                coupon_code: checkout.couponCode || null,
                customer: {
                    name: checkout.name,
                    phone: checkout.phone,
                    email: checkout.email || null,
                },
                shipping: {
                    label: 'Home',
                    district: checkout.district,
                    area: checkout.area || null,
                    postal_code: checkout.postalCode || null,
                    address_line: checkout.addressLine,
                },
                customer_note: checkout.customerNote || null,
            }),
        });

        order.value = payload.data;

        if (checkout.paymentMethod === 'sslcommerz') {
            await initiatePayment(payload.data, checkout);
        }

        couponPreview.value = null;
        cart.value = null;
        hasLoaded.value = false;
        clearSessionId();

        return payload.data;
    } catch (checkoutRequestError) {
        checkoutError.value = checkoutRequestError.message;
        return null;
    } finally {
        isCheckingOut.value = false;
    }
};

const submitManualPaymentProof = async (payment, proof) => {
    if (!payment?.id || !order.value) {
        return null;
    }

    isSubmittingPaymentProof.value = true;
    checkoutError.value = null;

    try {
        const payload = await request(`/api/storefront/payments/${payment.id}/manual-confirmation`, {
            method: 'POST',
            body: JSON.stringify({
                order_number: order.value.order_number,
                customer_phone: order.value.customer_phone,
                sender_phone: proof.senderPhone,
                transaction_id: proof.transactionId,
                method: payment.method,
                note: proof.note || null,
            }),
        });

        const updatedPayment = payload.data;
        order.value = {
            ...order.value,
            payments: order.value.payments.map((existingPayment) => existingPayment.id === updatedPayment.id ? updatedPayment : existingPayment),
        };

        return updatedPayment;
    } catch (proofError) {
        checkoutError.value = proofError.message;
        return null;
    } finally {
        isSubmittingPaymentProof.value = false;
    }
};

const openCart = async () => {
    isCartOpen.value = true;

    if (!hasLoaded.value) {
        await syncCart();
    }
};

const closeCart = () => {
    isCartOpen.value = false;
};

const resetOrder = () => {
    order.value = null;
};

export function useCart() {
    onMounted(() => {
        if (!hasLoaded.value) {
            syncCart();
        }
    });

    return {
        addProduct,
        applyCoupon,
        clearCoupon,
        cart,
        checkoutError,
        couponPreview: computed(() => couponPreview.value),
        closeCart,
        deliveryZones: computed(() => deliveryZones.value),
        error,
        isAdding: (product) => computed(() => Boolean(product?.id && addingProductIds.value.has(product.id))),
        isApplyingCoupon,
        isCartOpen,
        isCheckingOut,
        isLoading,
        isMutating,
        isSubmittingPaymentProof,
        itemCount: computed(() => cart.value?.item_count ?? 0),
        items: computed(() => cart.value?.items ?? []),
        loadDeliveryZones,
        loadPaymentMethods,
        openCart,
        order: computed(() => order.value),
        paymentRedirectUrl: computed(() => paymentRedirectUrl.value),
        paymentMethods: computed(() => paymentMethods.value),
        placeOrder,
        removeItem,
        resetOrder,
        submitManualPaymentProof,
        subtotal: computed(() => cart.value?.subtotal ?? 0),
        syncCart,
        updateItem,
    };
}
