'use client';

import { useRouter } from 'next/navigation';
import { useState } from 'react';

export function CancelButton({ bookingId }: { bookingId: number }) {
  const router = useRouter();
  const [busy, setBusy] = useState(false);

  async function cancel() {
    if (!confirm('Отменить запись?')) return;
    setBusy(true);
    const res = await fetch('/api/account/cancel', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ bookingId }),
    });
    setBusy(false);
    if (res.ok) router.refresh();
  }

  return (
    <button type="button" className="btn btn--ghost btn--sm" onClick={cancel} disabled={busy}>
      {busy ? '…' : 'Отменить'}
    </button>
  );
}
