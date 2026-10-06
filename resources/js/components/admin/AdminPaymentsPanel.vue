<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const isSaving = ref(false);
const isRefunding = ref(false);
const isReviewingProof = ref(false);
const isLoadingDetail = ref(false);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
const payments = ref([]);
const selectedPayment = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', status: '', method: '', page: 1 });
const form = reactive({ status: 'pending', method: 'cod', amount: 0, gateway: '', gateway_transaction_id: '' });
const refundForm = reactive({ amount: 0, method: 'manual', reference: '', reason: '', admin_note: '' });
const proofReviewForm = reactive({ admin_note: '' });
let searchTimer = null;

const statuses = ['pending', 'unpaid', 'paid', 'failed', 'refunded'];
const methods = ['cod', 'sslcommerz', 'bkash', 'nagad', 'card'];
const refundMethods = ['manual', 'bkash', 'nagad', 'bank', 'card', 'sslcommerz'];
const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'পেমেন্ট অপারেশন', title: 'পেমেন্ট ম্যানেজমেন্ট', subtitle: 'COD, bKash, Nagad, SSLCommerz এবং card transaction এক জায়গায় reconcile করুন।', exportCsv: 'CSV Export', search: 'অর্ডার, কাস্টমার বা transaction ID খুঁজুন', allStatus: 'সব স্ট্যাটাস', allMethod: 'সব মেথড', payment: 'Payment', order: 'Order', customer: 'Customer', method: 'Method', status: 'Status', amount: 'Amount', gateway: 'Gateway', transaction: 'Transaction ID', paidAt: 'Paid at', details: 'Payment Details', refund: 'Refund', refundAmount: 'Refund amount', refundMethod: 'Refund method', refundReason: 'Refund reason', refundReference: 'Refund reference', refundNote: 'Refund note', recordRefund: 'Record Refund', recordingRefund: 'Recording...', refunded: 'Refunded', remaining: 'Remaining', refundHistory: 'Refund history', noRefunds: 'No refunds recorded.', resultPage: 'Result page', gatewaySession: 'Gateway session', callback: 'Callback event', validation: 'Validation', bankTransaction: 'Bank transaction', manualTransaction: 'Manual transaction', manualSender: 'Sender phone', manualStatus: 'Proof status', proofReview: 'Manual proof review', proofReviewNote: 'Review note', approveProof: 'Approve proof', rejectProof: 'Reject proof', reviewingProof: 'Reviewing...', proofAllowed: 'Proof submitted', risk: 'Gateway note', paidAllowed: 'Can mark paid', failAllowed: 'Can mark failed', refundAllowed: 'Refund eligible', manualWarning: 'Manual updates should match gateway/COD proof before saving.', openResult: 'Open result', timeline: 'পেমেন্ট টাইমলাইন', noTimeline: 'এখনো কোনো payment activity নেই।', activityBy: 'By', rawPayload: 'Raw gateway payload', save: 'Update Payment', saving: 'Updating...', empty: 'কোনো পেমেন্ট পাওয়া যায়নি।', failed: 'পেমেন্ট লোড/আপডেট করা যায়নি।', select: 'একটি পেমেন্ট সিলেক্ট করুন।', loadingDetail: 'ডিটেইল লোড হচ্ছে...', prev: 'Prev', next: 'Next'
} : {
    eyebrow: 'Payment Operations', title: 'Payment Management', subtitle: 'Reconcile COD, bKash, Nagad, SSLCommerz, and card transactions from one admin workspace.', exportCsv: 'Export CSV', search: 'Search order, customer, or transaction ID', allStatus: 'All Status', allMethod: 'All Methods', payment: 'Payment', order: 'Order', customer: 'Customer', method: 'Method', status: 'Status', amount: 'Amount', gateway: 'Gateway', transaction: 'Transaction ID', paidAt: 'Paid at', details: 'Payment Details', resultPage: 'Result page', gatewaySession: 'Gateway session', callback: 'Callback event', validation: 'Validation', bankTransaction: 'Bank transaction', manualTransaction: 'Manual transaction', manualSender: 'Sender phone', manualStatus: 'Proof status', proofReview: 'Manual proof review', proofReviewNote: 'Review note', approveProof: 'Approve proof', rejectProof: 'Reject proof', reviewingProof: 'Reviewing...', proofAllowed: 'Proof submitted', risk: 'Gateway note', paidAllowed: 'Can mark paid', failAllowed: 'Can mark failed', refundAllowed: 'Refund eligible', manualWarning: 'Manual updates should match gateway/COD proof before saving.', openResult: 'Open result', timeline: 'Payment Timeline', noTimeline: 'No payment activity yet.', activityBy: 'By', rawPayload: 'Raw gateway payload', save: 'Update Payment', saving: 'Updating...', empty: 'No payments found.', failed: 'Could not load or update payments.', select: 'Select a payment.', loadingDetail: 'Loading payment detail...', prev: 'Prev', next: 'Next'
});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const toTaka = (amount) => Math.round(Number(amount || 0) / 100);
const toPaisa = (amount) => Math.round(Number(amount || 0) * 100);
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';
const badgeClass = (status) => ({ paid: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300', pending: 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]', unpaid: 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300', failed: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-[#ff9bae]', refunded: 'bg-blue-500/10 text-blue-700 ring-blue-500/20 dark:text-blue-300' }[status] ?? 'bg-slate-500/10 text-slate-600 ring-slate-500/20');

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);
    if (filters.method) params.set('method', filters.method);

    const query = params.toString();

    return '/api/admin/payments/export' + (query ? '?' + query : '');
});

const timelineLabel = (item) => locale.value === 'bn' ? item.label_bn || item.label : item.label;
const timelineDescription = (item) => locale.value === 'bn' ? item.description_bn || item.description : item.description;

const gatewayRows = computed(() => {
    const summary = selectedPayment.value?.gateway_summary ?? {};

    return [
        { label: text.value.gatewaySession, value: summary.sessionkey },
        { label: text.value.callback, value: summary.callback_event },
        { label: text.value.validation, value: summary.validation_status },
        { label: text.value.bankTransaction, value: summary.bank_transaction_id },
        { label: text.value.manualTransaction, value: summary.manual_transaction_id },
        { label: text.value.manualSender, value: summary.manual_sender_phone },
        { label: text.value.manualStatus, value: summary.manual_status },
        { label: text.value.risk, value: summary.risk_message },
        { label: text.value.paidAt, value: formatDate(summary.received_at) },
    ].filter((row) => row.value && row.value !== '-');
});

const actionChips = computed(() => {
    const actions = selectedPayment.value?.actions ?? {};

    return [
        { label: text.value.paidAllowed, active: actions.can_mark_paid },
        { label: text.value.failAllowed, active: actions.can_mark_failed },
        { label: text.value.refundAllowed, active: actions.can_refund },
        { label: text.value.proofAllowed, active: actions.can_verify_manual_proof },
    ].filter((action) => action.active);
});

const compactPayload = computed(() => {
    if (!selectedPayment.value?.gateway_payload) {
        return '';
    }

    return JSON.stringify(selectedPayment.value.gateway_payload, null, 2);
});

const applyPayment = (payment) => {
    selectedPayment.value = payment;
    form.status = payment.status;
    form.method = payment.method;
    form.amount = toTaka(payment.amount);
    form.gateway = payment.gateway ?? '';
    form.gateway_transaction_id = payment.gateway_transaction_id ?? '';
    refundForm.amount = Math.round(Number(payment.refund_summary?.remaining_amount || 0) / 100);
    refundForm.method = 'manual';
    refundForm.reference = '';
    refundForm.reason = '';
    refundForm.admin_note = '';
    proofReviewForm.admin_note = '';
    fieldErrors.value = {};
    formError.value = null;
};

const selectPayment = async (payment) => {
    applyPayment(payment);
    isLoadingDetail.value = true;

    try {
        const response = await fetch('/api/admin/payments/' + payment.id, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        applyPayment(payload.data);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isLoadingDetail.value = false;
    }
};

const loadPayments = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;
    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);
    if (filters.method) params.set('method', filters.method);

    try {
        const response = await fetch('/api/admin/payments?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        payments.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
        if (selectedPayment.value) {
            const refreshed = payments.value.find((payment) => payment.id === selectedPayment.value.id);
            if (refreshed) {
                applyPayment({ ...refreshed, timeline: selectedPayment.value.timeline });
            } else {
                selectedPayment.value = null;
            }
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadPayments(1), 350);
};

const save = async () => {
    if (!selectedPayment.value) return;
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch('/api/admin/payments/' + selectedPayment.value.id, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ status: form.status, method: form.method, amount: toPaisa(form.amount), gateway: form.gateway || null, gateway_transaction_id: form.gateway_transaction_id || null }),
        });
        const payload = await response.json();
        if (!response.ok) { fieldErrors.value = payload.errors ?? {}; throw new Error(payload?.message ?? 'Save failed'); }
        applyPayment(payload.data);
        await loadPayments(filters.page);
        if (selectedPayment.value) await selectPayment(selectedPayment.value);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

const recordRefund = async () => {
    if (!selectedPayment.value) return;
    isRefunding.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch('/api/admin/payments/' + selectedPayment.value.id + '/refunds', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({
                amount: toPaisa(refundForm.amount),
                method: refundForm.method,
                reference: refundForm.reference || null,
                reason: refundForm.reason,
                admin_note: refundForm.admin_note || null,
            }),
        });
        const payload = await response.json();
        if (!response.ok) { fieldErrors.value = payload.errors ?? {}; throw new Error(payload?.message ?? 'Refund failed'); }
        applyPayment(payload.data);
        await loadPayments(filters.page);
        if (selectedPayment.value) await selectPayment(selectedPayment.value);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isRefunding.value = false;
    }
};

const reviewManualProof = async (decision) => {
    if (!selectedPayment.value) return;
    isReviewingProof.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch('/api/admin/payments/' + selectedPayment.value.id + '/manual-proof', {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ decision, admin_note: proofReviewForm.admin_note || null }),
        });
        const payload = await response.json();
        if (!response.ok) { fieldErrors.value = payload.errors ?? {}; throw new Error(payload?.message ?? 'Proof review failed'); }
        applyPayment(payload.data);
        await loadPayments(filters.page);
        if (selectedPayment.value) await selectPayment(selectedPayment.value);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isReviewingProof.value = false;
    }
};

watch(() => [filters.status, filters.method], () => loadPayments(1));
onMounted(() => loadPayments());
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(360px,0.55fr)]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                <div class="mt-5 grid gap-3 xl:grid-cols-[minmax(0,1fr)_170px_170px_150px]">
                    <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch"></label>
                    <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ text.allStatus }}</option><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select>
                    <select v-model="filters.method" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ text.allMethod }}</option><option v-for="method in methods" :key="method" :value="method">{{ method }}</option></select>
                    <a class="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-[#171512] px-4 text-sm font-black text-white shadow-lg shadow-black/10 ring-1 ring-black/8 transition hover:bg-[#27272a] dark:bg-[#9f243a] dark:text-white dark:ring-[#9f243a]/25" :href="exportUrl"><FontAwesomeIcon :icon="icons.chart" />{{ text.exportCsv }}</a>
                </div>
            </div>
            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>
            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="payments.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                    <button v-for="payment in payments" :key="payment.id" class="rounded-lg bg-[#ffffff] p-4 text-left shadow-lg shadow-black/5 ring-1 ring-black/6 transition hover:bg-white dark:bg-black/20 dark:ring-white/10 dark:hover:bg-white/10" :class="selectedPayment?.id === payment.id ? 'ring-2 ring-[#8f2638] dark:ring-[#9f243a]' : ''" type="button" @click="selectPayment(payment)">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"><div class="flex min-w-0 gap-3"><span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.cart" /></span><div class="min-w-0"><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ payment.method }} · {{ payment.gateway || 'manual' }}</p><h3 class="mt-1 truncate text-base font-black">{{ payment.order?.order_number || 'Order #' + payment.order?.id }}</h3><p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ payment.order?.customer_name }} · {{ payment.order?.customer_phone }}</p></div></div><div class="shrink-0 md:text-right"><p class="text-lg font-black">{{ formatPrice(payment.amount) }}</p><span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="badgeClass(payment.status)">{{ payment.status }}</span></div></div>
                    </button>
                </template>
            </div>
            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ payments.length }} / {{ meta.total }}</p><div class="flex items-center gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadPayments(meta.current_page - 1)">{{ text.prev }}</button><span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadPayments(meta.current_page + 1)">{{ text.next }}</button></div></div>
        </div>
        <aside class="grid content-start gap-5">
            <section class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
                <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.details }}</p>
                <div v-if="!selectedPayment" class="mt-4 rounded-lg bg-white/8 p-4 text-sm font-bold text-white/70 ring-1 ring-white/10">{{ text.select }}</div>
                <div v-else class="mt-4 grid gap-4">
                    <div v-if="isLoadingDetail" class="rounded-lg bg-white/8 p-3 text-xs font-black text-[#ff8aa0] ring-1 ring-white/10">{{ text.loadingDetail }}</div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h3 class="truncate text-xl font-black">{{ selectedPayment.order?.order_number }}</h3>
                            <p class="mt-1 text-sm font-semibold text-white/68">{{ selectedPayment.order?.customer_name }} · {{ selectedPayment.order?.customer_phone }}</p>
                        </div>
                        <span class="w-fit rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="badgeClass(selectedPayment.status)">{{ selectedPayment.status }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.amount }}</p><p class="mt-1 text-lg font-black">{{ formatPrice(selectedPayment.amount) }}</p></div>
                        <div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.paidAt }}</p><p class="mt-1 text-sm font-black">{{ formatDate(selectedPayment.paid_at) }}</p></div>
                    </div>
                    <div class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <p class="text-[10px] font-black uppercase text-white/45">{{ text.transaction }}</p>
                        <p class="mt-1 break-all text-sm font-black">{{ selectedPayment.gateway_transaction_id || '-' }}</p>
                    </div>
                    <div v-if="actionChips.length" class="flex flex-wrap gap-2">
                        <span v-for="chip in actionChips" :key="chip.label" class="rounded-full bg-[#9f243a]/14 px-3 py-1 text-[10px] font-black uppercase text-[#ff8aa0] ring-1 ring-[#9f243a]/24">{{ chip.label }}</span>
                    </div>
                    <div v-if="gatewayRows.length" class="grid gap-2 rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <div v-for="row in gatewayRows" :key="row.label" class="flex items-start justify-between gap-4 border-b border-white/10 pb-2 last:border-b-0 last:pb-0">
                            <span class="text-xs font-bold text-white/50">{{ row.label }}</span>
                            <span class="max-w-48 break-words text-right text-xs font-black text-white">{{ row.value }}</span>
                        </div>
                    </div>
                    <a v-if="selectedPayment.result_url" class="inline-flex h-10 w-fit items-center justify-center gap-2 rounded-lg bg-white px-4 text-sm font-black text-[#171512] shadow-lg shadow-black/20" :href="selectedPayment.result_url" target="_blank" rel="noreferrer">{{ text.openResult }} <FontAwesomeIcon :icon="icons.arrow" /></a>
                    <div class="grid gap-3 rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.timeline }}</p>
                        <div v-if="selectedPayment.timeline?.length" class="relative grid gap-3 before:absolute before:bottom-2 before:left-3 before:top-2 before:w-px before:bg-white/12">
                            <article v-for="item in selectedPayment.timeline" :key="item.type + item.created_at" class="relative grid grid-cols-[1.5rem_minmax(0,1fr)] gap-3">
                                <span class="mt-1 grid size-6 place-items-center rounded-full bg-[#9f243a] text-[9px] font-black text-white ring-4 ring-[#171512] dark:ring-[#27272a]">{{ item.type === 'payment.created' ? 'C' : item.type.includes('refund') ? 'R' : 'P' }}</span>
                                <div class="rounded-lg bg-black/16 p-3 ring-1 ring-white/10">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                        <p class="text-sm font-black">{{ timelineLabel(item) }}</p>
                                        <span class="text-[10px] font-bold uppercase text-white/45">{{ formatDate(item.created_at) }}</span>
                                    </div>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-white/62">{{ timelineDescription(item) }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-[10px] font-black uppercase text-white/50">
                                        <span v-if="item.status" class="rounded-full bg-white/8 px-2 py-1 ring-1 ring-white/10">{{ item.status }}</span>
                                        <span v-if="item.amount" class="rounded-full bg-white/8 px-2 py-1 ring-1 ring-white/10">{{ formatPrice(item.amount) }}</span>
                                        <span v-if="item.actor" class="rounded-full bg-white/8 px-2 py-1 ring-1 ring-white/10">{{ text.activityBy }} {{ item.actor.name }}</span>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <p v-else class="text-sm font-semibold text-white/55">{{ text.noTimeline }}</p>
                    </div>
                    <div class="grid gap-3 rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.refundHistory }}</p>
                            <p class="text-xs font-black text-white/70">{{ text.remaining }} {{ formatPrice(selectedPayment.refund_summary?.remaining_amount || 0) }}</p>
                        </div>
                        <div v-if="selectedPayment.refunds?.length" class="grid gap-2">
                            <div v-for="refund in selectedPayment.refunds" :key="refund.id" class="rounded-lg bg-black/16 p-3 text-xs ring-1 ring-white/10">
                                <div class="flex items-start justify-between gap-3"><span class="font-black">{{ formatPrice(refund.amount) }}</span><span class="font-black uppercase text-white/50">{{ refund.method }}</span></div>
                                <p class="mt-1 text-white/60">{{ refund.reason }}</p>
                                <p v-if="refund.reference" class="mt-1 break-all font-bold text-[#ff8aa0]">{{ refund.reference }}</p>
                            </div>
                        </div>
                        <p v-else class="text-sm font-semibold text-white/55">{{ text.noRefunds }}</p>
                    </div>
                    <details v-if="compactPayload" class="rounded-lg bg-black/18 p-4 ring-1 ring-white/10">
                        <summary class="cursor-pointer text-xs font-black uppercase text-[#ff8aa0]">{{ text.rawPayload }}</summary>
                        <pre class="mt-3 max-h-52 overflow-auto whitespace-pre-wrap break-words text-xs leading-5 text-white/70">{{ compactPayload }}</pre>
                    </details>
                </div>
            </section>
            <form v-if="selectedPayment" class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10" @submit.prevent="save"><p class="rounded-lg bg-[#f6f6f6] px-4 py-3 text-xs font-bold leading-5 text-[#4b5563] ring-1 ring-black/6 dark:bg-black/20 dark:text-[#d1d5db] dark:ring-white/10">{{ text.manualWarning }}</p><div v-if="formError" class="mt-3 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div><div class="mt-4 grid gap-3"><label class="grid gap-1 text-sm font-bold"><span>{{ text.status }}</span><select v-model="form.status" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.method }}</span><select v-model="form.method" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><option v-for="method in methods" :key="method" :value="method">{{ method }}</option></select></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.amount }}</span><input v-model="form.amount" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10" type="number" min="0"><small v-if="firstError('amount')" class="text-[#8f2638]">{{ firstError('amount') }}</small></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.gateway }}</span><input v-model="form.gateway" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.transaction }}</span><input v-model="form.gateway_transaction_id" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><small v-if="firstError('gateway_transaction_id')" class="text-[#8f2638]">{{ firstError('gateway_transaction_id') }}</small></label><button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button></div></form>
            <section v-if="selectedPayment?.actions?.can_verify_manual_proof" class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.proofReview }}</p>
                <label class="mt-4 grid gap-1 text-sm font-bold"><span>{{ text.proofReviewNote }}</span><textarea v-model="proofReviewForm.admin_note" class="min-h-20 rounded-lg border-0 bg-[#f6f6f6] px-3 py-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea></label>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <button class="h-11 rounded-lg bg-emerald-600 px-4 text-sm font-black text-white shadow-lg shadow-emerald-600/15 disabled:opacity-60" type="button" :disabled="isReviewingProof" @click="reviewManualProof('approved')">{{ isReviewingProof ? text.reviewingProof : text.approveProof }}</button>
                    <button class="h-11 rounded-lg bg-[#8f2638] px-4 text-sm font-black text-white shadow-lg shadow-[#8f2638]/15 disabled:opacity-60" type="button" :disabled="isReviewingProof" @click="reviewManualProof('rejected')">{{ isReviewingProof ? text.reviewingProof : text.rejectProof }}</button>
                </div>
            </section>
            <form v-if="selectedPayment?.actions?.can_refund" class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10" @submit.prevent="recordRefund">
                <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.refund }}</p>
                <div class="mt-4 grid gap-3">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.refundAmount }}</span><input v-model="refundForm.amount" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10" type="number" min="1"><small v-if="firstError('amount')" class="text-[#8f2638]">{{ firstError('amount') }}</small></label>
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.refundMethod }}</span><select v-model="refundForm.method" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><option v-for="method in refundMethods" :key="method" :value="method">{{ method }}</option></select></label>
                    </div>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.refundReference }}</span><input v-model="refundForm.reference" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.refundReason }}</span><textarea v-model="refundForm.reason" class="min-h-24 rounded-lg border-0 bg-[#f6f6f6] px-3 py-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea><small v-if="firstError('reason')" class="text-[#8f2638]">{{ firstError('reason') }}</small></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.refundNote }}</span><textarea v-model="refundForm.admin_note" class="min-h-20 rounded-lg border-0 bg-[#f6f6f6] px-3 py-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea></label>
                    <button class="h-11 rounded-lg bg-[#171512] px-5 text-sm font-black text-white shadow-lg shadow-black/12 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isRefunding">{{ isRefunding ? text.recordingRefund : text.recordRefund }}</button>
                </div>
            </form>
        </aside>
    </section>
</template>
