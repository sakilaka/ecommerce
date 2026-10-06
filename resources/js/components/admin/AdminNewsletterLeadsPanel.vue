<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const leads = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 12 });
const filters = reactive({ q: '', status: '', source: '', page: 1 });
const isLoading = ref(false);
const isSaving = ref(false);
const selectedLead = ref(null);
const error = ref(null);
let searchTimer = null;

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'অফার লিডস',
    title: 'Newsletter & Offer Leads',
    subtitle: 'Homepage থেকে আসা email/SMS opt-in, source, interest এবং consent status ট্র্যাক করুন।',
    search: 'Email, phone বা interest খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    active: 'অ্যাক্টিভ',
    inactive: 'ইনঅ্যাক্টিভ',
    allSources: 'সব সোর্স',
    homepage: 'Homepage',
    email: 'Email',
    phone: 'Phone',
    interest: 'Interest',
    source: 'Source',
    consent: 'Consent',
    note: 'Admin note',
    save: 'Save Status',
    saving: 'Saving...',
    empty: 'কোনো lead পাওয়া যায়নি।',
    failed: 'Newsletter leads লোড/আপডেট করা যায়নি।',
    select: 'Lead select করলে details ও status update দেখা যাবে।',
    details: 'Lead Details',
    subscribed: 'Subscribed',
    created: 'Created',
    showing: 'দেখানো হচ্ছে',
    of: 'মোট',
    previous: 'আগের',
    next: 'পরের',
} : {
    eyebrow: 'Offer Leads',
    title: 'Newsletter & Offer Leads',
    subtitle: 'Track email/SMS opt-ins, source, interest, and consent status captured from the homepage.',
    search: 'Search email, phone, or interest',
    allStatus: 'All Status',
    active: 'Active',
    inactive: 'Inactive',
    allSources: 'All Sources',
    homepage: 'Homepage',
    email: 'Email',
    phone: 'Phone',
    interest: 'Interest',
    source: 'Source',
    consent: 'Consent',
    note: 'Admin note',
    save: 'Save Status',
    saving: 'Saving...',
    empty: 'No leads found.',
    failed: 'Could not load or update newsletter leads.',
    select: 'Select a lead to review details and update status.',
    details: 'Lead Details',
    subscribed: 'Subscribed',
    created: 'Created',
    showing: 'Showing',
    of: 'of',
    previous: 'Previous',
    next: 'Next',
});

const statusOptions = computed(() => [
    { value: '', label: text.value.allStatus },
    { value: 'active', label: text.value.active },
    { value: 'inactive', label: text.value.inactive },
]);

const sourceOptions = computed(() => [
    { value: '', label: text.value.allSources },
    { value: 'homepage', label: text.value.homepage },
]);

const stats = computed(() => {
    const active = leads.value.filter((lead) => lead.is_active).length;
    const email = leads.value.filter((lead) => lead.email).length;
    const sms = leads.value.filter((lead) => lead.phone).length;

    return [
        { label: text.value.active, value: formatNumber(active), icon: icons.gift, tone: 'bg-[#8f2638] text-white' },
        { label: text.value.email, value: formatNumber(email), icon: icons.user, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
        { label: text.value.phone, value: formatNumber(sms), icon: icons.cart, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
    ];
});

const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';

const leadTitle = (lead) => lead.email || lead.phone || 'Lead #' + lead.id;

const loadLeads = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);
    if (filters.source) params.set('source', filters.source);

    try {
        const response = await fetch('/api/admin/newsletter-leads?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const data = await response.json();
        leads.value = data.data ?? [];
        meta.value = data.meta ?? meta.value;
        if (selectedLead.value) {
            selectedLead.value = leads.value.find((lead) => lead.id === selectedLead.value.id) ?? selectedLead.value;
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadLeads(1), 350);
};

const selectLead = (lead) => {
    selectedLead.value = { ...lead };
};

const saveLead = async () => {
    if (!selectedLead.value) return;
    isSaving.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/admin/newsletter-leads/' + selectedLead.value.id, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ is_active: selectedLead.value.is_active, admin_note: selectedLead.value.admin_note || null }),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data?.message ?? 'Save failed');
        selectedLead.value = data.data;
        await loadLeads(filters.page);
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

watch(() => [filters.status, filters.source], () => loadLeads(1));
onMounted(() => loadLeads());
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.42fr)]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#9f243a]">{{ text.eyebrow }}</p>
                <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                <div class="mt-5 grid gap-2 sm:grid-cols-3">
                    <article v-for="card in stats" :key="card.label" class="rounded-lg p-4 shadow-lg shadow-black/5 ring-1 ring-black/5" :class="card.tone"><div class="flex items-center justify-between gap-2 text-[10px] font-black uppercase opacity-75"><span>{{ card.label }}</span><FontAwesomeIcon :icon="card.icon" /></div><p class="mt-2 text-xl font-black">{{ card.value }}</p></article>
                </div>
                <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_170px_170px]">
                    <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#9f243a]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch"></label>
                    <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                    <select v-model="filters.source" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in sourceOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                </div>
            </div>

            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#9f243a]">{{ error }}</div>

            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="leads.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                    <button v-for="lead in leads" :key="lead.id" class="rounded-lg bg-[#ffffff] p-4 text-left shadow-lg shadow-black/5 ring-1 ring-black/6 transition hover:bg-white dark:bg-black/20 dark:ring-white/10 dark:hover:bg-white/10" :class="selectedLead?.id === lead.id ? 'ring-2 ring-[#8f2638] dark:ring-[#9f243a]' : ''" type="button" @click="selectLead(lead)">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0"><p class="truncate text-base font-black">{{ leadTitle(lead) }}</p><p class="mt-1 truncate text-xs font-bold uppercase text-[#8f2638] dark:text-[#9f243a]">{{ lead.interest || '-' }}</p></div>
                            <span class="w-max rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="lead.is_active ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300' : 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300'">{{ lead.is_active ? text.active : text.inactive }}</span>
                        </div>
                        <div class="mt-3 grid gap-2 text-xs font-semibold text-[#374151] dark:text-[#e5e7eb] sm:grid-cols-3"><span>{{ text.phone }}: {{ lead.phone || '-' }}</span><span>{{ text.source }}: {{ lead.source }}</span><span>{{ text.created }}: {{ formatDate(lead.created_at) }}</span></div>
                    </button>
                </template>
            </div>

            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.showing }} {{ formatNumber(leads.length) }} {{ text.of }} {{ formatNumber(meta.total) }}</p><div class="flex gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page <= 1 || isLoading" @click="loadLeads(meta.current_page - 1)">{{ text.previous }}</button><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page >= meta.last_page || isLoading" @click="loadLeads(meta.current_page + 1)">{{ text.next }}</button></div></div>
        </div>

        <aside class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
            <p class="text-xs font-black uppercase text-[#9f243a]">{{ text.details }}</p>
            <div v-if="!selectedLead" class="mt-4 rounded-lg bg-white/8 p-4 text-sm font-bold text-white/70 ring-1 ring-white/10">{{ text.select }}</div>
            <form v-else class="mt-4 grid gap-4" @submit.prevent="saveLead">
                <div class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.email }}</p><p class="mt-1 break-words text-sm font-black">{{ selectedLead.email || '-' }}</p></div>
                <div class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.phone }}</p><p class="mt-1 text-sm font-black">{{ selectedLead.phone || '-' }}</p></div>
                <div class="grid grid-cols-2 gap-3"><div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.consent }}</p><p class="mt-1 text-sm font-black">{{ formatDate(selectedLead.consented_at) }}</p></div><div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.subscribed }}</p><p class="mt-1 text-sm font-black">{{ formatDate(selectedLead.last_subscribed_at) }}</p></div></div>
                <label class="flex items-center gap-2 text-sm font-black"><input v-model="selectedLead.is_active" class="size-4 accent-[#9f243a]" type="checkbox">{{ text.active }}</label>
                <label class="grid gap-2 text-sm font-black"><span>{{ text.note }}</span><textarea v-model="selectedLead.admin_note" class="min-h-28 rounded-lg border border-white/10 bg-white/8 px-3 py-2 text-sm font-semibold outline-none focus:border-[#9f243a]"></textarea></label>
                <button class="h-11 rounded-lg bg-[#9f243a] px-4 text-sm font-black text-white disabled:opacity-60" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button>
            </form>
        </aside>
    </section>
</template>
