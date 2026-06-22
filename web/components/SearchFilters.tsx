import Link from 'next/link';

import type { TaxonomyTermWithCount } from '@/lib/graphql/types';

interface SearchFiltersProps {
  cities: TaxonomyTermWithCount[];
  categories: TaxonomyTermWithCount[];
  selectedCity?: string;
  selectedCategory?: string;
}

/**
 * Серверная форма фильтров каталога. Отправляется методом GET на /search,
 * состояние живёт в URL — без клиентского JS, переживает перезагрузку и
 * шарится ссылкой.
 */
export function SearchFilters({
  cities,
  categories,
  selectedCity,
  selectedCategory,
}: SearchFiltersProps) {
  return (
    <form className="filters" method="get" action="/search">
      <div className="field">
        <label htmlFor="city">Город</label>
        <select id="city" name="city" defaultValue={selectedCity ?? ''}>
          <option value="">Все города</option>
          {cities.map((city) => (
            <option key={city.slug} value={city.slug}>
              {city.name}
              {city.count ? ` (${city.count})` : ''}
            </option>
          ))}
        </select>
      </div>

      <div className="field">
        <label htmlFor="category">Категория</label>
        <select id="category" name="category" defaultValue={selectedCategory ?? ''}>
          <option value="">Все категории</option>
          {categories.map((category) => (
            <option key={category.slug} value={category.slug}>
              {category.name}
              {category.count ? ` (${category.count})` : ''}
            </option>
          ))}
        </select>
      </div>

      <button type="submit" className="btn btn--primary">
        Показать
      </button>

      {(selectedCity || selectedCategory) && (
        <Link href="/search" className="btn btn--ghost">
          Сбросить
        </Link>
      )}
    </form>
  );
}
