<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { categoryCollageImage, copy, icons } = useStorefront();
const { collectionHref } = useStorefrontLinks();
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.gift" />
                        {{ copy.offersSection.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.offersSection.titleLineOne }}
                        <span class="text-wine">{{ copy.offersSection.titleLineTwo }}</span>
                    </h2>
                </div>
                <a
                    :href="collectionHref(copy.offersSection.eyebrow)"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full bg-ink px-4 text-sm font-semibold text-white transition hover:bg-wine dark:bg-wine"
                >
                    {{ copy.offersSection.action }}
                    <FontAwesomeIcon :icon="icons.arrowRight" />
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
                <a
                    v-for="(collection, index) in copy.offersSection.collections"
                    :key="collection.title"
                    :href="collectionHref(collection.title)"
                    class="group overflow-hidden rounded-[1.4rem] bg-[#141414] shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)] animate-card-in transition duration-300 hover:-translate-y-1"
                    :style="{ animationDelay: `${index * 70}ms` }"
                >
                    <div class="relative p-2.5 pb-0">
                        <div class="relative aspect-square overflow-hidden rounded-2xl bg-wine-deep">
                            <div
                                class="absolute inset-0 bg-no-repeat transition duration-500 group-hover:scale-105"
                                :style="{
                                    backgroundImage: 'url(' + categoryCollageImage + ')',
                                    backgroundSize: '400% 200%',
                                    backgroundPosition: collection.position,
                                }"
                            ></div>
                        </div>
                        <span class="absolute right-4 top-4 rounded-full bg-wine px-2.5 py-1 text-[10px] font-semibold text-white">
                            {{ collection.window }}
                        </span>
                    </div>
                    <div class="px-4 pb-4 pt-3">
                        <h3 class="text-sm font-semibold leading-snug text-white">{{ collection.title }}</h3>
                        <p class="mt-1 line-clamp-2 text-[11px] leading-5 text-white/50">{{ collection.text }}</p>
                    </div>
                </a>
            </div>
        </div>
    </section>
</template>
