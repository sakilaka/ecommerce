<?php

namespace App\Enums;

enum HomepageSectionType: string
{
    case Announcement = 'announcement';
    case Hero = 'hero';
    case PromoBanner = 'promo_banner';
    case FeaturedCollection = 'featured_collection';
    case ProductRail = 'product_rail';
    case CategoryRail = 'category_rail';
    case BrandRail = 'brand_rail';
    case SeoLinks = 'seo_links';
}
