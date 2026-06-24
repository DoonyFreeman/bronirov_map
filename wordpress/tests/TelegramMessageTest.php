<?php
/**
 * Тесты чистых билдеров Telegram-сообщений.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Tests;

use PHPUnit\Framework\TestCase;
use ServiceHub\Notifications\Telegram;

final class TelegramMessageTest extends TestCase {

	public function test_booking_text_contains_details(): void {
		$text = Telegram::booking_text(
			array(
				'service' => 'Мужская стрижка',
				'company' => 'Барбершоп',
				'date'    => '2026-06-24',
				'time'    => '14:00',
				'client'  => 'Иван',
				'status'  => 'pending',
			)
		);
		$this->assertStringContainsString( 'Новая заявка', $text );
		$this->assertStringContainsString( 'Мужская стрижка', $text );
		$this->assertStringContainsString( '14:00', $text );
		$this->assertStringContainsString( 'Иван', $text );
	}

	public function test_confirm_keyboard_has_callback_data(): void {
		$kb = Telegram::confirm_keyboard( 21 );
		$buttons = $kb['inline_keyboard'][0];
		$this->assertSame( 'confirm:21', $buttons[0]['callback_data'] );
		$this->assertSame( 'decline:21', $buttons[1]['callback_data'] );
	}
}
