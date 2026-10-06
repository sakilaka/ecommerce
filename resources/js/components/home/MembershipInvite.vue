<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, heroImage, icons } = useStorefront();
const benefitIcons = [icons.crown, icons.gift, icons.user];
</script>

<template>
    <section class="bg-[#ffffff] py-14 transition-colors dark:bg-[#171717] sm:py-16 lg:py-20">
        <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
            <div class="relative overflow-hidden rounded-lg bg-[#171512] shadow-2xl shadow-black/20 ring-1 ring-black/10 dark:bg-[#9f243a] dark:ring-white/10">
                <img
                    :src="heroImage"
                    alt="Premium membership invitation"
                    class="absolute inset-y-0 right-0 hidden h-full w-1/2 object-cover opacity-38 mix-blend-screen dark:opacity-24 dark:mix-blend-multiply lg:block"
                >
                <div class="absolute inset-0 bg-gradient-to-r from-[#171512] via-[#171512]/96 to-[#171512]/50 dark:from-[#9f243a] dark:via-[#9f243a]/94 dark:to-[#9f243a]/55"></div>

                <div class="relative z-10 grid gap-10 p-6 sm:p-8 lg:grid-cols-[0.58fr_0.42fr] lg:p-10 xl:p-12">
                    <div class="max-w-2xl text-white dark:text-white">
                        <p class="inline-flex items-center gap-2 rounded-full border border-[#9f243a]/35 bg-white/8 px-4 py-2 text-xs font-black uppercase tracking-normal text-[#ff8aa0] dark:border-black/10 dark:bg-black/8 dark:text-white">
                            <FontAwesomeIcon :icon="icons.crown" />
                            {{ copy.membershipSection.eyebrow }}
                        </p>

                        <h2 class="mt-7 max-w-2xl font-serif text-4xl leading-[1.1] sm:text-5xl lg:text-[3.55rem]">
                            <span class="block">{{ copy.membershipSection.titleLineOne }}</span>
                            <span class="block italic text-[#ff8aa0] dark:text-white">{{ copy.membershipSection.titleLineTwo }}</span>
                        </h2>

                        <p class="mt-6 max-w-xl text-base leading-8 text-white/76 dark:text-white/76">
                            {{ copy.membershipSection.subtitle }}
                        </p>

                        <form class="mt-8 flex max-w-xl flex-col gap-3 rounded-lg bg-white p-2 shadow-2xl shadow-black/20 dark:bg-[#171512] sm:flex-row" @submit.prevent>
                            <label class="sr-only" for="member-email">{{ copy.membershipSection.placeholder }}</label>
                            <input
                                id="member-email"
                                type="email"
                                class="h-12 min-w-0 flex-1 rounded-md px-4 text-sm font-semibold text-[#171512] outline-none placeholder:text-[#6b7280] dark:bg-[#171512] dark:text-white dark:placeholder:text-[#9ca3af]"
                                :placeholder="copy.membershipSection.placeholder"
                            >
                            <button class="inline-flex h-12 items-center justify-center gap-3 rounded-lg bg-[#8f2638] px-5 text-sm font-black text-white transition hover:bg-[#741c2b] dark:bg-[#9f243a] dark:text-white dark:hover:bg-[#861e31]" type="submit">
                                {{ copy.membershipSection.action }}
                                <FontAwesomeIcon :icon="icons.arrowRight" />
                            </button>
                        </form>

                        <p class="mt-4 text-sm text-white/58 dark:text-white/70">
                            {{ copy.membershipSection.note }}
                        </p>
                    </div>

                    <div class="grid content-end gap-4">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div v-for="stat in copy.membershipSection.stats" :key="stat.label" class="rounded-lg border border-white/12 bg-white/8 p-5 text-white backdrop-blur dark:border-black/10 dark:bg-black/8 dark:text-white">
                                <p class="font-serif text-4xl leading-none">{{ stat.value }}</p>
                                <p class="mt-2 text-sm font-bold text-white/68 dark:text-white/70">{{ stat.label }}</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-white/12 bg-white/8 p-4 backdrop-blur dark:border-black/10 dark:bg-black/8">
                            <div v-for="(benefit, index) in copy.membershipSection.benefits" :key="benefit.title" class="flex gap-4 border-b border-white/10 py-4 first:pt-0 last:border-b-0 last:pb-0 dark:border-black/10">
                                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-[#9f243a]/16 text-[#ff8aa0] dark:bg-black/10 dark:text-white">
                                    <FontAwesomeIcon :icon="benefitIcons[index]" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-white dark:text-white">{{ benefit.title }}</p>
                                    <p class="mt-1 text-xs leading-5 text-white/62 dark:text-white/70">{{ benefit.text }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
