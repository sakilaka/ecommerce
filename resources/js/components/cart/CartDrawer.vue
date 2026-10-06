<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCart } from '../../composables/useCart';
import { useAuth } from '../../composables/useAuth';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons, locale } = useStorefront();
const {
    applyCoupon,
    checkoutError,
    clearCoupon,
    couponPreview,
    closeCart,
    deliveryZones,
    error,
    isApplyingCoupon,
    isCartOpen,
    isCheckingOut,
    isLoading,
    isMutating,
    isSubmittingPaymentProof,
    items,
    loadDeliveryZones,
    loadPaymentMethods,
    order,
    paymentMethods: availablePaymentMethods,
    placeOrder,
    removeItem,
    resetOrder,
    submitManualPaymentProof,
    subtotal,
    updateItem,
} = useCart();
const { addresses, loadAddresses, loadOrders, user } = useAuth();

const step = ref('cart');
const checkout = reactive({
    name: '',
    phone: '',
    email: '',
    district: 'Dhaka',
    area: 'Inside Dhaka',
    postalCode: '',
    addressLine: '',
    couponCode: '',
    customerNote: '',
    customerAddressId: null,
    paymentMethod: 'cod',
});
const paymentProof = reactive({ senderPhone: '', transactionId: '', note: '' });
const paymentProofSubmitted = ref(false);

const text = computed(() => locale.value === 'bn' ? {
    title: 'আপনার কার্ট',
    checkoutTitle: 'চেকআউট',
    successTitle: 'অর্ডার কনফার্ম হয়েছে',
    statusLabel: 'স্ট্যাটাস',
    paymentLabel: 'পেমেন্ট',
    empty: 'কার্ট এখন খালি',
    emptyHint: 'পণ্য যোগ করলে এখানে দেখা যাবে।',
    subtotal: 'সাবটোটাল',
    delivery: 'ডেলিভারি',
    discount: 'ডিসকাউন্ট',
    total: 'মোট',
    checkout: 'চেকআউট শুরু করুন',
    placeOrder: 'অর্ডার করুন',
    continue: 'শপিং চালিয়ে যান',
    back: 'কার্টে ফিরুন',
    qty: 'পরিমাণ',
    name: 'পূর্ণ নাম',
    phone: 'মোবাইল নম্বর',
    email: 'ইমেইল',
    district: 'জেলা',
    area: 'এরিয়া',
    address: 'পূর্ণ ঠিকানা',
    coupon: 'কুপন কোড',
    applyCouponText: 'কুপন প্রয়োগ করুন',
    couponApplied: 'কুপন প্রয়োগ হয়েছে',
    removeCoupon: 'কুপন সরান',
    note: 'ডেলিভারি নোট',
    payment: 'পেমেন্ট মেথড',
    paymentAvailable: 'এখন available',
    paymentComingSoon: 'শীঘ্রই আসছে',
    paymentHint: 'Available method বেছে নিন। bKash/Nagad হলে order করার পর payment instruction দেখাবে।',
    manualPaymentTitle: 'Mobile payment instruction',
    manualPaymentHint: 'Send Money করার পর transaction ID সংরক্ষণ করুন। Admin verify করলে payment paid হবে।',
    merchantNumber: 'Merchant number',
    paymentReference: 'Reference',
    payableAmount: 'Payable amount',
    senderPhone: 'Sender phone',
    transactionId: 'Transaction ID',
    proofNote: 'Note',
    submitProof: 'Submit payment proof',
    submittingProof: 'Submitting...',
    proofReceived: 'Payment proof submitted. Admin will verify shortly.',
    senderPhone: 'Sender phone',
    transactionId: 'Transaction ID',
    proofNote: 'Note',
    submitProof: 'Submit payment proof',
    submittingProof: 'Submitting...',
    proofReceived: 'Payment proof submitted. Admin will verify shortly.',
    savedAddresses: 'সেভ করা ঠিকানা',
    manualAddress: 'নতুন ঠিকানা ব্যবহার করুন',
    selectedAddress: 'নির্বাচিত',
    defaultAddress: 'ডিফল্ট',
    successHint: 'আমরা দ্রুত আপনার অর্ডার নিশ্চিত করব।',
    orderNo: 'অর্ডার নম্বর',
    shippingTo: 'ডেলিভারি ঠিকানা',
    orderItems: 'অর্ডার আইটেম',
    trackThisOrder: 'এই অর্ডার দেখুন',
    savedToHistory: 'অর্ডারটি আপনার অ্যাকাউন্টে সেভ হয়েছে।',
    guestTrackHint: 'অর্ডার ট্র্যাক করতে অর্ডার নম্বর ও ফোন নম্বর সংরক্ষণ করুন।',
} : {
    title: 'Your Cart',
    checkoutTitle: 'Checkout',
    successTitle: 'Order placed',
    statusLabel: 'Status',
    paymentLabel: 'Payment',
    empty: 'Your cart is empty',
    emptyHint: 'Added products will appear here.',
    subtotal: 'Subtotal',
    delivery: 'Delivery',
    discount: 'Discount',
    total: 'Total',
    checkout: 'Start Checkout',
    placeOrder: 'Place Order',
    continue: 'Continue Shopping',
    back: 'Back to Cart',
    qty: 'Qty',
    name: 'Full name',
    phone: 'Mobile number',
    email: 'Email',
    district: 'District',
    area: 'Area',
    address: 'Full address',
    coupon: 'Coupon code',
    applyCouponText: 'Apply Coupon',
    couponApplied: 'Coupon applied',
    removeCoupon: 'Remove Coupon',
    note: 'Delivery note',
    payment: 'Payment Method',
    paymentAvailable: 'Available now',
    paymentComingSoon: 'Coming soon',
    paymentHint: 'Choose an available method. bKash/Nagad will show payment instructions after order placement.',
    manualPaymentTitle: 'Mobile payment instruction',
    manualPaymentHint: 'After Send Money, keep the transaction ID. Admin will verify it before marking payment paid.',
    merchantNumber: 'Merchant number',
    paymentReference: 'Reference',
    payableAmount: 'Payable amount',
    savedAddresses: 'Saved Addresses',
    manualAddress: 'Use New Address',
    selectedAddress: 'Selected',
    defaultAddress: 'Default',
    successHint: 'We will confirm your order shortly.',
    orderNo: 'Order number',
    shippingTo: 'Shipping To',
    orderItems: 'Order Items',
    trackThisOrder: 'View This Order',
    savedToHistory: 'This order has been saved to your account.',
    guestTrackHint: 'Save this order number and phone number for tracking.',
});

const districts = computed(() => [...new Set(deliveryZones.value.map((zone) => zone.district))]);
const areas = computed(() => deliveryZones.value.filter((zone) => zone.district === checkout.district));
const selectedZone = computed(() => areas.value.find((zone) => zone.area === checkout.area) ?? areas.value[0] ?? null);
const deliveryTotal = computed(() => (selectedZone.value?.delivery_charge ?? 0) + (selectedZone.value?.cod_charge ?? 0));
const discountTotal = computed(() => couponPreview.value?.discount_total ?? 0);
const grandTotal = computed(() => Math.max(0, subtotal.value + deliveryTotal.value - discountTotal.value));
const hasSavedAddresses = computed(() => user.value && addresses.value.length > 0);
const paymentIcon = (value) => ({
    cod: icons.truck,
    sslcommerz: icons.shield,
    bkash: icons.cart,
    nagad: icons.tags,
    card: icons.gem,
}[value] ?? icons.gem);

const paymentMethods = computed(() => availablePaymentMethods.value.map((method) => ({
    ...method,
    active: method.enabled,
    name: locale.value === 'bn' ? method.short_label_bn : method.short_label,
    label: locale.value === 'bn' ? method.label_bn : method.label,
    description: locale.value === 'bn' ? method.description_bn : method.description,
    icon: paymentIcon(method.value),
})));
const selectedAddress = computed(() => addresses.value.find((address) => address.id === checkout.customerAddressId) ?? null);
const selectedPaymentMethod = computed(() => paymentMethods.value.find((method) => method.value === checkout.paymentMethod) ?? null);
const successPayment = computed(() => order.value?.payments?.[0] ?? null);
const manualPaymentInstruction = computed(() => {
    const payload = successPayment.value?.gateway_payload ?? null;

    return payload?.next_action === 'manual_payment_instructions' ? payload : null;
});
const manualSubmission = computed(() => manualPaymentInstruction.value?.manual_submission ?? null);

const formatPrice = (amount) => `৳${Math.round(Number(amount || 0) / 100).toLocaleString('en-BD')}`;

const applyAddress = (address) => {
    checkout.customerAddressId = address?.id ?? null;

    if (!address) {
        return;
    }

    checkout.name = address.name;
    checkout.phone = address.phone;
    checkout.district = address.district;
    checkout.area = address.area ?? '';
    checkout.postalCode = address.postal_code ?? '';
    checkout.addressLine = address.address_line;
};

const hydrateCheckoutFromCustomer = () => {
    if (!user.value) {
        return;
    }

    checkout.name = checkout.name || user.value.name || '';
    checkout.phone = checkout.phone || user.value.phone || '';
    checkout.email = checkout.email || user.value.email || '';
};

const beginCheckout = async () => {
    await Promise.all([loadDeliveryZones(), loadPaymentMethods()]);

    if (user.value) {
        await loadAddresses();
    }

    if (!checkout.district && districts.value.length) {
        checkout.district = districts.value[0];
    }

    if (!checkout.area && areas.value.length) {
        checkout.area = areas.value[0].area ?? '';
    }

    const defaultAddress = addresses.value.find((address) => address.is_default) ?? addresses.value[0] ?? null;

    if (defaultAddress) {
        applyAddress(defaultAddress);
    } else {
        hydrateCheckoutFromCustomer();
    }

    const selectedMethod = paymentMethods.value.find((method) => method.value === checkout.paymentMethod);
    const firstActiveMethod = paymentMethods.value.find((method) => method.active);

    if (!selectedMethod?.active && firstActiveMethod) {
        checkout.paymentMethod = firstActiveMethod.value;
    }

    resetOrder();
    step.value = 'checkout';
};

const openCheckoutFromEvent = async () => {
    if (!isCartOpen.value) {
        isCartOpen.value = true;
    }

    await beginCheckout();
};

const submitCoupon = async () => {
    await applyCoupon(checkout.couponCode.trim());
};

const removeCoupon = () => {
    checkout.couponCode = '';
    clearCoupon();
};

const submitCheckout = async () => {
    const placedOrder = await placeOrder(checkout);

    if (placedOrder) {
        if (user.value) {
            await loadOrders();
        }

        step.value = 'success';
    }
};

const submitPaymentProof = async () => {
    const updatedPayment = await submitManualPaymentProof(successPayment.value, paymentProof);

    if (updatedPayment) {
        paymentProofSubmitted.value = true;
        paymentProof.senderPhone = '';
        paymentProof.transactionId = '';
        paymentProof.note = '';
    }
};

const openOrderHistory = async () => {
    if (user.value) {
        await loadOrders();
        window.location.href = '/customer/dashboard';
    }
};

watch(() => checkout.district, () => {
    if (!selectedAddress.value) {
        checkout.area = areas.value[0]?.area ?? '';
    }
});

watch(() => checkout.couponCode, (couponCode) => {
    if (couponPreview.value && couponCode.trim().toUpperCase() !== couponPreview.value.code) {
        clearCoupon();
    }
});

watch(selectedAddress, (address) => {
    if (address) {
        applyAddress(address);
    }
});

watch(isCartOpen, (open) => {
    if (!open) {
        step.value = 'cart';
    }
});

watch(user, () => {
    hydrateCheckoutFromCustomer();
});

onMounted(() => {
    window.addEventListener('aurelia:checkout-open', openCheckoutFromEvent);
});

onBeforeUnmount(() => {
    window.removeEventListener('aurelia:checkout-open', openCheckoutFromEvent);
});
</script>

<template>
    <Teleport to="body">
        <div v-if="isCartOpen" class="fixed inset-0 z-[90]">
            <button class="absolute inset-0 bg-black/45 backdrop-blur-sm" type="button" aria-label="Close cart" @click="closeCart"></button>

            <aside class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-[#ffffff] shadow-2xl shadow-black/30 ring-1 ring-black/10 transition-colors dark:bg-[#111111] dark:ring-white/10 sm:max-w-lg">
                <header class="flex items-center justify-between gap-4 border-b border-black/10 px-5 py-4 dark:border-white/10 sm:px-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.brand }}</p>
                        <h2 class="mt-1 text-2xl font-black text-[#171512] dark:text-white">{{ step === 'checkout' ? text.checkoutTitle : step === 'success' ? text.successTitle : text.title }}</h2>
                    </div>
                    <button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white/85 text-[#171512] shadow-sm transition hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white" type="button" aria-label="Close cart" @click="closeCart">
                        <FontAwesomeIcon :icon="icons.xmark" />
                    </button>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div v-if="error || checkoutError" class="mb-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">
                        {{ error || checkoutError }}
                    </div>

                    <div v-if="isLoading" class="grid min-h-56 place-items-center rounded-lg bg-white/70 text-sm font-black text-[#374151] ring-1 ring-black/5 dark:bg-white/8 dark:text-[#e5e7eb] dark:ring-white/10">
                        Loading cart...
                    </div>

                    <div v-else-if="step === 'success' && order" class="space-y-4">
                        <section class="rounded-lg bg-white/84 p-5 text-center shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <span class="mx-auto grid size-16 place-items-center rounded-lg bg-[#171512] text-2xl text-white shadow-lg shadow-black/15 dark:bg-[#9f243a] dark:text-white">
                                <FontAwesomeIcon :icon="icons.shield" />
                            </span>
                            <h3 class="mt-5 text-xl font-black text-[#171512] dark:text-white">{{ text.successTitle }}</h3>
                            <p class="mt-2 text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ user ? text.savedToHistory : text.guestTrackHint }}</p>
                        </section>

                        <section class="rounded-lg bg-white/84 p-4 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <div class="rounded-lg bg-[#171512] p-4 text-white dark:bg-[#9f243a] dark:text-white">
                                <p class="text-[11px] font-black uppercase tracking-normal opacity-75">{{ text.orderNo }}</p>
                                <p class="mt-1 break-all text-lg font-black">{{ order.order_number }}</p>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-left">
                                <div class="rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                                    <p class="text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.statusLabel }}</p>
                                    <p class="mt-1 text-sm font-black capitalize text-[#171512] dark:text-white">{{ order.status }}</p>
                                </div>
                                <div class="rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                                    <p class="text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.paymentLabel }}</p>
                                    <p class="mt-1 text-sm font-black uppercase text-[#171512] dark:text-white">{{ order.payment_method }}</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-2 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]">
                                <div class="flex items-center justify-between"><span>{{ text.subtotal }}</span><span>{{ formatPrice(order.subtotal) }}</span></div>
                                <div class="flex items-center justify-between"><span>{{ text.delivery }}</span><span>{{ formatPrice(order.shipping_total) }}</span></div>
                                <div v-if="order.discount_total > 0" class="flex items-center justify-between text-[#8f2638] dark:text-[#ff8aa0]"><span>{{ text.discount }}</span><span>-{{ formatPrice(order.discount_total) }}</span></div>
                                <div class="flex items-center justify-between border-t border-black/10 pt-3 text-lg text-[#171512] dark:border-white/10 dark:text-white"><span>{{ text.total }}</span><strong>{{ formatPrice(order.grand_total) }}</strong></div>
                            </div>
                            <div v-if="manualPaymentInstruction" class="mt-4 rounded-lg bg-[#fff5f6] p-4 text-left ring-1 ring-[#9f243a]/20 dark:bg-[#9f243a]/10 dark:ring-[#9f243a]/20">
                                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.manualPaymentTitle }}</p>
                                <div class="mt-3 grid gap-2 text-sm font-bold text-[#171512] dark:text-white">
                                    <div class="flex items-center justify-between gap-3"><span class="text-[#4b5563] dark:text-[#d1d5db]">{{ text.merchantNumber }}</span><strong>{{ manualPaymentInstruction.merchant_number }}</strong></div>
                                    <div class="flex items-center justify-between gap-3"><span class="text-[#4b5563] dark:text-[#d1d5db]">{{ text.paymentReference }}</span><strong class="break-all text-right">{{ manualPaymentInstruction.reference }}</strong></div>
                                    <div class="flex items-center justify-between gap-3"><span class="text-[#4b5563] dark:text-[#d1d5db]">{{ text.payableAmount }}</span><strong>{{ formatPrice(manualPaymentInstruction.amount) }}</strong></div>
                                </div>
                                <p class="mt-3 text-xs font-semibold leading-5 text-[#4b5563] dark:text-[#d1d5db]">{{ text.manualPaymentHint }}</p>
                                <div v-if="manualSubmission || paymentProofSubmitted" class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 ring-1 ring-emerald-500/15 dark:bg-emerald-400/10 dark:text-emerald-200">{{ text.proofReceived }}</div>
                                <form v-else class="mt-4 grid gap-3" @submit.prevent="submitPaymentProof">
                                    <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.senderPhone }}<input v-model="paymentProof.senderPhone" required pattern="01[3-9][0-9]{8}" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="tel" placeholder="01712345678"></label>
                                    <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.transactionId }}<input v-model="paymentProof.transactionId" required minlength="6" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="text"></label>
                                    <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.proofNote }}<textarea v-model="paymentProof.note" class="mt-2 min-h-20 w-full rounded-lg border border-black/10 bg-white px-3 py-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white"></textarea></label>
                                    <button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSubmittingPaymentProof">{{ isSubmittingPaymentProof ? text.submittingProof : text.submitProof }}</button>
                                </form>
                            </div>
                        </section>

                        <section class="rounded-lg bg-white/84 p-4 text-left shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <h4 class="text-sm font-black text-[#171512] dark:text-white">{{ text.shippingTo }}</h4>
                            <p class="mt-2 text-sm font-bold text-[#171512] dark:text-white">{{ order.customer_name }} · {{ order.customer_phone }}</p>
                            <p class="mt-1 text-xs font-semibold leading-5 text-[#4b5563] dark:text-[#d1d5db]">{{ order.shipping_address?.address_line }}, {{ order.shipping_address?.area }}, {{ order.shipping_address?.district }}</p>
                        </section>

                        <section class="rounded-lg bg-white/84 p-4 text-left shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <h4 class="text-sm font-black text-[#171512] dark:text-white">{{ text.orderItems }}</h4>
                            <div class="mt-3 space-y-2">
                                <article v-for="item in order.items" :key="item.id" class="flex items-center justify-between gap-3 rounded-lg bg-[#f6f6f6] p-3 text-sm ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                                    <div class="min-w-0">
                                        <p class="line-clamp-1 font-black text-[#171512] dark:text-white">{{ item.product_name }}</p>
                                        <p class="mt-1 text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.qty }} {{ item.quantity }}</p>
                                    </div>
                                    <strong class="shrink-0 text-[#8f2638] dark:text-[#ff8aa0]">{{ formatPrice(item.line_total) }}</strong>
                                </article>
                            </div>
                        </section>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <a class="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#8f2638] text-sm font-black text-white transition hover:bg-[#741c2b] dark:bg-[#9f243a] dark:text-white" :href="'/order-tracking?order=' + encodeURIComponent(order.order_number)">
                                {{ text.trackThisOrder }}
                                <FontAwesomeIcon :icon="icons.arrowRight" />
                            </a>
                            <button class="flex h-11 w-full items-center justify-center rounded-lg bg-[#171512] text-sm font-black text-white transition hover:bg-[#27272a] dark:bg-white/10 dark:text-white dark:hover:bg-white/15" type="button" @click="closeCart">
                                {{ text.continue }}
                            </button>
                        </div>
                    </div>

                    <form v-else-if="step === 'checkout'" class="space-y-4" @submit.prevent="submitCheckout">

                        <div v-if="hasSavedAddresses" class="rounded-lg bg-white/84 p-4 shadow-lg shadow-black/5 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-black text-[#171512] dark:text-white">{{ text.savedAddresses }}</p>
                                <button class="text-xs font-black text-[#8f2638] dark:text-[#ff8aa0]" type="button" @click="applyAddress(null)">{{ text.manualAddress }}</button>
                            </div>
                            <div class="flex snap-x gap-3 overflow-x-auto pb-1">
                                <button v-for="address in addresses" :key="address.id" class="min-w-64 snap-start rounded-lg border p-3 text-left transition" :class="checkout.customerAddressId === address.id ? 'border-[#8f2638] bg-[#8f2638]/8 dark:border-[#9f243a] dark:bg-[#9f243a]/10' : 'border-black/10 bg-[#f6f6f6] hover:bg-white dark:border-white/10 dark:bg-black/20 dark:hover:bg-white/10'" type="button" @click="applyAddress(address)">
                                    <div class="flex items-center justify-between gap-2">
                                        <strong class="text-sm font-black text-[#171512] dark:text-white">{{ address.label }}</strong>
                                        <span v-if="checkout.customerAddressId === address.id" class="rounded-full bg-[#8f2638] px-2 py-0.5 text-[10px] font-black uppercase text-white dark:bg-[#9f243a] dark:text-white">{{ text.selectedAddress }}</span>
                                        <span v-else-if="address.is_default" class="rounded-full bg-black/5 px-2 py-0.5 text-[10px] font-black uppercase text-[#374151] dark:bg-white/10 dark:text-[#e5e7eb]">{{ text.defaultAddress }}</span>
                                    </div>
                                    <p class="mt-2 text-xs font-bold text-[#171512] dark:text-white">{{ address.name }} · {{ address.phone }}</p>
                                    <p class="mt-1 line-clamp-2 text-xs font-semibold leading-5 text-[#4b5563] dark:text-[#d1d5db]">{{ address.address_line }}, {{ address.area }}, {{ address.district }}</p>
                                </button>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white/84 p-4 shadow-lg shadow-black/5 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <p class="mb-3 text-sm font-black text-[#171512] dark:text-white">{{ text.payment }}</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label v-for="method in paymentMethods" :key="method.value" class="relative flex min-h-16 items-center gap-3 rounded-lg border p-3 transition" :class="method.active ? 'cursor-pointer border-[#8f2638] bg-[#8f2638]/8 dark:border-[#9f243a] dark:bg-[#9f243a]/10' : 'cursor-not-allowed border-black/8 bg-[#f6f6f6]/70 opacity-60 dark:border-white/10 dark:bg-black/20'">
                                    <input v-model="checkout.paymentMethod" class="sr-only" type="radio" name="payment_method" :value="method.value" :disabled="!method.active">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-lg" :class="method.active ? 'bg-[#8f2638] text-white dark:bg-[#9f243a] dark:text-white' : 'bg-white text-[#4b5563] dark:bg-white/10 dark:text-[#d1d5db]'"><FontAwesomeIcon :icon="method.icon" /></span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-black text-[#171512] dark:text-white">{{ method.name }}</span>
                                        <span class="mt-0.5 block text-[11px] font-black uppercase tracking-normal" :class="method.active ? 'text-[#8f2638] dark:text-[#ff8aa0]' : 'text-[#4b5563] dark:text-[#d1d5db]'">{{ method.active ? text.paymentAvailable : text.paymentComingSoon }}</span>
                                        <span class="mt-1 block line-clamp-2 text-xs font-semibold leading-5 text-[#4b5563] dark:text-[#d1d5db]">{{ method.description }}</span>
                                        <span v-if="method.instruction_type === 'manual_mobile_payment' && method.merchant_number" class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-[10px] font-black text-[#8f2638] ring-1 ring-black/5 dark:bg-black/20 dark:text-[#ff8aa0] dark:ring-white/10">{{ method.merchant_number }}</span>
                                    </span>
                                </label>
                            </div>
                            <p class="mt-3 rounded-lg bg-[#f6f6f6] px-3 py-2 text-xs font-semibold leading-5 text-[#4b5563] dark:bg-black/20 dark:text-[#d1d5db]">{{ text.paymentHint }}</p>
                        </div>

                        <div class="rounded-lg bg-white/84 p-4 shadow-lg shadow-black/5 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.name }}<input v-model="checkout.name" required class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="text"></label>
                                <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.phone }}<input v-model="checkout.phone" required pattern="01[3-9][0-9]{8}" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="tel" placeholder="01712345678"></label>
                                <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db] sm:col-span-2">{{ text.email }}<input v-model="checkout.email" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="email"></label>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white/84 p-4 shadow-lg shadow-black/5 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.district }}<select v-model="checkout.district" required class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white"><option v-for="district in districts" :key="district" :value="district">{{ district }}</option></select></label>
                                <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.area }}<select v-model="checkout.area" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white"><option v-for="zone in areas" :key="zone.id" :value="zone.area || ''">{{ zone.area || zone.district }}</option></select></label>
                                <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db] sm:col-span-2">{{ text.address }}<textarea v-model="checkout.addressLine" required rows="3" class="mt-2 w-full resize-none rounded-lg border border-black/10 bg-white px-3 py-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white"></textarea></label>
                                <div class="sm:col-span-2 rounded-lg border border-black/10 bg-white p-3 dark:border-white/10 dark:bg-black/20">
                                    <div class="flex items-end gap-2">
                                        <label class="min-w-0 flex-1 text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.coupon }}<input v-model="checkout.couponCode" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-[#ffffff] px-3 text-sm font-black uppercase text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-white/10 dark:text-white" type="text" placeholder="EID10"></label>
                                        <button class="mb-0 h-11 shrink-0 rounded-lg bg-[#171512] px-4 text-xs font-black text-white transition hover:bg-[#27272a] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="isApplyingCoupon || !checkout.couponCode" @click="submitCoupon">{{ text.applyCouponText }}</button>
                                    </div>
                                    <div v-if="couponPreview" class="mt-3 flex items-center justify-between gap-3 rounded-lg bg-[#8f2638]/8 px-3 py-2 text-xs font-black text-[#8f2638] dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">
                                        <span>{{ text.couponApplied }}: {{ couponPreview.code }} · -{{ formatPrice(couponPreview.discount_total) }}</span>
                                        <button class="underline" type="button" @click="removeCoupon">{{ text.removeCoupon }}</button>
                                    </div>
                                </div>
                                <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.note }}<input v-model="checkout.customerNote" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="text"></label>
                            </div>
                        </div>
                    </form>

                    <div v-else-if="items.length === 0" class="grid min-h-72 place-items-center rounded-lg bg-white/78 p-8 text-center shadow-sm ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                        <div>
                            <span class="mx-auto grid size-14 place-items-center rounded-lg bg-[#171512] text-xl text-white shadow-lg shadow-black/15 dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.cart" /></span>
                            <h3 class="mt-5 text-lg font-black text-[#171512] dark:text-white">{{ text.empty }}</h3>
                            <p class="mt-2 text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.emptyHint }}</p>
                        </div>
                    </div>

                    <div v-else class="space-y-3">
                        <article v-for="item in items" :key="item.id" class="grid grid-cols-[4.75rem_1fr] gap-3 rounded-lg bg-white/86 p-3 shadow-lg shadow-black/5 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <div class="aspect-square overflow-hidden rounded-md bg-[#f3f4f6]"><div class="size-full bg-cover bg-center" :style="{ backgroundImage: 'url(/images/category-collage.png)', backgroundSize: '400% 200%', backgroundPosition: item.product?.image_position || '0% 0%' }"></div></div>
                            <div class="min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0"><p class="truncate text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ item.product?.category?.name || item.variant_name || 'Product' }}</p><h3 class="mt-1 line-clamp-2 text-sm font-black leading-5 text-[#171512] dark:text-white">{{ item.name }}</h3></div>
                                    <button class="grid size-8 shrink-0 place-items-center rounded-md border border-black/10 text-xs text-[#8f2638] transition hover:bg-[#8f2638] hover:text-white disabled:opacity-40 dark:border-white/10 dark:text-[#ff8aa0]" type="button" :disabled="isMutating" @click="removeItem(item)"><FontAwesomeIcon :icon="icons.trash" /></button>
                                </div>
                                <div class="mt-3 flex items-end justify-between gap-3">
                                    <div><p class="text-base font-black text-[#8f2638] dark:text-[#ff8aa0]">{{ formatPrice(item.line_total) }}</p><p class="text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ formatPrice(item.unit_price) }}</p></div>
                                    <div class="flex h-9 items-center rounded-lg border border-black/10 bg-[#ffffff] dark:border-white/10 dark:bg-black/20"><button class="grid size-9 place-items-center text-xs disabled:opacity-40" type="button" :disabled="isMutating || item.quantity <= 1" :aria-label="text.qty" @click="updateItem(item, item.quantity - 1)"><FontAwesomeIcon :icon="icons.minus" /></button><span class="grid min-w-8 place-items-center text-sm font-black">{{ item.quantity }}</span><button class="grid size-9 place-items-center text-xs disabled:opacity-40" type="button" :disabled="isMutating" :aria-label="text.qty" @click="updateItem(item, item.quantity + 1)"><FontAwesomeIcon :icon="icons.plus" /></button></div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <footer v-if="step !== 'success'" class="border-t border-black/10 bg-white/72 px-5 py-5 shadow-[0_-18px_40px_rgba(0,0,0,0.06)] backdrop-blur-xl dark:border-white/10 dark:bg-white/8 sm:px-6">
                    <div class="space-y-2 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]">
                        <div class="flex items-center justify-between"><span>{{ text.subtotal }}</span><span>{{ formatPrice(subtotal) }}</span></div>
                        <div v-if="step === 'checkout'" class="flex items-center justify-between"><span>{{ text.delivery }}</span><span>{{ formatPrice(deliveryTotal) }}</span></div>
                        <div v-if="step === 'checkout' && discountTotal > 0" class="flex items-center justify-between text-[#8f2638] dark:text-[#ff8aa0]"><span>{{ text.discount }}</span><span>-{{ formatPrice(discountTotal) }}</span></div>
                        <div class="flex items-center justify-between border-t border-black/10 pt-3 text-lg text-[#171512] dark:border-white/10 dark:text-white"><span>{{ text.total }}</span><strong>{{ formatPrice(step === 'checkout' ? grandTotal : subtotal) }}</strong></div>
                    </div>
                    <button v-if="step === 'cart'" class="mt-4 flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-xl shadow-[#8f2638]/20 transition hover:bg-[#741c2b] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#9f243a] dark:text-white dark:hover:bg-[#861e31]" type="button" :disabled="items.length === 0" @click="beginCheckout">{{ text.checkout }}<FontAwesomeIcon :icon="icons.arrowRight" /></button>
                    <button v-else class="mt-4 flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-xl shadow-[#8f2638]/20 transition hover:bg-[#741c2b] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#9f243a] dark:text-white dark:hover:bg-[#861e31]" type="button" :disabled="isCheckingOut" @click="submitCheckout">{{ text.placeOrder }}<FontAwesomeIcon :icon="icons.arrowRight" /></button>
                    <button class="mt-2 flex h-11 w-full items-center justify-center rounded-lg border border-black/10 bg-transparent text-sm font-black text-[#171512] transition hover:bg-white dark:border-white/10 dark:text-white dark:hover:bg-white/10" type="button" @click="step === 'checkout' ? step = 'cart' : closeCart()">{{ step === 'checkout' ? text.back : text.continue }}</button>
                </footer>
            </aside>
        </div>
    </Teleport>
</template>
