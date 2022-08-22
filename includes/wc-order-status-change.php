<?php
/*
 * Get escrow status by txn_no
 */
function tcpg_tcpg_request_api_orderstatus($txn_no)
{

  $woocommerce_tz_tazapay_settings = get_option('woocommerce_tz_tazapay_settings');

  $method = "GET";
  $APIEndpoint = "/v1/escrow/" . $txn_no;
  $apiKey     = $woocommerce_tz_tazapay_settings['sandboxmode'] ? esc_html($woocommerce_tz_tazapay_settings['sandbox_api_key']) : esc_html($woocommerce_tz_tazapay_settings['live_api_key']);
  $apiSecret  = $woocommerce_tz_tazapay_settings['sandboxmode'] ? esc_html($woocommerce_tz_tazapay_settings['sandbox_api_secret_key']) : esc_html($woocommerce_tz_tazapay_settings['live_api_secret_key']);

  if ($woocommerce_tz_tazapay_settings['sandboxmode'] == 'sandbox') {
    $api_url = 'https://api-sandbox.tazapay.com';
  } else {
    $api_url = 'https://api.tazapay.com';
  }

  $basic_auth = $apiKey . ':' . $apiSecret;

  $authentication = "Basic " . base64_encode($basic_auth);

  $response = wp_remote_post(
    esc_url_raw( $api_url ) . $APIEndpoint,
    array(
      'method'      => 'GET',
      'sslverify'   => false,
      'headers'     => array(
				'Authorization' => $authentication,
        'Content-Type' => 'application/json'
      )
    )
  );
  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    esc_html_e('Something went wrong: ' . $error_message, 'wc-tp-payment-gateway');
  } else {
    $api_array = json_decode(wp_remote_retrieve_body($response));
  }

  return $api_array;
}

if (!wp_next_scheduled('tcpg_order_hook')) {
  wp_schedule_event(strtotime('12:00:00'), 'daily', 'tcpg_order_hook');
}

add_action('tcpg_order_hook', 'tcpg_order_change', 10, 0);
function tcpg_order_change()
{
  global $wpdb;
  $orderList = $wpdb->get_results("SELECT pm.post_id AS order_id FROM {$wpdb->prefix}postmeta AS pm LEFT JOIN {$wpdb->prefix}posts AS p ON pm.post_id = p.ID WHERE p.post_type = 'shop_order' AND p.post_status = 'wc-on-hold' AND pm.meta_key = '_payment_method'");

  foreach ($orderList as $orderPost) {

    $order           = new WC_Order($orderPost->order_id);
    $paymentMethod   = get_post_meta($orderPost->order_id, '_payment_method', true);
    $txn_no          = get_post_meta($orderPost->order_id, 'txn_no', true);
    $getEscrowstate  = tcpg_tcpg_request_api_orderstatus($txn_no);

    if ($getEscrowstate->status == 'success' && $paymentMethod == 'tz_tazapay' && ($getEscrowstate->data->state == 'Payment_Recieved' || $getEscrowstate->data->sub_state == 'Payment_Done')) {
      $order->update_status('processing');
    }
    if ($getEscrowstate->status == 'success' && $paymentMethod == 'tz_tazapay' && $getEscrowstate->data->sub_state == 'Payment_Failed') {
      $order->update_status('cancelled');
    }
  }
}
