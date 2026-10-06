<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { copy, icons } = useStorefront();
const { brandHref, sortHref } = useStorefrontLinks();
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.store" />
                        {{ copy.brandStores.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.brandStores.titleLineOne }}
                        <span class="text-wine">{{ copy.brandStores.titleLineTwo }}</span>
                    </h2>
                </div>
                <a
                    :href="sortHref('featured')"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full bg-ink px-4 text-sm font-semibold text-white transition hover:bg-wine dark:bg-wine"
                >
                    {{ copy.brandStores.action }}
                    <FontAwesomeIcon :icon="icons.arrowRight" />
                </a>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <a
                    v-for="(partner, index) in copy.brandStores.partners"
                    :key="partner.name"
                    :href="brandHref(partner.name)"
                    class="group flex items-center gap-3.5 overflow-hidden rounded-[1.4rem] bg-[#141414] p-3.5 shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)] animate-card-in transition duration-300 hover:-translate-y-1"
                    :style="{ animationDelay: `${(index % 3) * 70}ms` }"
                >
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-[radial-gradient(circle_at_50%_40%,rgb(143_38_56_/_0.8),rgb(20_20_20)_72%)] text-lg font-semibold text-white ring-1 ring-white/10">
                        {{ partner.initial }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="truncate text-sm font-semibold text-white">{{ partner.name }}</span>
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-wine/20 px-2 py-0.5 text-[10px] font-semibold text-wine">
                                <FontAwesomeIcon :icon="icons.shield" class="text-[9px]" />
                                {{ copy.brandStores.verifyLabel }}
                            </span>
                        </span>
                        <span class="mt-1 block truncate text-[11px] text-white/50">{{ partner.category }} · {{ partner.offer }}</span>
                        <span class="mt-1 block truncate text-[11px] text-white/40">{{ partner.trust }}</span>
                    </span>
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-wine text-xs text-white transition group-hover:bg-white group-hover:text-ink">
                        <FontAwesomeIcon :icon="icons.arrowRight" />
                    </span>
                </a>
            </div>
        </div>
    </section>
</template>
