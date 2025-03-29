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


add_filter('woocommerce_payment_gateways', 'add_b2b_invoice_gateway');
function add_b2b_invoice_gateway($gateways)
{
    $gateways[] = 'WC_Gateway_B2B_Invoice';
    return $gateways;
}

add_action('woocommerce_loaded', 'init_b2b_invoice_gateway');
function init_b2b_invoice_gateway()
{
    class WC_Gateway_B2B_Invoice extends WC_Payment_Gateway
    {
        private $allowed_shipping_methods;

        public function __construct()
        {
            $this->id = 'b2b_invoice';
            $this->method_title = 'Pay by Invoice';
            $this->method_description = 'Allow selected users to pay via invoice.';
            $this->has_fields = true;

            $this->init_form_fields();

            $this->init_settings();

            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->allowed_shipping_methods = $this->get_option('allowed_shipping_methods', []);

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function is_available()
        {
            if (!is_user_logged_in()) {
                return false;
            }
            if (is_admin() || !WC()->cart->needs_shipping()) {
                return parent::is_available();
            }
            $chosen_methods = WC()->session->get('chosen_shipping_methods');
            $chosen_method  = is_array($chosen_methods) ? current($chosen_methods) : '';

            if (!in_array($chosen_method, $this->allowed_shipping_methods)) {
                return false;
            }

            return parent::is_available();
        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'     => array(
                    'title'       => __('Enable/Disable', 'woocommerce-b2b-invoice-customers'),
                    'label'       => __('Enable Invoice Payment', 'woocommerce-b2b-invoice-customers'),
                    'type'        => 'checkbox',
                    'default'     => 'yes'
                ),
                'title'       => array(
                    'title'       => __('Title', 'woocommerce-b2b-invoice-customers'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-b2b-invoice-customers'),
                    'default'     => __('Lasku yritykselle', 'woocommerce-b2b-invoice-customers')
                ),
                'description' => array(
                    'title'       => __('Description', 'woocommerce-b2b-invoice-customers'),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-b2b-invoice-customers'),
                    'default'     => __("Maksa kätevästi verkkolaskulla", 'woocommerce-b2b-invoice-customers')
                ),
                'allowed_shipping_methods' => array(
                    'title'       => __('Allowed Shipping Methods', 'woocommerce-b2b-invoice-customers'),
                    'type'        => 'multiselect',
                    'description' => __('Choose shipping methods allowed when using this gateway.', 'woocommerce-b2b-invoice-customers'),
                    'options'     => $this->get_shipping_zones_and_methods_for_select(),
                    'class'       => 'wc-enhanced-select',
                    'default'     => []
                )
            );
        }

        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            $user_id = $order->get_user_id();

            $allowed  = get_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', true) === 'yes';
            $disabled = get_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', true) === 'yes';

            if (!$allowed || $disabled) {
                $order->add_order_note('Invoice payment was attempted, but user is not eligible.');
                $order->update_status('failed', 'Invoice payment not allowed for this user.');

                wc_add_notice(__('Laskulla maksaminen ei ole sallittua tililläsi.', 'woocommerce-b2b-invoice-customers'), 'error');

                return [
                    'result' => 'failure',
                    'redirect' => wc_get_checkout_url(),
                ];
            }

            $status = get_option('b2b_invoice_order_status', 'on-hold');
            $order->update_status($status, 'Invoice payment method selected');

            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }

        public function validate_fields()
        {
            $user_id = get_current_user_id();
            $allowed = get_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', true) === 'yes';
            $disabled = get_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', true) === 'yes';

            if (!$allowed) {
                wc_add_notice(__('Et voi tällä hetkellä käyttää laskujen maksamista. Ota yhteyttä asiakaspalveluun.', 'woocommerce-b2b-invoice-customers'), 'error');
                return false;
            }

            if ($disabled) {
                wc_add_notice(__('Laskulla maksu on tilapäisesti poistettu käytöstä tililläsi. Ota yhteyttä asiakaspalveluun.', 'woocommerce-b2b-invoice-customers'), 'error');
                return false;
            }

            return true;
        }

        private function get_shipping_zones_and_methods_for_select()
        {
            $zones = WC_Shipping_Zones::get_zones();
            $zone_objects = [];
            $settings = get_option('woocommerce_b2b_invoice_settings');

            foreach ($zones as $zone_data) {
                $zone_objects[] = new WC_Shipping_Zone($zone_data['zone_id']);
            }

            $zone_objects[] = new WC_Shipping_Zone(0);

            $options = [];

            foreach ($zone_objects as $zone) {
                $zone_name = $zone->get_zone_name();
                $shipping_methods = $zone->get_shipping_methods();

                foreach ($shipping_methods as $method) {
                    $instance_id = $method->instance_id;
                    $method_id = $method->id;
                    $key = "{$method_id}:{$instance_id}";

                    $method_title = $method->get_title();
                    $label = "{$zone_name} – {$method_title}";
                    $options[$key] = $label;
                }
            }
            return $options;
        }


        public function payment_fields()
        {
            if (!is_user_logged_in()) return;

            $user_id = get_current_user_id();
            $allowed = get_user_meta($user_id, 'wc_b2b_ic_can_invoice_order', true) === 'yes';
            $disabled = get_user_meta($user_id, 'wc_b2b_ic_disable_invoice_order', true) === 'yes';

            if ($allowed && $disabled) {
                echo '<div class="woocommerce-info">' . esc_attr__('Laskulla maksu on tilapäisesti poistettu käytöstä tililläsi. Ota yhteyttä asiakaspalveluun.', 'woocommerce-b2b-invoice-customers') . '</div>';
                return;
            }

            $companies = get_user_meta($user_id, 'wc_b2b_ic_invoice_companies', true);
            if (empty($companies)) {
                echo '<div class="woocommerce-info">' . esc_attr__('Tililtäsi ei löytynyt laskutusyrityksiä, ota yhteyttä asiakaspalveluun.', 'woocommerce-b2b-invoice-customers') . '</div>';
                return;
            }
            echo '<input type="text" name="invoice_customer_reference" id="customer_reference" class="input-text" style="width:100%;" placeholder="' . esc_attr__('Viite laskulle', 'woocommerce-b2b-invoice-customers') . '" /></p>';
            echo '<p><strong>' . esc_attr__('Valitse yritys', 'woocommerce-b2b-invoice-customers') . ':</strong></p>';
            echo '<select name="selected_invoice_company" id="selected_invoice_company" class="woocommerce-select" style="width:100%;">';
            echo '<option disabled selected value="">' . esc_attr__('Yritys', 'woocommerce-b2b-invoice-customers') . '</option>';
            foreach ($companies as $index => $company) {
                $label = esc_html($company['company_name'] . ' - ' . $company['y_tunnus']);
                echo "<option value=\"" . $company['y_tunnus'] . "\" 
                    data-verkkolaskutusosoite=\"" . esc_attr($company['verkkolaskutusosoite']) . "\" 
                    data-valittaja=\"" . esc_attr($company['valittaja']) . "\">{$label}</option>";
            }
            echo '</select>';

            echo '<div id="invoice-details" style="margin-top: 1em; display:none;">';
            echo '<p><strong>' . esc_attr__('Verkkolaskutusosoite', 'woocommerce-b2b-invoice-customers') . ':</strong> <span id="verkkolaskutusosoite"></span></p>';
            echo '<p><strong>' . esc_attr__('Välittäjä', 'woocommerce-b2b-invoice-customers') . ':</strong> <span id="valittaja"></span></p>';
            echo '</div>';

?>
            <script>
                jQuery(document).ready(function($) {
                    $('#selected_invoice_company').on('change', function() {
                        const selected = this.options[this.selectedIndex];
                        const verkkolasku = selected.dataset.verkkolaskutusosoite || '';
                        const val = selected.dataset.valittaja || '';

                        if (verkkolasku && val) {
                            $('#verkkolaskutusosoite').text(verkkolasku);
                            $('#valittaja').text(val);
                            $('#invoice-details').show();
                        } else {
                            $('#invoice-details').hide();
                        }
                    });
                });
            </script>
<?php
        }
    }
}
