<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useAuth } from '../../composables/useAuth';
import { useStorefront } from '../../composables/useStorefront';
import AddressBook from './AddressBook.vue';

const { copy, icons, locale } = useStorefront();
const {
    authError,
    closeAuth,
    isAuthLoading,
    isAuthOpen,
    isOrderLoading,
    loadOrders,
    login,
    logout,
    orderError,
    orders,
    register,
    trackedOrder,
    trackOrder,
    updatePassword,
    updateProfile,
    user,
} = useAuth();
const mode = ref('login');
const accountNotice = ref('');

const form = reactive({
    name: '',
    email: '',
    phone: '',
    login: '',
    password: '',
    password_confirmation: '',
    remember: true,
});

const profileForm = reactive({
    name: '',
    email: '',
    phone: '',
});

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const tracking = reactive({
    order_number: '',
    phone: '',
});

const text = computed(() => locale.value === 'bn' ? {
    account: 'অ্যাকাউন্ট',
    welcome: 'স্বাগতম',
    login: 'লগইন',
    register: 'রেজিস্টার',
    loginHint: 'ইমেইল বা ফোন দিয়ে লগইন করুন।',
    registerHint: 'দ্রুত চেকআউটের জন্য অ্যাকাউন্ট তৈরি করুন।',
    name: 'পূর্ণ নাম',
    email: 'ইমেইল',
    phone: 'মোবাইল নম্বর',
    identifier: 'ইমেইল বা ফোন',
    password: 'পাসওয়ার্ড',
    confirmPassword: 'পাসওয়ার্ড নিশ্চিত করুন',
    remember: 'মনে রাখুন',
    submitLogin: 'লগইন করুন',
    submitRegister: 'অ্যাকাউন্ট তৈরি করুন',
    logout: 'লগআউট',
    dashboard: 'ড্যাশবোর্ড',
    dashboardHint: 'অর্ডার, ঠিকানা ও প্রোফাইল পুরো পেজে ম্যানেজ করুন।',
    openDashboard: 'ড্যাশবোর্ড খুলুন',
    loggedIn: 'আপনি লগইন করেছেন।',
    profile: 'প্রোফাইল তথ্য',
    profileHint: 'চেকআউট ও ডেলিভারির জন্য তথ্য আপডেট রাখুন।',
    saveProfile: 'প্রোফাইল সেভ করুন',
    profileSaved: 'প্রোফাইল আপডেট হয়েছে।',
    passwordChange: 'পাসওয়ার্ড পরিবর্তন',
    passwordHint: 'নিরাপত্তার জন্য শক্তিশালী নতুন পাসওয়ার্ড ব্যবহার করুন।',
    currentPassword: 'বর্তমান পাসওয়ার্ড',
    newPassword: 'নতুন পাসওয়ার্ড',
    confirmNewPassword: 'নতুন পাসওয়ার্ড নিশ্চিত করুন',
    savePassword: 'পাসওয়ার্ড আপডেট করুন',
    passwordSaved: 'পাসওয়ার্ড আপডেট হয়েছে।',
    recentOrders: 'সাম্প্রতিক অর্ডার',
    refresh: 'রিফ্রেশ',
    trackOrder: 'অর্ডার ট্র্যাক করুন',
    orderNumber: 'অর্ডার নম্বর',
    noOrders: 'এখনও কোনো অর্ডার নেই।',
    total: 'মোট',
    submitTrack: 'ট্র্যাক করুন',
    items: 'আইটেম',
    activeOrder: 'চলমান অর্ডার',
    orderHistoryHint: 'স্ট্যাটাস, ডেলিভারি ও পেমেন্ট তথ্য এক জায়গায় দেখুন।',
    placedOn: 'অর্ডারের তারিখ',
    deliveryTo: 'ডেলিভারি',
    payment: 'পেমেন্ট',
    orderTotal: 'অর্ডার টোটাল',
    subtotal: 'সাবটোটাল',
    deliveryCharge: 'ডেলিভারি চার্জ',
    discount: 'ডিসকাউন্ট',
    products: 'পণ্য',
    statusSteps: ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
} : {
    account: 'Account',
    welcome: 'Welcome',
    login: 'Login',
    register: 'Register',
    loginHint: 'Use email or phone to access your account.',
    registerHint: 'Create an account for faster checkout.',
    name: 'Full name',
    email: 'Email',
    phone: 'Mobile number',
    identifier: 'Email or phone',
    password: 'Password',
    confirmPassword: 'Confirm password',
    remember: 'Remember me',
    submitLogin: 'Login',
    submitRegister: 'Create Account',
    logout: 'Logout',
    dashboard: 'Dashboard',
    dashboardHint: 'Manage orders, addresses, and profile on the full account page.',
    openDashboard: 'Open Dashboard',
    loggedIn: 'You are signed in.',
    profile: 'Profile Details',
    profileHint: 'Keep checkout and delivery information ready.',
    saveProfile: 'Save Profile',
    profileSaved: 'Profile updated.',
    passwordChange: 'Change Password',
    passwordHint: 'Use a strong new password for account security.',
    currentPassword: 'Current password',
    newPassword: 'New password',
    confirmNewPassword: 'Confirm new password',
    savePassword: 'Update Password',
    passwordSaved: 'Password updated.',
    recentOrders: 'Recent Orders',
    refresh: 'Refresh',
    trackOrder: 'Track Order',
    orderNumber: 'Order number',
    noOrders: 'No orders yet.',
    total: 'Total',
    submitTrack: 'Track Order',
    items: 'items',
    activeOrder: 'Active Order',
    orderHistoryHint: 'See status, delivery, and payment information in one place.',
    placedOn: 'Placed on',
    deliveryTo: 'Delivery',
    payment: 'Payment',
    orderTotal: 'Order Total',
    subtotal: 'Subtotal',
    deliveryCharge: 'Delivery Charge',
    discount: 'Discount',
    products: 'Products',
    statusSteps: ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'],
});

const formatPrice = (amount) => `৳${Math.round(Number(amount || 0) / 100).toLocaleString('en-BD')}`;
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value === 'bn' ? 'bn-BD' : 'en-BD', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
}).format(new Date(value)) : '';
const normalizedStatus = (orderItem) => String(orderItem?.status ?? '').toLowerCase();
const statusStepIndex = (orderItem) => {
    const flow = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
    const index = flow.indexOf(normalizedStatus(orderItem));

    return index === -1 ? 0 : index;
};
const isTerminalProblemStatus = (orderItem) => ['cancelled', 'returned'].includes(normalizedStatus(orderItem));
const statusBadgeClasses = (orderItem) => isTerminalProblemStatus(orderItem)
    ? 'bg-[#8f2638]/10 text-[#8f2638] dark:bg-[#ff8aa1]/12 dark:text-[#ffb8c6]'
    : 'bg-[#9f243a]/12 text-[#8f2638] dark:bg-[#9f243a]/15 dark:text-[#ff8aa0]';
const orderAddress = (orderItem) => [
    orderItem.shipping_address?.address_line,
    orderItem.shipping_address?.area ?? orderItem.shipping_area,
    orderItem.shipping_address?.district ?? orderItem.shipping_district,
].filter(Boolean).join(', ');

const syncProfileForm = () => {
    profileForm.name = user.value?.name ?? '';
    profileForm.email = user.value?.email ?? '';
    profileForm.phone = user.value?.phone ?? '';
};

watch(user, syncProfileForm, { immediate: true });

watch(isAuthOpen, async (isOpen) => {
    if (isOpen && user.value) {
        await loadOrders();
    }
});

const submitTracking = async () => {
    await trackOrder(tracking);
};

const openDashboard = () => {
    window.location.href = '/customer/dashboard';
};

const submitProfile = async () => {
    accountNotice.value = '';
    const updatedUser = await updateProfile({ ...profileForm });

    if (updatedUser) {
        accountNotice.value = text.value.profileSaved;
    }
};

const submitPassword = async () => {
    accountNotice.value = '';
    const updated = await updatePassword({ ...passwordForm });

    if (updated) {
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        accountNotice.value = text.value.passwordSaved;
    }
};

const submit = async () => {
    if (mode.value === 'login') {
        const loggedInUser = await login({ login: form.login, password: form.password, remember: form.remember });

        if (loggedInUser) {
            window.location.href = '/customer/dashboard';
        }

        return;
    }

    const registeredUser = await register({
        name: form.name,
        email: form.email,
        phone: form.phone,
        password: form.password,
        password_confirmation: form.password_confirmation,
    });

    if (registeredUser) {
        window.location.href = '/customer/dashboard';
    }
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isAuthOpen" class="fixed inset-0 z-[95]">
            <button class="absolute inset-0 bg-black/45 backdrop-blur-sm" type="button" aria-label="Close account" @click="closeAuth"></button>

            <aside class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white font-sans shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)] dark:bg-[#111111] sm:max-w-lg">
                <header class="flex items-center justify-between gap-4 border-b border-black/10 px-5 py-4 dark:border-white/10 sm:px-6">
                    <div>
                        <p class="text-[11px] font-semibold text-wine">{{ copy.brand }}</p>
                        <h2 class="mt-1 text-2xl font-semibold text-ink dark:text-white">{{ text.account }}</h2>
                    </div>
                    <button class="grid size-9 place-items-center rounded-full border border-black/10 text-ink transition hover:bg-wine hover:text-white dark:border-white/10 dark:text-white" type="button" aria-label="Close account" @click="closeAuth">
                        <FontAwesomeIcon :icon="icons.xmark" />
                    </button>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div v-if="user" class="grid gap-4">
                        <section class="overflow-hidden rounded-[1.4rem] bg-[#141414] p-5 text-white">
                            <div class="flex items-start gap-3.5">
                                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-wine text-lg"><FontAwesomeIcon :icon="icons.user" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-semibold text-wine">{{ text.dashboard }}</p>
                                    <h3 class="mt-1 text-lg font-semibold">{{ text.welcome }}, {{ user.name }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-white/55">{{ text.dashboardHint }}</p>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-1.5 rounded-2xl bg-white/8 p-3 text-sm font-semibold text-white/70">
                                <p class="truncate">{{ user.email }}</p>
                                <p>{{ user.phone }}</p>
                            </div>
                            <div v-if="authError" class="note-wine mt-4">{{ authError }}</div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <button class="btn-wine" type="button" @click="openDashboard">
                                    {{ text.openDashboard }}
                                    <FontAwesomeIcon :icon="icons.arrowRight" />
                                </button>
                                <button class="btn-ghost" type="button" :disabled="isAuthLoading" @click="logout">{{ text.logout }}</button>
                            </div>
                        </section>
                    </div>

                    <div v-else class="space-y-4">
                        <form class="space-y-4" @submit.prevent="submit">
                            <div class="grid grid-cols-2 gap-1.5 rounded-full bg-ink/6 p-1 dark:bg-white/8">
                                <button class="h-9 rounded-full text-sm font-semibold transition" :class="mode === 'login' ? 'bg-wine text-white' : 'text-ink dark:text-white'" type="button" @click="mode = 'login'">{{ text.login }}</button>
                                <button class="h-9 rounded-full text-sm font-semibold transition" :class="mode === 'register' ? 'bg-wine text-white' : 'text-ink dark:text-white'" type="button" @click="mode = 'register'">{{ text.register }}</button>
                            </div>

                            <div class="panel-card">
                                <h3 class="text-lg font-semibold text-ink dark:text-white">{{ mode === 'login' ? text.login : text.register }}</h3>
                                <p class="mt-1 text-sm font-semibold text-ink/55 dark:text-white/55">{{ mode === 'login' ? text.loginHint : text.registerHint }}</p>
                                <div v-if="authError" class="note-wine mt-4">{{ authError }}</div>

                                <div class="mt-5 grid gap-3">
                                    <label v-if="mode === 'register'" class="field-label">{{ text.name }}<input v-model="form.name" required class="field-input" type="text"></label>
                                    <label v-if="mode === 'register'" class="field-label">{{ text.email }}<input v-model="form.email" required class="field-input" type="email"></label>
                                    <label v-if="mode === 'register'" class="field-label">{{ text.phone }}<input v-model="form.phone" required pattern="01[3-9][0-9]{8}" class="field-input" type="tel" placeholder="01712345678"></label>
                                    <label v-if="mode === 'login'" class="field-label">{{ text.identifier }}<input v-model="form.login" required class="field-input" type="text"></label>
                                    <label class="field-label">{{ text.password }}<input v-model="form.password" required minlength="8" class="field-input" type="password"></label>
                                    <label v-if="mode === 'register'" class="field-label">{{ text.confirmPassword }}<input v-model="form.password_confirmation" required minlength="8" class="field-input" type="password"></label>
                                    <label v-if="mode === 'login'" class="flex items-center gap-3 text-sm font-semibold text-ink dark:text-white"><input v-model="form.remember" class="size-4 accent-wine" type="checkbox">{{ text.remember }}</label>
                                </div>
                            </div>

                            <button class="btn-wine w-full" type="submit" :disabled="isAuthLoading">
                                {{ mode === 'login' ? text.submitLogin : text.submitRegister }}
                                <FontAwesomeIcon :icon="icons.arrowRight" />
                            </button>
                        </form>

                        <section class="panel-card">
                            <h3 class="text-lg font-semibold text-ink dark:text-white">{{ text.trackOrder }}</h3>
                            <div v-if="orderError" class="note-wine mt-4">{{ orderError }}</div>
                            <form class="mt-4 grid gap-3" @submit.prevent="submitTracking">
                                <label class="field-label">{{ text.orderNumber }}<input v-model="tracking.order_number" required class="field-input" type="text" placeholder="ORD-260829-ABC12345"></label>
                                <label class="field-label">{{ text.phone }}<input v-model="tracking.phone" required pattern="01[3-9][0-9]{8}" class="field-input" type="tel" placeholder="01712345678"></label>
                                <button class="btn-ink w-full" type="submit" :disabled="isOrderLoading">{{ text.submitTrack }}<FontAwesomeIcon :icon="icons.arrowRight" /></button>
                            </form>
                            <article v-if="trackedOrder" class="mt-4 overflow-hidden rounded-2xl bg-ink/5 dark:bg-white/8">
                                <div class="flex items-start justify-between gap-3 border-b border-black/8 p-4 dark:border-white/10">
                                    <div class="min-w-0">
                                        <strong class="block truncate text-sm font-semibold text-ink dark:text-white">{{ trackedOrder.order_number }}</strong>
                                        <p class="mt-1 text-[11px] font-semibold text-ink/50 dark:text-white/50">{{ text.placedOn }} {{ formatDate(trackedOrder.created_at) }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-wine/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-wine">{{ trackedOrder.status }}</span>
                                </div>
                                <div class="p-4">
                                    <div v-if="!isTerminalProblemStatus(trackedOrder)" class="grid grid-cols-5 gap-1">
                                        <div v-for="(statusStep, statusIndex) in text.statusSteps" :key="statusStep" class="min-w-0">
                                            <div class="h-1.5 rounded-full" :class="statusIndex <= statusStepIndex(trackedOrder) ? 'bg-wine' : 'bg-black/10 dark:bg-white/12'"></div>
                                            <p class="mt-1 truncate text-[9px] font-semibold text-ink/50 dark:text-white/50">{{ statusStep }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl bg-white p-3 dark:bg-white/8">
                                            <p class="text-[10px] font-semibold text-wine">{{ text.payment }}</p>
                                            <p class="mt-1 text-sm font-semibold uppercase text-ink dark:text-white">{{ trackedOrder.payment_method }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white p-3 dark:bg-white/8">
                                            <p class="text-[10px] font-semibold text-wine">{{ text.total }}</p>
                                            <p class="mt-1 text-sm font-semibold text-ink dark:text-white">{{ formatPrice(trackedOrder.grand_total) }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 rounded-2xl bg-white p-3 dark:bg-white/8">
                                        <p class="text-[10px] font-semibold text-wine">{{ text.deliveryTo }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs font-semibold leading-5 text-ink/55 dark:text-white/55">{{ orderAddress(trackedOrder) }}</p>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <div v-for="lineItem in trackedOrder.items" :key="lineItem.id" class="flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-2 text-xs dark:bg-white/8">
                                            <p class="line-clamp-1 min-w-0 font-semibold text-ink dark:text-white">{{ lineItem.product_name }}</p>
                                            <span class="shrink-0 font-semibold text-wine">{{ formatPrice(lineItem.line_total) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </section>
                    </div>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
