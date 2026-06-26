import type { Metadata } from 'next';
import { Manrope } from 'next/font/google';
import Link from 'next/link';

import './globals.css';

const manrope = Manrope({
  subsets: ['latin', 'cyrillic'],
  variable: '--font-sans',
  display: 'swap',
});

export const metadata: Metadata = {
  title: {
    default: 'ServiceHub — каталог услуг и онлайн-запись',
    template: '%s · ServiceHub',
  },
  description:
    'Найдите салоны, мастеров и сервисы рядом. Поиск по городу и категории, цены, контакты, онлайн-запись.',
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="ru" className={manrope.variable}>
      <body>
        <header className="site-header">
          <div className="container site-header__inner">
            <Link href="/" className="brand">
              <span className="brand__dot" aria-hidden="true" />
              ServiceHub
            </Link>
            <nav className="site-nav" aria-label="Основная навигация">
              <Link href="/search">Каталог</Link>
              <Link href="/account/bookings">Кабинет</Link>
              <Link href="/login">Войти</Link>
            </nav>
          </div>
        </header>

        {children}

        <footer className="site-footer">
          <div className="container">
            © {new Date().getFullYear()} ServiceHub — каталог услуг и онлайн-запись.
          </div>
        </footer>
      </body>
    </html>
  );
}
