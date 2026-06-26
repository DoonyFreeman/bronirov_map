import { NextResponse, type NextRequest } from 'next/server';

import { getMyFavorites, toggleFavorite } from '@/lib/api/account';
import { getToken } from '@/lib/auth';

export const dynamic = 'force-dynamic';

/** GET /api/account/favorite — состояние: { authed, companyIds }. */
export async function GET() {
  const token = await getToken();
  if (!token) {
    return NextResponse.json({ authed: false, companyIds: [] });
  }
  const favorites = await getMyFavorites(token);
  return NextResponse.json({ authed: true, companyIds: favorites.map((c) => c.databaseId) });
}

/** POST /api/account/favorite — { companyId }. Требует cookie сессии. */
export async function POST(request: NextRequest) {
  const token = await getToken();
  if (!token) {
    return NextResponse.json({ error: 'Не авторизован' }, { status: 401 });
  }
  const { companyId } = await request.json().catch(() => ({}));
  if (!companyId) {
    return NextResponse.json({ error: 'Нет companyId' }, { status: 400 });
  }
  const result = await toggleFavorite(token, Number(companyId));
  return NextResponse.json(result);
}
