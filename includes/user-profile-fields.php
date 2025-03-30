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
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $can_invoice = get_user_meta($user->ID, 'wc_b2b_ic_can_invoice_order', true);
    $disable_invoice = get_user_meta($user->ID, 'wc_b2b_ic_disable_invoice_order', true);
    $companies = get_user_meta($user->ID, 'wc_b2b_ic_invoice_companies', true) ?: [];

?>
    <h2><?php echo __('Invoice settings', 'woocommerce-b2b-invoice'); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="can_invoice_order"><?php echo __('Allow pay by invoice', 'woocommerce-b2b-invoice'); ?></label></th>
            <td>
                <input type="checkbox" name="can_invoice_order" id="can_invoice_order" value="yes" <?php checked($can_invoice, 'yes'); ?> />
            </td>
        </tr>
        <tr>
            <th><label for="disable_invoice_order"><?php echo __('Temporarily disable invoice payment', 'woocommerce-b2b-invoice'); ?></label></th>
            <td>
                <input type="checkbox" name="disable_invoice_order" id="disable_invoice_order" value="yes" <?php checked($disable_invoice, 'yes'); ?> />
            </td>
        </tr>
        <tr>
            <th><?php echo __('Companies', 'woocommerce-b2b-invoice'); ?></th>
        </tr>
        <tr>
            <td>
                <div id="invoice-companies-wrapper">
                    <?php foreach ($companies as $key => $company): ?>
                        <?php $business_id = esc_attr($company['business_id'] ?? $key); ?>
                        <div class="invoice-company" style="margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between;">
                                <p><strong><?php echo __('Company name', 'woocommerce-b2b-invoice') ?>:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $business_id; ?>][company_name]" placeholder="<?php echo __('Company name', 'woocommerce-b2b-invoice'); ?>" value="<?php echo esc_attr($company['company_name']); ?>" />
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <p><strong><?php echo __('Business ID', 'woocommerce-b2b-invoice') ?>:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $business_id; ?>][business_id]" placeholder="<?php echo __('Business ID', 'woocommerce-b2b-invoice'); ?>" value="<?php echo esc_attr($company['business_id'] ?? ''); ?>" />
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <p><strong><?php echo __('E-invoice address', 'woocommerce-b2b-invoice') ?>:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $business_id; ?>][e_invoice_address]" placeholder="<?php echo __('E-invoice address', 'woocommerce-b2b-invoice'); ?>" value="<?php echo esc_attr($company['e_invoice_address'] ?? ''); ?>" />
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <p><strong><?php echo __('Intermediary', 'woocommerce-b2b-invoice') ?>:</strong></p>
                                <input type="text" name="invoice_companies[<?php echo $business_id; ?>][intermediary]" placeholder="<?php echo __('Intermediary', 'woocommerce-b2b-invoice'); ?>" value="<?php echo esc_attr($company['intermediary'] ?? ''); ?>" />
                            </div>
                            <button type="button" class="button delete-invoice-company" data-business-id="<?php echo $business_id; ?>"><?php echo __('Delete', 'woocommerce-b2b-invoice'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" id="add-invoice-company"><?php echo __('Add', 'woocommerce-b2b-invoice'); ?></button>
            </td>
        </tr>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.getElementById('invoice-companies-wrapper');
            const button = document.getElementById('add-invoice-company');

            button.addEventListener('click', () => {
                const businessID = prompt("Enter a business ID for the new company:");
                if (!businessID) {
                    alert("Business ID is required!");
                    return;
                }

                const div = document.createElement('div');
                div.className = 'invoice-company';
                div.style = 'margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;';
                div.innerHTML = `
            <div style="display: flex; justify-content: space-between;">
                <p><strong>Company name:</strong></p>
                <input type="text" name="invoice_companies[${businessID}][company_name]" placeholder="Company name" />
            </div>
            <div style="display: flex; justify-content: space-between;">
                <p><strong>Business ID:</strong></p>
                <input type="text" name="invoice_companies[${businessID}][business_id]" placeholder="Business ID" value="${businessID}" readonly />
            </div>
            <div style="display: flex; justify-content: space-between;">
                <p><strong>E-invoice address:</strong></p>
                <input type="text" name="invoice_companies[${businessID}][e_invoice_address]" placeholder="E-invoice address" />
            </div>
            <div style="display: flex; justify-content: space-between;">
                <p><strong>Intermediary:</strong></p>
                <input type="text" name="invoice_companies[${businessID}][intermediary]" placeholder="Intermediary"/>
            </div>
        `;
                wrapper.appendChild(div);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.getElementById('invoice-companies-wrapper');
            wrapper.addEventListener('click', (event) => {
                if (event.target.classList.contains('delete-invoice-company')) {
                    const businessID = event.target.getAttribute('data-business-id');
                    if (confirm(`Are you sure you want to delete the company with Business ID: ${businessID}?`)) {
                        event.target.closest('.invoice-company').remove();
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `delete_invoice_companies[]`;
                        input.value = businessID;
                        wrapper.appendChild(input);
                    }
                }
            });
        });
    </script>
<?php
}

add_action('personal_options_update', 'b2b_invoice_save_user_fields');
add_action('edit_user_profile_update', 'b2b_invoice_save_user_fields');

function b2b_invoice_save_user_fields($user_id)
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    update_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', isset($_POST['can_invoice_order']) ? 'yes' : '');
    update_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', isset($_POST['disable_invoice_order']) ? 'yes' : 'no');

    if (isset($_POST['delete_invoice_companies'])) {
        foreach ($_POST['delete_invoice_companies'] as $business_id) {
            unset($companies[$business_id]);
        }
    }

    if (isset($_POST['invoice_companies'])) {
        $clean = [];
        foreach ($_POST['invoice_companies'] as $key => $company) {
            $new_business_id = sanitize_text_field($company['business_id'] ?? $key);
            $clean[$new_business_id] = [
                'company_name' => sanitize_text_field($company['company_name'] ?? ''),
                'business_id' => $new_business_id,
                'e_invoice_address' => sanitize_text_field($company['e_invoice_address'] ?? ''),
                'intermediary' => sanitize_text_field($company['intermediary'] ?? ''),
            ];
        }
        update_user_meta($user_id, 'wc_b2b_ic_invoice_companies', $clean);
    } else {
        delete_user_meta($user_id, 'wc_b2b_ic_invoice_companies');
    }
}
