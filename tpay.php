<?php

/*
 * Plugin Name:       Tazapay Checkout Payment Gateway
 * Plugin URI:        https://wordpress.org/plugins/tazapay
 * Description:       Pay securely with buyer protection.
 * Version:           3.0.2
 * Author:            Tazapay
 * Author URI:        https://wordpress.org/plugins/tazapay
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-tp-payment-gateway
 */

define('TAZAPAY_VERSION', '3.0.2');
define('TAZAPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TAZAPAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

define('TZP_CSS_JSS_VERISON', time());
define('TZP_PUBLIC_ASSETS_DIR', plugins_url('assets/', __FILE__));
// constants
define('PAID', 'paid');
define('SUCCEEDED', 'succeeded');
define('PENDING', 'pending');
define('ON_HOLD', 'on-hold');
define('FAILED', 'failed');
define('REQUIRES_ACTION', 'requires_action');
define('CHECKOUT_PAID', 'checkout.paid');
define('REFUND_SUCCEEDED', 'refund.succeeded');
define('REFUND_FAILED', 'refund.failed');
define('REFUND_PENDING', 'refund.pending');
define('COMPLETED', 'completed');
define('PROCESSING', 'processing');
define('APPROVED', 'approved');

$plugin = plugin_basename(__FILE__);


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function declare_cart_checkout_blocks_compatibility()
{
  // Check if the required class exists
  if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
    // Declare compatibility for 'cart_checkout_blocks'
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
  }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// htdocs/wordpress/wp-content/plugins
// htdocs/wordpress/wp-includes/functions.php

/* Deactivate */
function tzp_deactivate()
{

  // wp_clear_scheduled_hook( 'my_hourly_event' );

  /*
   * This action hook unregisters our PHP class as a WooCommerce payment gateway
   */
  remove_filter('woocommerce_payment_gateways', 'tzp_add_gateway_class');
  remove_action('plugins_loaded', 'tzp_init_gateway_class');
}

register_deactivation_hook(__FILE__, 'tzp_deactivate');

function tzp_get_plugin_info()
{
  return get_plugin_data(__FILE__);
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'tzp_init_gateway_class');
function tzp_init_gateway_class()
{
  if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . '/wp-admin/includes/plugin.php';
  }
  if (!is_plugin_active('woocommerce/woocommerce.php')) {
    return;
  }

  require_once TAZAPAY_PLUGIN_PATH . 'includes/tazapay-apis.php';
  require_once TAZAPAY_PLUGIN_PATH . 'includes/tazapay-methods.php';
  require_once TAZAPAY_PLUGIN_PATH . 'includes/class-wc-tpay.php';

}

// Frontend css and js

add_action('wp_enqueue_scripts', 'tzp_frontend_enqueue_styles');
function tzp_frontend_enqueue_styles()
{
  wp_enqueue_style('tazapay-frontend-css', TZP_PUBLIC_ASSETS_DIR . 'css/tazapay-frontend.css', array(), TZP_CSS_JSS_VERISON, 'all');
  wp_enqueue_script('tazapay-admin', TZP_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), TZP_CSS_JSS_VERISON, true);
}

// Backend css and js

add_action('admin_enqueue_scripts', 'tzp_enqueue_styles');
function tzp_enqueue_styles()
{
  wp_enqueue_style('tazapay-frontend-css', TZP_PUBLIC_ASSETS_DIR . 'css/tazapay-frontend.css', array(), TZP_CSS_JSS_VERISON, 'all');
  wp_enqueue_script('tazapay-admin', TZP_PUBLIC_ASSETS_DIR . 'js/tazapay-form.js', array('jquery'), TZP_CSS_JSS_VERISON, true);
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'tzp_add_gateway_class');
function tzp_add_gateway_class($gateways)
{
  $gateways[] = 'TPAY_Gateway';
  return $gateways;
}

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'woocommerce_tazapay_blocks_support');
/**
 * Custom function to register a payment method type
 */
function woocommerce_tazapay_blocks_support()
{
  // Check if the required class exists
  if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
    return;
  }
  // Include the custom Blocks Checkout class
  require_once plugin_dir_path(__FILE__) . 'includes/block-checkout.php';
  // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
  add_action(
    'woocommerce_blocks_payment_method_type_registration',
    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
      // Register an instance of WC_TazaPay_Blocks_Support
      $payment_method_registry->register(new WC_TazaPay_Blocks_Support);
    }
  );
}


/*
 * Plugin settings page
 */

add_filter("plugin_action_links_$plugin", 'tzp_plugin_settings_link');
function tzp_plugin_settings_link($links)
{
  $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=tazapay">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}