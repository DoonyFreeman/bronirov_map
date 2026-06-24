/**
 * Доступ к переменным окружения с понятными ошибками при отсутствии.
 */

function required(name: string, fallback?: string): string {
  const value = process.env[name] ?? fallback;
  if (value === undefined || value === '') {
    throw new Error(`Не задана переменная окружения ${name}`);
  }
  return value;
}

export const env = {
  /** URL GraphQL-эндпоинта WordPress. */
  graphqlEndpoint: required('WORDPRESS_GRAPHQL_ENDPOINT', 'http://localhost:8080/graphql'),
  /** Публичный базовый URL WordPress (для картинок/ссылок). */
  wordpressUrl: required('NEXT_PUBLIC_WORDPRESS_URL', 'http://localhost:8080'),
  /** Публичный базовый URL фронтенда (канонические ссылки, JSON-LD, sitemap). */
  siteUrl: required('NEXT_PUBLIC_SITE_URL', 'http://localhost:3000'),
  /** Username Telegram-бота для deep-link (пусто — блок не показываем). */
  telegramBot: process.env.NEXT_PUBLIC_TELEGRAM_BOT ?? '',
} as const;
