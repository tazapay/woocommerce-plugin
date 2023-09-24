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


function tzp_process_getCheckoutResponse($response){

    $order = wc_get_order((int)$response['partner_reference_id']);
    $orderStatus = $order->get_status();
    $state = $response['state'];
    $sub_state = $response['sub_state'];
    
    if(PAYMENT_RECEIVED == $state){
        $settings = tzp_getAdminAPISettings();
        $targetStatus = $settings['targetStatus'];

        if( PENDING == $orderStatus){

            $order->update_status($targetStatus, __('TZ Payment completed.'));
            $order->reduce_order_stock();

        } else if( ON_HOLD == $orderStatus ) {
          $order->update_status($targetStatus, __('TZ Payment verified.'));
            $order->reduce_order_stock();
            
        /* } else if ( 'cancelled' == $orderStatus) {

            TODO: Implement auto refund?
        */
        } else {

            // Seems like order already processed
            // skip processing for other orderstatuses
        }

        return 'success';
    } else if( AWAITING_PAYMENT ==  $state ){

        if( PAYMENT_FAILED ==  $sub_state ){

            if( PENDING == $orderStatus || ON_HOLD == $orderStatus){
                // TODO: mark fail or retry? => Retry
                $order->add_order_note('TZ Payment attempt failed.');

                // $order->update_status('failed','Payment failed')
            } else {
                // order status and payment status mismatch
            }
            return 'failed';
        } else if( PAYMENT_REPORTED ==  $sub_state || UNDER_REVIEW == $sub_state){

            if( PENDING == $orderStatus ){
                $order->update_status(ON_HOLD,'Payment reported');
                $order->add_order_note('TZ Payment reported.');

            } else {
                // order status and payment status mismatch
            }
            return 'processing';
        } else {

            // Seems like order already processed
            // skip processing for other statuses
            return PENDING;
        }
    } else {

        // skip processing as order status should be pending already
        if( PENDING == $orderStatus ){

            // payment yet to be made
        } else {

            // order status and payment status mismatch
        }

        return PENDING;
    }

}

function tzp_process_getRefundResponse($response,$order_id){

    $order = wc_get_order((int)$order_id);
    $orderStatus = $order->get_status();

    $settings = tzp_getAdminAPISettings();
    $targetStatus = $settings['targetStatus'];

    if( APPROVED == $response->status ){

        if( PROCESSING == $orderStatus || COMPLETED == $orderStatus ){

            // Alternate // $order->set_status('');
            $order->update_status('refunded','Payment refunded');
            $order->add_order_note('TZ Refund approved.');

        } else {

            // Seems like order already processed
            // skip processing for other orderstatuses
        }

        return 'approved';
    } else if( REJECTED == $response->status ){

        $order->add_order_note('TZ Refund rejected.');
        $order->update_status($targetStatus, __('Refund request rejected'));

        return 'rejected';
    } else if( REFUND_INITIATED ==  $response->status){

        $order->add_order_note('TZ Refund initiated.');
        return PENDING;
    } else if( UNDER_REVIEW == $response->status ){

        $order->add_order_note('TZ Refund under review.');
        return PENDING;
    } else {

        return 'error';
    }

}

/*
// Not used anymore
public function mark_order_complete($order_id){

    $order = wc_get_order( $order_id );

    $order->payment_complete();
    $order->reduce_order_stock();
}

public function process_order_completion($order_id){

    $order = wc_get_order( $order_id );

    // verify the payment status for the current order
    if( $order->get_status() != 'Processing' ){

        // if not updated then we need call get checkout session API to get the latest payment status

        $this->mark_order_complete($order_id);
    }

    $order = wc_get_order( $order_id );

    // now check again
    if( $order->get_status() === 'Processing' ){
        
        // WC()->session->set( 'token' , '' );
        // WC()->session->set( 'complete_url' , '' );
        WC()->session->__unset('token')
        WC()->session->__unset('complete_url')
        WC()->session->__unset('abort_url')
        wc_clear_notices();
        wc_add_notice('Order placed', 'success');
    } else {

        // if txn is not succefull then show error
        wc_add_notice('Failed to validate the payment', 'error');
        wp_redirect( $order->get_view_order_url() );
    }
}
*/