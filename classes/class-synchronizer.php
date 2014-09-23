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

	//////////////////////////////////////////////////

	/**
	 * Bootstraps the synchronizer
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		$this->queue_stock = array();

		// Actions
		// add_action( 'init', array( $this, 'debug_response' ) );
		// add_action( 'init',	array( $this, 'maybe_synchronize' ) );
		add_action( 'init',	array( $this, 'maybe_synchronize' ) );

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
		if ( $product instanceof WC_Product ) {

			// Check if the stock is managed so we are sure it should be synchronized
			// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L484-L491
			if ( $product->managing_stock() ) {
				// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L123-L130
				$sku = $product->get_sku();

				// Check if the SKU is not empty so we have an unique identifier
				if ( ! empty( $sku ) ) {
					// @see https://github.com/woothemes/woocommerce/blob/v2.2.3/includes/abstracts/abstract-wc-product.php#L132-L139
					$qty = $product->get_stock_quantity();

					// Map
					$this->queue_stock[ $sku ] = $qty;
				}
			}
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Synchronize the stock
	 *
	 * @param array $map
	 */
	private function synchronize_stock( $stock ) {
		$urls     = get_option( 'woocommerce_stock_sync_urls', array() );
		$password = get_option( 'woocommerce_stock_sync_password' );

		if ( is_array( $urls ) ) {
			foreach ( $urls as $url ) {
				$request_url = add_query_arg( array(
					'wc_stock_sync' => true,
					'source'        => site_url( '/' ),
					'password'      => $password,
				), $url );

				$response = wp_remote_post( $request_url, array(
					'body' => json_encode( $stock ),
				) );

				// @see https://github.com/WordPress/WordPress/blob/4.0/wp-includes/http.php#L241-L256https://github.com/WordPress/WordPress/blob/4.0/wp-includes/http.php#L241-L256
				$response_code = wp_remote_retrieve_response_code( $response );

				if ( 200 == $response_code ) {
					$body = wp_remote_retrieve_body( $result );

					$message = sprintf(
						__( 'Synced to: %s (response code: %s)', '' ),
						sprintf( '<code>%s</code>', $url ),
						sprintf( '<code>%s</code>', $response_code )
					);

					$this->log_message( $message );
				} else {
					$error = '';
					if ( is_wp_error( $response ) ) {
						$error = $response->get_error_message();
					}

					$message = sprintf(
						__( 'Could not sync to: %s (response code: %s, error: %s)', '' ),
						sprintf( '<code>%s</code>', $url ),
						sprintf( '<code>%s</code>', $response_code ),
						sprintf( '<code>%s</code>', $error )
					);

					$this->log_message( $message );
				}
			}
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Maybe synchronize
	 */
	public function maybe_synchronize() {
		$this->process_sync = filter_has_var( INPUT_GET, 'wc_stock_sync' );

		if ( $this->process_sync ) {
			// Stock
			$stock = json_decode( file_get_contents( 'php://input' ), true );

			$this->log_message( 'Maybe sync !!!' );
			$this->log_message( json_encode( $stock ) );

			if ( is_array( $stock ) ) {

			}

			$object = new stdClass();
			$object->result = true;

			// Send JSON
			// @see https://github.com/WordPress/WordPress/blob/4.0/wp-includes/functions.php#L2614-L2629
			wp_send_json( $object );
		}
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

	//////////////////////////////////////////////////

	/**
	 * Called on 'woocommerce_restore_order_stock'
	 *
	 * @param WC_Order $order
	 */
	public function restore_order_stock( $order ) {
		$success = self::synchronize_order( $order, self::$restore_stock_action_name );

		self::log_message( sprintf( __( 'Restored order stock -[<a href="%s">%d</a>]- out of %d sites responded with success.', 'woocommerce_stock_sync' ),
			get_edit_post_link( $order->id ),
			$order->id,
			$success,
			count( Stock_Synchronization::$synced_sites )
		) );
	}

	public function synchronize_product( $product, $site ) {
		$skus = array();
		// Get the quantity
		$skus[ $product->get_sku() ] = $product->get_stock_quantity();

		// Remote post
		$result = wp_remote_post( $site, array(
			'body' => array(
				'woocommerce_stock_sync' => true,
				'source'   => site_url( '/' ),
				'password' => Stock_Synchronization::$synced_sites_password,
				'action'   => self::$synchronize_all_stock_action_name,
				'skus'     => $skus,
			),
		) );

		$body = wp_remote_retrieve_body( $result );

		if ( ! is_wp_error( $result ) && false !== strpos( $body, self::$synchronization_success_message ) ) {
			return true;
		} else {
			return $result;
		}
	}

	/**
	 * Contacts synced sites to notify them of the order action (reduce or restore of stock)
	 *
	 * @param WC_Order $order
	 * @param string $action
	 * @return int $succes sites confirmed succesful synchronization
	 */
	private function synchronize_order( $order, $action ) {
		$success = 0;

		// Build products array: sku => quantity
		$items = $order->get_items();
		$skus  = array();

		if ( is_array( $items ) && count( $items ) > 0 ) {
			foreach ( $items as $item ) {
				$product = $order->get_product_from_item( $item );
				$skus[ $product->get_sku() ] = $item['qty'];
			}
		} else {
			return 0;
		}

		// Notify synced websites
		if ( count( Stock_Synchronization::$synced_sites ) > 0 ) {

			foreach ( Stock_Synchronization::$synced_sites as $site ) {

				// Remote post
				$result = wp_remote_post( $site, array(
					'body' => array(
						'woocommerce_stock_sync' => true,
						'source'   => site_url( '/' ),
						'password' => Stock_Synchronization::$synced_sites_password,
						'action'   => $action,
						'skus'     => $skus,
					),
				) );

				$body = wp_remote_retrieve_body( $result );

				if ( ! is_wp_error( $result ) && false !== strpos( $body, self::$synchronization_success_message ) ) {
					$success++;
				}
			}
		} else {
			return 0;
		}

		return $success;
	}

	/**
	 * Contacts synced sites to synchronize the stock of all products
	 *
	 * The data send consists of the source website, a password, an action
	 * command and a list of SKUs with their stock quantities (SKU => stock quantitty)
	 */
	public function synchronize_all_stock() {
		$success = 0;
		$skus    = array();

		global $wpdb;

		$sql_query = "
			SELECT
				{$wpdb->posts}.ID ,
				MAX( IF( {$wpdb->postmeta}.meta_key = '_sku', {$wpdb->postmeta}.meta_value, NULL ) ) AS sku,
				MAX( IF( {$wpdb->postmeta}.meta_key = '_stock', {$wpdb->postmeta}.meta_value, NULL ) ) AS stock
			FROM
				{$wpdb->posts}
					LEFT JOIN
				{$wpdb->postmeta}
						ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
			WHERE
				{$wpdb->posts}.post_type = 'product'
					OR
				{$wpdb->posts}.post_type = 'product_variant'
			GROUP BY
				{$wpdb->posts}.ID
			ORDER BY
				{$wpdb->posts}.ID ASC
			;
		";

		$products = $wpdb->get_results( $sql_query, OBJECT );

		foreach ( $products as $product ) {
			if ( ! empty( $product->sku ) ) {
				$skus[ $product->sku ] = $product->stock;
			}
		}

		// Notify synced websites
		if ( count( Stock_Synchronization::$synced_sites ) > 0 ) {
			foreach ( Stock_Synchronization::$synced_sites as $site ) {
				// Remote post
				$result = wp_remote_post( $site, array(
					'body' => array(
						'woocommerce_stock_sync' => true,
						'source'                 => site_url( '/' ),
						'password'               => Stock_Synchronization::$synced_sites_password,
						'action'                 => self::$synchronize_all_stock_action_name,
						'skus'                   => $skus,
						'timeout'                => 300,
					),
				) );

				$body = wp_remote_retrieve_body( $result );

				if ( false !== strpos( $body, self::$synchronization_success_message ) ) {
					$success++;
				}
			}
		} else {
			return 0;
		}

		self::log_message( sprintf( __( 'Synchronized all stock - %d out of %d sites responded with success.', 'woocommerce_stock_sync' ),
			$success,
			count( Stock_Synchronization::$synced_sites )
		) );
	}

	/**
	 * Logs a message as a comment that can be read back from the admin screen
	 *
	 * @param string $message
	 */
	public function log_message( $message ) {
		$log = get_option( 'wc_stock_sync_log', array() );

		$message = date( 'd-m-o H:i:s' ) . ' - ' . $message;

		array_unshift( $log, $message );

		// Slice to maximum size
		$log = array_slice( $log, 0, 50 );

		// Write to log
		update_option( 'wc_stock_sync_log', $log );
	}
}
