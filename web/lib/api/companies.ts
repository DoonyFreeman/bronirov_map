import { gqlFetch } from '@/lib/graphql/client';
import {
  ALL_COMPANY_SLUGS_QUERY,
  CATALOG_FILTERS_QUERY,
  COMPANIES_QUERY,
  COMPANY_BY_SLUG_QUERY,
} from '@/lib/graphql/queries';
import type {
  AllCompanySlugsResponse,
  CatalogFiltersResponse,
  CompaniesResponse,
  Company,
  CompanyBySlugResponse,
  CompanyFilters,
  CompanyProfile,
  TaxonomyTermWithCount,
} from '@/lib/graphql/types';

/** Общий тег кэша каталога — ревалидируется вебхуком из WordPress. */
const CATALOG_TAG = 'companies:list';
const REVALIDATE_SECONDS = 3600;

/**
 * Список компаний каталога с опциональными фильтрами по городу и категории.
 */
export async function getCompanies(filters: CompanyFilters = {}): Promise<Company[]> {
  const data = await gqlFetch<CompaniesResponse>(COMPANIES_QUERY, {
    variables: {
      city: filters.city ?? null,
      category: filters.category ?? null,
    },
    tags: [CATALOG_TAG],
    revalidate: REVALIDATE_SECONDS,
  });
  return data.companies.nodes;
}

/**
 * Полный профиль компании по slug (для страницы /company/[slug]).
 * Тег `companies:list` ревалидируется вебхуком при изменении компании,
 * её услуг или отзывов.
 */
export async function getCompanyBySlug(slug: string): Promise<CompanyProfile | null> {
  const data = await gqlFetch<CompanyBySlugResponse>(COMPANY_BY_SLUG_QUERY, {
    variables: { slug },
    tags: [CATALOG_TAG, `company:${slug}`],
    revalidate: REVALIDATE_SECONDS,
  });
  return data.company;
}

/**
 * Слаги всех компаний — для generateStaticParams и sitemap.
 */
export async function getAllCompanySlugs(): Promise<string[]> {
  const data = await gqlFetch<AllCompanySlugsResponse>(ALL_COMPANY_SLUGS_QUERY, {
    tags: [CATALOG_TAG],
    revalidate: REVALIDATE_SECONDS,
  });
  return data.companies.nodes.map((node) => node.slug);
}

/**
 * Справочники для фильтров: города и категории (с количеством).
 */
export async function getCatalogFilters(): Promise<{
  cities: TaxonomyTermWithCount[];
  categories: TaxonomyTermWithCount[];
}> {
  const data = await gqlFetch<CatalogFiltersResponse>(CATALOG_FILTERS_QUERY, {
    tags: [CATALOG_TAG],
    revalidate: REVALIDATE_SECONDS,
  });
  return {
    cities: data.cities.nodes,
    categories: data.serviceCategories.nodes,
  };
}
