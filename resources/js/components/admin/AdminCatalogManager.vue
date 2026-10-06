<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const props = defineProps({ type: { type: String, required: true } });
const { icons, locale } = useStorefront();

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
const items = ref([]);
const parentOptions = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', status: '', official: '', page: 1 });
const isFormOpen = ref(false);
const editingId = ref(null);
let searchTimer = null;

const isBrand = computed(() => props.type === 'brands');
const endpoint = computed(() => '/api/admin/' + props.type);

const copy = computed(() => {
    const bn = locale.value === 'bn';
    if (isBrand.value) {
        return bn ? { eyebrow: 'ব্র্যান্ড স্টোর', title: 'ব্র্যান্ড ম্যানেজমেন্ট', subtitle: 'অফিশিয়াল ও সাধারণ ব্র্যান্ড আলাদা করে সাজান, যাতে কাস্টমার দ্রুত ভরসাযোগ্য স্টোর খুঁজে পায়।', add: 'নতুন ব্র্যান্ড', edit: 'ব্র্যান্ড আপডেট', search: 'ব্র্যান্ড নাম বা স্লাগ খুঁজুন', name: 'ব্র্যান্ড নাম', slug: 'স্লাগ', media: 'লোগো পাথ / URL', description: 'বিবরণ', seoTitle: 'SEO title', seoDescription: 'SEO description', seoHelp: 'Search result এর title এবং description নিয়ন্ত্রণ করুন।', official: 'অফিশিয়াল', active: 'অ্যাক্টিভ', inactive: 'ইনঅ্যাক্টিভ', all: 'সব স্ট্যাটাস', onlyOfficial: 'শুধু অফিশিয়াল', allBrands: 'সব ব্র্যান্ড', products: 'পণ্য', save: 'সেভ করুন', saving: 'সেভ হচ্ছে...', close: 'বন্ধ করুন', empty: 'কোনো ব্র্যান্ড পাওয়া যায়নি।', failed: 'ব্র্যান্ড লোড/সেভ করা যায়নি।' }
            : { eyebrow: 'Brand Stores', title: 'Brand Management', subtitle: 'Separate official and regular brands so customers can discover trusted storefronts faster.', add: 'Add Brand', edit: 'Update Brand', search: 'Search brand name or slug', name: 'Brand name', slug: 'Slug', media: 'Logo path / URL', description: 'Description', seoTitle: 'SEO title', seoDescription: 'SEO description', seoHelp: 'Control the title and description shown in search results.', official: 'Official', active: 'Active', inactive: 'Inactive', all: 'All Status', onlyOfficial: 'Official only', allBrands: 'All Brands', products: 'Products', save: 'Save', saving: 'Saving...', close: 'Close', empty: 'No brands found.', failed: 'Could not load or save brands.' };
    }
    return bn ? { eyebrow: 'ডিপার্টমেন্ট স্ট্রাকচার', title: 'ক্যাটাগরি ম্যানেজমেন্ট', subtitle: 'হোমপেজ, মেগা মেনু এবং পণ্য ফিল্টারের জন্য পরিষ্কার ক্যাটাগরি হায়ারার্কি তৈরি করুন।', add: 'নতুন ক্যাটাগরি', edit: 'ক্যাটাগরি আপডেট', search: 'ক্যাটাগরি নাম বা স্লাগ খুঁজুন', name: 'ক্যাটাগরি নাম', slug: 'স্লাগ', media: 'ইমেজ পাথ / URL', description: 'বিবরণ', seoTitle: 'SEO title', seoDescription: 'SEO description', seoHelp: 'Search result এর title এবং description নিয়ন্ত্রণ করুন।', parent: 'Parent category', noParent: 'Parent নেই', sort: 'Sort order', active: 'অ্যাক্টিভ', inactive: 'ইনঅ্যাক্টিভ', all: 'সব স্ট্যাটাস', products: 'পণ্য', children: 'সাবক্যাটাগরি', save: 'সেভ করুন', saving: 'সেভ হচ্ছে...', close: 'বন্ধ করুন', empty: 'কোনো ক্যাটাগরি পাওয়া যায়নি।', failed: 'ক্যাটাগরি লোড/সেভ করা যায়নি।' }
        : { eyebrow: 'Department Structure', title: 'Category Management', subtitle: 'Build a clean category hierarchy for homepage merchandising, mega menu navigation, and product filtering.', add: 'Add Category', edit: 'Update Category', search: 'Search category name or slug', name: 'Category name', slug: 'Slug', media: 'Image path / URL', description: 'Description', seoTitle: 'SEO title', seoDescription: 'SEO description', seoHelp: 'Control the title and description shown in search results.', parent: 'Parent category', noParent: 'No parent', sort: 'Sort order', active: 'Active', inactive: 'Inactive', all: 'All Status', products: 'Products', children: 'Subcategories', save: 'Save', saving: 'Saving...', close: 'Close', empty: 'No categories found.', failed: 'Could not load or save categories.' };
});

const emptyForm = () => isBrand.value ? { name: '', slug: '', logo_path: '', description: '', seo_title: '', seo_description: '', is_official: false, is_active: true } : { parent_id: '', name: '', slug: '', image_path: '', description: '', seo_title: '', seo_description: '', sort_order: 0, is_active: true };
const form = reactive(emptyForm());
const statusOptions = computed(() => [{ value: '', label: copy.value.all }, { value: 'active', label: copy.value.active }, { value: 'inactive', label: copy.value.inactive }]);
const modalTitle = computed(() => editingId.value ? copy.value.edit : copy.value.add);
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';

const resetForm = () => { Object.keys(form).forEach((key) => delete form[key]); Object.assign(form, emptyForm()); editingId.value = null; fieldErrors.value = {}; formError.value = null; };

const loadItems = async (page = filters.page) => {
    isLoading.value = true; error.value = null; filters.page = page;
    const params = new URLSearchParams({ per_page: '12', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.status) params.set('status', filters.status);
    if (isBrand.value && filters.official) params.set('official', '1');
    try {
        const response = await fetch(endpoint.value + '?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Request failed');
        const payload = await response.json();
        items.value = payload.data ?? []; meta.value = payload.meta ?? meta.value;
        if (!isBrand.value) parentOptions.value = items.value.filter((item) => item.is_active);
    } catch (requestError) { error.value = copy.value.failed; } finally { isLoading.value = false; }
};

const queueSearch = () => { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(() => loadItems(1), 350); };
const openCreate = () => { resetForm(); isFormOpen.value = true; };
const openEdit = (item) => { resetForm(); editingId.value = item.id; Object.assign(form, isBrand.value ? { name: item.name, slug: item.slug, logo_path: item.logo_path ?? '', description: item.description ?? '', seo_title: item.seo_title ?? '', seo_description: item.seo_description ?? '', is_official: Boolean(item.is_official), is_active: Boolean(item.is_active) } : { parent_id: item.parent_id ?? '', name: item.name, slug: item.slug, image_path: item.image_path ?? '', description: item.description ?? '', seo_title: item.seo_title ?? '', seo_description: item.seo_description ?? '', sort_order: item.sort_order ?? 0, is_active: Boolean(item.is_active) }); isFormOpen.value = true; };

const payload = () => {
    if (isBrand.value) return { ...form, slug: form.slug || null, logo_path: form.logo_path || null, description: form.description || null, seo_title: form.seo_title || null, seo_description: form.seo_description || null };
    return { ...form, parent_id: form.parent_id ? Number(form.parent_id) : null, slug: form.slug || null, image_path: form.image_path || null, description: form.description || null, seo_title: form.seo_title || null, seo_description: form.seo_description || null, sort_order: Number(form.sort_order) };
};

const save = async () => {
    isSaving.value = true; formError.value = null; fieldErrors.value = {};
    try {
        const response = await fetch(editingId.value ? endpoint.value + '/' + editingId.value : endpoint.value, { method: editingId.value ? 'PATCH' : 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, credentials: 'same-origin', body: JSON.stringify(payload()) });
        const data = await response.json();
        if (!response.ok) { fieldErrors.value = data.errors ?? {}; throw new Error('Save failed'); }
        isFormOpen.value = false; await loadItems(editingId.value ? filters.page : 1);
    } catch (requestError) { formError.value = copy.value.failed; } finally { isSaving.value = false; }
};

watch(() => [filters.status, filters.official], () => loadItems(1));
watch(() => props.type, () => { filters.q = ''; filters.status = ''; filters.official = ''; resetForm(); loadItems(1); });
onMounted(() => loadItems());
</script>

<template>
    <section class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
        <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div><p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.eyebrow }}</p><h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ copy.title }}</h2><p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ copy.subtitle }}</p></div>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#171512] px-4 text-sm font-black text-white shadow-lg shadow-black/12 dark:bg-[#9f243a] dark:text-white" type="button" @click="openCreate"><FontAwesomeIcon :icon="icons.plus" />{{ copy.add }}</button>
            </div>
            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px]">
                <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="copy.search" @input="queueSearch"></label>
                <select v-model="filters.status" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                <select v-if="isBrand" v-model="filters.official" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10"><option value="">{{ copy.allBrands }}</option><option value="1">{{ copy.onlyOfficial }}</option></select>
            </div>
        </div>
        <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>
        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
            <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
            <article v-else-if="items.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ copy.empty }}</article>
            <template v-else>
            <article v-for="item in items" :key="item.id" class="rounded-lg bg-[#ffffff] p-4 shadow-lg shadow-black/5 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                <div class="flex items-start justify-between gap-3"><div class="flex min-w-0 gap-3"><span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="isBrand ? icons.store : icons.grip" /></span><div class="min-w-0"><h3 class="truncate text-base font-black">{{ item.name }}</h3><p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">/{{ item.slug }}</p></div></div><button class="grid size-9 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="openEdit(item)"><FontAwesomeIcon :icon="icons.wand" /></button></div>
                <p class="mt-4 line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-[#374151] dark:text-[#e5e7eb]">{{ item.description || '-' }}</p>
                <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-black uppercase"><span class="rounded-full px-2.5 py-1 ring-1" :class="item.is_active ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300' : 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300'">{{ item.is_active ? copy.active : copy.inactive }}</span><span v-if="isBrand && item.is_official" class="rounded-full bg-[#9f243a]/16 px-2.5 py-1 text-[#8f2638] ring-1 ring-[#9f243a]/30 dark:text-[#ff8aa0]">{{ copy.official }}</span><span class="rounded-full bg-white px-2.5 py-1 text-[#4b5563] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#d1d5db] dark:ring-white/10">{{ item.products_count }} {{ copy.products }}</span><span v-if="!isBrand" class="rounded-full bg-white px-2.5 py-1 text-[#4b5563] ring-1 ring-black/6 dark:bg-white/10 dark:text-[#d1d5db] dark:ring-white/10">{{ item.children_count }} {{ copy.children }}</span></div>
            </article>
            </template>
        </div>
        <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ items.length }} / {{ meta.total }}</p><div class="flex items-center gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadItems(meta.current_page - 1)">Prev</button><span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadItems(meta.current_page + 1)">Next</button></div></div>
    </section>

    <div v-if="isFormOpen" class="fixed inset-0 z-50 overflow-y-auto bg-black/45 p-3 backdrop-blur-sm sm:p-6">
        <form class="mx-auto max-w-2xl rounded-lg bg-[#ffffff] shadow-2xl ring-1 ring-black/10 dark:bg-[#111111] dark:ring-white/10" @submit.prevent="save">
            <div class="flex items-center justify-between gap-3 border-b border-black/8 p-5 dark:border-white/10"><div><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ copy.eyebrow }}</p><h3 class="mt-1 text-xl font-black">{{ modalTitle }}</h3></div><button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-white/10" type="button" @click="isFormOpen = false"><FontAwesomeIcon :icon="icons.xmark" /></button></div>
            <div class="grid gap-4 p-5">
                <div v-if="formError" class="rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
                <label v-if="!isBrand" class="grid gap-1 text-sm font-bold"><span>{{ copy.parent }}</span><select v-model="form.parent_id" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"><option value="">{{ copy.noParent }}</option><option v-for="option in parentOptions" :key="option.id" :value="option.id">{{ option.name }}</option></select></label>
                <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ copy.name }}</span><input v-model="form.name" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" required><small v-if="firstError('name')" class="text-[#8f2638]">{{ firstError('name') }}</small></label><label class="grid gap-1 text-sm font-bold"><span>{{ copy.slug }}</span><input v-model="form.slug" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></label></div>
                <label v-if="isBrand" class="grid gap-1 text-sm font-bold"><span>{{ copy.media }}</span><input v-model="form.logo_path" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></label>
                <label v-else class="grid gap-1 text-sm font-bold"><span>{{ copy.media }}</span><input v-model="form.image_path" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></label>
                <label class="grid gap-1 text-sm font-bold"><span>{{ copy.description }}</span><textarea v-model="form.description" class="min-h-24 rounded-lg border-0 bg-white px-3 py-2 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"></textarea></label>
                <div class="rounded-lg bg-white/70 p-4 ring-1 ring-black/6 dark:bg-white/6 dark:ring-white/10"><div class="flex items-center justify-between gap-3"><p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">SEO</p><p class="text-right text-[11px] font-semibold text-[#4b5563] dark:text-[#d1d5db]">{{ copy.seoHelp }}</p></div><div class="mt-3 grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ copy.seoTitle }}</span><input v-model="form.seo_title" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" maxlength="70"></label><label class="grid gap-1 text-sm font-bold"><span>{{ copy.seoDescription }}</span><input v-model="form.seo_description" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" maxlength="180"></label></div></div>
                <label v-if="!isBrand" class="grid gap-1 text-sm font-bold"><span>{{ copy.sort }}</span><input v-model="form.sort_order" class="h-11 rounded-lg border-0 bg-white px-3 outline-none ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10" type="number" min="0"></label>
                <div class="flex flex-wrap gap-4"><label v-if="isBrand" class="flex items-center gap-2 text-sm font-black"><input v-model="form.is_official" type="checkbox">{{ copy.official }}</label><label class="flex items-center gap-2 text-sm font-black"><input v-model="form.is_active" type="checkbox">{{ copy.active }}</label></div>
            </div>
            <div class="flex flex-col gap-3 border-t border-black/8 p-5 dark:border-white/10 sm:flex-row sm:justify-end"><button class="h-11 rounded-lg border border-black/10 px-5 text-sm font-black dark:border-white/10" type="button" @click="isFormOpen = false">{{ copy.close }}</button><button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? copy.saving : copy.save }}</button></div>
        </form>
    </div>
</template>
