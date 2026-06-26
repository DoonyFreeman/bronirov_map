import { NextResponse } from 'next/server';

import { TOKEN_COOKIE } from '@/lib/auth';

export const dynamic = 'force-dynamic';

/** POST /api/auth/logout — очищает cookie сессии. */
export async function POST() {
  const res = NextResponse.json({ ok: true });
  res.cookies.delete(TOKEN_COOKIE);
  return res;
}
