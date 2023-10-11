<?php

function tzp_getOrderKey($order){
    $orderKey = null;

    if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>='))
    {
        return $order->get_order_key();
    }

    return $order->order_key;
}


function tzp_getCheckoutUrl($order_id){
    global $woocommerce;

    if ( version_compare( WOOCOMMERCE_VERSION, '2.5.2', '>=' ) ) {
        return wc_get_cart_url();
    } else {
        return $woocommerce->cart->get_cart_url();
    }
}

function tzp_getPaymentUrl($order_id){
    global $woocommerce;

    $order = wc_get_order($order_id);

    $orderKey = tzp_getOrderKey($order);

    if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>='))
    {
        return array(
            'result' => 'success',
            'redirect' => add_query_arg('key', $orderKey, $order->get_checkout_payment_url(true))
        );
    }
    else if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>='))
    {
        return array(
            'result' => 'success',
            'redirect' => add_query_arg('order', $order->get_id(),
                add_query_arg('key', $orderKey, $order->get_checkout_payment_url(true)))
        );
    }
    else
    {
        return array(
            'result' => 'success',
            'redirect' => add_query_arg('order', $order->get_id(),
                add_query_arg('key', $orderKey, get_permalink(get_option('woocommerce_pay_page_id'))))
        );
    }
}

function tzp_process_getCheckoutResponse($response, $order_id){

    $order = wc_get_order($order_id);
    $payment_status = $response['data']['payment_status'];
    $event_type = $response['type'];

    $orderStatus = $order->get_status();

    if($payment_status  == PAID && $event_type == CHECKOUT_PAID){
      $order->add_meta_data('payment_done', 1);
      $settings = tzp_getAdminAPISettings();
      $targetStatus = $settings['targetStatus'];

      $order->update_status($targetStatus, __('TZ Payment completed.'));
      $order->reduce_order_stock();

      return SUCCEEDED;
    } else if($payment_status == REQUIRES_ACTION && ON_HOLD != $orderStatus){
      $order->update_status(ON_HOLD,'TZ Payment reported.');
          
      return PROCESSING;
    } else if($payment_status == FAILED){
      $order->update_status(FAILED, __('TZ Payment failed.'));

      return FAILED;
    }
}

function tzp_process_getRefundResponse($response,$order_id){

    $order = wc_get_order($orderId);
    $payment_status = $response['data']['status'];
    $event_type = $response['type'];

    if($payment_status == SUCCEEDED && $event_type == REFUND_SUCCEEDED){
      $order->update_status('refunded','TZ Payment refunded.');

      return APPROVED;
    } else if($payment_status == FAILED && $event_type == REFUND_FAILED){
      $order->add_order_note('TZ Refund failed.');

      return FAILED;
    } else if($payment_status == PENDING && $event_type == REFUND_PENDING){
      $order->add_order_note('TZ Refund initiated.');

      return PENDING;
    } else {
      return 'error';
    }
}