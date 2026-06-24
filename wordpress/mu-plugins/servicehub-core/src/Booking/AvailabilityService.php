<?php
/**
 * Сбор данных из WordPress и расчёт свободных слотов записи.
 *
 * Достаёт рабочие часы компании, длительность/буфер услуги и занятые брони,
 * затем делегирует чистому SlotGenerator. Доступность считается на уровне
 * компании (общий календарь) — MVP без выбора сотрудника.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Booking;

use ServiceHub\PostTypes\BookingPostType;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\PostTypes\ServicePostType;

defined( 'ABSPATH' ) || exit;

/**
 * Считает свободные слоты для услуги на конкретную дату.
 */
final class AvailabilityService {

	private const STEP_MINUTES     = 30;
	private const DEFAULT_DURATION = 60;

	private SlotGenerator $generator;

	public function __construct( ?SlotGenerator $generator = null ) {
		$this->generator = $generator ?? new SlotGenerator();
	}

	/**
	 * Свободные времена начала записи на услугу в дату (Y-m-d).
	 *
	 * @param int    $service_id ID услуги.
	 * @param string $date       Дата 'Y-m-d'.
	 * @return array<int, string> Времена 'HH:MM'.
	 */
	public function available_slots( int $service_id, string $date ): array {
		if ( ServicePostType::POST_TYPE !== get_post_type( $service_id ) ) {
			return array();
		}
		$timestamp = strtotime( $date );
		if ( false === $timestamp ) {
			return array();
		}

		$company_id = (int) get_post_meta( $service_id, 'service_company', true );
		if ( ! $company_id || CompanyPostType::POST_TYPE !== get_post_type( $company_id ) ) {
			return array();
		}

		$duration = (int) get_post_meta( $service_id, 'service_duration', true );
		$duration = $duration > 0 ? $duration : self::DEFAULT_DURATION;
		$buffer   = (int) get_post_meta( $service_id, 'service_buffer', true );

		$weekday = strtolower( gmdate( 'D', $timestamp ) ); // mon, tue, …
		$hours   = $this->hours_for_day( $company_id, $weekday );
		if ( null === $hours ) {
			return array();
		}

		return $this->generator->generate(
			$hours['open'],
			$hours['close'],
			array(),
			$duration,
			$buffer,
			self::STEP_MINUTES,
			$this->booked_intervals( $company_id, $date )
		);
	}

	/**
	 * Рабочие часы компании на день недели или null (выходной).
	 *
	 * @param int    $company_id ID компании.
	 * @param string $weekday    'mon'…'sun'.
	 * @return array{open:string,close:string}|null
	 */
	private function hours_for_day( int $company_id, string $weekday ): ?array {
		if ( ! function_exists( 'get_field' ) ) {
			return null;
		}
		$rows = get_field( 'company_hours', $company_id );
		if ( ! is_array( $rows ) ) {
			return null;
		}
		foreach ( $rows as $row ) {
			if ( ( $row['day'] ?? '' ) === $weekday && ! empty( $row['open_time'] ) && ! empty( $row['close_time'] ) ) {
				return array(
					'open'  => (string) $row['open_time'],
					'close' => (string) $row['close_time'],
				);
			}
		}
		return null;
	}

	/**
	 * Занятые интервалы компании на дату (pending + confirmed).
	 *
	 * @param int    $company_id ID компании.
	 * @param string $date       Дата 'Y-m-d'.
	 * @return array<int, array{time:string,duration:int}>
	 */
	private function booked_intervals( int $company_id, string $date ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => BookingPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'   => 'booking_company',
						'value' => $company_id,
					),
					array(
						'key'   => 'booking_date',
						'value' => $date,
					),
					array(
						'key'     => 'booking_status',
						'value'   => array( BookingPostType::STATUS_PENDING, BookingPostType::STATUS_CONFIRMED ),
						'compare' => 'IN',
					),
				),
			)
		);

		$intervals = array();
		foreach ( $query->posts as $booking_id ) {
			$time = (string) get_post_meta( $booking_id, 'booking_time', true );
			if ( '' === $time ) {
				continue;
			}
			$intervals[] = array(
				'time'     => $time,
				'duration' => (int) get_post_meta( $booking_id, 'booking_duration', true ),
			);
		}
		return $intervals;
	}
}
