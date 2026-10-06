<script setup>
import { computed, reactive } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useAuth } from '../../composables/useAuth';
import OrderTrackingTimeline from '../order/OrderTrackingTimeline.vue';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();
const { isOrderLoading, orderError, trackOrder, trackedOrder } = useAuth();

const form = reactive({
    order_number: new URLSearchParams(window.location.search).get('order') || '',
    phone: '',
});
const pageCopy = {
    en: {
        eyebrow: 'Order Tracking',
        title: 'Track your delivery with order number and phone.',
        subtitle: 'Use the exact phone number used at checkout. Logged-in customers can also view complete history from their dashboard.',
        orderNumber: 'Order Number',
        phone: 'Mobile Number',
        placeholderOrder: 'AUR-2026-0001',
        placeholderPhone: '01712345678',
        action: 'Track Order',
        dashboard: 'Customer Dashboard',
        notSubmitted: 'Enter your order information to see the latest status.',
        found: 'Order Found',
        status: 'Order Status',
        payment: 'Payment',
        total: 'Total',
        delivery: 'Delivery Address',
        items: 'Items',
        qty: 'Qty',
        timeline: 'Progress',
        support: 'Need help?',
        supportText: 'Call 09612-345678 with your order number for faster support.',
        transaction: 'Transaction ID',
        senderPhone: 'Sender Phone',
        manualPayment: 'Mobile Payment Review',
        manualPaymentStatuses: { submitted_for_review: 'Payment proof submitted - waiting for admin review', verified: 'Payment verified', rejected: 'Payment proof rejected - contact support' },
        steps: ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
        statusLabels: {
            pending: 'Pending',
            confirmed: 'Confirmed',
            processing: 'Processing',
            shipped: 'Shipped',
            delivered: 'Delivered',
            cancelled: 'Cancelled',
            returned: 'Returned',
        },
    },
    bn: {
        eyebrow: 'Order Tracking',
        title: 'Order number ও phone দিয়ে delivery track করুন।',
        subtitle: 'Checkout এ যে phone number ব্যবহার করেছেন সেটি দিন। Login customer dashboard থেকেও full order history দেখতে পারবেন।',
        orderNumber: 'Order Number',
        phone: 'Mobile Number',
        placeholderOrder: 'AUR-2026-0001',
        placeholderPhone: '01712345678',
        action: 'Order Track করুন',
        dashboard: 'Customer Dashboard',
        notSubmitted: 'Latest status দেখতে order information দিন।',
        found: 'Order পাওয়া গেছে',
        status: 'Order Status',
        payment: 'Payment',
        total: 'মোট',
        delivery: 'Delivery Address',
        items: 'Items',
        qty: 'Qty',
        timeline: 'Progress',
        support: 'সহায়তা লাগবে?',
        supportText: 'দ্রুত support এর জন্য 09612-345678 নম্বরে order number সহ কল করুন।',
        transaction: 'Transaction ID',
        senderPhone: 'Sender Phone',
        manualPayment: 'Mobile Payment Review',
        manualPaymentStatuses: { submitted_for_review: 'Payment proof জমা হয়েছে - admin review pending', verified: 'Payment verified', rejected: 'Payment proof rejected - support এ যোগাযোগ করুন' },
        steps: ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
        statusLabels: {
            pending: 'Pending',
            confirmed: 'Confirmed',
            processing: 'Processing',
            shipped: 'Shipped',
            delivered: 'Delivered',
            cancelled: 'Cancelled',
            returned: 'Returned',
        },
    },
};

const copy = computed(() => pageCopy[locale.value]);
const statusOrder = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
const currentStatusIndex = computed(() => statusOrder.indexOf(trackedOrder.value?.status));
const isTerminalException = computed(() => ['cancelled', 'returned'].includes(trackedOrder.value?.status));
const manualSummary = computed(() => trackedOrder.value?.payments?.[0]?.manual_summary ?? null);

const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatStatus = (status) => copy.value.statusLabels[status] || status || '-';
const formatManualStatus = (status) => copy.value.manualPaymentStatuses?.[status] || status || '-';

const submitTracking = async () => {
    await trackOrder({
        order_number: form.order_number.trim(),
        phone: form.phone.trim(),
    });
};
</script>

<template>
    <section class="bg-[#f6f6f6] py-8 transition-colors dark:bg-[#111111] sm:py-10 lg:py-14">
        <div class="mx-auto grid max-w-[1440px] gap-6 px-4 sm:px-6 lg:grid-cols-[0.38fr_0.62fr] lg:px-12">
            <aside class="h-max rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10 sm:p-7 lg:sticky lg:top-36">
                <span class="inline-flex items-center gap-2 rounded-full bg-[#fff5f6] px-3 py-1.5 text-xs font-black uppercase tracking-normal text-[#8f2638] dark:bg-[#9f243a]/15 dark:text-[#ff8aa0]">
                    <FontAwesomeIcon :icon="icons.truck" />
                    {{ copy.eyebrow }}
                </span>
                <h1 class="mt-5 font-serif text-4xl leading-[1.08] text-[#171512] dark:text-white sm:text-5xl lg:text-[3.25rem]">{{ copy.title }}</h1>
                <p class="mt-4 text-base leading-8 text-[#374151] dark:text-[#e5e7eb]">{{ copy.subtitle }}</p>

                <form class="mt-6 space-y-4" @submit.prevent="submitTracking">
                    <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">
                        {{ copy.orderNumber }}
                        <input v-model="form.order_number" required class="mt-2 h-12 w-full rounded-lg border border-black/10 bg-[#ffffff] px-4 text-sm font-black uppercase text-[#171512] outline-none transition focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="text" :placeholder="copy.placeholderOrder">
                    </label>
                    <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">
                        {{ copy.phone }}
                        <input v-model="form.phone" required pattern="01[3-9][0-9]{8}" class="mt-2 h-12 w-full rounded-lg border border-black/10 bg-[#ffffff] px-4 text-sm font-black text-[#171512] outline-none transition focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="tel" :placeholder="copy.placeholderPhone">
                    </label>
                    <button class="flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-xl shadow-[#8f2638]/20 transition hover:bg-[#741c2b] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-[#9f243a] dark:text-white dark:hover:bg-[#861e31]" type="submit" :disabled="isOrderLoading">
                        <FontAwesomeIcon :icon="icons.search" />
                        {{ copy.action }}
                    </button>
                    <a href="/customer/dashboard" class="flex h-11 w-full items-center justify-center gap-3 rounded-lg border border-black/10 bg-transparent text-sm font-black text-[#171512] transition hover:bg-white dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                        <FontAwesomeIcon :icon="icons.user" />
                        {{ copy.dashboard }}
                    </a>
                </form>
            </aside>

            <div class="space-y-5">
                <p v-if="orderError" class="rounded-lg border border-[#b4233c]/20 bg-[#fff1f3] px-5 py-4 text-sm font-black text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#2b1117] dark:text-[#ff8aa0]">{{ orderError }}</p>

                <section v-if="trackedOrder" class="overflow-hidden rounded-lg bg-white/88 shadow-2xl shadow-black/8 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                    <div class="border-b border-black/8 bg-[#171512] p-5 text-white dark:border-white/10 dark:bg-black/30 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-normal text-[#ff8aa0]">{{ copy.found }}</p>
                                <h2 class="mt-2 text-2xl font-black sm:text-3xl">{{ trackedOrder.order_number }}</h2>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-black uppercase text-white ring-1 ring-white/15">{{ copy.status }}: {{ formatStatus(trackedOrder.status) }}</span>
                                <span class="rounded-full bg-[#9f243a] px-3 py-1.5 text-xs font-black uppercase text-white">{{ copy.total }} {{ formatPrice(trackedOrder.grand_total) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 p-5 sm:p-6 xl:grid-cols-[1fr_0.38fr]">
                        <div class="space-y-5">
                            <OrderTrackingTimeline :order="trackedOrder" />

                            <section class="rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.items }}</p>
                                <div class="mt-4 divide-y divide-black/8 overflow-hidden rounded-lg bg-white/80 ring-1 ring-black/5 dark:divide-white/10 dark:bg-white/8 dark:ring-white/10">
                                    <article v-for="item in trackedOrder.items" :key="item.id" class="flex items-center justify-between gap-4 p-4">
                                        <div class="min-w-0">
                                            <h3 class="line-clamp-1 text-sm font-black text-[#171512] dark:text-white">{{ item.product_name }}</h3>
                                            <p class="mt-1 text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ item.variant_name || item.sku }} · {{ copy.qty }} {{ item.quantity }}</p>
                                        </div>
                                        <strong class="shrink-0 text-sm font-black text-[#8f2638] dark:text-[#ff8aa0]">{{ formatPrice(item.line_total) }}</strong>
                                    </article>
                                </div>
                            </section>
                        </div>

                        <aside class="space-y-4">
                            <section class="rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.delivery }}</p>
                                <p class="mt-3 text-sm font-black text-[#171512] dark:text-white">{{ trackedOrder.customer_name }} · {{ trackedOrder.customer_phone }}</p>
                                <p class="mt-2 text-sm font-semibold leading-6 text-[#4b5563] dark:text-[#d1d5db]">{{ trackedOrder.shipping_address?.address_line }}, {{ trackedOrder.shipping_address?.area }}, {{ trackedOrder.shipping_address?.district }}</p>
                            </section>

                            <section class="rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.payment }}</p>
                                <div class="mt-3 space-y-2 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]">
                                    <div class="flex justify-between gap-3"><span>{{ copy.payment }}</span><span class="uppercase">{{ trackedOrder.payment_method }}</span></div>
                                    <div class="flex justify-between gap-3"><span>{{ copy.status }}</span><span class="capitalize">{{ trackedOrder.payment_status }}</span></div>
                                    <div class="flex justify-between gap-3 border-t border-black/10 pt-3 text-base text-[#171512] dark:border-white/10 dark:text-white"><span>{{ copy.total }}</span><strong>{{ formatPrice(trackedOrder.grand_total) }}</strong></div>
                                </div>
                            </section>

                            <section class="rounded-lg bg-[#171512] p-4 text-white shadow-xl shadow-black/10 dark:bg-white/8 dark:ring-1 dark:ring-white/10">
                                <FontAwesomeIcon :icon="icons.shield" class="text-[#ff8aa0]" />
                                <h3 class="mt-3 text-sm font-black">{{ copy.support }}</h3>
                                <p class="mt-2 text-xs font-semibold leading-5 text-white/72">{{ copy.supportText }}</p>
                            </section>
                        </aside>
                    </div>
                </section>

                <section v-else class="grid min-h-[28rem] place-items-center rounded-lg bg-white/72 p-8 text-center shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                    <div class="max-w-sm">
                        <span class="mx-auto grid size-14 place-items-center rounded-lg bg-[#171512] text-xl text-white shadow-lg shadow-black/15 dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.truck" /></span>
                        <h2 class="mt-5 text-xl font-black text-[#171512] dark:text-white">{{ copy.notSubmitted }}</h2>
                    </div>
                </section>
            </div>
        </div>
    </section>
</template>
