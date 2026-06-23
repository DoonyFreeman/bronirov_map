import { RatingStars } from '@/components/RatingStars';
import type { Review } from '@/lib/graphql/types';

const dateFormatter = new Intl.DateTimeFormat('ru-RU', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
});

export function ReviewsList({ reviews }: { reviews: Review[] }) {
  if (reviews.length === 0) {
    return <p style={{ color: 'var(--color-muted-fg)' }}>Отзывов пока нет.</p>;
  }

  return (
    <ul className="reviews">
      {reviews.map((review) => (
        <li key={review.databaseId} className="review">
          <div className="review__head">
            <span className="review__author">{review.author ?? 'Аноним'}</span>
            {review.verified && (
              <span className="chip" title="Отзыв после подтверждённой записи">
                Проверен
              </span>
            )}
            {review.date && (
              <time className="review__date" dateTime={review.date}>
                {dateFormatter.format(new Date(review.date))}
              </time>
            )}
          </div>
          <RatingStars rating={review.rating} size={15} />
          {review.text && <p className="review__text">{review.text}</p>}
        </li>
      ))}
    </ul>
  );
}
