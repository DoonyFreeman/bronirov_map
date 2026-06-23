import type { CompanyHours } from '@/lib/graphql/types';

const DAY_LABELS: Record<string, string> = {
  mon: 'Понедельник',
  tue: 'Вторник',
  wed: 'Среда',
  thu: 'Четверг',
  fri: 'Пятница',
  sat: 'Суббота',
  sun: 'Воскресенье',
};
const DAY_ORDER = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

export function HoursTable({ hours }: { hours: CompanyHours[] }) {
  if (hours.length === 0) {
    return <p style={{ color: 'var(--color-muted-fg)' }}>Часы работы не указаны.</p>;
  }

  const byDay = new Map(hours.filter((h) => h.day).map((h) => [h.day as string, h]));

  return (
    <table className="hours">
      <tbody>
        {DAY_ORDER.map((day) => {
          const row = byDay.get(day);
          return (
            <tr key={day}>
              <th scope="row">{DAY_LABELS[day]}</th>
              <td>
                {row && row.open && row.close ? (
                  `${row.open} – ${row.close}`
                ) : (
                  <span style={{ color: 'var(--color-muted-fg)' }}>Выходной</span>
                )}
              </td>
            </tr>
          );
        })}
      </tbody>
    </table>
  );
}
