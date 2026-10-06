<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons } = useStorefront();
</script>

<template>
    <div class="relative overflow-hidden border-b border-white/10 bg-ink text-white transition-colors dark:border-white/10 dark:bg-black">
        <div class="atelier-accent-line absolute inset-x-0 top-0 h-px"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgb(143_38_56_/_0.38),transparent_42%)]"></div>

        <div class="relative mx-auto flex h-10 max-w-[1440px] items-center justify-center gap-4 px-4 text-center sm:px-8 lg:justify-between lg:px-12">
            <p class="inline-flex min-w-0 items-center justify-center gap-2.5 text-xs font-medium sm:text-sm">
                <span class="relative grid size-2 shrink-0 place-items-center">
                    <span class="absolute size-2 rounded-full bg-wine animate-pulse-glow"></span>
                    <span class="size-1.5 rounded-full bg-wine"></span>
                </span>
                <FontAwesomeIcon :icon="icons.truck" class="hidden shrink-0 text-wine sm:inline" />
                <span class="truncate">{{ copy.announcement.primary }}</span>
            </p>

            <div class="hidden items-center gap-5 text-[11px] font-semibold text-white/75 lg:flex">
                <span class="inline-flex items-center gap-2 text-white">
                    <FontAwesomeIcon :icon="icons.gift" class="text-wine" />
                    {{ copy.announcement.sale }}
                </span>
                <span class="h-3.5 w-px bg-white/20"></span>
                <span>{{ copy.announcement.support }}</span>
            </div>
        </div>
    </div>
</template>
