<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import AddressBook from '../auth/AddressBook.vue';
import CustomerNotificationsPanel from './CustomerNotificationsPanel.vue';
import CustomerOrdersPanel from './CustomerOrdersPanel.vue';
import CustomerReviewsPanel from './CustomerReviewsPanel.vue';
import CustomerReturnsPanel from './CustomerReturnsPanel.vue';
import CustomerWishlistPanel from './CustomerWishlistPanel.vue';
import CustomerSupportPanel from './CustomerSupportPanel.vue';
import CustomerAccountSettingsPanel from './CustomerAccountSettingsPanel.vue';
import CustomerTrackingPanel from './CustomerTrackingPanel.vue';
import { customerLoginUrl, useAuth } from '../../composables/useAuth';
import { useCart } from '../../composables/useCart';
import { useWishlist } from '../../composables/useWishlist';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons, locale, localeToggleLabel, themeIcon, themeLabel, toggleLocale, toggleTheme } = useStorefront();
const { addProduct, isAdding, itemCount, openCart } = useCart();
const { isLoadingWishlist, isToggling, loadWishlist, toggleWishlist, wishlistError, wishlistItems } = useWishlist();
const {
    authError,
    isAuthLoading,
    isOrderLoading,
    isReturnLoading,
    isReviewLoading,
    isSupportLoading,
    clearSelectedOrder,
    loadOrder,
    loadOrders,
    loadReturnRequests,
    loadProductReviews,
    loadSupportTickets,
    loadUser,
    logout,
    orderError,
    orders,
    selectedOrder,
    returnError,
    returnRequests,
    reviewError,
    productReviews,
    supportError,
    supportTickets,
    trackedOrder,
    trackOrder,
    requestReturn,
    submitProductReview,
    submitSupportTicket,
    updatePassword,
    updateProfile,
    user,
} = useAuth();

const validPanels = new Set(['overview', 'notifications', 'orders', 'addresses', 'profile', 'security', 'returns', 'reviews', 'wishlist', 'support', 'track']);
const requestedPanel = new URLSearchParams(window.location.search).get('panel');
const activePanel = ref(validPanels.has(requestedPanel) ? requestedPanel : 'overview');
const isSessionReady = ref(false);
const notice = ref('');
const profileForm = reactive({ name: '', email: '', phone: '' });
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const tracking = reactive({ order_number: '', phone: '' });
const returnForm = reactive({ order_id: null, order_item_id: null, product_name: '', reason: '' });
const reviewForm = reactive({ order_id: null, order_item_id: null, product_name: '', rating: 5, title: '', body: '' });
const supportForm = reactive({ order_id: '', topic: 'order_help', priority: 'normal', subject: '', message: '' });
const returnNotice = ref('');
const reviewNotice = ref('');
const supportNotice = ref('');

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'Customer Dashboard',
    title: 'আপনার শপিং কন্ট্রোল সেন্টার',
    subtitle: 'অর্ডার, ঠিকানা, প্রোফাইল ও সাপোর্ট এক জায়গায় ম্যানেজ করুন।',
    continueShopping: 'কেনাকাটা চালিয়ে যান',
    checkingAccount: 'অ্যাকাউন্ট যাচাই হচ্ছে...',
    name: 'পূর্ণ নাম', email: 'ইমেইল', phone: 'মোবাইল নম্বর',
    overview: 'ওভারভিউ', orders: 'অর্ডার', addresses: 'ঠিকানা', profile: 'প্রোফাইল', security: 'সিকিউরিটি', returns: 'রিটার্ন', reviews: 'রিভিউ', wishlist: 'উইশলিস্ট', support: 'সাপোর্ট', track: 'ট্র্যাকিং', logout: 'লগআউট',
    totalOrders: 'মোট অর্ডার', activeOrders: 'চলমান অর্ডার', lastOrder: 'শেষ অর্ডার', noOrders: 'এখনও কোনো অর্ডার নেই।', recentOrders: 'সাম্প্রতিক অর্ডার', refresh: 'রিফ্রেশ', placedOn: 'অর্ডারের তারিখ', payment: 'পেমেন্ট', transaction: 'Transaction ID', senderPhone: 'Sender Phone', manualPaymentStatuses: { submitted_for_review: 'Payment proof জমা হয়েছে - admin review pending', verified: 'Payment verified', rejected: 'Payment proof rejected - support এ যোগাযোগ করুন' }, deliveryTo: 'ডেলিভারি', products: 'পণ্য', items: 'আইটেম', total: 'মোট', viewDetails: 'Details দেখুন', orderDetails: 'Order Details', invoiceSummary: 'Invoice Summary', customer: 'Customer', closeDetails: 'Close Details', subtotal: 'সাবটোটাল', deliveryCharge: 'Delivery Charge', discount: 'Discount', grandTotal: 'Grand Total', wishlistPanel: 'Saved Products', noWishlist: 'Wishlist empty. Product save করলে এখানে দেখা যাবে।', addToCart: 'Cart এ যোগ করুন', remove: 'Remove', viewProduct: 'View Product', variant: 'Variant', stock: 'Stock', requestReturn: 'Return Request', returnReason: 'Return reason', submitReturn: 'Submit Return', cancelReturn: 'Cancel', returnRequested: 'Return request submitted.', returnsTitle: 'Return Requests', noReturns: 'No return request yet.', returnPickup: 'Return pickup', pickupPending: 'Pickup scheduling pending', pickupTracking: 'Tracking', trackPickup: 'Track pickup', returnPickup: 'Return pickup', pickupPending: 'Pickup scheduling pending', pickupTracking: 'Tracking', trackPickup: 'Track pickup', notEligibleReturn: 'Return not available', refundableUntil: 'Return window', writeReview: 'Review দিন', reviewTitle: 'Review title', reviewBody: 'Review details', submitReview: 'Submit Review', cancelReview: 'Cancel', reviewSubmitted: 'Review submitted for moderation.', reviewsTitle: 'Product Reviews', noReviews: 'No review yet.', alreadyReviewed: 'Reviewed', pendingApproval: 'Pending approval', supportTitle: 'Customer Support', supportSubtitle: 'Order, delivery, return, payment অথবা account issue পাঠান।', supportTopic: 'Topic', supportPriority: 'Priority', supportSubject: 'Subject', supportMessage: 'Message', supportOrder: 'Related order', submitSupport: 'Submit Ticket', supportSubmitted: 'Support ticket submitted.', noTickets: 'No support ticket yet.', optionalOrder: 'No order selected', normal: 'Normal', high: 'High', notifications: 'নোটিফিকেশন', notificationTitle: 'Latest Updates', noNotifications: 'No customer updates yet.', actionNeeded: 'Action needed', informational: 'Update',
    saveProfile: 'প্রোফাইল সেভ করুন', profileSaved: 'প্রোফাইল আপডেট হয়েছে।', currentPassword: 'বর্তমান পাসওয়ার্ড', newPassword: 'নতুন পাসওয়ার্ড', confirmNewPassword: 'নতুন পাসওয়ার্ড নিশ্চিত করুন', savePassword: 'পাসওয়ার্ড আপডেট করুন', passwordSaved: 'পাসওয়ার্ড আপডেট হয়েছে।',
    trackTitle: 'অর্ডার ট্র্যাক করুন', orderNumber: 'অর্ডার নম্বর', submitTrack: 'ট্র্যাক করুন', emptyHint: 'চেকআউট করলে অর্ডার এখানে দেখা যাবে।', statusSteps: ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
} : {
    eyebrow: 'Customer Dashboard',
    title: 'Your shopping control center',
    subtitle: 'Manage orders, addresses, profile, and support from one premium workspace.',
    continueShopping: 'Continue shopping',
    checkingAccount: 'Checking your account...',
    name: 'Full name', email: 'Email', phone: 'Mobile number',
    overview: 'Overview', orders: 'Orders', addresses: 'Addresses', profile: 'Profile', security: 'Security', returns: 'Returns', reviews: 'Reviews', wishlist: 'Wishlist', support: 'Support', track: 'Tracking', logout: 'Logout',
    totalOrders: 'Total Orders', activeOrders: 'Active Orders', lastOrder: 'Last Order', noOrders: 'No orders yet.', recentOrders: 'Recent Orders', refresh: 'Refresh', placedOn: 'Placed on', payment: 'Payment', transaction: 'Transaction ID', senderPhone: 'Sender Phone', manualPaymentStatuses: { submitted_for_review: 'Payment proof submitted - waiting for admin review', verified: 'Payment verified', rejected: 'Payment proof rejected - contact support' }, deliveryTo: 'Delivery', products: 'Products', items: 'items', total: 'Total', viewDetails: 'View Details', orderDetails: 'Order Details', invoiceSummary: 'Invoice Summary', customer: 'Customer', closeDetails: 'Close Details', subtotal: 'Subtotal', deliveryCharge: 'Delivery Charge', discount: 'Discount', grandTotal: 'Grand Total', wishlistPanel: 'Saved Products', noWishlist: 'Your wishlist is empty. Saved products will appear here.', addToCart: 'Add to Cart', remove: 'Remove', viewProduct: 'View Product', variant: 'Variant', stock: 'Stock', requestReturn: 'Request Return', returnReason: 'Return reason', submitReturn: 'Submit Return', cancelReturn: 'Cancel', returnRequested: 'Return request submitted.', returnsTitle: 'Return Requests', noReturns: 'No return request yet.', notEligibleReturn: 'Return not available', refundableUntil: 'Return window', writeReview: 'Write Review', reviewTitle: 'Review title', reviewBody: 'Review details', submitReview: 'Submit Review', cancelReview: 'Cancel', reviewSubmitted: 'Review submitted for moderation.', reviewsTitle: 'Product Reviews', noReviews: 'No review yet.', alreadyReviewed: 'Reviewed', pendingApproval: 'Pending approval', supportTitle: 'Customer Support', supportSubtitle: 'Send order, delivery, return, payment, or account questions to support.', supportTopic: 'Topic', supportPriority: 'Priority', supportSubject: 'Subject', supportMessage: 'Message', supportOrder: 'Related order', submitSupport: 'Submit Ticket', supportSubmitted: 'Support ticket submitted.', noTickets: 'No support ticket yet.', optionalOrder: 'No order selected', normal: 'Normal', high: 'High', notifications: 'Notifications', notificationTitle: 'Latest Updates', noNotifications: 'No customer updates yet.', actionNeeded: 'Action needed', informational: 'Update',
    saveProfile: 'Save Profile', profileSaved: 'Profile updated.', currentPassword: 'Current password', newPassword: 'New password', confirmNewPassword: 'Confirm new password', savePassword: 'Update Password', passwordSaved: 'Password updated.',
    trackTitle: 'Track Order', orderNumber: 'Order number', submitTrack: 'Track Order', emptyHint: 'Placed checkout orders will appear here.', statusSteps: ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
});

const navItems = computed(() => [
    { id: 'overview', label: text.value.overview, icon: icons.chart },
    { id: 'notifications', label: text.value.notifications, icon: icons.clock },
    { id: 'orders', label: text.value.orders, icon: icons.bag },
    { id: 'addresses', label: text.value.addresses, icon: icons.truck },
    { id: 'profile', label: text.value.profile, icon: icons.user },
    { id: 'security', label: text.value.security, icon: icons.shield },
    { id: 'returns', label: text.value.returns, icon: icons.returns },
    { id: 'reviews', label: text.value.reviews, icon: icons.star },
    { id: 'wishlist', label: text.value.wishlist, icon: icons.heart },
    { id: 'support', label: text.value.support, icon: icons.shield },
    { id: 'track', label: text.value.track, icon: icons.search },
]);

const activeOrders = computed(() => orders.value.filter((order) => !['delivered', 'cancelled', 'returned'].includes(String(order.status).toLowerCase())));
const latestOrder = computed(() => orders.value[0] ?? null);
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value)) : '';
const normalizedStatus = (order) => String(order?.status ?? '').toLowerCase();
const statusStepIndex = (order) => {
    const flow = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
    const index = flow.indexOf(normalizedStatus(order));

    return index === -1 ? 0 : index;
};
const isTerminalProblemStatus = (order) => ['cancelled', 'returned'].includes(normalizedStatus(order));
const statusBadgeClasses = (order) => isTerminalProblemStatus(order)
    ? 'bg-wine/10 text-wine'
    : 'bg-wine/12 text-wine';
const orderAddress = (order) => [order.shipping_address?.address_line, order.shipping_address?.area ?? order.shipping_area, order.shipping_address?.district ?? order.shipping_district].filter(Boolean).join(', ');
const customerNotifications = computed(() => [
    ...activeOrders.value.map((order) => ({
        id: 'order-' + order.id,
        tone: 'info',
        icon: icons.truck,
        title: order.order_number,
        message: text.value.statusSteps[statusStepIndex(order)] + ' · ' + formatPrice(order.grand_total),
        time: formatDate(order.created_at),
        createdAt: order.created_at,
        panel: 'orders',
    })),
    ...returnRequests.value.filter((returnRequest) => returnRequest.status !== 'refunded').map((returnRequest) => ({
        id: 'return-' + returnRequest.id,
        tone: returnRequest.status === 'requested' ? 'action' : 'info',
        icon: icons.returns,
        title: returnRequest.return_number,
        message: (returnRequest.order_item?.product_name || text.value.returnsTitle) + ' · ' + returnRequest.status,
        time: formatDate(returnRequest.created_at),
        createdAt: returnRequest.created_at,
        panel: 'returns',
    })),
    ...productReviews.value.filter((review) => review.status === 'pending').map((review) => ({
        id: 'review-' + review.id,
        tone: 'action',
        icon: icons.star,
        title: review.product?.name || review.order_item?.product_name || text.value.reviewsTitle,
        message: text.value.pendingApproval,
        time: formatDate(review.created_at),
        createdAt: review.created_at,
        panel: 'reviews',
    })),
    ...supportTickets.value.filter((ticket) => ticket.status !== 'resolved').map((ticket) => ({
        id: 'support-' + ticket.id,
        tone: ticket.priority === 'high' ? 'action' : 'info',
        icon: icons.shield,
        title: ticket.ticket_number,
        message: ticket.subject,
        time: formatDate(ticket.created_at),
        createdAt: ticket.created_at,
        panel: 'support',
    })),
].sort((first, second) => new Date(second.createdAt || 0) - new Date(first.createdAt || 0)).slice(0, 12));

const syncProfileForm = () => {
    profileForm.name = user.value?.name ?? '';
    profileForm.email = user.value?.email ?? '';
    profileForm.phone = user.value?.phone ?? '';
};

watch(user, async (currentUser) => {
    syncProfileForm();

    if (currentUser) {
        await loadOrders();
        await loadReturnRequests();
        await loadProductReviews();
        await loadWishlist();
        await loadSupportTickets();
    }
}, { immediate: true });

onMounted(async () => {
    await loadUser();
    isSessionReady.value = true;

    if (!user.value) {
        window.location.replace(customerLoginUrl(window.location.pathname + window.location.search));
        return;
    }

    await loadOrders();
    await loadReturnRequests();
    await loadProductReviews();
    await loadWishlist();
    await loadSupportTickets();
});

const signOut = async () => {
    await logout();
    window.location.href = '/';
};

const submitProfile = async () => {
    notice.value = '';
    const updatedUser = await updateProfile({ ...profileForm });

    if (updatedUser) {
        notice.value = text.value.profileSaved;
    }
};

const submitPassword = async () => {
    notice.value = '';
    const updated = await updatePassword({ ...passwordForm });

    if (updated) {
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        notice.value = text.value.passwordSaved;
    }
};

const showOrderDetails = async (order) => {
    activePanel.value = 'orders';
    await loadOrder(order.id);
};

const closeOrderDetails = () => {
    clearSelectedOrder();
};

const returnRequestForItem = (item) => returnRequests.value.find((returnRequest) => returnRequest.order_item?.id === item.id);

const canRequestReturn = (order, item) => normalizedStatus(order) === 'delivered' && item.is_refundable && !returnRequestForItem(item);

const openReturnForm = (order, item) => {
    returnNotice.value = '';
    returnForm.order_id = order.id;
    returnForm.order_item_id = item.id;
    returnForm.product_name = item.product_name;
    returnForm.reason = '';
};

const closeReturnForm = () => {
    returnForm.order_id = null;
    returnForm.order_item_id = null;
    returnForm.product_name = '';
    returnForm.reason = '';
};

const submitReturnRequest = async () => {
    returnNotice.value = '';
    const created = await requestReturn({
        order_id: returnForm.order_id,
        order_item_id: returnForm.order_item_id,
        reason: returnForm.reason,
    });

    if (created) {
        returnNotice.value = text.value.returnRequested;
        closeReturnForm();
        await loadReturnRequests();
        await loadProductReviews();
        await loadWishlist();
        await loadSupportTickets();
    }
};

const reviewForItem = (item) => productReviews.value.find((review) => review.order_item?.id === item.id);

const canReviewItem = (order, item) => normalizedStatus(order) === 'delivered' && !reviewForItem(item);

const openReviewForm = (order, item) => {
    reviewNotice.value = '';
    reviewForm.order_id = order.id;
    reviewForm.order_item_id = item.id;
    reviewForm.product_name = item.product_name;
    reviewForm.rating = 5;
    reviewForm.title = '';
    reviewForm.body = '';
};

const closeReviewForm = () => {
    reviewForm.order_id = null;
    reviewForm.order_item_id = null;
    reviewForm.product_name = '';
    reviewForm.rating = 5;
    reviewForm.title = '';
    reviewForm.body = '';
};

const submitReview = async () => {
    reviewNotice.value = '';
    const created = await submitProductReview({
        order_id: reviewForm.order_id,
        order_item_id: reviewForm.order_item_id,
        rating: reviewForm.rating,
        title: reviewForm.title,
        body: reviewForm.body,
    });

    if (created) {
        reviewNotice.value = text.value.reviewSubmitted;
        closeReviewForm();
        await loadProductReviews();
        await loadWishlist();
        await loadSupportTickets();
    }
};

const wishlistProductUrl = (item) => item.product?.slug ? '/products/' + item.product.slug : '/products';

const addWishlistItemToCart = async (item) => {
    if (!item.product) {
        return;
    }

    await addProduct({
        ...item.product,
        product_variant_id: item.variant?.id ?? item.product_variant_id ?? null,
    }, 1);
};

const supportTopics = computed(() => [
    { value: 'order_help', label: 'Order Help' },
    { value: 'delivery', label: 'Delivery' },
    { value: 'return_refund', label: 'Return / Refund' },
    { value: 'payment', label: 'Payment' },
    { value: 'product', label: 'Product' },
    { value: 'account', label: 'Account' },
]);

const resetSupportForm = () => {
    supportForm.order_id = '';
    supportForm.topic = 'order_help';
    supportForm.priority = 'normal';
    supportForm.subject = '';
    supportForm.message = '';
};

const submitSupport = async () => {
    supportNotice.value = '';
    const created = await submitSupportTicket({
        order_id: supportForm.order_id || null,
        topic: supportForm.topic,
        priority: supportForm.priority,
        subject: supportForm.subject,
        message: supportForm.message,
    });

    if (created) {
        supportNotice.value = text.value.supportSubmitted;
        resetSupportForm();
        await loadSupportTickets();
    }
};

const submitTracking = async () => {
    await trackOrder(tracking);
};
</script>

<template>
    <div class="min-h-screen bg-white font-sans text-ink dark:bg-[#111111] dark:text-white">
        <div v-if="!isSessionReady || !user" class="grid min-h-screen place-items-center px-4">
            <p class="text-sm font-semibold text-ink/55 dark:text-white/55">{{ text.checkingAccount }}</p>
        </div>

        <template v-else>
            <header class="sticky top-0 z-40 border-b border-black/10 bg-white/95 backdrop-blur-xl dark:border-white/10 dark:bg-[#111111]/95">
                <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <a class="flex min-w-0 items-center gap-3" href="/customer/dashboard">
                        <span class="grid size-10 place-items-center rounded-2xl bg-ink text-lg font-semibold text-white dark:bg-white dark:text-ink">A</span>
                        <span class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ copy.brand }}</p>
                            <p class="text-[11px] font-semibold text-wine">{{ text.eyebrow }}</p>
                        </span>
                    </a>
                    <div class="flex items-center gap-2">
                        <button class="hidden size-10 place-items-center rounded-full border border-black/10 text-xs font-semibold dark:border-white/10 sm:grid" type="button" :aria-label="localeToggleLabel" @click="toggleLocale">{{ localeToggleLabel }}</button>
                        <button class="hidden size-10 place-items-center rounded-full border border-black/10 dark:border-white/10 sm:grid" type="button" :aria-label="themeLabel" @click="toggleTheme"><FontAwesomeIcon :icon="themeIcon" /></button>
                        <button class="relative grid size-10 place-items-center rounded-full border border-black/10 dark:border-white/10" type="button" :aria-label="copy.cart" @click="openCart">
                            <FontAwesomeIcon :icon="icons.cart" />
                            <span class="absolute -right-0.5 -top-0.5 grid min-w-5 place-items-center rounded-full bg-wine px-1 text-[10px] font-semibold leading-4 text-white">{{ itemCount }}</span>
                        </button>
                        <a class="btn-ghost hidden md:inline-flex" href="/">{{ text.continueShopping }}</a>
                        <button class="btn-ink" type="button" :disabled="isAuthLoading" @click="signOut">{{ text.logout }}</button>
                    </div>
                </div>
            </header>

            <section class="px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-[1440px]">
                    <div class="mb-5">
                        <h1 class="text-2xl font-semibold leading-snug sm:text-3xl">{{ text.title }}</h1>
                        <p class="mt-1 max-w-2xl text-sm font-semibold leading-6 text-ink/55 dark:text-white/55">{{ text.subtitle }}</p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
                        <aside class="rounded-[1.4rem] bg-[#141414] p-3 text-white lg:sticky lg:top-20 lg:self-start">
                            <div class="mb-3 flex items-center gap-3 rounded-2xl bg-wine/20 p-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-wine text-sm font-semibold">{{ user.name?.charAt(0) }}</span>
                                <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ user.name }}</p><p class="truncate text-xs font-semibold text-white/55">{{ user.email }}</p></div>
                            </div>
                            <nav class="flex gap-2 overflow-x-auto pb-1 lg:grid lg:overflow-visible lg:pb-0">
                                <button v-for="item in navItems" :key="item.id" class="flex h-10 shrink-0 items-center gap-3 rounded-full px-3.5 text-[13px] font-semibold transition lg:w-full" :class="activePanel === item.id ? 'bg-wine text-white' : 'text-white/65 hover:bg-white/8 hover:text-white'" type="button" @click="activePanel = item.id"><FontAwesomeIcon :icon="item.icon" />{{ item.label }}</button>
                            </nav>
                            <a class="btn-ghost mt-3 w-full border-white/15 bg-white/8 text-white hover:bg-wine md:hidden" href="/">{{ text.continueShopping }}</a>
                        </aside>

                        <div class="min-w-0 space-y-5">
                    <section v-if="activePanel === 'overview'" class="space-y-5">
                        <div class="grid gap-3 sm:grid-cols-4">
                            <div class="rounded-[1.2rem] bg-[#141414] p-4 text-white"><p class="text-[11px] font-semibold text-wine">{{ text.totalOrders }}</p><p class="mt-2 text-2xl font-semibold">{{ orders.length }}</p></div>
                            <div class="rounded-[1.2rem] bg-[#141414] p-4 text-white"><p class="text-[11px] font-semibold text-wine">{{ text.activeOrders }}</p><p class="mt-2 text-2xl font-semibold">{{ activeOrders.length }}</p></div>
                            <div class="rounded-[1.2rem] bg-[#141414] p-4 text-white"><p class="text-[11px] font-semibold text-wine">{{ text.lastOrder }}</p><p class="mt-2 truncate text-base font-semibold">{{ latestOrder?.order_number ?? '-' }}</p></div>
                            <button class="rounded-[1.2rem] bg-[#141414] p-4 text-left text-white transition hover:-translate-y-0.5" type="button" @click="activePanel = 'notifications'"><p class="text-[11px] font-semibold text-wine">{{ text.notifications }}</p><p class="mt-2 text-2xl font-semibold">{{ customerNotifications.length }}</p></button>
                        </div>
                    </section>

                    <CustomerNotificationsPanel
                        v-if="activePanel === 'notifications'"
                        :notifications="customerNotifications"
                        :text="text"
                        @navigate="activePanel = $event"
                    />

                    <CustomerOrdersPanel
                        v-if="activePanel === 'overview' || activePanel === 'orders'"
                        :orders="orders"
                        :selected-order="selectedOrder"
                        :text="text"
                        :icons="icons"
                        :is-order-loading="isOrderLoading"
                        :is-review-loading="isReviewLoading"
                        :is-return-loading="isReturnLoading"
                        :order-error="orderError"
                        :review-error="reviewError"
                        :review-notice="reviewNotice"
                        :return-error="returnError"
                        :return-notice="returnNotice"
                        :review-form="reviewForm"
                        :return-form="returnForm"
                        :format-price="formatPrice"
                        :format-date="formatDate"
                        :status-badge-classes="statusBadgeClasses"
                        :status-step-index="statusStepIndex"
                        :is-terminal-problem-status="isTerminalProblemStatus"
                        :order-address="orderAddress"
                        :can-review-item="canReviewItem"
                        :review-for-item="reviewForItem"
                        :can-request-return="canRequestReturn"
                        :return-request-for-item="returnRequestForItem"
                        @refresh="loadOrders"
                        @show-details="showOrderDetails"
                        @close-details="closeOrderDetails"
                        @open-review="({ order, item }) => openReviewForm(order, item)"
                        @close-review="closeReviewForm"
                        @submit-review="submitReview"
                        @set-review-rating="reviewForm.rating = $event"
                        @open-return="({ order, item }) => openReturnForm(order, item)"
                        @close-return="closeReturnForm"
                        @submit-return="submitReturnRequest"
                    />

                    <AddressBook v-if="activePanel === 'addresses'" />

                    <CustomerAccountSettingsPanel
                        v-if="activePanel === 'profile' || activePanel === 'security'"
                        :mode="activePanel"
                        :profile-form="profileForm"
                        :password-form="passwordForm"
                        :text="text"
                        :icons="icons"
                        :notice="notice"
                        :is-auth-loading="isAuthLoading"
                        @submit-profile="submitProfile"
                        @submit-password="submitPassword"
                    />

                    <CustomerReviewsPanel
                        v-if="activePanel === 'reviews'"
                        :reviews="productReviews"
                        :text="text"
                        :icons="icons"
                        :is-loading="isReviewLoading"
                        :error="reviewError"
                        @refresh="loadProductReviews"
                    />

                    <CustomerReturnsPanel
                        v-if="activePanel === 'returns'"
                        :returns="returnRequests"
                        :text="text"
                        :is-loading="isReturnLoading"
                        :error="returnError"
                        :format-price="formatPrice"
                        :format-date="formatDate"
                        :icons="icons"
                        @refresh="loadReturnRequests"
                    />

                    <CustomerWishlistPanel
                        v-if="activePanel === 'wishlist'"
                        :wishlist-items="wishlistItems"
                        :text="text"
                        :icons="icons"
                        :is-loading-wishlist="isLoadingWishlist"
                        :wishlist-error="wishlistError"
                        :is-adding="isAdding"
                        :is-toggling="isToggling"
                        :format-price="formatPrice"
                        :wishlist-product-url="wishlistProductUrl"
                        @refresh="loadWishlist"
                        @toggle="(product) => toggleWishlist(product, 'customer_dashboard')"
                        @add-to-cart="addWishlistItemToCart"
                    />

                    <CustomerSupportPanel
                        v-if="activePanel === 'support'"
                        :orders="orders"
                        :support-tickets="supportTickets"
                        :support-topics="supportTopics"
                        :support-form="supportForm"
                        :text="text"
                        :icons="icons"
                        :is-support-loading="isSupportLoading"
                        :support-error="supportError"
                        :support-notice="supportNotice"
                        @submit="submitSupport"
                        @refresh="loadSupportTickets"
                    />

                    <CustomerTrackingPanel
                        v-if="activePanel === 'track'"
                        :tracking="tracking"
                        :tracked-order="trackedOrder"
                        :text="text"
                        :is-order-loading="isOrderLoading"
                        :order-error="orderError"
                        :format-price="formatPrice"
                        :status-badge-classes="statusBadgeClasses"
                        @submit="submitTracking"
                    />
                </div>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

