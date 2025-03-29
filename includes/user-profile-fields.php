<?php

/**
 * @package   WooCommerce_B2B_Invoice
 * @author    Riko Valkonen
 * @license   GPL-2.0-or-later
 * @link      https://github.com/rikovalkonen/woocommerce-b2b-invoice
 * @copyright Copyright (c) 2025
 *
 * This file is part of WooCommerce B2B Invoice Gateway.
 *
 * WooCommerce B2B Invoice Gateway is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

defined('ABSPATH') || exit;


add_action('show_user_profile', 'b2b_invoice_user_fields');
add_action('edit_user_profile', 'b2b_invoice_user_fields');

function b2b_invoice_user_fields($user)
{
    $can_invoice = get_user_meta($user->ID, 'wc_b2b_ic_can_invoice_order', true);
    $disable_invoice = get_user_meta($user->ID, 'wc_b2b_ic_disable_invoice_order', true);
    $companies = get_user_meta($user->ID, 'wc_b2b_ic_invoice_companies', true) ?: [];

?>
    <h2><?php echo __('Laskutus asetukset', 'woocommerce-b2b-invoice-customers'); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="can_invoice_order"><?php echo __('Salli maksu laskulla', 'woocommerce-b2b-invoice-customers'); ?></label></th>
            <td>
                <input type="checkbox" name="can_invoice_order" id="can_invoice_order" value="yes" <?php checked($can_invoice, 'yes'); ?> />
            </td>
        </tr>
        <tr>
            <th><label for="disable_invoice_order"><?php echo __('Poista laskulla maksu väliaikaisesti käytöstä', 'woocommerce-b2b-invoice-customers'); ?></label></th>
            <td>
                <input type="checkbox" name="disable_invoice_order" id="disable_invoice_order" value="yes" <?php checked($disable_invoice, 'yes'); ?> />
            </td>
        </tr>
        <tr>
            <th><?php echo __('Laskutus yritykset', 'woocommerce-b2b-invoice-customers'); ?></th>
        </tr>
        <tr>
            <td>
                <div id="invoice-companies-wrapper">
                    <?php foreach ($companies as $key => $company): ?>
                        <?php $y_tunnus = esc_attr($company['y_tunnus'] ?? $key); ?>
                        <div class="invoice-company" style="margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
                            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                                <p><strong>Yrityksen nimi:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $y_tunnus; ?>][company_name]" placeholder="<?php echo __('Yrityksen nimi', 'woocommerce-b2b-invoice-customers'); ?>" value="<?php echo esc_attr($company['company_name']); ?>" />
                            </div>
                            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                                <p><strong>Y-tunnus:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $y_tunnus; ?>][y_tunnus]" placeholder="<?php echo __('Y-tunnus', 'woocommerce-b2b-invoice-customers'); ?>" value="<?php echo esc_attr($company['y_tunnus'] ?? ''); ?>" />
                            </div>
                            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                                <p><strong>Verkkolaskutusosoite:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $y_tunnus; ?>][verkkolaskutusosoite]" placeholder="<?php echo __('Verkkolaskutusosoite', 'woocommerce-b2b-invoice-customers'); ?>" value="<?php echo esc_attr($company['verkkolaskutusosoite'] ?? ''); ?>" />
                            </div>
                            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                                <p><strong>Välittäjä:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $y_tunnus; ?>][valittaja]" placeholder="<?php echo __('Välittäjä', 'woocommerce-b2b-invoice-customers'); ?>" value="<?php echo esc_attr($company['valittaja'] ?? ''); ?>" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" id="add-invoice-company"><?php echo __('Lisää yritys', 'woocommerce-b2b-invoice-customers'); ?></button>
            </td>
        </tr>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.getElementById('invoice-companies-wrapper');
            const button = document.getElementById('add-invoice-company');

            button.addEventListener('click', () => {
                const yTunnus = prompt("Enter a unique Y-tunnus for the new company:");
                if (!yTunnus) {
                    alert("Y-tunnus is required!");
                    return;
                }

                const div = document.createElement('div');
                div.className = 'invoice-company';
                div.style = 'margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;';
                div.innerHTML = `
            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                <p><strong>Yrityksen nimi:</strong></p>
                <input type="text" name="invoice_companies[${yTunnus}][company_name]" placeholder="Yrityksen nimi" />
            </div>
            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                <p><strong>Y-tunnus:</strong></p>
                <input type="text" name="invoice_companies[${yTunnus}][y_tunnus]" placeholder="Y-tunnus" value="${yTunnus}" readonly />
            </div>
            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                <p><strong>Verkkolaskutusosoite:</strong></p>
                <input type="text" name="invoice_companies[${yTunnus}][verkkolaskutusosoite]" placeholder="Verkkolaskutusosoite" />
            </div>
            <div style="display: flex; gap: 1rem; justify-content: space-between;">
                <p><strong>Välittäjä:</strong></p>
                <input type="text" name="invoice_companies[${yTunnus}][valittaja]" placeholder="Välittäjä"/>
            </div>
        `;
                wrapper.appendChild(div);
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
            ];
        }, $_POST['invoice_companies']);
        update_user_meta($user_id, 'wc_b2b_ic_invoice_companies', $clean);
    } else {
        delete_user_meta($user_id, 'wc_b2b_ic_invoice_companies');
    }
}
