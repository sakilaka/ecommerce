<script setup>
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import AnnouncementBar from './components/layout/AnnouncementBar.vue';
import SiteHeader from './components/layout/SiteHeader.vue';
import CategoryNavigation from './components/layout/CategoryNavigation.vue';
import HeroSection from './components/home/HeroSection.vue';
import TrustServiceStrip from './components/home/TrustServiceStrip.vue';
import ShopByCategory from './components/home/ShopByCategory.vue';
import SiteFooter from './components/layout/SiteFooter.vue';
import CartDrawer from './components/cart/CartDrawer.vue';
import AccountDrawer from './components/auth/AccountDrawer.vue';
import { useSeo } from './composables/useSeo';

const FlashSale = defineAsyncComponent(() => import('./components/home/FlashSale.vue'));
const FeaturedCollections = defineAsyncComponent(() => import('./components/home/FeaturedCollections.vue'));
const BestSellers = defineAsyncComponent(() => import('./components/home/BestSellers.vue'));
const PromotionalBanner = defineAsyncComponent(() => import('./components/home/PromotionalBanner.vue'));
const PopularProducts = defineAsyncComponent(() => import('./components/home/PopularProducts.vue'));
const NewArrivals = defineAsyncComponent(() => import('./components/home/NewArrivals.vue'));
const OfficialBrandStores = defineAsyncComponent(() => import('./components/home/OfficialBrandStores.vue'));
const ShopByBudget = defineAsyncComponent(() => import('./components/home/ShopByBudget.vue'));
const RecommendedForYou = defineAsyncComponent(() => import('./components/home/RecommendedForYou.vue'));
const TrendingNow = defineAsyncComponent(() => import('./components/home/TrendingNow.vue'));
const SeasonalOffers = defineAsyncComponent(() => import('./components/home/SeasonalOffers.vue'));
const WhyShopWithUs = defineAsyncComponent(() => import('./components/home/WhyShopWithUs.vue'));
const CustomerReviews = defineAsyncComponent(() => import('./components/home/CustomerReviews.vue'));
const MobileAppPromotion = defineAsyncComponent(() => import('./components/home/MobileAppPromotion.vue'));
const NewsletterOffers = defineAsyncComponent(() => import('./components/home/NewsletterOffers.vue'));
const PopularSearches = defineAsyncComponent(() => import('./components/home/PopularSearches.vue'));
const CustomerDashboard = defineAsyncComponent(() => import('./components/customer/CustomerDashboard.vue'));
const CustomerAuthPage = defineAsyncComponent(() => import('./components/auth/CustomerAuthPage.vue'));
const AdminDashboard = defineAsyncComponent(() => import('./components/admin/AdminDashboard.vue'));
const AdminLogin = defineAsyncComponent(() => import('./components/admin/AdminLogin.vue'));
const ProductCatalogPage = defineAsyncComponent(() => import('./components/storefront/ProductCatalogPage.vue'));
const ProductDetailPage = defineAsyncComponent(() => import('./components/storefront/ProductDetailPage.vue'));
const OrderTrackingPage = defineAsyncComponent(() => import('./components/storefront/OrderTrackingPage.vue'));
const PaymentResultPage = defineAsyncComponent(() => import('./components/storefront/PaymentResultPage.vue'));

const isCustomerDashboard = computed(() => window.location.pathname.startsWith('/customer/dashboard'));
const isCustomerAuth = computed(() => window.location.pathname === '/login' || window.location.pathname === '/register');
const isAdminDashboard = computed(() => window.location.pathname.startsWith('/admin/dashboard'));
const isAdminLogin = computed(() => window.location.pathname.startsWith('/admin/login'));
const hideStorefrontChrome = computed(() => isAdminDashboard.value || isAdminLogin.value || isCustomerDashboard.value || isCustomerAuth.value);
const isProductDetail = computed(() => /^\/products\/[^/]+/.test(window.location.pathname));
const isProductCatalog = computed(() => window.location.pathname === '/products');
const isOrderTracking = computed(() => window.location.pathname === '/order-tracking');
const isPaymentResult = computed(() => window.location.pathname === '/payment/result');
const { setSeo } = useSeo();

const defaultHomepageSections = [
    'announcement-free-delivery',
    'category-navigation',
    'eid-mega-sale-hero',
    'trust-service-strip',
    'shop-by-category',
    'flash-sale',
    'featured-collections',
    'best-sellers',
    'smartphone-payment-banner',
    'popular-products',
    'new-arrivals',
    'official-brand-stores',
    'shop-by-budget',
    'recommended-for-you',
    'trending-now',
    'seasonal-collection',
    'why-shop-with-us',
    'customer-reviews',
    'mobile-app-promotion',
    'newsletter-offers',
    'popular-searches',
    'site-footer',
];

const activeHomepageSections = ref(new Set(defaultHomepageSections));
const isHomePage = computed(() => !isAdminDashboard.value && !isAdminLogin.value && !isCustomerDashboard.value && !isCustomerAuth.value && !isProductDetail.value && !isProductCatalog.value && !isOrderTracking.value && !isPaymentResult.value);
const showHomeSection = (key) => activeHomepageSections.value.has(key);
const showAnyHomeSection = (keys) => keys.some((key) => showHomeSection(key));

const loadHomepageVisibility = async () => {
    if (!isHomePage.value) {
        return;
    }

    try {
        const response = await fetch('/api/storefront/homepage-sections', { headers: { Accept: 'application/json' } });
        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload?.message ?? 'Could not load homepage sections');
        }

        activeHomepageSections.value = new Set((payload.data ?? []).map((section) => section.key));
    } catch (error) {
        activeHomepageSections.value = new Set(defaultHomepageSections);
    }
};

const seoByPath = computed(() => {
    if (isOrderTracking.value) {
        return {
            title: 'Track Your Order | Aurelia Bangladesh',
            description: 'Track Aurelia Bangladesh orders by order number and phone, including delivery status, payment status, and support details.',
            canonical: window.location.origin + '/order-tracking',
        };
    }

    if (isPaymentResult.value) {
        return {
            title: 'Payment Status | Aurelia Bangladesh',
            description: 'Review your Aurelia payment confirmation, order status, and next steps after checkout.',
            canonical: window.location.origin + '/payment/result',
            robots: 'noindex, follow',
        };
    }

    if (isCustomerAuth.value) {
        const isRegister = window.location.pathname === '/register';

        return {
            title: (isRegister ? 'Create Account' : 'Login') + ' | Aurelia Bangladesh',
            description: isRegister
                ? 'Create an Aurelia customer account to manage orders, addresses, and saved products.'
                : 'Sign in to your Aurelia customer account to manage orders, addresses, and support.',
            canonical: window.location.origin + window.location.pathname,
            robots: 'noindex, follow',
        };
    }

    if (isCustomerDashboard.value) {
        return {
            title: 'Customer Dashboard | Aurelia Bangladesh',
            description: 'Manage Aurelia orders, addresses, profile, wishlist, and customer support.',
            canonical: window.location.origin + '/customer/dashboard',
            robots: 'noindex, follow',
        };
    }

    if (!isAdminDashboard.value && !isAdminLogin.value && !isCustomerDashboard.value && !isCustomerAuth.value && !isProductDetail.value && !isProductCatalog.value) {
        return {
            title: 'Aurelia | Premium Ecommerce in Bangladesh',
            description: 'Shop Eid offers, electronics, fashion, beauty, home essentials, grocery, official brand stores, and fast Bangladesh delivery from Aurelia.',
            canonical: window.location.origin + '/',
            structuredData: [
                {
                    id: 'aurelia-organization',
                    data: {
                        '@context': 'https://schema.org',
                        '@type': 'Organization',
                        name: 'Aurelia Premium Market',
                        url: window.location.origin,
                        logo: window.location.origin + '/images/premium-hero.png',
                        contactPoint: [{ '@type': 'ContactPoint', telephone: '+8809612345678', contactType: 'customer support', areaServed: 'BD' }],
                    },
                },
                {
                    id: 'aurelia-website',
                    data: {
                        '@context': 'https://schema.org',
                        '@type': 'WebSite',
                        name: 'Aurelia',
                        url: window.location.origin,
                        potentialAction: {
                            '@type': 'SearchAction',
                            target: window.location.origin + '/products?q={search_term_string}',
                            'query-input': 'required name=search_term_string',
                        },
                    },
                },
            ],
        };
    }

    return null;
});

onMounted(() => {
    if (seoByPath.value) {
        setSeo(seoByPath.value);
    }

    loadHomepageVisibility();
});
</script>

<template>
    <div class="min-h-screen bg-white font-sans text-ink transition-colors duration-300 dark:bg-[#111111] dark:text-[#f9fafb]">
        <AnnouncementBar v-if="!hideStorefrontChrome && showHomeSection('announcement-free-delivery')" />
        <div v-if="!hideStorefrontChrome" class="sticky top-0 z-50 bg-white dark:bg-[#111111]">
            <SiteHeader />
            <CategoryNavigation v-if="!isHomePage || showHomeSection('category-navigation')" />
        </div>

        <main>
            <AdminDashboard v-if="isAdminDashboard" />
            <AdminLogin v-else-if="isAdminLogin" />
            <CustomerAuthPage v-else-if="isCustomerAuth" />
            <CustomerDashboard v-else-if="isCustomerDashboard" />
            <ProductDetailPage v-else-if="isProductDetail" />
            <ProductCatalogPage v-else-if="isProductCatalog" />
            <OrderTrackingPage v-else-if="isOrderTracking" />
            <PaymentResultPage v-else-if="isPaymentResult" />
            <template v-else>
            <HeroSection v-if="showHomeSection('eid-mega-sale-hero')" />
            <TrustServiceStrip v-if="showHomeSection('trust-service-strip')" />
            <ShopByCategory v-if="showHomeSection('shop-by-category')" />
            <FlashSale v-if="showHomeSection('flash-sale')" />
            <FeaturedCollections v-if="showHomeSection('featured-collections')" />
            <BestSellers v-if="showHomeSection('best-sellers')" />
            <PromotionalBanner v-if="showHomeSection('smartphone-payment-banner')" />
            <PopularProducts v-if="showHomeSection('popular-products')" />
            <NewArrivals v-if="showHomeSection('new-arrivals')" />
            <OfficialBrandStores v-if="showHomeSection('official-brand-stores')" />
            <ShopByBudget v-if="showHomeSection('shop-by-budget')" />
            <RecommendedForYou v-if="showHomeSection('recommended-for-you')" />
            <TrendingNow v-if="showHomeSection('trending-now')" />
            <SeasonalOffers v-if="showHomeSection('seasonal-collection')" />
            <WhyShopWithUs v-if="showHomeSection('why-shop-with-us')" />
            <CustomerReviews v-if="showHomeSection('customer-reviews')" />
            <MobileAppPromotion v-if="showHomeSection('mobile-app-promotion')" />
            <NewsletterOffers v-if="showHomeSection('newsletter-offers')" />
            <PopularSearches v-if="showHomeSection('popular-searches')" />
            </template>
        </main>

        <SiteFooter v-if="!hideStorefrontChrome && (!isHomePage || showHomeSection('site-footer'))" />
        <CartDrawer v-if="!isAdminDashboard && !isAdminLogin && !isCustomerAuth" />
        <AccountDrawer v-if="!hideStorefrontChrome" />
    </div>
</template>
