<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    mode: { type: String, required: true },
    profileForm: { type: Object, required: true },
    passwordForm: { type: Object, required: true },
    text: { type: Object, required: true },
    icons: { type: Object, required: true },
    notice: { type: String, default: '' },
    isAuthLoading: { type: Boolean, required: true },
});

const emit = defineEmits(['submit-profile', 'submit-password']);
</script>

<template>
    <form
        v-if="mode === 'profile'"
        class="panel-card"
        @submit.prevent="emit('submit-profile')"
    >
        <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.profile }}</h2>
        <div v-if="notice" class="note-wine mt-4">{{ notice }}</div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <label class="field-label">
                {{ text.name }}
                <input v-model="profileForm.name" required class="field-input" type="text">
            </label>
            <label class="field-label">
                {{ text.email }}
                <input v-model="profileForm.email" required class="field-input" type="email">
            </label>
            <label class="field-label">
                {{ text.phone }}
                <input v-model="profileForm.phone" required pattern="01[3-9][0-9]{8}" class="field-input" type="tel">
            </label>
        </div>

        <button class="btn-ink mt-5" type="submit" :disabled="isAuthLoading">
            {{ text.saveProfile }}<FontAwesomeIcon :icon="icons.arrowRight" />
        </button>
    </form>

    <form
        v-else-if="mode === 'security'"
        class="panel-card"
        @submit.prevent="emit('submit-password')"
    >
        <h2 class="text-lg font-semibold text-ink dark:text-white">{{ text.security }}</h2>
        <div v-if="notice" class="note-wine mt-4">{{ notice }}</div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <label class="field-label">
                {{ text.currentPassword }}
                <input v-model="passwordForm.current_password" required minlength="8" class="field-input" type="password">
            </label>
            <label class="field-label">
                {{ text.newPassword }}
                <input v-model="passwordForm.password" required minlength="8" class="field-input" type="password">
            </label>
            <label class="field-label">
                {{ text.confirmNewPassword }}
                <input v-model="passwordForm.password_confirmation" required minlength="8" class="field-input" type="password">
            </label>
        </div>

        <button class="btn-ink mt-5" type="submit" :disabled="isAuthLoading">
            {{ text.savePassword }}<FontAwesomeIcon :icon="icons.arrowRight" />
        </button>
    </form>
</template>
