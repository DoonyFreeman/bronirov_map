import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';

import { FavoriteButton } from '@/components/FavoriteButton';
import { HoursTable } from '@/components/HoursTable';
import { RatingStars } from '@/components/RatingStars';
import { ReviewsList } from '@/components/ReviewsList';
import { ServiceList } from '@/components/ServiceList';
import { getAllCompanySlugs, getCompanyBySlug } from '@/lib/api/companies';
import { buildCompanyJsonLd, companyUrl } from '@/lib/seo/company-jsonld';

export const revalidate = 3600;

interface ProfileProps {
  params: Promise<{ slug: string }>;
}

export async function generateStaticParams() {
  try {
    const slugs = await getAllCompanySlugs();
    return slugs.map((slug) => ({ slug }));
  } catch {
    return [];
  }
}

export async function generateMetadata({ params }: ProfileProps): Promise<Metadata> {
  const { slug } = await params;
  const company = await getCompanyBySlug(slug).catch(() => null);
  if (!company) {
    return { title: 'Компания не найдена' };
  }
  const description =
    company.excerpt?.replace(/<[^>]+>/g, '').trim() ||
    `${company.title} — услуги, цены, отзывы и контакты.`;
  const url = companyUrl(slug);
  return {
    title: company.title,
    description,
    alternates: { canonical: url },
    openGraph: {
      title: company.title,
      description,
      url,
      type: 'website',
      images: company.featuredImage?.node.sourceUrl
        ? [company.featuredImage.node.sourceUrl]
        : undefined,
    },
  };
}

export default async function CompanyProfilePage({ params }: ProfileProps) {
  const { slug } = await params;
  const company = await getCompanyBySlug(slug).catch(() => null);

  if (!company) {
    notFound();
  }

  const city = company.cities.nodes[0];
  const category = company.serviceCategories.nodes[0];
  const jsonLd = buildCompanyJsonLd(company, companyUrl(slug));

  return (
    <main className="container section">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />

      <nav className="breadcrumb" aria-label="Хлебные крошки">
        <Link href="/search">Каталог</Link> <span aria-hidden="true">/</span> {company.title}
      </nav>

      <header className="profile-head">
        <h1>{company.title}</h1>
        <div className="profile-head__meta">
          <RatingStars rating={company.averageRating} count={company.reviewCount} />
          {category && <span className="chip">{category.name}</span>}
          {city && <span className="chip chip--city">{city.name}</span>}
          <FavoriteButton companyId={company.databaseId} />
        </div>
        <div className="profile-contacts">
          {company.address && <span>{company.address}</span>}
          {company.phone && <a href={`tel:${company.phone}`}>{company.phone}</a>}
        </div>
      </header>

      {company.content && (
        <section className="profile-section">
          <div className="prose" dangerouslySetInnerHTML={{ __html: company.content }} />
        </section>
      )}

      <div className="profile-grid">
        <div>
          <section className="profile-section">
            <h2>Услуги и цены</h2>
            <ServiceList services={company.services} />
          </section>

          <section className="profile-section">
            <h2>Отзывы {company.reviewCount ? `(${company.reviewCount})` : ''}</h2>
            <ReviewsList reviews={company.reviews} />
          </section>
        </div>

        <aside>
          <section className="profile-section">
            <h2>Часы работы</h2>
            <HoursTable hours={company.hours} />
          </section>
        </aside>
      </div>
    </main>
  );
}
