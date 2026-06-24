<?php
/**
 * Транспорт Telegram Bot API + чистые билдеры сообщений.
 *
 * Отправка — fire-and-forget через wp_remote_post.
 * ponytail: без очереди/ретраев — добавить WP-Cron-очередь, если доставка
 * начнёт падать. Билдеры текста/клавиатуры вынесены статикой и покрыты тестом.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Notifications;

// ponytail: без ABSPATH-гарда — файл только автозагружается (как SlotGenerator),
// чистые билдеры тестируются вне WP; WP-вызовы живут внутри методов.

/**
 * Отправка сообщений ботом и построение их содержимого.
 */
final class Telegram {

	private const API = 'https://api.telegram.org/bot';

	/**
	 * Токен бота из окружения (пусто — уведомления выключены).
	 */
	public static function token(): string {
		return (string) getenv( 'TELEGRAM_BOT_TOKEN' );
	}

	/**
	 * Текст уведомления о брони.
	 *
	 * @param array{service:string,company:string,date:string,time:string,client:string,status:string} $data Данные.
	 */
	public static function booking_text( array $data ): string {
		$labels = array(
			'pending'   => '🆕 Новая заявка',
			'confirmed' => '✅ Запись подтверждена',
			'cancelled' => '❌ Запись отклонена',
		);
		$head = $labels[ $data['status'] ] ?? 'Запись';
		return sprintf(
			"%s\n\nУслуга: %s\nКомпания: %s\nДата: %s %s\nКлиент: %s",
			$head,
			$data['service'],
			$data['company'],
			$data['date'],
			$data['time'],
			$data['client']
		);
	}

	/**
	 * Inline-клавиатура «Подтвердить/Отклонить» для брони.
	 *
	 * @param int $booking_id ID брони.
	 * @return array<string, mixed>
	 */
	public static function confirm_keyboard( int $booking_id ): array {
		return array(
			'inline_keyboard' => array(
				array(
					array(
						'text'          => 'Подтвердить',
						'callback_data' => 'confirm:' . $booking_id,
					),
					array(
						'text'          => 'Отклонить',
						'callback_data' => 'decline:' . $booking_id,
					),
				),
			),
		);
	}

	/**
	 * Отправить сообщение в чат.
	 *
	 * @param string                    $chat_id  ID чата.
	 * @param string                    $text     Текст.
	 * @param array<string, mixed>|null $keyboard reply_markup или null.
	 */
	public static function send_message( string $chat_id, string $text, ?array $keyboard = null ): void {
		if ( '' === self::token() || '' === $chat_id ) {
			return;
		}
		$body = array(
			'chat_id' => $chat_id,
			'text'    => $text,
		);
		if ( null !== $keyboard ) {
			$body['reply_markup'] = wp_json_encode( $keyboard );
		}
		self::call( 'sendMessage', $body );
	}

	/**
	 * Ответить на нажатие inline-кнопки (убрать «часики»).
	 *
	 * @param string $callback_query_id ID callback.
	 * @param string $text              Короткий тост.
	 */
	public static function answer_callback( string $callback_query_id, string $text ): void {
		if ( '' === self::token() ) {
			return;
		}
		self::call(
			'answerCallbackQuery',
			array(
				'callback_query_id' => $callback_query_id,
				'text'              => $text,
			)
		);
	}

	/**
	 * Вызов метода Bot API (неблокирующий).
	 *
	 * @param string               $method Метод API.
	 * @param array<string, mixed> $body   Тело запроса.
	 */
	private static function call( string $method, array $body ): void {
		wp_remote_post(
			self::API . self::token() . '/' . $method,
			array(
				'timeout'  => 3,
				'blocking' => false,
				'body'     => $body,
			)
		);
	}
}
