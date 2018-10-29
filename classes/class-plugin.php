<?php

/**
 * Main class, bootstraps the plugin
 */
class Pronamic_WP_WC_StockSyncPlugin {
	/**
	 * The plugin file
	 *
	 * @var string
	 */
	public $file;

	/**
	 * The plugin directory
	 *
	 * @var string
	 */
	public $dir;

	/**
	 * Version
	 *
	 * @var string
	 */
	public $version = '2.4.0';

	//////////////////////////////////////////////////

	/**
	 * Syncrhonizer object
	 *
	 * @var Pronamic_WP_WC_StockSyncSynchronizer
	 */
	public $synchronizer;

	/**
	 * Admin (only set in admin)
	 *
	 * @var Pronamic_WP_WC_StockSyncAdmin
	 */
	public $admin;

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 *
	 * @param string file path and name
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->dir  = plugin_dir_path( $file );

		// Actions
		add_action( 'init', array( $this, 'init' ) );

		// Other
		$this->synchronizer = new Pronamic_WP_WC_StockSyncSynchronizer( $this );

		if ( is_admin() ) {
			$this->admin = new Pronamic_WP_WC_StockSyncAdmin( $this );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Initialize
	 */
	public function init() {
		load_plugin_textdomain( 'woocommerce_stock_sync', false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
	}

	//////////////////////////////////////////////////

	/**
	 * Get version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	//////////////////////////////////////////////////

	/**
	 * Logs a message as a comment that can be read back from the admin screen
	 *
	 * @param string $message
	 */
	public function log( $item ) {
		$log = get_option( 'wc_stock_sync_log', array() );

		array_unshift( $log, $item );

		// Slice to maximum size
		$log = array_slice( $log, 0, 50 );

		// Write to log
		update_option( 'wc_stock_sync_log', $log );
	}
}
