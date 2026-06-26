'use client';

import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';

/**
 * Кнопка избранного. Сама подгружает состояние (GET /api/account/favorite),
 * чтобы страница профиля оставалась статической (без чтения cookie на сервере).
 */
export function FavoriteButton({ companyId }: { companyId: number }) {
  const router = useRouter();
  const [authed, setAuthed] = useState(false);
  const [favorite, setFavorite] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    fetch('/api/account/favorite')
      .then((r) => (r.ok ? r.json() : { authed: false, companyIds: [] }))
      .then((d) => {
        if (!active) return;
        setAuthed(Boolean(d.authed));
        setFavorite(Array.isArray(d.companyIds) && d.companyIds.includes(companyId));
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, [companyId]);

  async function toggle() {
    if (!authed) {
      router.push('/login');
      return;
    }
    setBusy(true);
    const res = await fetch('/api/account/favorite', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ companyId }),
    });
    setBusy(false);
    if (res.ok) {
      const data = await res.json();
      setFavorite(Boolean(data.isFavorite));
    }
  }

  return (
    <button
      type="button"
      className={`btn btn--ghost btn--sm ${favorite ? 'btn--fav' : ''}`}
      onClick={toggle}
      disabled={busy}
      aria-pressed={favorite}
    >
      {favorite ? '♥ В избранном' : '♡ В избранное'}
    </button>
  );
}
