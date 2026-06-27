import { NextResponse, type NextRequest } from 'next/server';

import { setBookingStatus } from '@/lib/api/dashboard';
import { getToken } from '@/lib/auth';

export const dynamic = 'force-dynamic';

/** POST /api/dashboard/booking-status — { bookingId, status }. Только владелец. */
export async function POST(request: NextRequest) {
  const token = await getToken();
  if (!token) {
    return NextResponse.json({ error: 'Не авторизован' }, { status: 401 });
  }
  const { bookingId, status } = await request.json().catch(() => ({}));
  if (!bookingId || !status) {
    return NextResponse.json({ error: 'Нужны bookingId и status' }, { status: 400 });
  }
  try {
    const result = await setBookingStatus(token, Number(bookingId), String(status));
    return NextResponse.json({ status: result });
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Ошибка' },
      { status: 422 },
    );
  }
}
