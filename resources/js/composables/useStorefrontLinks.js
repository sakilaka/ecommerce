const categoryAliases = {
    smartphones: 'electronics',
    smartphone: 'electronics',
    phones: 'electronics',
    electronics: 'electronics',
    gadgets: 'electronics',
    computers: 'electronics',
    laptop: 'electronics',
    laptops: 'electronics',
    'mobile accessories': 'electronics',
    'মোবাইল অ্যাকসেসরিজ': 'electronics',
    'স্মার্টফোন': 'electronics',
    'ইলেকট্রনিক্স': 'electronics',
    fashion: 'fashion',
    'men’s fashion': 'fashion',
    "men's fashion": 'fashion',
    'women’s fashion': 'fashion',
    "women's fashion": 'fashion',
    panjabi: 'fashion',
    saree: 'fashion',
    bags: 'fashion',
    sneakers: 'fashion',
    'eid collection': 'fashion',
    'eid fashion': 'fashion',
    'পুরুষ ফ্যাশন': 'fashion',
    'নারী ফ্যাশন': 'fashion',
    'ঈদ কালেকশন': 'fashion',
    beauty: 'beauty',
    skincare: 'beauty',
    'skin care': 'beauty',
    cosmetics: 'beauty',
    'বিউটি': 'beauty',
    'স্কিন কেয়ার': 'beauty',
    'home & living': 'home-living',
    home: 'home-living',
    appliances: 'home-living',
    'home essentials': 'home-living',
    'smart home': 'home-living',
    'হোম ও লিভিং': 'home-living',
    'হোম এসেনশিয়ালস': 'home-living',
    groceries: 'groceries',
    grocery: 'groceries',
    'daily grocery': 'groceries',
    'গ্রোসারি': 'groceries',
    'ডেইলি গ্রোসারি': 'groceries',
    sports: 'sports',
    'স্পোর্টস': 'sports',
    kids: 'kids',
    'baby care': 'kids',
    'কিডস': 'kids',
    'বেবি কেয়ার': 'kids',
    automotive: 'automotive',
    'অটোমোটিভ': 'automotive',
    offers: 'offers',
    offer: 'offers',
    'অফার': 'offers',
};

const brandAliases = {
    samsung: 'samsung',
    xiaomi: 'xiaomi',
    walton: 'walton',
    singer: 'singer',
    apex: 'apex',
    beautyhub: 'beautyhub',
};

const normalize = (value) => String(value || '').trim().toLowerCase();
const slugify = (value) => normalize(value).replace(/&/g, 'and').replace(/[^a-z0-9ঀ-৿]+/g, '-').replace(/^-+|-+$/g, '');

const withQuery = (params) => {
    const query = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && String(value).trim() !== '') {
            query.set(key, String(value));
        }
    });

    const queryString = query.toString();

    return '/products' + (queryString ? '?' + queryString : '');
};

const categorySlug = (value) => categoryAliases[normalize(value)] || slugify(value);
const brandSlug = (value) => brandAliases[normalize(value)] || slugify(value);

export function useStorefrontLinks() {
    return {
        brandHref: (brand) => withQuery({ brand: brandSlug(brand), sort: 'featured' }),
        campaignHref: (campaign) => withQuery({ campaign: slugify(campaign), sort: 'featured' }),
        categoryHref: (category) => withQuery({ category: categorySlug(category), sort: 'featured' }),
        collectionHref: (collection) => withQuery({ collection: slugify(collection), sort: 'featured' }),
        productHref: (product) => product?.slug ? '/products/' + product.slug : withQuery({ q: product?.name || '' }),
        searchHref: (query) => withQuery({ q: query }),
        sortHref: (sort = 'featured') => withQuery({ sort }),
        topicHref: (topic) => withQuery({ topic: slugify(topic), sort: 'featured' }),
        withQuery,
    };
}
