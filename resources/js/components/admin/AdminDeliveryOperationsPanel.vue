<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const AdminFulfillmentOverviewPanel = defineAsyncComponent(() => import('./AdminFulfillmentOverviewPanel.vue'));
const AdminShipmentsPanel = defineAsyncComponent(() => import('./AdminShipmentsPanel.vue'));
const AdminDeliveryZonesPanel = defineAsyncComponent(() => import('./AdminDeliveryZonesPanel.vue'));

const { icons, locale } = useStorefront();
const activeTab = ref('overview');

const text = computed(() => locale.value === 'bn' ? {
    overview: 'ফুলফিলমেন্ট',
    shipments: 'শিপমেন্ট',
    zones: 'ডেলিভারি জোন',
} : {
    overview: 'Fulfillment',
    shipments: 'Shipments',
    zones: 'Delivery Zones',
});

const tabs = computed(() => [
    { id: 'overview', label: text.value.overview, icon: icons.chart },
    { id: 'shipments', label: text.value.shipments, icon: icons.truck },
    { id: 'zones', label: text.value.zones, icon: icons.grip },
]);
</script>

<template>
    <div class="space-y-4">
        <div class="flex gap-2 overflow-x-auto rounded-lg bg-white/80 p-1.5 shadow-lg shadow-black/5 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <button v-for="tab in tabs" :key="tab.id" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg px-4 text-sm font-black transition" :class="activeTab === tab.id ? 'bg-[#171512] text-white shadow-md shadow-black/10 dark:bg-[#9f243a] dark:text-white' : 'text-[#374151] hover:bg-black/5 dark:text-[#e5e7eb] dark:hover:bg-white/10'" type="button" @click="activeTab = tab.id">
                <FontAwesomeIcon :icon="tab.icon" />
                {{ tab.label }}
            </button>
        </div>

        <AdminFulfillmentOverviewPanel v-if="activeTab === 'overview'" />
        <AdminShipmentsPanel v-else-if="activeTab === 'shipments'" />
        <AdminDeliveryZonesPanel v-else />
    </div>
</template>
