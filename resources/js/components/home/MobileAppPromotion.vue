<script setup>
import { computed, onMounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, heroImage, icons } = useStorefront();
const platform = ref('web');

onMounted(() => {
    const userAgent = window.navigator.userAgent.toLowerCase();

    if (/iphone|ipad|ipod/.test(userAgent)) {
        platform.value = 'ios';
        return;
    }

    if (/android/.test(userAgent)) {
        platform.value = 'android';
    }
});

const storeHref = (store) => store.platform === 'android'
    ? 'https://play.google.com/store'
    : 'https://www.apple.com/app-store/';

const visibleStores = computed(() => {
    if (platform.value === 'web') {
        return copy.value.mobileAppSection.stores;
    }

    return copy.value.mobileAppSection.stores.filter((store) => store.platform === platform.value);
});
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="relative overflow-hidden rounded-[1.6rem] bg-ink text-white shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_82%_40%,rgb(143_38_56_/_0.72),transparent_46%)]"></div>
                <img :src="heroImage" alt="" class="pointer-events-none absolute inset-y-0 right-0 hidden h-full w-[38%] object-cover opacity-25 lg:block">

                <div class="relative grid gap-6 p-5 sm:p-7 lg:grid-cols-[1fr_auto] lg:items-center lg:p-8">
                    <div class="max-w-2xl">
                        <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                            <FontAwesomeIcon :icon="icons.cart" />
                            {{ copy.mobileAppSection.eyebrow }}
                        </p>
                        <h2 class="mt-3 text-3xl font-semibold leading-snug sm:text-4xl">
                            {{ copy.mobileAppSection.titleLineOne }}
                            <span class="text-wine">{{ copy.mobileAppSection.titleLineTwo }}</span>
                        </h2>
                        <p class="mt-3 max-w-xl text-sm leading-7 text-white/65">
                            {{ copy.mobileAppSection.subtitle }}
                        </p>

                        <div class="mt-5 grid gap-2 sm:grid-cols-3">
                            <div v-for="benefit in copy.mobileAppSection.benefits" :key="benefit.title" class="rounded-2xl bg-white/8 px-4 py-3 ring-1 ring-white/10">
                                <span class="grid size-8 place-items-center rounded-full bg-wine text-xs">
                                    <FontAwesomeIcon :icon="icons[benefit.icon]" />
                                </span>
                                <h3 class="mt-2 text-sm font-semibold">{{ benefit.title }}</h3>
                                <p class="mt-1 text-[11px] leading-5 text-white/55">{{ benefit.text }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-3 lg:w-64">
                        <div class="rounded-2xl bg-white p-4 text-ink">
                            <p class="text-sm font-semibold">{{ copy.mobileAppSection.qrLabel }}</p>
                            <p class="mt-1 text-[11px] leading-5 text-ink/55">{{ copy.mobileAppSection.storeNote }}</p>
                        </div>
                        <a
                            v-for="store in visibleStores"
                            :key="store.platform"
                            :href="storeHref(store)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-11 items-center gap-3 rounded-full bg-wine px-4 text-white transition hover:bg-white hover:text-ink"
                        >
                            <FontAwesomeIcon :icon="icons.cart" />
                            <span class="leading-tight">
                                <span class="block text-[10px] font-semibold uppercase tracking-wide opacity-80">{{ store.label }}</span>
                                <span class="block text-sm font-semibold">{{ store.name }}</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
