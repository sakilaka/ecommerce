<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const isSaving = ref(false);
const isOrderSaving = ref(false);
const togglingSectionId = ref(null);
const orderDirty = ref(false);
const orderMessage = ref(null);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
const sections = ref([]);
const selectedSection = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', type: '', active: '', page: 1 });
const form = reactive({ key: '', type: 'hero', title: '', is_active: true, sort_order: 10, content_en: '', content_bn: '', settings: '', starts_at: '', ends_at: '' });
let searchTimer = null;

const types = ['announcement', 'hero', 'promo_banner', 'featured_collection', 'product_rail', 'category_rail', 'brand_rail', 'seo_links'];

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'হোমপেজ CMS',
    title: 'হোমপেজ সেকশন ম্যানেজমেন্ট',
    subtitle: 'Announcement, hero, campaign banner এবং merchandising section backend থেকে control করুন।',
    search: 'key বা title খুঁজুন',
    allTypes: 'সব টাইপ',
    allStatus: 'সব স্ট্যাটাস',
    active: 'Active',
    inactive: 'Inactive',
    createNew: 'নতুন সেকশন',
    editSection: 'Section Editor',
    key: 'Key',
    type: 'Type',
    label: 'Admin title',
    order: 'Sort order',
    saveOrder: 'অর্ডার সেভ',
    savingOrder: 'অর্ডার সেভ হচ্ছে...',
    orderSaved: 'Section order updated.',
    reorderHint: 'Arrow দিয়ে homepage section সাজান।',
    up: 'উপরে নিন',
    down: 'নিচে নিন',
    campaignPreview: 'Campaign Preview',
    activeField: 'Active section',
    contentEn: 'English content JSON',
    contentBn: 'Bangla content JSON',
    settings: 'Settings JSON',
    starts: 'Starts at',
    ends: 'Ends at',
    save: 'Save Section',
    saving: 'Saving...',
    empty: 'কোনো homepage section পাওয়া যায়নি।',
    failed: 'Homepage section লোড/সেভ করা যায়নি।',
    select: 'সেকশন সিলেক্ট করুন অথবা নতুন তৈরি করুন।',
    schedule: 'Schedule',
    preview: 'Content Preview',
    prev: 'Prev',
    next: 'Next',
} : {
    eyebrow: 'Homepage CMS',
    title: 'Homepage Section Management',
    subtitle: 'Control announcements, hero campaigns, promo banners, and merchandising sections from the backend.',
    search: 'Search key or title',
    allTypes: 'All Types',
    allStatus: 'All Status',
    active: 'Active',
    inactive: 'Inactive',
    createNew: 'New Section',
    editSection: 'Section Editor',
    key: 'Key',
    type: 'Type',
    label: 'Admin title',
    order: 'Sort order',
    saveOrder: 'Save order',
    savingOrder: 'Saving order...',
    orderSaved: 'Section order updated.',
    reorderHint: 'Use arrows to arrange homepage sections.',
    up: 'Move up',
    down: 'Move down',
    campaignPreview: 'Campaign Preview',
    activeField: 'Active section',
    contentEn: 'English content JSON',
    contentBn: 'Bangla content JSON',
    settings: 'Settings JSON',
    starts: 'Starts at',
    ends: 'Ends at',
    save: 'Save Section',
    saving: 'Saving...',
    empty: 'No homepage sections found.',
    failed: 'Could not load or save homepage sections.',
    select: 'Select a section or create a new one.',
    schedule: 'Schedule',
    preview: 'Content Preview',
    prev: 'Prev',
    next: 'Next',
});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const campaignPresets = [
    {
        key: 'eid-mega-sale-banner',
        title: 'Eid Mega Sale Banner',
        sort_order: 42,
        content: {
            en: { eyebrow: 'Eid Mega Sale', title: 'Save up to 50% on Eid essentials', subtitle: 'Fashion, beauty, smartphones, and gifts with nationwide delivery.', highlight: 'Up to 50% Off', cta: 'Shop Eid Deals' },
            bn: { eyebrow: 'ঈদ মেগা সেল', title: 'ঈদের প্রয়োজনীয় পণ্যে ৫০% পর্যন্ত ছাড়', subtitle: 'Fashion, beauty, smartphone এবং gifts সারাদেশে delivery.', highlight: '৫০% পর্যন্ত ছাড়', cta: 'ঈদ ডিল দেখুন' },
        },
        settings: { cta_url: '/products?campaign=eid-mega-sale', image: '/images/premium-hero.png', tone: 'maroon-primary', placement: 'home_after_best_sellers', coupon_code: 'EID50' },
    },
    {
        key: 'ramadan-daily-deals-banner',
        title: 'Ramadan Daily Deals Banner',
        sort_order: 44,
        content: {
            en: { eyebrow: 'Ramadan Deals', title: 'Daily savings for Ramadan shopping', subtitle: 'Groceries, modest fashion, home needs, and gifts in one campaign.', highlight: 'Fresh deals every day', cta: 'Explore Ramadan Deals' },
            bn: { eyebrow: 'রমজান ডিল', title: 'রমজান শপিংয়ে প্রতিদিন নতুন সেভিংস', subtitle: 'Groceries, modest fashion, home needs এবং gifts এক campaign-এ।', highlight: 'প্রতিদিন নতুন ডিল', cta: 'রমজান ডিল দেখুন' },
        },
        settings: { cta_url: '/products?campaign=ramadan-deals', image: '/images/category-collage.png', tone: 'emerald-primary', placement: 'home_midpage', coupon_code: 'RAMADAN10' },
    },
    {
        key: 'boishakh-style-refresh-banner',
        title: 'Boishakh Style Refresh Banner',
        sort_order: 46,
        content: {
            en: { eyebrow: 'Pohela Boishakh', title: 'Style refresh for the new year', subtitle: 'Saree, panjabi, beauty, accessories, and festive gifts.', highlight: 'Curated festive picks', cta: 'Shop Boishakh' },
            bn: { eyebrow: 'পহেলা বৈশাখ', title: 'নতুন বছরের জন্য স্টাইল রিফ্রেশ', subtitle: 'Saree, panjabi, beauty, accessories এবং festive gifts.', highlight: 'Curated festive picks', cta: 'বৈশাখ কালেকশন দেখুন' },
        },
        settings: { cta_url: '/products?campaign=pohela-boishakh', image: '/images/premium-hero.png', tone: 'cream-maroon', placement: 'home_editorial', coupon_code: 'BOISHAKH20' },
    },
];
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';
const statusClass = (section) => section.is_active ? 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:bg-[#9f243a]/20 dark:text-[#ff8aa0] dark:ring-[#9f243a]/30' : 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:bg-white/10 dark:text-slate-300 dark:ring-white/10';
const pretty = (value) => JSON.stringify(value ?? {}, null, 2);
const inputDate = (value) => value ? new Date(value).toISOString().slice(0, 16) : '';
const activePreviewContent = computed(() => parseJson(form.content_en) || selectedSection.value?.content?.en || {});
const activePreviewSettings = computed(() => parseJson(form.settings, {}) || selectedSection.value?.settings || {});
const canReorder = computed(() => !filters.q.trim() && !filters.type && filters.active === '' && meta.value.current_page === 1);

const resetForm = () => {
    selectedSection.value = null;
    form.key = '';
    form.type = 'hero';
    form.title = '';
    form.is_active = true;
    form.sort_order = 10;
    form.content_en = pretty({ title: 'New campaign', subtitle: 'Short customer-facing copy', cta: 'Shop Now' });
    form.content_bn = pretty({ title: 'নতুন ক্যাম্পেইন', subtitle: 'ছোট customer-facing copy', cta: 'শপ করুন' });
    form.settings = pretty({ cta_url: '/products' });
    form.starts_at = '';
    form.ends_at = '';
    fieldErrors.value = {};
    formError.value = null;
};

const selectSection = (section) => {
    selectedSection.value = section;
    form.key = section.key;
    form.type = section.type;
    form.title = section.title ?? '';
    form.is_active = Boolean(section.is_active);
    form.sort_order = section.sort_order;
    form.content_en = pretty(section.content?.en ?? {});
    form.content_bn = pretty(section.content?.bn ?? {});
    form.settings = pretty(section.settings ?? {});
    form.starts_at = inputDate(section.starts_at);
    form.ends_at = inputDate(section.ends_at);
    fieldErrors.value = {};
    formError.value = null;
};

const parseJson = (value, fallback = {}) => {
    try { return value.trim() ? JSON.parse(value) : fallback; } catch (jsonError) { return null; }
};

const loadSections = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;
    const params = new URLSearchParams({ per_page: '50', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.type) params.set('type', filters.type);
    if (filters.active !== '') params.set('active', filters.active);

    try {
        const response = await fetch('/api/admin/homepage-sections?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        sections.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
        orderDirty.value = false;
        orderMessage.value = null;
        if (selectedSection.value) {
            const refreshed = sections.value.find((section) => section.id === selectedSection.value.id);
            selectedSection.value = refreshed ?? null;
            if (refreshed) selectSection(refreshed);
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadSections(1), 350);
};

const save = async () => {
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    const en = parseJson(form.content_en);
    const bn = parseJson(form.content_bn);
    const settings = parseJson(form.settings, null);
    if (!en || !bn || settings === null) {
        formError.value = text.value.failed;
        fieldErrors.value = { content: ['JSON content is invalid.'] };
        isSaving.value = false;
        return;
    }

    try {
        const response = await fetch('/api/admin/homepage-sections' + (selectedSection.value ? '/' + selectedSection.value.id : ''), {
            method: selectedSection.value ? 'PATCH' : 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ key: form.key, type: form.type, title: form.title || null, is_active: form.is_active, sort_order: Number(form.sort_order), content: { en, bn }, settings, starts_at: form.starts_at || null, ends_at: form.ends_at || null }),
        });
        const payload = await response.json();
        if (!response.ok) { fieldErrors.value = payload.errors ?? {}; throw new Error(payload?.message ?? 'Save failed'); }
        selectSection(payload.data);
        await loadSections(filters.page);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

const toggleSection = async (section) => {
    togglingSectionId.value = section.id;
    error.value = null;

    try {
        const response = await fetch('/api/admin/homepage-sections/' + section.id, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({
                key: section.key,
                type: section.type,
                title: section.title,
                is_active: !section.is_active,
                sort_order: section.sort_order,
                content: section.content,
                settings: section.settings ?? {},
                starts_at: section.starts_at,
                ends_at: section.ends_at,
            }),
        });
        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload?.message ?? 'Toggle failed');
        }

        const updated = payload.data;
        sections.value = sections.value.map((item) => item.id === updated.id ? updated : item);
        if (selectedSection.value?.id === updated.id) {
            selectSection(updated);
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        togglingSectionId.value = null;
    }
};

const moveSection = (index, direction) => {
    if (!canReorder.value) return;
    const target = index + direction;
    if (target < 0 || target >= sections.value.length) return;

    const orderedSections = [...sections.value];
    const [section] = orderedSections.splice(index, 1);
    orderedSections.splice(target, 0, section);
    sections.value = orderedSections;
    orderDirty.value = true;
    orderMessage.value = null;

    if (selectedSection.value) {
        const refreshed = sections.value.find((item) => item.id === selectedSection.value.id);
        if (refreshed) selectSection(refreshed);
    }
};

const saveOrder = async () => {
    if (!canReorder.value || !orderDirty.value) return;

    isOrderSaving.value = true;
    error.value = null;
    orderMessage.value = null;

    try {
        const response = await fetch('/api/admin/homepage-sections/reorder', {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify({ sections: sections.value.map((section, index) => ({ id: section.id, sort_order: (index + 1) * 10 })) }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Reorder failed');
        sections.value = payload.data ?? sections.value;
        orderDirty.value = false;
        orderMessage.value = text.value.orderSaved;
        if (selectedSection.value) {
            const refreshed = sections.value.find((section) => section.id === selectedSection.value.id);
            if (refreshed) selectSection(refreshed);
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isOrderSaving.value = false;
    }
};

watch(() => [filters.type, filters.active], () => loadSections(1));
onMounted(() => { resetForm(); loadSections(); });
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(380px,0.55fr)]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                    </div>
                    <button class="h-11 rounded-lg bg-[#171512] px-5 text-sm font-black text-white shadow-lg shadow-black/10 dark:bg-[#9f243a] dark:text-white" type="button" @click="resetForm">{{ text.createNew }}</button>
                </div>
                <div class="mt-5 grid gap-3 xl:grid-cols-[minmax(0,1fr)_180px_150px]">
                    <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                        <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                        <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                    </label>
                    <select v-model="filters.type" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ text.allTypes }}</option><option v-for="type in types" :key="type" :value="type">{{ type }}</option></select>
                    <select v-model="filters.active" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ text.allStatus }}</option><option value="1">{{ text.active }}</option><option value="0">{{ text.inactive }}</option></select>
                </div>
            </div>

            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>
            <div class="mx-4 mt-4 flex flex-col gap-3 rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]"><span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-[#8f2638] shadow-sm dark:bg-white/10 dark:text-[#ff8aa0]"><FontAwesomeIcon :icon="icons.grip" /></span><span>{{ text.reorderHint }}</span></div>
                <div class="flex items-center gap-2"><span v-if="orderMessage" class="text-xs font-black text-[#8f2638] dark:text-emerald-300">{{ orderMessage }}</span><button class="h-10 rounded-lg bg-[#171512] px-4 text-xs font-black text-white shadow-lg shadow-black/10 disabled:cursor-not-allowed disabled:opacity-45 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="!canReorder || !orderDirty || isOrderSaving" @click="saveOrder">{{ isOrderSaving ? text.savingOrder : text.saveOrder }}</button></div>
            </div>
            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="sections.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                    <article v-for="(section, index) in sections" :key="section.id" class="rounded-lg bg-[#ffffff] p-3 shadow-lg shadow-black/5 ring-1 ring-black/6 transition hover:bg-white dark:bg-black/20 dark:ring-white/10 dark:hover:bg-white/10" :class="selectedSection?.id === section.id ? 'ring-2 ring-[#8f2638] dark:ring-[#9f243a]' : ''">
                        <div class="grid gap-3 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-start">
                            <div class="flex gap-2 sm:flex-col">
                                <button class="grid size-9 place-items-center rounded-lg bg-white text-xs text-[#171512] shadow-sm ring-1 ring-black/6 disabled:cursor-not-allowed disabled:opacity-35 dark:bg-white/10 dark:text-white dark:ring-white/10" type="button" :title="text.up" :disabled="!canReorder || index === 0 || isOrderSaving" @click="moveSection(index, -1)"><FontAwesomeIcon :icon="icons.chevronDown" class="rotate-180" /></button>
                                <button class="grid size-9 place-items-center rounded-lg bg-white text-xs text-[#171512] shadow-sm ring-1 ring-black/6 disabled:cursor-not-allowed disabled:opacity-35 dark:bg-white/10 dark:text-white dark:ring-white/10" type="button" :title="text.down" :disabled="!canReorder || index === sections.length - 1 || isOrderSaving" @click="moveSection(index, 1)"><FontAwesomeIcon :icon="icons.chevronDown" /></button>
                            </div>
                            <button class="min-w-0 text-left" type="button" @click="selectSection(section)">
                                <div class="flex min-w-0 gap-3"><span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.wand" /></span><div class="min-w-0"><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ section.type }} · #{{ section.sort_order }}</p><h3 class="mt-1 truncate text-base font-black">{{ section.title || section.key }}</h3><p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ section.key }}</p></div></div>
                                <p class="mt-3 line-clamp-2 text-sm font-semibold leading-5 text-[#374151] dark:text-[#e5e7eb]">{{ section.content?.[locale]?.title || section.content?.en?.title || section.content?.[locale]?.message || section.content?.en?.message }}</p>
                            </button>
                            <div class="flex shrink-0 flex-col gap-2 sm:min-w-40 sm:items-end">
                                <button class="group inline-flex h-10 w-full items-center justify-between gap-3 rounded-lg px-2.5 text-xs font-black uppercase ring-1 transition disabled:cursor-wait disabled:opacity-60 sm:w-36" :class="section.is_active ? 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 hover:bg-[#8f2638]/15 dark:bg-[#9f243a]/20 dark:text-[#ff8aa0] dark:ring-[#9f243a]/30' : 'bg-slate-500/10 text-slate-600 ring-slate-500/20 hover:bg-slate-500/15 dark:bg-white/10 dark:text-slate-300 dark:ring-white/10'" type="button" :disabled="togglingSectionId === section.id" @click.stop="toggleSection(section)">
                                    <span class="grid h-5 w-9 shrink-0 place-items-center rounded-full p-0.5 transition" :class="section.is_active ? 'bg-[#8f2638] dark:bg-[#9f243a]' : 'bg-slate-400 dark:bg-slate-600'">
                                        <span class="size-4 rounded-full bg-white shadow-sm transition" :class="section.is_active ? 'translate-x-2' : '-translate-x-2'"></span>
                                    </span>
                                    <span class="min-w-0 truncate">{{ togglingSectionId === section.id ? text.toggling : (section.is_active ? text.visible : text.hidden) }}</span>
                                </button>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-3 py-1.5 text-[10px] font-black uppercase ring-1" :class="statusClass(section)">{{ section.is_active ? text.active : text.inactive }}</span>
                                    <span class="grid size-8 place-items-center rounded-full bg-[#f6f6f6] text-[11px] font-black text-[#8f2638] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#ff8aa0] dark:ring-white/10">{{ index + 1 }}</span>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ sections.length }} / {{ meta.total }}</p><div class="flex items-center gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadSections(meta.current_page - 1)">{{ text.prev }}</button><span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadSections(meta.current_page + 1)">{{ text.next }}</button></div></div>
        </div>

        <aside class="grid content-start gap-5">
            <form class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10" @submit.prevent="save">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.editSection }}</p>
                <div v-if="formError" class="mt-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
                <div class="mt-4 grid gap-3">
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.key }}</span><input v-model="form.key" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><small v-if="firstError('key')" class="text-[#8f2638]">{{ firstError('key') }}</small></label>
                    <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.type }}</span><select v-model="form.type" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><option v-for="type in types" :key="type" :value="type">{{ type }}</option></select></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.order }}</span><input v-model="form.sort_order" type="number" min="0" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></label></div>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.label }}</span><input v-model="form.title" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></label>
                    <label class="flex min-h-11 items-center justify-between gap-3 rounded-lg bg-[#f6f6f6] px-3 text-sm font-bold ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><span>{{ text.activeField }}</span><input v-model="form.is_active" class="size-4 accent-[#8f2638]" type="checkbox"></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.contentEn }}</span><textarea v-model="form.content_en" class="min-h-32 font-mono text-xs rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.contentBn }}</span><textarea v-model="form.content_bn" class="min-h-32 font-mono text-xs rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea><small v-if="firstError('content')" class="text-[#8f2638]">{{ firstError('content') }}</small></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.settings }}</span><textarea v-model="form.settings" class="min-h-24 font-mono text-xs rounded-lg border-0 bg-[#f6f6f6] px-3 py-2 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></textarea></label>
                    <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.starts }}</span><input v-model="form.starts_at" type="datetime-local" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 text-xs outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.ends }}</span><input v-model="form.ends_at" type="datetime-local" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 text-xs outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"></label></div>
                    <button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button>
                </div>
            </form>
            <section class="overflow-hidden rounded-lg bg-[#171512] text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
                <div class="relative min-h-72 p-5">
                    <img v-if="activePreviewSettings.image" class="absolute inset-0 size-full object-cover opacity-35" :src="activePreviewSettings.image" alt="">
                    <div class="absolute inset-0 bg-gradient-to-r from-[#171512] via-[#171512]/88 to-[#8f2638]/45"></div>
                    <div class="relative max-w-sm">
                        <p class="inline-flex rounded-full bg-[#9f243a] px-3 py-1 text-[10px] font-black uppercase text-white">{{ activePreviewContent.eyebrow || text.campaignPreview }}</p>
                        <h3 class="mt-5 text-2xl font-black leading-tight">{{ activePreviewContent.title || selectedSection?.title || text.preview }}</h3>
                        <p class="mt-3 text-sm font-semibold leading-6 text-white/72">{{ activePreviewContent.subtitle || activePreviewContent.message || text.select }}</p>
                        <p v-if="activePreviewContent.highlight" class="mt-4 text-xl font-black text-[#ff8aa0]">{{ activePreviewContent.highlight }}</p>
                        <a class="mt-5 inline-flex h-11 items-center justify-center rounded-lg bg-white px-4 text-sm font-black text-[#171512]" :href="activePreviewSettings.cta_url || '/products'">{{ activePreviewContent.cta || 'Shop Now' }}</a>
                    </div>
                </div>
                <div class="grid gap-2 border-t border-white/10 p-4 text-xs font-bold text-white/60">
                    <p>{{ text.schedule }}: {{ form.starts_at || formatDate(selectedSection?.starts_at) }} - {{ form.ends_at || formatDate(selectedSection?.ends_at) }}</p>
                    <p>URL: {{ activePreviewSettings.cta_url || '/products' }}</p>
                    <p v-if="activePreviewSettings.coupon_code">Coupon: {{ activePreviewSettings.coupon_code }}</p>
                </div>
            </section>
        </aside>
    </section>
</template>
