<?php
add_filter('wcvendors_dashboard_nav_items', 'tcpg_wcvendors_dashboard_nav');
/**
 * Add tazapay information menu in vendor dashboard.
 */
function tcpg_wcvendors_dashboard_nav($items)
{
	$items['tazapay_information'] = array(
		'url'    => admin_url('profile.php?page=tazapay-seller-information'),
		'label'  => esc_html__('Tazapay information', 'wc-tp-payment-gateway'),
		'target' => '_top',
	);
	return $items;
}

add_action('admin_menu', 'tcpg_wcvendors_dashboard_sellerinfo_page');
/**
 * Adds a submenu page under a profile parent.
 */
function tcpg_wcvendors_dashboard_sellerinfo_page()
{
	add_submenu_page(
		'profile.php',
		__('Tazapay information', 'wc-tp-payment-gateway'),
		__('Tazapay information', 'wc-tp-payment-gateway'),
		'manage_product',
		'tazapay-seller-information',
		'tcpg_sellerinfo_wcvendors'
	);
}

function tcpg_sellerinfo_wcvendors()
{

?>
	<div class="wrap tazapay-account-information">
		<?php
		global $woocommerce, $wpdb;

		$countries_obj          = new WC_Countries();
		$countries              = $countries_obj->__get('countries');

		$woocommerce_tz_tazapay_settings  = get_option('woocommerce_tz_tazapay_settings');
		$sandboxmode                      = esc_html($woocommerce_tz_tazapay_settings['sandboxmode']);
		$tazapay_seller_type              = esc_html($woocommerce_tz_tazapay_settings['tazapay_seller_type']);
		$tazapay_multi_seller_plugin      = esc_html($woocommerce_tz_tazapay_settings['tazapay_multi_seller_plugin']);

		if ($sandboxmode == 'sandbox') {
			$api_url     = 'https://api-sandbox.tazapay.com';
			$environment = 'sandbox';
		} else {
			$api_url     = 'https://api.tazapay.com';
			$environment = 'production';
		}

		if (is_user_logged_in() && $tazapay_seller_type == 'multiseller' && is_admin()) {
			$seller_user    = get_userdata(get_current_user_id());
			$user_email     = sanitize_email($seller_user->user_email);
		} else {
			$user_email     = sanitize_email($woocommerce_tz_tazapay_settings['seller_email']);
		}

		$tablename      = $wpdb->prefix . 'tazapay_user';
		$seller_results = $wpdb->get_results("SELECT * FROM $tablename WHERE email = '" . $user_email . "' AND environment = '" . $environment . "'");
		$db_account_id  = isset($seller_results[0]->account_id) ? esc_html($seller_results[0]->account_id) : '';
		$apiRequestCall = new TCPG_Gateway();
		$getuserapi 	= $apiRequestCall->tcpg_request_api_getuser($user_email);

		if (!empty($getuserapi->data->id)) {

			$account_id = isset($getuserapi->data->id) ? sanitize_text_field($getuserapi->data->id) : '';

			if (empty($db_account_id)) {

				$wpdb->insert(
					$tablename,
					array(
						'account_id'           => isset($account_id) ? sanitize_text_field($account_id) : '',
						'user_type'            => "seller",
						'email'                => isset($getuserapi->data->email) ? sanitize_text_field($getuserapi->data->email) : '',
						'first_name'           => isset($getuserapi->data->first_name) ? sanitize_text_field($getuserapi->data->first_name) : '',
						'last_name'            => isset($getuserapi->data->last_name) ? sanitize_text_field($getuserapi->data->last_name) : '',
						'contact_code'         => isset($getuserapi->data->contact_code) ? sanitize_text_field($getuserapi->data->contact_code) : '',
						'contact_number'       => isset($getuserapi->data->contact_number) ? sanitize_text_field($getuserapi->data->contact_number) : '',
						'country'              => isset($getuserapi->data->country_code) ? sanitize_text_field($getuserapi->data->country_code) : '',
						'ind_bus_type'         => isset($getuserapi->data->ind_bus_type) ? sanitize_text_field($getuserapi->data->ind_bus_type) : '',
						'business_name'        => isset($getuserapi->data->business_name) ? sanitize_text_field($getuserapi->data->business_name) : '',
						'environment'          => isset($environment) ? sanitize_text_field($environment) : '',
						'created'              => current_time('mysql')
					),
					array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
				);
			}
		}

		if (empty($db_account_id) || empty($getuserapi->data->id)) {

			if (isset($_POST['submit'])) {

				$indbustype           = isset($_POST['indbustype']) ? sanitize_text_field($_POST['indbustype']) : '';
				$first_name           = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
				$last_name            = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
				$business_name        = isset($_POST['business_name']) ? sanitize_text_field($_POST['business_name']) : '';
				$phone_number         = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
				$country              = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
				$seller_email         = $user_email;

				$phoneCode            = $apiRequestCall->tcpg_getphonecode($country);

				if ($business_name) {
					$args = array(
						"email"                 => $seller_email,
						"country"               => $country,
						"contact_code"          => $phoneCode,
						"contact_number"        => $phone_number,
						"ind_bus_type"          => $indbustype,
						"business_name"         => $business_name
					);
				} else {
					$args = array(
						"email"                 => $seller_email,
						"first_name"            => $first_name,
						"last_name"             => $last_name,
						"contact_code"          => $phoneCode,
						"contact_number"        => $phone_number,
						"country"               => $country,
						"ind_bus_type"          => $indbustype
					);
				}

				$api_endpoint = "/v1/user";
				$api_url  = $api_url . '/v1/user';

				$createUser = $apiRequestCall->tcpg_request_apicall($api_url, $api_endpoint, $args, '');

				if ($createUser->status == 'success') {

					$tablename  = $wpdb->prefix . 'tazapay_user';
					$account_id = $createUser->data->account_id;

					$wpdb->insert(
						$tablename,
						array(
							'account_id'           => $account_id,
							'user_type'            => "seller",
							'email'                => $seller_email,
							'first_name'           => $first_name,
							'last_name'            => $last_name,
							'contact_code'         => $phoneCode,
							'contact_number'       => $phone_number,
							'country'              => $country,
							'ind_bus_type'         => $indbustype,
							'business_name'        => $business_name,
							'environment'          => $environment,
							'created'              => current_time('mysql')
						),
						array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
					);

		?>
					<div class="notice notice-success is-dismissible">
						<p><?php esc_html_e($createUser->message, 'wc-tp-payment-gateway'); ?></p>
					</div>
					<?php
					if (is_admin()) {
						wp_redirect(admin_url('profile.php?page=tazapay-seller-information'), 301);
					}
					exit();
				} else {

					$create_user_error_msg = "";
					$create_user_error_msg = "Create Tazapay User Error: " . esc_html($createUser->message);

					foreach ($createUser->errors as $key => $error) {

						if (isset($error->code)) {
							$create_user_error_msg .= "code: " . esc_html($error->code) . '<br>';
						}
						if (isset($error->message)) {
							$create_user_error_msg .= "Message: " . esc_html($error->message) . '<br>';
						}
						if (isset($error->remarks)) {
							$create_user_error_msg .= "Remarks: " . esc_html($error->remarks) . '<br>';
						}
					}
					?>
					<div class="notice notice-error is-dismissible">
						<p><?php esc_html_e($create_user_error_msg, 'wc-tp-payment-gateway'); ?></p>
					</div>
			<?php
				}
			}
			?>
			<h2>
				<?php esc_html_e('Create Tazapay Account', 'wc-tp-payment-gateway'); ?>
			</h2>
			<hr>
			<form method="post" name="accountform" action="" class="tazapay_form dokan-form-horizontal">
				<div class="container">
					<div class="dokan-form-group">
						<label for="firstname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Ind Bus Type', 'wc-tp-payment-gateway'); ?></b></label>
						<div class="dokan-w5">
							<select id="indbustype" name="indbustype" class="dokan-form-control">
								<option value=""><?php esc_html_e('Select Type', 'wc-tp-payment-gateway'); ?></option>
								<option value="Individual"><?php esc_html_e('Individual', 'wc-tp-payment-gateway'); ?></option>
								<option value="Business"><?php esc_html_e('Business', 'wc-tp-payment-gateway'); ?></option>
							</select>
						</div>
					</div>
					<div id="individual">
						<div class="dokan-form-group">
							<label for="firstname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('First Name', 'wc-tp-payment-gateway'); ?></b></label>
							<div class="dokan-w5">
								<input type="text" placeholder="<?php esc_attr_e('First Name', 'wc-tp-payment-gateway'); ?>" name="first_name" id="first_name">
							</div>
						</div>
						<div class="dokan-form-group">
							<label for="lastname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Last Name', 'wc-tp-payment-gateway'); ?></b></label>
							<div class="dokan-w5">
								<input type="text" placeholder="<?php esc_attr_e('Last Name', 'wc-tp-payment-gateway'); ?>" name="last_name" id="last_name">
							</div>
						</div>
					</div>
					<div id="business" class="dokan-form-group">
						<label for="businessname" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Business Name', 'wc-tp-payment-gateway'); ?></b></label>
						<div class="dokan-w5">
							<input type="text" placeholder="<?php esc_attr_e('Business Name', 'wc-tp-payment-gateway'); ?>" name="business_name" id="business_name">
						</div>
					</div>
					<div class="dokan-form-group">
						<label for="email" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('E-Mail', 'wc-tp-payment-gateway'); ?></b></label>
						<div class="dokan-w5">
							<?php
							if (esc_html($user_email)) {
							?>
								<input type="text" placeholder="<?php esc_attr_e('Enter Email', 'wc-tp-payment-gateway'); ?>" name="email" id="email" value="<?php esc_html_e($user_email, 'wc-tp-payment-gateway'); ?>" readonly disabled>
							<?php } else { ?>
								<input type="text" placeholder="<?php esc_attr_e('Enter Email', 'wc-tp-payment-gateway'); ?>" name="email" id="email">
							<?php
							}
							?>
						</div>
					</div>
					<div class="dokan-form-group">
						<label for="phonenumber" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Phone Number', 'wc-tp-payment-gateway'); ?></b></label>
						<div class="dokan-w5">
							<input type="text" placeholder="<?php esc_attr_e('Phone Number', 'wc-tp-payment-gateway'); ?>" name="phone_number" id="phone_number">
						</div>
					</div>
					<div class="dokan-form-group">
						<label for="country" class="dokan-w3 dokan-control-label"><b><?php esc_html_e('Country', 'wc-tp-payment-gateway'); ?></b></label>
						<div class="dokan-w5">
							<select id="country" name="country" class="dokan-form-control">
								<option value=""><?php esc_html_e('Select country', 'wc-tp-payment-gateway'); ?></option>
								<?php
								foreach ($countries as $country_code => $country) {
								?>
									<option value="<?php esc_html_e($country_code, 'wc-tp-payment-gateway'); ?>"><?php esc_html_e($country, 'wc-tp-payment-gateway'); ?></option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
					<input type="submit" class="registerbtn dokan-btn dokan-btn-danger dokan-btn-theme" name="submit" value="<?php esc_html_e('Submit', 'wc-tp-payment-gateway'); ?>">
				</div>
			</form>
			<?php
		}

		if (!empty($db_account_id)) {

			$first_name     = isset($seller_results[0]->first_name) ? esc_html($seller_results[0]->first_name) : '';
			$last_name      = isset($seller_results[0]->last_name) ? esc_html($seller_results[0]->last_name) : '';
			$user_type      = isset($seller_results[0]->user_type) ? esc_html($seller_results[0]->user_type) : '';
			$contact_code   = isset($seller_results[0]->contact_code) ? esc_html($seller_results[0]->contact_code) : '';
			$contact_number = isset($seller_results[0]->contact_number) ? esc_html($seller_results[0]->contact_number) : '';
			$country_name   = isset($seller_results[0]->country) ? esc_html($seller_results[0]->country) : '';
			$ind_bus_type   = isset($seller_results[0]->ind_bus_type) ? esc_html($seller_results[0]->ind_bus_type) : '';
			$business_name  = isset($seller_results[0]->business_name) ? esc_html($seller_results[0]->business_name) : '';
			$created        = isset($seller_results[0]->created) ? esc_html($seller_results[0]->created) : '';
			$environment    = isset($seller_results[0]->environment) ? esc_html($seller_results[0]->environment) : '';
			$countryName    = WC()->countries->countries[$country_name];

			if ($tazapay_seller_type == 'multiseller' && $tazapay_multi_seller_plugin == 'wc-vendors') {
			?>
				<h2>
					<?php esc_html_e('Tazapay Account Information', 'wc-tp-payment-gateway'); ?>
				</h2>
				<hr>
			<?php
			}
			?>
			<table class="wp-list-table widefat fixed striped table-view-list">
				<tr>
					<th><?php esc_html_e('Tazapay Account UUID:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($db_account_id, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('User Type:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($user_type, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Entity Type:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($ind_bus_type, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<?php if ($business_name) { ?>
					<tr>
						<th><?php esc_html_e('Business Name:', 'wc-tp-payment-gateway'); ?></th>
						<td><?php esc_html_e($business_name, 'wc-tp-payment-gateway'); ?></td>
					</tr>
				<?php } else { ?>
					<tr>
						<th><?php esc_html_e('First Name:', 'wc-tp-payment-gateway'); ?></th>
						<td><?php esc_html_e($first_name, 'wc-tp-payment-gateway'); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Last Name:', 'wc-tp-payment-gateway'); ?></th>
						<td><?php esc_html_e($last_name, 'wc-tp-payment-gateway'); ?></td>
					</tr>
				<?php } ?>
				<tr>
					<th><?php esc_html_e('E-mail:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($user_email, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Contact Code:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($contact_code, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Contact Number:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($contact_number, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Country:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($countryName, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Environment:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($environment, 'wc-tp-payment-gateway'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Created At:', 'wc-tp-payment-gateway'); ?></th>
					<td><?php esc_html_e($created, 'wc-tp-payment-gateway'); ?></td>
				</tr>
			</table>
		<?php
		}
		?>
	</div>
<?php
}
