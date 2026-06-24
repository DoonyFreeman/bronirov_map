import { gqlFetch } from '@/lib/graphql/client';
import {
  AVAILABLE_SLOTS_QUERY,
  CREATE_BOOKING_MUTATION,
  SERVICE_FOR_BOOKING_QUERY,
} from '@/lib/graphql/queries';
import type {
  AvailableSlotsResponse,
  CreateBookingInput,
  CreateBookingResponse,
  CreateBookingResult,
  ServiceForBooking,
  ServiceForBookingResponse,
} from '@/lib/graphql/types';

/** Данные услуги для страницы бронирования. */
export async function getServiceForBooking(id: number): Promise<ServiceForBooking | null> {
  const data = await gqlFetch<ServiceForBookingResponse>(SERVICE_FOR_BOOKING_QUERY, {
    variables: { id: String(id) },
    tags: ['companies:list'],
    revalidate: 3600,
  });
  return data.service;
}

/** Свободные слоты — всегда свежие (без кэша). */
export async function getAvailableSlots(serviceId: number, date: string): Promise<string[]> {
  const data = await gqlFetch<AvailableSlotsResponse>(AVAILABLE_SLOTS_QUERY, {
    variables: { serviceId: String(serviceId), date },
    revalidate: 0,
  });
  return data.availableSlots;
}

/** Создать бронь (мутация, без кэша). */
export async function createBooking(input: CreateBookingInput): Promise<CreateBookingResult> {
  const data = await gqlFetch<CreateBookingResponse>(CREATE_BOOKING_MUTATION, {
    variables: { input },
    revalidate: 0,
  });
  if (!data.createServiceBooking) {
    throw new Error('Пустой ответ при создании брони');
  }
  return data.createServiceBooking;
}
