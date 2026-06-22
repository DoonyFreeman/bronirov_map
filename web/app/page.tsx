import { CompanyGrid } from '@/components/CompanyGrid';
import { SearchFilters } from '@/components/SearchFilters';
import { getCatalogFilters, getCompanies } from '@/lib/api/companies';
import type { Company, TaxonomyTermWithCount } from '@/lib/graphql/types';

// ISR: страница пересобирается по тегу `companies:list` (вебхук из WP) или раз в час.
export const revalidate = 3600;

export default async function HomePage() {
  let companies: Company[] = [];
  let cities: TaxonomyTermWithCount[] = [];
  let categories: TaxonomyTermWithCount[] = [];
  let backendOk = true;

  try {
    [companies, { cities, categories }] = await Promise.all([
      getCompanies({}),
      getCatalogFilters(),
    ]);
  } catch {
    backendOk = false;
  }

  return (
    <main>
      <section className="hero container">
        <h1>Найдите услугу и мастера рядом</h1>
        <p>
          Салоны, барбершопы, стоматологии и сервисы вашего города. Сравните цены, посмотрите
          контакты и запишитесь онлайн.
        </p>
        <div style={{ marginTop: 'var(--space-8)', textAlign: 'left' }}>
          <SearchFilters cities={cities} categories={categories} />
        </div>
      </section>

      <section className="section container">
        <div className="section__head">
          <h2>Компании на платформе</h2>
          <span className="section__count">{companies.length} в каталоге</span>
        </div>

        {backendOk ? (
          <CompanyGrid companies={companies.slice(0, 8)} />
        ) : (
          <div className="empty">
            <p>Нет связи с бэкендом.</p>
            <p>
              Запустите <code>docker compose up -d</code> и{' '}
              <code>docker compose run --rm wpcli</code>.
            </p>
          </div>
        )}
      </section>
    </main>
  );
}
