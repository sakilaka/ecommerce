<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    wishlistItems: { type: Array, required: true },
    text: { type: Object, required: true },
    icons: { type: Object, required: true },
    isLoadingWishlist: { type: Boolean, required: true },
    wishlistError: { type: String, default: '' },
    isAdding: { type: Function, required: true },
    isToggling: { type: Function, required: true },
    formatPrice: { type: Function, required: true },
    wishlistProductUrl: { type: Function, required: true },
});

const emit = defineEmits(['refresh', 'toggle', 'add-to-cart']);
</script>

<template>
    <section class="panel-card">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.wishlistPanel }}</h2>
            <button class="btn-ghost" type="button" :disabled="isLoadingWishlist" @click="emit('refresh')">
                {{ text.refresh }}
            </button>
        </div>

        <div v-if="wishlistError" class="note-wine mt-4">{{ wishlistError }}</div>
        <p v-if="wishlistItems.length === 0" class="mt-4 rounded-2xl bg-ink/5 px-4 py-8 text-center text-sm font-semibold text-ink/50 dark:bg-white/8 dark:text-white/50">{{ text.noWishlist }}</p>

        <div v-else class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="wishlistItem in wishlistItems" :key="wishlistItem.id" class="overflow-hidden rounded-[1.4rem] bg-[#141414] text-white">
                <a class="block aspect-square overflow-hidden" :href="wishlistProductUrl(wishlistItem)">
                    <span class="block size-full bg-no-repeat transition duration-500 hover:scale-105" :style="{ backgroundImage: 'url(/images/category-collage.png)', backgroundSize: '400% 200%', backgroundPosition: wishlistItem.product?.image_position || '0% 0%' }"></span>
                </a>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-semibold text-wine">{{ wishlistItem.product?.sku || text.wishlist }}</p>
                            <a class="mt-1 line-clamp-2 text-sm font-semibold leading-5 text-white hover:text-wine" :href="wishlistProductUrl(wishlistItem)">{{ wishlistItem.product?.name }}</a>
                        </div>
                        <button class="grid size-8 shrink-0 place-items-center rounded-full bg-white/10 text-xs text-white transition hover:bg-wine disabled:opacity-50" type="button" :disabled="isToggling(wishlistItem.product).value" :aria-label="text.remove" @click="emit('toggle', wishlistItem.product)">
                            <FontAwesomeIcon :icon="icons.trash" />
                        </button>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs font-semibold text-white/55">
                        <div class="rounded-2xl bg-white/8 p-2"><span class="block text-[10px] text-wine">{{ text.variant }}</span>{{ wishlistItem.variant?.name || '-' }}</div>
                        <div class="rounded-2xl bg-white/8 p-2"><span class="block text-[10px] text-wine">{{ text.stock }}</span>{{ wishlistItem.variant?.stock_quantity ?? '-' }}</div>
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <strong class="text-sm font-semibold">{{ formatPrice(wishlistItem.product?.price) }}</strong>
                        <button class="btn-wine h-8 px-3 text-[10px]" type="button" :disabled="isAdding(wishlistItem.product).value" @click="emit('add-to-cart', wishlistItem)">
                            <FontAwesomeIcon :icon="icons.cart" />{{ text.addToCart }}
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
