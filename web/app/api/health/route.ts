import { NextResponse } from 'next/server';

import { gqlFetch } from '@/lib/graphql/client';

export const dynamic = 'force-dynamic';

/**
 * Healthcheck: проверяет, что фронт жив и достучался до WPGraphQL.
 * GET /api/health
 */
export async function GET() {
  try {
    const data = await gqlFetch<{ serviceHubInfo: { version: string } | null }>(/* GraphQL */ `
      query Health {
        serviceHubInfo {
          version
        }
      }
    `);
    return NextResponse.json({
      web: 'ok',
      wordpress: 'ok',
      version: data.serviceHubInfo?.version ?? null,
    });
  } catch (error) {
    return NextResponse.json(
      {
        web: 'ok',
        wordpress: 'unreachable',
        error: error instanceof Error ? error.message : String(error),
      },
      { status: 503 },
    );
  }
}
