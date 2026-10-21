<?php

namespace WPDesk\GatewayWPPay\Blocks;

use WPDesk\GatewayWPPay\WooCommerceGateway\WalletGateway;

final class WalletBlock extends PaymentBlock {
	public function __construct( WalletGateway $gateway, string $assets_url ) {
		parent::__construct( $gateway, $assets_url . 'js/checkout-blocks/wallets', $gateway->icon );
		$this->name = $gateway->id;
	}

	public function is_active(): bool {
		return is_admin() ? 'yes' === $this->gateway->enabled : parent::is_active();
	}

	public function get_payment_method_script_handles() {
		$asset = require dirname( __DIR__, 2 ) . '/assets/js/checkout-blocks/wallets.asset.php';
		wp_register_script( 'wppay-wallet-blocks', $this->script_url . '.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'wppay-wallet-blocks', 'pay-wp', dirname( __DIR__, 2 ) . '/lang' );
		wp_enqueue_style( 'wppay-wallets', dirname( $this->script_url, 3 ) . '/css/wallets.css', [], filemtime( dirname( __DIR__, 2 ) . '/assets/css/wallets.css' ) );
		return [ 'wppay-wallet-blocks' ];
	}

	public function get_payment_method_data() {
		if ( is_admin() ) {
			return parent::get_payment_method_data();
		}
		try {
			return array_merge( parent::get_payment_method_data(), $this->gateway->get_frontend_data() );
		} catch ( \Exception $e ) {
			return parent::get_payment_method_data();
		}
	}
}
