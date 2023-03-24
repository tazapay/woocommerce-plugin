<?php

function tzp_getAdminAPISettings(){
    $woocommerce_tz_tazapay_settings = get_option('woocommerce_tz_tazapay_settings');

    $envmode = $woocommerce_tz_tazapay_settings['select_env_mode'];
    $apiKey = $woocommerce_tz_tazapay_settings['prod_api_key'];
    $secretKey = $woocommerce_tz_tazapay_settings['prod_secret_key'];
    $baseApiUrl = 'https://api.tazapay.com';

    if($envmode === 'Sandbox'){
      $apiKey = $woocommerce_tz_tazapay_settings['sandbox_api_key'];
      $secretKey = $woocommerce_tz_tazapay_settings['sandbox_secret_key'];
      $baseApiUrl = 'https://api-sandbox.tazapay.com';
    }

    $branding = $woocommerce_tz_tazapay_settings['branding'] == 'yes' ? true : false;

    $targetStatus = $woocommerce_tz_tazapay_settings['tazapay_order_status'];

    $paymentFilter = $woocommerce_tz_tazapay_settings['remove_payment_methods'];
    $paymentStyle = $woocommerce_tz_tazapay_settings['custom_style_css'];

    $paymentStyle = preg_replace('~[\r\n]+~', '', $paymentStyle);

    return array(
        'branding'      => $branding,
        'envmode'       => $envmode,
        'apiKey'        => $apiKey,
        'secretKey'     => $secretKey,
        'baseApiUrl'    => $baseApiUrl,
        'paymentFilter' => $paymentFilter,
        'paymentStyle'  => $paymentStyle,
        'targetStatus'  => $targetStatus
    );
}

// API Basic Auth
function tzp_authentication($api_key, $api_secret){

    $apiKey = $api_key;
    $apiSecret = $api_secret;
    $basic_auth = $apiKey . ':' . $apiSecret;
    $authentication = "Basic " . base64_encode($basic_auth);

    return $authentication;
}

// NOTE: This Api is called to ensure api keys are valid on save in admin settings, so don't use tzp_getAdminAPISettings function for keys use data passed by caller.
function tzp_collectMetaData_api($apiKey, $secretKey, $baseApiUrl){

    $method = "GET";
    $APIEndpoint = "/v1/metadata/collect";
    $sampleQuery = "?amount=10&buyer_country=IN&invoice_currency=USD&seller_country=SG";
    $api_url = $baseApiUrl . $APIEndpoint . $sampleQuery;
    $authentication = tzp_authentication($apiKey, $secretKey);

    $response = wp_remote_post(
        esc_url_raw($api_url),
        array(
            'method' => 'GET',
            'sslverify' => false,
            'timeout' => 45,
            'headers' => array(
                'Authorization' => $authentication,
                'Content-Type' => 'application/json',
            ),
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

function tzp_call_api($args,$params,$msg=""){

    $method         = $args['method'] ? $args['method'] : "GET";

    if( !$args['endpoint'] ){
        error_log('ap-endpoint-undefined '.json_encode($msg));

        return;
    }

    $settings       = tzp_getAdminAPISettings();
    $authentication = tzp_authentication($settings['apiKey'], $settings['secretKey']);
    $url            = $settings['baseApiUrl'] . $args['endpoint'];

    if( 'GET' == $method ){

        if( $params ){

            $url      = $url.'?'.http_build_query($params);
        }
        // error_log(json_encode(array(
        //     'LOG' => 'api wrap 144',
        //     '$method' => $method,
        //     '$url' => $url,
        //     '$authentication' => $authentication
        // )));

        $response = wp_remote_get(
            $url,
            array(
                'method' => 'GET',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                    'Accept' => 'application/json',
                    'Authorization' => $authentication,
                    // 'Content-Type' => 'application/json',
                ),
                // 'body' => $body,
            )
        );

    } else {
        $body     = json_encode($params);

        // error_log($body);

        $response = wp_remote_post(
            esc_url_raw($url),
            array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                    'Authorization' => $authentication,
                    'Content-Type' => 'application/json',
                ),
                'body' => $body,
            )
        );
    }

    // error_log(print_r($response,true));

    if (is_wp_error($response)) {

        $error_message = $response->get_error_message();
        esc_html_e('Something went wrong: ' . $error_message, 'wc-tp-payment-gateway');
    } else {

        // TODO: validate response->status for req response status

        /*
        $upload_dir = wp_upload_dir();

        if(! empty( $upload_dir['basedir'] )){
            $filename = $upload_dir['basedir'] . '/' . sanitize_file_name('[tazapay_payment_log],.txt');
            $responsetxt = esc_html($msg) . '-' . wp_remote_retrieve_body($response) . "\n";
            if (file_exists($filename)) {
                $handle = fopen($filename, 'a') or die('Cannot open file:  ' . $filename);
                fwrite($handle, $responsetxt);
            } else {
                $handle = fopen($filename, "w") or die("Unable to open file!");
                fwrite($handle, $responsetxt);
            }
            fclose($handle);
        }
        */

        $api_array = json_decode(wp_remote_retrieve_body($response));
    }

    if( !$api_array || 'success' != $api_array->status){

        error_log('API failed - '.$msg);
        error_log(json_encode($api_array));
        return;
    }

    return $api_array;
}

// Checkout API - GET
function tzp_get_checkout_api($order_id){

    $txn_no = get_post_meta($order_id, 'txn_no', true);

    $response = tzp_call_api(
        array(
            'method'   => 'GET',
            'endpoint' => '/v1/checkout/'.$txn_no
        ),
        null,
        "Get Checkout API"
    );

    // verify orderid-reference_id, txn_no, invoice currency, amount

    $order = wc_get_order($order_id);

    if( $order_id != $response->data->reference_id ){
        error_log('Validation failed: '.$order_id.' reference_id');
        return null;
    } else if( $txn_no != $response->data->txn_no ){
        error_log('Validation failed: '.$order_id.' txn_no');
        return null;
    } else if( $order->get_currency() != $response->data->invoice_currency ){
        error_log('Validation failed: '.$order_id.' invoice_currency');
        return null;
    } else if( $order->get_total() != $response->data->invoice_amount ){
        error_log('Validation failed: '.$order_id.' invoice_amount');
        return null;
    }

    return $response->data;
}

// Checkout API - POST
function tzp_create_checkout_api($args, $order_id){

    $response = tzp_call_api(
        array(
            'method'   => 'POST',
            'endpoint' => '/v1/checkout'
        ),
        $args,
        "Post Checkout API"
    );

    // TODO: validate response

    if( $order_id != $response->data->partner_reference_id){
        error_log('Invalid api response - partner_reference_id mismatch');
        error_log('expected: '.$order_id.' got: '.$response->data->partner_reference_id);
        error_log(json_encode($response->data));
        return;
    }
    return $response;
}

// Refund API
function tzp_refund_request_api($args, $order_id){

    $method = "POST";
    $api_endpoint = '/v1/payment/refund/request';
    $apiSettings = tzp_getAdminAPISettings();
    $api_url = $apiSettings['baseApiUrl'] . $api_endpoint;
    $authentication = tzp_authentication($apiSettings['apiKey'], $apiSettings['secretKey']);
    $json = json_encode($args);

    $response = wp_remote_post(
        esc_url_raw($api_url),
        array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'Authorization' => $authentication,
                'Content-Type' => 'application/json',
            ),
            'body' => $json,
        )
    );

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        esc_html_e('Something went wrong: ' . $error_message, 'wc-tp-payment-gateway');
    } else {
        $upload_dir = wp_upload_dir();

        if(! empty( $upload_dir['basedir'] )){
            $filename = $upload_dir['basedir'] . '/' . sanitize_file_name('[tazapay_payment_log],.txt');
            $responsetxt = 'Oder Id:' . esc_html($order_id) . '-' . wp_remote_retrieve_body($response) . "\n";
            if (file_exists($filename)) {
                $handle = fopen($filename, 'a') or die('Cannot open file:  ' . $filename);
                fwrite($handle, $responsetxt);
            } else {
                $handle = fopen($filename, "w") or die("Unable to open file!");
                fwrite($handle, $responsetxt);
            }
            fclose($handle);
        }

        $api_array = json_decode(wp_remote_retrieve_body($response));
    }

    return $api_array;
}

function tzp_get_refund_api($order_id){
    $txn_no = get_post_meta($order_id, 'txn_no', true);

    $response = tzp_call_api(
        array(
            'method'   => 'GET',
            'endpoint' => '/v1/payment/refund/status'
        ),
        array(
            'txn_no' => $txn_no
        ),
        "Get Refund API"
    );

    // TODO:
    // verify orderid-reference_id, txn_no, invoice currency, amount

    $order = wc_get_order($order_id);
    $reference_id = get_post_meta($orderId, 'reference_id', true);

    $refundRequestStatus = $response->data->txn_no[0];

    // TODO: check if its ->txn_no
    if( $reference_id != $refundRequestStatus->reference_id ){
        error_log('Validation failed: '.$order_id.' refund reference_id');
        return null;
    }

    return $response->data->txn_no;
}