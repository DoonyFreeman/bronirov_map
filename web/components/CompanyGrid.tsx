import { CompanyCard } from '@/components/CompanyCard';
import type { Company } from '@/lib/graphql/types';

export function CompanyGrid({ companies }: { companies: Company[] }) {
  if (companies.length === 0) {
    return (
      <div className="empty">
        <p>По заданным фильтрам компании не найдены.</p>
        <p>Попробуйте изменить город или категорию.</p>
      </div>
    );
  }

  return (
    <div className="grid">
      {companies.map((company) => (
        <CompanyCard key={company.databaseId} company={company} />
      ))}
    </div>
  );
}
