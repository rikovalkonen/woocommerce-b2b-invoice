<?php
add_filter('woocommerce_payment_gateways', 'add_b2b_invoice_gateway');
function add_b2b_invoice_gateway($gateways) {
    $gateways[] = 'WC_Gateway_B2B_Invoice';
    return $gateways;
}

add_action('plugins_loaded', 'init_b2b_invoice_gateway');
function init_b2b_invoice_gateway() {
    class WC_Gateway_B2B_Invoice extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'b2b_invoice';
            $this->method_title = 'Pay by Invoice';
            $this->method_description = 'Allow selected users to pay via invoice.';
            $this->has_fields = false;

            $this->enabled = 'yes';
            $this->title = 'Pay by Invoice';

            $this->init_form_fields();
            $this->init_settings();

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $status = get_option('b2b_invoice_order_status', 'on-hold');
            $order->update_status($status, 'Invoice payment method selected');
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }
    }
}
