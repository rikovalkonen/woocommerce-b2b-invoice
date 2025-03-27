<?php
/*
Plugin Name: WooCommerce B2B Invoice Customers
Description: Custom invoice gateway.
Version: 0.1
Author: Riko Valkonen
*/

defined('ABSPATH') || exit;

define('WCB2B_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once WCB2B_PLUGIN_DIR . 'includes/gateway.php';
require_once WCB2B_PLUGIN_DIR . 'includes/restrict-gateway.php';
require_once WCB2B_PLUGIN_DIR . 'includes/order-meta.php';
require_once WCB2B_PLUGIN_DIR . 'includes/user-profile-fields.php';
require_once WCB2B_PLUGIN_DIR . 'includes/settings-behavior.php';
