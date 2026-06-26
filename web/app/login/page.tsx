import type { Metadata } from 'next';

import { LoginForm } from '@/components/LoginForm';

export const metadata: Metadata = { title: 'Вход' };

export default function LoginPage() {
  return (
    <main className="container section" style={{ maxWidth: 480 }}>
      <h1 style={{ fontSize: '1.75rem' }}>Вход в кабинет</h1>
      <p style={{ color: 'var(--color-muted-fg)', marginBottom: 'var(--space-6)' }}>
        Войдите учётной записью WordPress, чтобы видеть свои записи и избранное.
      </p>
      <LoginForm />
    </main>
  );
}
