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
const reviews = ref([]);
const selectedReview = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', status: '', rating: '', verified: '', page: 1 });
const form = reactive({ status: 'pending', is_verified_purchase: false, admin_note: '' });
let searchTimer = null;

const statuses = ['pending', 'approved', 'rejected', 'hidden'];
const ratings = [5, 4, 3, 2, 1];

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'রিভিউ মডারেশন',
    title: 'প্রোডাক্ট রিভিউ ম্যানেজমেন্ট',
    subtitle: 'রেটিং, customer context, verified purchase signal এবং moderation decision এক জায়গায় নিয়ন্ত্রণ করুন।',
    search: 'পণ্য, কাস্টমার, ফোন বা রিভিউ খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    allRatings: 'সব রেটিং',
    allVerified: 'সব রিভিউ',
    verifiedOnly: 'Verified only',
    unverifiedOnly: 'Unverified only',
    review: 'Review',
    product: 'Product',
    customer: 'Customer',
    rating: 'Rating',
    status: 'Status',
    note: 'Admin note',
    verified: 'Verified Purchase',
    helpful: 'Helpful',
    save: 'Update Review',
    saving: 'Updating...',
    empty: 'কোনো রিভিউ পাওয়া যায়নি।',
    failed: 'রিভিউ লোড/আপডেট করা যায়নি।',
    select: 'একটি রিভিউ সিলেক্ট করুন।',
    details: 'Review Details',
    moderation: 'Moderation Decision',
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    hidden: 'Hidden',
    prev: 'Prev',
    next: 'Next',
} : {
    eyebrow: 'Review Moderation',
    title: 'Product Review Management',
    subtitle: 'Control ratings, customer context, verified purchase signals, and moderation decisions from one admin workspace.',
    search: 'Search product, customer, phone, or review',
    allStatus: 'All Status',
    allRatings: 'All Ratings',
    allVerified: 'All Reviews',
    verifiedOnly: 'Verified only',
    unverifiedOnly: 'Unverified only',
    review: 'Review',
    product: 'Product',
    customer: 'Customer',
    rating: 'Rating',
    status: 'Status',
    note: 'Admin note',
    verified: 'Verified Purchase',
    helpful: 'Helpful',
    save: 'Update Review',
    saving: 'Updating...',
    empty: 'No reviews found.',
    failed: 'Could not load or update reviews.',
    select: 'Select a review.',
    details: 'Review Details',
    moderation: 'Moderation Decision',
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    hidden: 'Hidden',
    prev: 'Prev',
    next: 'Next',
});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';
const statusLabel = (status) => text.value[status] ?? status;
const statusTone = (status) => ({ pending: 'bg-[#9f243a]/12 text-[#8f2638] ring-[#9f243a]/20 dark:text-[#ff8aa0]', approved: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-emerald-300', rejected: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-[#ff9bae]', hidden: 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300' }[status] ?? 'bg-slate-500/10 text-slate-600 ring-slate-500/20');

const selectReview = (review) => {
    selectedReview.value = review;
    form.status = review.status;
    form.is_verified_purchase = Boolean(review.is_verified_purchase);
    form.admin_note = review.admin_note ?? '';
    fieldErrors.value = {};
    formError.value = null;
};

const loadReviews = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);
    if (filters.rating) params.set('rating', filters.rating);
    if (filters.verified !== '') params.set('verified', filters.verified);

    try {
        const response = await fetch('/api/admin/reviews?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        reviews.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
        if (selectedReview.value) {
            const refreshed = reviews.value.find((review) => review.id === selectedReview.value.id);
            selectedReview.value = refreshed ?? null;
            if (refreshed) selectReview(refreshed);
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadReviews(1), 350);
};

const save = async () => {
    if (!selectedReview.value) return;
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch('/api/admin/reviews/' + selectedReview.value.id, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ status: form.status, is_verified_purchase: form.is_verified_purchase, admin_note: form.admin_note || null }),
        });
        const payload = await response.json();
        if (!response.ok) { fieldErrors.value = payload.errors ?? {}; throw new Error(payload?.message ?? 'Save failed'); }
        selectReview(payload.data);
        await loadReviews(filters.page);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

watch(() => [filters.status, filters.rating, filters.verified], () => loadReviews(1));
onMounted(() => loadReviews());
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(360px,0.55fr)]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                <div class="mt-5 grid gap-3 xl:grid-cols-[minmax(0,1fr)_150px_140px_150px]">
                    <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                        <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                        <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                    </label>
                    <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ text.allStatus }}</option><option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option></select>
                    <select v-model="filters.rating" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ text.allRatings }}</option><option v-for="rating in ratings" :key="rating" :value="String(rating)">{{ rating }}★</option></select>
                    <select v-model="filters.verified" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ text.allVerified }}</option><option value="1">{{ text.verifiedOnly }}</option><option value="0">{{ text.unverifiedOnly }}</option></select>
                </div>
            </div>

            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="reviews.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                    <button v-for="review in reviews" :key="review.id" class="rounded-lg bg-[#ffffff] p-4 text-left shadow-lg shadow-black/5 ring-1 ring-black/6 transition hover:bg-white dark:bg-black/20 dark:ring-white/10 dark:hover:bg-white/10" :class="selectedReview?.id === review.id ? 'ring-2 ring-[#8f2638] dark:ring-[#9f243a]' : ''" type="button" @click="selectReview(review)">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="flex min-w-0 gap-3">
                                <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.star" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ review.rating }}★ · {{ review.product?.sku || review.product?.name }}</p>
                                    <h3 class="mt-1 truncate text-base font-black">{{ review.title || review.product?.name }}</h3>
                                    <p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ review.customer?.name || review.customer_name }} · {{ formatDate(review.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2 md:justify-end">
                                <span v-if="review.is_verified_purchase" class="rounded-full bg-[#8f2638]/10 px-2.5 py-1 text-[10px] font-black uppercase text-[#8f2638] ring-1 ring-[#8f2638]/20 dark:text-emerald-300">{{ text.verified }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="statusTone(review.status)">{{ statusLabel(review.status) }}</span>
                            </div>
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm font-semibold leading-5 text-[#374151] dark:text-[#e5e7eb]">{{ review.body }}</p>
                    </button>
                </template>
            </div>

            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ reviews.length }} / {{ meta.total }}</p>
                <div class="flex items-center gap-2">
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadReviews(meta.current_page - 1)">{{ text.prev }}</button>
                    <span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadReviews(meta.current_page + 1)">{{ text.next }}</button>
                </div>
            </div>
        </div>

        <aside class="grid content-start gap-5">
            <section class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
                <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.details }}</p>
                <div v-if="!selectedReview" class="mt-4 rounded-lg bg-white/8 p-4 text-sm font-bold text-white/70 ring-1 ring-white/10">{{ text.select }}</div>
                <div v-else class="mt-4 grid gap-4">
                    <div>
                        <div class="flex items-center gap-1 text-[#ff8aa0]"><FontAwesomeIcon v-for="index in selectedReview.rating" :key="index" :icon="icons.star" /></div>
                        <h3 class="mt-2 text-xl font-black leading-tight">{{ selectedReview.title || selectedReview.product?.name }}</h3>
                        <p class="mt-1 text-sm font-semibold text-white/68">{{ formatDate(selectedReview.created_at) }}</p>
                    </div>
                    <div class="grid gap-2 rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.product }}</p>
                        <p class="font-black">{{ selectedReview.product?.name || '-' }}</p>
                        <p class="text-sm font-semibold text-white/65">{{ selectedReview.product?.sku || '-' }}</p>
                    </div>
                    <div class="grid gap-2 rounded-lg bg-white/8 p-4 ring-1 ring-white/10">
                        <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.customer }}</p>
                        <p class="font-black">{{ selectedReview.customer?.name || selectedReview.customer_name || '-' }}</p>
                        <p class="text-sm font-semibold text-white/65">{{ selectedReview.customer?.phone || selectedReview.customer_phone || selectedReview.customer?.email || selectedReview.customer_email || '-' }}</p>
                    </div>
                    <p class="rounded-lg bg-white/8 p-4 text-sm font-semibold leading-6 text-white/76 ring-1 ring-white/10">{{ selectedReview.body }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.helpful }}</p><p class="mt-1 text-lg font-black">{{ selectedReview.helpful_count }}</p></div>
                        <div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.verified }}</p><p class="mt-1 text-sm font-black">{{ selectedReview.is_verified_purchase ? 'Yes' : 'No' }}</p></div>
                    </div>
                </div>
            </section>

            <form v-if="selectedReview" class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10" @submit.prevent="save">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.moderation }}</p>
                <div v-if="formError" class="mt-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
                <div class="mt-4 grid gap-3">
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.status }}</span><select v-model="form.status" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option></select><small v-if="firstError('status')" class="text-[#8f2638]">{{ firstError('status') }}</small></label>
                    <label class="flex min-h-11 items-center justify-between gap-3 rounded-lg bg-[#f6f6f6] px-3 text-sm font-bold ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><span>{{ text.verified }}</span><input v-model="form.is_verified_purchase" class="size-4 accent-[#8f2638]" type="checkbox"></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.note }}</span><textarea v-model="form.admin_note" class="min-h-28 rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea><small v-if="firstError('admin_note')" class="text-[#8f2638]">{{ firstError('admin_note') }}</small></label>
                    <button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button>
                </div>
            </form>
        </aside>
    </section>
</template>
