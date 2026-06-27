'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';

/**
 * Авторизационная часть шапки. Сама проверяет статус входа (GET /api/auth/me),
 * чтобы корневой layout оставался статическим. Залогинен → Кабинет/Бизнес/Выйти,
 * иначе → Войти.
 */
export function AuthNav() {
  const router = useRouter();
  const [authed, setAuthed] = useState<boolean | null>(null);

  useEffect(() => {
    let active = true;
    fetch('/api/auth/me')
      .then((r) => r.json())
      .then((d) => active && setAuthed(Boolean(d.authed)))
      .catch(() => active && setAuthed(false));
    return () => {
      active = false;
    };
  }, []);

  async function logout() {
    await fetch('/api/auth/logout', { method: 'POST' });
    setAuthed(false);
    router.push('/');
    router.refresh();
  }

  if (authed === null) {
    return null; // не мигаем до проверки
  }

  if (!authed) {
    return <Link href="/login">Войти</Link>;
  }

  return (
    <>
      <Link href="/account/bookings">Кабинет</Link>
      <Link href="/dashboard">Бизнес</Link>
      <button type="button" className="site-nav__link-btn" onClick={logout}>
        Выйти
      </button>
    </>
  );
}
