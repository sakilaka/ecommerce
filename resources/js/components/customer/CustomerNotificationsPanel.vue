<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    notifications: {
        type: Array,
        required: true,
    },
    text: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['navigate']);
</script>

<template>
    <section class="panel-card">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.notificationTitle }}</h2>
            <span class="rounded-full bg-wine/10 px-3 py-1 text-[11px] font-semibold text-wine">{{ notifications.length }}</span>
        </div>

        <p v-if="notifications.length === 0" class="mt-4 rounded-2xl bg-ink/5 px-4 py-8 text-center text-sm font-semibold text-ink/50 dark:bg-white/8 dark:text-white/50">
            {{ text.noNotifications }}
        </p>

        <div v-else class="mt-4 space-y-2">
            <button
                v-for="notification in notifications"
                :key="notification.id"
                class="group flex w-full items-start gap-3 rounded-2xl bg-ink/5 p-3 text-left transition hover:-translate-y-0.5 hover:bg-wine/8 dark:bg-white/8"
                type="button"
                @click="emit('navigate', notification.panel)"
            >
                <span class="grid size-9 shrink-0 place-items-center rounded-full text-xs" :class="notification.tone === 'action' ? 'bg-wine text-white' : 'bg-white text-wine ring-1 ring-black/10 dark:bg-white/10'">
                    <FontAwesomeIcon :icon="notification.icon" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="max-w-full truncate text-sm font-semibold text-ink dark:text-white">{{ notification.title }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="notification.tone === 'action' ? 'bg-wine/10 text-wine' : 'bg-black/5 text-ink/55 dark:bg-white/10 dark:text-white/55'">
                            {{ notification.tone === 'action' ? text.actionNeeded : text.informational }}
                        </span>
                    </span>
                    <span class="mt-1 line-clamp-2 text-xs font-semibold leading-5 text-ink/55 dark:text-white/55">{{ notification.message }}</span>
                    <span class="mt-2 block text-[11px] font-semibold text-wine sm:hidden">{{ notification.time }}</span>
                </span>

                <span class="hidden shrink-0 text-[11px] font-semibold text-wine sm:block">{{ notification.time }}</span>
            </button>
        </div>
    </section>
</template>
