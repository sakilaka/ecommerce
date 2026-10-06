<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const formError = ref(null);
const shipments = ref([]);
const selectedShipment = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', status: '', courier: '', district: '', cod_only: false, page: 1 });
const form = reactive({ status: 'pending', courier_name: '', tracking_number: '', tracking_url: '', estimated_delivery_at: '', event_location: '', admin_note: '' });
let searchTimer = null;

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'ডেলিভারি ফুলফিলমেন্ট',
    title: 'শিপমেন্ট ও কুরিয়ার ম্যানেজমেন্ট',
    subtitle: 'অর্ডার থেকে তৈরি হওয়া শিপমেন্ট, COD collection, কুরিয়ার assignment এবং tracking status এক জায়গায় নিয়ন্ত্রণ করুন।',
    search: 'অর্ডার, নাম, ফোন বা tracking খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    allCourier: 'সব কুরিয়ার',
    allDistrict: 'সব জেলা',
    codOnly: 'COD only',
    shipment: 'শিপমেন্ট',
    recipient: 'রিসিপিয়েন্ট',
    courier: 'কুরিয়ার',
    tracking: 'Tracking',
    cod: 'COD',
    status: 'স্ট্যাটাস',
    order: 'অর্ডার',
    delivery: 'ডেলিভারি',
    estimated: 'Estimated delivery',
    note: 'Admin note',
    eventLocation: 'Location',
    statusHistory: 'Status History',
    failedHelp: 'Write the reason when delivery failed.',
    eventLocation: 'লোকেশন',
    statusHistory: 'স্ট্যাটাস হিস্টোরি',
    failedHelp: 'Failed delivery হলে কারণ লিখুন।',
    save: 'শিপমেন্ট আপডেট',
    saving: 'আপডেট হচ্ছে...',
    close: 'বন্ধ করুন',
    empty: 'কোনো শিপমেন্ট পাওয়া যায়নি।',
    failed: 'শিপমেন্ট লোড/আপডেট করা যায়নি।',
    prev: 'Prev',
    next: 'Next',
} : {
    eyebrow: 'Delivery Fulfillment',
    title: 'Shipment & Courier Management',
    subtitle: 'Control checkout-created shipments, COD collection, courier assignment, and tracking status from one workspace.',
    search: 'Search order, name, phone, or tracking',
    allStatus: 'All Status',
    allCourier: 'All Couriers',
    allDistrict: 'All Districts',
    codOnly: 'COD only',
    shipment: 'Shipment',
    recipient: 'Recipient',
    courier: 'Courier',
    tracking: 'Tracking',
    cod: 'COD',
    status: 'Status',
    order: 'Order',
    delivery: 'Delivery',
    estimated: 'Estimated delivery',
    note: 'Admin note',
    save: 'Update Shipment',
    saving: 'Updating...',
    close: 'Close',
    empty: 'No shipments found.',
    failed: 'Could not load or update shipments.',
    prev: 'Prev',
    next: 'Next',
});

const shipmentStatuses = ['pending', 'ready_to_ship', 'shipped', 'in_transit', 'delivered', 'failed', 'returned', 'cancelled'];
const couriers = ['Steadfast', 'Pathao Courier', 'RedX', 'Paperfly', 'Sundarban Courier'];
const districts = ['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna', 'Barishal', 'Rangpur', 'Mymensingh'];
const statusOptions = computed(() => [{ value: '', label: text.value.allStatus }, ...shipmentStatuses.map((value) => ({ value, label: value.replaceAll('_', ' ') }))]);
const courierOptions = computed(() => [{ value: '', label: text.value.allCourier }, ...couriers.map((value) => ({ value, label: value }))]);
const districtOptions = computed(() => [{ value: '', label: text.value.allDistrict }, ...districts.map((value) => ({ value, label: value }))]);

const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const dateInputValue = (value) => value ? new Date(value).toISOString().slice(0, 16) : '';
const apiDateValue = (value) => value ? new Date(value).toISOString() : null;
const badgeClass = (status) => ({ pending: 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]', ready_to_ship: 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:text-sky-300', shipped: 'bg-purple-500/10 text-purple-700 ring-purple-500/20 dark:text-purple-300', in_transit: 'bg-indigo-500/10 text-indigo-700 ring-indigo-500/20 dark:text-indigo-300', delivered: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300', failed: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-red-300', returned: 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300', cancelled: 'bg-black/5 text-[#374151] ring-black/10 dark:bg-white/10 dark:text-white' }[status] ?? 'bg-black/5 text-[#374151] ring-black/10 dark:bg-white/10 dark:text-white');

const loadShipments = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;
    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    ['q', 'status', 'courier', 'district'].forEach((key) => { if (String(filters[key]).trim()) params.set(key, String(filters[key]).trim()); });
    if (filters.cod_only) params.set('cod_only', '1');

    try {
        const response = await fetch('/api/admin/shipments?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        shipments.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadShipments(1), 350);
};

const openShipment = async (id) => {
    error.value = null;
    formError.value = null;
    try {
        const response = await fetch('/api/admin/shipments/' + id, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        selectedShipment.value = payload.data;
        Object.assign(form, {
            status: selectedShipment.value.status,
            courier_name: selectedShipment.value.courier_name ?? '',
            tracking_number: selectedShipment.value.tracking_number ?? '',
            tracking_url: selectedShipment.value.tracking_url ?? '',
            estimated_delivery_at: dateInputValue(selectedShipment.value.estimated_delivery_at),
            event_location: selectedShipment.value.recipient_district ?? '',
            admin_note: selectedShipment.value.admin_note ?? '',
        });
    } catch (requestError) {
        error.value = text.value.failed;
    }
};

const updateShipment = async () => {
    if (!selectedShipment.value) return;
    isSaving.value = true;
    formError.value = null;
    try {
        const response = await fetch('/api/admin/shipments/' + selectedShipment.value.id, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ ...form, estimated_delivery_at: apiDateValue(form.estimated_delivery_at) }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        selectedShipment.value = payload.data;
        await loadShipments(filters.page);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

watch(() => [filters.status, filters.courier, filters.district, filters.cod_only], () => loadShipments(1));
onMounted(() => loadShipments());
</script>

<template>
    <section class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
            <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
            <div class="mt-2 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                    <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                </div>
                <div class="rounded-lg bg-[#171512] px-4 py-3 text-white shadow-lg shadow-black/10 dark:bg-[#9f243a] dark:text-white">
                    <p class="text-[10px] font-black uppercase opacity-70">{{ text.shipment }}</p>
                    <p class="text-xl font-black">{{ meta.total }}</p>
                </div>
            </div>
            <div class="mt-5 grid gap-3 xl:grid-cols-[minmax(0,1fr)_170px_170px_170px_auto]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch"></label>
                <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                <select v-model="filters.courier" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in courierOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                <select v-model="filters.district" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-3 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in districtOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                <label class="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-[#f6f6f6] px-4 text-sm font-black ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><input v-model="filters.cod_only" type="checkbox" class="size-4 accent-[#8f2638]">{{ text.codOnly }}</label>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

        <div class="grid gap-3 p-4 xl:grid-cols-2">
            <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
            <article v-else-if="shipments.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
            <article v-for="shipment in shipments" v-else :key="shipment.id" class="rounded-lg bg-[#ffffff] p-4 shadow-lg shadow-black/5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 gap-3">
                        <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.truck" /></span>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ shipment.order?.order_number }}</p>
                            <h3 class="mt-1 truncate text-lg font-black">{{ shipment.recipient_name }}</h3>
                            <p class="mt-1 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]">{{ shipment.recipient_phone }} · {{ shipment.recipient_district }}{{ shipment.recipient_area ? ', ' + shipment.recipient_area : '' }}</p>
                        </div>
                    </div>
                    <button class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-white px-4 text-sm font-black shadow-sm ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="button" @click="openShipment(shipment.id)"><FontAwesomeIcon :icon="icons.wand" />{{ text.delivery }}</button>
                </div>
                <div class="mt-4 grid gap-2 sm:grid-cols-4">
                    <div class="rounded-lg bg-white p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.status }}</p><span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-black capitalize ring-1" :class="badgeClass(shipment.status)">{{ shipment.status.replaceAll('_', ' ') }}</span></div>
                    <div class="rounded-lg bg-white p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.courier }}</p><p class="mt-2 truncate text-sm font-black">{{ shipment.courier_name || '-' }}</p></div>
                    <div class="rounded-lg bg-white p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.tracking }}</p><p class="mt-2 truncate text-sm font-black">{{ shipment.tracking_number || '-' }}</p></div>
                    <div class="rounded-lg bg-white p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.cod }}</p><p class="mt-2 text-sm font-black">{{ formatPrice(shipment.cod_amount) }}</p></div>
                </div>
            </article>
        </div>

        <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ shipments.length }} / {{ meta.total }}</p>
            <div class="flex items-center gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadShipments(meta.current_page - 1)">{{ text.prev }}</button><span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadShipments(meta.current_page + 1)">{{ text.next }}</button></div>
        </div>
    </section>

    <div v-if="selectedShipment" class="fixed inset-0 z-50 overflow-y-auto bg-black/45 p-3 backdrop-blur-sm">
        <div class="mx-auto my-6 max-w-2xl rounded-lg bg-[#ffffff] p-5 shadow-2xl shadow-black/30 ring-1 ring-black/10 dark:bg-[#171512] dark:ring-white/10">
            <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ selectedShipment.order?.order_number }}</p><h3 class="mt-1 text-2xl font-black">{{ selectedShipment.recipient_name }}</h3><p class="mt-1 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]">{{ selectedShipment.recipient_address }}</p></div><button class="grid size-10 place-items-center rounded-lg bg-white ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="button" @click="selectedShipment = null"><FontAwesomeIcon :icon="icons.xmark" /></button></div>
            <div v-if="formError" class="mt-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-black">{{ text.status }}<select v-model="form.status" class="h-12 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"><option v-for="status in shipmentStatuses" :key="status" :value="status">{{ status.replaceAll('_', ' ') }}</option></select></label>
                <label class="grid gap-2 text-sm font-black">{{ text.courier }}<input v-model="form.courier_name" class="h-12 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" list="courier-list"><datalist id="courier-list"><option v-for="courier in couriers" :key="courier" :value="courier" /></datalist></label>
                <label class="grid gap-2 text-sm font-black">{{ text.tracking }}<input v-model="form.tracking_number" class="h-12 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></label>
                <label class="grid gap-2 text-sm font-black">{{ text.estimated }}<input v-model="form.estimated_delivery_at" class="h-12 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="datetime-local"></label>
                <label class="grid gap-2 text-sm font-black sm:col-span-2">Tracking URL<input v-model="form.tracking_url" class="h-12 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="url"></label>
                <label class="grid gap-2 text-sm font-black">{{ text.eventLocation }}<input v-model="form.event_location" class="h-12 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></label>
                <label class="grid gap-2 text-sm font-black sm:col-span-2">{{ text.note }}<textarea v-model="form.admin_note" class="min-h-24 rounded-lg border-0 bg-white p-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></textarea><span v-if="form.status === 'failed'" class="text-xs font-bold text-[#8f2638] dark:text-[#ff8aa0]">{{ text.failedHelp }}</span></label>
            </div>
            <div v-if="selectedShipment.events?.length" class="mt-5 rounded-lg bg-white/70 p-4 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.statusHistory }}</p>
                <div class="mt-3 space-y-2">
                    <div v-for="event in selectedShipment.events" :key="event.id" class="grid gap-2 rounded-lg bg-[#ffffff] p-3 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10 sm:grid-cols-[auto_1fr_auto]">
                        <span class="w-fit rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="badgeClass(event.status)">{{ event.status.replaceAll('_', ' ') }}</span>
                        <div class="min-w-0"><p class="text-sm font-bold">{{ event.message || event.event_type.replaceAll('_', ' ') }}</p><p class="mt-1 text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ event.location || '-' }}</p></div>
                        <p class="text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ formatDate(event.occurred_at) }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end"><button class="h-11 rounded-lg border border-black/10 px-5 text-sm font-black dark:border-white/10" type="button" @click="selectedShipment = null">{{ text.close }}</button><button class="h-11 rounded-lg bg-[#171512] px-5 text-sm font-black text-white shadow-lg shadow-black/15 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="isSaving" @click="updateShipment">{{ isSaving ? text.saving : text.save }}</button></div>
        </div>
    </div>
</template>
