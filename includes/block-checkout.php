<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_TazaPay_Blocks_Support extends AbstractPaymentMethodType
{
  private $gateway;

  protected $name = 'tazapay';

  public function initialize()
  {
    $this->settings = get_option('woocommerce_tazapay_settings', []);
    $payment_gateways_class = WC()->payment_gateways();
    $payment_gateways = $payment_gateways_class->payment_gateways();

    $this->gateway = $payment_gateways['tazapay'];
  }

  public function is_active()
  {
    return $this->gateway->is_available();
  }

  public function get_payment_method_script_handles()
  {
    wp_register_script(
      'wc-tazapay-blocks-integration',
      TAZAPAY_PLUGIN_URL . 'block/checkout.js',
      [
        'wc-blocks-registry',
        'wc-settings',
        'wp-element',
        'wp-html-entities',
        'wp-i18n',
      ],
      null,
      true
    );
    if (function_exists('wp_set_script_translations')) {
      wp_set_script_translations('wc-tazapay-blocks-integration', 'tazapay', TAZAPAY_PLUGIN_PATH . 'languages/');

    }
    return ['wc-tazapay-blocks-integration'];
  }

  public function get_payment_method_data()
  {
    return [
      'title' => $this->gateway->title,
      'description' => $this->gateway->description,
    ];
  }
}