<script setup>
import { computed, onMounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons, locale } = useStorefront();

const searches = ref([]);

const fallbackSearches = computed(() => copy.value.popularSearchesSection.searches ?? []);
const visibleSearches = computed(() => searches.value.length ? searches.value.map((search) => ({
    label: locale.value === 'bn' ? (search.label_bn || search.label_en) : search.label_en,
    href: search.href,
})) : fallbackSearches.value);

const loadSearches = async () => {
    try {
        const response = await fetch('/api/storefront/popular-searches?limit=16', { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return;
        }
        const payload = await response.json();
        searches.value = Array.isArray(payload.data) ? payload.data : [];
    } catch (requestError) {
        searches.value = [];
    }
};

onMounted(() => loadSearches());
</script>

<template>
    <section class="bg-white py-10 transition-colors dark:bg-[#111111] sm:py-12">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                        <FontAwesomeIcon :icon="icons.search" />
                        {{ copy.popularSearchesSection.eyebrow }}
                    </p>
                    <h2 class="mt-2 text-2xl font-semibold text-ink dark:text-white sm:text-3xl">{{ copy.popularSearchesSection.title }}</h2>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <a
                    v-for="search in visibleSearches"
                    :key="search.href + search.label"
                    :href="search.href"
                    class="rounded-full bg-ink/6 px-3.5 py-2 text-[12px] font-semibold text-ink transition hover:bg-wine hover:text-white dark:bg-white/8 dark:text-white dark:hover:bg-wine"
                >
                    {{ search.label }}
                </a>
            </div>
        </div>
    </section>
</template>
