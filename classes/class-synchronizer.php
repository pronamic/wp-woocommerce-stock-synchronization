<?php

/**
 * Class Stock_Synchronization_Synchronizer contains functions to notify
 * and be notified by other websites that it's synced with.
 */
class Pronamic_WP_WC_StockSyncSynchronizer {
	/**
	 * Queue for the stock to synchronize
	 *
	 * @var string
	 */
	private $queue_stock;

	/**
	 * Flag to process synchronization.
	 *
	 * @var boolean
	 */
	private $process_sync;

	//////////////////////////////////////////////////

	/**
	 * Bootstraps the synchronizer
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		$this->queue_stock  = array();
		$this->process_sync = false;

		// Actions
		add_action( 'init', array( $this, 'maybe_synchronize' ) );

		// Synchronize actions

		// Product - Set Stock
		// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L164-L206
		add_action( 'woocommerce_product_set_stock', array( $this, 'product_set_stock' ) );

		// Product Variation - Set Stock
		// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/class-wc-product-variation.php#L389-L440
		add_action( 'woocommerce_variation_set_stock', array( $this, 'product_set_stock' ) );

		// Shutdown
		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Product set stock
	 *
	 * @param WC_Product $product
	 */
	public function product_set_stock( $product ) {
		// Check if the product variable is indeed an WooCommerce product object
		// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L13
		if ( ! ( $product instanceof WC_Product ) ) {
			return;
		}

		// Check if the stock is managed so we are sure it should be synchronized
		// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L484-L491
		if ( ! $product->managing_stock() ) {
			return;
		}

		// WooCommerce Multilingual
		if ( defined( 'ICL_LANGUAGE_CODE' ) && defined( 'WCML_VERSION' ) ) {
			global $woocommerce_wpml;

			if ( is_object( $woocommerce_wpml ) && isset( $woocommerce_wpml->products ) && method_exists( $woocommerce_wpml->products, 'is_original_product' ) ) {
				if ( ! $woocommerce_wpml->products->is_original_product( $product->get_id() ) ) {
					return;
				}
			}
		}

		// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L123-L130
		$sku = $product->get_sku();

		// Check if the SKU is not empty so we have an unique identifier
		if ( ! empty( $sku ) ) {
			// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L132-L139
			$qty = $product->get_stock_quantity();

			if ( is_null( $qty ) ) {
				$qty = 0;
			}

			// Map
			$this->queue_stock[ $sku ] = $qty;
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Get synchronize URL, make sure we encode the parameters.
	 *
	 * @see https://core.trac.wordpress.org/browser/tags/4.0/src/wp-includes/functions.php#L720
	 * @see https://core.trac.wordpress.org/browser/tags/4.0/src/wp-includes/functions.php#L654
	 *
	 * @param string $uri
	 * @return string
	 */
	public function get_sync_url( $url ) {
		$url = add_query_arg( urlencode_deep( array(
			'wc_stock_sync' => true,
			'source'        => wp_parse_url( site_url( '/' ), PHP_URL_HOST ),
			'password'      => get_option( 'woocommerce_stock_sync_password' ),
		) ), $url );

		return $url;
	}

	//////////////////////////////////////////////////

	/**
	 * Synchronize the stock
	 *
	 * @param array $map
	 */
	public function synchronize_stock( $stock ) {
		$urls = get_option( 'woocommerce_stock_sync_urls', array() );

		if ( ! is_array( $urls ) ) {
			return;
		}

		foreach ( $urls as $url ) {
			$request_url = $this->get_sync_url( $url );

			$result = wp_remote_post( $request_url, array(
				'body'    => wp_json_encode( $stock ),
				'timeout' => 45,
			) );

			// @see https://github.com/WordPress/WordPress/blob/4.0/wp-includes/http.php#L241-L256https://github.com/WordPress/WordPress/blob/4.0/wp-includes/http.php#L241-L256
			$response_code = wp_remote_retrieve_response_code( $result );

			$body = wp_remote_retrieve_body( $result );

			$data = json_decode( $body );

			$log       = new stdClass();
			$log->time = time();

			if ( ( 200 == $response_code ) && $data ) { // WPCS: loose comparison ok.
				$log->message = sprintf(
					/* translators: 1: url, 2: response code */
					__( 'Succeeded - Synchronization to: %1$s (response code: %2$s)', 'woocommerce_stock_sync' ),
					sprintf( '<code>%s</code>', $url ),
					sprintf( '<code>%s</code>', $response_code )
				);
			} else {
				$error = '';

				if ( is_wp_error( $result ) ) {
					$error = $result->get_error_message();
				}

				$log->message = sprintf(
					/* translators: 1: url, 2: response code, 3: error */
					__( 'Failed - Synchronization to: %1$s (response code: %2$s, error: %3$s)', 'woocommerce_stock_sync' ),
					sprintf( '<code>%s</code>', $url ),
					sprintf( '<code>%s</code>', $response_code ),
					sprintf( '<code>%s</code>', $error )
				);
			}

			$this->plugin->log( $log );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Maybe synchronize
	 */
	public function maybe_synchronize() {
		global $post;

		if ( filter_has_var( INPUT_GET, 'wc_stock_sync' ) ) {
			$password = get_option( 'woocommerce_stock_sync_password' );

			$password_input = filter_input( INPUT_GET, 'password', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );

			$this->process_sync = ( $password === $password_input );
		}

		if ( ! $this->process_sync ) {
			return;
		}

		// From
		$source = filter_input( INPUT_GET, 'source', FILTER_SANITIZE_STRING );

		$log          = new stdClass();
		$log->time    = time();
		$log->message = sprintf(
			/* translators: %s: <code>source URL</code> */
			__( 'Received synchronization request from %s', 'woocommerce_stock_sync' ),
			sprintf( '<code>%s</code>', $source )
		);

		$this->plugin->log( $log );

		// Stock
		$data  = file_get_contents( 'php://input' );
		$stock = json_decode( $data, true );

		$response          = new stdClass();
		$response->version = $this->plugin->get_version();
		$response->result  = false;

		if ( ! is_array( $stock ) ) {
			return;
		}

		$response->result = true;
		$response->stock  = $stock;

		foreach ( $stock as $sku => $quantity ) {
			$product_id = wc_get_product_id_by_sku( $sku );

			wc_update_product_stock( $product_id, $quantity );
		}

		// Send JSON
		// @see https://github.com/WordPress/WordPress/blob/4.0/wp-includes/functions.php#L2614-L2629
		wp_send_json( $response );
	}

	//////////////////////////////////////////////////

	/**
	 * Shutdown
	 */
	public function shutdown() {
		// Queue stock synchronize
		if ( ! empty( $this->queue_stock ) && ! $this->process_sync ) {
			$this->synchronize_stock( $this->queue_stock );
		}
	}
}
