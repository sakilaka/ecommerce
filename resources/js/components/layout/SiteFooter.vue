<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';

const { copy, icons } = useStorefront();
</script>

<template>
    <footer class="bg-ink text-white">
        <div class="mx-auto max-w-[1440px] px-5 py-10 sm:px-8 lg:px-12">
            <div class="grid gap-8 lg:grid-cols-[0.34fr_0.66fr]">
                <div>
                    <a href="/" class="inline-flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-2xl bg-wine text-lg font-semibold">A</span>
                        <span class="leading-tight">
                            <span class="block text-lg font-semibold">{{ copy.brand }}</span>
                            <span class="block text-[11px] font-semibold text-white/50">{{ copy.tagline }}</span>
                        </span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-white/55">{{ copy.footerSection.description }}</p>

                    <div class="mt-5 rounded-2xl bg-white/6 p-4 ring-1 ring-white/10">
                        <p class="text-sm font-semibold text-wine">{{ copy.footerSection.customerCareTitle }}</p>
                        <div class="mt-3 grid gap-1.5 text-sm font-semibold text-white/65">
                            <p>{{ copy.footerSection.hotline }}</p>
                            <p>{{ copy.footerSection.email }}</p>
                            <p>{{ copy.footerSection.hours }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <details v-for="group in copy.footerSection.groups" :key="group.title" class="group rounded-2xl bg-white/6 p-4 ring-1 ring-white/10 lg:bg-transparent lg:p-0 lg:ring-0" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-white [&::-webkit-details-marker]:hidden lg:cursor-default">
                            {{ group.title }}
                            <FontAwesomeIcon :icon="icons.chevronDown" class="transition group-open:rotate-180 lg:hidden" />
                        </summary>
                        <div class="mt-4 grid gap-2.5">
                            <a v-for="link in group.links" :key="link.label" :href="link.href" class="text-sm font-semibold text-white/55 transition hover:text-wine">
                                {{ link.label }}
                            </a>
                        </div>
                    </details>
                </div>
            </div>

            <div class="mt-8 grid gap-5 border-t border-white/10 pt-6 lg:grid-cols-[1fr_auto] lg:items-end">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold text-wine">{{ copy.footerSection.paymentTitle }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span v-for="method in copy.footerSection.paymentMethods" :key="method" class="rounded-full bg-white/8 px-3 py-1.5 text-[11px] font-semibold text-white/70 ring-1 ring-white/10">
                                {{ method }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-wine">{{ copy.footerSection.socialTitle }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a v-for="channel in copy.footerSection.socialChannels" :key="channel" :href="'/products?q=' + encodeURIComponent(channel)" class="grid size-9 place-items-center rounded-full bg-white/8 text-[11px] font-semibold text-white ring-1 ring-white/10 transition hover:bg-wine">
                                {{ channel.slice(0, 2) }}
                            </a>
                        </div>
                    </div>
                </div>

                <p class="text-sm font-semibold text-white/45 lg:text-right">{{ copy.footerSection.copyright }}</p>
            </div>
        </div>
    </footer>
</template>
