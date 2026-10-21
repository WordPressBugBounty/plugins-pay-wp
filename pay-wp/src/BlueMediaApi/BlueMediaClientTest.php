<?php

namespace WPDesk\GatewayWPPay\BlueMediaApi;


class BlueMediaClientTest extends BlueMediaClient {
	protected bool $sandbox = true;

	protected function get_wallet_api_address(): string {
		return 'https://testpay.autopay.eu';
	}

	protected function get_api_address(): string {
		return 'https://pay-accept.bm.pl';
	}
}
