import type { Metadata } from 'next';

import { MapExplorer } from '@/components/MapExplorer';
import { getCompanies } from '@/lib/api/companies';
import type { Company } from '@/lib/graphql/types';

export const revalidate = 3600;

export const metadata: Metadata = {
  title: 'Карта компаний',
  description: 'Компании на карте: найдите ближайшие услуги рядом с вами.',
};

export default async function MapPage() {
  let companies: Company[] = [];
  try {
    companies = await getCompanies({});
  } catch {
    companies = [];
  }

  return (
    <main className="container section">
      <div className="section__head">
        <h1 style={{ fontSize: '1.75rem' }}>Компании на карте</h1>
        <span className="section__count">{companies.length} компаний</span>
      </div>
      <MapExplorer companies={companies} />
    </main>
  );
}
