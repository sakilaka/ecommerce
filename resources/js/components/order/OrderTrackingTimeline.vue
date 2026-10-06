<script setup>
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const props = defineProps({
    order: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});

const { icons, locale } = useStorefront();

const text = computed(() => locale.value === 'bn' ? {
    title: 'ডেলিভারি টাইমলাইন',
    subtitle: 'কুরিয়ার ও ডেলিভারি অগ্রগতি এখানে দেখা যাবে।',
    orderPlaced: 'অর্ডার প্লেসড',
    confirmed: 'কনফার্মড',
    processing: 'প্রসেসিং',
    shipped: 'শিপড',
    inTransit: 'অন দ্য ওয়ে',
    delivered: 'ডেলিভার্ড',
    cancelled: 'অর্ডার বাতিল হয়েছে',
    returned: 'অর্ডার রিটার্ন হয়েছে',
    failed: 'ডেলিভারি ব্যর্থ হয়েছে',
    courier: 'কুরিয়ার',
    tracking: 'Tracking ID',
    expected: 'Expected delivery',
    cod: 'COD amount',
    notAssigned: 'এখনও courier assign হয়নি',
    latestUpdates: 'সর্বশেষ আপডেট',
    location: 'লোকেশন',
} : {
    title: 'Delivery Timeline',
    subtitle: 'Courier and delivery progress will appear here.',
    orderPlaced: 'Order placed',
    confirmed: 'Confirmed',
    processing: 'Processing',
    shipped: 'Shipped',
    inTransit: 'On the way',
    delivered: 'Delivered',
    cancelled: 'Order cancelled',
    returned: 'Order returned',
    failed: 'Delivery failed',
    courier: 'Courier',
    tracking: 'Tracking ID',
    expected: 'Expected delivery',
    cod: 'COD amount',
    notAssigned: 'Courier not assigned yet',
    latestUpdates: 'Latest updates',
    location: 'Location',
});

const shipment = computed(() => props.order?.shipments?.[0] ?? null);
const normalizedOrderStatus = computed(() => String(props.order?.status ?? '').toLowerCase());
const normalizedShipmentStatus = computed(() => String(shipment.value?.status ?? '').toLowerCase());
const currentKey = computed(() => normalizedShipmentStatus.value || normalizedOrderStatus.value);
const exceptionLabel = computed(() => ({ cancelled: text.value.cancelled, returned: text.value.returned, failed: text.value.failed }[currentKey.value] ?? ''));
const steps = computed(() => [
    { key: 'pending', label: text.value.orderPlaced, date: props.order?.created_at, icon: icons.bag },
    { key: 'confirmed', label: text.value.confirmed, date: props.order?.confirmed_at, icon: icons.shield },
    { key: 'processing', label: text.value.processing, date: null, icon: icons.clock },
    { key: 'shipped', label: text.value.shipped, date: shipment.value?.shipped_at ?? props.order?.shipped_at, icon: icons.truck },
    { key: 'in_transit', label: text.value.inTransit, date: shipment.value?.shipped_at ?? props.order?.shipped_at, icon: icons.truck },
    { key: 'delivered', label: text.value.delivered, date: shipment.value?.delivered_at ?? props.order?.delivered_at, icon: icons.award },
]);
const stepOrder = ['pending', 'confirmed', 'processing', 'ready_to_ship', 'shipped', 'in_transit', 'delivered'];
const currentIndex = computed(() => {
    if (currentKey.value === 'ready_to_ship') return 3;
    const index = stepOrder.indexOf(currentKey.value);

    return index === -1 ? 0 : index;
});
const visualStepIndex = (step) => step.key === 'shipped' && currentIndex.value >= 4 ? 4 : stepOrder.indexOf(step.key);
const isComplete = (step) => visualStepIndex(step) <= currentIndex.value;
const visibleEvents = computed(() => shipment.value?.events ?? []);
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '';
</script>

<template>
    <section class="rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10" :class="compact ? 'shadow-none' : 'shadow-xl shadow-black/5'">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#9f243a]">{{ text.title }}</p>
                <p class="mt-1 text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
            </div>
            <span v-if="exceptionLabel" class="w-fit rounded-full bg-[#8f2638]/10 px-3 py-1.5 text-xs font-black uppercase text-[#8f2638] ring-1 ring-[#8f2638]/20 dark:bg-[#9f243a]/10 dark:text-[#9f243a] dark:ring-[#9f243a]/20">{{ exceptionLabel }}</span>
        </div>

        <div v-if="!exceptionLabel" class="mt-5 grid gap-3 sm:grid-cols-3 xl:grid-cols-6">
            <div v-for="step in steps" :key="step.key" class="relative rounded-lg p-3 ring-1 transition" :class="isComplete(step) ? 'bg-[#171512] text-white ring-[#171512] dark:bg-[#9f243a] dark:text-white dark:ring-[#9f243a]' : 'bg-white/78 text-[#4b5563] ring-black/6 dark:bg-white/8 dark:text-[#d1d5db] dark:ring-white/10'">
                <div class="flex items-center gap-2">
                    <span class="grid size-8 place-items-center rounded-lg" :class="isComplete(step) ? 'bg-white/14 dark:bg-[#171512]/10' : 'bg-[#f6f6f6] dark:bg-black/20'"><FontAwesomeIcon :icon="step.icon" /></span>
                    <p class="text-xs font-black uppercase leading-tight">{{ step.label }}</p>
                </div>
                <p class="mt-2 min-h-4 text-[11px] font-bold opacity-75">{{ formatDate(step.date) || ' ' }}</p>
            </div>
        </div>

        <div v-if="visibleEvents.length" class="mt-5 rounded-lg bg-white/70 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.latestUpdates }}</p>
            <div class="mt-3 space-y-2">
                <div v-for="event in visibleEvents" :key="event.id" class="grid gap-2 rounded-lg bg-[#ffffff] p-3 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10 sm:grid-cols-[auto_1fr_auto]">
                    <span class="w-fit rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="event.status === 'failed' ? 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-red-300' : 'bg-[#9f243a]/16 text-[#8f2638] ring-[#9f243a]/30 dark:text-[#9f243a]'">{{ event.status.replaceAll('_', ' ') }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[#171512] dark:text-white">{{ event.message || event.event_type.replaceAll('_', ' ') }}</p>
                        <p v-if="event.location" class="mt-1 text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.location }}: {{ event.location }}</p>
                    </div>
                    <p class="text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ formatDate(event.occurred_at) }}</p>
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-4">
            <div class="rounded-lg bg-white/80 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.courier }}</p>
                <p class="mt-1 truncate text-sm font-black text-[#171512] dark:text-white">{{ shipment?.courier_name || text.notAssigned }}</p>
            </div>
            <div class="rounded-lg bg-white/80 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.tracking }}</p>
                <a v-if="shipment?.tracking_url" class="mt-1 block truncate text-sm font-black text-[#8f2638] dark:text-[#9f243a]" :href="shipment.tracking_url" target="_blank" rel="noreferrer">{{ shipment.tracking_number || shipment.tracking_url }}</a>
                <p v-else class="mt-1 truncate text-sm font-black text-[#171512] dark:text-white">{{ shipment?.tracking_number || '-' }}</p>
            </div>
            <div class="rounded-lg bg-white/80 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.expected }}</p>
                <p class="mt-1 truncate text-sm font-black text-[#171512] dark:text-white">{{ formatDate(shipment?.estimated_delivery_at) || '-' }}</p>
            </div>
            <div class="rounded-lg bg-white/80 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.cod }}</p>
                <p class="mt-1 truncate text-sm font-black text-[#171512] dark:text-white">{{ formatPrice(shipment?.cod_amount) }}</p>
            </div>
        </div>
    </section>
</template>
