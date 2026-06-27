import Link from 'next/link';
import { redirect } from 'next/navigation';

import { LogoutButton } from '@/components/LogoutButton';
import { getMyCompany } from '@/lib/api/dashboard';
import { getToken } from '@/lib/auth';

export default async function DashboardLayout({ children }: { children: React.ReactNode }) {
  const token = await getToken();
  if (!token) {
    redirect('/login');
  }

  const company = await getMyCompany(token).catch(() => null);

  if (!company) {
    return (
      <main className="container section">
        <h1 style={{ fontSize: '1.5rem' }}>Бизнес-кабинет</h1>
        <p style={{ color: 'var(--color-muted-fg)' }}>
          К вашему аккаунту не привязана компания. Обратитесь к администратору, чтобы он указал вас
          владельцем компании.
        </p>
      </main>
    );
  }

  return (
    <main className="container section">
      <div className="section__head">
        <div>
          <h1 style={{ fontSize: '1.5rem' }}>{company.title}</h1>
          <nav className="site-nav" aria-label="Бизнес-кабинет" style={{ marginTop: 8 }}>
            <Link href="/dashboard">Сводка</Link>
            <Link href="/dashboard/bookings">Записи</Link>
            <Link href="/dashboard/services">Услуги</Link>
          </nav>
        </div>
        <LogoutButton />
      </div>
      {children}
    </main>
  );
}
