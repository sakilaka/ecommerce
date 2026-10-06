import { computed, onMounted, ref } from 'vue';

export const CUSTOMER_DASHBOARD_PATH = '/customer/dashboard';
export const CUSTOMER_LOGIN_PATH = '/login';

export const safeCustomerPath = (value) => {
    if (typeof value !== 'string' || value === '') {
        return CUSTOMER_DASHBOARD_PATH;
    }

    if (!value.startsWith('/') || value.startsWith('//') || value.includes('\\') || value.includes('://')) {
        return CUSTOMER_DASHBOARD_PATH;
    }

    if (value.startsWith('/admin') || value.startsWith('/login') || value.startsWith('/register')) {
        return CUSTOMER_DASHBOARD_PATH;
    }

    return value;
};

export const customerLoginUrl = (next = CUSTOMER_DASHBOARD_PATH) => {
    const path = safeCustomerPath(next);

    if (path === CUSTOMER_DASHBOARD_PATH) {
        return CUSTOMER_LOGIN_PATH;
    }

    return `${CUSTOMER_LOGIN_PATH}?next=${encodeURIComponent(path)}`;
};

export const redirectToIntendedCustomerPath = () => {
    const next = new URLSearchParams(window.location.search).get('next');
    window.location.href = safeCustomerPath(next);
};

const user = ref(null);
const isAuthOpen = ref(false);
const isAuthLoading = ref(false);
const authError = ref(null);
const orders = ref([]);
const returnRequests = ref([]);
const productReviews = ref([]);
const supportTickets = ref([]);
const addresses = ref([]);
const trackedOrder = ref(null);
const selectedOrder = ref(null);
const orderError = ref(null);
const returnError = ref(null);
const reviewError = ref(null);
const supportError = ref(null);
const addressError = ref(null);
const isAddressLoading = ref(false);
const isOrderLoading = ref(false);
const isReturnLoading = ref(false);
const isReviewLoading = ref(false);
const isSupportLoading = ref(false);
const hasLoadedUser = ref(false);

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const cartSessionId = () => window.localStorage.getItem('aurelia_cart_session_id');

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(cartSessionId() ? { 'X-Cart-Session': cartSessionId() } : {}),
            ...(options.headers ?? {}),
        },
    });

    const payload = response.status === 204 ? null : await response.json();

    if (!response.ok) {
        throw new Error(payload?.message ?? 'Authentication request failed.');
    }

    return payload;
};

const loadUser = async () => {
    if (hasLoadedUser.value) {
        return;
    }

    try {
        const payload = await request('/auth/user');
        user.value = payload.data;
        hasLoadedUser.value = true;
    } catch {
        user.value = null;
    }
};

const login = async (credentials) => {
    isAuthLoading.value = true;
    authError.value = null;

    try {
        const payload = await request('/auth/login', {
            method: 'POST',
            body: JSON.stringify(credentials),
        });
        user.value = payload.data;
        isAuthOpen.value = false;

        return payload.data;
    } catch (error) {
        authError.value = error.message;
        return null;
    } finally {
        isAuthLoading.value = false;
    }
};

const register = async (details) => {
    isAuthLoading.value = true;
    authError.value = null;

    try {
        const payload = await request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(details),
        });
        user.value = payload.data;
        isAuthOpen.value = false;

        return payload.data;
    } catch (error) {
        authError.value = error.message;
        return null;
    } finally {
        isAuthLoading.value = false;
    }
};

const updateProfile = async (details) => {
    isAuthLoading.value = true;
    authError.value = null;

    try {
        const payload = await request('/auth/profile', {
            method: 'PATCH',
            body: JSON.stringify(details),
        });
        user.value = payload.data;

        return payload.data;
    } catch (error) {
        authError.value = error.message;
        return null;
    } finally {
        isAuthLoading.value = false;
    }
};

const updatePassword = async (details) => {
    isAuthLoading.value = true;
    authError.value = null;

    try {
        await request('/auth/password', {
            method: 'PATCH',
            body: JSON.stringify(details),
        });

        return true;
    } catch (error) {
        authError.value = error.message;
        return false;
    } finally {
        isAuthLoading.value = false;
    }
};

const logout = async () => {
    isAuthLoading.value = true;
    authError.value = null;

    try {
        await request('/auth/logout', { method: 'POST' });
        user.value = null;
        addresses.value = [];
        orders.value = [];
        returnRequests.value = [];
        productReviews.value = [];
        supportTickets.value = [];
        selectedOrder.value = null;
    } catch (error) {
        authError.value = error.message;
    } finally {
        isAuthLoading.value = false;
    }
};

const openAuth = () => {
    isAuthOpen.value = true;
};

const closeAuth = () => {
    isAuthOpen.value = false;
};

const loadAddresses = async () => {
    if (!user.value) {
        addresses.value = [];
        return;
    }

    isAddressLoading.value = true;
    addressError.value = null;

    try {
        const payload = await request('/customer/addresses');
        addresses.value = Array.isArray(payload.data) ? payload.data : [];
    } catch (error) {
        addressError.value = error.message;
    } finally {
        isAddressLoading.value = false;
    }
};

const saveAddress = async (details) => {
    isAddressLoading.value = true;
    addressError.value = null;

    try {
        const isUpdate = Boolean(details.id);
        const payload = await request(isUpdate ? `/customer/addresses/${details.id}` : '/customer/addresses', {
            method: isUpdate ? 'PATCH' : 'POST',
            body: JSON.stringify(details),
        });
        await loadAddresses();

        return payload.data;
    } catch (error) {
        addressError.value = error.message;
        return null;
    } finally {
        isAddressLoading.value = false;
    }
};

const deleteAddress = async (addressId) => {
    isAddressLoading.value = true;
    addressError.value = null;

    try {
        await request(`/customer/addresses/${addressId}`, { method: 'DELETE' });
        addresses.value = addresses.value.filter((address) => address.id !== addressId);

        return true;
    } catch (error) {
        addressError.value = error.message;
        return false;
    } finally {
        isAddressLoading.value = false;
    }
};

const loadOrders = async () => {
    if (!user.value) {
        return;
    }

    isOrderLoading.value = true;
    orderError.value = null;

    try {
        const payload = await request('/customer/orders');
        orders.value = Array.isArray(payload.data) ? payload.data : [];

        if (selectedOrder.value) {
            selectedOrder.value = orders.value.find((order) => order.id === selectedOrder.value.id) ?? selectedOrder.value;
        }
    } catch (error) {
        orderError.value = error.message;
    } finally {
        isOrderLoading.value = false;
    }
};

const loadOrder = async (orderId) => {
    if (!user.value || !orderId) {
        return null;
    }

    isOrderLoading.value = true;
    orderError.value = null;

    try {
        const payload = await request('/customer/orders/' + orderId);
        selectedOrder.value = payload.data;

        return payload.data;
    } catch (error) {
        orderError.value = error.message;
        return null;
    } finally {
        isOrderLoading.value = false;
    }
};

const clearSelectedOrder = () => {
    selectedOrder.value = null;
};

const loadReturnRequests = async () => {
    if (!user.value) {
        return;
    }

    isReturnLoading.value = true;
    returnError.value = null;

    try {
        const payload = await request('/customer/return-requests');
        returnRequests.value = Array.isArray(payload.data) ? payload.data : [];
    } catch (error) {
        returnError.value = error.message;
    } finally {
        isReturnLoading.value = false;
    }
};

const requestReturn = async (details) => {
    if (!user.value) {
        return null;
    }

    isReturnLoading.value = true;
    returnError.value = null;

    try {
        const payload = await request('/customer/return-requests', {
            method: 'POST',
            body: JSON.stringify(details),
        });
        returnRequests.value = [payload.data, ...returnRequests.value.filter((returnRequest) => returnRequest.id !== payload.data.id)];

        return payload.data;
    } catch (error) {
        returnError.value = error.message;
        return null;
    } finally {
        isReturnLoading.value = false;
    }
};

const loadProductReviews = async () => {
    if (!user.value) {
        return;
    }

    isReviewLoading.value = true;
    reviewError.value = null;

    try {
        const payload = await request('/customer/product-reviews');
        productReviews.value = Array.isArray(payload.data) ? payload.data : [];
    } catch (error) {
        reviewError.value = error.message;
    } finally {
        isReviewLoading.value = false;
    }
};

const submitProductReview = async (details) => {
    if (!user.value) {
        return null;
    }

    isReviewLoading.value = true;
    reviewError.value = null;

    try {
        const payload = await request('/customer/product-reviews', {
            method: 'POST',
            body: JSON.stringify(details),
        });
        productReviews.value = [payload.data, ...productReviews.value.filter((review) => review.id !== payload.data.id)];

        return payload.data;
    } catch (error) {
        reviewError.value = error.message;
        return null;
    } finally {
        isReviewLoading.value = false;
    }
};

const loadSupportTickets = async () => {
    if (!user.value) {
        return;
    }

    isSupportLoading.value = true;
    supportError.value = null;

    try {
        const payload = await request('/customer/support-tickets');
        supportTickets.value = Array.isArray(payload.data) ? payload.data : [];
    } catch (error) {
        supportError.value = error.message;
    } finally {
        isSupportLoading.value = false;
    }
};

const submitSupportTicket = async (details) => {
    if (!user.value) {
        return null;
    }

    isSupportLoading.value = true;
    supportError.value = null;

    try {
        const payload = await request('/customer/support-tickets', {
            method: 'POST',
            body: JSON.stringify(details),
        });
        supportTickets.value = [payload.data, ...supportTickets.value.filter((ticket) => ticket.id !== payload.data.id)];

        return payload.data;
    } catch (error) {
        supportError.value = error.message;
        return null;
    } finally {
        isSupportLoading.value = false;
    }
};

const trackOrder = async (details) => {
    isOrderLoading.value = true;
    orderError.value = null;
    trackedOrder.value = null;

    try {
        const payload = await request('/api/storefront/orders/track', {
            method: 'POST',
            body: JSON.stringify(details),
        });
        trackedOrder.value = payload.data;

        return payload.data;
    } catch (error) {
        orderError.value = error.message;
        return null;
    } finally {
        isOrderLoading.value = false;
    }
};

export function useAuth() {
    onMounted(loadUser);

    return {
        addressError,
        addresses: computed(() => addresses.value),
        authError,
        clearSelectedOrder,
        closeAuth,
        isAuthLoading,
        isAddressLoading,
        isAuthOpen,
        isLoggedIn: computed(() => Boolean(user.value)),
        isOrderLoading,
        isReturnLoading,
        isReviewLoading,
        isSupportLoading,
        loadUser,
        loadAddresses,
        loadOrders,
        loadOrder,
        loadReturnRequests,
        loadProductReviews,
        loadSupportTickets,
        login,
        logout,
        openAuth,
        orderError,
        orders: computed(() => orders.value),
        register,
        requestReturn,
        submitProductReview,
        submitSupportTicket,
        returnError,
        returnRequests: computed(() => returnRequests.value),
        reviewError,
        productReviews: computed(() => productReviews.value),
        supportError,
        supportTickets: computed(() => supportTickets.value),
        selectedOrder: computed(() => selectedOrder.value),
        deleteAddress,
        saveAddress,
        trackedOrder: computed(() => trackedOrder.value),
        trackOrder,
        updatePassword,
        updateProfile,
        user: computed(() => user.value),
    };
}
