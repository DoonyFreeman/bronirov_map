/**
 * Ручные TypeScript-типы под текущую схему WPGraphQL.
 * Когда WP запущен, можно перейти на codegen (`npm run codegen`).
 */

export interface TaxonomyTerm {
  name: string;
  slug: string;
}

export interface TaxonomyTermWithCount extends TaxonomyTerm {
  count: number | null;
}

export interface MediaItem {
  sourceUrl: string | null;
  altText: string | null;
}

export interface Company {
  databaseId: number;
  title: string;
  slug: string;
  excerpt: string | null;
  phone: string | null;
  address: string | null;
  priceFrom: number | null;
  latitude: number | null;
  longitude: number | null;
  featuredImage: { node: MediaItem } | null;
  cities: { nodes: TaxonomyTerm[] };
  serviceCategories: { nodes: TaxonomyTerm[] };
}

export interface CompaniesResponse {
  companies: { nodes: Company[] };
}

export interface CatalogFiltersResponse {
  cities: { nodes: TaxonomyTermWithCount[] };
  serviceCategories: { nodes: TaxonomyTermWithCount[] };
}

export interface CompanyFilters {
  city?: string;
  category?: string;
}
