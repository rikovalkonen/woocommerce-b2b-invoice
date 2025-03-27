<?php

add_action('show_user_profile', 'b2b_invoice_user_fields');
add_action('edit_user_profile', 'b2b_invoice_user_fields');

function b2b_invoice_user_fields($user)
{
    $can_invoice = get_user_meta($user->ID, 'wc_b2b_ic_can_invoice_order', true);
    $disable_invoice = get_user_meta($user->ID, 'wc_b2b_ic_disable_invoice_order', true);
    $companies = get_user_meta($user->ID, 'wc_b2b_ic_invoice_companies', true) ?: [];

?>
    <h2>Invoice Settings</h2>
    <table class="form-table">
        <tr>
            <th><label for="can_invoice_order">Allow Invoice Payment</label></th>
            <td>
                <input type="checkbox" name="can_invoice_order" id="can_invoice_order" value="yes" <?php checked($can_invoice, 'yes'); ?> />
            </td>
        </tr>
        <tr>
            <th><label for="disable_invoice_order">Temporarily Disable Invoice</label></th>
            <td>
                <input type="checkbox" name="disable_invoice_order" id="disable_invoice_order" value="yes" <?php checked($disable_invoice, 'yes'); ?> />
            </td>
        </tr>
        <tr>
            <th>Invoice Companies</th>
            <td>
                <div id="invoice-companies-wrapper">
                    <?php foreach ($companies as $index => $company): ?>
                        <div class="invoice-company" style="margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
                            <input type="text" name="invoice_companies[<?php echo $index; ?>][company_name]" placeholder="Company Name" value="<?php echo esc_attr($company['company_name']); ?>" style="width: 100%;" />
                            <input type="text" name="invoice_companies[<?php echo $index; ?>][y_tunnus]" placeholder="Y-tunnus" value="<?php echo esc_attr($company['y_tunnus'] ?? ''); ?>" style="width: 100%;" />
                            <input type="text" name="invoice_companies[<?php echo $index; ?>][verkkolaskutusosoite]" placeholder="Verkkolaskutusosoite" value="<?php echo esc_attr($company['verkkolaskutusosoite'] ?? ''); ?>" style="width: 100%;" />
                            <input type="text" name="invoice_companies[<?php echo $index; ?>][valittaja]" placeholder="Välittäjä" value="<?php echo esc_attr($company['valittaja'] ?? ''); ?>" style="width: 100%;" />
                            <textarea name="invoice_companies[<?php echo $index; ?>][company_address]" placeholder="Company Address" style="width: 100%;"><?php echo esc_textarea($company['company_address']); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" id="add-invoice-company">Add Company</button>
            </td>
        </tr>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.getElementById('invoice-companies-wrapper');
            const button = document.getElementById('add-invoice-company');
            let index = <?php echo count($companies); ?>;

            button.addEventListener('click', () => {
                const div = document.createElement('div');
                div.className = 'invoice-company';
                div.style = 'margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;';
                div.innerHTML = `
                    <input type="text" name="invoice_companies[\${index}][company_name]" placeholder="Company Name" style="width: 100%;" />
                    <input type="text" name="invoice_companies[\${index}][y_tunnus]" placeholder="Y-tunnus" style="width: 100%;" />
                    <input type="text" name="invoice_companies[\${index}][verkkolaskutusosoite]" placeholder="Verkkolaskutusosoite" style="width: 100%;" />
                    <input type="text" name="invoice_companies[\${index}][valittaja]" placeholder="Välittäjä" style="width: 100%;" />
                    <textarea name="invoice_companies[\${index}][company_address]" placeholder="Company Address" style="width: 100%;"></textarea>
                `;
                wrapper.appendChild(div);
                index++;
            });
        });
    </script>
<?php
}

add_action('personal_options_update', 'b2b_invoice_save_user_fields');
add_action('edit_user_profile_update', 'b2b_invoice_save_user_fields');

function b2b_invoice_save_user_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) return;

    update_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', isset($_POST['can_invoice_order']) ? 'yes' : '');
    update_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', isset($_POST['disable_invoice_order']) ? 'yes' : 'no');

    if (isset($_POST['invoice_companies'])) {
        $clean = array_map(function ($company) {
            return [
                'company_name' => sanitize_text_field($company['company_name'] ?? ''),
                'y_tunnus' => sanitize_text_field($company['y_tunnus'] ?? ''),
                'verkkolaskutusosoite' => sanitize_text_field($company['verkkolaskutusosoite'] ?? ''),
                'valittaja' => sanitize_text_field($company['valittaja'] ?? ''),
                'company_address' => sanitize_textarea_field($company['company_address'] ?? ''),
            ];
        }, $_POST['invoice_companies']);
        update_user_meta($user_id, 'wc_b2b_ic_invoice_companies', $clean);
    } else {
        delete_user_meta($user_id, 'wc_b2b_ic_invoice_companies');
    }
}
