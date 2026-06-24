<?php
/**
 * Telegram-уведомления о бронировании.
 *
 * - Новая бронь → сообщение компании с кнопками «Подтвердить/Отклонить».
 * - Нажатие кнопки (Telegram webhook) → смена статуса брони.
 * - Клиент подключает уведомления deep-link'ом t.me/bot?start=<bookingId>.
 * - Смена статуса → сообщение клиенту (если он привязал чат).
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Notifications;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\BookingPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Хуки уведомлений и входящий webhook бота.
 */
final class TelegramNotifications implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'servicehub_booking_created', array( $this, 'notify_company' ), 10, 1 );
		add_action( 'updated_post_meta', array( $this, 'on_meta_updated' ), 10, 4 );
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Новая бронь → компании с кнопками подтверждения.
	 *
	 * @param int $booking_id ID брони.
	 */
	public function notify_company( int $booking_id ): void {
		$data       = $this->booking_data( $booking_id );
		$company_id = (int) get_post_meta( $booking_id, 'booking_company', true );
		$chat       = (string) get_post_meta( $company_id, 'company_telegram_chat_id', true );

		Telegram::send_message(
			$chat,
			Telegram::booking_text( $data ),
			Telegram::confirm_keyboard( $booking_id )
		);
	}

	/**
	 * Смена статуса брони → уведомить клиента (если привязан чат).
	 *
	 * @param int    $meta_id  ID мета (не используется).
	 * @param int    $post_id  ID записи.
	 * @param string $meta_key Ключ мета.
	 * @param mixed  $value    Значение.
	 */
	public function on_meta_updated( int $meta_id, int $post_id, string $meta_key, $value ): void {
		if ( 'booking_status' !== $meta_key ) {
			return;
		}
		if ( ! in_array( $value, array( BookingPostType::STATUS_CONFIRMED, BookingPostType::STATUS_CANCELLED ), true ) ) {
			return;
		}
		$chat = (string) get_post_meta( $post_id, 'booking_client_chat_id', true );
		if ( '' === $chat ) {
			return;
		}
		$data           = $this->booking_data( $post_id );
		$data['status'] = (string) $value;
		Telegram::send_message( $chat, Telegram::booking_text( $data ) );
	}

	/**
	 * Зарегистрировать REST-маршрут для входящих апдейтов Telegram.
	 */
	public function register_route(): void {
		register_rest_route(
			'servicehub/v1',
			'/telegram',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'check_secret' ),
				'callback'            => array( $this, 'handle_update' ),
			)
		);
	}

	/**
	 * Проверка секрета вебхука (заголовок Telegram при setWebhook).
	 *
	 * @param \WP_REST_Request $request Запрос.
	 */
	public function check_secret( \WP_REST_Request $request ): bool {
		$secret = (string) getenv( 'TELEGRAM_WEBHOOK_SECRET' );
		return '' !== $secret
			&& hash_equals( $secret, (string) $request->get_header( 'x_telegram_bot_api_secret_token' ) );
	}

	/**
	 * Обработать апдейт: нажатие кнопки или /start привязки.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response
	 */
	public function handle_update( \WP_REST_Request $request ): \WP_REST_Response {
		$update = (array) $request->get_json_params();

		if ( isset( $update['callback_query'] ) ) {
			$this->handle_callback( (array) $update['callback_query'] );
		} elseif ( isset( $update['message'] ) ) {
			$this->handle_message( (array) $update['message'] );
		}

		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Нажата inline-кнопка: сменить статус брони.
	 *
	 * @param array<string, mixed> $callback callback_query.
	 */
	private function handle_callback( array $callback ): void {
		$parts  = explode( ':', (string) ( $callback['data'] ?? '' ) );
		$action = $parts[0] ?? '';
		$id     = (int) ( $parts[1] ?? 0 );
		$map    = array(
			'confirm' => BookingPostType::STATUS_CONFIRMED,
			'decline' => BookingPostType::STATUS_CANCELLED,
		);

		if ( isset( $map[ $action ] ) && BookingPostType::POST_TYPE === get_post_type( $id ) ) {
			update_post_meta( $id, 'booking_status', $map[ $action ] );
			Telegram::answer_callback( (string) ( $callback['id'] ?? '' ), 'Статус обновлён' );
		}
	}

	/**
	 * Сообщение боту: привязка клиента через /start <bookingId>.
	 *
	 * @param array<string, mixed> $message message.
	 */
	private function handle_message( array $message ): void {
		$text    = (string) ( $message['text'] ?? '' );
		$chat_id = (string) ( $message['chat']['id'] ?? '' );
		if ( '' === $chat_id || ! str_starts_with( $text, '/start' ) ) {
			return;
		}

		$booking_id = (int) trim( substr( $text, strlen( '/start' ) ) );
		if ( BookingPostType::POST_TYPE !== get_post_type( $booking_id ) ) {
			return;
		}

		update_post_meta( $booking_id, 'booking_client_chat_id', $chat_id );
		Telegram::send_message( $chat_id, sprintf( 'Вы подключили уведомления по записи №%d.', $booking_id ) );
	}

	/**
	 * Собрать данные брони для текста уведомления.
	 *
	 * @param int $booking_id ID брони.
	 * @return array{service:string,company:string,date:string,time:string,client:string,status:string}
	 */
	private function booking_data( int $booking_id ): array {
		$service_id = (int) get_post_meta( $booking_id, 'booking_service', true );
		$company_id = (int) get_post_meta( $booking_id, 'booking_company', true );
		return array(
			'service' => get_the_title( $service_id ),
			'company' => get_the_title( $company_id ),
			'date'    => (string) get_post_meta( $booking_id, 'booking_date', true ),
			'time'    => (string) get_post_meta( $booking_id, 'booking_time', true ),
			'client'  => (string) get_post_meta( $booking_id, 'booking_client_name', true ),
			'status'  => (string) get_post_meta( $booking_id, 'booking_status', true ),
		);
	}
}
