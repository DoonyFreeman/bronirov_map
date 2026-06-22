import type { Metadata } from 'next';

import { CompanyGrid } from '@/components/CompanyGrid';
import { SearchFilters } from '@/components/SearchFilters';
import { getCatalogFilters, getCompanies } from '@/lib/api/companies';
import type { Company, TaxonomyTermWithCount } from '@/lib/graphql/types';

export const revalidate = 3600;

export const metadata: Metadata = {
  title: 'Поиск компаний',
  description: 'Поиск компаний по городу и категории услуг.',
};

interface SearchPageProps {
  searchParams: Promise<{ city?: string; category?: string }>;
}

export default async function SearchPage({ searchParams }: SearchPageProps) {
  const { city, category } = await searchParams;

  let companies: Company[] = [];
  let cities: TaxonomyTermWithCount[] = [];
  let categories: TaxonomyTermWithCount[] = [];
  let backendOk = true;

  try {
    [companies, { cities, categories }] = await Promise.all([
      getCompanies({ city, category }),
      getCatalogFilters(),
    ]);
  } catch {
    backendOk = false;
  }

  return (
    <main className="container section">
      <div className="section__head">
        <h1 style={{ fontSize: '1.75rem' }}>Поиск компаний</h1>
        {backendOk && <span className="section__count">{companies.length} найдено</span>}
      </div>

      <SearchFilters
        cities={cities}
        categories={categories}
        selectedCity={city}
        selectedCategory={category}
      />

      <div style={{ marginTop: 'var(--space-8)' }}>
        {backendOk ? (
          <CompanyGrid companies={companies} />
        ) : (
          <div className="empty">Нет связи с бэкендом.</div>
        )}
      </div>
    </main>
  );
}
