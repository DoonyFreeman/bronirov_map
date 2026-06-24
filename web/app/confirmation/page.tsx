import type { Metadata } from 'next';
import Link from 'next/link';

import { env } from '@/lib/env';

export const metadata: Metadata = { title: 'Запись принята' };

interface ConfirmationProps {
  searchParams: Promise<{
    service?: string;
    company?: string;
    date?: string;
    time?: string;
    booking?: string;
  }>;
}

export default async function ConfirmationPage({ searchParams }: ConfirmationProps) {
  const { service, company, date, time, booking } = await searchParams;
  const telegramLink =
    env.telegramBot && booking ? `https://t.me/${env.telegramBot}?start=${booking}` : null;

  return (
    <main className="container section" style={{ maxWidth: 640, textAlign: 'center' }}>
      <div
        style={{
          fontSize: '3rem',
          color: 'var(--color-success)',
          marginBottom: 'var(--space-4)',
        }}
        aria-hidden="true"
      >
        ✓
      </div>
      <h1>Заявка на запись принята</h1>
      <p style={{ color: 'var(--color-muted-fg)', marginTop: 'var(--space-3)' }}>
        Мы передали заявку компании. Статус — <strong>ожидает подтверждения</strong>.
      </p>

      {(service || date) && (
        <div
          style={{
            margin: 'var(--space-8) auto',
            padding: 'var(--space-6)',
            border: '1px solid var(--color-border)',
            borderRadius: 'var(--radius-lg)',
            background: 'var(--color-surface)',
            textAlign: 'left',
            maxWidth: 420,
          }}
        >
          {service && (
            <p>
              <strong>Услуга:</strong> {service}
            </p>
          )}
          {company && (
            <p>
              <strong>Компания:</strong> {company}
            </p>
          )}
          {date && (
            <p>
              <strong>Дата:</strong> {date} {time && `· ${time}`}
            </p>
          )}
        </div>
      )}

      {telegramLink && (
        <p style={{ marginBottom: 'var(--space-6)' }}>
          <a href={telegramLink} className="btn btn--ghost" target="_blank" rel="noopener">
            Получать статус в Telegram
          </a>
        </p>
      )}

      <Link href="/" className="btn btn--primary">
        На главную
      </Link>
    </main>
  );
}
