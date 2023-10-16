<?php


function tzp_webhook_payment_status_change(){
  http_response_code(200);
	$response = json_decode(file_get_contents("php://input"), true);

  if (!isset($_GET['order_id'])) {
      exit;
  }

  $order_id = (int)sanitize_text_field($_GET['order_id']);
  $order = wc_get_order($order_id);
  $paymentMethod = $order->get_payment_method();

  if ($paymentMethod == 'tz_tazapay') {

    if(is_null($response)){
      exit;    
    }

    $payment_status = $response['data']['payment_status'];
    $orderStatus = $order->get_status();
    $order->add_order_note('Current status is ' . $orderStatus . '. TZ Payment Webhook received with ' . $payment_status);
    $payment_done = (int)get_post_meta( $order_id, 'payment_done', true);

    if($payment_done == 1){
      exit;    
    }

    $state = tzp_process_getCheckoutResponse($response, $order_id);

    switch ($state) {
      case SUCCEEDED:
        return tzp_thankyou_page($order_id);
        break;
      case PROCESSING:
        return tzp_thankyou_page($order_id);
        break;  
      case FAILED:
        return tzp_thankyou_page($order_id);
        break;                                      
      default:
        return tzp_thankyou_page($order_id);
        break;
    }
  }
}

function tzp_webhook_refund_status_change(){
  http_response_code(200);
	$response = json_decode(file_get_contents("php://input"), true);

  if (!isset($_GET['order_id'])) {
      exit;
  }

  $order_id = (int)sanitize_text_field($_GET['order_id']);
  $order = wc_get_order($order_id);
  $paymentMethod = $order->get_payment_method();

  if ($paymentMethod == 'tz_tazapay') {

    if( is_null($response) ){
        exit;    
    }

    $payment_status = $response['data']['status'];
    $orderStatus = $order->get_status();
    $order->add_order_note('Current status is ' . $orderStatus . '. TZ Refund Webhook received with ' . $payment_status);

    if($orderStatus == PROCESSING || $orderStatus == COMPLETED){

      $state = tzp_process_getRefundResponse($response,$order_id);

      switch ($state) {
        case APPROVED:
            # code...
            break;
        case FAILED:
            # code...
            break;
        case PENDING:
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