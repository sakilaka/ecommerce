<script setup>
import { computed, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { customerLoginUrl, useAuth } from '../../composables/useAuth';
import { useCart } from '../../composables/useCart';
import { useStorefront } from '../../composables/useStorefront';
import { useWishlist } from '../../composables/useWishlist';

const { copy, icons, localeToggleLabel, themeIcon, themeLabel, toggleLocale, toggleTheme } = useStorefront();
const { itemCount, openCart } = useCart();
const { user } = useAuth();
const { wishlistCount } = useWishlist();
const isMobileMenuOpen = ref(false);
const searchTerm = ref(new URLSearchParams(window.location.search).get('q') ?? '');
const menuIcon = computed(() => (isMobileMenuOpen.value ? icons.xmark : icons.bars));

const openAccount = () => {
    window.location.href = user.value ? '/customer/dashboard' : '/login';
};

const openWishlist = () => {
    window.location.href = user.value
        ? '/customer/dashboard?panel=wishlist'
        : customerLoginUrl('/customer/dashboard?panel=wishlist');
};

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const submitSearch = () => {
    const params = new URLSearchParams();

    if (searchTerm.value.trim()) {
        params.set('q', searchTerm.value.trim());
    }

    window.location.href = `/products${params.toString() ? `?${params.toString()}` : ''}`;
};
</script>

<template>
    <header class="border-b border-black/10 bg-white dark:border-white/10 dark:bg-[#111111]">
        <div class="mx-auto flex max-w-[1440px] flex-wrap items-center gap-3 px-4 py-2.5 sm:px-6 lg:min-h-16 lg:flex-nowrap lg:px-12">
            <button
                class="grid size-11 shrink-0 place-items-center rounded-full border border-black/10 bg-white text-sm text-ink shadow-sm transition hover:border-wine/40 hover:text-wine dark:border-white/10 dark:bg-white/8 dark:text-white lg:hidden"
                type="button"
                :aria-label="isMobileMenuOpen ? copy.closeMenu : copy.menu"
                :aria-expanded="isMobileMenuOpen"
                @click="toggleMobileMenu"
            >
                <FontAwesomeIcon :icon="menuIcon" />
            </button>

            <a href="/" class="flex min-w-0 shrink-0 items-center gap-3" aria-label="Aurelia market home">
                <span class="relative grid size-11 place-items-center sm:size-12">
                    <span class="absolute inset-0 rounded-2xl bg-ink shadow-lg shadow-black/20 dark:bg-white"></span>
                    <span class="absolute left-1 top-1 h-2 w-2 rounded-tl-sm border-l border-t border-wine"></span>
                    <span class="absolute right-1 top-1 h-2 w-2 rounded-tr-sm border-r border-t border-wine"></span>
                    <span class="absolute bottom-1 left-1 h-2 w-2 rounded-bl-sm border-b border-l border-wine"></span>
                    <span class="absolute bottom-1 right-1 h-2 w-2 rounded-br-sm border-b border-r border-wine"></span>
                    <span class="relative text-2xl font-semibold leading-none text-white dark:text-ink sm:text-[1.7rem]">A</span>
                </span>
                <span class="hidden min-w-0 leading-tight sm:block">
                    <span class="block truncate text-[1.25rem] font-semibold leading-none text-ink dark:text-white">{{ copy.brand }}</span>
                    <span class="mt-1.5 block max-w-40 truncate text-[11px] font-medium text-wine">{{ copy.tagline }}</span>
                </span>
            </a>

            <form
                class="order-last flex h-12 basis-full items-center gap-3 rounded-full bg-white px-4 shadow-[0_12px_40px_-24px_rgb(17_17_17_/_0.45)] ring-1 ring-black/10 transition focus-within:ring-2 focus-within:ring-wine dark:bg-white/8 dark:ring-white/10 lg:order-none lg:mx-6 lg:h-12 lg:min-w-0 lg:flex-1 lg:basis-auto"
                @submit.prevent="submitSearch"
            >
                <FontAwesomeIcon :icon="icons.search" class="shrink-0 text-wine" />
                <input
                    v-model="searchTerm"
                    class="min-w-0 flex-1 bg-transparent text-sm font-medium text-ink outline-none placeholder:text-[#6b7280] dark:text-white dark:placeholder:text-[#9ca3af] sm:text-[0.95rem]"
                    type="search"
                    :placeholder="copy.headerSearchPlaceholder"
                >
                <button
                    class="hidden h-8 items-center justify-center rounded-full bg-wine px-4 text-xs font-semibold text-white shadow-md shadow-wine/25 transition hover:bg-wine-deep dark:bg-wine-soft md:inline-flex"
                    type="submit"
                    aria-label="Search"
                >
                    <FontAwesomeIcon :icon="icons.search" />
                </button>
            </form>

            <div class="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-2">
                <button
                    class="hidden size-11 place-items-center rounded-full border border-black/10 bg-white text-[11px] font-semibold text-ink shadow-sm transition hover:border-wine/40 hover:text-wine dark:border-white/10 dark:bg-white/8 dark:text-white sm:grid"
                    type="button"
                    :aria-label="localeToggleLabel"
                    @click="toggleLocale"
                >
                    {{ localeToggleLabel }}
                </button>
                <button
                    class="hidden size-11 place-items-center rounded-full border border-black/10 bg-white text-ink shadow-sm transition hover:border-wine/40 hover:text-wine dark:border-white/10 dark:bg-white/8 dark:text-white sm:grid"
                    type="button"
                    :aria-label="themeLabel"
                    @click="toggleTheme"
                >
                    <FontAwesomeIcon :icon="themeIcon" />
                </button>
                <button
                    class="hidden h-11 items-center gap-2 rounded-full border border-black/10 bg-white px-4 text-sm font-semibold text-ink transition hover:border-wine/40 hover:bg-white dark:border-white/10 dark:bg-white/8 dark:text-white md:inline-flex"
                    type="button"
                    @click="openAccount"
                >
                    <FontAwesomeIcon :icon="icons.user" class="text-wine" />
                    {{ user?.name || copy.account }}
                </button>
                <button
                    class="relative hidden size-11 place-items-center rounded-full border border-black/10 bg-white text-ink transition hover:border-wine/40 dark:border-white/10 dark:bg-white/8 dark:text-white md:grid"
                    :aria-label="copy.wishlist"
                    type="button"
                    @click="openWishlist"
                >
                    <FontAwesomeIcon :icon="icons.heart" class="text-wine" />
                    <span class="absolute -right-0.5 -top-0.5 grid min-w-5 place-items-center rounded-full bg-wine px-1 text-[10px] font-semibold leading-4 text-white">{{ wishlistCount }}</span>
                </button>
                <button
                    class="inline-flex h-11 items-center gap-2 rounded-full bg-wine px-3.5 text-sm font-semibold text-white shadow-[0_14px_30px_-16px_rgb(143_38_56_/_0.9)] transition hover:bg-wine-deep dark:bg-wine-soft sm:px-4"
                    type="button"
                    @click="openCart"
                >
                    <FontAwesomeIcon :icon="icons.cart" />
                    <span class="hidden sm:inline">{{ copy.cart }}</span>
                    <span class="grid min-w-5 place-items-center rounded-full bg-black/20 px-1.5 text-[11px] font-semibold text-white">{{ itemCount }}</span>
                </button>
            </div>
        </div>

        <div v-if="isMobileMenuOpen" class="border-t border-black/10 bg-white px-4 py-4 shadow-2xl shadow-black/10 dark:border-white/10 dark:bg-[#171717] lg:hidden">
            <div class="grid gap-3">
                <button
                    class="flex min-h-12 items-center justify-between gap-3 rounded-2xl bg-ink px-4 py-3 text-left text-sm font-semibold text-white shadow-lg shadow-black/15 dark:bg-wine-soft"
                    type="button"
                    @click="isMobileMenuOpen = false; openAccount()"
                >
                    <span class="inline-flex items-center gap-3">
                        <FontAwesomeIcon :icon="icons.user" class="text-wine" />
                        {{ user?.name || copy.loginRegister }}
                    </span>
                    <FontAwesomeIcon :icon="icons.arrowRight" />
                </button>

                <div class="grid grid-cols-2 gap-2">
                    <a
                        :href="user ? '/customer/dashboard?panel=wishlist' : customerLoginUrl('/customer/dashboard?panel=wishlist')"
                        class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-black/10 bg-white px-3 text-sm font-semibold text-ink shadow-sm dark:border-white/10 dark:bg-white/8 dark:text-white"
                        @click="isMobileMenuOpen = false"
                    >
                        <FontAwesomeIcon :icon="icons.heart" class="text-wine" />
                        {{ copy.wishlist }}
                    </a>
                    <a
                        href="/order-tracking"
                        class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-black/10 bg-white px-3 text-sm font-semibold text-ink shadow-sm dark:border-white/10 dark:bg-white/8 dark:text-white"
                        @click="isMobileMenuOpen = false"
                    >
                        <FontAwesomeIcon :icon="icons.truck" class="text-wine" />
                        {{ copy.trackOrder }}
                    </a>
                </div>

                <a
                    :href="user ? '/customer/dashboard?panel=support' : customerLoginUrl('/customer/dashboard?panel=support')"
                    class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-black/10 bg-white px-3 text-sm font-semibold text-ink shadow-sm dark:border-white/10 dark:bg-white/8 dark:text-white"
                    @click="isMobileMenuOpen = false"
                >
                    <FontAwesomeIcon :icon="icons.shield" class="text-wine" />
                    {{ copy.customerCare }}
                </a>

                <div class="grid grid-cols-2 gap-2 pt-1 sm:hidden">
                    <button
                        class="grid h-11 place-items-center rounded-2xl border border-black/10 bg-white text-xs font-semibold text-ink shadow-sm dark:border-white/10 dark:bg-white/8 dark:text-white"
                        type="button"
                        :aria-label="localeToggleLabel"
                        @click="toggleLocale"
                    >
                        {{ localeToggleLabel }}
                    </button>
                    <button
                        class="grid h-11 place-items-center rounded-2xl border border-black/10 bg-white text-ink shadow-sm dark:border-white/10 dark:bg-white/8 dark:text-white"
                        type="button"
                        :aria-label="themeLabel"
                        @click="toggleTheme"
                    >
                        <FontAwesomeIcon :icon="themeIcon" />
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>
