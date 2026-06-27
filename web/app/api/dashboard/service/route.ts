import { NextResponse, type NextRequest } from 'next/server';

import { deleteService, saveService } from '@/lib/api/dashboard';
import { getToken } from '@/lib/auth';

export const dynamic = 'force-dynamic';

/** POST /api/dashboard/service — создать/обновить услугу. */
export async function POST(request: NextRequest) {
  const token = await getToken();
  if (!token) {
    return NextResponse.json({ error: 'Не авторизован' }, { status: 401 });
  }
  const body = await request.json().catch(() => ({}));
  if (!body.title) {
    return NextResponse.json({ error: 'Нужно название' }, { status: 400 });
  }
  try {
    const id = await saveService(token, {
      serviceId: body.serviceId ? Number(body.serviceId) : undefined,
      title: String(body.title),
      price: body.price != null ? Number(body.price) : undefined,
      duration: body.duration != null ? Number(body.duration) : undefined,
    });
    return NextResponse.json({ serviceDatabaseId: id });
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Ошибка' },
      { status: 422 },
    );
  }
}

/** DELETE /api/dashboard/service — { serviceId }. */
export async function DELETE(request: NextRequest) {
  const token = await getToken();
  if (!token) {
    return NextResponse.json({ error: 'Не авторизован' }, { status: 401 });
  }
  const { serviceId } = await request.json().catch(() => ({}));
  if (!serviceId) {
    return NextResponse.json({ error: 'Нужен serviceId' }, { status: 400 });
  }
  try {
    const deleted = await deleteService(token, Number(serviceId));
    return NextResponse.json({ deleted });
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : 'Ошибка' },
      { status: 422 },
    );
  }
}
