<script setup>
import { computed, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCatalogProducts } from '../../composables/useCatalogProducts';
import { useStorefront } from '../../composables/useStorefront';
import PremiumProductCard from './PremiumProductCard.vue';

const { copy, icons } = useStorefront();
const { mergeWithFallback } = useCatalogProducts();
const activeTab = ref('All');
const visibleCount = ref(8);

const products = computed(() => mergeWithFallback(copy.value.popularProducts.products, 12));

const filteredProducts = computed(() => {
    if (activeTab.value === 'All') {
        return products.value;
    }

    return products.value.filter((product) => product.category === activeTab.value);
});

const visibleProducts = computed(() => filteredProducts.value.slice(0, visibleCount.value));
const hasMoreProducts = computed(() => visibleCount.value < filteredProducts.value.length);

const setActiveTab = (tab) => {
    activeTab.value = tab;
};

const loadMoreProducts = () => {
    visibleCount.value += 4;
};

watch(activeTab, () => {
    visibleCount.value = 8;
});
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.grip" />
                        {{ copy.popularProducts.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.popularProducts.titleLineOne }}
                        <span class="text-wine">{{ copy.popularProducts.titleLineTwo }}</span>
                    </h2>
                </div>

                <div class="flex gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button
                        v-for="tab in copy.popularProducts.tabs"
                        :key="tab"
                        class="h-9 shrink-0 rounded-full px-3.5 text-[12px] font-semibold transition"
                        :class="activeTab === tab ? 'bg-wine text-white' : 'bg-ink/6 text-ink hover:bg-ink hover:text-white dark:bg-white/8 dark:text-white dark:hover:bg-wine'"
                        type="button"
                        @click="setActiveTab(tab)"
                    >
                        {{ tab }}
                    </button>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <PremiumProductCard
                    v-for="(product, index) in visibleProducts"
                    :key="product.name"
                    :product="product"
                    :index="index"
                    wishlist-source="popular_products"
                />
            </div>

            <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row sm:justify-between">
                <p class="text-[12px] font-semibold text-ink/55 dark:text-white/55">
                    {{ copy.popularProducts.showing }} {{ visibleProducts.length }} {{ copy.popularProducts.of }} {{ filteredProducts.length }}
                </p>
                <button
                    v-if="hasMoreProducts"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-ink px-4 text-sm font-semibold text-white transition hover:bg-wine dark:bg-wine"
                    type="button"
                    @click="loadMoreProducts"
                >
                    {{ copy.popularProducts.loadMore }}
                    <FontAwesomeIcon :icon="icons.arrowRight" />
                </button>
            </div>
        </div>
    </section>
</template>
