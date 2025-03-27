<?php
// Restrict the custom payment gateway based on allowed and disabled users
add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    if (!is_admin() && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $allowed  = get_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', true) === 'yes';
        $disabled = get_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', true) === 'yes';

        if (!$allowed || $disabled) {
            unset($gateways['b2b_invoice']);
        }
    }

    return $gateways;
});
