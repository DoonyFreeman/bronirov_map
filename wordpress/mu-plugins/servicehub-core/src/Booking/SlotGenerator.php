<?php
/**
 * Чистый движок генерации слотов записи (без зависимостей от WordPress).
 *
 * Изолирован намеренно — вся арифметика времени тестируется юнит-тестами
 * (tests/SlotGeneratorTest.php), а сбор данных из WP живёт в AvailabilityService.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Booking;

/**
 * Генерирует список свободных времён начала записи на день.
 */
final class SlotGenerator {

	/**
	 * Свободные слоты на день.
	 *
	 * @param string|null       $open        Время открытия 'HH:MM' (null — выходной).
	 * @param string|null       $close       Время закрытия 'HH:MM'.
	 * @param array<int, array> $breaks      Перерывы: [['start'=>'13:00','end'=>'14:00']].
	 * @param int               $duration    Длительность услуги, мин.
	 * @param int               $buffer      Буфер между записями, мин.
	 * @param int               $step        Шаг сетки слотов, мин.
	 * @param array<int, array> $booked      Занятые слоты: [['time'=>'14:00','duration'=>60]].
	 * @return array<int, string>               Времена начала 'HH:MM'.
	 */
	public function generate(
		?string $open,
		?string $close,
		array $breaks,
		int $duration,
		int $buffer,
		int $step,
		array $booked
	): array {
		if ( null === $open || null === $close || $duration <= 0 || $step <= 0 ) {
			return array();
		}

		$open_min  = $this->to_minutes( $open );
		$close_min = $this->to_minutes( $close );

		$break_intervals  = array_map(
			fn ( $b ) => array( $this->to_minutes( $b['start'] ), $this->to_minutes( $b['end'] ) ),
			$breaks
		);
		$booked_intervals = array_map(
			fn ( $b ) => array( $this->to_minutes( $b['time'] ), $this->to_minutes( $b['time'] ) + (int) $b['duration'] ),
			$booked
		);

		$slots = array();
		for ( $start = $open_min; $start + $duration <= $close_min; $start += $step ) {
			$end = $start + $duration;

			if ( $this->overlaps_any_break( $start, $end, $break_intervals ) ) {
				continue;
			}
			if ( $this->overlaps_any_booking( $start, $end, $booked_intervals, $buffer ) ) {
				continue;
			}

			$slots[] = $this->to_time( $start );
		}

		return $slots;
	}

	/**
	 * Пересекается ли слот [start,end) с каким-либо перерывом.
	 *
	 * @param int                        $start     Начало слота, мин.
	 * @param int                        $end       Конец слота, мин.
	 * @param array<int, array{int,int}> $breaks Перерывы в минутах.
	 */
	private function overlaps_any_break( int $start, int $end, array $breaks ): bool {
		foreach ( $breaks as [$bs, $be] ) {
			if ( $start < $be && $end > $bs ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Пересекается ли слот с занятой записью (с учётом буфера).
	 *
	 * @param int                        $start  Начало слота, мин.
	 * @param int                        $end    Конец слота, мин.
	 * @param array<int, array{int,int}> $booked Занятые интервалы в минутах.
	 * @param int                        $buffer Буфер, мин.
	 */
	private function overlaps_any_booking( int $start, int $end, array $booked, int $buffer ): bool {
		foreach ( $booked as [$bs, $be] ) {
			if ( $start < $be + $buffer && $end > $bs - $buffer ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 'HH:MM' → минуты от полуночи.
	 *
	 * @param string $time Время.
	 */
	private function to_minutes( string $time ): int {
		[$h, $m] = array_pad( explode( ':', $time ), 2, '0' );
		return ( (int) $h ) * 60 + (int) $m;
	}

	/**
	 * Минуты от полуночи → 'HH:MM'.
	 *
	 * @param int $minutes Минуты.
	 */
	private function to_time( int $minutes ): string {
		return sprintf( '%02d:%02d', intdiv( $minutes, 60 ), $minutes % 60 );
	}
}
