<?php

namespace WPDesk\GatewayWPPay\Helpers;

final class WalletPayment {
	public static function read_json( $raw, int $limit ): array {
		if ( ! is_string( $raw ) || strlen( $raw ) > $limit ) {
			throw new \RuntimeException( __( 'Invalid payment data.', 'pay-wp' ) );
		}
		// Store API payment_data is already unslashed; classic POST is not.
		$data = json_decode( $raw, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$data = json_decode( wp_unslash( $raw ), true );
		}
		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( __( 'Invalid payment data.', 'pay-wp' ) );
		}
		return $data;
	}

	public static function read_quote(): array {
		$quote = self::read_json( $_POST['wppay_wallet_quote'] ?? '', 2048 );
		$signature = $quote['signature'] ?? null;
		unset( $quote['signature'] );
		if ( ! is_string( $signature ) || ! hash_equals( self::sign_quote( $quote ), $signature )
			|| ! isset( $quote['issued'], $quote['reference'] ) || ! is_int( $quote['issued'] )
			|| $quote['issued'] < time() - 600 || $quote['issued'] > time()
			|| ! is_string( $quote['reference'] ) || ! preg_match( '/^wpw[a-f0-9]{29}$/D', $quote['reference'] ) ) {
			throw new \RuntimeException( __( 'Please refresh the checkout and authorize the payment again.', 'pay-wp' ) );
		}
		return $quote;
	}

	public static function sign_quote( array $quote ): string {
		$session = WC()->session;
		if ( ! $session ) {
			throw new \RuntimeException( __( 'Please refresh the checkout and authorize the payment again.', 'pay-wp' ) );
		}
		if ( ! $session->has_session() ) {
			$session->set_customer_session_cookie( true );
		}
		// Session data survives WooCommerce's guest-to-account migration; the customer ID does not.
		$binding = $session->get( 'wppay_wallet_binding' );
		if ( ! $binding ) {
			$binding = bin2hex( random_bytes( 32 ) );
			$session->set( 'wppay_wallet_binding', $binding );
		}
		return wp_hash( wp_json_encode( $quote ) . '|' . $binding, 'auth' );
	}

	public static function payment_token( string $wallet, array $quote ): string {
		$data = self::read_json( $_POST['wppay_wallet_token'] ?? '', 65536 );
		$required = 'apple' === $wallet ? [ 'signature', 'version', 'data' ] : [ 'signature', 'protocolVersion', 'signedMessage' ];
		foreach ( $required as $key ) {
			if ( empty( $data[ $key ] ) || ! is_string( $data[ $key ] ) ) {
				throw new \RuntimeException( __( 'Invalid payment token. Please authorize the payment again.', 'pay-wp' ) );
			}
		}
		if ( 'apple' === $wallet ) {
			$application_data = $data['header']['applicationData'] ?? null;
			if ( ! is_string( $application_data ) || ! hash_equals( hash( 'sha256', $quote['reference'] ), strtolower( $application_data ) ) ) {
				throw new \RuntimeException( __( 'Invalid payment token. Please authorize the payment again.', 'pay-wp' ) );
			}
		}
		return base64_encode( wp_json_encode( $data ) );
	}

	public static function resolve_order( string $reference ) {
		if ( preg_match( '/^wpw[a-f0-9]{29}$/D', $reference ) ) {
			$attempt = get_option( 'wppay_wallet_reference_' . $reference, [] );
			return wc_get_order( $attempt['order'] ?? 0 );
		}
		return ctype_digit( $reference ) ? wc_get_order( (int) $reference ) : false;
	}

	public static function assert_can_start( array $transaction ): void {
		$reference = (string) ( $transaction['orderID'] ?? $transaction['OrderID'] ?? '' );
		$order = self::resolve_order( $reference );
		if ( $order && ( $order->is_paid() || get_option( 'wppay_wallet_pending_' . $order->get_id() ) ) ) {
			throw new \RuntimeException( __( 'The previous payment is awaiting confirmation. Please contact the shop before trying again.', 'pay-wp' ) );
		}
	}

	public static function confirm_itn( $order, $itn, string $service_id ): bool {
		$reference = $itn->getOrderID();
		if ( ! preg_match( '/^wpw[a-f0-9]{29}$/D', $reference ) ) {
			return true;
		}
		$attempt = get_option( 'wppay_wallet_reference_' . $reference, [] );
		return $order && ( $attempt['order'] ?? 0 ) === $order->get_id()
			&& ( $attempt['service'] ?? '' ) === $service_id
			&& ( $attempt['currency'] ?? '' ) === $itn->getCurrency()
			&& ( $attempt['amount'] ?? '' ) === number_format( (float) $itn->getAmount(), 2, '.', '' )
			&& $order->get_currency() === $itn->getCurrency()
			&& number_format( (float) $order->get_total(), 2, '.', '' ) === $attempt['amount'];
	}

	public static function finish_attempt( int $order_id, string $reference ): void {
		if ( get_option( 'wppay_wallet_pending_' . $order_id ) === $reference ) {
			delete_option( 'wppay_wallet_pending_' . $order_id );
		}
	}

	public static function delete_order_references( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			foreach ( $order->get_meta( '_wppay_wallet_reference', false ) as $meta ) {
				delete_option( 'wppay_wallet_reference_' . $meta->value );
			}
		}
		delete_option( 'wppay_wallet_pending_' . $order_id );
	}
}
