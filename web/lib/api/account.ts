import { gqlFetch } from '@/lib/graphql/client';
import {
  CANCEL_BOOKING_MUTATION,
  LOGIN_MUTATION,
  MY_BOOKINGS_QUERY,
  MY_FAVORITES_QUERY,
  TOGGLE_FAVORITE_MUTATION,
} from '@/lib/graphql/queries';
import type { Company } from '@/lib/graphql/types';

export interface BookingView {
  databaseId: number;
  date: string | null;
  time: string | null;
  status: string | null;
  serviceName: string | null;
  companyName: string | null;
}

/** Логин в WordPress, возвращает JWT. */
export async function login(username: string, password: string): Promise<string> {
  const data = await gqlFetch<{ login: { authToken: string | null } | null }>(LOGIN_MUTATION, {
    variables: { username, password },
    revalidate: 0,
  });
  const token = data.login?.authToken;
  if (!token) {
    throw new Error('Неверный логин или пароль');
  }
  return token;
}

/** Брони текущего пользователя. */
export async function getMyBookings(token: string): Promise<BookingView[]> {
  const data = await gqlFetch<{ myBookings: BookingView[] }>(MY_BOOKINGS_QUERY, {
    token,
    revalidate: 0,
  });
  return data.myBookings;
}

/** Избранные компании пользователя. */
export async function getMyFavorites(token: string): Promise<Company[]> {
  const data = await gqlFetch<{ myFavorites: Company[] }>(MY_FAVORITES_QUERY, {
    token,
    revalidate: 0,
  });
  return data.myFavorites;
}

/** Отменить бронь. */
export async function cancelBooking(token: string, id: number): Promise<string | null> {
  const data = await gqlFetch<{ cancelBooking: { status: string | null } | null }>(
    CANCEL_BOOKING_MUTATION,
    { token, variables: { id: String(id) }, revalidate: 0 },
  );
  return data.cancelBooking?.status ?? null;
}

/** Переключить компанию в избранном. */
export async function toggleFavorite(
  token: string,
  companyId: number,
): Promise<{ isFavorite: boolean; companyIds: number[] }> {
  const data = await gqlFetch<{
    toggleFavorite: { isFavorite: boolean; companyIds: number[] } | null;
  }>(TOGGLE_FAVORITE_MUTATION, { token, variables: { id: String(companyId) }, revalidate: 0 });
  return data.toggleFavorite ?? { isFavorite: false, companyIds: [] };
}
