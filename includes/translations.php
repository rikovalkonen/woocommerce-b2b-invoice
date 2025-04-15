<?php

function woocommerce_b2b_invoice_load_textdomain()
{
    load_plugin_textdomain(
        'woocommerce-b2b-invoice',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );

    error_log(__('Company name', 'woocommerce-b2b-invoice'));
}
add_action('init', 'woocommerce_b2b_invoice_load_textdomain');
