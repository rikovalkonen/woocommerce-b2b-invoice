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
        $display = $company['company_name'] . "\nVAT: " . $company['y_tunnus'] . "\n" .
            "Verkkolaskutusosoite: " . $company['verkkolaskutusosoite'] . "\n" .
            "Välittäjä: " . $company['valittaja'] . "\n" .
            "Viite: " . $reference;

        if ($display) {
            $order->update_meta_data('_invoice_company_details', $display);
            $meta_updated = true;
        }
    }

    if ($reference) {
        $order->update_meta_data('_invoice_customer_reference', $reference);
        $meta_updated = true;
    }

    if ($meta_updated) {
        $order->save();
    }
}, 10, 2);

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $company = $order->get_meta('_invoice_company_details');
    if ($company) {
        echo '<p><strong>' . __('Yritys', 'woocommerce-b2b-invoice') . ':</strong><br>' . nl2br(esc_html($company)) . '</p>';
    }
});

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $reference = $order->get_meta('_invoice_customer_reference');
    if ($reference) {
        echo '<p><strong>Viite laskulle:</strong><br>' . nl2br(esc_html($reference)) . '</p>';
    }
});

add_filter('woocommerce_email_order_meta_fields', function ($fields, $sent_to_admin, $order) {
    $company = $order->get_meta('_invoice_company_details');
    $reference = $order->get_meta('_invoice_customer_reference');
    if ($reference) {
        $fields['invoice_customer_reference'] = [
            'label' => __('Viite laskulle', 'woocommerce-b2b-invoice'),
            'value' => nl2br(esc_html($reference))
        ];
    }
    if ($company) {
        $fields['invoice_company'] = [
            'label' => __('Yritys', 'woocommerce-b2b-invoice'),
            'value' => nl2br(esc_html($company))
        ];
    }
    return $fields;
}, 10, 3);
