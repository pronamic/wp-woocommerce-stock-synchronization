<?php
/*
Plugin Name: WooCommerce Stock Synchronization
Plugin URI: http://www.happywp.com/plugins/woocommerce-stock-synchronization/
Description: Synchronizes stock with sites that are connected to one another, using WooCommerce Stock Synchronization.

Version: 1.1.2
Requires at least: 3.0

Author: Pronamic
Author URI: http://www.pronamic.eu/

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
