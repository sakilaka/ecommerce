<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    reviews: { type: Array, required: true },
    text: { type: Object, required: true },
    icons: { type: Object, required: true },
    isLoading: { type: Boolean, required: true },
    error: { type: String, default: '' },
});

const emit = defineEmits(['refresh']);
</script>

<template>
    <section class="panel-card">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.reviewsTitle }}</h2>
            <button class="btn-ghost" type="button" :disabled="isLoading" @click="emit('refresh')">
                {{ text.refresh }}
            </button>
        </div>

        <div v-if="error" class="note-wine mt-4">{{ error }}</div>
        <p v-if="reviews.length === 0" class="mt-4 rounded-2xl bg-ink/5 px-4 py-8 text-center text-sm font-semibold text-ink/50 dark:bg-white/8 dark:text-white/50">{{ text.noReviews }}</p>

        <div v-else class="mt-4 grid gap-3 md:grid-cols-2">
            <article v-for="review in reviews" :key="review.id" class="rounded-2xl bg-ink/5 p-4 dark:bg-white/8">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="line-clamp-1 text-sm font-semibold text-ink dark:text-white">{{ review.product?.name || review.order_item?.product_name }}</p>
                        <p class="mt-1 line-clamp-1 text-xs font-semibold text-ink/55 dark:text-white/55">{{ review.title || review.body }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-wine/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-wine">{{ review.status }}</span>
                </div>
                <div class="mt-3 flex gap-1 text-wine">
                    <FontAwesomeIcon v-for="star in review.rating" :key="star" :icon="icons.star" />
                </div>
                <p class="mt-2 line-clamp-2 text-xs font-semibold leading-5 text-ink/55 dark:text-white/55">{{ review.body }}</p>
            </article>
        </div>
    </section>
</template>
