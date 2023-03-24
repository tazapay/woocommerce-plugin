<?php


function tzp_webhook_payment_status_change(){

    if (!isset($_GET['order_id'])) {
        // create_taza_logs('return_from_tazapay order_id check failed');
        exit;
    }

    $order_id = (int)sanitize_text_field($_GET['order_id']);
    $order = wc_get_order($order_id);
    $paymentMethod = $order->get_payment_method();

    if ($paymentMethod == 'tz_tazapay') {

        $orderStatus = $order->get_status();
        $order->add_order_note('TZ Payment Webhook received', true);

        if( 'pending' == $orderStatus || 'on-hold' == $orderStatus){

            $response = tzp_get_checkout_api($order_id);

            if( is_null($response) ){
                exit;    
            }

            $state = tzp_process_getCheckoutResponse($response);

            // TODO: respond to webhook request
            // so that if required the sender can retry later
            // or mark webhook request as successfully posted and processed

            switch ($state) {
                case 'success':
                    # code...
                    break;
                case 'failed':
                    # code...
                    break;
                case 'processing':
                    # code...
                    break;
                case 'pending':
                    # code...
                    break;                                        
                default:
                    # error handler
                    # code...
                    break;
            }
        }

    }
}

function tzp_webhook_refund_status_change(){

    if (!isset($_GET['order_id'])) {
        // create_taza_logs('return_from_tazapay order_id check failed');
        exit;
    }

    $order_id = (int)sanitize_text_field($_GET['order_id']);
    $order = wc_get_order($order_id);
    $paymentMethod = $order->get_payment_method();

    if ($paymentMethod == 'tz_tazapay') {

        $order->add_order_note('TZ Refund Webhook received', true);

        $orderStatus = $order->get_status();

        if( 'processing' == $orderStatus || 'complete' == $orderStatus || 'refunded' == $orderStatus ){

            $response = tzp_get_refund_api($order_id);

            if( is_null($response) ){
                exit;    
            }

            $state = tzp_process_getRefundResponse($response,$order_id);

            // TODO: respond to webhook request
            // so that if required the sender can retry later
            // or mark webhook request as successfully posted and processed

            switch ($state) {
                case 'approved':
                    # code...
                    break;
                case 'rejected':
                    # code...
                    break;
                case 'pending':
                    # code...
                    break;
                default:
                    # error handler
                    # code...
                    break;
            }
        }
    }
}