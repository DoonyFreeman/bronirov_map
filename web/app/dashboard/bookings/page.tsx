import { BookingStatusButtons } from '@/components/BookingStatusButtons';
import { getCompanyBookings } from '@/lib/api/dashboard';
import { getToken } from '@/lib/auth';

export const metadata = { title: 'Записи — кабинет' };

const STATUS_LABELS: Record<string, string> = {
  pending: 'Ожидает',
  confirmed: 'Подтверждена',
  cancelled: 'Отменена',
};

export default async function DashboardBookings() {
  const token = await getToken();
  const bookings = await getCompanyBookings(token!);

  return (
    <section className="profile-section">
      <h2>Записи клиентов</h2>
      {bookings.length === 0 ? (
        <p style={{ color: 'var(--color-muted-fg)' }}>Записей пока нет.</p>
      ) : (
        <ul className="service-list">
          {bookings.map((b) => (
            <li key={b.databaseId} className="service-row">
              <div>
                <span className="service-row__name">{b.serviceName}</span>
                <span className="service-row__dur">
                  {b.date} · {b.time}
                </span>
                <div style={{ color: 'var(--color-muted-fg)', fontSize: '0.9rem' }}>
                  {b.clientName} · {b.clientPhone} · {STATUS_LABELS[b.status ?? ''] ?? b.status}
                </div>
              </div>
              <BookingStatusButtons bookingId={b.databaseId} status={b.status ?? ''} />
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
