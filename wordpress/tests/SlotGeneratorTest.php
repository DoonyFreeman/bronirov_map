<?php
/**
 * Юнит-тесты чистого движка генерации слотов.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Tests;

use PHPUnit\Framework\TestCase;
use ServiceHub\Booking\SlotGenerator;

final class SlotGeneratorTest extends TestCase {

	private SlotGenerator $gen;

	protected function setUp(): void {
		$this->gen = new SlotGenerator();
	}

	public function test_basic_slots_within_hours(): void {
		// 10:00–12:00, услуга 60 мин, шаг 60 → 10:00 и 11:00 (12:00 не влезает).
		$slots = $this->gen->generate( '10:00', '12:00', array(), 60, 0, 60, array() );
		$this->assertSame( array( '10:00', '11:00' ), $slots );
	}

	public function test_step_granularity(): void {
		$slots = $this->gen->generate( '10:00', '12:00', array(), 60, 0, 30, array() );
		$this->assertSame( array( '10:00', '10:30', '11:00' ), $slots );
	}

	public function test_closed_day_returns_empty(): void {
		$this->assertSame( array(), $this->gen->generate( null, null, array(), 60, 0, 30, array() ) );
	}

	public function test_duration_does_not_fit_before_close(): void {
		// 10:00–11:00, услуга 90 мин → ничего не влезает.
		$this->assertSame( array(), $this->gen->generate( '10:00', '11:00', array(), 90, 0, 30, array() ) );
	}

	public function test_booked_interval_blocks_overlapping_starts(): void {
		// Занято 10:00 на 60 мин, буфер 0.
		$booked = array( array( 'time' => '10:00', 'duration' => 60 ) );
		$slots  = $this->gen->generate( '10:00', '12:00', array(), 60, 0, 30, $booked );
		$this->assertSame( array( '11:00' ), $slots );
	}

	public function test_buffer_between_bookings(): void {
		// Тот же занятый слот, но буфер 30 мин → 11:00 тоже исключается.
		$booked = array( array( 'time' => '10:00', 'duration' => 60 ) );
		$slots  = $this->gen->generate( '10:00', '12:00', array(), 60, 30, 30, $booked );
		$this->assertSame( array(), $slots );
	}

	public function test_break_excludes_overlapping_slots(): void {
		// 10:00–14:00, услуга 60, шаг 60, перерыв 12:00–13:00.
		$breaks = array( array( 'start' => '12:00', 'end' => '13:00' ) );
		$slots  = $this->gen->generate( '10:00', '14:00', $breaks, 60, 0, 60, array() );
		$this->assertSame( array( '10:00', '11:00', '13:00' ), $slots );
	}
}
