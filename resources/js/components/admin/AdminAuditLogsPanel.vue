<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const error = ref(null);
const logs = ref([]);
const selectedLog = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ q: '', event: '', page: 1 });
let searchTimer = null;

const eventOptions = ['store_settings.updated', 'delivery_zone.created', 'delivery_zone.updated', 'return_request.updated'];
const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'অ্যাডমিন অ্যাক্টিভিটি',
    title: 'অডিট লগস',
    subtitle: 'কোন অ্যাডমিন কখন কী পরিবর্তন করেছে, IP, user agent, old/new values সহ ট্র্যাক করুন।',
    search: 'ইভেন্ট, অ্যাডমিন, IP বা মডেল খুঁজুন',
    allEvents: 'সব ইভেন্ট',
    event: 'ইভেন্ট',
    admin: 'অ্যাডমিন',
    subject: 'Subject',
    ip: 'IP Address',
    time: 'সময়',
    oldValues: 'Old Values',
    newValues: 'New Values',
    userAgent: 'User Agent',
    select: 'একটি অডিট লগ সিলেক্ট করুন।',
    details: 'Log Details',
    empty: 'কোনো অডিট লগ পাওয়া যায়নি।',
    failed: 'অডিট লগ লোড করা যায়নি।',
    prev: 'Prev',
    next: 'Next',
} : {
    eyebrow: 'Admin Activity',
    title: 'Audit Logs',
    subtitle: 'Track who changed what, when it happened, and the before/after values for sensitive admin actions.',
    search: 'Search event, admin, IP, or model',
    allEvents: 'All Events',
    event: 'Event',
    admin: 'Admin',
    subject: 'Subject',
    ip: 'IP Address',
    time: 'Time',
    oldValues: 'Old Values',
    newValues: 'New Values',
    userAgent: 'User Agent',
    select: 'Select an audit log.',
    details: 'Log Details',
    empty: 'No audit logs found.',
    failed: 'Could not load audit logs.',
    prev: 'Prev',
    next: 'Next',
});

const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }).format(new Date(value)) : '-';
const formatJson = (value) => value ? JSON.stringify(value, null, 2) : '-';
const eventLabel = (event) => event.replaceAll('_', ' ').replaceAll('.', ' / ');

const loadLogs = async (page = filters.page) => {
    isLoading.value = true;
    error.value = null;
    filters.page = page;

    const params = new URLSearchParams({ per_page: '15', page: String(page) });
    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.event) params.set('event', filters.event);

    try {
        const response = await fetch('/api/admin/audit-logs?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.message ?? 'Request failed');
        }

        logs.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;

        if (selectedLog.value) {
            selectedLog.value = logs.value.find((log) => log.id === selectedLog.value.id) ?? null;
        }
    } catch (requestError) {
        error.value = text.value.failed;
    } finally {
        isLoading.value = false;
    }
};

const queueSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadLogs(1), 350);
};

watch(() => filters.event, () => loadLogs(1));
onMounted(() => loadLogs());
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(360px,0.55fr)]">
        <div class="rounded-lg bg-white/88 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
            <div class="border-b border-black/8 p-5 dark:border-white/10 sm:p-6">
                <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_230px]">
                    <label class="flex h-12 items-center gap-3 rounded-lg bg-[#f6f6f6] px-4 ring-1 ring-black/6 dark:bg-black/20 dark:ring-white/10">
                        <FontAwesomeIcon :icon="icons.search" class="text-[#8f2638] dark:text-[#ff8aa0]" />
                        <input v-model="filters.q" class="min-w-0 flex-1 bg-transparent text-sm font-bold outline-none placeholder:text-[#6b7280] dark:text-white" type="search" :placeholder="text.search" @input="queueSearch">
                    </label>
                    <select v-model="filters.event" class="h-12 rounded-lg border-0 bg-[#f6f6f6] px-4 text-sm font-black outline-none ring-1 ring-black/6 dark:bg-black/20 dark:text-white dark:ring-white/10">
                        <option value="">{{ text.allEvents }}</option>
                        <option v-for="event in eventOptions" :key="event" :value="event">{{ eventLabel(event) }}</option>
                    </select>
                </div>
            </div>

            <div v-if="error" class="m-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>

            <div class="grid gap-3 p-4">
                <article v-if="isLoading" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">Loading...</article>
                <article v-else-if="logs.length === 0" class="rounded-lg bg-[#f6f6f6] p-5 text-sm font-black dark:bg-black/20">{{ text.empty }}</article>
                <template v-else>
                    <button v-for="log in logs" :key="log.id" class="rounded-lg bg-[#ffffff] p-4 text-left shadow-lg shadow-black/5 ring-1 ring-black/6 transition hover:bg-white dark:bg-black/20 dark:ring-white/10 dark:hover:bg-white/10" :class="selectedLog?.id === log.id ? 'ring-2 ring-[#8f2638] dark:ring-[#9f243a]' : ''" type="button" @click="selectedLog = log">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="flex min-w-0 gap-3">
                                <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.clock" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase text-[#8f2638] dark:text-[#ff8aa0]">{{ eventLabel(log.event) }}</p>
                                    <h3 class="mt-1 truncate text-base font-black">{{ log.user?.name || 'System' }}</h3>
                                    <p class="mt-1 truncate text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ log.auditable_label || '-' }} #{{ log.auditable_id || '-' }}</p>
                                </div>
                            </div>
                            <div class="shrink-0 text-left md:text-right">
                                <p class="text-sm font-black">{{ formatDate(log.created_at) }}</p>
                                <p class="mt-1 text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ log.ip_address || '-' }}</p>
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            <div class="flex flex-col gap-3 border-t border-black/8 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ logs.length }} / {{ meta.total }}</p>
                <div class="flex items-center gap-2">
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page <= 1" @click="loadLogs(meta.current_page - 1)">{{ text.prev }}</button>
                    <span class="grid h-10 min-w-10 place-items-center rounded-lg bg-[#f6f6f6] px-3 text-sm font-black dark:bg-black/20">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button class="h-10 rounded-lg border border-black/10 px-4 text-sm font-black disabled:opacity-45 dark:border-white/10" type="button" :disabled="isLoading || meta.current_page >= meta.last_page" @click="loadLogs(meta.current_page + 1)">{{ text.next }}</button>
                </div>
            </div>
        </div>

        <aside class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
            <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.details }}</p>
            <div v-if="!selectedLog" class="mt-4 rounded-lg bg-white/8 p-4 text-sm font-bold text-white/70 ring-1 ring-white/10">{{ text.select }}</div>
            <div v-else class="mt-4 grid gap-4">
                <div class="grid gap-2 rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.event }}</p><p class="font-black">{{ eventLabel(selectedLog.event) }}</p></div>
                <div class="grid gap-2 rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.admin }}</p><p class="font-black">{{ selectedLog.user?.name || 'System' }}</p><p class="text-sm font-semibold text-white/65">{{ selectedLog.user?.email || '-' }}</p></div>
                <div class="grid grid-cols-2 gap-3"><div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.subject }}</p><p class="mt-1 text-sm font-black">{{ selectedLog.auditable_label || '-' }} #{{ selectedLog.auditable_id || '-' }}</p></div><div class="rounded-lg bg-white/8 p-3 ring-1 ring-white/10"><p class="text-[10px] font-black uppercase text-white/45">{{ text.ip }}</p><p class="mt-1 text-sm font-black">{{ selectedLog.ip_address || '-' }}</p></div></div>
                <div class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.oldValues }}</p><pre class="mt-3 max-h-52 overflow-auto whitespace-pre-wrap text-xs font-semibold leading-5 text-white/70">{{ formatJson(selectedLog.old_values) }}</pre></div>
                <div class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.newValues }}</p><pre class="mt-3 max-h-52 overflow-auto whitespace-pre-wrap text-xs font-semibold leading-5 text-white/70">{{ formatJson(selectedLog.new_values) }}</pre></div>
                <div class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10"><p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.userAgent }}</p><p class="mt-2 break-words text-xs font-semibold leading-5 text-white/65">{{ selectedLog.user_agent || '-' }}</p></div>
            </div>
        </aside>
    </section>
</template>
