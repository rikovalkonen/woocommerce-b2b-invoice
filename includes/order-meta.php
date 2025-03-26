<?php
// Save selected invoice company to order meta
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $companies = get_field('invoice_companies', 'user_' . $user_id);
    $selected_index = $_POST['selected_invoice_company'] ?? null;

    if ($selected_index !== null && isset($companies[$selected_index])) {
        $company = $companies[$selected_index];
        $display = $company['company_name'] . "\nVAT: " . $company['vat_number'] . "\n" . $company['company_address'];
        $order->update_meta_data('_invoice_company_details', $display);
    }
}, 10, 2);

// Show invoice company on the admin order page
add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $company = $order->get_meta('_invoice_company_details');
    if ($company) {
        echo '<p><strong>Invoice Company:</strong><br>' . nl2br(esc_html($company)) . '</p>';
    }
});

// Include invoice company in emails
add_filter('woocommerce_email_order_meta_fields', function ($fields, $sent_to_admin, $order) {
    $company = $order->get_meta('_invoice_company_details');
    if ($company) {
        $fields['invoice_company'] = [
            'label' => 'Invoice Company',
            'value' => nl2br(esc_html($company))
        ];
    }
    return $fields;
}, 10, 3);
