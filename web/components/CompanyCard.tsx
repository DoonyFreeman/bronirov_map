import Image from 'next/image';

import type { Company } from '@/lib/graphql/types';

const priceFormatter = new Intl.NumberFormat('ru-RU');

export function CompanyCard({ company }: { company: Company }) {
  const city = company.cities.nodes[0];
  const category = company.serviceCategories.nodes[0];
  const image = company.featuredImage?.node;

  return (
    <article className="card">
      <div className="card__media">
        {image?.sourceUrl ? (
          <Image
            src={image.sourceUrl}
            alt={image.altText || company.title}
            width={400}
            height={250}
            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
          />
        ) : (
          <span aria-hidden="true">{company.title.charAt(0)}</span>
        )}
      </div>

      <div className="card__body">
        <h3 className="card__title">{company.title}</h3>

        <div className="card__meta">
          {category && <span className="chip">{category.name}</span>}
          {city && <span className="chip chip--city">{city.name}</span>}
        </div>

        {company.address && <div className="card__meta">{company.address}</div>}

        {typeof company.priceFrom === 'number' && (
          <div className="card__price">
            от {priceFormatter.format(company.priceFrom)} ₽ <small>· за услугу</small>
          </div>
        )}
      </div>
    </article>
  );
}
