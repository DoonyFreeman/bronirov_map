import { env } from '@/lib/env';

export interface FetchOptions {
  /** Переменные запроса. */
  variables?: Record<string, unknown>;
  /** Теги кэша Next.js для адресной ревалидации (revalidateTag). */
  tags?: string[];
  /** Время жизни кэша в секундах (ISR). `0` — без кэша. */
  revalidate?: number;
  /** JWT для авторизованных запросов (Authorization: Bearer). */
  token?: string;
}

interface GraphQLResponse<TResult> {
  data?: TResult;
  errors?: Array<{ message: string }>;
}

/**
 * Выполнить GraphQL-запрос к WordPress/WPGraphQL на сервере.
 *
 * Используем нативный `fetch` Next.js, чтобы напрямую работали теги кэша и ISR
 * (`next: { tags, revalidate }`). Для публичных запросов токен не нужен —
 * мутации и приватные данные подключим в Спринте 5.
 */
export async function gqlFetch<TResult>(
  query: string,
  { variables, tags, revalidate, token }: FetchOptions = {},
): Promise<TResult> {
  const headers: Record<string, string> = { 'Content-Type': 'application/json' };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  const response = await fetch(env.graphqlEndpoint, {
    method: 'POST',
    headers,
    body: JSON.stringify({ query, variables }),
    next: { tags, revalidate },
  });

  if (!response.ok) {
    throw new Error(`GraphQL HTTP ${response.status} ${response.statusText}`);
  }

  const json = (await response.json()) as GraphQLResponse<TResult>;

  if (json.errors?.length) {
    throw new Error(json.errors.map((e) => e.message).join('; '));
  }
  if (!json.data) {
    throw new Error('GraphQL: пустой ответ (нет data)');
  }

  return json.data;
}
