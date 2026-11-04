<?php

namespace WPDesk\GatewayWPPay\Helpers;

use WPPayVendor\BlueMedia\Itn\Builder\ItnDtoBuilder;
use WPPayVendor\BlueMedia\Itn\ValueObject\Itn;
use WPPayVendor\BlueMedia\Hash\HashableInterface;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;

final class ItnMessage implements HashableInterface, SerializableInterface {
	private Itn $itn;
	private array $hash_data;

	private function __construct( Itn $itn, array $hash_data ) {
		$this->itn = $itn;
		$this->hash_data = $hash_data;
	}

	public function get_itn(): Itn {
		return $this->itn;
	}

	public function toArray(): array {
		return $this->hash_data;
	}

	public function getHash(): string {
		return $this->itn->getHash();
	}

	public function isHashPresent(): bool {
		return $this->itn->isHashPresent();
	}

	public static function parse( $encoded_transactions ): self {
		if ( ! is_string( $encoded_transactions ) || strlen( $encoded_transactions ) > 65536 ) {
			throw new \InvalidArgumentException( 'Invalid ITN payload.' );
		}
		$payload = base64_decode( $encoded_transactions, true );
		if ( false === $payload || false !== stripos( $payload, '<!DOCTYPE' ) ) {
			throw new \InvalidArgumentException( 'Invalid ITN encoding.' );
		}
		$previous = libxml_use_internal_errors( true );
		try {
			$document = new \DOMDocument();
			if ( ! $document->loadXML( $payload, LIBXML_NONET ) || $document->doctype
				|| ! $document->schemaValidate( __DIR__ . '/itn.xsd' ) ) {
				$error = libxml_get_last_error();
				$element = '';
				if ( $error && preg_match( "/Element '([a-zA-Z][a-zA-Z0-9]*)'/", $error->message, $matches ) ) {
					$element = $matches[1];
				}
				throw new \InvalidArgumentException( sprintf( 'Invalid ITN structure (element: %s; XML error: %d).', $element, $error ? $error->code : 0 ) );
			}
			// The protocol uses | separators; no field may introduce another column.
			if ( false !== strpos( $document->documentElement->textContent, '|' ) ) {
				throw new \InvalidArgumentException( 'Invalid ITN field.' );
			}
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
		$xpath = new \DOMXPath( $document );
		$data = [ 'serviceID' => $xpath->evaluate( 'string(/transactionList/serviceID)' ) ];
		// Autopay hash order: https://developers.autopay.pl/pdf?documentId=384 (ITN and additional ITN fields).
		foreach ( [
			'orderID', 'remoteID', 'amount', 'currency', 'gatewayID', 'paymentDate', 'paymentStatus', 'paymentStatusDetails',
			'addressIP', 'customerNumber', 'title',
			'customerData/fName', 'customerData/lName', 'customerData/streetName', 'customerData/streetHouseNo',
			'customerData/streetStaircaseNo', 'customerData/streetPremiseNo', 'customerData/postalCode',
			'customerData/city', 'customerData/nrb', 'customerData/senderData'
		] as $field ) {
			$value = $xpath->evaluate( 'string(/transactionList/transactions/transaction/' . $field . ')' );
			if ( '' !== $value ) {
				$data[ $field ] = $value;
			}
		}
		return new self( ItnDtoBuilder::build( $payload )->getItn(), $data );
	}
}
