<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCatalogProducts } from '../../composables/useCatalogProducts';
import { useCart } from '../../composables/useCart';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { categoryCollageImage, copy, icons } = useStorefront();
const { addProduct, isAdding } = useCart();
const { mergeWithFallback } = useCatalogProducts();
const { campaignHref, productHref } = useStorefrontLinks();
const remainingSeconds = ref(0);
const products = computed(() => mergeWithFallback(copy.value.flashSale.products, 4));
let timerId;

const updateCountdown = () => {
    const now = new Date();
    const endOfDay = new Date(now);
    endOfDay.setHours(23, 59, 59, 999);
    remainingSeconds.value = Math.max(0, Math.floor((endOfDay.getTime() - now.getTime()) / 1000));
};

const countdown = computed(() => {
    const hours = Math.floor(remainingSeconds.value / 3600);
    const minutes = Math.floor((remainingSeconds.value % 3600) / 60);
    const seconds = remainingSeconds.value % 60;

    return [hours, minutes, seconds].map((unit) => String(unit).padStart(2, '0'));
});

onMounted(() => {
    updateCountdown();
    timerId = window.setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    window.clearInterval(timerId);
});
</script>

<template>
    <section class="bg-white py-14 transition-colors dark:bg-[#111111] sm:py-16 lg:py-20">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="grid gap-6 overflow-hidden rounded-2xl bg-ink p-5 text-white sm:p-7 lg:grid-cols-[1fr_auto] lg:items-center lg:p-8">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-4 py-2 text-[11px] font-semibold text-white">
                        <span class="size-1.5 rounded-full bg-wine animate-pulse-glow"></span>
                        <FontAwesomeIcon :icon="icons.fire" class="text-wine" />
                        {{ copy.flashSale.eyebrow }}
                    </p>
                    <h2 class="mt-5 max-w-2xl text-4xl font-semibold leading-[1.15] sm:text-5xl lg:text-[3.1rem]">
                        <span class="block">{{ copy.flashSale.titleLineOne }}</span>
                        <span class="mt-1 block text-wine">{{ copy.flashSale.titleLineTwo }}</span>
                    </h2>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-white/70 sm:text-base">
                        {{ copy.flashSale.subtitle }}
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                    <p class="mb-3 flex items-center gap-2 text-[11px] font-semibold text-white/80">
                        <FontAwesomeIcon :icon="icons.clock" class="text-wine" />
                        {{ copy.flashSale.endingLabel }}
                    </p>
                    <div class="grid grid-cols-3 gap-2">
                        <span
                            v-for="(unit, index) in countdown"
                            :key="index + '-' + unit"
                            class="grid h-14 min-w-16 place-items-center rounded-2xl bg-white text-2xl font-semibold text-ink animate-tick dark:bg-ink dark:text-white dark:ring-1 dark:ring-white/10"
                        >
                            {{ unit }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a
                    :href="campaignHref('flash sale')"
                    class="inline-flex h-11 items-center justify-center gap-3 rounded-full border border-black/10 bg-white px-5 text-sm font-semibold text-ink transition hover:border-wine/30 hover:text-wine dark:border-white/10 dark:bg-white/8 dark:text-white"
                >
                    {{ copy.flashSale.action }}
                    <FontAwesomeIcon :icon="icons.arrowRight" />
                </a>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <article
                    v-for="(product, index) in products"
                    :key="product.name"
                    class="group relative overflow-hidden rounded-2xl bg-ink shadow-[0_28px_50px_-30px_rgb(17_17_17_/_0.7)] ring-1 ring-black/10 animate-card-in transition duration-500 hover:-translate-y-1.5 hover:shadow-[0_36px_60px_-28px_rgb(143_38_56_/_0.45)] dark:ring-white/10"
                    :style="{ animationDelay: `${index * 90}ms` }"
                >
                    <span class="absolute inset-x-0 top-0 z-20 h-0.5 bg-wine"></span>
                    <span class="pointer-events-none absolute left-3 top-3 z-20 h-5 w-5 border-l border-t border-white/80"></span>
                    <span class="pointer-events-none absolute right-3 top-3 z-20 h-5 w-5 border-r border-t border-white/80"></span>

                    <div class="relative aspect-[3/4] overflow-hidden">
                        <div
                            class="absolute inset-0 bg-cover bg-no-repeat transition duration-700 ease-out group-hover:scale-110"
                            :style="{ backgroundImage: 'url(' + categoryCollageImage + ')', backgroundSize: '400% 200%', backgroundPosition: product.imagePosition }"
                        ></div>
                        <span class="pointer-events-none absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent transition duration-700 group-hover:translate-x-full"></span>
                        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/25 to-transparent opacity-80 transition duration-500 group-hover:opacity-90"></div>

                        <span class="absolute left-0 top-8 z-10 rounded-r-full bg-wine px-3 py-1.5 text-[11px] font-semibold text-white shadow-lg">
                            {{ product.discount }}
                        </span>
                        <span class="absolute right-3 top-8 z-10 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-semibold text-ink backdrop-blur dark:bg-ink/80 dark:text-white">
                            {{ product.stock }}
                        </span>

                        <div class="absolute inset-x-3 bottom-3 z-10 translate-y-1 rounded-xl border border-white/30 bg-white/92 p-3 shadow-lg backdrop-blur-xl transition duration-500 group-hover:translate-y-0 dark:border-white/10 dark:bg-ink/88">
                            <p class="truncate text-[10px] font-semibold text-wine">{{ product.category }}</p>
                            <a :href="productHref(product)" class="mt-1 block line-clamp-2 text-sm font-semibold leading-5 text-ink hover:text-wine dark:text-white">
                                {{ product.name }}
                            </a>
                            <div class="mt-2.5 flex items-end justify-between gap-2">
                                <div>
                                    <p class="text-base font-semibold text-wine">{{ product.currentPrice }}</p>
                                    <p class="text-[11px] text-[#6b7280] line-through dark:text-[#9ca3af]">{{ product.previousPrice }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="hidden text-[10px] text-[#6b7280] sm:inline dark:text-[#9ca3af]">{{ product.sold }}</span>
                                    <button
                                        class="grid size-9 shrink-0 place-items-center rounded-full bg-ink text-sm text-white shadow-md transition duration-300 group-hover:bg-wine dark:bg-wine"
                                        type="button"
                                        :aria-label="product.name"
                                        :disabled="!product.id || isAdding(product).value"
                                        @click="addProduct(product)"
                                    >
                                        <FontAwesomeIcon :icon="icons.cart" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
