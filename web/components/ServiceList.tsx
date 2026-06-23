import type { Service } from '@/lib/graphql/types';

const priceFormatter = new Intl.NumberFormat('ru-RU');

function formatDuration(minutes: number): string {
  if (minutes < 60) return `${minutes} мин`;
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return m ? `${h} ч ${m} мин` : `${h} ч`;
}

export function ServiceList({ services }: { services: Service[] }) {
  if (services.length === 0) {
    return <p style={{ color: 'var(--color-muted-fg)' }}>Услуги пока не добавлены.</p>;
  }

  return (
    <ul className="service-list">
      {services.map((service) => (
        <li key={service.databaseId} className="service-row">
          <div>
            <span className="service-row__name">{service.title}</span>
            {typeof service.duration === 'number' && (
              <span className="service-row__dur">{formatDuration(service.duration)}</span>
            )}
          </div>
          {typeof service.price === 'number' && (
            <span className="service-row__price">{priceFormatter.format(service.price)} ₽</span>
          )}
        </li>
      ))}
    </ul>
  );
}
