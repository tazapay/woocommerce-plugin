<?php
class TCPG_Gateway extends WC_Payment_Gateway {
	/**
	 * Class constructor
	 */
	public function __construct() {

		session_start();

		$this->id = 'tz_tazapay'; // payment gateway plugin ID
		$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields = true; // in case you need a custom form
		$this->method_title = 'Tazapay Gateway';
		$this->method_description = __('Collect payments from buyers, hold it until the seller/service provider fulfills their obligations before releasing the payment to them.', 'wc-tp-payment-gateway'); // will be displayed on the options page

		$this->callBackUrl = get_site_url() . '/wc-api/' . $this->id;

		// gateways can support subscriptions, refunds, saved payment methods,
		// but in this gateway we begin with simple payments
		$this->supports = array(
			'products',
			'refunds',
		);

		$this->title = $this->get_option('title');
		$this->seller_name = $this->get_option('seller_name');
		$this->seller_email = $this->get_option('seller_email');
		$this->seller_id = $this->get_option('seller_id');
		$this->tazapay_seller_type = $this->get_option('tazapay_seller_type');
		$this->tazapay_multi_seller_plugin = $this->get_option('tazapay_multi_seller_plugin');
		$this->seller_country = $this->get_option('seller_country');
		$this->txn_type_escrow = $this->get_option('txn_type_escrow');
		$this->release_mechanism = $this->get_option('release_mechanism');
		$this->fee_paid_by = $this->get_option('fee_paid_by');
		$this->fee_percentage = $this->get_option('fee_percentage');
		$this->enabled = $this->get_option('enabled');
		$this->sandboxmode = 'sandbox' === $this->get_option('sandboxmode');
		$this->live_api_key = $this->sandboxmode ? $this->get_option('sandbox_api_key') : $this->get_option('live_api_key');
		$this->live_api_secret_key = $this->sandboxmode ? $this->get_option('sandbox_api_secret_key') : $this->get_option('live_api_secret_key');

		if ($this->sandboxmode == 'sandbox') {
			$this->base_api_url = 'https://api-sandbox.tazapay.com';
			$this->environment = 'sandbox';
		} else {
			$this->base_api_url = 'https://api.tazapay.com';
			$this->environment = 'production';
		}

		//$this->create_taza_logs("Start > Get initial User API> tcpg_payment_gateway_disable_tazapay > /v1/user/ \n");
		//$this->seller_data = $this->tcpg_request_api_getuser($this->get_option('seller_email'));

		//$this->countryconfig = $this->tcpg_request_api_countryconfig($this->seller_data->data->country_code);

		if(empty($_SESSION['seller_info']))
		{
			$_SESSION['seller_info'] = $this->save_seller_information($this->get_option('seller_email'));
			$this->seller_data = $_SESSION['seller_info']->info;
			$this->countryconfig = $_SESSION['seller_info']->seller_country;
		}else{
			$this->seller_data = $_SESSION['seller_info']->info;
			$this->countryconfig = $_SESSION['seller_info']->seller_country;
		}


		// Method with all the options fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		global $woocommerce, $post;

		if ((isset($post->post_type) && $post->post_type == 'shop_order') && (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit')) {
			$this->update_status($post->ID);
		}

		// This action hook saves the settings
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'tcpg_getuser_info_options'));

		add_action('woocommerce_thankyou', array($this, 'tcpg_view_order_and_thankyou_page'), 20);

		add_filter('woocommerce_gateway_icon', array($this, 'tcpg_woocommerce_icons'), 10, 2);
		add_filter('woocommerce_available_payment_gateways', array($this, 'tcpg_woocommerce_available_payment_gateways'));
		add_action('woocommerce_admin_order_data_after_order_details', array($this, 'tcpg_order_meta_general'));

		add_action('wp_ajax_order_status_refresh', array($this, 'tazapay_order_status_refresh'));

		add_action('woocommerce_api_tz_tazapay', array($this, 'webhook'));
	
	}

	/*
	*This function will work to store the information of seller
	*/
	public function save_seller_information($seller_email = "")
	{
		if($seller_email != "")
		{
			global $wpdb;

			$table_seller_info = $wpdb->prefix . 'tazapay_seller_info';
			$table_country_config = $wpdb->prefix . 'tazapay_seller_country_config';
			$results = $wpdb->get_results("SELECT * FROM $table_seller_info WHERE  `email` = '$seller_email'");
			if(!empty($results))
			{
				return $this->convertInObject($results[0]);	//CALLING FUNCTION TO CONVERT FROM ARRAY TO OBJECT
			}else{ 
				require_once(ABSPATH . 'wp-content/plugins/tazapay/wc-tazapay-payment-gateway.php');
				tcpg_seller_info_install();   //DROP TABLE FOR INSERTING FRESH SELLER INFORMATION
				tcpg_country_config_install();	//DROP TABLE FOR INSERTING FRESH SELLER COUNTRY INFORMATION
				$sellerData = array();
				$countryConfigData = array();
				$gerSellerInfo = (object)array();
				$sellerInfo = $this->tcpg_request_api_getuser($seller_email);
				if($sellerInfo->status == 'success')
				{
					//*******START TO ADD SELLER INFO*****//
					$gerSellerInfo->info = $sellerInfo;
					$insert_seller_id = $wpdb->insert(
						$table_seller_info,
						array(
							'account_id' => $sellerInfo->data->id,
							'company_name' => $sellerInfo->data->company_name,
							'first_name' => $sellerInfo->data->first_name,
							'last_name' => $sellerInfo->data->last_name,
							'email' => $sellerInfo->data->email,
							'country' => $sellerInfo->data->country,
							'country_code' => $sellerInfo->data->country_code,
							'contact_code' => $sellerInfo->data->contact_code,
							'contact_number' => $sellerInfo->data->contact_number,
							'customer_id' => $sellerInfo->data->customer_id,
							'ind_bus_type' => $sellerInfo->data->ind_bus_type,
							'status' => $sellerInfo->status
						),
						array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
					);
					$seller_info_id = $wpdb->insert_id;

					//*******END TO ADD SELLER INFO*****//

					//*******START TO ADD SELLER COUNTRY INFO*****//

					$country_config_type = array('buyer_countries','seller_countries');
					$sellerCountryConfig = $this->tcpg_request_api_countryconfig($sellerInfo->data->country_code);
					if($sellerCountryConfig->status == 'success')
					{
						$gerSellerInfo->seller_country = $sellerCountryConfig;
						$market_type = $sellerCountryConfig->data->market_type;
						$status = $sellerCountryConfig->status;
						$convertArrayCountry = (array)$sellerCountryConfig->data;
						for($i = 0; $i <= count($country_config_type)-1; $i++)
						{
							if(isset($convertArrayCountry[$country_config_type[$i]]))
							{
								$country = $convertArrayCountry[$country_config_type[$i]];
								foreach($country as $value)
								{
									$wpdb->insert($table_country_config,
									array(
										'seller_info_id' => $seller_info_id,
										'market_type' => $market_type,
										'country_config_type' => $country_config_type[$i],
										'country_config_code' => $value,
										'status' => $status,
									),
									array('%s', '%s', '%s', '%s', '%s')
								);
								}
							}
						}
					}
					//*******END TO ADD SELLER COUNTRY INFO*****//
					unset($_SESSION['seller_info']);
					$_SESSION['seller_info'] = $gerSellerInfo;
					return $gerSellerInfo;
				}
			}
		}
	}

	/*
	*THIS FUNCTION IS BEING CALLED FOR CONVERTING IN OBJECT FROM ARRAY
	*/
	public function convertInObject($sellerinfo = "")
	{
		$gerSellerInfo = (object)array();
		$gerSellerInfo2 = (object)array();
		$gerSellerInfo3 = (object)array();
		$gerSellerInfo->status = $sellerinfo->status;
		$gerSellerInfo2->id = $sellerinfo->account_id;
		$gerSellerInfo2->company_name = $sellerinfo->company_name;
		$gerSellerInfo2->first_name = $sellerinfo->first_name;
		$gerSellerInfo2->last_name = $sellerinfo->last_name;
		$gerSellerInfo2->email = $sellerinfo->email;
		$gerSellerInfo2->country = $sellerinfo->country;
		$gerSellerInfo2->country_code = $sellerinfo->country_code;
		$gerSellerInfo2->contact_code = $sellerinfo->contact_code;
		$gerSellerInfo2->contact_number = $sellerinfo->contact_number;
		$gerSellerInfo2->customer_id = $sellerinfo->customer_id;
		$gerSellerInfo2->ind_bus_type = $sellerinfo->ind_bus_type;
		$gerSellerInfo->data = $gerSellerInfo2;
		$gerSellerInfo3->info = $gerSellerInfo;

		//------------------------------Seller country config--------------------
		global $wpdb;
		$id = $sellerinfo->id;
		$table_country_config = $wpdb->prefix . 'tazapay_seller_country_config';
		$results = $wpdb->get_results("SELECT * FROM $table_country_config WHERE  `seller_info_id` = '$id'");
		if(!empty($results))
		{
			$getCountry = (object)array();
			$getCountry2 = (object)array();
			$array = array();
			$buyer_countries = array();
			$seller_countries = array();
			$country_config_type = array('buyer_countries','seller_countries');
				foreach($results as $value)
				{
					$getCountry->status = $value->status;
					$getCountry2->market_type = $value->market_type;
					if($value->country_config_type == 'buyer_countries')
					{
						array_push($buyer_countries,$value->country_config_code);
					}
					elseif($value->country_config_type == 'seller_countries')
					{
						array_push($seller_countries,$value->country_config_code);
					}
				}
				
				$getCountry2->buyer_countries = $buyer_countries;
				$getCountry2->seller_countries = $seller_countries;
				$getCountry->data = $getCountry2;
				$gerSellerInfo3->seller_country = $getCountry;
		}
		return $gerSellerInfo3;
	}

	/*
		    * Plugin options
	*/
	public function create_taza_logs($msg = '') {
		$t = microtime(true);
		$micro = sprintf("%06d", ($t - floor($t)) * 1000000);
		$d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
		$time = $d->format("m/d/Y H:i:s v") . 'ms ';

		$txt = $time . " > {$msg}";
		@file_put_contents('tazapay_logs.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

	}

	public function init_form_fields() {
		global $woocommerce, $wpdb;

		$countries_obj = new WC_Countries();
		$countries = $countries_obj->__get('countries');
		$text1 = __('Production mode is used for LIVE transactions, Sandbox mode can be used for testing', 'wc-tp-payment-gateway');
		$text2 = __('Request Credentials', 'wc-tp-payment-gateway');
		$text5 = 'Please input the Sandbox API Key received from Tazapay';
		$text6 = 'Please input the Sandbox API Secret Key received from Tazapay';
		$text7 = 'Please input the Production API Key received from Tazapay';
		$text8 = 'Please input the Production API Secret Key received from Tazapay';

		if ($this->get_option('sandboxmode') === 'sandbox') {
			$wrongApiKey = $this->isRegistered("1101", "Wrong API Key. $text5");
			if (!empty($wrongApiKey)) {
				$text5 = __('<strong style="color:red;">' . $wrongApiKey . '</strong>', 'wc-tp-payment-gateway');
			}
			$wrongApiSecret = $this->isRegistered("1102", "Wrong API Secret Key . $text6");
			if (!empty($wrongApiSecret)) {
				$text6 = __('<strong style="color:red;">' . $wrongApiSecret . '</strong>', 'wc-tp-payment-gateway');
			}

			$text3 = __('Request Sandbox credentials for accepting payments via Tazapay. Signup now and go to \'Request API Key\'', 'wc-tp-payment-gateway');
			$signupurl = 'https://sandbox.tazapay.com/signup';
		} else {
			$wrongApiKey = $this->isRegistered("1101", "Wrong API Key. $text7");
			if (!empty($wrongApiKey)) {
				$text7 = __('<strong style="color:red;">' . $wrongApiKey . '</strong>', 'wc-tp-payment-gateway');
			}
			$wrongApiSecret = $this->isRegistered("1102", "Wrong API Secret Key . $text8");
			if (!empty($wrongApiSecret)) {
				$text8 = __('<strong style="color:red;">' . $wrongApiSecret . '</strong>', 'wc-tp-payment-gateway');
			}

			$text3 = __('Request Production credentials for accepting payments via Tazapay. Signup now and go to \'Request API Key\'', 'wc-tp-payment-gateway');
			$signupurl = 'https://app.tazapay.com/signup';
		}

		//Seller credential is being checked
		$text4 = 'Please input the email ID which you used to signup with Tazapay';
		$wrongEmail = $this->isRegistered("1316", "Wrong email ID. $text4");
		if (!empty($wrongEmail)) {
			$text4 = __('<strong style="color:red;">' . $wrongEmail . '</strong>', 'wc-tp-payment-gateway');
		}

		if (is_plugin_active('dokan-lite/dokan.php')) {
			$activeplugin = array(
				'dokan' => __('Dokan', 'wc-tp-payment-gateway'),
			);
		} else if (is_plugin_active('wc-vendors/class-wc-vendors.php')) {
			$activeplugin = array(
				'wc-vendors' => __('WC Vendors', 'wc-tp-payment-gateway'),
			);
		} else if (is_plugin_active('wc-multivendor-marketplace/wc-multivendor-marketplace.php')) {
			$activeplugin = array(
				'wcfm-marketplace' => __('WCFM Marketplace', 'wc-tp-payment-gateway'),
			);
		} else {
			$activeplugin = array(
				'' => __('No Marketplace Plugin Active', 'wc-tp-payment-gateway'),
			);
		}

		$this->form_fields = array(
			'title' => array(
				'title' => __('Title', 'wc-tp-payment-gateway'),
				'type' => 'text',
				'description' => __('Payment method title', 'wc-tp-payment-gateway'),
				'default' => 'Pay Now, Release Later',
				'class' => '',
			),
			'sandboxmode' => array(
				'title' => __('Select Mode', 'wc-tp-payment-gateway'),
				'label' => __('Select Mode', 'wc-tp-payment-gateway'),
				'type' => 'select',
				'options' => array(
					'production' => __('Production', 'wc-tp-payment-gateway'),
					'sandbox' => __('Sandbox', 'wc-tp-payment-gateway'),
				),
				'description' => __($text1 . '<br><br><a href="' . esc_url($signupurl) . '" class="tz-signupurl button-primary" target="_blank" title="Request credentials for accepting payments via Tazapay">' . $text2 . '</a><p class="description signup-help-text">' . $text3 . '</p>', 'wc-tp-payment-gateway'),
				'default' => 'production',
				'class' => '',
			),
			'sandbox_api_key' => array(
				'title' => __('Sandbox API Key', 'wc-tp-payment-gateway'),
				'type' => 'password',
				'description' => __($text5, 'wc-tp-payment-gateway'),
				'class' => 'tazapay-sandbox',
			),
			'sandbox_api_secret_key' => array(
				'title' => __('Sandbox API Secret Key', 'wc-tp-payment-gateway'),
				'type' => 'password',
				'description' => __($text6, 'wc-tp-payment-gateway'),
				'class' => 'tazapay-sandbox',
			),
			'live_api_key' => array(
				'title' => __('Production API Key', 'wc-tp-payment-gateway'),
				'type' => 'password',
				'description' => __($text7, 'wc-tp-payment-gateway'),
				'class' => 'tazapay-production',
			),
			'live_api_secret_key' => array(
				'title' => __('Production API Secret Key', 'wc-tp-payment-gateway'),
				'type' => 'password',
				'description' => __($text8, 'wc-tp-payment-gateway'),
				'class' => 'tazapay-production',
			),
			'seller_email' => array(
				'title' => __('Email', 'wc-tp-payment-gateway'),
				'type' => 'text',
				'description' => __($text4, 'wc-tp-payment-gateway'),
				'class' => 'tazapay-singleseller',
			),
			'tazapay_seller_type' => array(
				'title' => __('Platform Type', 'wc-tp-payment-gateway'),
				'type' => 'select',
				'options' => array(
					'singleseller' => __('Single seller', 'wc-tp-payment-gateway'),
					'multiseller' => __('Multi Seller', 'wc-tp-payment-gateway'),
				),
				'description' => __('Select Multi Seller if you have other sellers on your platform, keep Single Seller if you are the only seller on the platform', 'wc-tp-payment-gateway'),
				'class' => 'tazapay-seller-type',
			),
			'tazapay_multi_seller_plugin' => array(
				'title' => __('Vendor Plugin Name', 'wc-tp-payment-gateway'),
				'type' => 'select',
				'options' => $activeplugin,
				'description' => __('Please select the plugin you use to manage vendors (sellers) on your platform', 'wc-tp-payment-gateway'),
				'class' => 'tazapay-multiseller',
			),
			'seller_id' => array(
				'type' => 'hidden',
				'class' => 'tazapay-singleseller',
			),
		);
	}

	public function tcpg_getuser_info_options() {

		//	$this->create_taza_logs("Start > Get User API> tcpg_getuser_info_options > /v1/user/");

		$getSellerInfo = $this->save_seller_information($this->get_option('seller_email'));

		//$getuserapi = $this->tcpg_request_api_getuser($this->get_option('seller_email'));
		$getuserapi = $getSellerInfo->info;

		if (!empty($getuserapi->data->id)) {
			$seller_account_id = $getuserapi->data->id;
			$woocommerce_tz_tazapay_settings = get_option('woocommerce_tz_tazapay_settings');
			$woocommerce_tz_tazapay_settings['seller_id'] = $seller_account_id;

			update_option('woocommerce_tz_tazapay_settings', $woocommerce_tz_tazapay_settings);
		} else {
			//foreach ($getuserapi->errors as $key => $error) {
			//if (isset($error->message)) {
			?>
                    <!-- <div class="notice notice-error is-dismissible">
                        <p><?php //esc_html_e($error->message, 'wc-tp-payment-gateway'); ?></p>
                    </div> -->
            <?php
//}
			//}
		}
	}

	/*
		    * This plugin will wordk to refund payment which payment has been done
		    * Pass transaction ID to refund amount to buyer
	*/
	public function process_refund($orderId, $amount = null, $reason = 'refund') {

		//$this->create_taza_logs("Start > Refund process_refund ({$orderId})");
		$order = wc_get_order($orderId);
		$txn_no = get_post_meta($orderId, 'txn_no', true);
		if (!$order or !$txn_no) {
			return new WP_Error('error', __('Refund failed: No transaction ID', 'woocommerce'));
		}

		if ($order->get_payment_method() == 'tz_tazapay') {
			$convamount = floatval($amount);
			$refundArg = array(
				'txn_no' => $txn_no,
				'amount' => $convamount,
				'callback_url' => $this->callBackUrl,
				'remarks' => $reason,
			);

			$api_endpoint = "/v1/payment/refund/request";
			$api_url = $this->base_api_url . '/v1/payment/refund/request';
			$result = $this->tcpg_request_apicall($api_url, $api_endpoint, $refundArg, $orderId);

			if (!empty($result)) {
				if ($result->status == 'error') {
					$message = $result->errors[0]->message;
					return new WP_Error('error', __($message, 'woocommerce'));
				}

				//Success
				if ($result->status == 'created') {
					$reference_id = $result->data->reference_id;
					//$reference_id = json_encode($result);
					update_post_meta($orderId, 'reference_id', $reference_id);
					//$order->update_status( $order->update_status( 'refunded', __( 'Paid order refunded.', 'woocommerce' ) ) );
				}
			}
		}

		//$this->create_taza_logs("End > Refund process_refund ({$orderId})");

		return true;
	}

	function webhook() {

		//$this->create_taza_logs("Start > webhook");

		header('HTTP/1.1 200 OK');
		$respnse = json_decode(file_get_contents("php://input"), true);

		if ($respnse['state'] == 'Payment_Received')
		{
			$txn_no = $respnse['txn_no'];
			$post_id = tazapay_post_id_by_meta_key_and_value('txn_no', $txn_no);
			$my_post = array(
				'ID' => $post_id,
				'post_status' => 'wc-processing',
			);
			wp_update_post($my_post);
		}
		
		if ($respnse['status'] == 'requested') {
			$reference_id = $respnse['reference_id'];
			$post_id = tazapay_post_id_by_meta_key_and_value('reference_id', $reference_id);
			$my_post = array(
				'ID' => $post_id,
				'post_status' => 'wc-processing',
			);
			wp_update_post($my_post);
		}

		if ($respnse['status'] == 'approved') {
			$reference_id = $respnse['reference_id'];
			$post_id = tazapay_post_id_by_meta_key_and_value('reference_id', $reference_id);
			$post = get_post($post_id);
			$post->post_status = "wc-refunded";
			wp_update_post($post);
			$data = array(
				'comment_post_ID' => $post_id,
				'comment_author' => 'WooCommerce',
				'comment_author_email' => 'woocommerce@democenter.net',
				'comment_author_url' => '',
				'comment_content' => 'Refund request has been approved, reference id ' . $reference_id,
				'comment_author_IP' => '',
				'comment_agent' => 'WooCommerce',
				'comment_type' => 'order_note',
				'comment_date' => date('Y-m-d H:i:s'),
				'comment_date_gmt' => date('Y-m-d H:i:s'),
				'comment_approved' => 1,
			);
			$comment_id = wp_insert_comment($data);
		}

		if ($respnse['status'] == 'rejected') {
			$reference_id = $respnse['reference_id'];
			$post_id = tazapay_post_id_by_meta_key_and_value('reference_id', $reference_id);
			$post = get_post($post_id);
			$post->post_status = "wc-processing";
			wp_update_post($post);
			//Delete row when rejected
			$args = array(
				'posts_per_page' => 1,
				'order' => 'DESC',
				'post_parent' => $post_id,
				'post_type' => 'shop_order_refund',
			);

			$get_children_array = get_children($args, ARRAY_A);
			if (!empty($get_children_array)) {
				foreach ($get_children_array as $post) {
					wp_delete_post($post['ID'], true);
				}
			}
			//End delete
			$data = array(
				'comment_post_ID' => $post_id,
				'comment_author' => 'WooCommerce',
				'comment_author_email' => 'woocommerce@democenter.net',
				'comment_author_url' => '',
				'comment_content' => 'Refund request has been cancelled',
				'comment_author_IP' => '',
				'comment_agent' => 'WooCommerce',
				'comment_type' => 'order_note',
				'comment_date' => date('Y-m-d H:i:s'),
				'comment_date_gmt' => date('Y-m-d H:i:s'),
				'comment_approved' => 1,
			);
			$comment_id = wp_insert_comment($data);
		}

		//$this->create_taza_logs("End > webhook");
		die();
	}

	public function update_status($post_id) {

		//$this->create_taza_logs("Start > Refund update_status ({$post_id})");

		$txn_no = get_post_meta($post_id, 'txn_no', true);
		$paymentMethod = get_post_meta($post_id, '_payment_method', true);
		$payment_title = get_post_meta($post_id, '_payment_method_title', true);
		if (!empty($txn_no) && $paymentMethod == 'tz_tazapay') {

			//$tcpg_request_api_orderstatus = new TCPG_Gateway();
			$getEscrowstate = $this->tcpg_request_api_orderstatus($txn_no);
			$getRefundStatus = $this->tcpg_request_api_refundstatus($txn_no);

			if ($getRefundStatus->data[0]->status == 'approved') {
				updatePostStatus($post_id, 'wc-refunded'); //Refund status
			} elseif ($getRefundStatus->data[0]->status == 'requested') {
				updatePostStatus($post_id, 'wc-processing'); //Refund status
			} elseif ($getRefundStatus->data[0]->status == 'rejected') {
				updatePostStatus($post_id, 'wc-processing'); //Refund status
			} else {

			}
		}

		//$this->create_taza_logs("End > Refund update_status ({$post_id}) \n");
	}

	/*
		    * Get escrow status by txn_no
	*/

	public function tcpg_authentication(){

		$apiKey = $this->live_api_key;
		$apiSecret = $this->live_api_secret_key;
		$basic_auth = $apiKey . ':' . $apiSecret;
		$authentication = "Basic " . base64_encode($basic_auth);

		return $authentication;
	}

	public function tcpg_request_api_refundstatus($txn_no) {
		
		$method = "GET";
		$APIEndpoint = "/v1/payment/refund/status";
		$api_url = $this->base_api_url;

		$authentication = $this->tcpg_authentication();

		$response = wp_remote_post(
			esc_url_raw($api_url) . $APIEndpoint . '?txn_no=' . $txn_no,
			array(
				'method' => 'GET',
				'sslverify' => false,
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

	/*
		    * Get phone code
		    * @return string
	*/
	public function tcpg_getphonecode($countryCode) {
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

	/**
	 * You will need it if you want your custom form
	 */
	public function payment_fields() {
	}

	/*
		    * Fields validation
	*/
	public function validate_fields() {
		if (sanitize_email($_POST['billing_email']) == $this->get_option('seller_email')) {
			wc_add_notice('Buyer and seller email should not be identical, Please change buyer email address.', 'error');
			return false;
		}
		return true;
	}

	/*
		    * Api call
	*/
	public function tcpg_request_apicall($api_url, $api_endpoint, $args, $order_id) {

		$method = "POST";
		$APIEndpoint = $api_endpoint;

		$authentication = $this->tcpg_authentication();

		$json = json_encode($args);
		$json = str_replace('"invoice_amount":"' . $args['invoice_amount'] . '"', '"invoice_amount":' . $args['invoice_amount'] . '', $json);
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

			$api_array = json_decode(wp_remote_retrieve_body($response));
		}

		//$this->create_taza_logs("End > Request apicall \n");
		return $api_array;
	}

	/*
		    * Get invoice currency api
	*/
	public function tcpg_request_api_invoicecurrency($buyer_country, $seller_country) {
		
		$method = "GET";
		$APIEndpoint = "/v1/metadata/invoicecurrency";
		$api_url = $this->base_api_url;

		$authentication = $this->tcpg_authentication();

		$response = wp_remote_post(
			esc_url_raw($api_url) . $APIEndpoint . '?buyer_country=' . $buyer_country . '&seller_country=' . $seller_country,
			array(
				'method' => 'GET',
				'sslverify' => false,
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

		//$this->create_taza_logs("End > Get Invoice Currency {$buyer_country} - {$seller_country}");

		return $api_array;
	}

	/*
		    * Get user api
	*/
	public function tcpg_request_api_getuser($emailoruuid) {
		
		$method = "GET";
		$APIEndpoint = "/v1/user/" . $emailoruuid;
		$api_url = $this->base_api_url;

		$authentication = $this->tcpg_authentication();

		$response = wp_remote_post(
			esc_url_raw($api_url) . $APIEndpoint,
			array(
				'method' => 'GET',
				'sslverify' => false,
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

		//$this->create_taza_logs("End > Get User \n");

		return $api_array;
	}

	/*
		    * Country config api
	*/
	public function tcpg_request_api_countryconfig($country_code) {

		$method = "GET";
		$APIEndpoint = "/v1/metadata/countryconfig";
		$api_url = $this->base_api_url;

		$authentication = $this->tcpg_authentication();

		$response = wp_remote_post(
			esc_url_raw($api_url) . $APIEndpoint . '?country=' . $country_code,
			array(
				'method' => 'GET',
				'sslverify' => false,
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

		//$this->create_taza_logs("End > Get User Country Code \n");
		return $api_array;
	}

	/*
		    * Get escrow status by txn_no
	*/
	public function tcpg_request_api_orderstatus($txn_no) {
	
		$method = "GET";
		$APIEndpoint = "/v1/escrow/" . $txn_no;
		$api_url = $this->base_api_url;

		$authentication = $this->tcpg_authentication();

		$response = wp_remote_post(
			esc_url_raw($api_url) . $APIEndpoint,
			array(
				'method' => 'GET',
				'sslverify' => false,
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

	/*
		    * We're processing the payments here
	*/
	public function process_payment($order_id) {
		global $woocommerce, $wpdb;

		//$this->create_taza_logs("Start > Payment process ({$order_id})");

		$getsellerapi = $this->seller_data;

		$order = wc_get_order($order_id);
		$account_id = "";
		$user_email = $order->get_billing_email();
		$phoneCode = $this->tcpg_getphonecode($order->get_billing_country());
		$tablename = $wpdb->prefix . 'tazapay_user';
		$site_currency = get_option('woocommerce_currency');
		// for multiseller
		if ($this->tazapay_seller_type == 'multiseller') {

			foreach (WC()->cart->get_cart() as $cart_item) {
				$item_name = $cart_item['data']->get_title();
				$product_id = $cart_item['data']->get_id();
				$vendor_id = get_post_field('post_author', $product_id);
				$vendor = get_userdata($vendor_id);
				$seller_email[] = sanitize_email($vendor->user_email);
			}
			$seller_email = array_unique($seller_email);
			$sellercount = count($seller_email);
			$blogusers = get_users('role=administrator');

			foreach ($blogusers as $user) {
				$admin_email = sanitize_email($user->user_email);
			}

			if ($sellercount == 1 && $seller_email[0] == $admin_email) {

				//$this->create_taza_logs("Start > Get User API> process_payment multiseller (seller email)> /v1/user/");

				$getsellerapi = $this->tcpg_request_api_getuser($this->seller_email);
			} else {

				//	$this->create_taza_logs("Start > Get User API> process_payment multiseller (seller email)> /v1/user/");

				$getsellerapi = $this->tcpg_request_api_getuser($seller_email[0]);
			}
		} else {

			/*$this->create_taza_logs("Start > Get User API> process_payment Single seller (User email)> /v1/user/");
				$getuserapi = $this->tcpg_request_api_getuser($user_email);

				$this->create_taza_logs("Start > Get User API> process_payment Single seller (seller email)> /v1/user/");
			*/

		}
/*
if ($getuserapi->status == 'success') {
$buyer_country = $getuserapi->data->country_code;
$buyer_country_name = $getuserapi->data->country;
} else {
$buyer_country = $order->get_billing_country();
$buyer_country_name = WC()->countries->countries[$order->get_billing_country()];
}*/

		$buyer_country = $order->get_billing_country();
		$buyer_country_name = WC()->countries->countries[$order->get_billing_country()];

		//$this->create_taza_logs("Start > Get user country code > process_payment> /v1/metadata/countryconfig");

		//$countryconfig = $this->tcpg_request_api_countryconfig($getsellerapi->data->country_code);

		if ($this->countryconfig->status == 'success' && in_array($buyer_country, $this->countryconfig->data->buyer_countries)) {

			$invoice_currency_check = $this->tcpg_request_api_invoicecurrency($buyer_country, $getsellerapi->data->country_code);
			$store_currency = get_woocommerce_currency();

			if ($invoice_currency_check->status == 'success' && in_array($store_currency, $invoice_currency_check->data->currencies)) {
				$invoice_currency = true;
			} else {
				$message = __('Transactions between buyers from ' . esc_html($buyer_country_name) . ' and sellers from ' . esc_html($getsellerapi->data->country) . ' are currently not supported in ' . esc_html($site_currency) . '', 'wc-tp-payment-gateway');
				wc_add_notice($message, 'error');
			}
		} else {
			$country_config_message = __('Transactions between buyers from ' . esc_html($buyer_country_name) . ' and sellers from ' . esc_html($getsellerapi->data->country) . ' are currently not supported', 'wc-tp-payment-gateway');
			wc_add_notice($country_config_message, 'error');
		}

		if ($invoice_currency == true) {

			$seller_id = isset($getsellerapi->data->id) ? sanitize_text_field($getsellerapi->data->id) : '';

			foreach (WC()->cart->get_cart() as $cart_item) {
				$item_name = $cart_item['data']->get_title();
				$quantity = $cart_item['quantity'];
				$items[] = $quantity . ' x ' . $item_name;
			}

			$listofitems = implode(', ', $items);
			$description = get_bloginfo('name') . ' : ' . $listofitems;
			$checkoutArgs = array(
				"buyer" => array(
					"email" => $order->get_billing_email(),
					"country" => $order->get_billing_country(),
					"ind_bus_type" => "Individual",
					"first_name" => $order->get_billing_first_name(),
					"last_name" => $order->get_billing_last_name(),
					"contact_code" => $phoneCode,
					"contact_number" => $order->get_billing_phone(),
				),
				"seller_id" => $seller_id,
				"fee_paid_by" => "buyer",
				"invoice_currency" => get_option('woocommerce_currency'),
				"invoice_amount" => $order->get_total(),
				"txn_description" => $description,
				"callback_url" => $this->callBackUrl,
				"complete_url" => $this->get_return_url($order),
				"error_url" => $this->get_return_url($order),
			);
			$api_endpoint = "/v1/checkout";
			$api_url = $this->base_api_url . '/v1/checkout';
			$result = $this->tcpg_request_apicall($api_url, $api_endpoint, $checkoutArgs, $order_id);

			if ($result->status == 'error') {
				$payment_msg = "";
				foreach ($result->errors as $key => $error) {
					if (isset($error->message)) {
						$payment_msg .= " " . esc_html($error->message);
					}
				}
				$order->add_order_note($payment_msg, true);
				return wc_add_notice($payment_msg, 'error');
			}

			if ($result->status == 'success') {

				$tablename = $wpdb->prefix . 'tazapay_user';
				$account_id = isset($result->data->buyer->id) ? sanitize_text_field($result->data->buyer->id) : '';

				$user_results = $wpdb->get_results("SELECT account_id FROM $tablename WHERE email = '" . $order->get_billing_email() . "' AND environment = '" . $this->environment . "'");

				$db_account_id = isset($user_results[0]->account_id) ? sanitize_text_field($user_results[0]->account_id) : '';

				if (empty($db_account_id)) {
					$wpdb->insert(
						$tablename,
						array(
							'account_id' => $account_id,
							'user_type' => "buyer",
							'email' => $order->get_billing_email(),
							'first_name' => $order->get_billing_first_name(),
							'last_name' => $order->get_billing_last_name(),
							'contact_code' => $phoneCode,
							'contact_number' => $order->get_billing_phone(),
							'country' => $order->get_billing_country(),
							'ind_bus_type' => "Individual",
							'business_name' => "",
							'environment' => $this->environment,
							'created' => current_time('mysql'),
						),
						array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
					);
				}

				update_post_meta($order_id, 'account_id', $account_id);
				update_user_meta($userId, 'account_id', $account_id);
				update_user_meta($userId, 'first_name', $order->get_billing_first_name());
				update_user_meta($userId, 'last_name', $order->get_billing_last_name());
				update_user_meta($userId, 'contact_code', $phoneCode);
				update_user_meta($userId, 'contact_number', $order->get_billing_phone());
				update_user_meta($userId, 'ind_bus_type', 'Individual');
				update_user_meta($userId, 'created', current_time('mysql'));
				update_user_meta($userId, 'environment', $this->environment);
				update_post_meta($order_id, 'txn_no', $result->data->txn_no);

				$redirect_url = $result->data->redirect_url;
				$order->update_status('wc-on-hold', __('Awaiting offline payment', 'wc-tp-payment-gateway'));
				$order->reduce_order_stock();
				$woocommerce->cart->empty_cart();

				update_post_meta($order_id, 'redirect_url', $redirect_url);

				//$this->create_taza_logs("End > Payment Process ({$order_id}) \n");

				return array(
					'result' => 'success',
					'redirect' => esc_url($redirect_url),
				);

				$payment_api_endpoint = "/v1/session/payment";
				$api_url = $this->base_api_url . '/v1/session/payment';
				$result_payment = $this->tcpg_request_apicall($api_url, $payment_api_endpoint, $argsPayment, $order_id);

				if ($result_payment->status == 'error') {
					$payment_msg = "";
					$payment_msg = $result_payment->message;
					foreach ($result_payment->errors as $key => $error) {

						if (isset($error->code)) {
							$payment_msg .= ", code: " . esc_html($error->code);
						}
						if (isset($error->message)) {
							$payment_msg .= ", Message: " . esc_html($error->message);
						}
						if (isset($error->remarks)) {
							$payment_msg .= ", Remarks: " . esc_html($error->remarks);
						}
					}
					$order->add_order_note($payment_msg, true);

					return array(
						'result' => 'success',
						'redirect' => $this->get_return_url($order),
					);
				}

				if ($result_payment->status == 'success') {

					$redirect_url = $result_payment->data->redirect_url;
					$order->update_status('wc-on-hold', __('Awaiting offline payment', 'wc-tp-payment-gateway'));
					//$order->reduce_order_stock();
                    wc_reduce_stock_levels($order_id);
					$woocommerce->cart->empty_cart();

					update_post_meta($order_id, 'redirect_url', $redirect_url);

					//$this->create_taza_logs("End > Payment Process ({$order_id}) \n");

					return array(
						'result' => 'success',
						'redirect' => esc_url($redirect_url),
					);
				}
			}
		}
	}

	public function tcpg_add_payment_column_to_myaccount($columns) {
		$new_columns = [];

		foreach ($columns as $key => $name) {
			$new_columns[$key] = $name;

			if ('order-actions' === $key) {
				$new_columns['pay-order'] = __('Payment', 'wc-tp-payment-gateway');
			}
		}
		return $new_columns;
	}

	public function tcpg_add_pay_for_order_to_payment_column_myaccount($order) {

		if (in_array($order->get_status(), array('pending', 'on-hold'))) {

			$payment_url = get_post_meta($order->get_id(), 'redirect_url', true);

			if (isset($payment_url) && !empty($payment_url)) {
				printf('<a class="woocommerce-button button pay" href="%s">%s</a>', esc_url($payment_url), __("Pay With Escrow", "wc-tp-payment-gateway"));
			}
		}
	}

	public function tcpg_get_private_order_notes($order_id) {
		global $wpdb;

		$table_perfixed = $wpdb->prefix . 'comments';
		$results = $wpdb->get_results("SELECT * FROM $table_perfixed WHERE  `comment_post_ID` = $order_id AND `comment_type` LIKE  'order_note'");

		if (is_array($results) && count($results) > 0) {
			foreach ($results as $note) {
				$order_note[] = array(
					'note_id' => isset($note->comment_ID) ? esc_html($note->comment_ID) : '',
					'note_date' => isset($note->comment_date) ? esc_html($note->comment_date) : '',
					'note_author' => isset($note->comment_author) ? esc_html($note->comment_author) : '',
					'note_content' => isset($note->comment_content) ? esc_html($note->comment_content) : '',
				);
			}
		}
		return $order_note;
	}

	public function tcpg_view_order_and_thankyou_page($order_id) {
		$order = wc_get_order($order_id);
		$paymentMethod = get_post_meta($order_id, '_payment_method', true);

		if ($paymentMethod == 'tz_tazapay') {

			$user_email = $order->get_billing_email();
			$txn_no = get_post_meta($order_id, 'txn_no', true);
			$redirect_url = get_post_meta($order_id, 'redirect_url', true);

			global $wpdb;
			$tablename = $wpdb->prefix . 'tazapay_user';

			?>
            <h2><?php esc_html_e('Transaction Details', 'wc-tp-payment-gateway');?></h2>
            <p><?php esc_html_e('Pay Now, Release Later powered by Tazapay', 'wc-tp-payment-gateway');?></p>
            <table class="woocommerce-table shop_table gift_info">
                <tfoot>
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
                    <tr>
                        <th scope="row"><?php esc_html_e('Payment status', 'wc-tp-payment-gateway');?></th>
                        <td>
                            <?php
$getEscrowstate = $this->tcpg_request_api_orderstatus($txn_no);

			if (isset($_POST['order-status']) && !empty($getEscrowstate->data->state) && !empty($getEscrowstate->data->sub_state)) {
				?>
                                <p><strong><?php esc_html_e('Escrow state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->state, 'wc-tp-payment-gateway');?></p>
                                <p><strong><?php esc_html_e('Escrow sub_state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->sub_state, 'wc-tp-payment-gateway');?></p>
                            <?php
}
			?>
                            <form method="post" name="tazapay-order-status" action="">
                                <input type="submit" name="order-status" value="Refresh Status">
                            </form>
                            <?php

			if (isset($getEscrowstate->status) && $getEscrowstate->status == 'success' && ($getEscrowstate->data->state == 'Payment_Received' || $getEscrowstate->data->sub_state == 'Payment_Done')) {

				$order->update_status('processing');

				if ($getEscrowstate->data->state == 'Payment_Received') {
					esc_html_e('Completed', 'wc-tp-payment-gateway');
				}
				if ($getEscrowstate->data->sub_state == 'Payment_Done') {
					esc_html_e('Completed', 'wc-tp-payment-gateway');
				}
			} else {
				printf('<a class="woocommerce-button button pay" href="%s">%s</a>', esc_url($redirect_url), __("Pay With Escrow", "wc-tp-payment-gateway"));
			}
			?>
                        </td>
                    </tr>
                </tfoot>
            </table>
            <?php

			$order_notes = $this->tcpg_get_private_order_notes($order_id);
			if (isset($order_notes) && count($order_notes) > 1) {
				foreach ($order_notes as $note) {
					$note_date = esc_html($note['note_date']);
					$note_content = esc_html($note['note_content']);
					?>
                    <p><strong><?php esc_html_e(date('F j, Y h:i A', strtotime($note_date)), 'wc-tp-payment-gateway');?></strong><?php esc_html_e($note_content, 'wc-tp-payment-gateway');?></p>
            <?php
}
			}
		}
	}

	/*
		    * Add custom tazapay icons to WooCommerce Checkout Page
	*/
	public function tcpg_woocommerce_icons($icon, $id) {
		if ($id === 'tz_tazapay') {

			$logo_url = TCPG_PUBLIC_ASSETS_DIR . "images/logo-dark.svg";
			$payment_methods = TCPG_PUBLIC_ASSETS_DIR . "images/payment_methods.svg";

			$icon = '<div class="tazapay-checkout-button"><div class="tazapay-payment-logo"><span><img src=' . esc_url($logo_url) . ' alt="tazapay" /></span>';
			$icon .= __('Pay securely with buyer protection', 'wc-tp-payment-gateway');
			$icon .= '</div><div class="tazapay-payment-method"><img src=' . esc_url($payment_methods) . ' alt="tazapay" class="tazapay-payment-method"/></div></div>';

			return $icon;
		} else {
			return $icon;
		}
	}

	/*
		    * Available payment gateways
	*/
	public function tcpg_woocommerce_available_payment_gateways($available_gateways) {
		global $woocommerce, $wpdb;

		if (!is_checkout()) {
			return $available_gateways;
		}

		if (array_key_exists('tz_tazapay', $available_gateways)) {
			$available_gateways['tz_tazapay']->order_button_text = __('Place Order and Pay', 'wc-tp-payment-gateway');
		}

		// for singleseller
		if (empty($this->seller_id) && $this->tazapay_seller_type == 'singleseller') {
			unset($available_gateways['tz_tazapay']);
		}

		if (empty($this->seller_id) && $this->tazapay_seller_type == 'multiseller') {
			unset($available_gateways['tz_tazapay']);
		}

		// for multiseller
		foreach (WC()->cart->get_cart() as $cart_item) {

			$item_name = $cart_item['data']->get_title();
			$product_id = $cart_item['data']->get_id();
			$vendor_id = get_post_field('post_author', $product_id);
			$vendor = get_userdata($vendor_id);
			$seller_email[] = sanitize_email($vendor->user_email);
		}

		$selleremail = array_unique($seller_email);
		$blogusers = get_users('role=administrator');
		foreach ($blogusers as $user) {
			$admin_email = sanitize_email($user->user_email);
		}

		$sellercount = count($selleremail);
		if (($sellercount > 1 && $this->tazapay_seller_type == 'singleseller') || ($sellercount > 1 && $this->tazapay_seller_type == 'multiseller')) {
			unset($available_gateways['tz_tazapay']);
		} else {

			if ($selleremail[0] == $admin_email) {
				// no code needed
			} else {
				$tablename = $wpdb->prefix . 'tazapay_user';
				$seller_results = $wpdb->get_results("SELECT * FROM $tablename WHERE email = '" . sanitize_email($selleremail[0]) . "' AND environment = '" . $this->environment . "'");
				$account_id = isset($seller_results[0]->account_id) ? $seller_results[0]->account_id : '';

				if ($this->tazapay_seller_type == 'multiseller' && $this->tazapay_multi_seller_plugin == 'dokan' && empty($account_id)) {
					unset($available_gateways['tz_tazapay']);
				}

				if ($this->tazapay_seller_type == 'multiseller' && $this->tazapay_multi_seller_plugin == 'wc-vendors' && empty($account_id)) {
					unset($available_gateways['tz_tazapay']);
				}

				if ($this->tazapay_seller_type == 'multiseller' && $this->tazapay_multi_seller_plugin == 'wcfm-marketplace' && empty($account_id)) {
					unset($available_gateways['tz_tazapay']);
				}
			}
		}

		return $available_gateways;
	}
	/*
		    * Tazapay order meta general
	*/
	public function tcpg_order_meta_general($order) {
		$account_id = get_post_meta($order->get_id(), 'account_id', true);
		$txn_no = get_post_meta($order->get_id(), 'txn_no', true);

		if (isset($account_id) && !empty($account_id)) {
			?>
            <br class="clear" />
            <h3><?php esc_html_e('Transaction Details', 'wc-tp-payment-gateway');?></h3>

            <div class="address">
                <p><strong><?php esc_html_e('TazaPay Account UUID: ', 'wc-tp-payment-gateway');?></strong> <?php esc_html_e($account_id, 'wc-tp-payment-gateway');?></p>
                <p><strong><?php esc_html_e('Transaction no: ', 'wc-tp-payment-gateway');?></strong> <?php esc_html_e($txn_no, 'wc-tp-payment-gateway');?></p>
                <?php
$getEscrowstate = $this->tcpg_request_api_orderstatus($txn_no);

			if (isset($_GET['order-status']) && !empty($getEscrowstate->data->state) && !empty($getEscrowstate->data->sub_state)) {
				?>
                    <p><strong><?php esc_html_e('Escrow state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->state, 'wc-tp-payment-gateway');?></p>
                    <p><strong><?php esc_html_e('Escrow sub_state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->sub_state, 'wc-tp-payment-gateway');?></p>
                <?php
}
			?>
                <a href="<?php echo esc_url($order->get_edit_order_url(), 'wc-tp-payment-gateway'); ?>&order-status=true" class="order-status-response button button-primary"><?php esc_html_e('Refresh Status', 'wc-tp-payment-gateway');?></a>
            </div>
    <?php
}
	}

	//This is checking that is this email id of seller known the user of tazapay
	public function isRegistered($errorcode, $message) {
		//$isSellerRegistered = $this->tcpg_request_api_getuser($this->get_option('seller_email'));
		$isSellerRegistered = $this->seller_data;
		if (isset($isSellerRegistered->errors[0]->code)) {
			if ($isSellerRegistered->errors[0]->code == $errorcode) {
				return $message;
			}
		}
	}
}

/*
 * Returns matched post IDs for a pair of meta key and meta value from database
 *
 * @param string $meta_key
 * @param mixed $meta_value
 *
 * @return array|int Post ID(s)
 */
function tazapay_post_id_by_meta_key_and_value($meta_key, $meta_value) {
	global $wpdb;

	$ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value));

	if (count($ids) > 1) {
		return $ids;
	}
	// return array
	else {
		return $ids[0];
	}
	// return int
}

// Jquery script that send the Ajax request
add_action('woocommerce_after_checkout_form', 'tcpg_custom_checkout_js_script');
function tcpg_custom_checkout_js_script() {
	$field_key = 'billing_country';

	WC()->session->__unset('field_' . $field_key);
	?>
    <script type="text/javascript">
        jQuery(function($) {
            if (typeof wc_checkout_params === 'undefined')
                return false;

            var field = '[name="<?php esc_html_e($field_key, 'wc-tp-payment-gateway');?>"]';

            $('form.checkout').on('input change', field, function() {
                $.ajax({
                    type: 'POST',
                    url: wc_checkout_params.ajax_url,
                    data: {
                        'action': 'targeted_checkout_field_change',
                        'field_key': '<?php esc_html_e($field_key, 'wc-tp-payment-gateway');?>',
                        'field_value': $(this).val(),
                    },
                    success: function(result) {
                        $(document.body).trigger('update_checkout');
                    },
                });
            });
        });
    </script>
    <?php
}

// The Wordpress Ajax PHP receiver
add_action('wp_ajax_targeted_checkout_field_change', 'tcpg_get_ajax_targeted_checkout_field_change');
add_action('wp_ajax_nopriv_targeted_checkout_field_change', 'tcpg_get_ajax_targeted_checkout_field_change');
function tcpg_get_ajax_targeted_checkout_field_change() {
	// Checking that the posted email is valid
	if (isset($_POST['field_key']) && isset($_POST['field_value'])) {

		// Set the value in a custom Woocommerce session
		WC()->session->set('field_' . sanitize_text_field($_POST['field_key']), sanitize_text_field($_POST['field_value']));

		// Return the session value to jQuery
		echo json_encode(WC()->session->get('field_' . sanitize_text_field($_POST['field_key']))); // For testing only
	}
	wp_die(); // always use die at the end
}

// Disable specific payment method if specif checkout field is set
add_filter('woocommerce_available_payment_gateways', 'tcpg_payment_gateway_disable_tazapay');
function tcpg_payment_gateway_disable_tazapay($available_gateways) {
	global $woocommerce;

	if (is_admin()) {
		return $available_gateways;
	}

	$buyer_country = "";
	$request_api_call = new TCPG_Gateway();
	$payment_id = 'tz_tazapay';
	$field_key = 'billing_country';
	$field_value = WC()->session->get('field_' . $field_key);
	$site_currency = get_option('woocommerce_currency');

	if (isset($available_gateways[$payment_id]) && !empty($field_value)) {

		if ($request_api_call->tazapay_seller_type == 'multiseller') {
			// for multiseller

			if (sizeof(WC()->cart->get_cart()) > 0) {
				foreach (WC()->cart->get_cart() as $cart_item) {
					$item_name = $cart_item['data']->get_title();
					$product_id = $cart_item['data']->get_id();
					$vendor_id = get_post_field('post_author', $product_id);
					$vendor = get_userdata($vendor_id);
					$seller_email[] = $vendor->user_email;
				}

				$seller_email = array_unique($seller_email);
				$sellercount = count($seller_email);

				$blogusers = get_users('role=administrator');
				foreach ($blogusers as $user) {
					$admin_email = sanitize_email($user->user_email);
				}

				if ($seller_email[0] == $admin_email) {
					$seller_email[0] = sanitize_email($request_api_call->seller_email);
				}

				if ($sellercount == 1) {

					//	$request_api_call->create_taza_logs("Start > Get User API> tcpg_payment_gateway_disable_tazapay  sellercount==1> /v1/user/");

					$getuserapi = $request_api_call->tcpg_request_api_getuser($seller_email[0]);

					//$request_api_call->create_taza_logs("Start > Get user country code > tcpg_payment_gateway_disable_tazapay> /v1/metadata/countryconfig");

					$countryconfig = $request_api_call->tcpg_request_api_countryconfig($getuserapi->data->country_code);

					if ($countryconfig->status == 'success' && in_array($field_value, $countryconfig->data->buyer_countries)) {

						$invoice_currency_check = $request_api_call->tcpg_request_api_invoicecurrency($field_value, $getuserapi->data->country_code);
						$store_currency = get_woocommerce_currency();

						if ($invoice_currency_check->status == 'success' && in_array($store_currency, $invoice_currency_check->data->currencies)) {
							// no code required.
						} else {
							unset($available_gateways[$payment_id]);
							$buyer_country = WC()->countries->countries[$field_value];
							$message = __('Transactions between buyers from ' . esc_html($buyer_country) . ' and sellers from ' . esc_html($getuserapi->data->country) . ' are currently not supported in ' . esc_html($site_currency) . '', 'wc-tp-payment-gateway');
							wc_add_notice($message, 'error');
						}
					} else {
						unset($available_gateways[$payment_id]);
						$buyer_country = WC()->countries->countries[$field_value];
						$country_config_message = __('Transactions between buyers from ' . esc_html($buyer_country) . ' and sellers from ' . esc_html($getuserapi->data->country) . ' are currently not supported', 'wc-tp-payment-gateway');
						wc_add_notice($country_config_message, 'error');
					}
				}
			}
		} else {

			$seller_email = sanitize_email($request_api_call->seller_email);

			//$request_api_call->create_taza_logs("Start > Get User API> tcpg_payment_gateway_disable_tazapay single seller> /v1/user/");

			$getuserapi = $request_api_call->tcpg_request_api_getuser($seller_email);

			//$request_api_call->create_taza_logs("Start > Get user country code > tcpg_payment_gateway_disable_tazapay> /v1/metadata/countryconfig");

			$countryconfig = $request_api_call->tcpg_request_api_countryconfig($getuserapi->data->country_code);

			if ($countryconfig->status == 'success' && in_array($field_value, $countryconfig->data->buyer_countries)) {

				//	$request_api_call->create_taza_logs("Start > Get Currency > tcpg_payment_gateway_disable_tazapay> /v1/metadata/invoicecurrency");

				$invoice_currency_check = $request_api_call->tcpg_request_api_invoicecurrency($field_value, $getuserapi->data->country_code);
				$store_currency = get_woocommerce_currency();

				if ($invoice_currency_check->status == 'success' && in_array($store_currency, $invoice_currency_check->data->currencies)) {
					// no code required.
				} else {
					unset($available_gateways[$payment_id]);
					$buyer_country = WC()->countries->countries[$field_value];
					$message = __('Transactions between buyers from ' . esc_html($buyer_country) . ' and sellers from ' . esc_html($getuserapi->data->country) . ' are currently not supported in ' . esc_html($site_currency) . '', 'wc-tp-payment-gateway');
					wc_add_notice($message, 'error');
				}
			} else {
				unset($available_gateways[$payment_id]);
				$buyer_country = WC()->countries->countries[$field_value];
				$country_config_message = __('Transactions between buyers from ' . esc_html($buyer_country) . ' and sellers from ' . esc_html($getuserapi->data->country) . ' are currently not supported', 'wc-tp-payment-gateway');
				wc_add_notice($country_config_message, 'error');
			}
		}
	}
	return $available_gateways;
}

add_action('add_meta_boxes', 'tcpg_remove_shop_order_meta_boxe', 90);
function tcpg_remove_shop_order_meta_boxe() {
	remove_meta_box('postcustom', 'shop_order', 'normal');
}

add_filter('manage_edit-shop_order_columns', 'tcpg_shop_order_column', 20);
function tcpg_shop_order_column($columns) {
	$reordered_columns = array();

	foreach ($columns as $key => $column) {
		$reordered_columns[$key] = $column;
		if ($key == 'order_status') {
			$reordered_columns['tazapay-status'] = __('Payment Status', 'wc-tp-payment-gateway');
		}
	}
	return $reordered_columns;
}

// Adding custom fields meta data for each new column
add_action('manage_shop_order_posts_custom_column', 'tcpg_orders_list_column_content', 20, 2);
function tcpg_orders_list_column_content($column, $post_id) {
	switch ($column) {
	case 'tazapay-status':
		$txn_no = get_post_meta($post_id, 'txn_no', true);
		$paymentMethod = get_post_meta($post_id, '_payment_method', true);
		$payment_title = get_post_meta($post_id, '_payment_method_title', true);

		if (!empty($txn_no) && $paymentMethod == 'tz_tazapay') {

			$tcpg_request_api_orderstatus = new TCPG_Gateway();
			$getEscrowstate = $tcpg_request_api_orderstatus->tcpg_request_api_orderstatus($txn_no);
			$getRefundStatus = $tcpg_request_api_orderstatus->tcpg_request_api_refundstatus($txn_no);

			if ($getRefundStatus->data[0]->status == 'approved') {
				//updatePostStatus($post_id, 'wc-refunded');  //Refund status
				?>
                        <p><strong>Tazapay Refunded</strong></p>
                    <?php
} elseif ($getRefundStatus->data[0]->status == 'requested') {
				//updatePostStatus($post_id, 'wc-processing');  //Refund status
				?>
                        <p><strong>Tazapay Refund Requested</strong></p>
                    <?php
} elseif ($getRefundStatus->data[0]->status == 'rejected') {
				//updatePostStatus($post_id, 'wc-processing');  //Refund status
				?>
                        <p><strong>Tazapay Refund Rejected</strong></p>
                    <?php
} else {
				?>
                    <p><strong><?php esc_html_e('Escrow state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->state, 'wc-tp-payment-gateway');?></p>
                    <p><strong><?php esc_html_e('Escrow sub_state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->sub_state, 'wc-tp-payment-gateway');?></p>
                <?php
}
		} else {
			esc_html_e($payment_title, 'wc-tp-payment-gateway');
		}
		break;
	}
}

function updatePostStatus($post_id, $status) {
	$post_status = get_post_field('post_status', $post_id, true);
	if ($post_status != $status) {
		$my_post = array(
			'ID' => $post_id,
			'post_status' => $status,
		);
		wp_update_post($my_post);
	}
}

add_action('woocommerce_view_order', 'tcpg_view_order_page', 20);

function tcpg_view_order_page($order_id) {
	$request_api_call = new TCPG_Gateway();
	$order = wc_get_order($order_id);
	$paymentMethod = get_post_meta($order_id, '_payment_method', true);

	if ($paymentMethod == 'tz_tazapay') {

		$user_email = $order->get_billing_email();
		$txn_no = get_post_meta($order_id, 'txn_no', true);
		$redirect_url = get_post_meta($order_id, 'redirect_url', true);

		?>
        <h2><?php esc_html_e('Transaction Details', 'wc-tp-payment-gateway');?></h2>
        <p><?php esc_html_e('Pay Now, Release Later powered by Tazapay', 'wc-tp-payment-gateway');?></p>
        <table class="woocommerce-table shop_table gift_info">
            <tfoot>
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
                <tr>
                    <th scope="row"><?php esc_html_e('Payment status', 'wc-tp-payment-gateway');?></th>
                    <td>
                        <?php
$getEscrowstate = $request_api_call->tcpg_request_api_orderstatus($txn_no);

		if (isset($_POST['order-status']) && !empty($getEscrowstate->data->state) && !empty($getEscrowstate->data->sub_state)) {
			?>
                            <p><strong><?php esc_html_e('Escrow state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->state, 'wc-tp-payment-gateway');?></p>
                            <p><strong><?php esc_html_e('Escrow sub_state: ', 'wc-tp-payment-gateway');?></strong><?php esc_html_e($getEscrowstate->data->sub_state, 'wc-tp-payment-gateway');?></p>
                        <?php
}
		?>
                        <form method="post" name="tazapay-order-status" action="">
                            <input type="submit" name="order-status" value="<?php esc_html_e('Refresh Status', 'wc-tp-payment-gateway');?>">
                        </form>
                        <?php

		if (isset($getEscrowstate->status) && $getEscrowstate->status == 'success' && ($getEscrowstate->data->state == 'Payment_Received' || $getEscrowstate->data->sub_state == 'Payment_Done')) {
			$order->update_status('processing');

			if ($getEscrowstate->data->state == 'Payment_Received') {
				esc_html_e('Completed', 'wc-tp-payment-gateway');
			}
			if ($getEscrowstate->data->sub_state == 'Payment_Done') {
				esc_html_e('Completed', 'wc-tp-payment-gateway');
			}
		} else {
			printf('<a class="woocommerce-button button pay" href="%s">%s</a>', esc_url($redirect_url), __("Pay With Escrow", "wc-tp-payment-gateway"));
		}
		?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php

		$order_notes = $request_api_call->tcpg_get_private_order_notes($order_id);
		if (isset($order_notes) && count($order_notes) > 1) {
			foreach ($order_notes as $note) {
				$note_date = isset($note['note_date']) ? esc_html($note['note_date']) : '';
				$note_content = isset($note['note_content']) ? esc_html($note['note_content']) : '';
				?>
                <p><strong><?php esc_html_e(date('F j, Y h:i A', strtotime($note_date)), 'wc-tp-payment-gateway');?></strong><?php esc_html_e($note_content, 'wc-tp-payment-gateway');?></p>
<?php
}
		}
	}
}

add_action('admin_head', 'remove_manual_refunds');

function remove_manual_refunds() {
	echo '<style>
    .do-manual-refund {
    display: none !important;
    }
  </style>';
}
