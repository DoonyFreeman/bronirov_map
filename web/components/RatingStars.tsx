interface RatingStarsProps {
  rating: number | null;
  count?: number | null;
  size?: number;
}

/**
 * Звёздный рейтинг (с дробным заполнением последней звезды).
 * Доступно: число дублируется текстом, не только цветом.
 */
export function RatingStars({ rating, count, size = 18 }: RatingStarsProps) {
  if (!rating) {
    return <span style={{ color: 'var(--color-muted-fg)', fontSize: '0.9rem' }}>Нет оценок</span>;
  }

  const pct = Math.max(0, Math.min(100, (rating / 5) * 100));
  const label = `Рейтинг ${rating.toFixed(1)} из 5${count ? `, отзывов: ${count}` : ''}`;

  return (
    <span
      role="img"
      aria-label={label}
      style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}
    >
      <span
        style={{
          position: 'relative',
          display: 'inline-block',
          fontSize: size,
          lineHeight: 1,
          letterSpacing: 2,
        }}
        aria-hidden="true"
      >
        <span style={{ color: 'var(--color-border)' }}>★★★★★</span>
        <span
          style={{
            position: 'absolute',
            inset: 0,
            width: `${pct}%`,
            overflow: 'hidden',
            color: 'var(--color-primary)',
            whiteSpace: 'nowrap',
          }}
        >
          ★★★★★
        </span>
      </span>
      <span style={{ fontWeight: 600, fontVariantNumeric: 'tabular-nums' }}>
        {rating.toFixed(1)}
      </span>
      {typeof count === 'number' && count > 0 && (
        <span style={{ color: 'var(--color-muted-fg)', fontSize: '0.9rem' }}>· {count}</span>
      )}
    </span>
  );
}
