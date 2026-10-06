<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { categoryCollageImage, copy, icons } = useStorefront();
const { categoryHref } = useStorefrontLinks();
const categoryIcons = [icons.laptop, icons.shirt, icons.gem, icons.wand, icons.house, icons.laptop, icons.basket, icons.child];
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14 lg:py-16">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="overflow-hidden rounded-2xl border border-black/10 bg-white shadow-[0_24px_60px_-36px_rgb(17_17_17_/_0.5)] dark:border-white/10 dark:bg-[#161616] lg:grid lg:grid-cols-[0.38fr_1fr]">
                <div class="relative overflow-hidden bg-ink px-6 py-7 text-white lg:px-8 lg:py-9">
                    <div class="pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-wine/30 blur-3xl"></div>
                    <p class="relative inline-flex items-center gap-2 text-[11px] font-semibold text-white/70">
                        <FontAwesomeIcon :icon="icons.grip" class="text-wine" />
                        {{ copy.shopCategories.eyebrow }}
                    </p>
                    <h2 class="relative mt-4 max-w-xs text-[1.75rem] font-semibold leading-snug sm:text-[2rem]">
                        <span class="block">{{ copy.shopCategories.titleLineOne }}</span>
                        <span class="mt-1 block text-wine">{{ copy.shopCategories.titleLineTwo }}</span>
                    </h2>
                    <div class="relative mt-5 flex items-center gap-3">
                        <span class="h-px w-8 bg-wine"></span>
                        <span class="size-1 rounded-full bg-white"></span>
                    </div>
                    <p class="relative mt-5 max-w-sm text-sm leading-7 text-white/70">{{ copy.shopCategories.subtitle }}</p>
                </div>

                <div class="grid sm:grid-cols-2">
                    <a
                        v-for="(item, index) in copy.shopCategories.items"
                        :key="item.title"
                        :href="categoryHref(item.title)"
                        class="group flex items-center gap-3.5 border-b border-black/8 p-3.5 transition last:border-b-0 hover:bg-wine/[0.04] dark:border-white/8 dark:hover:bg-white/5 sm:border-r-0 sm:p-4 sm:odd:border-r sm:[&:nth-last-child(-n+2)]:border-b-0"
                    >
                        <span class="relative size-[4.25rem] shrink-0 overflow-hidden rounded-xl bg-ink shadow-sm">
                            <span class="absolute inset-0 bg-cover bg-no-repeat transition duration-500 group-hover:scale-110" :style="{ backgroundImage: 'url(' + categoryCollageImage + ')', backgroundSize: '400% 200%', backgroundPosition: item.position }"></span>
                            <span class="absolute inset-0 bg-ink/20"></span>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-[10px] font-semibold text-wine">0{{ index + 1 }}</span>
                            <span class="mt-0.5 block truncate text-sm font-semibold text-ink group-hover:text-wine dark:text-white">{{ item.title }}</span>
                            <span class="mt-0.5 block truncate text-xs text-[#6b7280] dark:text-[#d1d5db]">{{ item.text }}</span>
                        </span>

                        <span class="grid size-8 shrink-0 place-items-center rounded-full border border-black/10 text-xs text-ink transition group-hover:border-wine group-hover:bg-wine group-hover:text-white dark:border-white/10 dark:text-white">
                            <FontAwesomeIcon :icon="categoryIcons[index] || icons.arrowRight" />
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
