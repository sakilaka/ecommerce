<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useAuth } from '../../composables/useAuth';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons, locale, localeToggleLabel, themeIcon, themeLabel, toggleLocale, toggleTheme } = useStorefront();
const { authError, isAuthLoading, loadUser, login, logout, user } = useAuth();

const accessError = ref('');
const form = reactive({ login: 'admin@aurelia.test', password: 'Admin@12345', remember: true });

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'Admin Access',
    title: 'অ্যাডমিন কনসোলে লগইন করুন',
    subtitle: 'শুধুমাত্র অনুমোদিত অ্যাডমিন ইউজাররা ড্যাশবোর্ডে প্রবেশ করতে পারবেন।',
    identifier: 'ইমেইল বা ফোন',
    password: 'পাসওয়ার্ড',
    remember: 'মনে রাখুন',
    submit: 'অ্যাডমিন লগইন',
    storefront: 'স্টোরফ্রন্টে ফিরুন',
    denied: 'এই অ্যাকাউন্টে অ্যাডমিন অনুমতি নেই।',
    logout: 'লগআউট করুন',
} : {
    eyebrow: 'Admin Access',
    title: 'Login to Admin Console',
    subtitle: 'Only authorized admin users can access the management dashboard.',
    identifier: 'Email or phone',
    password: 'Password',
    remember: 'Remember me',
    submit: 'Admin Login',
    storefront: 'Back to Storefront',
    denied: 'This account does not have admin permission.',
    logout: 'Logout',
});

const submit = async () => {
    accessError.value = '';
    const loggedInUser = await login({ ...form });

    if (!loggedInUser) {
        return;
    }

    if (loggedInUser.role !== 'admin') {
        accessError.value = text.value.denied;
        return;
    }

    window.location.href = '/admin/dashboard';
};

const logoutAndReset = async () => {
    await logout();
    accessError.value = '';
};

onMounted(async () => {
    await loadUser();

    if (user.value?.role === 'admin') {
        window.location.href = '/admin/dashboard';
    }
});
</script>

<template>
    <main class="grid min-h-screen bg-[#f3f4f6] text-[#171512] transition-colors dark:bg-[#111111] dark:text-white lg:grid-cols-[0.95fr_1.05fr]">
        <section class="relative hidden overflow-hidden bg-[#111111] p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-45" style="background: radial-gradient(circle at 18% 16%, rgba(241,193,93,.26), transparent 28%), linear-gradient(135deg, rgba(143,38,56,.45), transparent 42%);"></div>
            <div class="relative flex items-center gap-3">
                <span class="grid size-12 place-items-center rounded-lg bg-[#9f243a] font-serif text-2xl font-black text-white">A</span>
                <div><p class="text-lg font-black">{{ copy.brand }}</p><p class="text-xs font-black uppercase text-white/55">Admin Console</p></div>
            </div>
            <div class="relative max-w-xl">
                <p class="text-xs font-black uppercase tracking-normal text-[#ff8aa0]">{{ text.eyebrow }}</p>
                <h1 class="mt-4 text-5xl font-black leading-tight">{{ text.title }}</h1>
                <p class="mt-5 text-base font-semibold leading-7 text-white/68">{{ text.subtitle }}</p>
            </div>
            <div class="relative grid grid-cols-3 gap-3 text-sm font-bold text-white/70">
                <span class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10">Orders</span>
                <span class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10">Catalog</span>
                <span class="rounded-lg bg-white/8 p-4 ring-1 ring-white/10">Inventory</span>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-12">
            <div class="w-full max-w-md">
                <div class="mb-6 flex items-center justify-between gap-3">
                    <a class="inline-flex h-10 items-center gap-2 rounded-lg border border-black/10 bg-white px-3 text-xs font-black shadow-sm dark:border-white/10 dark:bg-white/10" href="/">
                        <FontAwesomeIcon :icon="icons.arrowRight" class="rotate-180" />
                        {{ text.storefront }}
                    </a>
                    <div class="flex gap-2">
                        <button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white text-xs font-black shadow-sm dark:border-white/10 dark:bg-white/10" type="button" :aria-label="localeToggleLabel" @click="toggleLocale">{{ localeToggleLabel }}</button>
                        <button class="grid size-10 place-items-center rounded-lg border border-black/10 bg-white shadow-sm dark:border-white/10 dark:bg-white/10" type="button" :aria-label="themeLabel" @click="toggleTheme"><FontAwesomeIcon :icon="themeIcon" /></button>
                    </div>
                </div>

                <form class="rounded-lg bg-white/88 p-5 shadow-2xl shadow-black/10 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10 sm:p-6" @submit.prevent="submit">
                    <div class="mb-5 flex items-center gap-3 lg:hidden">
                        <span class="grid size-11 place-items-center rounded-lg bg-[#171512] font-serif text-xl text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white">A</span>
                        <div><p class="text-base font-black">{{ copy.brand }}</p><p class="text-xs font-black uppercase text-[#4b5563] dark:text-[#d1d5db]">Admin Console</p></div>
                    </div>
                    <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                    <h2 class="mt-2 text-3xl font-black leading-tight">{{ text.title }}</h2>
                    <p class="mt-3 text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                    <div v-if="authError || accessError" class="mt-4 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ accessError || authError }}</div>
                    <div class="mt-5 grid gap-3">
                        <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.identifier }}<input v-model="form.login" required class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="text"></label>
                        <label class="block text-xs font-black uppercase tracking-normal text-[#4b5563] dark:text-[#d1d5db]">{{ text.password }}<input v-model="form.password" required minlength="8" class="mt-2 h-11 w-full rounded-lg border border-black/10 bg-white px-3 text-sm font-bold normal-case text-[#171512] outline-none focus:border-[#8f2638] dark:border-white/10 dark:bg-black/20 dark:text-white" type="password"></label>
                        <label class="flex items-center gap-3 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]"><input v-model="form.remember" class="size-4 accent-[#8f2638]" type="checkbox">{{ text.remember }}</label>
                    </div>
                    <button class="mt-5 flex h-12 w-full items-center justify-center gap-3 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-xl shadow-[#8f2638]/20 transition hover:bg-[#741c2b] disabled:opacity-50 dark:bg-[#9f243a] dark:text-white" type="submit" :disabled="isAuthLoading">
                        {{ text.submit }}
                        <FontAwesomeIcon :icon="icons.arrowRight" />
                    </button>
                    <button v-if="user && user.role !== 'admin'" class="mt-3 h-11 w-full rounded-lg border border-black/10 bg-white text-sm font-black dark:border-white/10 dark:bg-white/10" type="button" @click="logoutAndReset">{{ text.logout }}</button>
                </form>
            </div>
        </section>
    </main>
</template>
