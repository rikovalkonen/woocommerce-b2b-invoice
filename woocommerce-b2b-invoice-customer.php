<?php
/*
Plugin Name: WooCommerce B2B Invoice Customers
Description: Adds a custom WooCommerce payment gateway for B2B customers, allowing invoice-based payments with advanced restrictions and user-specific settings.
Version: 1.0.0
Author: Riko Valkonen
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woocommerce-b2b-invoice-customers
*/

defined('ABSPATH') || exit;

define('WCB2B_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once WCB2B_PLUGIN_DIR . 'includes/gateway.php';
require_once WCB2B_PLUGIN_DIR . 'includes/order-meta.php';
require_once WCB2B_PLUGIN_DIR . 'includes/user-profile-fields.php';
