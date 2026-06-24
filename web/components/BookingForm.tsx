'use client';

import { useRouter } from 'next/navigation';
import { useCallback, useEffect, useRef, useState } from 'react';

function todayISO(): string {
  return new Date().toISOString().slice(0, 10);
}

interface BookingFormProps {
  serviceId: number;
  serviceTitle: string;
  companyTitle: string;
}

export function BookingForm({ serviceId, serviceTitle, companyTitle }: BookingFormProps) {
  const router = useRouter();
  const idempotencyKey = useRef<string>(crypto.randomUUID());

  const [date, setDate] = useState(todayISO);
  const [slots, setSlots] = useState<string[]>([]);
  const [slotsLoading, setSlotsLoading] = useState(false);
  const [time, setTime] = useState<string>('');

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [website, setWebsite] = useState(''); // honeypot
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadSlots = useCallback(async () => {
    setSlotsLoading(true);
    setTime('');
    try {
      const res = await fetch(`/api/slots?serviceId=${serviceId}&date=${date}`);
      const data = await res.json();
      setSlots(res.ok ? (data.slots ?? []) : []);
    } catch {
      setSlots([]);
    } finally {
      setSlotsLoading(false);
    }
  }, [serviceId, date]);

  useEffect(() => {
    void loadSlots();
  }, [loadSlots]);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setError(null);

    if (!time) {
      setError('Выберите время записи.');
      return;
    }

    setSubmitting(true);
    try {
      const res = await fetch('/api/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          serviceId,
          date,
          time,
          clientName: name,
          clientPhone: phone,
          clientEmail: email || undefined,
          idempotencyKey: idempotencyKey.current,
          website,
        }),
      });
      const data = await res.json();
      if (!res.ok) {
        setError(data.error ?? 'Не удалось создать бронь.');
        await loadSlots(); // слот мог занятьcя — обновим
        return;
      }
      const params = new URLSearchParams({
        service: serviceTitle,
        company: companyTitle,
        date,
        time,
      });
      router.push(`/confirmation?${params.toString()}`);
    } catch {
      setError('Сетевая ошибка. Попробуйте ещё раз.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form className="booking" onSubmit={handleSubmit}>
      <div className="field">
        <label htmlFor="date">Дата</label>
        <input
          id="date"
          type="date"
          min={todayISO()}
          value={date}
          onChange={(e) => setDate(e.target.value)}
        />
      </div>

      <fieldset className="booking__slots">
        <legend>Время</legend>
        {slotsLoading ? (
          <p className="booking__hint">Загрузка слотов…</p>
        ) : slots.length === 0 ? (
          <p className="booking__hint">На эту дату нет свободных слотов.</p>
        ) : (
          <div className="slot-grid">
            {slots.map((slot) => (
              <button
                type="button"
                key={slot}
                className={`slot ${time === slot ? 'slot--active' : ''}`}
                aria-pressed={time === slot}
                onClick={() => setTime(slot)}
              >
                {slot}
              </button>
            ))}
          </div>
        )}
      </fieldset>

      <div className="field">
        <label htmlFor="name">Имя</label>
        <input id="name" required value={name} onChange={(e) => setName(e.target.value)} />
      </div>
      <div className="field">
        <label htmlFor="phone">Телефон</label>
        <input
          id="phone"
          type="tel"
          required
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
        />
      </div>
      <div className="field">
        <label htmlFor="email">Email (необязательно)</label>
        <input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
      </div>

      {/* Honeypot — скрыт от людей, ловит ботов. */}
      <div aria-hidden="true" style={{ position: 'absolute', left: '-9999px' }}>
        <label htmlFor="website">Не заполняйте это поле</label>
        <input
          id="website"
          tabIndex={-1}
          autoComplete="off"
          value={website}
          onChange={(e) => setWebsite(e.target.value)}
        />
      </div>

      {error && (
        <p role="alert" className="booking__error">
          {error}
        </p>
      )}

      <button type="submit" className="btn btn--primary" disabled={submitting || !time}>
        {submitting ? 'Отправка…' : `Записаться на ${serviceTitle}`}
      </button>
    </form>
  );
}
