<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons } = useStorefront();
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="overflow-hidden rounded-[1.6rem] border border-black/10 dark:border-white/10">
                <div class="px-5 py-6 sm:px-7">
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.shield" />
                        {{ copy.whyShopSection.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-3xl font-semibold leading-snug text-ink dark:text-white sm:text-4xl">
                        {{ copy.whyShopSection.titleLineOne }}
                        <span class="text-wine">{{ copy.whyShopSection.titleLineTwo }}</span>
                    </h2>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="(reason, index) in copy.whyShopSection.reasons"
                        :key="reason.title"
                        class="flex gap-3.5 border-t border-black/8 p-5 dark:border-white/10 sm:p-6 lg:border-t-0 lg:border-l"
                        :class="index === 0 ? 'lg:border-l-0' : ''"
                    >
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-ink text-sm text-white dark:bg-wine">
                            <FontAwesomeIcon :icon="icons[reason.icon]" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-ink dark:text-white">{{ reason.title }}</h3>
                            <p class="mt-1.5 text-xs leading-5 text-ink/55 dark:text-white/55">{{ reason.text }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
