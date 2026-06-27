'use client';

import { useRouter } from 'next/navigation';
import { useState } from 'react';

import type { Service } from '@/lib/graphql/types';

const fmt = new Intl.NumberFormat('ru-RU');

export function ServiceEditor({ services }: { services: Service[] }) {
  const router = useRouter();
  const [editId, setEditId] = useState<number | null>(null);
  const [title, setTitle] = useState('');
  const [price, setPrice] = useState('');
  const [duration, setDuration] = useState('');
  const [busy, setBusy] = useState(false);

  function reset() {
    setEditId(null);
    setTitle('');
    setPrice('');
    setDuration('');
  }

  function startEdit(s: Service) {
    setEditId(s.databaseId);
    setTitle(s.title);
    setPrice(s.price != null ? String(s.price) : '');
    setDuration(s.duration != null ? String(s.duration) : '');
  }

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    const res = await fetch('/api/dashboard/service', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        serviceId: editId ?? undefined,
        title,
        price: price ? Number(price) : undefined,
        duration: duration ? Number(duration) : undefined,
      }),
    });
    setBusy(false);
    if (res.ok) {
      reset();
      router.refresh();
    }
  }

  async function remove(id: number) {
    if (!confirm('Удалить услугу?')) return;
    const res = await fetch('/api/dashboard/service', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ serviceId: id }),
    });
    if (res.ok) router.refresh();
  }

  return (
    <div>
      <ul className="service-list">
        {services.map((s) => (
          <li key={s.databaseId} className="service-row">
            <div>
              <span className="service-row__name">{s.title}</span>
              {s.duration != null && <span className="service-row__dur">{s.duration} мин</span>}
            </div>
            <div className="service-row__actions">
              {s.price != null && (
                <span className="service-row__price">{fmt.format(s.price)} ₽</span>
              )}
              <button type="button" className="btn btn--ghost btn--sm" onClick={() => startEdit(s)}>
                Изменить
              </button>
              <button
                type="button"
                className="btn btn--ghost btn--sm"
                onClick={() => remove(s.databaseId)}
              >
                Удалить
              </button>
            </div>
          </li>
        ))}
      </ul>

      <form
        className="booking"
        onSubmit={save}
        style={{ marginTop: 'var(--space-6)', maxWidth: 420 }}
      >
        <h3 style={{ margin: 0 }}>{editId ? 'Редактировать услугу' : 'Добавить услугу'}</h3>
        <div className="field">
          <label htmlFor="s-title">Название</label>
          <input id="s-title" required value={title} onChange={(e) => setTitle(e.target.value)} />
        </div>
        <div className="field">
          <label htmlFor="s-price">Цена, ₽</label>
          <input
            id="s-price"
            type="number"
            value={price}
            onChange={(e) => setPrice(e.target.value)}
          />
        </div>
        <div className="field">
          <label htmlFor="s-duration">Длительность, мин</label>
          <input
            id="s-duration"
            type="number"
            value={duration}
            onChange={(e) => setDuration(e.target.value)}
          />
        </div>
        <div className="service-row__actions">
          <button type="submit" className="btn btn--primary" disabled={busy}>
            {busy ? 'Сохранение…' : 'Сохранить'}
          </button>
          {editId && (
            <button type="button" className="btn btn--ghost" onClick={reset}>
              Отмена
            </button>
          )}
        </div>
      </form>
    </div>
  );
}
