const defaultTitle = 'Aurelia | Premium Bangladesh Ecommerce';
const defaultDescription = 'Shop authentic electronics, fashion, beauty, home, grocery, and lifestyle products across Bangladesh.';
const defaultImage = '/images/premium-hero.png';
const defaultSiteName = 'Aurelia Premium Market';

const absoluteUrl = (url) => {
    if (!url) {
        return '';
    }

    if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
    }

    return window.location.origin + (url.startsWith('/') ? url : '/' + url);
};

const setMetaTag = (selector, attributes) => {
    let element = document.head.querySelector(selector);

    if (!element) {
        element = document.createElement('meta');
        Object.entries(attributes.identity).forEach(([key, value]) => element.setAttribute(key, value));
        document.head.appendChild(element);
    }

    element.setAttribute('content', attributes.content || '');
};

const setCanonical = (href) => {
    if (!href) {
        return;
    }

    let element = document.head.querySelector('link[rel="canonical"]');

    if (!element) {
        element = document.createElement('link');
        element.setAttribute('rel', 'canonical');
        document.head.appendChild(element);
    }

    element.setAttribute('href', href);
};

const setStructuredData = (items = []) => {
    document.head.querySelectorAll('script[data-aurelia-seo="true"]').forEach((element) => element.remove());

    items.filter(Boolean).forEach((item, index) => {
        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.dataset.aureliaSeo = 'true';
        script.id = item.id || 'aurelia-structured-data-' + index;
        script.textContent = JSON.stringify(item.data || item);
        document.head.appendChild(script);
    });
};

export function useSeo() {
    const setSeo = ({ title, description, canonical, image, type = 'website', robots = 'index, follow', locale = 'en_BD', structuredData = [] } = {}) => {
        const resolvedTitle = title || defaultTitle;
        const resolvedDescription = description || defaultDescription;
        const resolvedCanonical = canonical || window.location.origin + window.location.pathname;
        const resolvedImage = absoluteUrl(image || defaultImage);

        document.title = resolvedTitle;
        document.documentElement.lang = locale === 'bn_BD' ? 'bn-BD' : 'en-BD';

        setMetaTag('meta[name="description"]', { identity: { name: 'description' }, content: resolvedDescription });
        setMetaTag('meta[name="robots"]', { identity: { name: 'robots' }, content: robots });
        setMetaTag('meta[property="og:title"]', { identity: { property: 'og:title' }, content: resolvedTitle });
        setMetaTag('meta[property="og:description"]', { identity: { property: 'og:description' }, content: resolvedDescription });
        setMetaTag('meta[property="og:type"]', { identity: { property: 'og:type' }, content: type });
        setMetaTag('meta[property="og:site_name"]', { identity: { property: 'og:site_name' }, content: defaultSiteName });
        setMetaTag('meta[property="og:url"]', { identity: { property: 'og:url' }, content: resolvedCanonical });
        setMetaTag('meta[property="og:image"]', { identity: { property: 'og:image' }, content: resolvedImage });
        setMetaTag('meta[property="og:locale"]', { identity: { property: 'og:locale' }, content: locale });
        setMetaTag('meta[name="twitter:card"]', { identity: { name: 'twitter:card' }, content: 'summary_large_image' });
        setMetaTag('meta[name="twitter:title"]', { identity: { name: 'twitter:title' }, content: resolvedTitle });
        setMetaTag('meta[name="twitter:description"]', { identity: { name: 'twitter:description' }, content: resolvedDescription });
        setMetaTag('meta[name="twitter:image"]', { identity: { name: 'twitter:image' }, content: resolvedImage });
        setCanonical(resolvedCanonical);
        setStructuredData(structuredData);
    };

    return { setSeo };
}
