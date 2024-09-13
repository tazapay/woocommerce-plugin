<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Tazapay_Blocks extends AbstractPaymentMethodType {

	protected $name = 'mygateway';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_tz_tazapay_settings', [] );
	}

	public function is_active() {
		return $this->get_setting( 'enabled' ) === 'yes';
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

}
?>