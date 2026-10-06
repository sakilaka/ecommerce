<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { copy, icons } = useStorefront();
const { sortHref } = useStorefrontLinks();
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="overflow-hidden rounded-[1.6rem] border border-black/10 dark:border-white/10 lg:grid lg:grid-cols-[0.32fr_1fr]">
                <aside class="relative overflow-hidden bg-ink px-6 py-7 text-white lg:px-7 lg:py-8">
                    <div class="pointer-events-none absolute -right-10 -top-10 size-36 rounded-full bg-wine/30 blur-3xl"></div>
                    <p class="relative inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.star" />
                        {{ copy.storiesSection.eyebrow }}
                    </p>
                    <p class="relative mt-5 text-5xl font-semibold leading-none">{{ copy.storiesSection.rating }}</p>
                    <p class="relative mt-2 text-xs font-semibold text-white/55">{{ copy.storiesSection.ratingText }}</p>
                    <div class="relative mt-5 flex flex-wrap gap-1.5">
                        <span
                            v-for="item in copy.storiesSection.proof"
                            :key="item"
                            class="rounded-full bg-white/8 px-2.5 py-1 text-[10px] font-semibold text-white/70"
                        >
                            {{ item }}
                        </span>
                    </div>
                </aside>

                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <h2 class="text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                            {{ copy.storiesSection.titleLineOne }}
                            <span class="text-wine">{{ copy.storiesSection.titleLineTwo }}</span>
                        </h2>
                        <a
                            :href="sortHref('featured')"
                            class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full bg-ink px-4 text-sm font-semibold text-white transition hover:bg-wine dark:bg-wine"
                        >
                            {{ copy.storiesSection.action }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </a>
                    </div>

                    <div class="mt-6 grid gap-3 md:grid-cols-3">
                        <article
                            v-for="review in copy.storiesSection.reviews"
                            :key="review.name"
                            class="rounded-[1.2rem] bg-[#141414] p-4 text-white"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="grid size-8 place-items-center rounded-full bg-white/10 text-xs">
                                    <FontAwesomeIcon :icon="icons.user" />
                                </span>
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-wine">
                                    <FontAwesomeIcon :icon="icons.star" />
                                    {{ review.rating }}
                                </span>
                            </div>
                            <p class="mt-3 text-[11px] font-semibold text-wine">{{ review.tag }}</p>
                            <blockquote class="mt-2 text-xs leading-5 text-white/65">“{{ review.quote }}”</blockquote>
                            <p class="mt-3 text-sm font-semibold">{{ review.name }}</p>
                            <p class="mt-0.5 text-[11px] text-white/45">{{ review.context }}</p>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
