<?php

function woocommerce_b2b_invoice_load_textdomain()
{
    load_plugin_textdomain(
        'woocommerce-b2b-invoice',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('init', 'woocommerce_b2b_invoice_load_textdomain');
