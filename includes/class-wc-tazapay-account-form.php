<?php
if (!is_admin()) {
    new TCPG_Account();
}
class TCPG_Account
{
    public function __construct()
    {
        add_shortcode('tazapay-account', array($this, 'tcpg_accountform_shortcode'));
    }

    // Create seller form shortcode
    public function tcpg_accountform_shortcode($atts)
    {
        if (!is_admin() && !wp_doing_ajax()) {
            ob_start();
            require_once plugin_dir_path(__FILE__) . 'shortcodes/tazapay-accountform-shortcode.php';
            return ob_get_clean();
        } else {
            return '[tazapay-account]';
        }
    }
}
