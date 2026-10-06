<script setup>
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCatalogProducts } from '../../composables/useCatalogProducts';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';
import PremiumProductCard from './PremiumProductCard.vue';

const { copy, icons } = useStorefront();
const { sortHref } = useStorefrontLinks();
const { mergeWithFallback } = useCatalogProducts();

const products = computed(() => mergeWithFallback(copy.value.arrivalsSection.products, 8));
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.clock" />
                        {{ copy.arrivalsSection.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.arrivalsSection.titleLineOne }}
                        <span class="text-wine">{{ copy.arrivalsSection.titleLineTwo }}</span>
                    </h2>
                </div>
                <a
                    :href="sortHref('latest')"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full bg-ink px-4 text-sm font-semibold text-white transition hover:bg-wine dark:bg-wine"
                >
                    {{ copy.arrivalsSection.action }}
                    <FontAwesomeIcon :icon="icons.arrowRight" />
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <PremiumProductCard
                    v-for="(product, index) in products"
                    :key="product.name"
                    :product="product"
                    :index="index"
                    wishlist-source="new_arrivals"
                />
            </div>
        </div>
    </section>
</template>
