import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';

import { BookingForm } from '@/components/BookingForm';
import { getServiceForBooking } from '@/lib/api/bookings';

export const revalidate = 3600;

interface BookingPageProps {
  params: Promise<{ serviceId: string }>;
}

const priceFormatter = new Intl.NumberFormat('ru-RU');

export async function generateMetadata({ params }: BookingPageProps): Promise<Metadata> {
  const { serviceId } = await params;
  const service = await getServiceForBooking(Number(serviceId)).catch(() => null);
  return { title: service ? `Запись: ${service.title}` : 'Запись на услугу' };
}

export default async function BookingPage({ params }: BookingPageProps) {
  const { serviceId } = await params;
  const service = await getServiceForBooking(Number(serviceId)).catch(() => null);

  if (!service) {
    notFound();
  }

  return (
    <main className="container section" style={{ maxWidth: 720 }}>
      {service.company && (
        <nav className="breadcrumb" aria-label="Хлебные крошки">
          <Link href={`/company/${service.company.slug}`}>{service.company.title}</Link>{' '}
          <span aria-hidden="true">/</span> Запись
        </nav>
      )}

      <header className="profile-head">
        <h1>Запись: {service.title}</h1>
        <div className="profile-contacts">
          {service.company && <span>{service.company.title}</span>}
          {typeof service.duration === 'number' && <span>{service.duration} мин</span>}
          {typeof service.price === 'number' && (
            <span>{priceFormatter.format(service.price)} ₽</span>
          )}
        </div>
      </header>

      <section className="profile-section">
        <BookingForm
          serviceId={service.databaseId}
          serviceTitle={service.title}
          companyTitle={service.company?.title ?? ''}
        />
      </section>
    </main>
  );
}
