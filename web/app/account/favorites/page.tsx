import type { Metadata } from 'next';

import { CompanyGrid } from '@/components/CompanyGrid';
import { getMyFavorites } from '@/lib/api/account';
import { getToken } from '@/lib/auth';

export const metadata: Metadata = { title: 'Избранное' };

export default async function FavoritesPage() {
  const token = await getToken();
  const companies = token ? await getMyFavorites(token) : [];

  return (
    <section className="profile-section">
      <h1 style={{ fontSize: '1.5rem', marginBottom: 'var(--space-4)' }}>Избранное</h1>
      {companies.length === 0 ? (
        <p style={{ color: 'var(--color-muted-fg)' }}>
          Пока пусто. Добавляйте компании кнопкой «В избранное» на их странице.
        </p>
      ) : (
        <CompanyGrid companies={companies} />
      )}
    </section>
  );
}
