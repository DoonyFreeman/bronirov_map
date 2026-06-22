import { gqlFetch } from '@/lib/graphql/client';
import { CATALOG_FILTERS_QUERY, COMPANIES_QUERY } from '@/lib/graphql/queries';
import type {
  CatalogFiltersResponse,
  CompaniesResponse,
  Company,
  CompanyFilters,
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
