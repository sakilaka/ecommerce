<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const searches = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 12 });
const filters = reactive({ q: '', status: '', page: 1 });
const isLoading = ref(false);
const isSaving = ref(false);
const isFormOpen = ref(false);
const editingId = ref(null);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
let searchTimer = null;

const emptyForm = () => ({
    label_en: '',
    label_bn: '',
    keyword: '',
    href: '/products?q=',
    sort_order: 0,
    click_count: 0,
    is_active: true,
});

const form = reactive(emptyForm());

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'SEO ও সার্চ',
    title: 'Popular Searches Management',
    subtitle: 'Homepage SEO internal links এবং high-intent search shortcut backend থেকে নিয়ন্ত্রণ করুন।',
    add: 'নতুন সার্চ লিংক',
    edit: 'সার্চ লিংক আপডেট',
    search: 'Label, keyword বা URL খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    active: 'অ্যাক্টিভ',
    inactive: 'ইনঅ্যাক্টিভ',
    labelEn: 'English label',
    labelBn: 'Bangla label',
    keyword: 'Keyword',
    href: 'Target URL',
    sort: 'Sort order',
    clicks: 'Clicks',
    status: 'Status',
    save: 'সেভ করুন',
    saving: 'সেভ হচ্ছে...',
    close: 'বন্ধ করুন',
    empty: 'কোনো popular search পাওয়া যায়নি।',
    unavailable: 'Popular search লোড/সেভ করা যায়নি।',
    showing: 'দেখানো হচ্ছে',
    of: 'মোট',
    previous: 'আগের',
    next: 'পরের',
    hint: 'Target URL অবশ্যই /products দিয়ে শুরু হবে।',
} : {
    eyebrow: 'SEO & Search',
    title: 'Popular Searches Management',
    subtitle: 'Control homepage SEO internal links and high-intent search shortcuts from the admin dashboard.',
    add: 'Add Search Link',
    edit: 'Update Search Link',
    search: 'Search label, keyword, or URL',
    allStatus: 'All Status',
    active: 'Active',
    inactive: 'Inactive',
    labelEn: 'English label',
    labelBn: 'Bangla label',
    keyword: 'Keyword',
    href: 'Target URL',
    sort: 'Sort order',
    clicks: 'Clicks',
    status: 'Status',
    save: 'Save',
    saving: 'Saving...',
    close: 'Close',
    empty: 'No popular searches found.',
    unavailable: 'Could not load or save popular searches.',
    showing: 'Showing',
    of: 'of',
    previous: 'Previous',
    next: 'Next',
    hint: 'Target URL must start with /products.',
});

const statusOptions = computed(() => [
    { value: '', label: text.value.allStatus },
    { value: 'active', label: text.value.active },
    { value: 'inactive', label: text.value.inactive },
]);

const modalTitle = computed(() => editingId.value ? text.value.edit : text.value.add);
const formatNumber = (amount) => Number(amount || 0).toLocaleString('en-BD');
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';

const stats = computed(() => {
    const activeCount = searches.value.filter((search) => search.is_active).length;
    const clicks = searches.value.reduce((sum, search) => sum + Number(search.click_count || 0), 0);

    return [
        { label: text.value.active, value: formatNumber(activeCount), icon: icons.search, tone: 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' },
        { label: text.value.clicks, value: formatNumber(clicks), icon: icons.chart, tone: 'bg-[#8f2638] text-white' },
        { label: text.value.of, value: formatNumber(meta.value.total), icon: icons.tags, tone: 'bg-white text-[#171512] dark:bg-white/10 dark:text-white' },
    ];
});

const resetForm = () => {
    Object.assign(form, emptyForm());
    editingId.value = null;
    fieldErrors.value = {};
    formError.value = null;
};

const fillForm = (search) => {
    resetForm();
    editingId.value = search.id;
    form.label_en = search.label_en ?? '';
    form.label_bn = search.label_bn ?? '';
    form.keyword = search.keyword ?? '';
    form.href = search.href ?? '/products?q=';
    form.sort_order = search.sort_order ?? 0;
    form.click_count = search.click_count ?? 0;
    form.is_active = Boolean(search.is_active);
};

const payload = () => ({
    label_en: form.label_en.trim(),
    label_bn: form.label_bn.trim() || null,
    keyword: form.keyword.trim(),
    href: form.href.trim(),
    sort_order: Number(form.sort_order),
    click_count: Number(form.click_count || 0),
    is_active: form.is_active,
});

const loadSearches = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);

    try {
        const response = await fetch('/api/admin/popular-searches?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const data = await response.json();
        searches.value = data.data ?? [];
        meta.value = data.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.unavailable;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadSearches(1), 350);
};

const openCreate = () => {
    resetForm();
    isFormOpen.value = true;
};

const openEdit = (search) => {
    fillForm(search);
    isFormOpen.value = true;
};

const saveSearch = async () => {
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch(editingId.value ? '/api/admin/popular-searches/' + editingId.value : '/api/admin/popular-searches', {
            method: editingId.value ? 'PATCH' : 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify(payload()),
        });
        const data = await response.json();
        if (!response.ok) {
            fieldErrors.value = data.errors ?? {};
            throw new Error('Save failed');
        }
        isFormOpen.value = false;
        await loadSearches(editingId.value ? filters.page : 1);
    } catch (requestError) {
        formError.value = text.value.unavailable;
    } finally {
        isSaving.value = false;
    }
};

watch(() => filters.status, () => loadSearches(1));
onMounted(() => loadSearches());
</script>

<template>
    <section class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                    <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                </div>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#171512] px-4 text-sm font-black text-white shadow-lg shadow-black/12 dark:bg-[#9f243a] dark:text-white" type="button" @click="openCreate"><FontAwesomeIcon :icon="icons.plus" />{{ text.add }}</button>
            </div>
            <div class="mt-5 grid gap-2 sm:grid-cols-3">
                <article v-for="card in stats" :key="card.label" class="rounded-lg p-4 shadow-lg shadow-black/5 ring-1 ring-black/5" :class="card.tone"><div class="flex items-center justify-between gap-2 text-[10px] font-black uppercase opacity-75"><span>{{ card.label }}</span><FontAwesomeIcon :icon="card.icon" /></div><p class="mt-2 text-xl font-black">{{ card.value }}</p></article>
            </div>
            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch"></label>
                <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

        <div class="grid gap-3 p-5 lg:grid-cols-2 xl:grid-cols-3">
            <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
            <article v-else-if="searches.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
            <template v-else>
                <article v-for="search in searches" :key="search.id" class="rounded-lg bg-[#ffffff] p-4 shadow-lg shadow-black/5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ search.keyword }}</p><h3 class="mt-1 truncate text-lg font-black">{{ search.label_en }}</h3><p class="mt-1 truncate text-sm font-semibold text-[#374151] dark:text-[#e5e7eb]">{{ search.label_bn || '-' }}</p></div><button class="grid size-10 shrink-0 place-items-center rounded-lg bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white" type="button" @click="openEdit(search)"><FontAwesomeIcon :icon="icons.wand" /></button></div>
                    <a class="mt-4 block truncate rounded-lg bg-white px-3 py-2 text-xs font-black text-[#8f2638] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#ff8aa0] dark:ring-white/10" :href="search.href">{{ search.href }}</a>
                    <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-black uppercase"><span class="rounded-full px-2.5 py-1 ring-1" :class="search.is_active ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300' : 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300'">{{ search.is_active ? text.active : text.inactive }}</span><span class="rounded-full bg-white px-2.5 py-1 text-[#4b5563] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#d1d5db] dark:ring-white/10">{{ text.sort }} {{ search.sort_order }}</span><span class="rounded-full bg-white px-2.5 py-1 text-[#4b5563] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#d1d5db] dark:ring-white/10">{{ formatNumber(search.click_count) }} {{ text.clicks }}</span></div>
                </article>
            </template>
        </div>

        <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ text.showing }} {{ formatNumber(searches.length) }} {{ text.of }} {{ formatNumber(meta.total) }}</p><div class="flex gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page <= 1 || isLoading" @click="loadSearches(meta.current_page - 1)">{{ text.previous }}</button><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-40 dark:border-white/10" type="button" :disabled="meta.current_page >= meta.last_page || isLoading" @click="loadSearches(meta.current_page + 1)">{{ text.next }}</button></div></div>
    </section>

    <div v-if="isFormOpen" class="fixed inset-0 z-50 overflow-y-auto bg-black/45 p-3 backdrop-blur-sm sm:p-6">
        <form class="mx-auto max-w-2xl rounded-lg bg-[#ffffff] shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10" @submit.prevent="saveSearch">
            <div class="flex items-center justify-between gap-3 border-b border-black/8 p-5 dark:border-white/10"><div><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p><h3 class="mt-1 text-xl font-black">{{ modalTitle }}</h3></div><button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="isFormOpen = false"><FontAwesomeIcon :icon="icons.xmark" /></button></div>
            <div class="grid gap-4 p-5">
                <div v-if="formError" class="rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
                <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.labelEn }}</span><input v-model="form.label_en" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" required><small v-if="firstError('label_en')" class="text-[#8f2638]">{{ firstError('label_en') }}</small></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.labelBn }}</span><input v-model="form.label_bn" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></label></div>
                <label class="grid gap-1 text-sm font-bold"><span>{{ text.keyword }}</span><input v-model="form.keyword" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" required><small v-if="firstError('keyword')" class="text-[#8f2638]">{{ firstError('keyword') }}</small></label>
                <label class="grid gap-1 text-sm font-bold"><span>{{ text.href }}</span><input v-model="form.href" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" required><small class="text-xs font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ firstError('href') || text.hint }}</small></label>
                <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.sort }}</span><input v-model="form.sort_order" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.clicks }}</span><input v-model="form.click_count" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"></label></div>
                <label class="flex items-center gap-2 text-sm font-black"><input v-model="form.is_active" class="size-4 accent-[#8f2638]" type="checkbox">{{ text.active }}</label>
            </div>
            <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:justify-end"><button class="h-11 rounded-lg border border-black/10 px-5 text-sm font-black dark:border-white/10" type="button" @click="isFormOpen = false">{{ text.close }}</button><button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button></div>
        </form>
    </div>
</template>
