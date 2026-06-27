'use client';

import dynamic from 'next/dynamic';
import Link from 'next/link';
import { useMemo, useState } from 'react';

import { distanceKm, type LatLng } from '@/lib/geo';
import type { Company } from '@/lib/graphql/types';

// Карта только на клиенте (Leaflet требует window).
const CompaniesMap = dynamic(() => import('@/components/CompaniesMap'), {
  ssr: false,
  loading: () => <div className="empty">Загрузка карты…</div>,
});

const RADII = [
  { label: 'Любой радиус', km: 0 },
  { label: '5 км', km: 5 },
  { label: '10 км', km: 10 },
  { label: '50 км', km: 50 },
];

export function MapExplorer({ companies }: { companies: Company[] }) {
  const [userPos, setUserPos] = useState<LatLng | null>(null);
  const [radiusKm, setRadiusKm] = useState(0);
  const [geoError, setGeoError] = useState<string | null>(null);

  function locate() {
    setGeoError(null);
    if (!navigator.geolocation) {
      setGeoError('Геолокация не поддерживается браузером.');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => setUserPos({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => setGeoError('Не удалось определить местоположение.'),
    );
  }

  const list = useMemo(() => {
    const withDist = companies
      .filter((c) => typeof c.latitude === 'number' && typeof c.longitude === 'number')
      .map((c) => ({
        company: c,
        dist: userPos
          ? distanceKm(userPos, { lat: c.latitude as number, lng: c.longitude as number })
          : null,
      }));
    const filtered =
      userPos && radiusKm > 0 ? withDist.filter((x) => (x.dist ?? Infinity) <= radiusKm) : withDist;
    return userPos ? filtered.sort((a, b) => (a.dist ?? 0) - (b.dist ?? 0)) : filtered;
  }, [companies, userPos, radiusKm]);

  return (
    <div>
      <div className="filters" style={{ marginBottom: 'var(--space-6)' }}>
        <button type="button" className="btn btn--primary" onClick={locate}>
          Найти рядом
        </button>
        {userPos && (
          <div className="field" style={{ flex: '0 1 200px' }}>
            <label htmlFor="radius">Радиус</label>
            <select
              id="radius"
              value={radiusKm}
              onChange={(e) => setRadiusKm(Number(e.target.value))}
            >
              {RADII.map((r) => (
                <option key={r.km} value={r.km}>
                  {r.label}
                </option>
              ))}
            </select>
          </div>
        )}
        {geoError && <span className="booking__error">{geoError}</span>}
      </div>

      <CompaniesMap companies={list.map((x) => x.company)} userPos={userPos} />

      <ul className="service-list" style={{ marginTop: 'var(--space-6)' }}>
        {list.map(({ company, dist }) => (
          <li key={company.databaseId} className="service-row">
            <div>
              <Link href={`/company/${company.slug}`} className="service-row__name">
                {company.title}
              </Link>
              {company.address && <span className="service-row__dur">{company.address}</span>}
            </div>
            {dist !== null && <span className="service-row__price">{dist.toFixed(1)} км</span>}
          </li>
        ))}
      </ul>
    </div>
  );
}
