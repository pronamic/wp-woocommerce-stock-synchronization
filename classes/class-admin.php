<?php

/**
 * Provides the settings and admin page
 */
class Pronamic_WP_WC_StockSyncAdmin {
	/**
	 * Bootstraps the admin part
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		// Actions
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Initializes admin
	 */
	public function admin_init() {
		// Settings - Pages
		add_settings_section(
			'woocommerce_stock_sync_general',
			__( 'General', 'woocommerce_stock_sync' ),
			'__return_false',
			'woocommerce_stock_sync'
		);

		add_settings_field(
			'woocommerce_stock_sync_urls',
			__( 'URLs', 'pronamic_companies' ),
			array( $this, 'input_urls' ),
			'woocommerce_stock_sync',
			'woocommerce_stock_sync_general',
			array( 'label_for' => 'woocommerce_stock_sync_urls' )
		);

		add_settings_field(
			'woocommerce_stock_sync_password',
			__( 'Password', 'woocommerce_stock_sync' ),
			array( $this, 'input_password' ),
			'woocommerce_stock_sync',
			'woocommerce_stock_sync_general',
			array( 'label_for' => 'woocommerce_stock_sync_password' )
		);

		register_setting( 'woocommerce_stock_sync', 'woocommerce_stock_sync_urls', array( $this, 'sanitize_urls' ) );
		register_setting( 'woocommerce_stock_sync', 'woocommerce_stock_sync_password' );

		// Empty log
		if ( filter_has_var( INPUT_POST, 'pronamic_wc_stock_sync_empty_log' ) ) {
			update_option( 'wc_stock_sync_log', array() );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Input text
	 *
	 * @param array $args
	 */
	public function input_password( $args ) {
		printf(
			'<input name="%s" id="%s" type="text" value="%s" class="%s" />',
			esc_attr( $args['label_for'] ),
			esc_attr( $args['label_for'] ),
			esc_attr( get_option( $args['label_for'] ) ),
			'regular-text code'
		);
	}

	/**
	 * Input text
	 *
	 * @param array $args
	 */
	public function input_urls( $args ) {
		$name = $args['label_for'];

		$urls = get_option( $name, array() );

		$i = '';

		foreach ( $urls as $url ) {
			printf(
				'<input name="%s[]" id="%s" type="url" value="%s" class="%s" />',
				esc_attr( $name ),
				esc_attr( $name . $i ),
				esc_attr( $url ),
				'regular-text code'
			);

			echo '<br />';

			$i++;
		}

		printf(
			'<input name="%s[]" id="%s" type="url" value="%s" class="%s" />',
			esc_attr( $name ),
			esc_attr( $name . $i++ ),
			esc_attr( '' ),
			'regular-text code'
		);
	}

	//////////////////////////////////////////////////

	/**
	 * Should be called on admin_menu hook. Adds settings pages to the admin menu.
	 */
	public function admin_menu() {
		add_submenu_page(
			'woocommerce', // parent_slug
			__( 'WooCommerce Stock Synchronization', 'woocommerce_stock_sync' ), // page_title
			__( 'Stock Synchronization', 'woocommerce_stock_sync' ), // menu_title
			'manage_options', // capability
			'woocommerce_stock_sync', // menu_slug
			array( $this, 'settings_page' ) // function
		);
	}

	//////////////////////////////////////////////////

	/**
	 * Settings page
	 */
	public function settings_page() {
		include $this->plugin->dir . 'admin/settings.php';
	}

	//////////////////////////////////////////////////

	/**
	 * Sanitizes list of synched sites, unifying all newline characters to the same newline character
	 */
	public function sanitize_urls( $data ) {
		$urls = array();

		if ( is_array( $data ) ) {
			foreach ( $data as $value ) {
				$url = filter_var( $value, FILTER_VALIDATE_URL );

				if ( $url ) {
					$urls[] = trailingslashit( $url );
				}
			}
		}

		return $urls;
	}

	//////////////////////////////////////////////////
	// Meta boxes
	//////////////////////////////////////////////////

	public function meta_boxes() {

	}
}
