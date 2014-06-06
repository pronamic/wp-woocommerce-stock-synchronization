<?php
/*
Plugin Name: WooCommerce Stock Synchronization
Plugin URI: http://www.happywp.com/plugins/woocommerce-stock-synchronization/
Description: Synchronizes stock with sites that are connected to one another, using WooCommerce Stock Synchronization.

Version: 1.1.2
Requires at least: 3.0

Author: Pronamic
Author URI: http://www.pronamic.eu/

Text Domain: stock-synchronization
Domain Path: /languages/

License: GPLv2

GitHub URI: https://github.com/pronamic/wp-woocommerce-stock-synchronization
*/

/**
 * Main class, bootstraps the plugin
 */
class Stock_Synchronization {
	/**
	 * The plugin file
	 *
	 * @var string
	 */
	public static $file;

	/**
	 * Log option name
	 *
	 * @var string
	 */
	public static $log_option_name = 'stock-synchronization-log';

	/**
	 * Maximum log length in number of rows
	 *
	 * @var int
	 */
	public static $max_log_length = 50;

	/**
	 * Sites to notify of stock change
	 *
	 * @var mixed
	 */
	public static $synced_sites;

	/**
	 * Password shared among sites in sychronization network
	 *
	 * @var string
	 */
	public static $synced_sites_password;

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 *
	 * @param string file path and name
	 */
	public static function bootstrap( $file ) {
		self::$file = $file;

		self::autoload();

		self::$synced_sites          = get_option( 'woocommerce_stock_sync_urls', array() );
		self::$synced_sites_password = get_option( 'woocommerce_stock_sync_password' );

		Stock_Synchronization_Synchronizer::Bootstrap();

		if ( is_admin() ) {
			Stock_Synchronization_Admin::Bootstrap();
		}

		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Initialize
	 */
	public static function init() {
		load_plugin_textdomain( 'woocommerce_stock_sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	//////////////////////////////////////////////////

	/**
	 * Returns path to the base directory of this plugin
	 *
	 * @return string pluginPath
	 */
	public static function get_plugin_path() {
		return dirname( __FILE__ );
	}

	//////////////////////////////////////////////////

	/**
	 * This function will load classes automatically on-call.
	 */
	public static function autoload() {
		if ( ! function_exists( 'spl_autoload_register' ) ) {
			return;
		}

		function stock_synchronization_autoload( $name ) {
			$name = strtolower( str_replace( '_', '-', $name ) );
			$file = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class-' . $name . '.php';

			if ( is_file( $file ) ) {
				require_once $file;
			}
		}

		spl_autoload_register( 'stock_synchronization_autoload' );
	}
}

/**
 * Bootsrap application
 */
Stock_Synchronization::bootstrap( __FILE__ );
