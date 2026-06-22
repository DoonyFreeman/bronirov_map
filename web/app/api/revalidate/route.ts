import { revalidateTag } from 'next/cache';
import { NextResponse, type NextRequest } from 'next/server';

/**
 * Эндпоинт адресной ревалидации кэша. Вызывается вебхуком из WordPress при
 * сохранении/удалении компании. Тело: { tags: string[] }.
 * Защита — секрет в заголовке x-revalidate-secret.
 *
 * POST /api/revalidate
 */
export async function POST(request: NextRequest) {
  const secret = process.env.REVALIDATE_SECRET;
  if (!secret || request.headers.get('x-revalidate-secret') !== secret) {
    return NextResponse.json({ revalidated: false, error: 'unauthorized' }, { status: 401 });
  }

  let tags: unknown;
  try {
    ({ tags } = await request.json());
  } catch {
    return NextResponse.json({ revalidated: false, error: 'invalid json' }, { status: 400 });
  }

  if (!Array.isArray(tags) || tags.some((t) => typeof t !== 'string')) {
    return NextResponse.json(
      { revalidated: false, error: 'tags must be string[]' },
      { status: 400 },
    );
  }

  for (const tag of tags as string[]) {
    revalidateTag(tag);
  }

  return NextResponse.json({ revalidated: true, tags, now: Date.now() });
}
