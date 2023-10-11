<?php

include 'tazapay-order.php';
include 'filters.php';
include 'hook-thankyou.php';
include 'webhooks.php';

/**
 * @file
 * This file defines the class TPAY_Gateway
 */
class TPAY_Gateway extends WC_Payment_Gateway
{
    /**
    * Class constructor, more about it in Step 3
    **/
    public function __construct() {


        $this->id = 'tz_tazapay'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom form
        $this->method_title = 'Tazapay Payments';
        $this->method_description = __('Borderless Payments for Global Businesses. Have your customer pay with their local payment methods along with cards and get higher conversion.', 'wc-tp-payment-gateway'); // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but in this tutorial we begin with simple payments
        $this->supports = array(
            'products',
            'refunds'
        );

        // Method with all the options fields
        $this->tzp_init_form_fields();

        // Load the settings.
        $this->init_settings();
        
        // assigns the values of fields in admin page.
        $this->tzp_assign_admin_settings();

        // Adds Icon
        add_filter('woocommerce_gateway_icon', 'tzp_woocommerce_icons', 10, 2);

        // We need custom JavaScript to obtain a token
        add_action( 'wp_enqueue_scripts', array( $this, 'tzp_payment_scripts' ) );

        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'tzp_update_admin_options' ) );

        // We need this to handle payment completion
        add_action('woocommerce_thankyou_' . $this->id , 'tzp_thankyou_page', 20);

        // We need this webhook for auto status updation
        add_action('woocommerce_api_tz_payment', 'tzp_webhook_payment_status_change');
        add_action('woocommerce_api_tz_refund', 'tzp_webhook_refund_status_change');
    }



    // Plugin options
    public function tzp_init_form_fields(){

        // APIs Block
        // need to add link
        $api_header_ref_link    = "https://support.tazapay.com/how-to-get-your-api-secret-keys";
        $api_header_description = '(To Learn more about generating key  <a href="' . esc_url($api_header_ref_link) . '" target="_blank" class="tazapay-click-here" >click here</a>)';

        // custom_style_css Block
        $custom_style_css_ref_link = "https://docs.tazapay.com/v1/reference/style-customisation";
        $custom_style_css_description = 'To learn more about custom styling  <a href="' . esc_url($custom_style_css_ref_link) . '" target="_blank" class="tazapay-click-here">click here</a>';
        
        // remove_payment_methods Block TODO: Yet to Confirm List
        $remove_payment_methods_description = "You can choose to remove payment methods that you do not want your customers to use to make a payment. For example, you may choose to disable Wire Transfer because the payment confirmation is not instantaneous.";
        $paymentMethodList = array(
          "ovo_idr"=> __("ovo_idr", "wc-tp-payment-geteway"),
          "bank_push_vnd"=> __("bank_push_vnd", "wc-tp-payment-geteway"),
          "bank_push_ngn"=> __("bank_push_ngn", "wc-tp-payment-geteway"),
          "bank_push_kes"=> __("bank_push_kes", "wc-tp-payment-geteway"),
          "bank_push_mxn"=> __("bank_push_mxn", "wc-tp-payment-geteway"),
          "bank_push_php"=> __("bank_push_php", "wc-tp-payment-geteway"),
          "bank_push_brl"=> __("bank_push_brl", "wc-tp-payment-geteway"),
          "poli_nzd"=> __("poli_nzd", "wc-tp-payment-geteway"),
          "internet_banking_zar"=> __("internet_banking_zar", "wc-tp-payment-geteway"),
          "fpx_corp_myr"=> __("fpx_corp_myr", "wc-tp-payment-geteway"),
          "fpx_ind_myr"=> __("fpx_ind_myr", "wc-tp-payment-geteway"),
          "poli_aud"=> __("poli_aud", "wc-tp-payment-geteway"),
          "wire_transfer"=> __("wire_transfer", "wc-tp-payment-geteway"),
          "fawry_egp"=> __("fawry_egp", "wc-tp-payment-geteway"),
          "cash_payment_brl"=> __("cash_payment_brl", "wc-tp-payment-geteway"),
          "boleto_brl"=> __("boleto_brl", "wc-tp-payment-geteway"),
          "mobile_money_egp"=> __("mobile_money_egp", "wc-tp-payment-geteway"),
          "qris_idr"=> __("qris_idr", "wc-tp-payment-geteway"),
          "pix_brl"=> __("pix_brl", "wc-tp-payment-geteway"),
          "upi_inr"=> __("upi_inr", "wc-tp-payment-geteway"),
          "paynow_sgd"=> __("paynow_sgd", "wc-tp-payment-geteway"),
          "promptpay_thb"=> __("promptpay_thb", "wc-tp-payment-geteway"),
          "payid_aud"=> __("payid_aud", "wc-tp-payment-geteway"),
          "sofort_eur"=> __("sofort_eur", "wc-tp-payment-geteway"),
          "bank_initiation_gbp"=> __("bank_initiation_gbp", "wc-tp-payment-geteway"),
          "sofort_gbp"=> __("sofort_gbp", "wc-tp-payment-geteway"),
          "bank_initiation_eur"=> __("bank_initiation_eur", "wc-tp-payment-geteway"),
          "dana_idr"=> __("dana_idr", "wc-tp-payment-geteway"),
          "internet_banking_cop"=> __("internet_banking_cop", "wc-tp-payment-geteway"),
          "mobile_money_ghs"=> __("mobile_money_ghs", "wc-tp-payment-geteway"),
          "mobile_money_ugx"=> __("mobile_money_ugx", "wc-tp-payment-geteway"),
          "mobile_money_tzs"=> __("mobile_money_tzs", "wc-tp-payment-geteway"),
          "mobile_money_kes"=> __("mobile_money_kes", "wc-tp-payment-geteway"),
          "mobilebanking_thb"=> __("mobilebanking_thb", "wc-tp-payment-geteway"),
          "linkaja_idr"=> __("linkaja_idr", "wc-tp-payment-geteway"),
          "card"=> __("card", "wc-tp-payment-geteway"),
          "local_bank_transfer_usd"=> __("local_bank_transfer_usd", "wc-tp-payment-geteway"),
          "local_bank_transfer_ngn"=> __("local_bank_transfer_ngn", "wc-tp-payment-geteway"),
          "local_bank_transfer_kes"=> __("local_bank_transfer_kes", "wc-tp-payment-geteway"),
          "local_bank_transfer_ars"=> __("local_bank_transfer_ars", "wc-tp-payment-geteway"),
          "local_bank_transfer_mxn"=> __("local_bank_transfer_mxn", "wc-tp-payment-geteway"),
          "local_bank_transfer_gbp"=> __("local_bank_transfer_gbp", "wc-tp-payment-geteway"),
          "local_bank_transfer_eur"=> __("local_bank_transfer_eur", "wc-tp-payment-geteway"),
          "local_bank_transfer_php"=> __("local_bank_transfer_php", "wc-tp-payment-geteway"),
          "local_bank_transfer_nzd"=> __("local_bank_transfer_nzd", "wc-tp-payment-geteway"),
          "local_bank_transfer_krw"=> __("local_bank_transfer_krw", "wc-tp-payment-geteway"),
          "local_bank_transfer_jpy"=> __("local_bank_transfer_jpy", "wc-tp-payment-geteway"),
          "local_bank_transfer_idr"=> __("local_bank_transfer_idr", "wc-tp-payment-geteway"),
          "local_bank_transfer_hkd"=> __("local_bank_transfer_hkd", "wc-tp-payment-geteway"),
          "local_bank_transfer_aud"=> __("local_bank_transfer_aud", "wc-tp-payment-geteway"),
          "local_bank_transfer_sgd"=> __("local_bank_transfer_sgd", "wc-tp-payment-geteway"),
          "local_bank_transfer_cad"=> __("local_bank_transfer_cad", "wc-tp-payment-geteway"),
          "grabpay_php"=> __("grabpay_php", "wc-tp-payment-geteway"),
          "shopeepay_php"=> __("shopeepay_php", "wc-tp-payment-geteway"),
          "paymaya_php"=> __("paymaya_php", "wc-tp-payment-geteway"),
          "card_local_ngn"=> __("card_local_ngn", "wc-tp-payment-geteway"),
          "card_cup_sgd"=> __("card_cup_sgd", "wc-tp-payment-geteway"),
          "card_cup_usd"=> __("card_cup_usd", "wc-tp-payment-geteway"),
          "card_cup_cny"=> __("card_cup_cny", "wc-tp-payment-geteway"),
          "payeasy_jpy"=> __("payeasy_jpy", "wc-tp-payment-geteway"),
          "payeasy_cash_jpy"=> __("payeasy_cash_jpy", "wc-tp-payment-geteway"),
          "alipay_cny"=> __("alipay_cny", "wc-tp-payment-geteway"),
          "blik_pln"=> __("blik_pln", "wc-tp-payment-geteway"),
          "giropay_eur"=> __("giropay_eur", "wc-tp-payment-geteway"),
        );
        
        // modes and others Block
        $select_env_mode_description = "Actual money movement will not happen in test mode (sandbox). You can use the test mode to test the payment gateway before your actual customers use it.";

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Tazapay Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'wc-tp-payment-gateway'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wc-tp-payment-gateway'),
                'default'     => __('Payment with Local Payment Methods and Cards', 'wc-tp-payment-gateway'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'wc-tp-payment-gateway'),
                'type'        => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'wc-tp-payment-gateway'),
                'default'     => __('Pay securely with buyer protection using Local payment methods.', 'wc-tp-payment-gateway'),
                'desc_tip'    => true,
            ),
            'branding' => array(
                'title'       => 'Enable Branding logos',
                'label'       => 'Show Payment Brand Logos',
                'type'        => 'checkbox',
                'description' => 'This controls visibility for payment brand logos on checkout page.',
                'default'     => 'yes'
            ),
            'select_env_mode' => array(
                'title'       => __('Select Mode', 'wc-tp-payment-gateway'),
                'label'       => __('Select Mode', 'wc-tp-payment-gateway'),
                'type'        => 'select',
                'options'     => array(
                    'Production' => __('Live Mode (Production)', 'wc-tp-payment-gateway'),
                    'Sandbox'    => __('Test Mode (Sandbox)', 'wc-tp-payment-gateway'),
                ),
                'class'       => 'wc-enhanced-select',
                'default'     => __('Live Mode (Production)', 'wc-tp-payment-gateway'),
                'description' => __( $select_env_mode_description, 'wc-tp-payment-gateway' ),
                'desc_tip'    => true,
            ),
            'prod_api_header' => array(
                'title'       => __('Enter live mode (production) API keys', 'wc-tp-payment-gateway'),
                'type'        => 'hidden',
                'class'       => 'tazapay_live_mode_fields',
                'description' => __($api_header_description, 'wc-tp-payment-gateway'),
            ),
            'prod_api_key' => array(
                'title'       => __('API_Key', 'wc-tp-payment-gateway'),
                'type'        => 'password',
                'placeholder' => 'Please Enter Production API_Key',
                'class'       => 'tazapay_live_mode_fields',
            ),
            'prod_secret_key' => array(
                'title'       => __('API_Secret', 'wc-tp-payment-gateway'),
                'type'        => 'password',
                'placeholder' => 'Please Enter Production API_Secret',
                'class'       => 'tazapay_live_mode_fields',
            ),
            'sandbox_api_header' => array(
                'title'       => __('Enter test mode (Sandbox) API keys', 'wc-tp-payment-gateway'),
                'type'        => 'hidden',
                'description' => __($api_header_description, 'wc-tp-payment-gateway'),
                'class'       => 'tazapay_test_mode_fields',
            ),
            'sandbox_api_key' => array(
                'title'       => __('API_Key', 'wc-tp-payment-gateway'),
                'type'        => 'password',
                'placeholder' => 'Please Enter Sandbox API_Key',
                'class'       => 'tazapay_test_mode_fields',
            ),
            'sandbox_secret_key' => array(
                'title'       => __('API_Secret', 'wc-tp-payment-gateway'),
                'type'        => 'password',
                'placeholder' => 'Please Enter Sandbox API_Secret',
                'class'       => 'tazapay_test_mode_fields',
            ),
            'tazapay_order_status' => array(
                'title'       => __('Order Status', 'wc-tp-payment-gateway'),
                'type'        => 'select',
                'options'     => array(
                    'processing' => __('Processing', 'wc-tp-payment-gateway'),
                    'completed'  => __('Completed', 'wc-tp-payment-gateway'),
                ),
                'class'       => 'wc-enhanced-select',
                'default'     => 'Processing',
                'description' => __( 'Set your desired order status upon successful payment.', 'wc-tp-payment-gateway' ),
            ),
            'remove_payment_methods' => array(
                'title'             => __( 'Remove Payment Methods', 'wc-tp-payment-gateway' ),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'default'           => '',
                'description'       => __( $remove_payment_methods_description, 'wc-tp-payment-gateway' ),
                'options'           => $paymentMethodList,
                'desc_tip'          => true,
                'custom_attributes' => array(
                    'data-placeholder' => __( 'Select payment methods', 'wc-tp-payment-gateway' ),
                ),
            ),
            'custom_style_css' => array(
                'title'       => __('Custom Style CSS', 'wc-tp-payment-gateway'),
                'type'        => 'textarea',
                'css'         => 'width:400px; height:100px',      
                'description' => __( $custom_style_css_description, 'wc-tp-payment-gateway' ),
				'default'     => __( '', 'wc-tp-payment-gateway' ),
            )
        );
    }

    // assigns the admin settings fields globally
    public function tzp_assign_admin_settings(){
        $this->title = $this->get_option( 'title' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->env_mode = $this->get_option( 'select_env_mode' );
        $this->api_key = $this->get_option('prod_api_key');
        $this->api_secret = $this->get_option('prod_secret_key');
        $this->base_api_url = 'https://service.tazapay.com';
        $this->sdk_version = '2.0';

        if($this->env_mode === 'Sandbox'){
            $this->api_key = $this->get_option('sandbox_api_key');
            $this->api_secret = $this->get_option('sandbox_secret_key');
            $this->base_api_url = 'https://service-sandbox.tazapay.com';
        }
        
        $this->orderStatusOnSuccess = $this->get_option('tazapay_order_status');
        $this->remove_payment_methods = $this->get_option('remove_payment_methods');
        $this->custom_style_css = $this->get_option('custom_style_css');
    }

    // Process Gateway Settings Form Fields.
    public function tzp_update_admin_options() {
      
        $this->init_settings();
        $post_data = $this->get_post_data();
        $error_fields = array();

        if(tzp_validate_api_keys($post_data)){
            if($post_data['woocommerce_tz_tazapay_select_env_mode'] === 'Sandbox'){
                WC_Admin_Settings::add_error(__('Please Enter Valid Sandbox API Keys.', 'wc-tp-payment-gateway'));
                array_push($error_fields, 'sandbox_api_key', 'sandbox_secret_key');
            }else{
                WC_Admin_Settings::add_error(__('Please Enter Valid Production API Keys.', 'wc-tp-payment-gateway'));
                array_push($error_fields, 'prod_api_key', 'prod_secret_key');
            }
        }

        if(!tzp_validate_custom_style_css($post_data['woocommerce_tz_tazapay_custom_style_css'])){
            WC_Admin_Settings::add_error(__('Please Enter Custom Styling in Valid JSON Format.', 'wc-tp-payment-gateway'));
            array_push($error_fields, 'custom_style_css');
        }
        
        foreach ( $this->get_form_fields() as $key => $field ) {
            $setting_value = $this->get_field_value( $key, $field, $post_data );

            if(!in_array($key, $error_fields)){
                $this->settings[$key] = $setting_value;
            }
        }

        return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ) );
    }

    /*
    * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
    */
    public function tzp_payment_scripts() {

        // we need JavaScript to process a token only on cart/checkout pages, right?
        if ( !is_wc_endpoint_url( 'order-pay' ) ) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ( 'no' === $this->enabled ) {
            return;
        }

        // no reason to enqueue JavaScript if API keys are not set
        if ( empty( $this->api_key ) || empty( $this->api_secret ) ) {
            return;
        }

        if ( empty( WC()->session->get( 'order_id' ) ) || empty( WC()->session->get( 'token' ) ) || empty( WC()->session->get( 'complete_url' ) ) ) {
            return;
        }

        // do not work with card detailes without SSL unless your website is in a test mode
        if ( ! $this->env_mode === 'Sandbox' && ! is_ssl() ) {
            return;
        }


        $order_id = WC()->session->get( 'order_id' );
        $order = wc_get_order($order_id);
        $orderStatus = $order->get_status();

        if( 'tz_tazapay' != $order->get_payment_method() ) {

            return;
        }

        if( 'processing' == $orderStatus || 'completed' == $orderStatus ){

            wp_redirect( WC()->session->get( 'complete_url' ) );
            exit;

        } else if( 'pending' == $orderStatus || 'on-hold' == $orderStatus ){

            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            if( $this->env_mode == 'Sandbox' ){
                
                wp_enqueue_script( 'tpay_js', 'https://js-sandbox.tazapay.com/v'.$this->sdk_version.'-sandbox.js' );
                // wp_enqueue_script( 'tpay_js', 'http://127.0.0.1:4173/v1.1-localdev.js' );
            } else {

                wp_enqueue_script( 'tpay_js', 'https://js.tazapay.com/v'.$this->sdk_version.'.js' );
            }
            
            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script( 'woocommerce_tpay', plugins_url( 'tpay.js', __FILE__ ), array( 'jquery', 'tpay_js' ) );

            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script( 'woocommerce_tpay', 'tpay_params', array(
                // 'publishableKey' => $this->publishable_key,
                'token' => WC()->session->get( 'token' ),
                'complete_url' => WC()->session->get( 'complete_url' ),
                'abort_url' => WC()->session->get( 'abort_url' ),
                'style' => WC()->session->get( 'style' ),
            ) );

            wp_enqueue_script( 'woocommerce_tpay' );

        } else if( 'cancelled' == $orderStatus ){
             wp_redirect( $order->get_view_order_url() );
             exit;
        // } else if( 'failed' == $orderStatus ){
        //      wp_redirect( wc_get_checkout_url() );
        //      exit;
        } else {

            // for $orderStatus == 'failed' or anything else

            wp_redirect( wc_get_checkout_url() );
            exit;
        }
    }
    
    public function get_description()
    {
        return $this->get_option('description');
    }

    public function process_payment($order_id){
        global $woocommerce;

        $order = wc_get_order($order_id);
        $items = array();

        foreach (WC()->cart->get_cart() as $cart_item) {
            $item_name = $cart_item['data']->get_title();
            $quantity = $cart_item['quantity'];
            $items[] = $quantity . ' x ' . $item_name;
        }
        $listofitems = implode(', ', $items);
        $description = get_bloginfo('name') . ' : ' . $listofitems;

        $abort_url = wc_get_checkout_url(); 
        $complete_url = $order->get_checkout_order_received_url();
        $callback_url = site_url().'/?wc-api=tz_payment&order_id='.$order_id;

        // Get the posted data from the checkout form.
        $posted_data = $_POST;
  
        // Check if the "ship to a different address" checkbox is checked.
        $ship_to_different_address = isset($posted_data['ship_to_different_address']) ? true : false;

        $checkoutArgs = tzp_checkoutRequestBody($order, $description, array(
            'abort_url' => $abort_url,
            'complete_url' => $complete_url,
            'callback_url' => $callback_url,
            'same_as_billing_address'=> $ship_to_different_address,
        ));

        $result = tzp_create_checkout_api($checkoutArgs, $order_id);

        if ($result->status === 'error') {
            $payment_err_msg = "";
            foreach ($result->errors as $key => $error) {
                if (isset($error->message)) {
                    $payment_err_msg .= "<br>" . esc_html($error->message);
                }
            }
            $order->add_order_note('TZ '.$payment_err_msg);
            wc_clear_notices();
            
            return wc_add_notice($payment_err_msg, 'error');
        }

        if ($result->status === 'success') {
            //create_taza_logs("End > Payment Process ({$order_id}) \n");
           
            $order->add_order_note('TZ Payment created - '.$result->data->payin);
            $settings = tzp_getAdminAPISettings();

            // Existing Behaviour
            $redirect_url = $result->data->url;

            // NOTE: If no UUID require Remove account_id in post meta
            $account_id = isset($result->data->customer) ? sanitize_text_field($result->data->customer) : '';
            update_post_meta($order_id, 'txn_no', $result->data->payin);
            update_post_meta($order_id, 'account_id', $account_id);
            update_post_meta($order_id, 'redirect_url', $redirect_url);
            

            // this is to extract key from url
            $parts = parse_url($redirect_url);

            if (isset($parts['path'])) {
              $pathParts = explode('/', $parts['path']);
              $lastKey = end($pathParts);
              update_post_meta($order_id, 'token', $lastKey);
              WC()->session->set( 'token' , $lastKey );
            }
            WC()->session->set( 'order_id' , $order_id );
            WC()->session->set( 'complete_url' , $complete_url );
            WC()->session->set( 'abort_url' , $abort_url );
            WC()->session->set( 'style' , $settings['paymentStyle'] );

            return tzp_getPaymentUrl($order);
        }

    }

    public function process_refund($orderId, $amount = null, $reason = 'refund'){
        $order = wc_get_order($orderId);
        $txn_no = get_post_meta($orderId, 'txn_no', true);
        if (!$order || !$txn_no) {
            return new WP_Error('error', __('Refund failed: No transaction ID', 'woocommerce'));
        }

        if ($order->get_payment_method() == 'tz_tazapay') {

          $callback_url = site_url().'/?wc-api=tz_refund&order_id='.$orderId;

          $refundArg = array(
            "payin" => $txn_no,
            "payment_attempt"=> "",
            "amount"=> formatToInt64($amount),
            "reason"=> $reason,
            "remarks"=> "",
            "source"=> "woocommerce",
            "webhook_url" => $callback_url,
            "metadata" => array(
              "reference_id" => $orderId,
            )
          );

          $result = tzp_refund_request_api($refundArg, $orderId);

          if (!empty($result)) {
              if ($result->status == 'error') {
                  $message = $result->errors[0]->message;
                  return new WP_Error('error', __($message, 'woocommerce'));
              }

              //Success
              if ($result->status == 'success') {
                  $reference_id = $result->data->metadata->metadata->reference_id;
                  update_post_meta($orderId, 'reference_id', $reference_id);
                  $order->add_order_note('TZ Refund requested.');
                  return true;
              }
          }
        }

        return false;
    }
}