'use client';

import { useRouter } from 'next/navigation';
import { useState } from 'react';

export function BookingStatusButtons({ bookingId, status }: { bookingId: number; status: string }) {
  const router = useRouter();
  const [busy, setBusy] = useState(false);

  async function set(next: string) {
    setBusy(true);
    const res = await fetch('/api/dashboard/booking-status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookingId, status: next }),
    });
    setBusy(false);
    if (res.ok) router.refresh();
  }

  if (status === 'cancelled') {
    return <span style={{ color: 'var(--color-muted-fg)' }}>Отменена</span>;
  }

  return (
    <div className="service-row__actions">
      {status !== 'confirmed' && (
        <button
          type="button"
          className="btn btn--primary btn--sm"
          onClick={() => set('confirmed')}
          disabled={busy}
        >
          Подтвердить
        </button>
      )}
      <button
        type="button"
        className="btn btn--ghost btn--sm"
        onClick={() => set('cancelled')}
        disabled={busy}
      >
        Отклонить
      </button>
    </div>
  );
}
