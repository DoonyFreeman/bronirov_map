import type { Metadata } from 'next';

import { CancelButton } from '@/components/CancelButton';
import { getMyBookings } from '@/lib/api/account';
import { getToken } from '@/lib/auth';

export const metadata: Metadata = { title: 'Мои записи' };

const STATUS_LABELS: Record<string, string> = {
  pending: 'Ожидает подтверждения',
  confirmed: 'Подтверждена',
  cancelled: 'Отменена',
};

export default async function MyBookingsPage() {
  const token = await getToken();
  const bookings = token ? await getMyBookings(token) : [];

  return (
    <section className="profile-section">
      <h1 style={{ fontSize: '1.5rem', marginBottom: 'var(--space-4)' }}>Мои записи</h1>
      {bookings.length === 0 ? (
        <p style={{ color: 'var(--color-muted-fg)' }}>Записей пока нет.</p>
      ) : (
        <ul className="service-list">
          {bookings.map((b) => (
            <li key={b.databaseId} className="service-row">
              <div>
                <span className="service-row__name">{b.companyName}</span>
                <span className="service-row__dur">{b.serviceName}</span>
                <div style={{ color: 'var(--color-muted-fg)', fontSize: '0.9rem' }}>
                  {b.date} · {b.time} · {STATUS_LABELS[b.status ?? ''] ?? b.status}
                </div>
              </div>
              {b.status !== 'cancelled' && <CancelButton bookingId={b.databaseId} />}
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
