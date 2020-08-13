<?php
/*
Plugin Name: WooCommerce Stock Synchronization
Plugin URI: https://www.pronamic.eu/plugins/woocommerce-stock-synchronization/
Description: Synchronizes stock with sites that are connected to one another, using WooCommerce Stock Synchronization.

Version: 2.5.0
Requires at least: 4.7

WC requires at least: 2.2.0
WC tested up to: 4.3.2

Author: Pronamic
Author URI: https://www.pronamic.eu/

Text Domain: woocommerce_stock_sync
Domain Path: /languages/

License: GPLv2

GitHub URI: https://github.com/pronamic/wp-woocommerce-stock-synchronization
*/

$dir = plugin_dir_path( __FILE__ );

require_once $dir . 'classes/class-plugin.php';
require_once $dir . 'classes/class-admin.php';
require_once $dir . 'classes/class-synchronizer.php';

/**
 * Plugin
 */
new Pronamic_WP_WC_StockSyncPlugin( __FILE__ );
