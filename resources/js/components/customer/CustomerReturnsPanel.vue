<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    returns: { type: Array, required: true },
    text: { type: Object, required: true },
    isLoading: { type: Boolean, required: true },
    error: { type: String, default: '' },
    formatPrice: { type: Function, required: true },
    formatDate: { type: Function, required: true },
    icons: { type: Object, required: true },
});

const emit = defineEmits(['refresh']);
</script>

<template>
    <section class="panel-card">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.returnsTitle }}</h2>
            <button class="btn-ghost" type="button" :disabled="isLoading" @click="emit('refresh')">
                {{ text.refresh }}
            </button>
        </div>

        <div v-if="error" class="note-wine mt-4">{{ error }}</div>
        <p v-if="returns.length === 0" class="mt-4 rounded-2xl bg-ink/5 px-4 py-8 text-center text-sm font-semibold text-ink/50 dark:bg-white/8 dark:text-white/50">{{ text.noReturns }}</p>

        <div v-else class="mt-4 grid gap-3 md:grid-cols-2">
            <article v-for="returnItem in returns" :key="returnItem.id" class="rounded-2xl bg-ink/5 p-4 dark:bg-white/8">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink dark:text-white">{{ returnItem.return_number }}</p>
                        <p class="mt-1 line-clamp-1 text-xs font-semibold text-ink/55 dark:text-white/55">{{ returnItem.order_item?.product_name }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-wine/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-wine">{{ returnItem.status }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-ink dark:text-white">{{ formatPrice(returnItem.refund_amount) }}</p>
                <p class="mt-2 line-clamp-2 text-xs font-semibold leading-5 text-ink/55 dark:text-white/55">{{ returnItem.reason }}</p>
                <div v-if="returnItem.pickup_shipment" class="mt-4 rounded-2xl bg-white p-3 dark:bg-white/8">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-xs text-white dark:bg-wine"><FontAwesomeIcon :icon="icons.truck" /></span>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-ink dark:text-white">{{ text.returnPickup }}</p>
                                <p class="truncate text-[11px] font-semibold text-ink/55 dark:text-white/55">{{ returnItem.pickup_shipment.courier_name || text.pickupPending }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 rounded-full bg-wine/10 px-2 py-1 text-[10px] font-semibold uppercase text-wine">{{ returnItem.pickup_shipment.status }}</span>
                    </div>
                    <div class="mt-3 grid gap-2 text-xs font-semibold text-ink/55 dark:text-white/55">
                        <p v-if="returnItem.pickup_shipment.tracking_number" class="truncate">{{ text.pickupTracking }}: <span class="font-semibold text-ink dark:text-white">{{ returnItem.pickup_shipment.tracking_number }}</span></p>
                        <a v-if="returnItem.pickup_shipment.tracking_url" class="font-semibold text-wine" :href="returnItem.pickup_shipment.tracking_url" target="_blank" rel="noreferrer">{{ text.trackPickup }}</a>
                        <div v-if="returnItem.pickup_shipment.events?.length" class="grid gap-2 border-t border-black/8 pt-2 dark:border-white/10">
                            <p v-for="event in returnItem.pickup_shipment.events.slice(0, 2)" :key="event.id" class="leading-5">{{ event.message }} <span class="text-wine">{{ formatDate(event.occurred_at) }}</span></p>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
