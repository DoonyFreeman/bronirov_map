'use client';

import L from 'leaflet';
import { MapContainer, Marker, Popup, TileLayer } from 'react-leaflet';

import 'leaflet/dist/leaflet.css';

import type { Company } from '@/lib/graphql/types';
import type { LatLng } from '@/lib/geo';

// ponytail: иконки маркера с CDN — иначе ломаются пути картинок в бандлере.
// Свести в локальные ассеты, если нужна офлайн-сборка.
const icon = L.icon({
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
});

const userIcon = L.divIcon({
  className: 'user-marker',
  html: '<span></span>',
  iconSize: [18, 18],
});

interface CompaniesMapProps {
  companies: Company[];
  userPos: LatLng | null;
}

export default function CompaniesMap({ companies, userPos }: CompaniesMapProps) {
  const points = companies.filter(
    (c) => typeof c.latitude === 'number' && typeof c.longitude === 'number',
  );
  const center: [number, number] = userPos
    ? [userPos.lat, userPos.lng]
    : points.length > 0
      ? [points[0].latitude as number, points[0].longitude as number]
      : [55.751, 37.618]; // Москва по умолчанию

  return (
    <MapContainer
      center={center}
      zoom={11}
      style={{ height: 420, borderRadius: 12 }}
      scrollWheelZoom
    >
      <TileLayer
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
      />
      {userPos && <Marker position={[userPos.lat, userPos.lng]} icon={userIcon} />}
      {points.map((c) => (
        <Marker
          key={c.databaseId}
          position={[c.latitude as number, c.longitude as number]}
          icon={icon}
        >
          <Popup>
            <strong>{c.title}</strong>
            <br />
            {c.address}
            <br />
            <a href={`/company/${c.slug}`}>Открыть профиль →</a>
          </Popup>
        </Marker>
      ))}
    </MapContainer>
  );
}
