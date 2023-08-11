<?php

function tzp_thankyou_page($order_id){
    global $woocommerce;

    $order = wc_get_order($order_id);
    $paymentMethod = $order->get_payment_method();
    
    if ($paymentMethod == 'tz_tazapay') {

        /*
        TODO:

        get order payment status
        if(payment_status = 'succ')
            stay on thank you page
            clear session data
        else if(payment_status = 'failed')
            navigate to checkout page(with pyment failure notice) for a retry
            clear session data
        else if(payment_status = 'pending')
            call getStatus API
            process result
        */
        // wc_clear_notices();

        $orderStatus = $order->get_status();
        
        if( 'processing' == $orderStatus || 'completed' == $orderStatus ){
            WC()->session->__unset('token');
            WC()->session->__unset('complete_url');
            WC()->session->__unset('abort_url');

            wc_add_notice('Payment verified.', 'success');
            tzp_TxnDetailsTable($order_id);

            // wc_add_notice('Order placed - '.$paymentMethod, 'success');

        } else if( 'failed' == $orderStatus ){
            WC()->session->__unset('token');
            WC()->session->__unset('complete_url');
            WC()->session->__unset('abort_url');

            wc_add_notice('Payment failed', 'error');
            // TODO: test if checkout url present for cleared cart?
            wp_redirect( wc_get_checkout_url() );
            exit;
        }
    }
}
