<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { categoryCollageImage, copy, icons } = useStorefront();
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.chart" />
                        {{ copy.trendingNow.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.trendingNow.titleLineOne }}
                        <span class="text-wine">{{ copy.trendingNow.titleLineTwo }}</span>
                    </h2>
                </div>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <a
                    v-for="(topic, index) in copy.trendingNow.topics"
                    :key="topic.title"
                    :href="topic.href"
                    class="group flex items-center gap-3 overflow-hidden rounded-[1.4rem] bg-[#141414] p-2.5 shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)] animate-card-in transition duration-300 hover:-translate-y-1"
                    :style="{ animationDelay: `${(index % 3) * 70}ms` }"
                >
                    <span class="relative size-[4.75rem] shrink-0 overflow-hidden rounded-2xl bg-wine-deep">
                        <span
                            class="absolute inset-0 bg-no-repeat transition duration-500 group-hover:scale-105"
                            :style="{
                                backgroundImage: 'url(' + categoryCollageImage + ')',
                                backgroundSize: '400% 200%',
                                backgroundPosition: topic.position,
                            }"
                        ></span>
                    </span>
                    <span class="min-w-0 flex-1 py-1 pr-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-white">{{ topic.title }}</span>
                            <span class="shrink-0 rounded-full bg-wine px-2 py-0.5 text-[10px] font-semibold text-white">{{ topic.trend }}</span>
                        </span>
                        <span class="mt-1 block line-clamp-2 text-[11px] leading-5 text-white/50">{{ topic.text }}</span>
                        <span class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-wine">
                            {{ copy.trendingNow.action }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </span>
                    </span>
                </a>
            </div>
        </div>
    </section>
</template>
