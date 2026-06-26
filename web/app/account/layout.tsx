import Link from 'next/link';
import { redirect } from 'next/navigation';

import { LogoutButton } from '@/components/LogoutButton';
import { getToken } from '@/lib/auth';

export default async function AccountLayout({ children }: { children: React.ReactNode }) {
  // Кабинет доступен только авторизованным.
  if (!(await getToken())) {
    redirect('/login');
  }

  return (
    <main className="container section">
      <div className="section__head">
        <nav className="site-nav" aria-label="Кабинет">
          <Link href="/account/bookings">Мои записи</Link>
          <Link href="/account/favorites">Избранное</Link>
        </nav>
        <LogoutButton />
      </div>
      {children}
    </main>
  );
}
