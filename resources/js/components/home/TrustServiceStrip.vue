<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons } = useStorefront();
const serviceIcons = [icons.truck, icons.bag, icons.shield, icons.returns];
</script>

<template>
    <section class="border-y border-black/10 bg-white py-6 transition-colors dark:border-white/10 dark:bg-[#111111] sm:py-8">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex gap-3 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:grid lg:grid-cols-4 lg:overflow-visible lg:pb-0">
                <article
                    v-for="(item, index) in copy.trustStrip"
                    :key="item.title"
                    class="group flex min-w-[16.5rem] items-center gap-4 rounded-2xl border border-black/10 bg-white p-4 shadow-[0_18px_40px_-28px_rgb(17_17_17_/_0.45)] transition hover:-translate-y-0.5 hover:border-wine/25 hover:shadow-xl dark:border-white/10 dark:bg-white/8 lg:min-w-0"
                >
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-wine/8 text-wine transition group-hover:bg-wine group-hover:text-white dark:bg-wine/20 dark:text-white">
                        <FontAwesomeIcon :icon="serviceIcons[index]" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[10px] font-semibold text-wine">0{{ index + 1 }}</span>
                        <span class="mt-1 block truncate text-sm font-semibold text-ink dark:text-white">{{ item.title }}</span>
                        <span class="mt-1 block truncate text-xs text-[#374151] dark:text-[#e5e7eb]">{{ item.text }}</span>
                    </span>
                </article>
            </div>
        </div>
    </section>
</template>
