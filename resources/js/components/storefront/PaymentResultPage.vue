<script setup>
import { computed, onMounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy: storefrontCopy, icons, locale } = useStorefront();

const order = ref(null);
const isLoading = ref(false);
const error = ref(null);

const content = {
    en: {
        paidEyebrow: 'Payment Confirmed',
        failedEyebrow: 'Payment Not Completed',
        pendingEyebrow: 'Payment Pending',
        paidTitle: 'Your payment is confirmed.',
        failedTitle: 'Payment could not be completed.',
        pendingTitle: 'We are checking your payment.',
        paidText: 'Your order payment was verified successfully. We will move it into processing shortly.',
        failedText: 'Your order payment failed or was cancelled. You can track the order or contact customer care for help.',
        pendingText: 'We have received the payment response and are waiting for final confirmation.',
        orderNumber: 'Order Number',
        payment: 'Payment',
        orderStatus: 'Order Status',
        total: 'Total Paid',
        method: 'Method',
        tracking: 'Track Order',
        continueShopping: 'Continue Shopping',
        customerCare: 'Customer Care',
        loading: 'Loading payment result...',
        notFound: 'Payment result not found. Please use order tracking with your phone number.',
        secure: 'SSLCommerz verification',
        support: 'Support available 9 AM - 10 PM',
    },
    bn: {
        paidEyebrow: 'পেমেন্ট কনফার্ম হয়েছে',
        failedEyebrow: 'পেমেন্ট সম্পন্ন হয়নি',
        pendingEyebrow: 'পেমেন্ট পেন্ডিং',
        paidTitle: 'আপনার পেমেন্ট কনফার্ম হয়েছে।',
        failedTitle: 'পেমেন্ট সম্পন্ন করা যায়নি।',
        pendingTitle: 'আমরা আপনার পেমেন্ট যাচাই করছি।',
        paidText: 'আপনার payment successfully verify হয়েছে। অর্ডারটি দ্রুত processing এ যাবে।',
        failedText: 'আপনার payment failed অথবা cancelled হয়েছে। Order track করুন অথবা customer care এ যোগাযোগ করুন।',
        pendingText: 'Payment response পাওয়া গেছে, final confirmation এর জন্য অপেক্ষা করা হচ্ছে।',
        orderNumber: 'অর্ডার নম্বর',
        payment: 'পেমেন্ট',
        orderStatus: 'অর্ডার স্ট্যাটাস',
        total: 'মোট পেমেন্ট',
        method: 'মেথড',
        tracking: 'অর্ডার ট্র্যাক করুন',
        continueShopping: 'শপিং চালিয়ে যান',
        customerCare: 'Customer Care',
        loading: 'Payment result loading...',
        notFound: 'Payment result পাওয়া যায়নি। Phone number দিয়ে order tracking ব্যবহার করুন।',
        secure: 'SSLCommerz verification',
        support: 'Support available 9 AM - 10 PM',
    },
};

const text = computed(() => content[locale.value]);
const payment = computed(() => order.value?.payments?.[0] ?? null);
const paymentStatus = computed(() => payment.value?.status ?? new URLSearchParams(window.location.search).get('status') ?? 'pending');
const isPaid = computed(() => paymentStatus.value === 'paid' || paymentStatus.value === 'success');
const isFailed = computed(() => paymentStatus.value === 'failed' || paymentStatus.value === 'cancelled');
const transactionId = computed(() => new URLSearchParams(window.location.search).get('transaction_id') ?? new URLSearchParams(window.location.search).get('tran_id') ?? '');
const statusTone = computed(() => {
    if (isPaid.value) {
        return 'from-[#8f2638] to-[#171512] text-white';
    }

    if (isFailed.value) {
        return 'from-[#8f2638] to-[#171512] text-white';
    }

    return 'from-[#9f243a] to-[#171512] text-white';
});
const pageCopy = computed(() => {
    if (isPaid.value) {
        return { eyebrow: text.value.paidEyebrow, title: text.value.paidTitle, body: text.value.paidText };
    }

    if (isFailed.value) {
        return { eyebrow: text.value.failedEyebrow, title: text.value.failedTitle, body: text.value.failedText };
    }

    return { eyebrow: text.value.pendingEyebrow, title: text.value.pendingTitle, body: text.value.pendingText };
});

const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const trackHref = computed(() => order.value?.order_number ? '/order-tracking?order_number=' + encodeURIComponent(order.value.order_number) : '/order-tracking');

const loadResult = async () => {
    if (!transactionId.value) {
        error.value = text.value.notFound;
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/storefront/payments/result?transaction_id=' + encodeURIComponent(transactionId.value), {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.message ?? text.value.notFound);
        }

        order.value = payload.data;
    } catch (resultError) {
        error.value = resultError.message || text.value.notFound;
    } finally {
        isLoading.value = false;
    }
};

onMounted(loadResult);
</script>

<template>
    <section class="min-h-[calc(100vh-9rem)] bg-[#f6f6f6] px-4 py-8 transition-colors dark:bg-[#111111] sm:px-6 lg:px-12 lg:py-12">
        <div class="mx-auto grid max-w-6xl gap-5 lg:grid-cols-[1fr_0.78fr] lg:items-stretch">
            <div class="overflow-hidden rounded-lg bg-white shadow-2xl shadow-black/8 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <div class="bg-gradient-to-br p-6 sm:p-8 lg:p-10" :class="statusTone">
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/12 px-4 py-2 text-xs font-black uppercase tracking-normal ring-1 ring-white/20">
                        <FontAwesomeIcon :icon="isPaid ? icons.shield : isFailed ? icons.xmark : icons.clock" />
                        {{ pageCopy.eyebrow }}
                    </p>
                    <h1 class="mt-6 max-w-3xl font-serif text-4xl leading-[1.05] sm:text-5xl lg:text-6xl">{{ pageCopy.title }}</h1>
                    <p class="mt-5 max-w-2xl text-base font-semibold leading-8 text-white/78">{{ pageCopy.body }}</p>
                </div>

                <div class="p-5 sm:p-7 lg:p-8">
                    <div v-if="isLoading" class="grid min-h-40 place-items-center rounded-lg bg-[#f6f6f6] text-sm font-black text-[#4b5563] dark:bg-black/20 dark:text-[#e5e7eb]">
                        {{ text.loading }}
                    </div>

                    <div v-else-if="error" class="rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 p-5 text-sm font-bold leading-6 text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">
                        {{ error }}
                    </div>

                    <div v-else-if="order" class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                            <p class="text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.orderNumber }}</p>
                            <p class="mt-2 break-all text-lg font-black text-[#171512] dark:text-white">{{ order.order_number }}</p>
                        </div>
                        <div class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                            <p class="text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.payment }}</p>
                            <p class="mt-2 text-lg font-black capitalize text-[#171512] dark:text-white">{{ order.payment_status }}</p>
                        </div>
                        <div class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                            <p class="text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.orderStatus }}</p>
                            <p class="mt-2 text-lg font-black capitalize text-[#171512] dark:text-white">{{ order.status }}</p>
                        </div>
                        <div class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                            <p class="text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.total }}</p>
                            <p class="mt-2 text-lg font-black text-[#8f2638] dark:text-[#ff8aa0]">{{ formatPrice(order.grand_total) }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <a class="inline-flex h-12 items-center justify-center gap-3 rounded-lg bg-[#171512] px-5 text-sm font-black text-white shadow-xl shadow-black/15 transition hover:bg-[#8f2638] dark:bg-[#9f243a] dark:text-white" :href="trackHref">
                            {{ text.tracking }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </a>
                        <a class="inline-flex h-12 items-center justify-center gap-3 rounded-lg border border-black/10 bg-white px-5 text-sm font-black text-[#171512] transition hover:bg-[#f6f6f6] dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15" href="/products">
                            {{ text.continueShopping }}
                        </a>
                    </div>
                </div>
            </div>

            <aside class="rounded-lg bg-white/82 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10 sm:p-6 lg:p-7">
                <div class="grid gap-4">
                    <div class="rounded-lg bg-[#171512] p-5 text-white dark:bg-[#9f243a] dark:text-white">
                        <p class="text-xs font-black uppercase tracking-normal opacity-70">{{ storefrontCopy.brand }}</p>
                        <h2 class="mt-2 text-2xl font-black">{{ text.secure }}</h2>
                        <p class="mt-3 text-sm font-semibold leading-6 opacity-75">{{ text.support }}</p>
                    </div>

                    <div class="grid gap-3">
                        <div class="flex items-center gap-3 rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-[#8f2638] text-white dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.shield" /></span>
                            <div>
                                <p class="text-sm font-black text-[#171512] dark:text-white">{{ text.method }}</p>
                                <p class="mt-1 text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ payment?.method ?? 'SSLCommerz' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-[#9f243a] text-white"><FontAwesomeIcon :icon="icons.truck" /></span>
                            <div>
                                <p class="text-sm font-black text-[#171512] dark:text-white">{{ text.customerCare }}</p>
                                <p class="mt-1 text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">09612-345678</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</template>
