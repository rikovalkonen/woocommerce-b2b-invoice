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

add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!is_user_logged_in() && ($_POST['selected_invoice_company'] || $_POST['invoice_customer_reference'])) return;

    $meta_updated = false;
    $user_id = get_current_user_id();
    $companies = get_user_meta($user_id, 'wc_b2b_ic_invoice_companies', true);
    $selected_index = $_POST['selected_invoice_company'] ?? null;
    $reference = sanitize_text_field($_POST['invoice_customer_reference'] ?? '');

    if ($selected_index !== null && isset($companies[$selected_index])) {
        $company = $companies[$selected_index];

        if ($company) {
            $order->update_meta_data('_wc_b2b_ic_invoice_company_name', $company['company_name']);
            $order->update_meta_data('_wc_b2b_ic_invoice_company_id', $company['business_id']);
            $order->update_meta_data('_wc_b2b_ic_invoice_company_einvoice_address', $company['e_invoice_address']);
            $order->update_meta_data('_wc_b2b_ic_invoice_company_intermediary', $company['intermediary']);
            $meta_updated = true;
        }
    }

    if ($reference) {
        $order->update_meta_data('_wc_b2b_ic_invoice_customer_reference', $reference);
        $meta_updated = true;
    }

    if ($meta_updated) {
        $order->save();
    }
}, 10, 2);

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $company_name = $order->get_meta('_wc_b2b_ic_invoice_company_name');
    $company_id = $order->get_meta('_wc_b2b_ic_invoice_company_id');
    $einvoice_address = $order->get_meta('_wc_b2b_ic_invoice_company_einvoice_address');
    $intermediary = $order->get_meta('_wc_b2b_ic_invoice_company_intermediary');

    if ($company_name) {
        echo '<p><strong>' . __('Company name', 'woocommerce-b2b-invoice') . ':</strong><br>' . esc_html($company_name) . '</p>';
    }
    if ($company_id) {
        echo '<p><strong>' . __('Business ID', 'woocommerce-b2b-invoice') . ':</strong><br>' . esc_html($company_id) . '</p>';
    }
    if ($einvoice_address) {
        echo '<p><strong>' . __('E-invoice address', 'woocommerce-b2b-invoice') . ':</strong><br>' . esc_html($einvoice_address) . '</p>';
    }
    if ($intermediary) {
        echo '<p><strong>' . __('Intermediary', 'woocommerce-b2b-invoice') . ':</strong><br>' . esc_html($intermediary) . '</p>';
    }
});

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $reference = $order->get_meta('_wc_b2b_ic_invoice_customer_reference');
    if ($reference) {
        echo '<p><strong>' . __('Reference', 'woocommerce-b2b-invoice') . ':</strong><br>' . esc_html($reference) . '</p>';
    }
});

add_filter('woocommerce_email_order_meta_fields', function ($fields, $sent_to_admin, $order) {
    $disable_invoice_fields = apply_filters('wc_b2b_disable_invoice_fields', false, $order, $sent_to_admin);
    if ($disable_invoice_fields) {
        return $fields;
    }
    $company_name = $order->get_meta('_wc_b2b_ic_invoice_company_name');
    $company_id = $order->get_meta('_wc_b2b_ic_invoice_company_id');
    $einvoice_address = $order->get_meta('_wc_b2b_ic_invoice_company_einvoice_address');
    $intermediary = $order->get_meta('_wc_b2b_ic_invoice_company_intermediary');
    $reference = $order->get_meta('_wc_b2b_ic_invoice_customer_reference');
    if ($reference) {
        $fields['invoice_customer_reference'] = [
            'label' => __('Invoice reference', 'woocommerce-b2b-invoice'),
            'value' => esc_html($reference)
        ];
    }
    if ($company_name) {
        $fields['invoice_company_name'] = [
            'label' => __('Company name', 'woocommerce-b2b-invoice'),
            'value' => esc_html($company_name)
        ];
    }
    if ($company_id) {
        $fields['invoice_company_id'] = [
            'label' => __('Business ID', 'woocommerce-b2b-invoice'),
            'value' => esc_html($company_id)
        ];
    }
    if ($einvoice_address) {
        $fields['invoice_company_einvoice_address'] = [
            'label' => __('E-invoice address', 'woocommerce-b2b-invoice'),
            'value' => esc_html($einvoice_address)
        ];
    }
    if ($intermediary) {
        $fields['invoice_company_intermediary'] = [
            'label' => __('Intermediary', 'woocommerce-b2b-invoice'),
            'value' => esc_html($intermediary)
        ];
    }
    return $fields;
}, 10, 3);
