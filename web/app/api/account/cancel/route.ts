import { NextResponse, type NextRequest } from 'next/server';

import { cancelBooking } from '@/lib/api/account';
import { getToken } from '@/lib/auth';

export const dynamic = 'force-dynamic';

/** POST /api/account/cancel — { bookingId }. Требует cookie сессии. */
export async function POST(request: NextRequest) {
  const token = await getToken();
  if (!token) {
    return NextResponse.json({ error: 'Не авторизован' }, { status: 401 });
  }
  const { bookingId } = await request.json().catch(() => ({}));
  if (!bookingId) {
    return NextResponse.json({ error: 'Нет bookingId' }, { status: 400 });
  }
  try {
    const status = await cancelBooking(token, Number(bookingId));
    return NextResponse.json({ status });
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Ошибка' },
      { status: 422 },
    );
  }
}
