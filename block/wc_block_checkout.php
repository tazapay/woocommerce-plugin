<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;

final class WC_Tazapay_Blocks extends AbstractPaymentMethodType {

  private $gateway;

	protected $name = 'tz_tazapay';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_tz_tazapay_settings', [] );
    $payment_gateways_class   = WC()->payment_gateways();
    $payment_gateways         = $payment_gateways_class->payment_gateways();
    $this->gateway = $payment_gateways['tz_tazapay'];

	}

	public function is_active() {
    return $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-tzp-blocks-integration',
			plugin_dir_url(__FILE__) . '../assets/js/tazapay-block.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			false,
			true
		);
		if( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-tzp-blocks-integration');
		}
		return [ 'wc-tzp-blocks-integration' ];
	}

  
  public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting('title'),
      'description' => $this->get_setting('description'),
		];
	}

}
?>