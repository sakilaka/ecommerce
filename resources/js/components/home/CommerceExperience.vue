<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { copy, icons } = useStorefront();
const { sortHref } = useStorefrontLinks();
const panelIcons = [icons.search, icons.cart, icons.chart];
const stepIcons = [icons.search, icons.bag, icons.cart, icons.truck];
</script>

<template>
    <section class="bg-[#f6f6f6] py-14 transition-colors dark:bg-[#111111] sm:py-16 lg:py-20">
        <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
            <div class="overflow-hidden rounded-lg bg-[#171512] text-white shadow-2xl shadow-black/15 dark:bg-[#9f243a] dark:text-white">
                <div class="grid lg:grid-cols-[0.42fr_0.58fr]">
                    <div class="p-6 sm:p-8 lg:p-10">
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-normal text-[#ff8aa0] dark:border-black/10 dark:bg-black/10 dark:text-white">
                            <FontAwesomeIcon :icon="icons.crown" />
                            {{ copy.atelierSection.eyebrow }}
                        </p>
                        <h2 class="mt-7 max-w-lg font-serif text-4xl leading-tight sm:text-5xl lg:text-[3.35rem]">
                            <span class="block">{{ copy.atelierSection.titleLineOne }}</span>
                            <span class="block italic text-[#ff8aa0] dark:text-white">{{ copy.atelierSection.titleLineTwo }}</span>
                        </h2>
                        <p class="mt-6 max-w-lg text-base leading-8 text-white/75 dark:text-white/76">
                            {{ copy.atelierSection.subtitle }}
                        </p>
                        <a :href="sortHref('featured')" class="mt-8 inline-flex h-12 items-center justify-center gap-3 rounded-lg bg-white px-6 text-sm font-bold text-[#171512] transition hover:bg-[#fff5f6] dark:bg-[#171512] dark:text-white dark:hover:bg-[#27272a]">
                            {{ copy.atelierSection.action }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </a>
                    </div>

                    <div class="grid gap-px bg-white/10 p-px dark:bg-black/10 sm:grid-cols-3">
                        <article v-for="(panel, index) in copy.atelierSection.panels" :key="panel.title" class="bg-[#27272a] p-6 dark:bg-[#fff5f6] sm:p-7 lg:min-h-[28rem]">
                            <div class="flex items-center justify-between gap-5">
                                <span class="grid size-12 place-items-center rounded-lg bg-white/10 text-[#ff8aa0] dark:bg-black/10 dark:text-white">
                                    <FontAwesomeIcon :icon="panelIcons[index]" />
                                </span>
                                <span class="font-serif text-5xl text-white/10 dark:text-black/10">{{ panel.stat }}</span>
                            </div>
                            <h3 class="mt-16 text-2xl font-black leading-tight sm:mt-20">{{ panel.title }}</h3>
                            <p class="mt-4 text-sm leading-7 text-white/66 dark:text-white/70">{{ panel.text }}</p>
                        </article>
                    </div>
                </div>
            </div>

            <div class="mx-auto mt-8 max-w-5xl rounded-lg bg-white/88 p-3 shadow-2xl shadow-black/8 ring-1 ring-black/5 backdrop-blur dark:bg-white/8 dark:ring-white/10 sm:p-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(step, index) in copy.atelierSection.steps"
                        :key="step"
                        class="group relative overflow-hidden rounded-lg border border-black/8 bg-[#ffffff] p-4 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/8 dark:border-white/10 dark:bg-[#171512] sm:p-5"
                    >
                        <div class="absolute left-12 top-9 hidden h-px w-full bg-gradient-to-r from-[#9f243a] to-transparent opacity-70 lg:block lg:group-last:hidden"></div>
                        <div class="relative z-10 flex items-center gap-4">
                            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-[#171512] text-[#ff8aa0] shadow-lg shadow-black/12 dark:bg-[#9f243a] dark:text-white">
                                <FontAwesomeIcon :icon="stepIcons[index]" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[11px] font-black uppercase tracking-normal text-[#8f2638] dark:text-[#ff8aa0]">
                                    0{{ index + 1 }}
                                </p>
                                <p class="mt-1 truncate text-base font-black text-[#171512] dark:text-white">
                                    {{ step }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
