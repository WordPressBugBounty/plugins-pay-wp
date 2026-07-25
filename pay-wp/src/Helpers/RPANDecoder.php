<?php

namespace WPDesk\GatewayWPPay\Helpers;

class RPANDecoder {
	public function base64_to_array( string $encoded_data ): array {

		try {
			$decoded_data = base64_decode( $encoded_data );
			$xml_data     = simplexml_load_string( $decoded_data );
		} catch ( \Exception $e ) {
			return [];
		}


		return DataConverter::simplexml_to_array( $xml_data );
	}
}
