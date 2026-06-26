import { NextResponse, type NextRequest } from 'next/server';

import { login } from '@/lib/api/account';
import { sessionCookie } from '@/lib/auth';

export const dynamic = 'force-dynamic';

/** POST /api/auth/login — { username, password } → ставит httpOnly-cookie. */
export async function POST(request: NextRequest) {
  let body: { username?: string; password?: string };
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: 'Некорректный запрос' }, { status: 400 });
  }
  if (!body.username || !body.password) {
    return NextResponse.json({ error: 'Введите логин и пароль' }, { status: 400 });
  }

  try {
    const token = await login(body.username, body.password);
    const res = NextResponse.json({ ok: true });
    res.cookies.set(sessionCookie(token));
    return res;
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Ошибка входа' },
      { status: 401 },
    );
  }
}
