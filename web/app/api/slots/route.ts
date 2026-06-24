import { NextResponse, type NextRequest } from 'next/server';

import { getAvailableSlots } from '@/lib/api/bookings';

export const dynamic = 'force-dynamic';

/** GET /api/slots?serviceId=10&date=2026-06-24 → { slots: string[] } */
export async function GET(request: NextRequest) {
  const serviceId = Number(request.nextUrl.searchParams.get('serviceId'));
  const date = request.nextUrl.searchParams.get('date') ?? '';

  if (!serviceId || !/^\d{4}-\d{2}-\d{2}$/.test(date)) {
    return NextResponse.json({ error: 'Нужны serviceId и date (YYYY-MM-DD)' }, { status: 400 });
  }

  try {
    const slots = await getAvailableSlots(serviceId, date);
    return NextResponse.json({ slots });
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Ошибка получения слотов' },
      { status: 502 },
    );
  }
}
