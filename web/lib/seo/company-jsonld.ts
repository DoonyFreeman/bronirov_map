import { env } from '@/lib/env';
import type { CompanyProfile } from '@/lib/graphql/types';

/**
 * Сформировать JSON-LD schema.org LocalBusiness для страницы компании.
 * Включает агрегированный рейтинг и каталог услуг (Offer).
 */
export function buildCompanyJsonLd(company: CompanyProfile, url: string) {
  const jsonLd: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    name: company.title,
    url,
    image: company.featuredImage?.node.sourceUrl ?? undefined,
    telephone: company.phone ?? undefined,
    address: company.address
      ? {
          '@type': 'PostalAddress',
          streetAddress: company.address,
          addressLocality: company.cities.nodes[0]?.name,
        }
      : undefined,
    geo:
      typeof company.latitude === 'number' && typeof company.longitude === 'number'
        ? {
            '@type': 'GeoCoordinates',
            latitude: company.latitude,
            longitude: company.longitude,
          }
        : undefined,
  };

  if (company.averageRating && company.reviewCount) {
    jsonLd.aggregateRating = {
      '@type': 'AggregateRating',
      ratingValue: company.averageRating,
      reviewCount: company.reviewCount,
      bestRating: 5,
      worstRating: 1,
    };
  }

  const offers = company.services
    .filter((s) => typeof s.price === 'number')
    .map((s) => ({
      '@type': 'Offer',
      name: s.title,
      price: s.price,
      priceCurrency: 'RUB',
    }));
  if (offers.length > 0) {
    jsonLd.makesOffer = offers;
  }

  return jsonLd;
}

/** Публичный URL страницы компании. */
export function companyUrl(slug: string): string {
  const base = env.siteUrl.replace(/\/$/, '');
  return `${base}/company/${encodeURIComponent(slug)}`;
}
