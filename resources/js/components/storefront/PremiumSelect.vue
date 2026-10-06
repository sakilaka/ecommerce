<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);
const { icons } = useStorefront();
const isOpen = ref(false);
const root = ref(null);

const selectedOption = computed(() => props.options.find((option) => String(option.value) === String(props.modelValue)) || props.options[0] || null);
const selectedLabel = computed(() => selectedOption.value?.label || '');

const toggleMenu = () => {
    isOpen.value = !isOpen.value;
};

const closeMenu = () => {
    isOpen.value = false;
};

const chooseOption = (option) => {
    emit('update:modelValue', option.value);
    closeMenu();
};

const onDocumentClick = (event) => {
    if (!root.value?.contains(event.target)) {
        closeMenu();
    }
};

const onDocumentKeydown = (event) => {
    if (event.key === 'Escape') {
        closeMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onDocumentKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onDocumentKeydown);
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            class="relative flex h-11 w-full items-center rounded-full bg-white/8 pl-4 pr-12 text-left text-sm font-medium text-white ring-1 ring-white/10 transition hover:ring-wine/50"
            :class="isOpen ? 'ring-wine/70' : ''"
            type="button"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            @click.stop="toggleMenu"
        >
            <span class="min-w-0 flex-1 truncate">{{ selectedLabel }}</span>
            <FontAwesomeIcon
                :icon="icons.chevronDown"
                class="pointer-events-none absolute right-4 text-[11px] text-white/70 transition"
                :class="isOpen ? 'rotate-180 text-wine' : ''"
            />
        </button>

        <div
            v-if="isOpen"
            class="absolute inset-x-0 top-[calc(100%+0.45rem)] z-40 max-h-64 overflow-y-auto rounded-2xl border border-white/10 bg-[#1a1a1a] p-1.5 shadow-[0_24px_40px_-18px_rgb(0_0_0_/_0.7)] [scrollbar-width:thin]"
            role="listbox"
        >
            <button
                v-for="option in options"
                :key="String(option.value)"
                class="flex w-full items-center rounded-xl px-3.5 py-2.5 text-left text-sm font-medium transition"
                :class="String(option.value) === String(modelValue) ? 'bg-wine text-white' : 'text-white/80 hover:bg-white/8 hover:text-white'"
                type="button"
                role="option"
                :aria-selected="String(option.value) === String(modelValue)"
                @click.stop="chooseOption(option)"
            >
                {{ option.label }}
            </button>
        </div>
    </div>
</template>
