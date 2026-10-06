<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import OrderTrackingTimeline from '../order/OrderTrackingTimeline.vue';

defineProps({
    orders: { type: Array, required: true },
    selectedOrder: { type: Object, default: null },
    text: { type: Object, required: true },
    icons: { type: Object, required: true },
    isOrderLoading: { type: Boolean, required: true },
    isReviewLoading: { type: Boolean, required: true },
    isReturnLoading: { type: Boolean, required: true },
    orderError: { type: String, default: '' },
    reviewError: { type: String, default: '' },
    reviewNotice: { type: String, default: '' },
    returnError: { type: String, default: '' },
    returnNotice: { type: String, default: '' },
    reviewForm: { type: Object, required: true },
    returnForm: { type: Object, required: true },
    formatPrice: { type: Function, required: true },
    formatDate: { type: Function, required: true },
    statusBadgeClasses: { type: Function, required: true },
    statusStepIndex: { type: Function, required: true },
    isTerminalProblemStatus: { type: Function, required: true },
    orderAddress: { type: Function, required: true },
    canReviewItem: { type: Function, required: true },
    reviewForItem: { type: Function, required: true },
    canRequestReturn: { type: Function, required: true },
    returnRequestForItem: { type: Function, required: true },
});

const firstPayment = (order) => order?.payments?.[0] ?? null;
const manualSummary = (order) => firstPayment(order)?.manual_summary ?? null;
const manualStatusLabel = (status, text) => text.manualPaymentStatuses?.[status] ?? status ?? '-';

const emit = defineEmits([
    'refresh',
    'show-details',
    'close-details',
    'open-review',
    'close-review',
    'submit-review',
    'set-review-rating',
    'open-return',
    'close-return',
    'submit-return',
]);
</script>

<template>
    <section class="overflow-hidden rounded-[1.4rem] border border-black/10 bg-white dark:border-white/10 dark:bg-white/8">
        <div class="flex items-center justify-between gap-3 border-b border-black/8 px-4 py-4 dark:border-white/10">
            <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.recentOrders }}</h2>
            <button class="btn-ghost" type="button" :disabled="isOrderLoading" @click="emit('refresh')">
                {{ text.refresh }}
            </button>
        </div>

        <div class="p-4">
            <div v-if="orderError" class="mb-3 rounded-md bg-wine/8 px-3 py-2 text-xs font-bold text-wine dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ orderError }}</div>
            <p v-if="orders.length === 0" class="rounded-lg bg-[#f6f6f6] px-4 py-8 text-center text-sm font-semibold text-[#4b5563] ring-1 ring-black/5 dark:bg-black/20 dark:text-[#d1d5db] dark:ring-white/10">{{ text.noOrders }} {{ text.emptyHint }}</p>

            <div v-else class="space-y-3">
                <article v-for="orderItem in orders" :key="orderItem.id" class="overflow-hidden rounded-lg bg-[#ffffff] ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                    <div class="flex flex-col gap-3 border-b border-black/8 p-4 dark:border-white/10 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-ink dark:text-white">{{ orderItem.order_number }}</p>
                            <p class="mt-1 text-[11px] font-bold uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.placedOn }} {{ formatDate(orderItem.created_at) }}</p>
                        </div>
                        <span class="w-fit shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase" :class="statusBadgeClasses(orderItem)">{{ orderItem.status }}</span>
                    </div>

                    <div class="grid gap-3 p-4 sm:grid-cols-3">
                        <div class="rounded-lg bg-white/78 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <p class="text-[10px] font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.total }}</p>
                            <p class="mt-1 text-base font-semibold text-ink dark:text-white">{{ formatPrice(orderItem.grand_total) }}</p>
                        </div>
                        <div class="rounded-lg bg-white/78 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <p class="text-[10px] font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.payment }}</p>
                            <p class="mt-1 text-sm font-semibold uppercase text-ink dark:text-white">{{ orderItem.payment_method }}</p>
                            <p v-if="manualSummary(orderItem)" class="mt-1 text-[11px] font-bold text-wine dark:text-[#ff8aa0]">{{ manualStatusLabel(manualSummary(orderItem).status, text) }}</p>
                        </div>
                        <div class="rounded-lg bg-white/78 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <p class="text-[10px] font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.products }}</p>
                            <p class="mt-1 text-sm font-semibold text-ink dark:text-white">{{ orderItem.items?.length || 0 }} {{ text.items }}</p>
                        </div>
                    </div>

                    <div class="px-4 pb-3">
                        <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-ink px-4 text-xs font-semibold text-white transition hover:bg-wine disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="isOrderLoading" @click="emit('show-details', orderItem)">
                            {{ text.viewDetails }}<FontAwesomeIcon :icon="icons.arrowRight" />
                        </button>
                    </div>

                    <div class="px-4 pb-4">
                        <div v-if="!isTerminalProblemStatus(orderItem)" class="grid grid-cols-5 gap-1">
                            <div v-for="(statusStep, statusIndex) in text.statusSteps" :key="statusStep" class="min-w-0">
                                <div class="h-1.5 rounded-full" :class="statusIndex <= statusStepIndex(orderItem) ? 'bg-wine dark:bg-[#9f243a]' : 'bg-black/10 dark:bg-white/12'"></div>
                                <p class="mt-1 truncate text-[9px] font-semibold uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ statusStep }}</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg bg-white/78 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <p class="text-[10px] font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.deliveryTo }}</p>
                            <p class="mt-1 line-clamp-2 text-xs font-semibold leading-5 text-[#374151] dark:text-[#e5e7eb]">{{ orderAddress(orderItem) }}</p>
                        </div>

                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            <div v-for="lineItem in orderItem.items" :key="lineItem.id" class="flex items-center justify-between gap-3 rounded-lg bg-white/70 px-3 py-2 text-xs ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                                <div class="min-w-0">
                                    <p class="line-clamp-1 font-semibold text-ink dark:text-white">{{ lineItem.product_name }}</p>
                                    <p class="mt-0.5 font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ lineItem.quantity }} x {{ formatPrice(lineItem.unit_price) }}</p>
                                </div>
                                <span class="shrink-0 font-semibold text-wine dark:text-[#ff8aa0]">{{ formatPrice(lineItem.line_total) }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section v-if="selectedOrder" class="overflow-hidden rounded-lg bg-white/90 shadow-2xl shadow-black/8 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="flex flex-col gap-4 border-b border-black/8 bg-ink p-5 text-white dark:border-white/10 dark:bg-black/30 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-normal text-[#ff8aa0]">{{ text.orderDetails }}</p>
                <h2 class="mt-2 truncate text-2xl font-semibold">{{ selectedOrder.order_number }}</h2>
                <p class="mt-1 text-xs font-semibold text-white/70">{{ text.placedOn }} {{ formatDate(selectedOrder.created_at) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase ring-1 ring-white/15">{{ selectedOrder.status }}</span>
                <button class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-ink transition hover:bg-[#9f243a]" type="button" @click="emit('close-details')">{{ text.closeDetails }}</button>
            </div>
        </div>

        <div class="grid gap-5 p-5 lg:grid-cols-[1fr_19rem]">
            <div class="space-y-5">
                <OrderTrackingTimeline :order="selectedOrder" />

                <section class="rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                    <p class="text-xs font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.products }}</p>
                    <div class="mt-4 divide-y divide-black/8 overflow-hidden rounded-lg bg-white/80 ring-1 ring-black/5 dark:divide-white/10 dark:bg-white/8 dark:ring-white/10">
                        <article v-for="lineItem in selectedOrder.items" :key="lineItem.id" class="grid gap-3 p-4 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div class="min-w-0">
                                <h3 class="line-clamp-1 text-sm font-semibold text-ink dark:text-white">{{ lineItem.product_name }}</h3>
                                <p class="mt-1 text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ lineItem.variant_name || lineItem.sku }} · {{ lineItem.quantity }} x {{ formatPrice(lineItem.unit_price) }}</p>
                            </div>
                            <div class="flex flex-col items-start gap-2 sm:items-end">
                                <strong class="text-sm font-semibold text-wine dark:text-[#ff8aa0]">{{ formatPrice(lineItem.line_total) }}</strong>
                                <div class="flex flex-wrap justify-start gap-2 sm:justify-end">
                                    <button v-if="canReviewItem(selectedOrder, lineItem)" class="h-9 rounded-lg bg-ink px-3 text-xs font-semibold text-white transition hover:bg-wine disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="button" @click="emit('open-review', { order: selectedOrder, item: lineItem })">{{ text.writeReview }}</button>
                                    <span v-else class="inline-flex h-9 items-center rounded-lg bg-white/70 px-3 text-[11px] font-semibold uppercase tracking-normal text-[#6b7280] ring-1 ring-black/5 dark:bg-white/8 dark:text-[#d1d5db] dark:ring-white/10">{{ reviewForItem(lineItem)?.status || text.alreadyReviewed }}</span>
                                    <button v-if="canRequestReturn(selectedOrder, lineItem)" class="h-9 rounded-lg border border-[#8f2638]/20 bg-white px-3 text-xs font-semibold text-wine transition hover:bg-wine hover:text-white dark:border-[#9f243a]/30 dark:bg-white/8 dark:text-[#ff8aa0] dark:hover:bg-[#9f243a] dark:hover:text-white" type="button" @click="emit('open-return', { order: selectedOrder, item: lineItem })">{{ text.requestReturn }}</button>
                                    <span v-else class="inline-flex h-9 items-center rounded-lg bg-white/70 px-3 text-[11px] font-semibold uppercase tracking-normal text-[#6b7280] ring-1 ring-black/5 dark:bg-white/8 dark:text-[#d1d5db] dark:ring-white/10">{{ returnRequestForItem(lineItem)?.status || text.notEligibleReturn }}</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <form v-if="reviewForm.order_item_id" class="rounded-lg border border-[#171512]/10 bg-white p-4 shadow-lg shadow-black/5 dark:border-white/10 dark:bg-white/8" @submit.prevent="emit('submit-review')">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.writeReview }}</p>
                            <h3 class="mt-1 line-clamp-1 text-sm font-semibold text-ink dark:text-white">{{ reviewForm.product_name }}</h3>
                        </div>
                        <button class="text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]" type="button" @click="emit('close-review')">{{ text.cancelReview }}</button>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button v-for="star in 5" :key="star" class="grid size-10 place-items-center rounded-lg border text-sm transition" :class="star <= reviewForm.rating ? 'border-[#9f243a] bg-[#fff5f6] text-[#9f243a] dark:border-[#9f243a] dark:bg-[#9f243a]/15 dark:text-[#ff8aa0]' : 'border-black/10 bg-[#f6f6f6] text-[#6b7280] dark:border-white/10 dark:bg-black/20 dark:text-[#d1d5db]'" type="button" @click="emit('set-review-rating', star)"><FontAwesomeIcon :icon="icons.star" /></button>
                    </div>
                    <label class="field-label mt-4">{{ text.reviewTitle }}<input v-model="reviewForm.title" maxlength="120" class="field-input" type="text"></label>
                    <label class="field-label mt-3">{{ text.reviewBody }}<textarea v-model="reviewForm.body" required minlength="10" maxlength="2000" rows="3" class="field-textarea"></textarea></label>
                    <button class="btn-ink mt-3" type="submit" :disabled="isReviewLoading">{{ text.submitReview }}<FontAwesomeIcon :icon="icons.arrowRight" /></button>
                </form>

                <p v-if="reviewNotice" class="rounded-lg border border-[#9f243a]/25 bg-[#9f243a]/10 px-4 py-3 text-sm font-semibold text-wine dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ reviewNotice }}</p>
                <p v-if="reviewError" class="rounded-lg border border-[#8f2638]/20 bg-wine/8 px-4 py-3 text-sm font-bold text-wine dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ reviewError }}</p>

                <form v-if="returnForm.order_item_id" class="rounded-lg border border-[#8f2638]/20 bg-[#fff5f6] p-4 shadow-lg shadow-black/5 dark:border-[#9f243a]/20 dark:bg-[#27272a]" @submit.prevent="emit('submit-return')">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.requestReturn }}</p>
                            <h3 class="mt-1 line-clamp-1 text-sm font-semibold text-ink dark:text-white">{{ returnForm.product_name }}</h3>
                        </div>
                        <button class="text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]" type="button" @click="emit('close-return')">{{ text.cancelReturn }}</button>
                    </div>
                    <label class="field-label mt-4">{{ text.returnReason }}<textarea v-model="returnForm.reason" required minlength="10" maxlength="1000" rows="3" class="field-textarea"></textarea></label>
                    <button class="mt-3 inline-flex h-10 items-center gap-2 rounded-lg bg-wine px-4 text-xs font-semibold text-white transition hover:bg-[#741c2b] disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isReturnLoading">{{ text.submitReturn }}<FontAwesomeIcon :icon="icons.arrowRight" /></button>
                </form>

                <p v-if="returnNotice" class="rounded-lg border border-[#9f243a]/25 bg-[#9f243a]/10 px-4 py-3 text-sm font-semibold text-wine dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ returnNotice }}</p>
                <p v-if="returnError" class="rounded-lg border border-[#8f2638]/20 bg-wine/8 px-4 py-3 text-sm font-bold text-wine dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ returnError }}</p>

                <section class="rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                    <p class="text-xs font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.deliveryTo }}</p>
                    <p class="mt-3 text-sm font-semibold text-ink dark:text-white">{{ selectedOrder.customer_name }} · {{ selectedOrder.customer_phone }}</p>
                    <p class="mt-2 text-sm font-semibold leading-6 text-[#4b5563] dark:text-[#d1d5db]">{{ orderAddress(selectedOrder) }}</p>
                </section>
            </div>

            <aside class="h-max rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.invoiceSummary }}</p>
                <div class="mt-4 space-y-3 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]">
                    <div class="flex justify-between gap-3"><span>{{ text.subtotal }}</span><span>{{ formatPrice(selectedOrder.subtotal) }}</span></div>
                    <div class="flex justify-between gap-3"><span>{{ text.deliveryCharge }}</span><span>{{ formatPrice(selectedOrder.shipping_total) }}</span></div>
                    <div v-if="selectedOrder.discount_total > 0" class="flex justify-between gap-3 text-wine dark:text-[#ff8aa0]"><span>{{ text.discount }}</span><span>-{{ formatPrice(selectedOrder.discount_total) }}</span></div>
                    <div class="flex justify-between gap-3 border-t border-black/10 pt-3 text-base text-ink dark:border-white/10 dark:text-white"><span>{{ text.grandTotal }}</span><strong>{{ formatPrice(selectedOrder.grand_total) }}</strong></div>
                </div>
                <div class="mt-4 rounded-lg bg-white/80 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                    <p class="text-[10px] font-semibold uppercase tracking-normal text-wine dark:text-[#ff8aa0]">{{ text.payment }}</p>
                    <p class="mt-1 text-sm font-semibold uppercase text-ink dark:text-white">{{ selectedOrder.payment_method }} · {{ selectedOrder.payment_status }}</p>
                </div>
            </aside>
        </div>
    </section>
</template>
