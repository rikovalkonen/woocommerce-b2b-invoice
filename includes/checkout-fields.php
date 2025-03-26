<?php
// Add dropdown to select invoice company
add_action('woocommerce_before_order_notes', function ($checkout) {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $allowed_users = get_option('b2b_invoice_allowed_users', []);
    $disabled_users = get_option('b2b_invoice_disabled_users', []);

    if (!in_array($user_id, $allowed_users) || in_array($user_id, $disabled_users)) return;

    $companies = get_field('invoice_companies', 'user_' . $user_id);
    if (empty($companies)) return;

    $options = ['' => 'Select an Invoice Company'];
    foreach ($companies as $index => $company) {
        $label = $company['company_name'] . ' – ' . $company['vat_number'];
        $options[$index] = $label;
    }

    woocommerce_form_field('selected_invoice_company', [
        'type'     => 'select',
        'class'    => ['form-row-wide'],
        'label'    => 'Choose Invoice Company',
        'required' => true,
        'options'  => $options,
    ]);
});

// Validate selection
add_action('woocommerce_checkout_process', function () {
    if (isset($_POST['selected_invoice_company']) && $_POST['selected_invoice_company'] === '') {
        wc_add_notice(__('Please select an invoice company.'), 'error');
    }
});
