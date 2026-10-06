<script setup>
import { computed, onMounted, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useStorefront } from '../../composables/useStorefront';
import { useStorefrontLinks } from '../../composables/useStorefrontLinks';

const { categoryCollageImage, copy, icons, locale } = useStorefront();
const { campaignHref } = useStorefrontLinks();

const campaignSection = ref(null);

const campaignContent = computed(() => campaignSection.value?.content?.[locale.value] ?? campaignSection.value?.content?.en ?? copy.value.promoBanner);
const campaignSettings = computed(() => campaignSection.value?.settings ?? {});
const campaignCoupon = computed(() => campaignSection.value?.coupon ?? null);
const campaignImage = computed(() => campaignSettings.value.image || categoryCollageImage);
const campaignUrl = computed(() => campaignSettings.value.cta_url || campaignHref('smartphone upgrade'));
const fallbackStats = computed(() => copy.value.promoBanner.stats);
const campaignNote = computed(() => campaignCoupon.value ? 'Use coupon ' + campaignCoupon.value.code : (campaignContent.value.note || copy.value.promoBanner.note));
const couponValue = computed(() => {
    if (!campaignCoupon.value) {
        return '';
    }

    return campaignCoupon.value.type === 'percent'
        ? campaignCoupon.value.value + '% OFF'
        : '৳' + Math.round(Number(campaignCoupon.value.value || 0) / 100).toLocaleString('en-BD') + ' OFF';
});

const loadCampaign = async () => {
    try {
        const response = await fetch('/api/storefront/homepage-sections?type=promo_banner', { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return;
        }
        const payload = await response.json();
        campaignSection.value = payload.data?.find((section) => section.key === 'smartphone-payment-banner') ?? payload.data?.[0] ?? null;
    } catch (error) {
        campaignSection.value = null;
    }
};

onMounted(() => loadCampaign());
</script>

<template>
    <section class="bg-white py-12 transition-colors dark:bg-[#111111] sm:py-14">
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="relative overflow-hidden rounded-[1.6rem] bg-ink text-white shadow-[0_24px_40px_-20px_rgb(0_0_0_/_0.55)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_82%_40%,rgb(143_38_56_/_0.72),transparent_46%)]"></div>
                <div
                    class="pointer-events-none absolute inset-y-0 right-0 hidden w-[42%] opacity-35 sm:block"
                    :style="{
                        backgroundImage: 'url(' + campaignImage + ')',
                        backgroundPosition: campaignSettings.image ? 'center' : '0% 0%',
                        backgroundRepeat: 'no-repeat',
                        backgroundSize: campaignSettings.image ? 'cover' : '400% 200%',
                    }"
                ></div>

                <div class="relative grid gap-6 p-5 sm:p-7 lg:grid-cols-[1fr_auto] lg:items-center lg:p-8">
                    <div class="max-w-2xl">
                        <p class="inline-flex items-center gap-2 text-[11px] font-semibold text-wine">
                            <FontAwesomeIcon :icon="icons.gem" />
                            {{ campaignContent.eyebrow || copy.promoBanner.eyebrow }}
                        </p>
                        <h2 class="mt-3 text-3xl font-semibold leading-snug sm:text-4xl">
                            {{ campaignContent.title || copy.promoBanner.title }}
                            <span class="text-wine">{{ campaignContent.highlight || copy.promoBanner.highlight }}</span>
                        </h2>
                        <p class="mt-3 max-w-xl text-sm leading-7 text-white/65">
                            {{ campaignContent.subtitle || copy.promoBanner.subtitle }}
                        </p>

                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            <a
                                :href="campaignUrl"
                                class="inline-flex h-10 items-center gap-2 rounded-full bg-wine px-4 text-sm font-semibold text-white transition hover:bg-white hover:text-ink"
                            >
                                {{ campaignContent.cta || copy.promoBanner.cta }}
                                <FontAwesomeIcon :icon="icons.arrowRight" />
                            </a>
                            <p class="text-[12px] font-semibold text-white/55">{{ campaignNote }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-3 lg:w-64 lg:grid-cols-1">
                        <div v-if="campaignCoupon" class="rounded-2xl bg-white px-4 py-3 text-ink">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-wine">Coupon</p>
                            <div class="mt-1 flex items-end justify-between gap-3">
                                <span class="text-lg font-semibold">{{ campaignCoupon.code }}</span>
                                <span class="text-[11px] font-semibold text-wine">{{ couponValue }}</span>
                            </div>
                        </div>
                        <div
                            v-for="stat in fallbackStats"
                            :key="stat.label"
                            class="flex items-center justify-between gap-3 rounded-2xl bg-white/8 px-4 py-3 ring-1 ring-white/10"
                        >
                            <span class="text-lg font-semibold">{{ stat.value }}</span>
                            <span class="text-right text-[10px] font-semibold uppercase tracking-wide text-white/55">{{ stat.label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
