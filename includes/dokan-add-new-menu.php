<?php

/**
 * Adds an 'Tazapay information' tab to the Dokan settings navigation menu.
 *
 * @param array $menu_items
 *
 * @return array
 */
function tcpg_add_account_tab($menu_items)
{
    $menu_items['tazapay-information'] = [
        'title'      => __('Tazapay information', 'wc-tp-payment-gateway'),
        'icon'       => '<i class="fa fa-user-circle"></i>',
        'url'        => dokan_get_navigation_url('settings/tazapay-information'),
        'pos'        => 90,
        'permission' => 'dokan_view_store_settings_menu',
    ];
    return $menu_items;
}
add_filter('dokan_get_dashboard_settings_nav', 'tcpg_add_account_tab');

/**
 * Sets the title for the 'Tazapay information' settings tab.
 *
 * @param string $title
 * @param string $tab
 *
 * @return string Title for tab with slug $tab
 */
function tcpg_set_account_tab_title($title, $tab)
{
    if ('tazapay-information' === $tab) {
        $title = __('Tazapay information', 'wc-tp-payment-gateway');
    }
    return $title;
}

add_filter('dokan_dashboard_settings_heading_title', 'tcpg_set_account_tab_title', 10, 2);

/**
 * Sets the help text for the 'Tazapay information' settings tab.
 *
 * @param string $help_text
 * @param string $tab
 *
 * @return string Help text for tab with slug $tab
 */
function tcpg_set_account_tab_help_text($help_text, $tab)
{
    if ('tazapay-information' === $tab) {
        $help_text = __('Personalize your store page by telling customers a little about yourself.', 'wc-tp-payment-gateway');
    }
    return $help_text;
}

add_filter('dokan_dashboard_settings_helper_text', 'tcpg_set_account_tab_help_text', 10, 2);

/**
 * Outputs the content for the 'Tazapay information' settings tab.
 *
 * @param array $query_vars WP query vars
 */
function tcpg_output_help_tab_content($query_vars)
{
    if (isset($query_vars['settings']) && 'tazapay-information' === $query_vars['settings']) {
        echo do_shortcode('[tazapay-account]');
    }
}

add_action('dokan_render_settings_content', 'tcpg_output_help_tab_content');
