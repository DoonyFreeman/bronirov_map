import { gqlFetch } from '@/lib/graphql/client';

// Главная страница на Спринте 0 — smoke-проверка связки Next ↔ WPGraphQL.
// Каталог компаний появится в Спринте 1. Не кэшируем во время сборки.
export const dynamic = 'force-dynamic';

interface ServiceHubInfo {
  serviceHubInfo: { version: string | null } | null;
}

async function getBackendStatus(): Promise<
  { ok: true; version: string } | { ok: false; error: string }
> {
  try {
    const data = await gqlFetch<ServiceHubInfo>(/* GraphQL */ `
      query ServiceHubInfo {
        serviceHubInfo {
          version
        }
      }
    `);
    return { ok: true, version: data.serviceHubInfo?.version ?? 'unknown' };
  } catch (error) {
    return { ok: false, error: error instanceof Error ? error.message : String(error) };
  }
}

export default async function HomePage() {
  const status = await getBackendStatus();

  return (
    <main style={{ maxWidth: 640, margin: '0 auto', padding: '4rem 1.5rem' }}>
      <h1 style={{ fontSize: '2rem', margin: 0 }}>ServiceHub</h1>
      <p style={{ color: 'var(--muted)' }}>Каталог услуг и онлайн-бронирование.</p>

      <section
        style={{
          marginTop: '2rem',
          padding: '1.25rem 1.5rem',
          border: '1px solid #23262d',
          borderRadius: 'var(--radius)',
        }}
      >
        <h2 style={{ fontSize: '1rem', marginTop: 0 }}>Состояние бэкенда (WPGraphQL)</h2>
        {status.ok ? (
          <p style={{ color: 'var(--ok)' }}>✅ Подключено. servicehub-core v{status.version}</p>
        ) : (
          <p style={{ color: 'var(--err)' }}>
            ⚠️ Нет связи с GraphQL. Запустите <code>docker compose up -d</code> и{' '}
            <code>docker compose run --rm wpcli</code>.
            <br />
            <small style={{ color: 'var(--muted)' }}>{status.error}</small>
          </p>
        )}
      </section>

      <p style={{ marginTop: '2rem', color: 'var(--muted)' }}>
        Спринт 0 — фундамент. Каталог компаний и фильтры — Спринт 1.
      </p>
    </main>
  );
}
