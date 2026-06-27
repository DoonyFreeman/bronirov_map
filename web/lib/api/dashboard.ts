import { gqlFetch } from '@/lib/graphql/client';
import {
  COMPANY_BOOKINGS_QUERY,
  DELETE_COMPANY_SERVICE_MUTATION,
  MY_COMPANY_QUERY,
  SAVE_COMPANY_SERVICE_MUTATION,
  SET_BOOKING_STATUS_MUTATION,
} from '@/lib/graphql/queries';
import type { Service } from '@/lib/graphql/types';

export interface MyCompany {
  databaseId: number;
  title: string;
  slug: string;
  services: Service[];
}

export interface CompanyBookingView {
  databaseId: number;
  date: string | null;
  time: string | null;
  status: string | null;
  serviceName: string | null;
  clientName: string | null;
  clientPhone: string | null;
}

/** Компания текущего владельца (или null). */
export async function getMyCompany(token: string): Promise<MyCompany | null> {
  const data = await gqlFetch<{ myCompany: MyCompany | null }>(MY_COMPANY_QUERY, {
    token,
    revalidate: 0,
  });
  return data.myCompany;
}

/** Брони компании владельца. */
export async function getCompanyBookings(token: string): Promise<CompanyBookingView[]> {
  const data = await gqlFetch<{ companyBookings: CompanyBookingView[] }>(COMPANY_BOOKINGS_QUERY, {
    token,
    revalidate: 0,
  });
  return data.companyBookings;
}

/** Сменить статус брони (confirmed|cancelled). */
export async function setBookingStatus(
  token: string,
  id: number,
  status: string,
): Promise<string | null> {
  const data = await gqlFetch<{ setBookingStatus: { status: string | null } | null }>(
    SET_BOOKING_STATUS_MUTATION,
    { token, variables: { id: String(id), status }, revalidate: 0 },
  );
  return data.setBookingStatus?.status ?? null;
}

export interface SaveServiceInput {
  serviceId?: number;
  title: string;
  price?: number;
  duration?: number;
}

/** Создать/обновить услугу. */
export async function saveService(token: string, input: SaveServiceInput): Promise<number | null> {
  const data = await gqlFetch<{ saveCompanyService: { serviceDatabaseId: number | null } | null }>(
    SAVE_COMPANY_SERVICE_MUTATION,
    {
      token,
      variables: {
        serviceId: input.serviceId ? String(input.serviceId) : null,
        title: input.title,
        price: input.price ?? null,
        duration: input.duration ?? null,
      },
      revalidate: 0,
    },
  );
  return data.saveCompanyService?.serviceDatabaseId ?? null;
}

/** Удалить услугу. */
export async function deleteService(token: string, id: number): Promise<boolean> {
  const data = await gqlFetch<{ deleteCompanyService: { deleted: boolean } | null }>(
    DELETE_COMPANY_SERVICE_MUTATION,
    { token, variables: { id: String(id) }, revalidate: 0 },
  );
  return Boolean(data.deleteCompanyService?.deleted);
}
