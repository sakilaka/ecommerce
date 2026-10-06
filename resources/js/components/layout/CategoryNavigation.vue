<script setup>
import { ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { copy, icons } = useStorefront();
const { categoryHref, searchHref } = useStorefrontLinks();
const isCategoryMenuOpen = ref(false);
const departmentIcons = [
    icons.laptop,
    icons.shirt,
    icons.gem,
    icons.house,
    icons.basket,
    icons.futbol,
    icons.child,
    icons.car,
    icons.tags,
];

const departmentHref = (department) => categoryHref(department);

const toggleCategoryMenu = () => {
    isCategoryMenuOpen.value = !isCategoryMenuOpen.value;
};
</script>

<template>
    <nav class="border-b border-black/10 bg-white dark:border-white/10 dark:bg-[#171717]" aria-label="Category navigation">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-12">
            <div class="flex h-12 items-center gap-2 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button
                    class="inline-flex h-9 shrink-0 items-center gap-2 rounded-full bg-ink py-0 pl-4 pr-5 text-sm font-semibold text-white shadow-lg shadow-black/15 transition hover:bg-wine dark:bg-wine-soft"
                    type="button"
                    :aria-expanded="isCategoryMenuOpen"
                    :aria-label="isCategoryMenuOpen ? copy.categoriesNav.close : copy.categoriesNav.all"
                    @click="toggleCategoryMenu"
                >
                    <FontAwesomeIcon :icon="icons.grip" />
                    <span>{{ copy.categoriesNav.all }}</span>
                    <FontAwesomeIcon :icon="icons.chevronDown" class="ml-1 text-[11px] transition" :class="isCategoryMenuOpen ? 'rotate-180' : ''" />
                </button>

                <a
                    v-for="(department, index) in copy.categoriesNav.departments"
                    :key="department"
                    :href="departmentHref(department)"
                    class="group relative hidden h-9 shrink-0 items-center gap-2 rounded-full px-3 text-sm font-medium text-ink/80 transition hover:bg-white hover:text-wine dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white sm:inline-flex"
                >
                    <FontAwesomeIcon :icon="departmentIcons[index]" class="text-wine" />
                    {{ department }}
                    <span class="absolute inset-x-4 bottom-1 h-px origin-left scale-x-0 bg-wine transition duration-300 group-hover:scale-x-100"></span>
                </a>
            </div>
        </div>

        <div v-if="isCategoryMenuOpen" class="border-t border-black/10 bg-white shadow-2xl shadow-black/10 dark:border-white/10 dark:bg-[#171717]">
            <div class="mx-auto grid max-w-[1440px] gap-5 px-4 py-5 sm:px-6 md:grid-cols-[1fr_0.42fr] lg:px-12">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <a
                        v-for="(department, index) in copy.categoriesNav.departments"
                        :key="department"
                        :href="departmentHref(department)"
                        class="group flex items-center gap-4 rounded-2xl border border-black/10 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-wine/30 hover:shadow-xl hover:shadow-wine/10 dark:border-white/10 dark:bg-white/8"
                        @click="isCategoryMenuOpen = false"
                    >
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-ink text-white transition group-hover:bg-wine dark:bg-wine-soft">
                            <FontAwesomeIcon :icon="departmentIcons[index]" />
                        </span>
                        <span class="text-sm font-semibold text-ink dark:text-white">{{ department }}</span>
                    </a>
                </div>

                <aside class="rounded-2xl bg-ink p-5 text-white shadow-xl dark:bg-wine-soft">
                    <p class="text-[11px] font-semibold text-white/70">{{ copy.categoriesNav.featuredTitle }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a
                            v-for="item in copy.categoriesNav.featured"
                            :key="item"
                            :href="searchHref(item)"
                            class="rounded-full border border-white/10 bg-white/8 px-3 py-2 text-xs font-semibold transition hover:bg-white hover:text-ink"
                            @click="isCategoryMenuOpen = false"
                        >
                            {{ item }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </nav>
</template>
