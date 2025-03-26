<?php
// Restrict the custom payment gateway based on allowed and disabled users
add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    if (!is_admin() && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $allowed_users = get_option('b2b_invoice_allowed_users', []);
        $disabled_users = get_option('b2b_invoice_disabled_users', []);

        if (!in_array($user_id, $allowed_users) || in_array($user_id, $disabled_users)) {
            unset($gateways['b2b_invoice']);
        }
    }
    return $gateways;
});
