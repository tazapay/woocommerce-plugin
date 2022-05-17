<?php

/**
 * WCFM - Custom Menus Query Var
 */
function tcpg_query_vars($query_vars)
{
	$wcfm_modified_endpoints = (array) get_option('wcfm_endpoints');

	$query_custom_menus_vars = array(
		'tazapay-information' => !empty($wcfm_modified_endpoints['tazapay-information']) ? esc_html($wcfm_modified_endpoints['tazapay-information']) : 'tazapayinformation',
	);

	$query_vars = array_merge($query_vars, $query_custom_menus_vars);

	return $query_vars;
}
add_filter('wcfm_query_vars', 'tcpg_query_vars', 50);

/**
 * WCFM - Custom Menus End Point Title
 */
function tcpg_endpoint_title($title, $endpoint)
{
	global $wp;
	switch ($endpoint) {
		case 'tazapay-information':
			$title = __('Tazapay Information', 'wcfm-custom-menus');
			break;
	}

	return $title;
}
add_filter('wcfm_endpoint_title', 'tcpg_endpoint_title', 50, 2);

/**
 * WCFM - Custom Menus Endpoint Intialize
 */
function tcpg_init()
{
	global $WCFM_Query;

	// Intialize WCFM End points
	$WCFM_Query->init_query_vars();
	$WCFM_Query->add_endpoints();

	if (!get_option('wcfm_updated_end_point_cms')) {
		// Flush rules after endpoint update
		flush_rewrite_rules();
		update_option('wcfm_updated_end_point_cms', 1);
	}
}
add_action('init', 'tcpg_init', 50);

/**
 * WCFM - Custom Menus Endpoiint Edit
 */
function tcpg_custom_menus_endpoints_slug($endpoints)
{
	$custom_menus_endpoints = array('wcfm-tazapay-information' => 'tazapayinformation');
	$endpoints = array_merge($endpoints, $custom_menus_endpoints);
	return $endpoints;
}
add_filter('wcfm_endpoints_slug', 'tcpg_custom_menus_endpoints_slug');

if (!function_exists('get_wcfm_custom_menus_url')) {
	function get_wcfm_custom_menus_url($endpoint)
	{
		global $WCFM;
		$wcfm_page = get_wcfm_page();
		$wcfm_custom_menus_url = wcfm_get_endpoint_url($endpoint, '', $wcfm_page);
		return $wcfm_custom_menus_url;
	}
}

/**
 * WCFM - Custom Menus
 */
function tcpg_wcfm_menus($menus)
{
	global $WCFM;

	$menus['tazapay-information'] = array(
		'label' => __('Tazapay Information', 'wc-tp-payment-gateway'),
		'url'   => get_wcfm_custom_menus_url('tazapayinformation'),
		'icon'  => 'cubes'
	);
	return $menus;
}
add_filter('wcfm_menus', 'tcpg_wcfm_menus', 20);

/**
 *  WCFM - Custom Menus Views
 */
function tcpg_csm_load_views($end_point)
{
	global $WCFM, $WCFMu;
	$plugin_path = trailingslashit(dirname(__FILE__));

	switch ($end_point) {
		case 'tazapay-information':
			require_once($plugin_path . 'tazapay-information.php');
			break;
	}
}
add_action('wcfm_load_views', 'tcpg_csm_load_views', 50);
add_action('before_wcfm_load_views', 'tcpg_csm_load_views', 50);
