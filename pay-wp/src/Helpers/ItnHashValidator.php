<?php

namespace WPDesk\GatewayWPPay\Helpers;

final class ItnHashValidator {

	public static function is_valid( string $encoded_transactions, callable $hash_generator ): bool {
		$xml = self::decode_payload( $encoded_transactions );

		if ( ! $xml instanceof \SimpleXMLElement ) {
			return false;
		}

		$data = DataConverter::simplexml_to_array( $xml );
		if ( empty( $data ) || empty( $data['hash'] ) ) {
			return false;
		}

		$received_hash = trim( (string) $data['hash'] );
		unset( $data['hash'] );

		$expected_hash = $hash_generator( ArrayFlattener::flatten_array( $data ) );

		return is_string( $expected_hash ) && hash_equals( $expected_hash, $received_hash );
	}

	private static function decode_payload( string $encoded_transactions ): ?\SimpleXMLElement {
		$decoded_payload = base64_decode( $encoded_transactions, true );
		if ( $decoded_payload === false ) {
			return null;
		}

		$previous_state = libxml_use_internal_errors( true );
		$xml            = simplexml_load_string( $decoded_payload );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		return $xml instanceof \SimpleXMLElement ? $xml : null;
	}
}
