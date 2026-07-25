<?php

namespace WPDesk\GatewayWPPay\WooCommerceGateway;

use WPDesk\GatewayWPPay\BlueMediaApi\BlueMediaClientFactory;
use WPPayVendor\Psr\Log\LoggerInterface;
use WPPayVendor\WPDesk\View\Renderer\Renderer;

final class GatewaysProxy {

	private StandardPaymentGateway $autopay_gateway;
	private BlikZeroEmbedGateway $blik_gateway;
	private SubscriptionGateway $subscription_gateway;
	private BlueMediaClientFactory $client_factory;
	private string $plugin_url;
	private LoggerInterface $logger;
	private Renderer $renderer;

	public function __construct( BlueMediaClientFactory $client_factory, string $plugin_url, Renderer $renderer, LoggerInterface $logger ) {
		$this->client_factory = $client_factory;
		$this->plugin_url     = $plugin_url;
		$this->logger         = $logger;
		$this->renderer       = $renderer;
	}

	public function get_autopay_gateway(): StandardPaymentGateway {
		return $this->autopay_gateway ??= new StandardPaymentGateway( $this->client_factory, $this->plugin_url, $this->logger );
	}

	public function get_blik_gateway(): BlikZeroEmbedGateway {
		return $this->blik_gateway ??= new BlikZeroEmbedGateway( $this->client_factory, $this->plugin_url, $this->renderer, $this->logger );
	}

	public function get_subscription_gateway(): SubscriptionGateway {
		return $this->subscription_gateway ??= new SubscriptionGateway( $this->client_factory, $this->plugin_url, $this->logger );
	}
}
