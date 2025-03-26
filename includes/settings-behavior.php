<?php
// Register submenu for Invoice Behavior
add_action('admin_menu', function () {
    add_submenu_page('b2b-invoice-settings', 'Invoice Behavior', 'Invoice Behavior', 'manage_woocommerce', 'b2b-invoice-behavior', 'b2b_invoice_behavior_page');
});

// Page content for selecting order status after invoice payment
function b2b_invoice_behavior_page() {
    if (!current_user_can('manage_woocommerce')) return;

    if (isset($_POST['b2b_invoice_order_status'])) {
        $status = sanitize_text_field($_POST['b2b_invoice_order_status']);
        update_option('b2b_invoice_order_status', $status);
        echo '<div class="updated"><p>Order status setting saved.</p></div>';
    }

    $selected_status = get_option('b2b_invoice_order_status', 'on-hold');
    $statuses = wc_get_order_statuses();

    echo '<div class="wrap"><h1>Invoice Payment Behavior</h1><form method="post">';
    echo '<table class="form-table"><tbody>';
    echo '<tr><th scope="row">Order Status after Invoice Payment</th><td><select name="b2b_invoice_order_status">';
    foreach ($statuses as $key => $label) {
        $selected = $selected_status === $key ? 'selected' : '';
        echo "<option value='{$key}' {$selected}>{$label}</option>";
    }
    echo '</select></td></tr>';
    echo '</tbody></table><p><input type="submit" class="button-primary" value="Save Changes"></p></form></div>';
}
