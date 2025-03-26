<?php
// Register admin menu pages
add_action('admin_menu', function () {
    add_menu_page('Invoice Settings', 'Invoice Settings', 'manage_woocommerce', 'b2b-invoice-settings', 'b2b_invoice_users_page');
    add_submenu_page('b2b-invoice-settings', 'Allowed Users', 'Allowed Users', 'manage_woocommerce', 'b2b-invoice-settings', 'b2b_invoice_users_page');
});

// Page content for Allowed Users management
function b2b_invoice_users_page() {
    if (!current_user_can('manage_woocommerce')) return;

    if (isset($_POST['b2b_invoice_users']) || isset($_POST['b2b_invoice_disabled_users'])) {
        $allowed_ids = isset($_POST['b2b_invoice_users']) ? array_map('intval', $_POST['b2b_invoice_users']) : [];
        $disabled_ids = isset($_POST['b2b_invoice_disabled_users']) ? array_map('intval', $_POST['b2b_invoice_disabled_users']) : [];

        update_option('b2b_invoice_allowed_users', $allowed_ids);
        update_option('b2b_invoice_disabled_users', $disabled_ids);

        echo '<div class="updated"><p>Invoice user settings updated.</p></div>';
    }

    $allowed_users = get_option('b2b_invoice_allowed_users', []);
    $disabled_users = get_option('b2b_invoice_disabled_users', []);
    $users = get_users(['role__in' => ['customer', 'subscriber']]);

    echo '<div class="wrap"><h1>Allowed Invoice Users</h1><form method="post">';
    echo '<table class="form-table"><thead><tr><th>User</th><th>Allow</th><th>Disable</th></tr></thead><tbody>';

    foreach ($users as $user) {
        $allow_checked = in_array($user->ID, $allowed_users) ? 'checked' : '';
        $disable_checked = in_array($user->ID, $disabled_users) ? 'checked' : '';
        echo "<tr>
            <th scope='row'>{$user->display_name} ({$user->user_email})</th>
            <td><input type='checkbox' name='b2b_invoice_users[]' value='{$user->ID}' {$allow_checked}></td>
            <td><input type='checkbox' name='b2b_invoice_disabled_users[]' value='{$user->ID}' {$disable_checked}></td>
        </tr>";
    }

    echo '</tbody></table><p><input type="submit" class="button-primary" value="Save Changes"></p></form></div>';
}
