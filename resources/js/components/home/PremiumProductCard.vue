<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useCart } from '../../composables/useCart';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';
import { useWishlist } from '../../composables/useWishlist';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        default: 0,
    },
    wishlistSource: {
        type: String,
        required: true,
    },
});

const { categoryCollageImage, copy, icons } = useStorefront();
const { productHref } = useStorefrontLinks();
const { addProduct, isAdding } = useCart();
const { isToggling, isWishlisted, toggleWishlist } = useWishlist();
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-[1.6rem] bg-[#141414] shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)] animate-card-in transition duration-300 hover:-translate-y-1"
        :style="{ animationDelay: `${(props.index % 4) * 70}ms` }"
    >
        <div class="relative isolate overflow-hidden px-4 pb-3 pt-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_40%,rgb(143_38_56_/_0.55),rgb(20_20_20)_68%)]"></div>
            <span class="pointer-events-none absolute right-3 top-12 size-10 rounded-full border border-white/15"></span>
            <span class="pointer-events-none absolute left-4 top-16 size-6 rounded-full border border-wine/50"></span>
            <span class="pointer-events-none absolute bottom-6 right-8 h-1 w-10 rounded-full bg-wine/80"></span>
            <span class="pointer-events-none absolute left-8 top-10 h-1 w-8 rounded-full bg-white/20"></span>

            <button
                class="absolute left-3 top-3 z-10 grid size-8 place-items-center rounded-full bg-white/95 text-ink shadow-md transition hover:bg-wine hover:text-white disabled:opacity-50"
                :class="isWishlisted(props.product).value ? 'bg-wine text-white' : ''"
                type="button"
                :aria-label="copy.wishlist"
                :disabled="isToggling(props.product).value"
                @click="toggleWishlist(props.product, props.wishlistSource)"
            >
                <FontAwesomeIcon :icon="icons.heart" class="text-xs" />
            </button>

            <div class="relative mx-auto mt-2 aspect-square w-[78%] overflow-hidden rounded-2xl">
                <div
                    class="absolute inset-0 bg-no-repeat transition duration-500 group-hover:scale-105"
                    :style="{
                        backgroundImage: 'url(' + categoryCollageImage + ')',
                        backgroundSize: '400% 200%',
                        backgroundPosition: props.product.position,
                    }"
                ></div>
            </div>
        </div>

        <div class="mt-auto border-t border-white/8 px-3.5 pb-3.5 pt-3">
            <div class="flex items-center gap-2">
                <span
                    v-if="props.product.badge || props.product.signal"
                    class="inline-flex max-w-[65%] truncate rounded-full bg-wine/15 px-2 py-0.5 text-[10px] font-semibold text-wine"
                >
                    {{ props.product.badge || props.product.signal }}
                </span>
                <span v-if="props.product.category" class="min-w-0 truncate text-[10px] text-white/45">
                    {{ props.product.category }}
                </span>
            </div>

            <a
                :href="productHref(props.product)"
                class="mt-2 block line-clamp-2 min-h-[2.5rem] text-[13px] font-semibold leading-5 text-white transition hover:text-wine"
            >
                {{ props.product.name }}
            </a>

            <div class="mt-2 flex items-center gap-1.5">
                <div class="flex items-center gap-px text-[9px] text-wine">
                    <FontAwesomeIcon v-for="star in 5" :key="star" :icon="icons.star" />
                </div>
                <p v-if="props.product.rating" class="text-[10px] text-white/55">
                    {{ props.product.rating }}
                    <span v-if="props.product.reviews">({{ props.product.reviews }})</span>
                </p>
            </div>

            <div class="mt-3 flex items-end justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[15px] font-semibold tracking-tight text-white">{{ props.product.price }}</p>
                    <p v-if="props.product.previousPrice" class="text-[11px] text-white/35 line-through">{{ props.product.previousPrice }}</p>
                </div>
                <p v-if="props.product.sold" class="mb-0.5 truncate text-[10px] font-medium text-white/45">{{ props.product.sold }}</p>
                <p v-else-if="props.product.arrived" class="mb-0.5 truncate text-[10px] font-medium text-white/45">{{ props.product.arrived }}</p>
            </div>

            <button
                class="mt-3 inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-full bg-wine text-[11px] font-semibold uppercase tracking-wide text-white transition hover:bg-white hover:text-ink disabled:opacity-50"
                type="button"
                :aria-label="props.product.name"
                :disabled="!props.product.id || isAdding(props.product).value"
                @click="addProduct(props.product)"
            >
                <FontAwesomeIcon :icon="icons.cart" class="text-[10px]" />
                {{ copy.cart }}
            </button>
        </div>
    </article>
</template>
