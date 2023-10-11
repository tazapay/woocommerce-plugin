<?php

function formatToInt64($amount) {
  $formattedAmount = number_format($amount, 2, '.', '');
  $formattedAmount = round($formattedAmount * 100);
  return $formattedAmount;
}

// get Checkout API Args
function tzp_checkoutRequestBody($order, $description, $paymentArgs){

  $plugin = tzp_get_plugin_info();
  $apiSettings = tzp_getAdminAPISettings();

  $billingPhoneCode  = tzp_getphonecode($order->get_billing_country());
  $shippingPhoneCode = tzp_getphonecode($order->get_shipping_country());

  $billingDetails = array(
    "name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
    "address"   => array(
        "line1"         => $order->get_billing_address_1(),
        "line2"         => $order->get_billing_address_2(),
        "city"          => $order->get_billing_city(),
        "state"         => "",
        "country"       => $order->get_billing_country(),
        "postal_code"   => $order->get_billing_postcode(),
    ),
  );

  // send phone field only if phone number is present
  if($order->get_billing_phone()) {
    $billingDetails['phone'] = array(
        "country_code"  => $billingPhoneCode,
        "number"        => $order->get_billing_phone(),
    );
  }

  if($order->get_billing_state()) {
    $billingDetails['address']['state'] = $order->get_billing_country() . '-' . $order->get_billing_state();
  }

  $shippingDetails = array(
    "name"              => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
    "address"   => array(
        "line1"         => $order->get_shipping_address_1(),
        "line2"         => $order->get_shipping_address_2(),
        "city"          => $order->get_shipping_city(),
        "state"         => "",
        "country"       => $order->get_shipping_country(),
        "postal_code"   => $order->get_shipping_postcode(),
    ),
  );

  if($order->get_shipping_state()) {
    $shippingDetails['address']['state'] = $order->get_shipping_country() . '-' . $order->get_shipping_state();
  }

  if($order->get_shipping_phone()) {
    $shippingDetails['phone'] = array(
        "country_code"  => $shippingPhoneCode,
        "number"        => $order->get_shipping_phone(),
    );
  }

  $customerDetails = array(
    "email"             => $order->get_billing_email(),
    "name"              => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
    "country"           => $order->get_billing_country(),
  );

  if($order->get_billing_phone()) {
    $customerDetails['phone'] = array(
        "country_code"  => $billingPhoneCode,
        "number"        => $order->get_billing_phone(),
    );
  }

  $today = new DateTime();
  $today->modify('+7 days');
  $expiresAt = $today->format('Y-m-d\TH:i:s\Z');

  $checkoutArgs = array(
    "amount"        => formatToInt64($order->get_total()),
    "invoice_currency"      => $order->get_currency(),
    "transaction_description"       => $description,
    "txn_source_category"    => "woocommerce",
    "txn_source"            => $plugin['Version'],
    "webhook_url"          => $paymentArgs['callback_url'],
    "success_url"          => $paymentArgs['complete_url'],
    "cancel_url"             => $paymentArgs['abort_url'],
    "shipping_details"  => $shippingDetails,
    "billing_details"   => $billingDetails,
    "customer_details"  => $customerDetails,
    "same_as_billing_address"  => $paymentArgs['same_as_billing_address'],
    "expires_at" => $expiresAt,
    "reference_id"      => strval($order->get_id()),
  );

  if( !(bool) empty($apiSettings['paymentFilter'])){
    $checkoutArgs['remove_payment_methods'] = $apiSettings['paymentFilter'];
  }

  return $checkoutArgs;
}

// get phoneCode
function tzp_getphonecode($countryCode){
    $countryCodeArray = [
    'AD' => '376',
    'AE' => '971',
    'AF' => '93',
    'AG' => '1268',
    'AI' => '1264',
    'AL' => '355',
    'AM' => '374',
    'AN' => '599',
    'AO' => '244',
    'AQ' => '672',
    'AR' => '54',
    'AS' => '1684',
    'AT' => '43',
    'AU' => '61',
    'AW' => '297',
    'AZ' => '994',
    'BA' => '387',
    'BB' => '1246',
    'BD' => '880',
    'BE' => '32',
    'BF' => '226',
    'BG' => '359',
    'BH' => '973',
    'BI' => '257',
    'BJ' => '229',
    'BL' => '590',
    'BM' => '1441',
    'BN' => '673',
    'BO' => '591',
    'BR' => '55',
    'BS' => '1242',
    'BT' => '975',
    'BW' => '267',
    'BY' => '375',
    'BZ' => '501',
    'CA' => '1',
    'CC' => '61',
    'CD' => '243',
    'CF' => '236',
    'CG' => '242',
    'CH' => '41',
    'CI' => '225',
    'CK' => '682',
    'CL' => '56',
    'CM' => '237',
    'CN' => '86',
    'CO' => '57',
    'CR' => '506',
    'CU' => '53',
    'CV' => '238',
    'CX' => '61',
    'CY' => '357',
    'CZ' => '420',
    'DE' => '49',
    'DJ' => '253',
    'DK' => '45',
    'DM' => '1767',
    'DO' => '1809',
    'DZ' => '213',
    'EC' => '593',
    'EE' => '372',
    'EG' => '20',
    'ER' => '291',
    'ES' => '34',
    'ET' => '251',
    'FI' => '358',
    'FJ' => '679',
    'FK' => '500',
    'FM' => '691',
    'FO' => '298',
    'FR' => '33',
    'GA' => '241',
    'GB' => '44',
    'GD' => '1473',
    'GE' => '995',
    'GH' => '233',
    'GI' => '350',
    'GL' => '299',
    'GM' => '220',
    'GN' => '224',
    'GQ' => '240',
    'GR' => '30',
    'GT' => '502',
    'GU' => '1671',
    'GW' => '245',
    'GY' => '592',
    'HK' => '852',
    'HN' => '504',
    'HR' => '385',
    'HT' => '509',
    'HU' => '36',
    'ID' => '62',
    'IE' => '353',
    'IL' => '972',
    'IM' => '44',
    'IN' => '91',
    'IQ' => '964',
    'IR' => '98',
    'IS' => '354',
    'IT' => '39',
    'JM' => '1876',
    'JO' => '962',
    'JP' => '81',
    'KE' => '254',
    'KG' => '996',
    'KH' => '855',
    'KI' => '686',
    'KM' => '269',
    'KN' => '1869',
    'KP' => '850',
    'KR' => '82',
    'KW' => '965',
    'KY' => '1345',
    'KZ' => '7',
    'LA' => '856',
    'LB' => '961',
    'LC' => '1758',
    'LI' => '423',
    'LK' => '94',
    'LR' => '231',
    'LS' => '266',
    'LT' => '370',
    'LU' => '352',
    'LV' => '371',
    'LY' => '218',
    'MA' => '212',
    'MC' => '377',
    'MD' => '373',
    'ME' => '382',
    'MF' => '1599',
    'MG' => '261',
    'MH' => '692',
    'MK' => '389',
    'ML' => '223',
    'MM' => '95',
    'MN' => '976',
    'MO' => '853',
    'MP' => '1670',
    'MR' => '222',
    'MS' => '1664',
    'MT' => '356',
    'MU' => '230',
    'MV' => '960',
    'MW' => '265',
    'MX' => '52',
    'MY' => '60',
    'MZ' => '258',
    'NA' => '264',
    'NC' => '687',
    'NE' => '227',
    'NG' => '234',
    'NI' => '505',
    'NL' => '31',
    'NO' => '47',
    'NP' => '977',
    'NR' => '674',
    'NU' => '683',
    'NZ' => '64',
    'OM' => '968',
    'PA' => '507',
    'PE' => '51',
    'PF' => '689',
    'PG' => '675',
    'PH' => '63',
    'PK' => '92',
    'PL' => '48',
    'PM' => '508',
    'PN' => '870',
    'PR' => '1',
    'PT' => '351',
    'PW' => '680',
    'PY' => '595',
    'QA' => '974',
    'RO' => '40',
    'RS' => '381',
    'RU' => '7',
    'RW' => '250',
    'SA' => '966',
    'SB' => '677',
    'SC' => '248',
    'SD' => '249',
    'SE' => '46',
    'SG' => '65',
    'SH' => '290',
    'SI' => '386',
    'SK' => '421',
    'SL' => '232',
    'SM' => '378',
    'SN' => '221',
    'SO' => '252',
    'SR' => '597',
    'ST' => '239',
    'SV' => '503',
    'SY' => '963',
    'SZ' => '268',
    'TC' => '1649',
    'TD' => '235',
    'TG' => '228',
    'TH' => '66',
    'TJ' => '992',
    'TK' => '690',
    'TL' => '670',
    'TM' => '993',
    'TN' => '216',
    'TO' => '676',
    'TR' => '90',
    'TT' => '1868',
    'TV' => '688',
    'TW' => '886',
    'TZ' => '255',
    'UA' => '380',
    'UG' => '256',
    'US' => '1',
    'UY' => '598',
    'UZ' => '998',
    'VA' => '39',
    'VC' => '1784',
    'VE' => '58',
    'VG' => '1284',
    'VI' => '1340',
    'VN' => '84',
    'VU' => '678',
    'WF' => '681',
    'WS' => '685',
    'XK' => '381',
    'YE' => '967',
    'YT' => '262',
    'ZA' => '27',
    'ZM' => '260',
    'ZW' => '263',
    ];
    $phoneCode = $countryCodeArray[$countryCode];
    return $phoneCode;
}

// validates the api keys entered in admin page.
function tzp_validate_api_keys($data){
  // tzp_create_taza_logs('tzp_validate_api_keys  api called');
  
  $mode = $data['woocommerce_tz_tazapay_select_env_mode'];

  $api_key = $data['woocommerce_tz_tazapay_prod_api_key'];
  $api_secret = $data['woocommerce_tz_tazapay_prod_secret_key'];
  $base_api_url = 'https://service.tazapay.com';

  
  if($mode === 'Sandbox'){
      $api_key = $data['woocommerce_tz_tazapay_sandbox_api_key'];
      $api_secret = $data['woocommerce_tz_tazapay_sandbox_secret_key'];
      $base_api_url = 'https://service-sandbox.tazapay.com';
  }
  
  $isValidKeys = tzp_collectMetaData_api($api_key, $api_secret, $base_api_url);
  if($isValidKeys->status == 'error'){
      return true;
  }
  return false;
}

// validates the custom styling format entered in admin page.
function tzp_validate_custom_style_css($custom_style_css){

  if($custom_style_css){

    $custom_style_css = stripslashes($custom_style_css);

    $json = json_decode($custom_style_css, true, 5);

    $isString = is_string($custom_style_css);
    $isArray = is_array($json);
    $isObject = is_object($json);

    return $isString && ($isArray || $isObject);
  }
  return true;
}

// logs for debugging
function tzp_create_taza_logs($msg = ''){
  $t = microtime(true);
  $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
  $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
  $time = $d->format("m/d/Y H:i:s v") . 'ms ';

  $txt = $time . " > {$msg}";
  @file_put_contents('tazapay_logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Adds txnDetailsTable in customers account orders->view.
add_action('woocommerce_view_order', 'tzp_TxnDetailsTable', 20);
function tzp_TxnDetailsTable($order_id){
  $order = wc_get_order($order_id);
  $paymentMethod = $order->get_payment_method();
  $txn_no = get_post_meta($order_id, 'txn_no', true);
  
  if (!empty($txn_no) && $paymentMethod == 'tz_tazapay') {
    $user_email = $order->get_billing_email();
    $getEscrowstate = tzp_get_checkout_api($order_id);
    ?>
    <h2><?php esc_html_e('Transaction Details', 'wc-tp-payment-gateway');?></h2>
    <p><?php esc_html_e('Payment powered by Tazapay', 'wc-tp-payment-gateway');?></p>
    <table class="woocommerce-table shop_table gift_info">
      <tbody>
        <tr>
          <th scope="row"><?php esc_html_e('Tazapay Payer E-Mail', 'wc-tp-payment-gateway');?></th>
          <td><?php esc_html_e($user_email, 'wc-tp-payment-gateway');?></td>
        </tr>
        <?php if ($txn_no) {?>
          <tr>
            <th scope="row"><?php esc_html_e('Transaction no', 'wc-tp-payment-gateway');?></th>
            <td><?php esc_html_e($txn_no, 'wc-tp-payment-gateway');?></td>
          </tr>
        <?php }?>
      </tbody>
    </table>
    <?php

  }
}

// Removes 'manually refund' button in woocommerce->orders->order page
add_action('admin_head', 'tzp_remove_manual_refunds');
function tzp_remove_manual_refunds(){

  echo '<style>
    .do-manual-refund {
      display: none !important;
    }
    </style>';
}

add_action('admin_head', 'tzp_remove_refund_button_for_refunded');
function tzp_remove_refund_button_for_refunded(){

  if( isset( $_GET['post'] ) ){

    $order_id = (int)sanitize_text_field($_GET['post']);
    $order = wc_get_order($order_id);

    if( !is_null($order) && $order ){
      $paymentMethod = $order->get_payment_method();

      if ($paymentMethod == 'tz_tazapay') {

        $orderStatus = $order->get_status();

        if( 'refunded' == $orderStatus ){

          echo '<style>
            button.button.refund-items {
              display: none !important;
            }
            </style>';
        }
      }
    }
  }
}

// removes 'custom fields' section in woocommerce->orders->order below item.
add_action('add_meta_boxes', 'tzp_remove_shop_order_meta_boxe', 90);
function tzp_remove_shop_order_meta_boxe(){
    remove_meta_box('postcustom', 'shop_order', 'normal');
}

// shows transaction details on click on an order in seller orders tab.
add_action('woocommerce_admin_order_data_after_order_details', 'tzp_order_meta_general');
function tzp_order_meta_general($order){
  $order_id = $order->get_id();
  $account_id = get_post_meta($order_id, 'account_id', true);
  $txn_no = get_post_meta($order_id, 'txn_no', true);

  if ($order->get_payment_method() == 'tz_tazapay' && isset($account_id) && !empty($account_id)) {
    ?>
    <br class="clear" />
    <h3><?php esc_html_e('Transaction Details', 'wc-tp-payment-gateway');?></h3>

    <div class="address">
      <p><strong><?php esc_html_e('TazaPay Transaction no: ', 'wc-tp-payment-gateway');?></strong> <?php esc_html_e($txn_no, 'wc-tp-payment-gateway');?></p>
      <?php
      $getEscrowstate = tzp_get_checkout_api($order_id);
      if ($getEscrowstate->status == 'success') {
        ?>
        <p><strong><?php esc_html_e('Payment status: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->payment_status, 'wc-tp-payment-gateway');?></p>
        <?php
      }
      ?>
      <a href="<?php echo esc_url($order->get_edit_order_url(), 'wc-tp-payment-gateway'); ?>&order-status=true" class="order-status-response button button-primary"><?php esc_html_e('Refresh Status', 'wc-tp-payment-gateway');?></a>
    </div>
    <?php
  }
}