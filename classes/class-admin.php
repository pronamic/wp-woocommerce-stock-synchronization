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
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'push_stock' ) );

		add_filter( 'option_page_capability_woocommerce_stock_sync', array( $this, 'option_page_capability' ) );
	}

	/**
	 * Initializes admin
	 */
	public function admin_init() {
		// Settings
		add_settings_section(
			'woocommerce_stock_sync_general',
			__( 'General', 'woocommerce_stock_sync' ),
			'__return_false',
			'woocommerce_stock_sync'
		);

		add_settings_field(
			'woocommerce_stock_sync_urls',
			__( 'URLs', 'woocommerce_stock_sync' ),
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

		// Action - Empty Log
		if ( filter_has_var( INPUT_POST, 'woocommerce_stock_sync_empty_log' ) && check_admin_referer( 'woocommerce_stock_sync_empty_log', 'woocommerce_stock_sync_nonce' ) ) {
			update_option( 'wc_stock_sync_log', array() );

			wp_safe_redirect( add_query_arg( 'deleted', true ) );

			exit;
		}
	}

	/**
	 * Filter required capability for option page.
	 *
	 * @return string
	 */
	public function option_page_capability() {
		// WooCommerce shop manager.
		return 'manage_woocommerce';
	}

	/**
	 * Action - Push Stock
	 **/
	public function push_stock() {
		if ( ! filter_has_var( INPUT_POST, 'woocommerce_stock_sync_push' ) && ! filter_has_var( INPUT_GET, 'push_stock' ) ) {
			return;
		}

		if ( ! check_admin_referer( 'woocommerce_stock_sync_push', 'woocommerce_stock_sync_nonce' ) ) {
			return;
		}

		if ( filter_has_var( INPUT_POST, 'woocommerce_stock_sync_push' ) ) {
			$push_stock = $this->get_stock();

			update_option( 'wc_stock_sync_push_stock', $push_stock );
		}

		$push_stock = get_option( 'wc_stock_sync_push_stock' );

		if ( ! is_array( $push_stock ) ) {
			return;
		}

		$stock = array_slice( $push_stock, 0, 15, true );

		$this->plugin->synchronizer->synchronize_stock( $stock );

		$push_stock = array_slice( $push_stock, 15, null, true );

		update_option( 'wc_stock_sync_push_stock', $push_stock );

		$stock_pushed = 0;

		if ( filter_has_var( INPUT_GET, 'push_stock' ) ) {
			$stock_pushed = filter_input( INPUT_GET, 'push_stock' );
		}

		$query_args = array(
			'push_stock' => ( $stock_pushed + 15 ),
			'synced'     => null,
		);

		if ( 0 === count( $push_stock ) ) {
			$query_args = array(
				'push_stock' => null,
				'synced'     => true,
			);

			delete_option( 'wc_stock_sync_push_stock' );
		}

		$query_args['woocommerce_stock_sync_nonce'] = wp_create_nonce( 'woocommerce_stock_sync_push' );

		?>

		<script type="text/javascript">
			setTimeout(
				function() {
					window.location.href = "<?php echo esc_url_raw( add_query_arg( $query_args ) ); ?>";
				},
				1250
			);
		</script>

		<?php
	}

	/**
	 * Get the stock of all products
	 *
	 * @return array
	 */
	private function get_stock() {
		global $wpdb;

		// Result
		$stock = array();

		// Query
		$query = "
			SELECT
				meta_sku.meta_value AS sku,
				meta_qty.meta_value AS qty
			FROM
				$wpdb->posts AS post
					INNER JOIN
				$wpdb->postmeta AS meta_sku
						ON post.ID = meta_sku.post_id AND meta_sku.meta_key = '_sku' AND meta_sku.meta_value != ''
					INNER JOIN
				$wpdb->postmeta AS meta_qty
						ON post.ID = meta_qty.post_id AND meta_qty.meta_key = '_stock'
			WHERE
				post.post_type IN ( 'product', 'product_variation' )
			;
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepare is OK.
		$results = $wpdb->get_results( $query );

		// Loop
		foreach ( $results as $result ) {
			$stock[ $result->sku ] = $result->qty;
		}

		return $stock;
	}

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
			'regular-text'
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
				'regular-text'
			);

			echo '<br />';

			$i++;
		}

		printf(
			'<input name="%s[]" id="%s" type="url" value="%s" class="%s" />',
			esc_attr( $name ),
			esc_attr( $name . ( $i++ ) ),
			esc_attr( '' ),
			'regular-text'
		);
	}

	/**
	 * Should be called on admin_menu hook. Adds settings pages to the admin menu.
	 */
	public function admin_menu() {
		add_submenu_page(
			'woocommerce', // parent_slug
			__( 'WooCommerce Stock Synchronization', 'woocommerce_stock_sync' ), // page_title
			__( 'Stock Synchronization', 'woocommerce_stock_sync' ), // menu_title
			'manage_woocommerce', // capability
			'woocommerce_stock_sync', // menu_slug
			array( $this, 'admin_page' ) // function
		);
	}

	/**
	 * Settings page
	 */
	public function admin_page() {
		include $this->plugin->dir . 'admin/page.php';
	}

	/**
	 * Sanitizes list of synced sites, unifying all newline characters to the same newline character
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
}
