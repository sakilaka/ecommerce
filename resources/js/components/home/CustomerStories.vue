<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { copy, icons } = useStorefront();
const { sortHref } = useStorefrontLinks();
const proofIcons = [icons.shield, icons.star, icons.user];
</script>

<template>
    <section class="bg-[#f6f6f6] py-14 transition-colors dark:bg-[#111111] sm:py-16 lg:py-20">
        <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
            <div class="grid gap-8 lg:grid-cols-[0.38fr_0.62fr] lg:items-end">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/75 px-4 py-2 text-xs font-black uppercase tracking-normal text-[#8f2638] shadow-sm ring-1 ring-black/5 dark:bg-white/10 dark:text-[#ff8aa0] dark:ring-white/10">
                        <FontAwesomeIcon :icon="icons.star" />
                        {{ copy.storiesSection.eyebrow }}
                    </p>
                    <h2 class="mt-6 max-w-xl font-serif text-4xl leading-[1.12] text-[#171512] dark:text-white sm:text-5xl lg:text-[3.3rem]">
                        <span class="block">{{ copy.storiesSection.titleLineOne }}</span>
                        <span class="block italic text-[#8f2638]">{{ copy.storiesSection.titleLineTwo }}</span>
                    </h2>
                </div>

                <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                    <p class="max-w-2xl text-base leading-8 text-[#374151] dark:text-[#e5e7eb]">
                        {{ copy.storiesSection.subtitle }}
                    </p>
                    <a :href="sortHref('featured')" class="inline-flex h-12 items-center justify-center gap-3 rounded-lg border border-black/10 bg-white/82 px-5 text-sm font-bold text-[#171512] shadow-sm transition hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15">
                        {{ copy.storiesSection.action }}
                        <FontAwesomeIcon :icon="icons.arrowRight" />
                    </a>
                </div>
            </div>

            <div class="mt-11 grid gap-5 lg:grid-cols-[0.36fr_0.64fr]">
                <aside class="rounded-lg bg-[#171512] p-6 text-white shadow-2xl shadow-black/15 dark:bg-[#9f243a] dark:text-white sm:p-8">
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-normal text-[#ff8aa0] dark:text-white">{{ copy.storiesSection.ratingText }}</p>
                            <p class="mt-5 font-serif text-7xl leading-none">{{ copy.storiesSection.rating }}</p>
                        </div>
                        <span class="grid size-14 place-items-center rounded-full bg-white/10 text-2xl text-[#ff8aa0] dark:bg-black/10 dark:text-white">
                            <FontAwesomeIcon :icon="icons.award" />
                        </span>
                    </div>

                    <div class="mt-8 flex gap-1 text-[#ff8aa0] dark:text-white" aria-label="Five star rating">
                        <FontAwesomeIcon v-for="star in 5" :key="star" :icon="icons.star" />
                    </div>

                    <div class="mt-9 grid gap-3">
                        <div v-for="(item, index) in copy.storiesSection.proof" :key="item" class="flex items-center gap-3 rounded-lg bg-white/8 p-3 dark:bg-black/8">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-[#9f243a]/15 text-[#ff8aa0] dark:bg-black/10 dark:text-white">
                                <FontAwesomeIcon :icon="proofIcons[index]" />
                            </span>
                            <p class="text-sm font-black">{{ item }}</p>
                        </div>
                    </div>
                </aside>

                <div class="grid gap-5 md:grid-cols-3">
                    <article v-for="review in copy.storiesSection.reviews" :key="review.name" class="flex min-h-80 flex-col justify-between rounded-lg bg-white/90 p-6 shadow-xl shadow-black/7 ring-1 ring-black/5 dark:bg-white/8 dark:ring-white/10">
                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="rounded-full bg-[#f3e6e9] px-3 py-1.5 text-xs font-black text-[#8f2638] dark:bg-white/10 dark:text-[#ff8aa0]">
                                    {{ review.tag }}
                                </span>
                                <FontAwesomeIcon :icon="icons.gem" class="text-[#8f2638]" />
                            </div>
                            <p class="mt-8 text-lg font-semibold leading-8 text-[#27272a] dark:text-white">
                                “{{ review.quote }}”
                            </p>
                        </div>

                        <div class="mt-8 flex items-center gap-4 border-t border-black/8 pt-5 dark:border-white/10">
                            <div class="grid size-12 place-items-center rounded-full bg-[#171512] text-sm font-black text-[#ff8aa0] dark:bg-[#9f243a] dark:text-white">
                                {{ review.name.slice(0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-[#171512] dark:text-white">{{ review.name }}</p>
                                <p class="mt-1 truncate text-xs text-[#374151] dark:text-[#e5e7eb]">{{ review.role }}</p>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
