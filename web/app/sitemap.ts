import type { MetadataRoute } from 'next';

import { getAllCompanySlugs } from '@/lib/api/companies';
import { env } from '@/lib/env';

export const revalidate = 3600;

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const base = env.siteUrl.replace(/\/$/, '');

  const staticPages: MetadataRoute.Sitemap = [
    { url: `${base}/`, changeFrequency: 'daily', priority: 1 },
    { url: `${base}/search`, changeFrequency: 'daily', priority: 0.8 },
  ];

  let companyPages: MetadataRoute.Sitemap = [];
  try {
    const slugs = await getAllCompanySlugs();
    companyPages = slugs.map((slug) => ({
      url: `${base}/company/${encodeURIComponent(slug)}`,
      changeFrequency: 'weekly',
      priority: 0.7,
    }));
  } catch {
    // WordPress недоступен на сборке — отдаём только статические страницы.
  }

  return [...staticPages, ...companyPages];
}
