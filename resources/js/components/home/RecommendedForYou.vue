<script setup>
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCatalogProducts } from '../../composables/useCatalogProducts';
import { useStorefront } from '../../composables/useStorefront';
import PremiumProductCard from './PremiumProductCard.vue';

const { copy, icons } = useStorefront();
const { mergeWithFallback } = useCatalogProducts();

const products = computed(() => mergeWithFallback(copy.value.recommendedSection.products, 4));
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.wand" />
                        {{ copy.recommendedSection.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.recommendedSection.titleLineOne }}
                        <span class="text-wine">{{ copy.recommendedSection.titleLineTwo }}</span>
                    </h2>
                    <p class="mt-2 text-[12px] font-semibold text-ink/50 dark:text-white/50">
                        {{ copy.recommendedSection.fallbackLabel }} · {{ copy.recommendedSection.fallbackText }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <span
                        v-for="signal in copy.recommendedSection.signals"
                        :key="signal"
                        class="rounded-full bg-ink/6 px-3 py-1.5 text-[11px] font-semibold text-ink dark:bg-white/8 dark:text-white"
                    >
                        {{ signal }}
                    </span>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <PremiumProductCard
                    v-for="(product, index) in products"
                    :key="product.name"
                    :product="product"
                    :index="index"
                    wishlist-source="recommended"
                />
            </div>
        </div>
    </section>
</template>
