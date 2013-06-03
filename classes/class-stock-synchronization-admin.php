<?php

/**
 * Provides the settings and admin page
 */
class Stock_Synchronization_Admin {
	
	/**
	 * Bootstraps the admin part
	 */
	public static function Bootstrap() {
		add_action( 'admin_init', array( __CLASS__, 'init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}
	
	/**
	 * Initializes admin
	 */
	public static function init() {
		// Settings - Pages
		add_settings_section(
			'woocommerce_stock_sync_general', // id
			__( 'General', 'woocommerce_stock_sync' ), // title
			'__return_false', // callback
			'woocommerce_stock_sync' // page
		);

		add_settings_field(
			'woocommerce_stock_sync_urls', // id
			__( 'URLs', 'pronamic_companies' ), // title
			array( __CLASS__, 'input_urls' ), // callback
			'woocommerce_stock_sync', // page
			'woocommerce_stock_sync_general', // section
			array( 'label_for' => 'woocommerce_stock_sync_urls' ) // args
		);

		add_settings_field(
			'woocommerce_stock_sync_password', // id
			__( 'Password', 'woocommerce_stock_sync' ), // title
			array( __CLASS__, 'input_password' ), // callback
			'woocommerce_stock_sync', // page
			'woocommerce_stock_sync_general', // section
			array( 'label_for' => 'woocommerce_stock_sync_password' ) // args
		);

		register_setting( 'woocommerce_stock_sync', 'woocommerce_stock_sync_urls', array( __CLASS__, 'sanitize_urls' ) );
		register_setting( 'woocommerce_stock_sync', 'woocommerce_stock_sync_password' );
	}

	/**
	 * Input text
	 *
	 * @param array $args
	 */
	public static function input_password( $args ) {
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
	public static function input_urls( $args ) {
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

	/**
	 * Should be called on admin_menu hook. Adds settings pages to the admin menu.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'woocommerce', // parent_slug
			__( 'WooCommerce Stock Synchronization', 'woocommerce_stock_sync' ), // page_title
			__( 'Stock Synchronization', 'woocommerce_stock_sync' ), // menu_title
			'manage_options', // capability
			'woocommerce_stock_sync', // menu_slug
			array( __CLASS__, 'settings_page' ) // function
		);
	}
	
	/**
	 * Settings page
	 */
	public static function settings_page() {
		include( Stock_Synchronization::get_plugin_path() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'settings.php' );
	}
	
	/**
	 * Sanitizes list of synched sites, unifying all newline characters to the same newline character
	 */
	public static function sanitize_urls( $data ) {
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
}
