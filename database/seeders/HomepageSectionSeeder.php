<?php

namespace Database\Seeders;

use App\Enums\HomepageSectionType;
use App\Models\HomepageSection;
use Illuminate\Database\Seeder;

class HomepageSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = [
            $this->section('announcement-free-delivery', HomepageSectionType::Announcement, 'Announcement Bar', 1, ['message' => 'Free Delivery on orders over ৳1,999', 'support' => 'Hotline: 09612-000-000'], ['message' => '৳১,৯৯৯ এর বেশি অর্ডারে ফ্রি ডেলিভারি', 'support' => 'হটলাইন: ০৯৬১২-০০০-০০০']),
            $this->section('category-navigation', HomepageSectionType::CategoryRail, 'Category Navigation', 3, ['title' => 'Main Category Navigation'], ['title' => 'প্রধান ক্যাটাগরি নেভিগেশন']),
            $this->section('eid-mega-sale-hero', HomepageSectionType::Hero, 'Hero Campaign', 10, ['eyebrow' => 'Eid Mega Sale', 'title' => 'Up to 50% off for Eid shopping.', 'subtitle' => 'Fashion, beauty, gadgets, grocery, and gifts with nationwide delivery.', 'cta' => 'Shop Eid Sale'], ['eyebrow' => 'ঈদ মেগা সেল', 'title' => 'ঈদ শপিংয়ে ৫০% পর্যন্ত ছাড়।', 'subtitle' => 'Fashion, beauty, gadgets, grocery এবং gifts সারাদেশে delivery.', 'cta' => 'ঈদ সেল দেখুন'], ['cta_url' => '/products?campaign=eid-mega-sale', 'image' => '/images/premium-hero.png']),
            $this->section('trust-service-strip', HomepageSectionType::FeaturedCollection, 'Trust / Service Strip', 20, ['title' => 'Nationwide Delivery | Cash on Delivery | Secure Payments | Easy Returns'], ['title' => 'সারাদেশে ডেলিভারি | ক্যাশ অন ডেলিভারি | সিকিউর পেমেন্ট | সহজ রিটার্ন']),
            $this->section('shop-by-category', HomepageSectionType::CategoryRail, 'Shop by Category', 30, ['title' => 'Shop by Category'], ['title' => 'ক্যাটাগরি অনুযায়ী শপিং']),
            $this->section('flash-sale', HomepageSectionType::ProductRail, 'Flash Sale', 40, ['title' => 'Real deals for today only.', 'cta' => 'View Flash Sale'], ['title' => 'আজকের ফ্ল্যাশ সেল', 'cta' => 'ফ্ল্যাশ সেল দেখুন']),
            $this->section('featured-collections', HomepageSectionType::FeaturedCollection, 'Featured Collections', 50, ['title' => 'Premium picks, arranged for effortless shopping.', 'cta' => 'Explore Collections'], ['title' => 'প্রিমিয়াম কালেকশন', 'cta' => 'কালেকশন দেখুন']),
            $this->section('best-sellers', HomepageSectionType::ProductRail, 'Best Sellers', 60, ['title' => 'Popular picks with proven demand.', 'cta' => 'View All Best Sellers'], ['title' => 'জনপ্রিয় বেস্ট সেলার', 'cta' => 'সব বেস্ট সেলার দেখুন']),
            $this->section('smartphone-payment-banner', HomepageSectionType::PromoBanner, 'Promotional Banner', 70, ['title' => 'Upgrade Your Smartphone', 'highlight' => 'save up to ৳10,000', 'subtitle' => 'Verified bank and MFS offers on selected official phones.', 'cta' => 'Shop Phone Deals'], ['title' => 'স্মার্টফোন আপগ্রেড করুন', 'highlight' => '৳১০,০০০ পর্যন্ত সেভ', 'subtitle' => 'Selected official phone-এ verified bank এবং MFS offer.', 'cta' => 'ফোন ডিল দেখুন'], ['cta_url' => '/products?category=smartphones', 'image' => '/images/category-collage.png']),
            $this->section('popular-products', HomepageSectionType::ProductRail, 'Popular Products', 80, ['title' => 'Explore popular products.', 'cta' => 'Apply Filters'], ['title' => 'জনপ্রিয় পণ্য দেখুন', 'cta' => 'ফিল্টার করুন']),
            $this->section('new-arrivals', HomepageSectionType::ProductRail, 'New Arrivals', 90, ['title' => 'Fresh arrivals just added.', 'cta' => 'View New Arrivals'], ['title' => 'নতুন আসা পণ্য', 'cta' => 'নতুন পণ্য দেখুন']),
            $this->section('official-brand-stores', HomepageSectionType::BrandRail, 'Official Brand Stores', 100, ['title' => 'Verified brand destinations.', 'cta' => 'Browse Brand Stores'], ['title' => 'ভেরিফায়েড ব্র্যান্ড স্টোর', 'cta' => 'ব্র্যান্ড স্টোর দেখুন']),
            $this->section('shop-by-budget', HomepageSectionType::CategoryRail, 'Shop by Budget', 110, ['title' => 'Find value faster by price range.', 'cta' => 'Shop this range'], ['title' => 'বাজেট অনুযায়ী শপিং', 'cta' => 'এই বাজেটে দেখুন']),
            $this->section('recommended-for-you', HomepageSectionType::ProductRail, 'Recommended for You', 120, ['title' => 'Personal picks with smart fallbacks.', 'cta' => 'Refresh Recommendations'], ['title' => 'আপনার জন্য সাজেস্টেড', 'cta' => 'Recommendation Refresh করুন']),
            $this->section('trending-now', HomepageSectionType::CategoryRail, 'Trending Now', 130, ['title' => 'Fast-moving searches and categories.'], ['title' => 'এখন ট্রেন্ডিং']),
            $this->section('seasonal-collection', HomepageSectionType::FeaturedCollection, 'Seasonal Collection', 140, ['title' => 'Bangladesh seasonal shopping calendar.', 'cta' => 'Shop Seasonal Picks'], ['title' => 'সিজনাল কালেকশন', 'cta' => 'সিজনাল পণ্য দেখুন']),
            $this->section('why-shop-with-us', HomepageSectionType::FeaturedCollection, 'Why Shop With Us', 150, ['title' => 'Reasons to complete checkout with Aurelia.'], ['title' => 'কেন আমাদের থেকে শপ করবেন']),
            $this->section('customer-reviews', HomepageSectionType::FeaturedCollection, 'Customer Reviews', 160, ['title' => 'Real feedback from verified purchases.', 'cta' => 'Browse Top Rated'], ['title' => 'ভেরিফায়েড কাস্টমার রিভিউ', 'cta' => 'টপ রেটেড দেখুন']),
            $this->section('mobile-app-promotion', HomepageSectionType::FeaturedCollection, 'Mobile App Promotion', 170, ['title' => 'Shop faster from your pocket.'], ['title' => 'মোবাইল অ্যাপে দ্রুত শপিং']),
            $this->section('newsletter-offers', HomepageSectionType::FeaturedCollection, 'Newsletter / Offers', 180, ['title' => 'Get sale alerts and exclusive offers.'], ['title' => 'অফার আপডেট পান']),
            $this->section('popular-searches', HomepageSectionType::SeoLinks, 'Popular Searches / SEO', 190, ['title' => 'Popular search links.'], ['title' => 'জনপ্রিয় সার্চ']),
            $this->section('site-footer', HomepageSectionType::FeaturedCollection, 'Footer', 200, ['title' => 'Company, support, policy, social, and payment links.'], ['title' => 'Company, support, policy, social এবং payment links.']),
        ];

        HomepageSection::query()
            ->whereIn('key', ['eid-mega-sale-banner', 'ramadan-daily-deals-banner', 'boishakh-style-refresh-banner'])
            ->update(['is_active' => false]);

        foreach ($sections as $section) {
            HomepageSection::query()->updateOrCreate(
                ['key' => $section['key']],
                array_merge([
                    'is_active' => true,
                    'starts_at' => null,
                    'ends_at' => null,
                ], $section)
            );
        }
    }

    /**
     * @param  array<string, mixed>  $contentEn
     * @param  array<string, mixed>  $contentBn
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function section(string $key, HomepageSectionType $type, string $title, int $sortOrder, array $contentEn, array $contentBn, array $settings = []): array
    {
        return [
            'key' => $key,
            'type' => $type->value,
            'title' => $title,
            'sort_order' => $sortOrder,
            'content' => [
                'en' => $contentEn,
                'bn' => $contentBn,
            ],
            'settings' => $settings,
        ];
    }
}
