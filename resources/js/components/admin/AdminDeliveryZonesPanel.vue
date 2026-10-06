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
const zones = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', active: '', cod: '', page: 1 });
const isFormOpen = ref(false);
const editingId = ref(null);
let searchTimer = null;

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'ডেলিভারি অপারেশন',
    title: 'ডেলিভারি জোন ম্যানেজমেন্ট',
    subtitle: 'বাংলাদেশের জেলা/এরিয়া অনুযায়ী ডেলিভারি চার্জ, COD, এবং estimated delivery window নিয়ন্ত্রণ করুন।',
    add: 'নতুন জোন',
    edit: 'জোন আপডেট',
    search: 'জেলা বা এরিয়া খুঁজুন',
    allStatus: 'সব স্ট্যাটাস',
    activeOnly: 'শুধু অ্যাক্টিভ',
    inactiveOnly: 'ইনঅ্যাক্টিভ',
    allCod: 'সব COD',
    codOnly: 'COD চালু',
    noCod: 'COD বন্ধ',
    district: 'জেলা',
    area: 'এরিয়া',
    charge: 'ডেলিভারি চার্জ',
    codCharge: 'COD চার্জ',
    minDays: 'মিন দিন',
    maxDays: 'ম্যাক্স দিন',
    codAvailable: 'COD Available',
    active: 'Active',
    inactive: 'Inactive',
    deliveryWindow: 'Delivery window',
    takaHint: 'টাকার হিসাবে লিখুন, সিস্টেম পয়সায় সেভ করবে।',
    save: 'Save Zone',
    saving: 'Saving...',
    close: 'Close',
    empty: 'কোনো ডেলিভারি জোন পাওয়া যায়নি।',
    failed: 'ডেলিভারি জোন লোড/সেভ করা যায়নি।',
    prev: 'Prev',
    next: 'Next',
    days: 'days',
} : {
    eyebrow: 'Delivery Operations',
    title: 'Delivery Zone Management',
    subtitle: 'Control Bangladesh district and area delivery charges, COD availability, and estimated delivery windows.',
    add: 'Add Zone',
    edit: 'Update Zone',
    search: 'Search district or area',
    allStatus: 'All Status',
    activeOnly: 'Active only',
    inactiveOnly: 'Inactive',
    allCod: 'All COD',
    codOnly: 'COD enabled',
    noCod: 'COD disabled',
    district: 'District',
    area: 'Area',
    charge: 'Delivery charge',
    codCharge: 'COD charge',
    minDays: 'Min days',
    maxDays: 'Max days',
    codAvailable: 'COD Available',
    active: 'Active',
    inactive: 'Inactive',
    deliveryWindow: 'Delivery window',
    takaHint: 'Enter values in taka; the system stores them as paisa.',
    save: 'Save Zone',
    saving: 'Saving...',
    close: 'Close',
    empty: 'No delivery zones found.',
    failed: 'Could not load or save delivery zones.',
    prev: 'Prev',
    next: 'Next',
    days: 'days',
});

const emptyForm = () => ({
    district: '',
    area: '',
    delivery_charge: 80,
    cod_charge: 0,
    estimated_min_days: 1,
    estimated_max_days: 3,
    cod_available: true,
    is_active: true,
});

const form = reactive(emptyForm());
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const toTaka = (amount) => Math.round(Number(amount || 0) / 100);
const toPaisa = (amount) => Math.round(Number(amount || 0) * 100);
const formatPrice = (amount) => '৳' + Math.round(Number(amount || 0) / 100).toLocaleString('en-BD');
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';
const modalTitle = computed(() => editingId.value ? text.value.edit : text.value.add);

const resetForm = () => {
    Object.keys(form).forEach((key) => delete form[key]);
    Object.assign(form, emptyForm());
    editingId.value = null;
    fieldErrors.value = {};
    formError.value = null;
};

const loadZones = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.active !== '') params.set('active', filters.active);
    if (filters.cod !== '') params.set('cod', filters.cod);

    try {
        const response = await fetch('/api/admin/delivery-zones?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.message ?? 'Request failed');
        }

        zones.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadZones(1), 350);
};

const openCreate = () => {
    resetForm();
    isFormOpen.value = true;
};

const openEdit = (zone) => {
    resetForm();
    editingId.value = zone.id;
    Object.assign(form, {
        district: zone.district,
        area: zone.area ?? '',
        delivery_charge: toTaka(zone.delivery_charge),
        cod_charge: toTaka(zone.cod_charge),
        estimated_min_days: zone.estimated_min_days,
        estimated_max_days: zone.estimated_max_days,
        cod_available: Boolean(zone.cod_available),
        is_active: Boolean(zone.is_active),
    });
    isFormOpen.value = true;
};

const payload = () => ({
    district: form.district,
    area: form.area || null,
    delivery_charge: toPaisa(form.delivery_charge),
    cod_charge: toPaisa(form.cod_charge),
    estimated_min_days: Number(form.estimated_min_days || 0),
    estimated_max_days: Number(form.estimated_max_days || 0),
    cod_available: Boolean(form.cod_available),
    is_active: Boolean(form.is_active),
});

const save = async () => {
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch(editingId.value ? '/api/admin/delivery-zones/' + editingId.value : '/api/admin/delivery-zones', {
            method: editingId.value ? 'PATCH' : 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload()),
        });
        const data = await response.json();

        if (!response.ok) {
            fieldErrors.value = data.errors ?? {};
            throw new Error(data?.message ?? 'Save failed');
        }

        isFormOpen.value = false;
        await loadZones(editingId.value ? filters.page : 1);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

watch(() => [filters.active, filters.cod], () => loadZones(1));
onMounted(() => loadZones());
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

            <div class="mt-5 grid gap-3 xl:grid-cols-[minmax(0,1fr)_180px_180px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                    <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                </label>
                <select v-model="filters.active" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                    <option value="">{{ text.allStatus }}</option>
                    <option value="1">{{ text.activeOnly }}</option>
                    <option value="0">{{ text.inactiveOnly }}</option>
                </select>
                <select v-model="filters.cod" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                    <option value="">{{ text.allCod }}</option>
                    <option value="1">{{ text.codOnly }}</option>
                    <option value="0">{{ text.noCod }}</option>
                </select>
            </div>
        </div>

        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

        <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
            <article v-else-if="zones.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
            <template v-else>
                <article v-for="zone in zones" :key="zone.id" class="rounded-lg bg-[#ffffff] p-4 shadow-lg shadow-black/5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 gap-3">
                            <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.truck" /></span>
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-black">{{ zone.district }}</h3>
                                <p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ zone.area || 'All areas' }}</p>
                            </div>
                        </div>
                        <button class="grid size-9 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="openEdit(zone)"><FontAwesomeIcon :icon="icons.wand" /></button>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div class="rounded-lg bg-white p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.charge }}</p><p class="mt-1 text-lg font-black">{{ formatPrice(zone.delivery_charge) }}</p></div>
                        <div class="rounded-lg bg-white p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">{{ text.codCharge }}</p><p class="mt-1 text-lg font-black">{{ formatPrice(zone.cod_charge) }}</p></div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-black uppercase">
                        <span class="rounded-full px-2.5 py-1 ring-1" :class="zone.is_active ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300' : 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300'">{{ zone.is_active ? text.active : text.inactive }}</span>
                        <span class="rounded-full px-2.5 py-1 ring-1" :class="zone.cod_available ? 'bg-[#9f243a]/16 text-[#8f2638] ring-[#9f243a]/30 dark:text-[#ff8aa0]' : 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-[#ff9bae]'">{{ zone.cod_available ? text.codOnly : text.noCod }}</span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[#4b5563] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#d1d5db] dark:ring-white/10">{{ zone.estimated_min_days }}-{{ zone.estimated_max_days }} {{ text.days }}</span>
                    </div>
                </article>
            </template>
        </div>

        <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ zones.length }} / {{ meta.total }}</p>
            <div class="flex items-center gap-2">
                <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadZones(meta.current_page - 1)">{{ text.prev }}</button>
                <span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadZones(meta.current_page + 1)">{{ text.next }}</button>
            </div>
        </div>
    </section>

    <div v-if="isFormOpen" class="fixed inset-0 z-50 overflow-y-auto bg-black/45 p-3 backdrop-blur-sm sm:p-6">
        <form class="mx-auto max-w-2xl rounded-lg bg-[#ffffff] shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10" @submit.prevent="save">
            <div class="flex items-center justify-between gap-3 border-b border-black/8 p-5 dark:border-white/10">
                <div><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p><h3 class="mt-1 text-xl font-black">{{ modalTitle }}</h3></div>
                <button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="isFormOpen = false"><FontAwesomeIcon :icon="icons.xmark" /></button>
            </div>
            <div class="grid gap-4 p-5">
                <div v-if="formError" class="rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
                <p class="rounded-lg bg-[#9f243a]/12 px-4 py-3 text-xs font-bold text-[#8f2638] ring-1 ring-[#9f243a]/25 dark:text-[#ff8aa0]">{{ text.takaHint }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.district }}</span><input v-model="form.district" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" required><small v-if="firstError('district')" class="text-[#8f2638]">{{ firstError('district') }}</small></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.area }}</span><input v-model="form.area" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"><small v-if="firstError('area')" class="text-[#8f2638]">{{ firstError('area') }}</small></label>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.charge }}</span><input v-model="form.delivery_charge" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"><small v-if="firstError('delivery_charge')" class="text-[#8f2638]">{{ firstError('delivery_charge') }}</small></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.codCharge }}</span><input v-model="form.cod_charge" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"></label>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.minDays }}</span><input v-model="form.estimated_min_days" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.maxDays }}</span><input v-model="form.estimated_max_days" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"><small v-if="firstError('estimated_max_days')" class="text-[#8f2638]">{{ firstError('estimated_max_days') }}</small></label>
                </div>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm font-black"><input v-model="form.cod_available" class="size-4 accent-[#8f2638]" type="checkbox">{{ text.codAvailable }}</label>
                    <label class="flex items-center gap-2 text-sm font-black"><input v-model="form.is_active" class="size-4 accent-[#8f2638]" type="checkbox">{{ text.active }}</label>
                </div>
            </div>
            <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:justify-end">
                <button class="h-11 rounded-lg border border-black/10 px-5 text-sm font-black dark:border-white/10" type="button" @click="isFormOpen = false">{{ text.close }}</button>
                <button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button>
            </div>
        </form>
    </div>
</template>
