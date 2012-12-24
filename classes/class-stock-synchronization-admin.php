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
		register_setting( 'stock-synchronization-settings', 'stock-synchronization-synced-sites', array( __CLASS__, 'sanitize_synced_sites' ) );
		register_setting( 'stock-synchronization-settings', 'stock-synchronization-synced-sites-password' );
	}

	/**
	 * Should be called on admin_menu hook. Adds settings pages to the admin menu.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'tools.php', // parent_slug
			__( 'Stock synchronization', 'stock-synchronization' ), // page_title
			__( 'Stock synchronization', 'stock-synchronization' ), // menu_title
			'manage_options', // capability
			'stock-synchronization-settings', // menu_slug
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
	public static function sanitize_synced_sites( $synced_sites ) { // TODO Sanitize better
		// Unify newline character
		return str_replace(
			array( "\r", "\n", "\r\n", "\n\r" ),
			"\r\n",
			$synced_sites
		);
	}
	
	/**
	 * Get synced sites
	 * 
	 * @return array
	 */
	public static function get_synced_sites() {
		return explode( "\r\n", get_option( 'stock-synchronization-synced-sites', '' ) );
	}
	
	/**
	 * Get synced sites password
	 *
	 * @return string
	 */
	public static function get_synced_sites_password() {
		return get_option( 'stock-synchronization-synced-sites-password', '' );
	}
}