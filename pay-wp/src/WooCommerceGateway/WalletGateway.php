<?php

namespace WPDesk\GatewayWPPay\WooCommerceGateway;

use WPDesk\GatewayWPPay\BlueMediaApi\BlueMediaClientFactory;
use WPDesk\GatewayWPPay\Helpers\WalletPayment;

final class WalletGateway extends AbstractEmbedGateway {
	use RefundTrait;

	private string $wallet;
	private string $plugin_url;

	public function __construct( BlueMediaClientFactory $client_factory, string $plugin_url, string $wallet ) {
		if ( ! in_array( $wallet, [ 'apple', 'google' ], true ) ) {
			throw new \InvalidArgumentException( 'Invalid wallet.' );
		}
		$this->wallet = $wallet;
		$this->plugin_url = $plugin_url;
		$this->client_factory = $client_factory;
		$this->id = 'wppay_' . $wallet . '_pay';
		$this->title = 'apple' === $wallet ? 'Apple Pay' : 'Google Pay';
		$this->method_title = 'Autopay – ' . $this->title;
		$this->method_description = __( 'Digital wallet payments through Autopay. Requires HTTPS and activation in Autopay.', 'pay-wp' );
		$this->icon = $plugin_url . '/assets/images/' . $wallet . '-pay-mark.' . ( 'apple' === $wallet ? 'svg' : 'png' );
		$this->has_fields = true;
		$this->supports = [ 'products', 'refunds' ];
		$this->init_form_fields();
		$this->form_fields['title']['default'] = $this->title;
		$this->form_fields['description']['default'] = sprintf( __( 'Pay securely with %s via Autopay.', 'pay-wp' ), $this->title );
		if ( 'apple' === $wallet ) {
			$this->form_fields['redirect'] = [
				'type' => 'checkbox', 'title' => __( 'Payment mode', 'pay-wp' ),
				'label' => __( 'Redirect to Apple Pay in Autopay', 'pay-wp' ), 'default' => 'no',
				'description' => __( 'Authorize the payment on the Autopay page with only Apple Pay available. Merchant ID, certificates and domain verification are not required in this mode.', 'pay-wp' ),
			];
			$this->form_fields['merchant_id'] = [
				'type' => 'text', 'title' => __( 'Apple merchant ID', 'pay-wp' ),
				'description' => __( 'One configuration is used for both test and production. After changing the Autopay environment, update these fields if the new environment requires different Apple Pay credentials.', 'pay-wp' ),
			];
			$this->form_fields['certificate'] = [
				'type' => 'text', 'title' => __( 'Merchant identity certificate (PEM path)', 'pay-wp' ),
				'description' => __( 'Absolute server path outside the public web directory. Use a PEM merchant identity certificate, not the payment processing certificate.', 'pay-wp' ),
			];
			$this->form_fields['private_key'] = [ 'type' => 'text', 'title' => __( 'Merchant identity private key (PEM path)', 'pay-wp' ) ];
			$this->form_fields['domain_association'] = [
				'type' => 'apple_domain_association', 'title' => __( 'Apple Pay domain verification file', 'pay-wp' ),
				'description' => __( 'Upload the domain association file from Apple for this environment. Register your domain with Apple and provide Autopay with the payment processing certificate generated from its CSR.', 'pay-wp' ),
			];
		} else {
			$this->form_fields['merchant_id'] = [
				'type' => 'text', 'title' => __( 'Google merchant ID', 'pay-wp' ), 'default' => '',
				'description' => __( 'Optional Merchant ID from Google Pay & Wallet Console. Used only in production for your own Google Pay account with an approved shop domain. Leave empty to use the merchant configuration supplied by Autopay. Not required or sent in test mode. This is not the Autopay ServiceID or AcceptorID.', 'pay-wp' ),
			];
			$this->method_description .= ' ' . __( 'Google Pay requires an active Autopay channel and merchant configuration for the checkout domain. Configuration errors are recorded in WooCommerce → Status → Logs (source: pay-wp).', 'pay-wp' );
		}
		$this->init_settings();
		$this->enabled = $this->get_option( 'enabled', 'no' );
		$this->title = $this->get_option( 'title', $this->title );
		$this->description = $this->get_option( 'description', '' );
	}

	public function hooks(): void {
		if ( 'apple' === $this->wallet && ! array_key_exists( 'merchant_id', $this->settings )
			&& ( array_key_exists( 'test_merchant_id', $this->settings ) || array_key_exists( 'production_merchant_id', $this->settings ) ) ) {
			// Migrate the active environment once; subsequent environment changes keep the shared settings.
			$prefix = $this->sandbox() ? 'test_' : 'production_';
			foreach ( [ 'merchant_id', 'certificate', 'private_key', 'domain_association' ] as $key ) {
				$this->settings[ $key ] = $this->get_option( $prefix . $key, '' );
			}
			update_option( $this->get_option_key(), $this->settings );
		}
		parent::hooks();
		add_action( 'wp', [ $this, 'start_order_pay_session' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wc_ajax_' . $this->id . '_config', [ $this, 'ajax_config' ] );
		if ( 'apple' === $this->wallet ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			add_action( 'wc_ajax_' . $this->id . '_session', [ $this, 'ajax_session' ] );
			add_action( 'template_redirect', [ $this, 'serve_domain_association' ], 0 );
		}
		add_action( 'wp', [ $this, 'validate_order_pay_request' ], 19 );
		add_action( 'wp', [ $this, 'order_pay_error' ], 21 );
		add_filter( 'woocommerce_payment_successful_result', [ $this, 'order_pay_result' ], 20, 2 );
	}

	private function sandbox(): bool {
		return 'yes' === $this->client_factory->get_settings()->get_option( StandardPaymentGateway::SETTINGS_FIELD_TEST );
	}

	public function start_order_pay_session(): void {
		// Establish the cookie before order-pay renders any HTML, even with an empty cart.
		if ( 'yes' === $this->enabled && ! $this->is_redirect_mode() && is_wc_endpoint_url( 'order-pay' )
			&& WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	public function is_redirect_mode(): bool {
		return 'apple' === $this->wallet && 'yes' === $this->get_option( 'redirect', 'no' );
	}

	public function get_field_value( $key, $field, $post_data = [] ) {
		$post_data = empty( $post_data ) ? $_POST : $post_data;
		if ( 'apple' === $this->wallet && isset( $post_data[ $this->get_field_key( 'redirect' ) ] )
			&& in_array( $key, [ 'merchant_id', 'certificate', 'private_key', 'domain_association' ], true ) ) {
			// Disabled inputs are not submitted; keep the native configuration when switching modes.
			return $this->get_option( $key, '' );
		}
		return parent::get_field_value( $key, $field, $post_data );
	}

	public function enqueue_admin_scripts(): void {
		if ( 'woocommerce_page_wc-settings' === ( get_current_screen()->id ?? '' )
			&& 'checkout' === ( $_GET['tab'] ?? '' ) && $this->id === ( $_GET['section'] ?? '' ) ) {
			wp_enqueue_script( 'wppay-wallet-admin', $this->plugin_url . '/assets/js/wallet-admin.js', [], filemtime( dirname( __DIR__, 2 ) . '/assets/js/wallet-admin.js' ), true );
		}
	}

	private function certificate_path( string $key ): string {
		$configured_path = (string) $this->get_option( $key, '' );
		$file = '' !== $configured_path ? realpath( $configured_path ) : false;
		$public = realpath( $_SERVER['DOCUMENT_ROOT'] ?? ABSPATH );
		if ( ! $file || ! is_file( $file ) || ! is_readable( $file )
			|| ( $public && strpos( $file, rtrim( $public, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR ) === 0 ) ) {
			throw new \RuntimeException( __( 'Apple Pay certificates must be readable files outside the public web directory.', 'pay-wp' ) );
		}
		return $file;
	}

	private function is_subscription( \WC_Order $order ): bool {
		return ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order, 'any' ) )
			|| ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order ) );
	}

	private function configured( string $currency ): bool {
		if ( 'yes' !== $this->enabled || ! is_ssl() || ! $this->client_factory->has_credentials( $currency ) ) {
			return false;
		}
		if ( 'apple' === $this->wallet && ! $this->is_redirect_mode() ) {
			if ( ! extension_loaded( 'curl' ) || ! $this->get_option( 'merchant_id', '' ) ) {
				return false;
			}
			$this->certificate_path( 'certificate' );
			$this->certificate_path( 'private_key' );
		}
		return true;
	}

	public function is_available() {
		try {
			if ( ! parent::is_available() || ( function_exists( 'wcs_cart_contains_subscription' ) && wcs_cart_contains_subscription() )
				|| ( function_exists( 'wcs_cart_contains_renewal' ) && wcs_cart_contains_renewal() ) ) {
				return false;
			}
			$order = is_wc_endpoint_url( 'order-pay' ) ? wc_get_order( absint( get_query_var( 'order-pay' ) ) ) : false;
			$total = $order ? $order->get_total() : ( WC()->cart ? WC()->cart->get_total( 'edit' ) : 0 );
			return $total > 0 && ( ! $order || ! $this->is_subscription( $order ) )
				&& $this->configured( $order ? $order->get_currency() : get_woocommerce_currency() );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	public function enqueue_scripts(): void {
		if ( 'yes' !== $this->enabled || ! is_checkout() ) {
			return;
		}
		wp_enqueue_script( 'wppay-wallets', $this->plugin_url . '/assets/js/wallets.js', [ 'jquery', 'wp-i18n' ], filemtime( dirname( __DIR__, 2 ) . '/assets/js/wallets.js' ), true );
		wp_set_script_translations( 'wppay-wallets', 'pay-wp', dirname( __DIR__, 2 ) . '/lang' );
		wp_enqueue_style( 'wppay-wallets', $this->plugin_url . '/assets/css/wallets.css', [], filemtime( dirname( __DIR__, 2 ) . '/assets/css/wallets.css' ) );
	}

	private function create_quote( ?\WC_Order $order = null ): array {
		$currency = $order ? $order->get_currency() : get_woocommerce_currency();
		$client = $this->client_factory->get_client( $currency );
		$total = $order ? $order->get_total() : ( WC()->cart ? WC()->cart->get_total( 'edit' ) : 0 );
		$quote = [
			'amount' => (string) round( (float) $total * 100 ), 'currency' => $currency,
			'service' => $client->get_service_id(), 'sandbox' => $this->sandbox(), 'gateway' => $this->id,
			'order' => $order ? $order->get_id() : 0, 'issued' => time(),
			'reference' => 'wpw' . substr( bin2hex( random_bytes( 16 ) ), 0, 29 ),
		];
		$quote['signature'] = WalletPayment::sign_quote( $quote );
		return $quote;
	}

	public function get_frontend_data( ?\WC_Order $order = null ): array {
		if ( $this->is_redirect_mode() ) {
			return [ 'id' => $this->id, 'wallet' => $this->wallet, 'label' => $this->title, 'redirect' => true ];
		}
		$quote = $this->create_quote( $order );
		$client = $this->client_factory->get_client( $quote['currency'] );
		return [
			'id' => $this->id, 'wallet' => $this->wallet, 'label' => $this->title, 'quote' => $quote,
			'country' => WC()->countries->get_base_country(),
			'merchantName' => wp_html_excerpt( wp_strip_all_tags( get_bloginfo( 'name' ) ), 64, '' ),
			'google' => 'google' === $this->wallet ? $client->get_google_pay_config(
				wp_parse_url( home_url(), PHP_URL_HOST ), trim( (string) $this->get_option( 'merchant_id', '' ) ),
				wp_html_excerpt( wp_strip_all_tags( get_bloginfo( 'name' ) ), 64, '' )
			) : null,
			'nonce' => wp_create_nonce( $this->id ),
			'checkoutNonce' => wp_create_nonce( $order ? 'woocommerce-pay' : 'woocommerce-process_checkout' ),
			'configUrl' => \WC_AJAX::get_endpoint( $this->id . '_config' ),
			'sessionUrl' => \WC_AJAX::get_endpoint( $this->id . '_session' ),
		];
	}

	public function payment_fields() {
		parent::payment_fields();
		if ( $this->is_redirect_mode() ) {
			return;
		}
		try {
			$order = is_wc_endpoint_url( 'order-pay' ) ? wc_get_order( absint( get_query_var( 'order-pay' ) ) ) : null;
			echo '<div class="wppay-wallet" data-config="' . esc_attr( wp_json_encode( $this->get_frontend_data( $order ?: null ) ) ) . '"></div>';
		} catch ( \Exception $e ) {
			// Keep the wallet marker so checkout availability checks can hide it and select another method.
			echo '<div class="wppay-wallet" data-config="{}"><p role="alert">' . esc_html__( 'This payment method is unavailable. Please choose another method.', 'pay-wp' ) . '</p></div>';
		}
	}

	public function ajax_config(): void {
		try {
			$order = null;
			if ( isset( $_POST['wppay_wallet_quote'] ) ) {
				// A session-bound signed quote also authenticates refresh after account creation changes the WP nonce.
				$quote = WalletPayment::read_quote();
				$order = ! empty( $quote['order'] ) ? wc_get_order( $quote['order'] ) : null;
				if ( ! empty( $quote['order'] ) && ( ! $order || ! $order->needs_payment() || $this->is_subscription( $order ) ) ) {
					throw new \RuntimeException( __( 'This order cannot be paid.', 'pay-wp' ) );
				}
				WalletPayment::assert_can_start( [ 'OrderID' => $quote['reference'] ] );
			} else {
				check_ajax_referer( $this->id, 'nonce' );
			}
			if ( 'yes' !== $this->enabled || ( $order ? ! $this->configured( $order->get_currency() ) : ( ! $this->is_available() || ! WC()->cart || WC()->cart->is_empty() ) ) ) {
				throw new \RuntimeException( __( 'This payment method is unavailable. Please choose another method.', 'pay-wp' ) );
			}
			if ( ! $order ) {
				WC()->cart->calculate_totals();
			}
			if ( isset( $quote ) ) {
				$this->validate_quote( $quote, $order ? $order->get_currency() : get_woocommerce_currency(), $order ? $order->get_total() : WC()->cart->get_total( 'edit' ), $order ? $order->get_id() : 0 );
			}
			wp_send_json_success( $this->get_frontend_data( $order ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
		}
	}

	private function validate_quote( array $quote, string $currency, $total, int $order_id = 0 ): void {
		if ( ( $quote['gateway'] ?? '' ) !== $this->id || ( $quote['currency'] ?? '' ) !== $currency
			|| ( $quote['amount'] ?? '' ) !== (string) round( (float) $total * 100 )
			|| ( $quote['service'] ?? '' ) !== $this->client_factory->get_client( $currency )->get_service_id()
			|| ( $quote['sandbox'] ?? null ) !== $this->sandbox()
			|| ( ! empty( $quote['order'] ) && $quote['order'] !== $order_id ) ) {
			throw new \RuntimeException( __( 'The order total or currency has changed. Please refresh the checkout and authorize the payment again.', 'pay-wp' ) );
		}
	}

	public function ajax_session(): void {
		check_ajax_referer( $this->id, 'nonce' );
		try {
			$quote = WalletPayment::read_quote();
			if ( 'apple' !== $this->wallet || $this->is_redirect_mode() || ! $this->configured( $quote['currency'] ?? '' ) || ( $quote['gateway'] ?? '' ) !== $this->id
				|| $quote['sandbox'] !== $this->sandbox() || $quote['service'] !== $this->client_factory->get_client( $quote['currency'] )->get_service_id() ) {
				throw new \RuntimeException( __( 'Apple Pay is unavailable.', 'pay-wp' ) );
			}
			$limit = 'wppay_apple_session_' . md5( \WC_Geolocation::get_ip_address() );
			if ( get_transient( $limit ) ) {
				throw new \RuntimeException( __( 'Please wait a moment before trying again.', 'pay-wp' ) );
			}
			set_transient( $limit, 1, 3 );
			$url = $_POST['validation_url'] ?? '';
			// Only Apple's HTTPS validation endpoints; never send the client certificate to arbitrary URLs.
			if ( ! is_string( $url ) || ! preg_match( '~^https://(?:apple-pay-gateway(?:-cert|-nc-pod[0-9]+|-pr-pod[0-9]+)?|cn-apple-pay-gateway)\.apple\.com/paymentservices/(?:startSession|paymentSession)$~D', $url ) ) {
				throw new \RuntimeException( __( 'Invalid Apple Pay validation URL.', 'pay-wp' ) );
			}
			$curl = curl_init( $url );
			curl_setopt_array( $curl, [
				CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
				CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSLCERT => $this->certificate_path( 'certificate' ),
				CURLOPT_SSLKEY => $this->certificate_path( 'private_key' ), CURLOPT_HTTPHEADER => [ 'Content-Type: application/json' ],
				CURLOPT_POSTFIELDS => wp_json_encode( [
					'merchantIdentifier' => (string) $this->get_option( 'merchant_id', '' ), 'initiative' => 'web',
					'initiativeContext' => wp_parse_url( home_url(), PHP_URL_HOST ),
					'displayName' => wp_html_excerpt( wp_strip_all_tags( get_bloginfo( 'name' ) ), 64, '' ),
				] ),
			] );
			$response = curl_exec( $curl );
			$code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			curl_close( $curl );
			$session = is_string( $response ) ? json_decode( $response, true ) : null;
			if ( 200 !== $code || ! is_array( $session ) || empty( $session['merchantSessionIdentifier'] ) || empty( $session['signature'] ) ) {
				throw new \RuntimeException( __( 'Apple Pay could not verify this shop. Please choose another payment method.', 'pay-wp' ) );
			}
			wp_send_json_success( $session );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
		}
	}

	public function process_payment( $order_id ): array {
		$locked_reference = null;
		$submitted = false;
		try {
			$order = wc_get_order( $order_id );
			if ( ! $order || ! $this->configured( $order->get_currency() ) || $this->is_subscription( $order ) ) {
				throw new \RuntimeException( __( 'This payment method is unavailable. Please choose another method.', 'pay-wp' ) );
			}
			if ( $order->is_paid() ) {
				return [ 'result' => 'success', 'redirect' => $this->get_return_url( $order ) ];
			}
			if ( ! $order->needs_payment() || $order->get_total() <= 0 ) {
				throw new \RuntimeException( __( 'This order cannot be paid.', 'pay-wp' ) );
			}
			$redirect_mode = $this->is_redirect_mode();
			$quote = $redirect_mode ? $this->create_quote( $order ) : WalletPayment::read_quote();
			$this->validate_quote( $quote, $order->get_currency(), $order->get_total(), $order->get_id() );
			$token = $redirect_mode ? null : WalletPayment::payment_token( $this->wallet, $quote );
			$client = $this->client_factory->get_client( $order->get_currency() );
			$reference = $quote['reference'];
			// ponytail: one atomic option per pending order; keep unknown results blocked until a verified ITN.
			if ( ! add_option( 'wppay_wallet_pending_' . $order_id, $reference, '', false ) ) {
				throw new \RuntimeException( __( 'The previous payment is awaiting confirmation. Please contact the shop before trying again.', 'pay-wp' ) );
			}
			$locked_reference = $reference;
			$order = wc_get_order( $order_id );
			if ( $order->is_paid() || ! $order->needs_payment() ) {
				WalletPayment::finish_attempt( $order->get_id(), $reference );
				throw new \RuntimeException( __( 'This order cannot be paid.', 'pay-wp' ) );
			}
			$this->validate_quote( $quote, $order->get_currency(), $order->get_total(), $order->get_id() );
			$attempt = [ 'order' => $order->get_id(), 'service' => $client->get_service_id(), 'currency' => $order->get_currency(), 'amount' => number_format( (float) $order->get_total(), 2, '.', '' ) ];
			if ( ! add_option( 'wppay_wallet_reference_' . $reference, $attempt, '', false ) ) {
				WalletPayment::finish_attempt( $order->get_id(), $reference );
				throw new \RuntimeException( __( 'Please refresh the checkout and authorize the payment again.', 'pay-wp' ) );
			}
			$order->add_order_note( sprintf( __( 'Autopay wallet payment reference: %s.', 'pay-wp' ), $reference ) );
			$order->add_meta_data( '_wppay_wallet_reference', $reference );
			$order->save();
			$submitted = true;
			$params = [
				'ServiceID' => $client->get_service_id(), 'OrderID' => $reference, 'Amount' => $attempt['amount'],
				'Description' => apply_filters( 'wp_pay\payment\description', __( 'Autopay transaction', 'pay-wp' ), $order, $this ),
				'GatewayID' => 'apple' === $this->wallet ? '1513' : '1512', 'Currency' => $order->get_currency(),
				'CustomerEmail' => $order->get_billing_email(), 'CustomerIP' => \WC_Geolocation::get_ip_address(),
			];
			if ( ! $redirect_mode ) {
				$params['PaymentToken'] = $token;
			}
			$result = $client->init_wallet_transaction( $params );
			if ( ( $result['orderID'] ?? '' ) !== $reference ) {
				throw new \RuntimeException( __( 'The payment status is unknown. Please contact the shop before trying again.', 'pay-wp' ) );
			}
			if ( 'NOTCONFIRMED' === ( $result['confirmation'] ?? '' ) || 'FAILURE' === ( $result['paymentStatus'] ?? '' ) ) {
				WalletPayment::finish_attempt( $order->get_id(), $reference );
				throw new \RuntimeException( __( 'Payment failed. Please authorize the payment again.', 'pay-wp' ) );
			}
			$redirect = $result['redirecturl'] ?? $this->get_return_url( $order );
			if ( $redirect_mode && empty( $result['redirecturl'] ) ) {
				throw new \RuntimeException( __( 'The payment status is unknown. Please contact the shop before trying again.', 'pay-wp' ) );
			}
			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}
			return [ 'result' => 'success', 'redirect' => $redirect ];
		} catch ( \Exception $e ) {
			if ( $locked_reference && ! $submitted ) {
				WalletPayment::finish_attempt( (int) $order_id, $locked_reference );
			}
			wc_add_notice( $e->getMessage(), 'error' );
			return [ 'result' => 'failure', 'message' => $e->getMessage() ];
		}
	}

	private function is_order_pay_request(): bool {
		return isset( $_POST['wppay_wallet_ajax'], $_POST['payment_method'] ) && $this->id === $_POST['payment_method'];
	}

	public function validate_order_pay_request(): void {
		if ( ! $this->is_order_pay_request() ) {
			return;
		}
		$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
		$key = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? wp_unslash( $_GET['key'] ) : '';
		if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || ! current_user_can( 'pay_for_order', $order->get_id() ) ) {
			wp_send_json( [ 'result' => 'failure', 'message' => __( 'You cannot pay for this order.', 'pay-wp' ) ], 403 );
		}
	}

	public function order_pay_result( $result, $order_id ) {
		if ( $this->is_order_pay_request() ) {
			wp_send_json( $result );
		}
		return $result;
	}

	public function order_pay_error(): void {
		if ( $this->is_order_pay_request() ) {
			wp_send_json( [ 'result' => 'failure', 'messages' => wc_print_notices( true ) ] );
		}
	}

	public function serve_domain_association(): void {
		$request = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
		if ( $request !== wp_parse_url( home_url( '/.well-known/apple-developer-merchantid-domain-association' ), PHP_URL_PATH ) ) {
			return;
		}
		$content = (string) $this->get_option( 'domain_association', '' );
		status_header( '' !== $content ? 200 : 404 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $content;
		exit;
	}

	public function generate_apple_domain_association_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$uploaded  = '' !== $this->get_option( $key, '' );
		$url       = home_url( '/.well-known/apple-developer-merchantid-domain-association' );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<input type="file" class="wppay-apple-domain-association" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>">
				<p class="description"><?php echo esc_html( $data['description'] ); ?></p>
				<?php if ( $uploaded ) : ?>
					<p class="description">
						<?php
						echo wp_kses_post(
							sprintf(
								__( 'The file is available at: %s', 'pay-wp' ),
								'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a>'
							)
						);
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	public function validate_apple_domain_association_field( $key, $value ) {
		$field_key = $this->get_field_key( $key );
		$upload    = $_FILES[ $field_key ] ?? null;

		if ( ! is_array( $upload ) || UPLOAD_ERR_NO_FILE === ( $upload['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			return $this->get_option( $key, '' );
		}
		if ( UPLOAD_ERR_OK !== ( $upload['error'] ?? null ) || ! is_uploaded_file( $upload['tmp_name'] ?? '' ) ) {
			\WC_Admin_Settings::add_error( __( 'The Apple Pay domain association file could not be uploaded.', 'pay-wp' ) );
			return $this->get_option( $key, '' );
		}
		if ( empty( $upload['size'] ) || $upload['size'] > 102400 ) {
			\WC_Admin_Settings::add_error( __( 'The Apple Pay domain association file must not be empty or larger than 100 KB.', 'pay-wp' ) );
			return $this->get_option( $key, '' );
		}

		$content = file_get_contents( $upload['tmp_name'] );
		if ( false === $content || false !== strpos( $content, "\0" ) ) {
			\WC_Admin_Settings::add_error( __( 'The Apple Pay domain association file is invalid.', 'pay-wp' ) );
			return $this->get_option( $key, '' );
		}

		\WC_Admin_Settings::add_message( __( 'The Apple Pay domain association file has been uploaded.', 'pay-wp' ) );
		return $content;
	}

}
