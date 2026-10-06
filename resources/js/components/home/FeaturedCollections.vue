<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { categoryCollageImage, copy, icons } = useStorefront();
const { categoryHref, sortHref } = useStorefrontLinks();
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.store" />
                        {{ copy.collectionSection.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.collectionSection.titleLineOne }}
                        <span class="text-wine">{{ copy.collectionSection.titleLineTwo }}</span>
                    </h2>
                </div>
                <a
                    :href="sortHref('featured')"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full bg-ink px-4 text-sm font-semibold text-white transition hover:bg-wine dark:bg-wine"
                >
                    {{ copy.collectionSection.action }}
                    <FontAwesomeIcon :icon="icons.arrowRight" />
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <a
                    v-for="(card, index) in copy.collectionSection.cards"
                    :key="card.title"
                    :href="categoryHref(card.title)"
                    class="group overflow-hidden rounded-3xl bg-ink shadow-[0_22px_40px_-24px_rgb(17_17_17_/_0.7)] ring-1 ring-black/20 animate-card-in transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_50px_-22px_rgb(143_38_56_/_0.45)]"
                    :style="{ animationDelay: `${index * 80}ms` }"
                >
                    <div class="relative p-2.5 pb-0">
                        <span class="pointer-events-none absolute -right-4 -top-6 size-20 rounded-full border border-white/10"></span>
                        <span class="pointer-events-none absolute -left-5 bottom-6 size-16 rounded-full border border-wine/30"></span>

                        <div class="relative aspect-square overflow-hidden rounded-2xl bg-wine-deep">
                            <div
                                class="absolute inset-0 bg-no-repeat transition duration-500 group-hover:scale-105"
                                :style="{
                                    backgroundImage: 'url(' + categoryCollageImage + ')',
                                    backgroundSize: '400% 200%',
                                    backgroundPosition: card.position,
                                }"
                            ></div>
                        </div>

                        <span class="absolute right-4 top-4 rounded-full bg-wine px-2.5 py-1 text-[10px] font-semibold text-white shadow-md">
                            {{ card.badge }}
                        </span>
                    </div>

                    <div class="px-4 pb-4 pt-3">
                        <h3 class="text-sm font-semibold leading-snug text-white">{{ card.title }}</h3>
                        <span class="mt-3 inline-flex h-8 items-center gap-2 rounded-full bg-wine px-3 text-[11px] font-semibold text-white transition group-hover:bg-white group-hover:text-ink">
                            {{ card.cta }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </section>
</template>
