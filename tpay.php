<?php

/*
 * Plugin Name:       Tazapay Checkout Payment Gateway
 * Plugin URI:        https://wordpress.org/plugins/tazapay
 * Description:       Pay securely with buyer protection.
 * Version:           3.0
 * Author:            Tazapay
 * Author URI:        https://wordpress.org/plugins/tazapay
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-tp-payment-gateway
 */

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


// htdocs/wordpress/wp-content/plugins
// htdocs/wordpress/wp-includes/functions.php

/* Deactivate */
function tzp_deactivate(){

    // wp_clear_scheduled_hook( 'my_hourly_event' );

    /*
    * This action hook unregisters our PHP class as a WooCommerce payment gateway
    */
    remove_filter( 'woocommerce_payment_gateways', 'tzp_add_gateway_class' );
    remove_action( 'plugins_loaded', 'tzp_init_gateway_class' );
}

register_deactivation_hook( __FILE__, 'tzp_deactivate');

function tzp_get_plugin_info(){
    return get_plugin_data(__FILE__);
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'tzp_init_gateway_class' );
function tzp_init_gateway_class() {

    include 'includes/tazapay-apis.php';
    include 'includes/tazapay-methods.php';
    include 'includes/class-wc-tpay.php';

    #TODO: Use alternate approach for below functionality
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
add_filter( 'woocommerce_payment_gateways', 'tzp_add_gateway_class' );
function tzp_add_gateway_class( $gateways ) {
	$gateways[] = 'TPAY_Gateway'; // your class name is here
	return $gateways;
}


/*
* Plugin settings page
*/

add_filter("plugin_action_links_$plugin", 'tzp_plugin_settings_link');
function tzp_plugin_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=tz_tazapay">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}