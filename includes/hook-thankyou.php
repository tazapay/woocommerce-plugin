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
        } else if( 'pending' == $orderStatus || 'on-hold' == $orderStatus ){

            /*

            #process result#

            if(payment_status = 'succ'){
                order>markComplete
            }
            else if(payment_status = 'failed')
                // navigate to checkout page(with pyment failure notice) for a retry
            else if(payment_status = 'pending')
            */
           
            $response = tzp_get_checkout_api($order_id);

            if( is_null($response) ){
                wp_redirect( wc_get_checkout_url() );
                wc_add_notice('Payment validation failed', 'error');
                exit;    
            }

            $state = tzp_process_getCheckoutResponse($response);

            if( 'success' == $state || 'fail' == $state ){

                return tzp_thankyou_page($order_id);
            } else {

                // Reload order details and order status
                $order = wc_get_order($order_id);
                $orderStatus = $order->get_status();

                if( 'pending' == $orderStatus ){

                    // polling?
                    // TODO: navigate to order-pay noload-sdk

                    wc_add_notice('Payment not made', 'info');
                    wp_redirect( $this->getPaymentUrl($order) );
                    exit;
                } else if( 'on-hold' == $orderStatus ){

                    wc_add_notice('Payment not verified', 'info');
                    wp_redirect( $order->get_view_order_url() );
                    exit;
                } else {

                    wc_add_notice('Payment verification error', 'error');
                    wp_redirect( $order->get_view_order_url() );
                    exit;
                }
            }
        }
    }
}
