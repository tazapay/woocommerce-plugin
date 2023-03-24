<?php

// Add custom tazapay icons to WooCommerce Checkout Page
function tzp_woocommerce_icons($icon, $id){
    if ($id === 'tz_tazapay') {

        $settings = tzp_getAdminAPISettings();

        error_log(json_encode($settings['branding']));

        if($settings['branding']){

            $logo_url = TZP_PUBLIC_ASSETS_DIR . "images/tazapay-logo-dark.png";
            $payment_methods = TZP_PUBLIC_ASSETS_DIR . "images/payment_methods.png";

            $icon = '<div class="tw-mt-2 tw-flex tw-flex-wrap tw-gap-2">';
            $icon .= '<div>';
            $icon .= '<img src=' . esc_url($logo_url) . ' alt="tazapay" />';
            $icon .= '</div>';
            $icon .= '<div>';
            $icon .= '<img src=' . esc_url($payment_methods) . ' alt="local payment" />';
            $icon .= '</div>';
            $icon .= '</div>';
        }

        // $icon = '<div class="tazapay-checkout-button"><div class="tazapay-payment-logo"><img src=' . esc_url($logo_url) . ' alt="tazapay" /><div class="tazapay-payment-vertical-line"></div>';
        // $icon .= __('Pay securely with buyer protection', 'wc-tp-payment-gateway');
        // $icon .= '</div><div class="tazapay-payment-method"><img src=' . esc_url($payment_methods) . ' alt="tazapay"/></div></div>';

        return $icon;
    } else {
        return $icon;
    }
}