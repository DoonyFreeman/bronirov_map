import { getCompanyBookings, getMyCompany } from '@/lib/api/dashboard';
import { getToken } from '@/lib/auth';

export const metadata = { title: 'Сводка — кабинет' };

function Stat({ label, value }: { label: string; value: number }) {
  return (
    <div
      style={{
        padding: 'var(--space-6)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-lg)',
        background: 'var(--color-surface)',
      }}
    >
      <div style={{ fontSize: '2rem', fontWeight: 700 }}>{value}</div>
      <div style={{ color: 'var(--color-muted-fg)' }}>{label}</div>
    </div>
  );
}

export default async function DashboardHome() {
  const token = await getToken();
  const [company, bookings] = await Promise.all([getMyCompany(token!), getCompanyBookings(token!)]);

  const pending = bookings.filter((b) => b.status === 'pending').length;
  const confirmed = bookings.filter((b) => b.status === 'confirmed').length;

  return (
    <section className="profile-section">
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))',
          gap: 'var(--space-4)',
        }}
      >
        <Stat label="Ожидают подтверждения" value={pending} />
        <Stat label="Подтверждены" value={confirmed} />
        <Stat label="Всего записей" value={bookings.length} />
        <Stat label="Услуг" value={company?.services.length ?? 0} />
      </div>
    </section>
  );
}
