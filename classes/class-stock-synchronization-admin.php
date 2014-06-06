<?php

/**
 * Provides the settings and admin page
 */
class Stock_Synchronization_Admin {

	/**
	 * Bootstraps the admin part
	 */
	public static function bootstrap() {
		add_action( 'admin_init', array( __CLASS__, 'init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'wp_ajax_stock_sync_single_product', array( __CLASS__, 'ajax_stock_synchronization' ) );
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

	public static function enqueue_scripts() {
		wp_enqueue_script( 'woocommerce_stock_sync_admin', plugins_url( 'assets/stock-synchronization-admin.js', Stock_Synchronization::$file ) );

		wp_localize_script( 'woocommerce_stock_sync_admin', 'StockSynchronizationVars', array(
			'single_product' => array(
				'spinner' => admin_url( 'images/wpspin_light.gif' ),
				'sync_success_success_message' => __( 'Synchronization was successful!', 'woocommerce_stock_sync' )
			)
		) );
	}

	public static function meta_boxes() {
		add_meta_box(
			'stock_synchronization',
			__( 'Stock Synchronization', 'woocommerce-stock-synchronization' ),
			array( __CLASS__, 'view_stock_synchronization_meta_box' ),
			'product',
			'side'
		);
	}

	public static function view_stock_synchronization_meta_box() {
		?>
		<script type="text/javascript">
			jQuery(StockSynchronizationAdmin.single_product.ready);
		</script>
		<div class="stock_synchronization_holder">
			<div class="jStockSync"></div>
			<button class="jSyncSingleProductButton button button-primary"><?php _e( 'Synchronize', 'woocommerce_stock_sync' ); ?></button>
			<span class="jSync_spinner_holder"></span>
		</div>

		<?php
	}

	public static function ajax_stock_synchronization() {
		$post_type = filter_input( INPUT_POST, 'post_type', FILTER_SANITIZE_STRING );
		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );

		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
			if ( $post_type == 'product' ) {
				$product = new WC_Product( $post_id );
			} else if ( $post_type == 'product_variation' ) {
				$product = new WC_Product_Variation( $post_id );
			}
		} else {
			$product = get_product( $post_id );
		}

		// Get all variations
		$variations = get_posts( 'post_parent=' . $post_id . '&post_type=product_variation&orderby=menu_order&order=ASC&fields=ids&post_status=any&numberposts=-1' );

		foreach ( Stock_Synchronization::$synced_sites as $site ) {

			$result = Stock_Synchronization_Synchronizer::synchronize_product( $product, $site );

			if ( $result instanceof WP_Error ) {
				$response = array( 'url' => $site, 'resp' => false, 'errors' => $result->get_error_messages() );
			} else {
				$response = array( 'url' => $site, 'resp' => true );
			}

			if ( ! empty( $variations ) ) {
				foreach ( $variations as $variation ) {

					if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
						$variation_product = new WC_Product_Variation( $variation );
					} else {
						$variation_product = get_product( $variation );
					}

					$result = Stock_Synchronization_Synchronizer::synchronize_product( $variation_product, $site );

					if ( $result instanceof WP_Error ) {
						$response['variations'][] = array( 'url' => $site, 'resp' => false, 'errors' => $result->get_error_messages() );
					} else {
						$response['variations'][] = array( 'url' => $site, 'resp' => true );
					}
				}
			}
		}

		wp_send_json( $response );
	}
}
