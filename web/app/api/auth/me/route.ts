import { NextResponse } from 'next/server';

import { getToken } from '@/lib/auth';

export const dynamic = 'force-dynamic';

/** GET /api/auth/me — { authed } по наличию валидной cookie-сессии. */
export async function GET() {
  return NextResponse.json({ authed: Boolean(await getToken()) });
}
