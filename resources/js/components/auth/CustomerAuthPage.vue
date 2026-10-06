<script setup>
import { computed, onMounted, reactive } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { redirectToIntendedCustomerPath, useAuth } from '../../composables/useAuth';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons, locale, localeToggleLabel, themeIcon, themeLabel, toggleLocale, toggleTheme } = useStorefront();
const { authError, isAuthLoading, loadUser, login, register, user } = useAuth();

const isRegister = computed(() => window.location.pathname === '/register');
const form = reactive({
    name: '',
    email: '',
    phone: '',
    login: '',
    password: '',
    password_confirmation: '',
    remember: true,
});

const text = computed(() => locale.value === 'bn' ? {
    loginTitle: 'আপনার অ্যাকাউন্টে প্রবেশ করুন',
    loginSubtitle: 'অর্ডার, উইশলিস্ট ও প্রোফাইল ম্যানেজ করতে লগইন করুন।',
    registerTitle: 'নতুন অ্যাকাউন্ট তৈরি করুন',
    registerSubtitle: 'একবার রেজিস্টার করলেই চেকআউট ও অর্ডার ট্র্যাকিং সহজ হয়।',
    login: 'লগইন',
    register: 'রেজিস্টার',
    name: 'পূর্ণ নাম',
    email: 'ইমেইল',
    phone: 'মোবাইল নম্বর',
    identifier: 'ইমেইল বা ফোন',
    password: 'পাসওয়ার্ড',
    confirmPassword: 'পাসওয়ার্ড নিশ্চিত করুন',
    remember: 'মনে রাখুন',
    submitLogin: 'লগইন করুন',
    submitRegister: 'অ্যাকাউন্ট তৈরি করুন',
    hasAccount: 'আগে থেকে অ্যাকাউন্ট আছে?',
    noAccount: 'নতুন কাস্টমার?',
    backToStore: 'স্টোরে ফিরুন',
    pointOrders: 'অর্ডার ও ট্র্যাকিং',
    pointAddresses: 'সেভ করা ঠিকানা',
    pointSupport: 'উইশলিস্ট ও সাপোর্ট',
} : {
    loginTitle: 'Sign in to your account',
    loginSubtitle: 'Log in to manage orders, wishlist, and your profile.',
    registerTitle: 'Create your customer account',
    registerSubtitle: 'Register once for faster checkout, tracking, and saved addresses.',
    login: 'Login',
    register: 'Register',
    name: 'Full name',
    email: 'Email',
    phone: 'Mobile number',
    identifier: 'Email or phone',
    password: 'Password',
    confirmPassword: 'Confirm password',
    remember: 'Remember me',
    submitLogin: 'Login',
    submitRegister: 'Create account',
    hasAccount: 'Already have an account?',
    noAccount: 'New customer?',
    backToStore: 'Back to store',
    pointOrders: 'Orders and tracking',
    pointAddresses: 'Saved addresses',
    pointSupport: 'Wishlist and support',
});

const switchHref = computed(() => {
    const next = new URLSearchParams(window.location.search).get('next');
    const suffix = next ? `?next=${encodeURIComponent(next)}` : '';

    return isRegister.value ? `/login${suffix}` : `/register${suffix}`;
});

const submit = async () => {
    const authenticatedUser = isRegister.value
        ? await register({
            name: form.name,
            email: form.email,
            phone: form.phone,
            password: form.password,
            password_confirmation: form.password_confirmation,
        })
        : await login({
            login: form.login,
            password: form.password,
            remember: form.remember,
        });

    if (authenticatedUser) {
        redirectToIntendedCustomerPath();
    }
};

onMounted(async () => {
    await loadUser();

    if (user.value) {
        redirectToIntendedCustomerPath();
    }
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white font-sans text-ink dark:bg-[#111111] dark:text-white">
        <header class="flex h-16 items-center justify-between gap-3 border-b border-black/10 px-4 sm:px-6 lg:px-10 dark:border-white/10">
            <a class="flex items-center gap-3" href="/">
                <span class="grid size-10 place-items-center rounded-2xl bg-ink text-lg font-semibold text-white dark:bg-white dark:text-ink">A</span>
                <span>
                    <p class="text-sm font-semibold">{{ copy.brand }}</p>
                    <p class="text-[11px] font-semibold text-ink/50 dark:text-white/50">{{ isRegister ? text.register : text.login }}</p>
                </span>
            </a>
            <div class="flex items-center gap-2">
                <button class="grid size-10 place-items-center rounded-full border border-black/10 text-xs font-semibold dark:border-white/10" type="button" :aria-label="localeToggleLabel" @click="toggleLocale">{{ localeToggleLabel }}</button>
                <button class="grid size-10 place-items-center rounded-full border border-black/10 dark:border-white/10" type="button" :aria-label="themeLabel" @click="toggleTheme"><FontAwesomeIcon :icon="themeIcon" /></button>
                <a class="btn-ghost hidden sm:inline-flex" href="/">{{ text.backToStore }}</a>
            </div>
        </header>

        <div class="grid flex-1 lg:grid-cols-[minmax(280px,0.85fr)_minmax(0,1.15fr)]">
            <aside class="relative hidden overflow-hidden bg-ink px-8 py-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-wine/30 blur-3xl"></div>
                <div class="relative">
                    <p class="text-[11px] font-semibold text-wine">{{ copy.brand }}</p>
                    <h1 class="mt-4 max-w-sm text-3xl font-semibold leading-snug">{{ isRegister ? text.registerTitle : text.loginTitle }}</h1>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-white/60">{{ isRegister ? text.registerSubtitle : text.loginSubtitle }}</p>
                </div>
                <ul class="relative grid gap-2 text-sm font-semibold text-white/75">
                    <li class="rounded-2xl bg-white/8 px-4 py-3">{{ text.pointOrders }}</li>
                    <li class="rounded-2xl bg-white/8 px-4 py-3">{{ text.pointAddresses }}</li>
                    <li class="rounded-2xl bg-white/8 px-4 py-3">{{ text.pointSupport }}</li>
                </ul>
            </aside>

            <section class="flex items-center justify-center px-4 py-10 sm:px-8">
                <form class="w-full max-w-md" @submit.prevent="submit">
                    <div class="lg:hidden">
                        <p class="text-[11px] font-semibold text-wine">{{ copy.brand }}</p>
                        <h1 class="mt-2 text-2xl font-semibold">{{ isRegister ? text.registerTitle : text.loginTitle }}</h1>
                        <p class="mt-2 text-sm font-semibold leading-6 text-ink/55 dark:text-white/55">{{ isRegister ? text.registerSubtitle : text.loginSubtitle }}</p>
                    </div>

                    <div v-if="authError" class="note-wine mt-5 lg:mt-0">{{ authError }}</div>

                    <div class="mt-6 grid gap-3">
                        <label v-if="isRegister" class="field-label">{{ text.name }}<input v-model="form.name" required class="field-input" type="text"></label>
                        <label v-if="isRegister" class="field-label">{{ text.email }}<input v-model="form.email" required class="field-input" type="email"></label>
                        <label v-if="isRegister" class="field-label">{{ text.phone }}<input v-model="form.phone" required pattern="01[3-9][0-9]{8}" class="field-input" type="tel" placeholder="01712345678"></label>
                        <label v-if="!isRegister" class="field-label">{{ text.identifier }}<input v-model="form.login" required class="field-input" type="text" autocomplete="username"></label>
                        <label class="field-label">{{ text.password }}<input v-model="form.password" required minlength="8" class="field-input" type="password" :autocomplete="isRegister ? 'new-password' : 'current-password'"></label>
                        <label v-if="isRegister" class="field-label">{{ text.confirmPassword }}<input v-model="form.password_confirmation" required minlength="8" class="field-input" type="password" autocomplete="new-password"></label>
                        <label v-if="!isRegister" class="flex items-center gap-3 text-sm font-semibold"><input v-model="form.remember" class="size-4 accent-wine" type="checkbox">{{ text.remember }}</label>
                    </div>

                    <button class="btn-wine mt-5 w-full" type="submit" :disabled="isAuthLoading">
                        {{ isRegister ? text.submitRegister : text.submitLogin }}
                        <FontAwesomeIcon :icon="icons.arrowRight" />
                    </button>

                    <p class="mt-5 text-center text-sm font-semibold text-ink/60 dark:text-white/60">
                        {{ isRegister ? text.hasAccount : text.noAccount }}
                        <a class="text-wine" :href="switchHref">{{ isRegister ? text.login : text.register }}</a>
                    </p>
                </form>
            </section>
        </div>
    </div>
</template>
