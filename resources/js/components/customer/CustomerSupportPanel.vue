<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    orders: { type: Array, required: true },
    supportTickets: { type: Array, required: true },
    supportTopics: { type: Array, required: true },
    supportForm: { type: Object, required: true },
    text: { type: Object, required: true },
    icons: { type: Object, required: true },
    isSupportLoading: { type: Boolean, required: true },
    supportError: { type: String, default: '' },
    supportNotice: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'refresh']);
</script>

<template>
    <section class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
        <form class="panel-card" @submit.prevent="emit('submit')">
            <p class="text-[11px] font-semibold text-wine">{{ text.supportTitle }}</p>
            <h2 class="mt-2 text-xl font-semibold text-ink dark:text-white">{{ text.supportSubtitle }}</h2>
            <div v-if="supportNotice" class="note-wine mt-4">{{ supportNotice }}</div>
            <div v-if="supportError" class="note-wine mt-4">{{ supportError }}</div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <label class="field-label">{{ text.supportTopic }}<select v-model="supportForm.topic" required class="field-select"><option v-for="topic in supportTopics" :key="topic.value" :value="topic.value">{{ topic.label }}</option></select></label>
                <label class="field-label">{{ text.supportPriority }}<select v-model="supportForm.priority" class="field-select"><option value="normal">{{ text.normal }}</option><option value="high">{{ text.high }}</option></select></label>
                <label class="field-label sm:col-span-2">{{ text.supportOrder }}<select v-model="supportForm.order_id" class="field-select"><option value="">{{ text.optionalOrder }}</option><option v-for="orderItem in orders" :key="orderItem.id" :value="orderItem.id">{{ orderItem.order_number }}</option></select></label>
                <label class="field-label sm:col-span-2">{{ text.supportSubject }}<input v-model="supportForm.subject" required minlength="5" maxlength="160" class="field-input" type="text"></label>
                <label class="field-label sm:col-span-2">{{ text.supportMessage }}<textarea v-model="supportForm.message" required minlength="10" maxlength="2000" rows="4" class="field-textarea"></textarea></label>
            </div>
            <button class="btn-ink mt-5" type="submit" :disabled="isSupportLoading">{{ text.submitSupport }}<FontAwesomeIcon :icon="icons.arrowRight" /></button>
        </form>

        <section class="panel-card">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.supportTitle }}</h2>
                <button class="btn-ghost" type="button" :disabled="isSupportLoading" @click="emit('refresh')">{{ text.refresh }}</button>
            </div>
            <p v-if="supportTickets.length === 0" class="mt-4 rounded-2xl bg-ink/5 px-4 py-8 text-center text-sm font-semibold text-ink/50 dark:bg-white/8 dark:text-white/50">{{ text.noTickets }}</p>
            <div v-else class="mt-4 space-y-3">
                <article v-for="ticket in supportTickets" :key="ticket.id" class="rounded-2xl bg-ink/5 p-4 dark:bg-white/8">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink dark:text-white">{{ ticket.ticket_number }}</p>
                            <p class="mt-1 line-clamp-1 text-xs font-semibold text-ink/55 dark:text-white/55">{{ ticket.subject }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-wine/10 px-2.5 py-1 text-[10px] font-semibold uppercase text-wine">{{ ticket.status }}</span>
                    </div>
                    <p class="mt-3 line-clamp-2 text-xs font-semibold leading-5 text-ink/55 dark:text-white/55">{{ ticket.message }}</p>
                    <p v-if="ticket.admin_reply" class="mt-3 rounded-2xl bg-white px-3 py-2 text-xs font-semibold leading-5 text-ink dark:bg-white/8 dark:text-white">{{ ticket.admin_reply }}</p>
                </article>
            </div>
        </section>
    </section>
</template>
