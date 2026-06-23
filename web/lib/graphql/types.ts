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
  averageRating: number | null;
  reviewCount: number | null;
  featuredImage: { node: MediaItem } | null;
  cities: { nodes: TaxonomyTerm[] };
  serviceCategories: { nodes: TaxonomyTerm[] };
}

export interface Service {
  databaseId: number;
  title: string;
  price: number | null;
  duration: number | null;
}

export interface Review {
  databaseId: number;
  date: string | null;
  author: string | null;
  rating: number | null;
  text: string | null;
  verified: boolean;
}

export interface CompanyHours {
  day: string | null;
  open: string | null;
  close: string | null;
}

export interface GalleryImage {
  sourceUrl: string | null;
  altText: string | null;
}

export interface CompanyProfile extends Company {
  content: string | null;
  services: Service[];
  reviews: Review[];
  hours: CompanyHours[];
  gallery: GalleryImage[];
}

export interface CompanyBySlugResponse {
  company: CompanyProfile | null;
}

export interface AllCompanySlugsResponse {
  companies: { nodes: { slug: string }[] };
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
