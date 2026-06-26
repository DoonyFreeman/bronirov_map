import { cookies } from 'next/headers';

/** Имя httpOnly-cookie с JWT WordPress. */
export const TOKEN_COOKIE = 'sh_token';

/** Прочитать JWT из cookie (серверные компоненты/роуты). */
export async function getToken(): Promise<string | undefined> {
  return (await cookies()).get(TOKEN_COOKIE)?.value;
}

/** Опции записи cookie сессии (httpOnly, 7 дней). */
export function sessionCookie(token: string) {
  return {
    name: TOKEN_COOKIE,
    value: token,
    httpOnly: true,
    sameSite: 'lax' as const,
    secure: process.env.NODE_ENV === 'production',
    path: '/',
    maxAge: 60 * 60 * 24 * 7,
  };
}
