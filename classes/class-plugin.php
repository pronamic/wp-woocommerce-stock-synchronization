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

	//////////////////////////////////////////////////

	/**
	 * Maximum log length in number of rows
	 *
	 * @var int
	 */
	public $max_log_length = 50;

	/**
	 * Sites to notify of stock change
	 *
	 * @var mixed
	 */
	public $synced_sites;

	/**
	 * Password shared among sites in sychronization network
	 *
	 * @var string
	 */
	public $synced_sites_password;

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 *
	 * @param string file path and name
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->dir  = plugin_dir_path( $file );

		$this->synchronizer = new Pronamic_WP_WC_StockSyncSynchronizer( $this );

		if ( is_admin() ) {
			$this->admin = new Pronamic_WP_WC_StockSyncAdmin( $this );
		}

		add_action( 'init', array( $this, 'init' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Initialize
	 */
	public function init() {
		load_plugin_textdomain( 'woocommerce_stock_sync', false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
	}
}
