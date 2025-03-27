<?php
// Save selected invoice company to order meta
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $companies = get_user_meta($user_id, 'wc_b2b_ic_invoice_companies', true);
    $selected_index = $_POST['selected_invoice_company'] ?? null;
    $reference = sanitize_text_field($_POST['invoice_customer_reference'] ?? '');

    if ($selected_index !== null && isset($companies[$selected_index])) {
        $company = $companies[$selected_index];
        $display = $company['company_name'] . "\nVAT: " . $company['y_tunnus'] . "\n" .
            "Address: " . $company['company_address'] . "\n" .
            "Verkkolaskutusosoite: " . $company['verkkolaskutusosoite'] . "\n" .
            "Välittäjä: " . $company['valittaja'] . "\n" .
            "Viite: " . $reference;

        if ($display) {
            $order->update_meta_data('_invoice_company_details', $display);
        }

        if ($reference) {
            $order->update_meta_data('_invoice_customer_reference', $reference);
        }
    }
}, 10, 2);

// Show invoice company on the admin order page
add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $company = $order->get_meta('_invoice_company_details');
    if ($company) {
        echo '<p><strong>Invoice Company:</strong><br>' . nl2br(esc_html($company)) . '</p>';
    }
});

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $reference = $order->get_meta('_invoice_customer_reference');
    if ($reference) {
        echo '<p><strong>Reference:</strong><br>' . nl2br(esc_html($reference)) . '</p>';
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
