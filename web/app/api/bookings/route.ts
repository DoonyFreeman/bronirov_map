import { NextResponse, type NextRequest } from 'next/server';

import { createBooking } from '@/lib/api/bookings';
import type { CreateBookingInput } from '@/lib/graphql/types';

export const dynamic = 'force-dynamic';

/** POST /api/bookings — создать бронь. Тело: CreateBookingInput. */
export async function POST(request: NextRequest) {
  let body: Partial<CreateBookingInput>;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: 'Некорректный JSON' }, { status: 400 });
  }

  const { serviceId, date, time, clientName, clientPhone, idempotencyKey } = body;
  if (!serviceId || !date || !time || !clientName || !clientPhone || !idempotencyKey) {
    return NextResponse.json({ error: 'Заполните обязательные поля' }, { status: 400 });
  }

  try {
    const result = await createBooking({
      serviceId: String(serviceId),
      date,
      time,
      clientName,
      clientPhone,
      clientEmail: body.clientEmail,
      idempotencyKey,
      notes: body.notes,
      website: body.website,
    });
    return NextResponse.json({ result });
  } catch (error) {
    // Сообщения валидации из WordPress (слот занят, лимит и т.п.) — 422.
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Не удалось создать бронь' },
      { status: 422 },
    );
  }
}
