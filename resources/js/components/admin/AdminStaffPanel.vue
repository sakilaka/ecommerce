<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const formError = ref(null);
const fieldErrors = ref({});
const staff = ref([]);
const selectedStaff = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', page: 1 });
const form = reactive({ name: '', email: '', phone: '', password: '', is_active: true, permissions: ['*'] });
let searchTimer = null;

const permissions = ['dashboard.view', 'orders.manage', 'payments.manage', 'catalog.manage', 'inventory.manage', 'customers.manage', 'delivery.manage', 'returns.manage', 'reviews.manage', 'marketing.manage', 'reports.view', 'activity.view', 'settings.manage', 'homepage.manage', 'wishlist.view', 'staff.manage'];

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'অ্যাডমিন সিকিউরিটি',
    title: 'স্টাফ ও পারমিশন ম্যানেজমেন্ট',
    subtitle: 'Super admin, limited staff এবং module permission professionalভাবে control করুন।',
    search: 'নাম, ইমেইল বা ফোন খুঁজুন',
    createNew: 'নতুন স্টাফ',
    name: 'Name',
    email: 'Email',
    phone: 'Phone',
    password: 'Password',
    active: 'Active',
    inactive: 'Inactive',
    permissions: 'Permissions',
    allAccess: 'Full access',
    save: 'Save Staff',
    saving: 'Saving...',
    empty: 'কোনো admin staff পাওয়া যায়নি।',
    failed: 'Staff লোড/সেভ করা যায়নি।',
    select: 'একজন staff select করুন অথবা নতুন তৈরি করুন।',
    prev: 'Prev',
    next: 'Next',
} : {
    eyebrow: 'Admin Security',
    title: 'Staff & Permission Management',
    subtitle: 'Control super admin, limited staff, and module permissions in a professional internal layer.',
    search: 'Search name, email, or phone',
    createNew: 'New Staff',
    name: 'Name',
    email: 'Email',
    phone: 'Phone',
    password: 'Password',
    active: 'Active',
    inactive: 'Inactive',
    permissions: 'Permissions',
    allAccess: 'Full access',
    save: 'Save Staff',
    saving: 'Saving...',
    empty: 'No admin staff found.',
    failed: 'Could not load or save staff.',
    select: 'Select a staff member or create a new one.',
    prev: 'Prev',
    next: 'Next',
});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const firstError = (name) => fieldErrors.value[name]?.[0] ?? '';
const initials = (name) => String(name || 'A').split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase();
const statusClass = (member) => member.is_active ? 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-emerald-300' : 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-[#ff9bae]';
const isFullAccess = computed(() => form.permissions.includes('*'));

const resetForm = () => {
    selectedStaff.value = null;
    form.name = '';
    form.email = '';
    form.phone = '';
    form.password = '';
    form.is_active = true;
    form.permissions = ['*'];
    fieldErrors.value = {};
    formError.value = null;
};

const selectStaff = (member) => {
    selectedStaff.value = member;
    form.name = member.name;
    form.email = member.email;
    form.phone = member.phone ?? '';
    form.password = '';
    form.is_active = Boolean(member.is_active);
    form.permissions = [...(member.permissions ?? ['*'])];
    fieldErrors.value = {};
    formError.value = null;
};

const togglePermission = (permission) => {
    if (permission === '*') {
        form.permissions = ['*'];
        return;
    }

    const current = form.permissions.filter((item) => item !== '*');
    form.permissions = current.includes(permission) ? current.filter((item) => item !== permission) : [...current, permission];
    if (form.permissions.length === 0) form.permissions = [permission];
};

const loadStaff = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;
    const params = new URLSearchParams({ per_page: '15', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());

    try {
        const response = await fetch('/api/admin/staff?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload?.message ?? 'Request failed');
        staff.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
        if (selectedStaff.value) {
            const refreshed = staff.value.find((member) => member.id === selectedStaff.value.id);
            selectedStaff.value = refreshed ?? null;
            if (refreshed) selectStaff(refreshed);
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadStaff(1), 350);
};

const save = async () => {
    isSaving.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        const body = { name: form.name, email: form.email, phone: form.phone || null, is_active: form.is_active, permissions: form.permissions };
        if (form.password) body.password = form.password;
        const response = await fetch('/api/admin/staff' + (selectedStaff.value ? '/' + selectedStaff.value.id : ''), {
            method: selectedStaff.value ? 'PATCH' : 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        const payload = await response.json();
        if (!response.ok) { fieldErrors.value = payload.errors ?? {}; throw new Error(payload?.message ?? 'Save failed'); }
        selectStaff(payload.data);
        await loadStaff(filters.page);
    } catch (requestError) {
        formError.value = text.value.failed;
    } finally {
        isSaving.value = false;
    }
};

onMounted(() => loadStaff());
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(380px,0.55fr)]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div><h2 class="text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2><p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p></div>
                    <button class="h-11 rounded-lg bg-[#171512] px-5 text-sm font-black text-white shadow-lg shadow-black/10 dark:bg-[#9f243a] dark:text-white" type="button" @click="resetForm">{{ text.createNew }}</button>
                </div>
                <label class="mt-5 flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10"><FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" /><input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch"></label>
            </div>
            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>
            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="staff.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                    <button v-for="member in staff" :key="member.id" class="rounded-lg bg-[#ffffff] p-4 text-left shadow-lg shadow-black/5 ring-1 ring-black/6 transition hover:bg-white dark:bg-black/20 dark:ring-white/10 dark:hover:bg-white/10" :class="selectedStaff?.id === member.id ? 'ring-2 ring-[#8f2638] dark:ring-[#9f243a]' : ''" type="button" @click="selectStaff(member)">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"><div class="flex min-w-0 gap-3"><span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white">{{ initials(member.name) }}</span><div class="min-w-0"><h3 class="truncate text-base font-black">{{ member.name }}</h3><p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ member.email }} · {{ member.phone || '-' }}</p><p class="mt-2 text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ member.permissions?.includes('*') ? text.allAccess : member.permissions?.length + ' permissions' }}</p></div></div><span class="h-fit rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="statusClass(member)">{{ member.is_active ? text.active : text.inactive }}</span></div>
                    </button>
                </template>
            </div>
            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ staff.length }} / {{ meta.total }}</p><div class="flex items-center gap-2"><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadStaff(meta.current_page - 1)">{{ text.prev }}</button><span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span><button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadStaff(meta.current_page + 1)">{{ text.next }}</button></div></div>
        </div>

        <form class="rounded-lg bg-white/88 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10" @submit.prevent="save">
            <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.permissions }}</p>
            <div v-if="formError" class="mt-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ formError }}</div>
            <div class="mt-4 grid gap-3">
                <label class="grid gap-1 text-sm font-bold"><span>{{ text.name }}</span><input v-model="form.name" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><small v-if="firstError('name')" class="text-[#8f2638]">{{ firstError('name') }}</small></label>
                <label class="grid gap-1 text-sm font-bold"><span>{{ text.email }}</span><input v-model="form.email" type="email" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><small v-if="firstError('email')" class="text-[#8f2638]">{{ firstError('email') }}</small></label>
                <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-sm font-bold"><span>{{ text.phone }}</span><input v-model="form.phone" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><small v-if="firstError('phone')" class="text-[#8f2638]">{{ firstError('phone') }}</small></label><label class="grid gap-1 text-sm font-bold"><span>{{ text.password }}</span><input v-model="form.password" type="password" class="h-11 rounded-lg border-0 bg-[#f6f6f6] px-3 outline-none ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><small v-if="firstError('password')" class="text-[#8f2638]">{{ firstError('password') }}</small></label></div>
                <label class="flex min-h-11 items-center justify-between gap-3 rounded-lg bg-[#f6f6f6] px-3 text-sm font-bold ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10"><span>{{ text.active }}</span><input v-model="form.is_active" class="size-4 accent-[#8f2638]" type="checkbox"></label>
                <div class="grid gap-2 rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/8 dark:bg-black/20 dark:ring-white/10">
                    <button class="flex min-h-10 items-center justify-between rounded-lg px-3 text-left text-sm font-black transition" :class="isFullAccess ? 'bg-[#171512] text-white dark:bg-[#9f243a] dark:text-white' : 'bg-white dark:bg-white/10'" type="button" @click="togglePermission('*')"><span>{{ text.allAccess }}</span><FontAwesomeIcon :icon="icons.shield" /></button>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button v-for="permission in permissions" :key="permission" class="min-h-10 rounded-lg px-3 text-left text-xs font-black transition" :class="form.permissions.includes(permission) && !isFullAccess ? 'bg-[#8f2638] text-white' : 'bg-white text-[#374151] dark:bg-white/10 dark:text-[#e5e7eb]'" type="button" @click="togglePermission(permission)">{{ permission }}</button>
                    </div>
                    <small v-if="firstError('permissions')" class="text-[#8f2638]">{{ firstError('permissions') }}</small>
                </div>
                <button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isSaving">{{ isSaving ? text.saving : text.save }}</button>
            </div>
        </form>
    </section>
</template>
