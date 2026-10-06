<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
const returns = ref([]);
const selectedReturn = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', status: '', page: 1 });
const form = reactive({ status: 'requested', refund_amount: 0, admin_note: '', pickup_courier_name: '', pickup_tracking_number: '', pickup_tracking_url: '', pickup_note: '' });
let searchTimer = null;

const statusOptions = ['requested', 'approved', 'received', 'refunded', 'rejected'];
const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'রিটার্ন অপারেশন',
    title: 'রিটার্ন ও রিফান্ড ম্যানেজমেন্ট',
    subtitle: 'রিটার্ন রিকোয়েস্ট, পণ্য/অর্ডার কনটেক্সট, refund amount এবং admin decision এক জায়গায় ম্যানেজ করুন।',
    search: 'রিটার্ন নম্বর, অর্ডার, কাস্টমার বা কারণ খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    returnNo: 'রিটার্ন',
    customer: 'কাস্টমার',
    product: 'পণ্য',
    order: 'অর্ডার',
    reason: 'কারণ',
    refund: 'Refund amount',
    status: 'Status',
    note: 'Admin note',
    pickup: 'পিকআপ ডেলিভারি',
    pickupCourier: 'Courier name',
    pickupTracking: 'Tracking number',
    pickupUrl: 'Tracking URL',
    pickupNote: 'Pickup note',
    pickupTimeline: 'Pickup timeline',
    noPickup: 'Approved করলে pickup shipment তৈরি হবে।',
    save: 'Update Return',
    saving: 'Updating...',
    empty: 'কোনো রিটার্ন রিকোয়েস্ট পাওয়া যায়নি।',
    failed: 'রিটার্ন রিকোয়েস্ট লোড/আপডেট করা যায়নি।',
    select: 'একটি রিটার্ন রিকোয়েস্ট সিলেক্ট করুন।',
    details: 'Request Details',
    timeline: 'Workflow Timeline',
    requested: 'Requested',
    approved: 'Approved',
    received: 'Received',
    refunded: 'Refunded',
    rejected: 'Rejected',
    prev: 'Prev',
    next: 'Next',
} : {
    eyebrow: 'Return Operations',
    title: 'Return & Refund Management',
    subtitle: 'Manage return requests, product/order context, refund amount, and admin decisions from one focused workspace.',
    search: 'Search return number, order, customer, or reason',
    allStatus: 'All Status',
    returnNo: 'Return',
    customer: 'Customer',
    product: 'Product',
    order: 'Order',
    reason: 'Reason',
    refund: 'Refund amount',
    status: 'Status',
    note: 'Admin note',
    pickup: 'Return Pickup',
    pickupCourier: 'Courier name',
    pickupTracking: 'Tracking number',
    pickupUrl: 'Tracking URL',
    pickupNote: 'Pickup note',
    pickupTimeline: 'Pickup timeline',
    noPickup: 'Approve the return to create a pickup shipment.',
    save: 'Update Return',
    saving: 'Updating...',
    empty: 'No return requests found.',
    failed: 'Could not load or update return requests.',
    select: 'Select a return request.',
    details: 'Request Details',
    timeline: 'Workflow Timeline',
    requested: 'Requested',
    approved: 'Approved',
    received: 'Received',
    refunded: 'Refunded',
    rejected: 'Rejected',
    prev: 'Prev',
    next: 'Next',
});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const toTaka = (amount) => Math.round(Number(amount || 0) / 100);
const toPaisa = (amount) => Math.round(Number(amount || 0) * 100);
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';
const statusLabel = (status) => text.value[status] ?? status;
const statusTone = (status) => ({ requested: 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]', approved: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-emerald-300', received: 'bg-blue-500/10 text-blue-700 ring-blue-500/20 dark:text-blue-300', refunded: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300', rejected: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-[#ff9bae]' }[status] ?? 'bg-slate-500/10 text-slate-600 ring-slate-500/20');

const selectReturn = (request) => {
    selectedReturn.value = request;
    form.status = request.status;
    form.refund_amount = toTaka(request.refund_amount);
    form.admin_note = request.admin_note ?? '';
    form.pickup_courier_name = request.pickup_shipment?.courier_name ?? '';
    form.pickup_tracking_number = request.pickup_shipment?.tracking_number ?? '';
    form.pickup_tracking_url = request.pickup_shipment?.tracking_url ?? '';
    form.pickup_note = request.pickup_shipment?.admin_note ?? '';
    formError.value = null;
    fieldErrors.value = {};
};

const loadReturns = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);

    try {
        const response = await fetch('/api/admin/return-requests?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.message ?? 'Request failed');
        }

        returns.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;

        if (selectedReturn.value) {
            const refreshed = returns.value.find((request) => request.id === selectedReturn.value.id);
            selectedReturn.value = refreshed ?? null;
            if (refreshed) selectReturn(refreshed);
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadReturns(1), 350);
};

const save = async () => {
    if (!selectedReturn.value) return;

    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch('/api/admin/return-requests/' + selectedReturn.value.id, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                status: form.status,
                refund_amount: toPaisa(form.refund_amount),
                admin_note: form.admin_note || null,
                pickup_courier_name: form.pickup_courier_name || null,
                pickup_tracking_number: form.pickup_tracking_number || null,
                pickup_tracking_url: form.pickup_tracking_url || null,
                pickup_note: form.pickup_note || null,
            }),
        });
        const payload = await response.json();

        if (!response.ok) {
            fieldErrors.value = payload.errors ?? {};
            throw new Error(payload?.message ?? 'Save failed');
        }

        selectReturn(payload.data);
        await loadReturns(filters.page);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

watch(() => filters.status, () => loadReturns(1));
onMounted(() => loadReturns());
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(360px,0.55fr)]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_180px]">
                    <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                        <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                        <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                    </label>
                    <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                        <option value="">{{ text.allStatus }}</option>
                        <option v-for="status in statusOptions" :key="status" :value="status">{{ statusLabel(status) }}</option>
                    </select>
                </div>
            </div>

            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="returns.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                <button v-for="request in returns" :key="request.id" class="rounded-lg bg-[#ffffff] p-4 text-left shadow-lg shadow-black/5 ring-1 ring-black/6 transition hover:bg-white dark:bg-black/20 dark:ring-white/10 dark:hover:bg-white/10" :class="selectedReturn?.id === request.id ? 'ring-2 ring-[#8f2638] dark:ring-[#9f243a]' : ''" type="button" @click="selectReturn(request)">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.returns" /></span>
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ request.return_number }}</p>
                                <h3 class="mt-1 truncate text-base font-black">{{ request.order_item?.product_name || request.order?.order_number }}</h3>
                                <p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ request.order?.customer_name || request.customer?.name }} · {{ request.order?.order_number }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 md:justify-end">
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="statusTone(request.status)">{{ statusLabel(request.status) }}</span>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-black uppercase text-[#4b5563] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#d1d5db] dark:ring-white/10">{{ formatPrice(request.refund_amount) }}</span>
                        </div>
                    </div>
                    <p class="mt-3 line-clamp-2 text-sm font-semibold leading-5 text-[#374151] dark:text-[#e5e7eb]">{{ request.reason }}</p>
                </button>
                </template>
            </div>

            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ returns.length }} / {{ meta.total }}</p>
                <div class="flex items-center gap-2">
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadReturns(meta.current_page - 1)">{{ text.prev }}</button>
                    <span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadReturns(meta.current_page + 1)">{{ text.next }}</button>
                </div>
            </div>
        </div>

        <aside class="grid content-start gap-5">
            <section class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
                <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.details }}</p>
                <div v-if="!selectedReturn" class="mt-4 rounded-lg bg-white/8 p-4 text-sm font-bold text-white/70 ring-1 ring-white/10">{{ text.select }}</div>
                <div v-else class="mt-4 grid gap-4">
                    <div>
                        <p class="text-xs font-black uppercase text-white/50">{{ text.returnNo }}</p>
                        <h3 class="mt-1 text-xl font-black">{{ selectedReturn.return_number }}</h3>
                        <p class="mt-1 text-sm font-semibold text-white/68">{{ formatDate(selectedReturn.created_at) }}</p>
                    </div>
                    <div class="grid gap-2 rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.product }}</p>
                        <p class="font-black">{{ selectedReturn.order_item?.product_name || '-' }}</p>
                        <p class="text-sm font-semibold text-white/65">{{ selectedReturn.order_item?.sku || selectedReturn.order_item?.variant_name || '-' }}</p>
                    </div>
                    <div class="grid gap-2 rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.order }}</p>
                        <p class="font-black">{{ selectedReturn.order?.order_number }}</p>
                        <p class="text-sm font-semibold text-white/65">{{ selectedReturn.order?.customer_name }} · {{ selectedReturn.order?.customer_phone }}</p>
                        <p class="text-sm font-semibold text-white/65">{{ selectedReturn.order?.shipping_district }} {{ selectedReturn.order?.shipping_area }}</p>
                    </div>
                    <p class="rounded-lg bg-white/8 p-4 text-sm font-semibold leading-6 text-white/76 ring-1 ring-white/10">{{ selectedReturn.reason }}</p>
                    <div class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.pickup }}</p>
                            <span v-if="selectedReturn.pickup_shipment" class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black uppercase text-white/80 ring-1 ring-white/10">{{ selectedReturn.pickup_shipment.status }}</span>
                        </div>
                        <div v-if="selectedReturn.pickup_shipment" class="mt-3 grid gap-3">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="rounded-lg bg-black/20 p-3 ring-1 ring-white/8">
                                    <p class="text-[10px] font-black uppercase text-white/45">{{ text.pickupCourier }}</p>
                                    <p class="mt-1 truncate font-black">{{ selectedReturn.pickup_shipment.courier_name || '-' }}</p>
                                </div>
                                <div class="rounded-lg bg-black/20 p-3 ring-1 ring-white/8">
                                    <p class="text-[10px] font-black uppercase text-white/45">{{ text.pickupTracking }}</p>
                                    <p class="mt-1 truncate font-black">{{ selectedReturn.pickup_shipment.tracking_number || '-' }}</p>
                                </div>
                            </div>
                            <div v-if="selectedReturn.pickup_shipment.events?.length" class="grid gap-2">
                                <p class="text-[10px] font-black uppercase text-white/45">{{ text.pickupTimeline }}</p>
                                <div v-for="event in selectedReturn.pickup_shipment.events.slice(0, 3)" :key="event.id" class="flex gap-3 rounded-lg bg-white/8 p-3 text-sm ring-1 ring-white/8">
                                    <span class="mt-1 size-2 shrink-0 rounded-full bg-[#9f243a]"></span>
                                    <div class="min-w-0">
                                        <p class="font-black">{{ event.message }}</p>
                                        <p class="mt-1 text-xs font-semibold text-white/55">{{ event.location || selectedReturn.order?.shipping_district }} · {{ formatDate(event.occurred_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm font-semibold leading-5 text-white/62">{{ text.noPickup }}</p>
                    </div>
                </div>
            </section>

            <form v-if="selectedReturn" class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10" @submit.prevent="save">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.timeline }}</p>
                <div v-if="formError" class="mt-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
                <div class="mt-4 grid gap-3">
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.status }}</span><select v-model="form.status" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><option v-for="status in statusOptions" :key="status" :value="status">{{ statusLabel(status) }}</option></select></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.refund }}</span><input v-model="form.refund_amount" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10" type="number" min="0"><small v-if="firstError('refund_amount')" class="text-[#8f2638]">{{ firstError('refund_amount') }}</small></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.note }}</span><textarea v-model="form.admin_note" class="min-h-28 rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea></label>
                    <div class="rounded-lg bg-[#ffffff] p-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                        <div class="flex items-center gap-3">
                            <span class="grid size-9 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.truck" /></span>
                            <p class="text-sm font-black text-[#171512] dark:text-white">{{ text.pickup }}</p>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="grid gap-1 text-sm font-bold"><span>{{ text.pickupCourier }}</span><input v-model="form.pickup_courier_name" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10" type="text"></label>
                            <label class="grid gap-1 text-sm font-bold"><span>{{ text.pickupTracking }}</span><input v-model="form.pickup_tracking_number" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10" type="text"></label>
                            <label class="grid gap-1 text-sm font-bold sm:col-span-2"><span>{{ text.pickupUrl }}</span><input v-model="form.pickup_tracking_url" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10" type="url"><small v-if="firstError('pickup_tracking_url')" class="text-[#8f2638]">{{ firstError('pickup_tracking_url') }}</small></label>
                            <label class="grid gap-1 text-sm font-bold sm:col-span-2"><span>{{ text.pickupNote }}</span><textarea v-model="form.pickup_note" class="min-h-24 rounded-lg border-0 bg-white px-3 py-2 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea></label>
                        </div>
                    </div>
                    <button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button>
                </div>
            </form>
        </aside>
    </section>
</template>
