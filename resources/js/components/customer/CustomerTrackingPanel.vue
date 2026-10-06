<script setup>
import OrderTrackingTimeline from '../order/OrderTrackingTimeline.vue';
defineProps({
    tracking: { type: Object, required: true },
    trackedOrder: { type: Object, default: null },
    text: { type: Object, required: true },
    isOrderLoading: { type: Boolean, required: true },
    orderError: { type: String, default: '' },
    formatPrice: { type: Function, required: true },
    statusBadgeClasses: { type: Function, required: true },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <section class="panel-card">
        <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.trackTitle }}</h2>
        <div v-if="orderError" class="note-wine mt-4">{{ orderError }}</div>

        <form class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]" @submit.prevent="emit('submit')">
            <label class="field-label">
                {{ text.orderNumber }}
                <input v-model="tracking.order_number" required class="field-input" type="text">
            </label>
            <label class="field-label">
                {{ text.phone }}
                <input v-model="tracking.phone" required pattern="01[3-9][0-9]{8}" class="field-input" type="tel">
            </label>
            <button class="btn-wine md:mt-6" type="submit" :disabled="isOrderLoading">
                {{ text.submitTrack }}
            </button>
        </form>

        <article v-if="trackedOrder" class="mt-4 space-y-4 rounded-2xl bg-ink/5 p-4 dark:bg-white/8">
            <div class="flex items-center justify-between gap-3">
                <strong class="truncate text-sm font-semibold text-ink dark:text-white">{{ trackedOrder.order_number }}</strong>
                <span class="rounded-full bg-wine/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-wine">{{ trackedOrder.status }}</span>
            </div>
            <div class="flex items-center justify-between text-sm font-semibold text-ink/55 dark:text-white/55">
                <span>{{ text.total }}</span>
                <span>{{ formatPrice(trackedOrder.grand_total) }}</span>
            </div>
            <OrderTrackingTimeline :order="trackedOrder" compact />
        </article>
    </section>
</template>
