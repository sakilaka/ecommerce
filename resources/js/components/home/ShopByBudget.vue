<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons } = useStorefront();
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="overflow-hidden rounded-[1.6rem] bg-ink text-white shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)]">
                <div class="flex flex-col gap-4 border-b border-white/10 px-5 py-6 sm:flex-row sm:items-end sm:justify-between sm:px-7">
                    <div>
                        <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                            <FontAwesomeIcon :icon="icons.tags" />
                            {{ copy.budgetSection.eyebrow }}
                        </p>
                        <h2 class="mt-2 text-3xl font-semibold leading-snug sm:text-4xl">
                            {{ copy.budgetSection.titleLineOne }}
                            <span class="text-wine">{{ copy.budgetSection.titleLineTwo }}</span>
                        </h2>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4">
                    <a
                        v-for="(tier, index) in copy.budgetSection.tiers"
                        :key="tier.label"
                        :href="tier.href"
                        class="group flex items-center justify-between gap-4 border-white/10 p-5 transition hover:bg-wine/15 sm:p-6 sm:odd:border-r lg:border-r lg:last:border-r-0"
                        :class="index < copy.budgetSection.tiers.length - 1 ? 'border-b lg:border-b-0' : ''"
                    >
                        <span class="min-w-0">
                            <span class="block text-lg font-semibold">{{ tier.label }}</span>
                            <span class="mt-1 block text-[11px] font-semibold text-wine">{{ tier.count }}</span>
                            <span class="mt-2 block text-xs leading-5 text-white/55">{{ tier.text }}</span>
                            <span class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-semibold text-white">
                                {{ copy.budgetSection.action }}
                                <FontAwesomeIcon :icon="icons.arrowRight" class="transition group-hover:translate-x-0.5" />
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
