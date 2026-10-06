<script setup>
import { computed, reactive, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons, locale } = useStorefront();
const benefitIcons = [icons.gift, icons.clock, icons.bag];
const isSubmitting = ref(false);
const isSubscribed = ref(false);
const error = ref(null);
const form = reactive({ email: '', phone: '', consent: false });

const message = computed(() => {
    if (error.value) {
        return error.value;
    }

    if (isSubscribed.value) {
        return locale.value === 'bn'
            ? 'ধন্যবাদ। আপনার offer preference সংরক্ষণ করা হয়েছে।'
            : 'Thanks. Your offer preference has been saved.';
    }

    return copy.value.membershipSection.note;
});

const submitNewsletter = async () => {
    isSubmitting.value = true;
    isSubscribed.value = false;
    error.value = null;

    try {
        const response = await fetch('/api/storefront/newsletter-leads', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: form.email || null,
                phone: form.phone || null,
                source: 'homepage',
                locale: locale.value,
                interest: 'offers-newsletter',
                consent: form.consent,
            }),
        });
        const payload = await response.json();

        if (!response.ok) {
            const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(firstError || payload?.message || 'Subscription failed.');
        }

        isSubscribed.value = true;
        form.email = '';
        form.phone = '';
        form.consent = false;
    } catch (requestError) {
        error.value = requestError.message;
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="overflow-hidden rounded-[1.6rem] border border-black/10 dark:border-white/10 lg:grid lg:grid-cols-[1fr_0.9fr]">
                <div class="p-5 sm:p-7">
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.gift" />
                        {{ copy.membershipSection.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.membershipSection.titleLineOne }}
                        <span class="text-wine">{{ copy.membershipSection.titleLineTwo }}</span>
                    </h2>
                    <p class="mt-3 max-w-xl text-sm leading-7 text-ink/55 dark:text-white/55">
                        {{ copy.membershipSection.subtitle }}
                    </p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div v-for="(benefit, index) in copy.membershipSection.benefits" :key="benefit.title">
                            <span class="grid size-8 place-items-center rounded-full bg-ink text-xs text-white dark:bg-wine">
                                <FontAwesomeIcon :icon="benefitIcons[index]" />
                            </span>
                            <h3 class="mt-2 text-sm font-semibold text-ink dark:text-white">{{ benefit.title }}</h3>
                            <p class="mt-1 text-[11px] leading-5 text-ink/55 dark:text-white/55">{{ benefit.text }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-ink p-5 text-white sm:p-7">
                    <form class="grid gap-3" @submit.prevent="submitNewsletter">
                        <input
                            v-model="form.email"
                            class="h-11 rounded-full border-0 bg-white px-4 text-sm font-semibold text-ink outline-none"
                            type="email"
                            :placeholder="copy.membershipSection.emailPlaceholder"
                        >
                        <input
                            v-model="form.phone"
                            class="h-11 rounded-full border-0 bg-white px-4 text-sm font-semibold text-ink outline-none"
                            type="tel"
                            :placeholder="copy.membershipSection.phonePlaceholder"
                        >
                        <label class="flex items-start gap-3 text-xs font-semibold leading-5 text-white/65">
                            <input v-model="form.consent" class="mt-0.5 size-4 accent-wine" type="checkbox">
                            <span>{{ copy.membershipSection.consent }}</span>
                        </label>
                        <button
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-wine px-5 text-sm font-semibold text-white transition hover:bg-white hover:text-ink disabled:opacity-60"
                            type="submit"
                            :disabled="isSubmitting"
                        >
                            {{ isSubmitting ? (locale === 'bn' ? 'সেভ হচ্ছে...' : 'Saving...') : copy.membershipSection.action }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </button>
                    </form>

                    <p class="mt-4 text-xs font-semibold leading-5" :class="error ? 'text-white' : 'text-white/55'">{{ message }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <div v-for="stat in copy.membershipSection.stats" :key="stat.label" class="rounded-2xl bg-white/8 px-4 py-3">
                            <p class="text-xl font-semibold">{{ stat.value }}</p>
                            <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-white/50">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
