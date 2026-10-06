<script setup>
import { ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { copy, heroImage, icons } = useStorefront();
const { campaignHref, categoryHref } = useStorefrontLinks();
const hasHeroImage = ref(true);
const categoryIcons = [icons.shirt, icons.laptop, icons.gem, icons.basket];
const benefitIcons = [icons.shield, icons.truck, icons.gift];
const metricIcons = [icons.users, icons.star, icons.truck, icons.returns];
const promoIcons = [icons.gift, icons.store];
</script>

<template>
    <section class="relative isolate overflow-x-clip bg-white transition-colors dark:bg-[#111111]">
        <div class="pointer-events-none absolute -left-24 top-0 size-[28rem] rounded-full bg-wine/12 blur-3xl dark:bg-wine/20"></div>
        <div class="pointer-events-none absolute -right-16 bottom-0 size-[32rem] rounded-full bg-black/6 blur-3xl dark:bg-white/5"></div>
        <div class="atelier-grain pointer-events-none absolute inset-0 opacity-70 dark:opacity-20"></div>
        <div class="pointer-events-none absolute left-1/2 top-24 hidden h-[34rem] w-[34rem] -translate-x-1/3 rounded-full border border-black/8 dark:border-white/10 lg:block"></div>

        <div class="relative mx-auto max-w-[1440px] px-5 py-10 sm:px-8 lg:px-12 lg:py-16">
            <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-8">
                <div class="lg:col-span-6 xl:col-span-5">
                    <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-4 py-2 text-[11px] font-semibold text-wine shadow-sm animate-fade-up dark:border-white/10 dark:bg-white/8 dark:text-white">
                        <span class="size-1.5 rounded-full bg-wine animate-pulse-glow"></span>
                        <FontAwesomeIcon :icon="icons.wand" class="text-wine" />
                        {{ copy.hero.eyebrow }}
                    </p>

                    <h1 class="text-4xl font-semibold leading-[1.15] text-ink animate-fade-up [animation-delay:90ms] dark:text-white sm:text-5xl md:text-6xl xl:text-[4.2rem]">
                        <span class="block">{{ copy.hero.titleLineOne }}</span>
                        <span class="mt-1 block text-wine">{{ copy.hero.titleLineTwo }}</span>
                    </h1>

                    <div class="mt-7 flex items-center gap-3 animate-fade-up [animation-delay:160ms]">
                        <span class="h-px w-10 bg-wine"></span>
                        <span class="size-1.5 rounded-full bg-ink dark:bg-white"></span>
                        <span class="h-px w-16 bg-black/20 dark:bg-white/20"></span>
                    </div>

                    <p class="mt-7 max-w-xl text-base leading-8 text-[#374151] animate-fade-up [animation-delay:220ms] dark:text-[#e5e7eb] sm:text-lg">
                        {{ copy.hero.subtitle }}
                    </p>

                    <div class="mt-9 flex flex-col gap-3 animate-fade-up [animation-delay:300ms] sm:flex-row">
                        <a
                            :href="campaignHref('eid mega sale')"
                            class="shimmer-cta inline-flex h-14 items-center justify-center gap-3 rounded-full px-8 text-sm font-semibold text-white shadow-[0_18px_40px_-18px_rgb(143_38_56_/_0.85)] transition hover:brightness-110"
                        >
                            {{ copy.hero.primaryCta }}
                            <FontAwesomeIcon :icon="icons.arrowRight" />
                        </a>
                        <a
                            :href="campaignHref('deals')"
                            class="inline-flex h-14 items-center justify-center gap-3 rounded-full border border-black/15 bg-white px-8 text-sm font-semibold text-ink transition hover:-translate-y-0.5 hover:border-wine/40 dark:border-white/15 dark:bg-white/8 dark:text-white"
                        >
                            <FontAwesomeIcon :icon="icons.bag" class="text-wine" />
                            {{ copy.hero.secondaryCta }}
                        </a>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-2 animate-fade-up [animation-delay:380ms]">
                        <a
                            v-for="(category, index) in copy.hero.categories"
                            :key="category"
                            :href="categoryHref(category)"
                            class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3.5 py-2 text-xs font-semibold text-ink transition hover:border-wine/30 hover:text-wine dark:border-white/10 dark:bg-white/8 dark:text-white dark:hover:text-white"
                        >
                            <FontAwesomeIcon :icon="categoryIcons[index]" class="text-wine" />
                            {{ category }}
                        </a>
                    </div>

                    <div class="mt-8 grid max-w-xl gap-4 text-sm text-[#374151] animate-fade-up [animation-delay:460ms] dark:text-[#e5e7eb] sm:grid-cols-3">
                        <div v-for="(benefit, index) in copy.hero.benefits" :key="benefit.title" class="flex items-start gap-3">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-white text-wine shadow-sm ring-1 ring-black/10 dark:bg-white/10 dark:text-white dark:ring-white/10">
                                <FontAwesomeIcon :icon="benefitIcons[index]" />
                            </span>
                            <div>
                                <p class="font-semibold text-ink dark:text-white">{{ benefit.title }}</p>
                                <p class="mt-1 text-xs leading-5">{{ benefit.text }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative lg:col-span-6 xl:col-span-7">
                    <div class="relative mx-auto max-w-[560px] lg:ml-auto lg:max-w-none">
                        <div class="absolute -right-6 -top-6 hidden size-28 rounded-full border border-dashed border-black/15 animate-spin-slow dark:border-white/20 lg:block"></div>
                        <div class="absolute -bottom-10 -left-8 hidden size-40 rounded-full bg-wine/10 blur-2xl lg:block"></div>

                        <figure class="relative overflow-hidden rounded-atelier bg-gradient-to-br from-wine via-wine-deep to-ink shadow-[0_40px_80px_-32px_rgb(17_17_17_/_0.65)] ring-1 ring-black/10 animate-fade-up [animation-delay:180ms] dark:ring-white/10">
                            <span class="absolute left-5 top-5 z-10 h-7 w-7 border-l border-t border-white/80"></span>
                            <span class="absolute right-5 top-5 z-10 h-7 w-7 border-r border-t border-white/80"></span>
                            <span class="absolute bottom-5 left-5 z-10 h-7 w-7 border-b border-l border-white/80"></span>
                            <span class="absolute bottom-5 right-5 z-10 h-7 w-7 border-b border-r border-white/80"></span>

                            <img
                                v-show="hasHeroImage"
                                :src="heroImage"
                                alt="Eid campaign products"
                                class="aspect-[4/5] w-full object-cover sm:aspect-[5/4] lg:aspect-[4/5] xl:aspect-[5/4]"
                                @error="hasHeroImage = false"
                            >
                            <div v-if="!hasHeroImage" class="aspect-[4/5] w-full bg-[linear-gradient(160deg,#8f2638,#6a1c2b_48%,#111111)] sm:aspect-[5/4] lg:aspect-[4/5] xl:aspect-[5/4]"></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-ink/55 via-ink/10 to-transparent"></div>

                            <figcaption class="absolute bottom-6 left-6 right-6 flex items-end justify-between gap-4 text-white">
                                <div>
                                    <p class="text-[10px] font-semibold text-white/70">Lookbook 01</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ copy.hero.eyebrow }}</p>
                                </div>
                                <FontAwesomeIcon :icon="icons.crown" class="text-white" />
                            </figcaption>
                        </figure>

                        <div class="absolute -left-3 top-8 z-20 hidden size-[7.5rem] place-items-center sm:grid lg:-left-8 lg:top-16">
                            <div class="absolute inset-0 rounded-full border border-dashed border-wine animate-spin-slow"></div>
                            <div class="grid size-[6.15rem] place-items-center rounded-full bg-white text-center shadow-2xl ring-1 ring-black/10 backdrop-blur-md dark:bg-ink/90 dark:ring-white/10">
                                <div>
                                    <p class="text-[9px] font-semibold text-wine">{{ copy.hero.dealLabel }}</p>
                                    <p class="mt-0.5 text-3xl font-semibold leading-none text-ink dark:text-white">{{ copy.hero.dealValue }}</p>
                                    <p class="text-[10px] font-semibold text-ink/70 dark:text-white/70">{{ copy.hero.dealSuffix }}</p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-for="(promo, index) in copy.hero.promoCards"
                            :key="promo.title"
                            class="absolute z-20 hidden w-[min(100%,16.25rem)] lg:block"
                            :class="index === 0 ? 'bottom-8 left-4 animate-float-soft xl:-left-6' : 'right-4 top-12 animate-float-delayed xl:-right-8'"
                        >
                            <a
                                :href="categoryHref(promo.title)"
                                class="group block rounded-2xl border border-black/10 bg-white/95 p-4 shadow-[0_24px_50px_-28px_rgb(17_17_17_/_0.7)] backdrop-blur-xl transition hover:-translate-y-1 hover:border-wine/30 dark:border-white/10 dark:bg-[#171717]/92"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <span class="grid size-9 place-items-center rounded-xl bg-ink text-white shadow-md dark:bg-wine-soft">
                                        <FontAwesomeIcon :icon="promoIcons[index]" />
                                    </span>
                                    <span class="rounded-full bg-wine/8 px-2.5 py-1 text-[10px] font-semibold text-wine dark:bg-white/10 dark:text-white">{{ promo.value }}</span>
                                </div>
                                <h2 class="mt-3 text-lg font-semibold leading-tight text-ink dark:text-white">{{ promo.title }}</h2>
                                <p class="mt-1.5 line-clamp-2 text-sm leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ promo.text }}</p>
                                <span class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-wine transition group-hover:gap-3">
                                    {{ copy.hero.primaryCta }}
                                    <FontAwesomeIcon :icon="icons.arrowRight" />
                                </span>
                            </a>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:hidden">
                        <a
                            v-for="(promo, index) in copy.hero.promoCards"
                            :key="promo.title"
                            :href="categoryHref(promo.title)"
                            class="rounded-2xl border border-black/10 bg-white p-4 shadow-xl shadow-black/8 ring-1 ring-black/5 backdrop-blur-md transition hover:-translate-y-0.5 dark:border-white/10 dark:bg-white/8"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="grid size-9 place-items-center rounded-xl bg-ink text-white dark:bg-wine-soft">
                                    <FontAwesomeIcon :icon="promoIcons[index]" />
                                </span>
                                <span class="rounded-full bg-wine/8 px-2.5 py-1 text-[10px] font-semibold text-wine dark:bg-white/10 dark:text-white">{{ promo.value }}</span>
                            </div>
                            <h2 class="mt-3 text-lg font-semibold leading-tight text-ink dark:text-white">{{ promo.title }}</h2>
                            <p class="mt-1.5 line-clamp-2 text-sm leading-6 text-[#374151] dark:text-[#e5e7eb]">{{ promo.text }}</p>
                        </a>
                    </div>
                </div>
            </div>

            <dl class="mt-12 grid gap-3 animate-fade-up [animation-delay:520ms] sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(metric, index) in copy.hero.metrics"
                    :key="metric.label"
                    class="group relative overflow-hidden rounded-2xl border border-black/10 bg-white p-5 shadow-[0_20px_40px_-28px_rgb(17_17_17_/_0.45)] backdrop-blur-md transition hover:-translate-y-1 hover:border-wine/25 hover:shadow-xl dark:border-white/10 dark:bg-white/8"
                >
                    <span class="absolute inset-x-5 top-0 h-px bg-gradient-to-r from-transparent via-wine to-transparent"></span>
                    <div class="flex items-center gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-wine/8 text-xl text-wine transition group-hover:bg-wine group-hover:text-white dark:bg-wine/20 dark:text-white">
                            <FontAwesomeIcon :icon="metricIcons[index]" />
                        </span>
                        <div>
                            <dt class="text-2xl font-semibold text-ink dark:text-white">{{ metric.value }}</dt>
                            <dd class="mt-1 text-sm text-[#374151] dark:text-[#e5e7eb]">{{ metric.label }}</dd>
                        </div>
                    </div>
                </div>
            </dl>
        </div>
    </section>
</template>
