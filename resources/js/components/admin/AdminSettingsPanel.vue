<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { icons, locale } = useStorefront();

const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const success = ref(null);
const validationErrors = ref({});
const paymentReadiness = ref(null);

const form = reactive({
    identity: {
        store_name: '',
        tagline: '',
        support_phone: '',
        support_email: '',
        address: '',
    },
    checkout: {
        cod_enabled: true,
        sslcommerz_enabled: false,
        bkash_enabled: false,
        nagad_enabled: false,
        card_enabled: false,
        bkash_merchant_number: '',
        bkash_account_type: 'merchant',
        nagad_merchant_number: '',
        nagad_account_type: 'merchant',
    },
    delivery: {
        free_delivery_threshold: 0,
        inside_dhaka_charge: 0,
        outside_dhaka_charge: 0,
        cod_charge: 0,
    },
    returns: {
        return_window_days: 0,
        refund_support_enabled: true,
    },
    operations: {
        overselling_allowed: true,
        auto_confirm_cod_orders: false,
        low_stock_threshold: 0,
    },
});

const text = computed(() => locale.value === 'bn' ? {
    eyebrow: 'স্টোর সেটিংস',
    title: 'স্টোর কনফিগারেশন',
    subtitle: 'সাপোর্ট, পেমেন্ট, ডেলিভারি, রিটার্ন এবং অপারেশন নিয়ম এক জায়গা থেকে নিয়ন্ত্রণ করুন।',
    loadError: 'সেটিংস লোড করা যায়নি।',
    saveError: 'সেটিংস সেভ করা যায়নি।',
    saved: 'সেটিংস আপডেট হয়েছে।',
    saving: 'সেভ হচ্ছে...',
    save: 'Save Settings',
    identity: 'স্টোর পরিচিতি',
    checkout: 'চেকআউট ও পেমেন্ট',
    delivery: 'ডেলিভারি চার্জ',
    returns: 'রিটার্ন পলিসি',
    operations: 'অপারেশন',
    storeName: 'স্টোর নাম',
    tagline: 'ট্যাগলাইন',
    supportPhone: 'সাপোর্ট ফোন',
    supportEmail: 'সাপোর্ট ইমেইল',
    address: 'অ্যাড্রেস',
    cod: 'Cash on Delivery',
    sslcommerz: 'SSLCommerz',
    bkash: 'bKash',
    nagad: 'Nagad',
    card: 'Card Payment',
    merchantNumber: 'মার্চেন্ট নম্বর',
    accountType: 'অ্যাকাউন্ট টাইপ',
    bkashSetup: 'bKash সেটআপ',
    nagadSetup: 'Nagad সেটআপ',
    paymentHint: 'Checkout এ যেসব method দেখাবেন সেগুলো চালু করুন। bKash/Nagad নম্বর customer payment instruction এ দেখাবে।',
    freeThreshold: 'ফ্রি ডেলিভারি অর্ডার ভ্যালু',
    insideDhaka: 'ঢাকার ভিতরে',
    outsideDhaka: 'ঢাকার বাইরে',
    codCharge: 'COD চার্জ',
    returnWindow: 'রিটার্ন উইন্ডো দিন',
    refundSupport: 'রিফান্ড সাপোর্ট',
    overselling: 'Overselling allowed',
    autoConfirm: 'COD auto confirm',
    lowStock: 'ডিফল্ট লো স্টক',
    takaHint: 'টাকার হিসাবে লিখুন, সিস্টেম পয়সায় সেভ করবে।',
    days: 'দিন',
    enabled: 'চালু',
    readiness: 'পেমেন্ট রেডিনেস',
    readinessSubtitle: 'Checkout চালু করার আগে payment setup status দেখুন।',
    ready: 'Ready',
    needsSetup: 'Setup দরকার',
    disabled: 'Disabled',
    missing: 'Missing',
    sandbox: 'Sandbox mode',
} : {
    eyebrow: 'Store Settings',
    title: 'Store Configuration',
    subtitle: 'Control support details, payments, delivery charges, return policy, and operations from one clean workspace.',
    loadError: 'Could not load settings.',
    saveError: 'Could not save settings.',
    saved: 'Settings updated.',
    saving: 'Saving...',
    save: 'Save Settings',
    identity: 'Store Identity',
    checkout: 'Checkout & Payments',
    delivery: 'Delivery Charges',
    returns: 'Return Policy',
    operations: 'Operations',
    storeName: 'Store name',
    tagline: 'Tagline',
    supportPhone: 'Support phone',
    supportEmail: 'Support email',
    address: 'Address',
    cod: 'Cash on Delivery',
    sslcommerz: 'SSLCommerz',
    bkash: 'bKash',
    nagad: 'Nagad',
    card: 'Card Payment',
    merchantNumber: 'Merchant number',
    accountType: 'Account type',
    bkashSetup: 'bKash setup',
    nagadSetup: 'Nagad setup',
    paymentHint: 'Enable methods shown at checkout. bKash/Nagad numbers appear in customer payment instructions.',
    freeThreshold: 'Free delivery order value',
    insideDhaka: 'Inside Dhaka',
    outsideDhaka: 'Outside Dhaka',
    codCharge: 'COD charge',
    returnWindow: 'Return window days',
    refundSupport: 'Refund support',
    overselling: 'Overselling allowed',
    autoConfirm: 'COD auto confirm',
    lowStock: 'Default low stock',
    takaHint: 'Enter values in taka; the system stores them as paisa.',
    days: 'days',
    enabled: 'Enabled',
    readiness: 'Payment Readiness',
    readinessSubtitle: 'Check payment setup status before taking checkout live.',
    ready: 'Ready',
    needsSetup: 'Needs setup',
    disabled: 'Disabled',
    missing: 'Missing',
    sandbox: 'Sandbox mode',
});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const moneyFields = ['free_delivery_threshold', 'inside_dhaka_charge', 'outside_dhaka_charge', 'cod_charge'];
const firstError = (name) => validationErrors.value?.[name]?.[0] ?? null;
const toTaka = (amount) => Math.round(Number(amount || 0) / 100);
const toPaisa = (amount) => Math.round(Number(amount || 0) * 100);

const assignSettings = (settings) => {
    Object.assign(form.identity, settings.identity ?? {});
    Object.assign(form.checkout, settings.checkout ?? {});
    Object.assign(form.returns, settings.returns ?? {});
    Object.assign(form.operations, settings.operations ?? {});

    const delivery = settings.delivery ?? {};
    moneyFields.forEach((field) => {
        form.delivery[field] = toTaka(delivery[field]);
    });
};

const payload = () => ({
    identity: { ...form.identity },
    checkout: {
        ...form.checkout,
        bkash_merchant_number: form.checkout.bkash_merchant_number || null,
        bkash_account_type: form.checkout.bkash_account_type || null,
        nagad_merchant_number: form.checkout.nagad_merchant_number || null,
        nagad_account_type: form.checkout.nagad_account_type || null,
    },
    delivery: Object.fromEntries(moneyFields.map((field) => [field, toPaisa(form.delivery[field])])),
    returns: {
        return_window_days: Number(form.returns.return_window_days || 0),
        refund_support_enabled: Boolean(form.returns.refund_support_enabled),
    },
    operations: {
        overselling_allowed: Boolean(form.operations.overselling_allowed),
        auto_confirm_cod_orders: Boolean(form.operations.auto_confirm_cod_orders),
        low_stock_threshold: Number(form.operations.low_stock_threshold || 0),
    },
});

const readinessMethods = computed(() => Object.entries(paymentReadiness.value?.methods ?? {}).map(([key, method]) => ({ key, ...method })));
const readinessStatusLabel = (status) => ({ ready: text.value.ready, needs_setup: text.value.needsSetup, disabled: text.value.disabled }[status] ?? status);
const readinessStatusClass = (status) => ({
    ready: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300',
    needs_setup: 'bg-[#8f2638]/10 text-[#8f2638] ring-[#8f2638]/20 dark:text-[#ffb1bf]',
    disabled: 'bg-black/5 text-[#4b5563] ring-black/8 dark:bg-white/8 dark:text-white/55 dark:ring-white/10',
}[status] ?? 'bg-black/5 text-[#4b5563] ring-black/8');
const localizedReadinessLabel = (method) => locale.value === 'bn' ? method.label_bn : method.label;

const loadSettings = async () => {
    isLoading.value = true;
    error.value = null;
    validationErrors.value = {};

    try {
        const response = await fetch('/api/admin/settings', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data?.message ?? text.value.loadError);
        }

        assignSettings(data.data);
        paymentReadiness.value = data.payment_readiness ?? null;
    } catch (settingsError) {
        error.value = settingsError.message;
    } finally {
        isLoading.value = false;
    }
};

const saveSettings = async () => {
    isSaving.value = true;
    error.value = null;
    success.value = null;
    validationErrors.value = {};

    try {
        const response = await fetch('/api/admin/settings', {
            method: 'PATCH',
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
            validationErrors.value = data?.errors ?? {};
            throw new Error(data?.message ?? text.value.saveError);
        }

        assignSettings(data.data);
        paymentReadiness.value = data.payment_readiness ?? null;
        success.value = data?.message ?? text.value.saved;
    } catch (settingsError) {
        error.value = settingsError.message;
    } finally {
        isSaving.value = false;
    }
};

const fieldClass = 'h-11 rounded-lg border-0 bg-white px-3 text-sm font-bold outline-none ring-1 ring-black/8 placeholder:text-[#6b7280] focus:ring-2 focus:ring-[#8f2638] dark:bg-white/10 dark:text-white dark:ring-white/10 dark:focus:ring-[#9f243a]';
const toggleClass = 'flex min-h-14 items-center justify-between gap-3 rounded-lg bg-white px-4 py-3 text-left ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10';

onMounted(loadSettings);
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(360px,0.55fr)]">
        <div class="rounded-lg bg-white/86 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10 sm:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.eyebrow }}</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl">{{ text.title }}</h2>
                    <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.subtitle }}</p>
                </div>
                <button class="h-11 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white shadow-lg shadow-[#8f2638]/18 disabled:opacity-60 dark:bg-[#9f243a] dark:text-white" type="button" :disabled="isSaving || isLoading" @click="saveSettings">
                    {{ isSaving ? text.saving : text.save }}
                </button>
            </div>

            <div v-if="error" class="mt-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ error }}</div>
            <div v-if="success" class="mt-5 rounded-lg border border-[#8f2638]/20 bg-[#8f2638]/8 px-4 py-3 text-sm font-bold text-[#8f2638] dark:border-[#9f243a]/30 dark:bg-[#9f243a]/10 dark:text-[#ff8aa0]">{{ success }}</div>

            <form class="mt-6 grid gap-5" @submit.prevent="saveSettings">
                <section class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10 sm:p-5">
                    <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.store" /></span><h3 class="text-lg font-black">{{ text.identity }}</h3></div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.storeName }}</span><input v-model="form.identity.store_name" :class="fieldClass" type="text"><small v-if="firstError('identity.store_name')" class="text-[#8f2638]">{{ firstError('identity.store_name') }}</small></label>
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.tagline }}</span><input v-model="form.identity.tagline" :class="fieldClass" type="text"></label>
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.supportPhone }}</span><input v-model="form.identity.support_phone" :class="fieldClass" type="text"><small v-if="firstError('identity.support_phone')" class="text-[#8f2638]">{{ firstError('identity.support_phone') }}</small></label>
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.supportEmail }}</span><input v-model="form.identity.support_email" :class="fieldClass" type="email"><small v-if="firstError('identity.support_email')" class="text-[#8f2638]">{{ firstError('identity.support_email') }}</small></label>
                        <label class="grid gap-1 text-sm font-bold sm:col-span-2"><span>{{ text.address }}</span><input v-model="form.identity.address" :class="fieldClass" type="text"></label>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10 sm:p-5">
                        <div class="flex items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-[#8f2638] text-white dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.cart" /></span><div><h3 class="text-lg font-black">{{ text.checkout }}</h3><p class="mt-1 text-xs font-bold leading-5 text-[#4b5563] dark:text-[#d1d5db]">{{ text.paymentHint }}</p></div></div>
                        <div class="mt-4 grid gap-2">
                            <label v-for="field in [['cod_enabled', text.cod], ['sslcommerz_enabled', text.sslcommerz], ['bkash_enabled', text.bkash], ['nagad_enabled', text.nagad], ['card_enabled', text.card]]" :key="field[0]" :class="toggleClass"><span class="text-sm font-black">{{ field[1] }}</span><input v-model="form.checkout[field[0]]" class="size-4 accent-[#8f2638]" type="checkbox"></label>
                        </div>
                        <div class="mt-4 grid gap-3 rounded-lg bg-white/75 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.bkashSetup }}</p>
                            <label class="grid gap-1 text-sm font-bold"><span>{{ text.merchantNumber }}</span><input v-model="form.checkout.bkash_merchant_number" :class="fieldClass" inputmode="tel" type="text"><small v-if="firstError('checkout.bkash_merchant_number')" class="text-[#8f2638]">{{ firstError('checkout.bkash_merchant_number') }}</small></label>
                            <label class="grid gap-1 text-sm font-bold"><span>{{ text.accountType }}</span><input v-model="form.checkout.bkash_account_type" :class="fieldClass" type="text"><small v-if="firstError('checkout.bkash_account_type')" class="text-[#8f2638]">{{ firstError('checkout.bkash_account_type') }}</small></label>
                        </div>
                        <div class="mt-3 grid gap-3 rounded-lg bg-white/75 p-3 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                            <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.nagadSetup }}</p>
                            <label class="grid gap-1 text-sm font-bold"><span>{{ text.merchantNumber }}</span><input v-model="form.checkout.nagad_merchant_number" :class="fieldClass" inputmode="tel" type="text"><small v-if="firstError('checkout.nagad_merchant_number')" class="text-[#8f2638]">{{ firstError('checkout.nagad_merchant_number') }}</small></label>
                            <label class="grid gap-1 text-sm font-bold"><span>{{ text.accountType }}</span><input v-model="form.checkout.nagad_account_type" :class="fieldClass" type="text"><small v-if="firstError('checkout.nagad_account_type')" class="text-[#8f2638]">{{ firstError('checkout.nagad_account_type') }}</small></label>
                        </div>
                    </div>

                    <div class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10 sm:p-5">
                        <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-lg bg-[#8f2638] text-white dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.returns" /></span><h3 class="text-lg font-black">{{ text.returns }}</h3></div>
                        <div class="mt-4 grid gap-3">
                            <label class="grid gap-1 text-sm font-bold"><span>{{ text.returnWindow }}</span><div class="flex items-center rounded-lg bg-white ring-1 ring-black/8 dark:bg-white/10 dark:ring-white/10"><input v-model="form.returns.return_window_days" class="h-11 min-w-0 flex-1 bg-transparent px-3 font-bold outline-none dark:text-white" type="number" min="0"><span class="px-3 text-xs font-black text-[#4b5563] dark:text-[#d1d5db]">{{ text.days }}</span></div></label>
                            <label :class="toggleClass"><span class="text-sm font-black">{{ text.refundSupport }}</span><input v-model="form.returns.refund_support_enabled" class="size-4 accent-[#8f2638]" type="checkbox"></label>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-[#f6f6f6] p-4 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10 sm:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-lg bg-[#9f243a] text-white"><FontAwesomeIcon :icon="icons.truck" /></span><h3 class="text-lg font-black">{{ text.delivery }}</h3></div><p class="text-xs font-bold text-[#4b5563] dark:text-[#d1d5db]">{{ text.takaHint }}</p></div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.freeThreshold }}</span><input v-model="form.delivery.free_delivery_threshold" :class="fieldClass" type="number" min="0"><small v-if="firstError('delivery.free_delivery_threshold')" class="text-[#8f2638]">{{ firstError('delivery.free_delivery_threshold') }}</small></label>
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.insideDhaka }}</span><input v-model="form.delivery.inside_dhaka_charge" :class="fieldClass" type="number" min="0"></label>
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.outsideDhaka }}</span><input v-model="form.delivery.outside_dhaka_charge" :class="fieldClass" type="number" min="0"></label>
                        <label class="grid gap-1 text-sm font-bold"><span>{{ text.codCharge }}</span><input v-model="form.delivery.cod_charge" :class="fieldClass" type="number" min="0"></label>
                    </div>
                </section>
            </form>
        </div>

        <aside class="grid content-start gap-5">
            <section class="rounded-lg bg-white/86 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">{{ text.readiness }}</p>
                        <p class="mt-2 text-sm font-bold leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ text.readinessSubtitle }}</p>
                    </div>
                    <span v-if="paymentReadiness" class="grid size-11 shrink-0 place-items-center rounded-lg bg-[#171512] text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white"><FontAwesomeIcon :icon="icons.shield" /></span>
                </div>
                <div v-if="paymentReadiness" class="mt-4 grid gap-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#6b7280] dark:text-[#d1d5db]">{{ text.enabled }}</p><p class="mt-1 text-xl font-black">{{ paymentReadiness.ready_count }} / {{ paymentReadiness.enabled_count }}</p></div>
                        <div class="rounded-lg bg-[#f6f6f6] p-3 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10"><p class="text-[10px] font-black uppercase text-[#6b7280] dark:text-[#d1d5db]">{{ text.sandbox }}</p><p class="mt-1 text-xl font-black">{{ paymentReadiness.sandbox ? 'On' : 'Off' }}</p></div>
                    </div>
                    <article v-for="method in readinessMethods" :key="method.key" class="rounded-lg bg-[#ffffff] p-3 ring-1 ring-black/5 dark:bg-black/20 dark:ring-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-black">{{ localizedReadinessLabel(method) }}</p>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1" :class="readinessStatusClass(method.status)">{{ readinessStatusLabel(method.status) }}</span>
                        </div>
                        <p v-if="method.missing?.length" class="mt-2 text-xs font-bold leading-5 text-[#8f2638] dark:text-[#ffb1bf]">{{ text.missing }}: {{ method.missing.join(', ') }}</p>
                    </article>
                </div>
            </section>

            <section class="rounded-lg bg-[#171512] p-5 text-white shadow-xl shadow-black/10 dark:bg-white/10 dark:ring-1 dark:ring-white/10">
                <p class="text-xs font-black uppercase text-[#ff8aa0]">{{ text.operations }}</p>
                <div class="mt-4 grid gap-3">
                    <label :class="toggleClass"><span class="text-sm font-black text-[#171512] dark:text-white">{{ text.overselling }}</span><input v-model="form.operations.overselling_allowed" class="size-4 accent-[#9f243a]" type="checkbox"></label>
                    <label :class="toggleClass"><span class="text-sm font-black text-[#171512] dark:text-white">{{ text.autoConfirm }}</span><input v-model="form.operations.auto_confirm_cod_orders" class="size-4 accent-[#9f243a]" type="checkbox"></label>
                    <label class="grid gap-1 text-sm font-bold"><span>{{ text.lowStock }}</span><input v-model="form.operations.low_stock_threshold" class="h-11 rounded-lg border-0 bg-white/10 px-3 outline-none ring-1 ring-white/10 focus:ring-2 focus:ring-[#9f243a]" type="number" min="0"></label>
                </div>
            </section>

            <section class="rounded-lg bg-white/86 p-5 shadow-xl shadow-black/6 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                <div class="flex items-center justify-between gap-3"><p class="text-sm font-black">{{ text.checkout }}</p><span class="rounded-full bg-[#8f2638]/10 px-3 py-1 text-xs font-black text-[#8f2638] dark:bg-[#9f243a]/15 dark:text-[#ff8aa0]">{{ text.enabled }}</span></div>
                <div class="mt-4 grid gap-2 text-sm font-bold text-[#374151] dark:text-[#e5e7eb]">
                    <p>COD: {{ form.checkout.cod_enabled ? 'On' : 'Off' }}</p>
                    <p>bKash: {{ form.checkout.bkash_enabled ? form.checkout.bkash_merchant_number || 'On' : 'Off' }}</p>
                    <p>Nagad: {{ form.checkout.nagad_enabled ? form.checkout.nagad_merchant_number || 'On' : 'Off' }}</p>
                    <p>Cards: {{ form.checkout.card_enabled || form.checkout.sslcommerz_enabled ? 'On' : 'Off' }}</p>
                </div>
            </section>
        </aside>
    </section>
</template>
