<?php

/**
 * Class Stock_Synchronization_Synchronizer contains functions to notify
 * and be notified by other websites that it's synced with.
 */
class Stock_Synchronization_Synchronizer {
	/**
	 * Reduce stock action name
	 *
	 * @var string
	 */
	private static $reduce_stock_action_name = 'reduce_stock';

	/**
	 * Restore stock action name
	 *
	 * @var string
	 */
	private static $restore_stock_action_name = 'restore_stock';

	/**
	 * Synchronize all stock action name
	 *
	 * @var string
	 */
	private static $synchronize_all_stock_action_name = 'synchronize_all';

	/**
	 * Synchronization success message
	 *
	 * @var string
	 */
	private static $synchronization_success_message = '!synchronization_success!';

	/**
	 * Bootstraps the synchronizer
	 */
	public static function bootstrap() {
		add_action( 'init', array( __CLASS__, 'debug_response' ) );
		add_action( 'init',	array( __CLASS__, 'maybe_synchronize' ) );

		add_action( 'woocommerce_reduce_order_stock',	array( __CLASS__, 'reduce_order_stock' ) );
		add_action( 'woocommerce_restore_order_stock',	array( __CLASS__, 'restore_order_stock' ) );
	}

	/**
	 * Called on 'woocommerce_reduce_order_stock'
	 *
	 * @param WC_Order $order
	 */
	public static function reduce_order_stock( $order ) {
		$success = self::synchronize_order( $order, self::$reduce_stock_action_name );

		self::log_message( sprintf( __( 'Reduced order stock -[<a href="%s">%d</a>]- %d out of %d sites responded with success.', 'woocommerce_stock_sync' ),
			get_edit_post_link( $order->id ),
			$order->id,
			$success,
			count( Stock_Synchronization::$synced_sites )
		) );
	}

	/**
	 * Called on 'woocommerce_restore_order_stock'
	 *
	 * @param WC_Order $order
	 */
	public static function restore_order_stock( $order ) {
		$success = self::synchronize_order( $order, self::$restore_stock_action_name );

		self::log_message( sprintf( __( 'Restored order stock -[<a href="%s">%d</a>]- out of %d sites responded with success.', 'woocommerce_stock_sync' ),
			get_edit_post_link( $order->id ),
			$order->id,
			$success,
			count( Stock_Synchronization::$synced_sites )
		) );
	}

	public static function synchronize_product( $product, $site ) {
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

		if ( ! is_wp_error( $result ) && strpos( $body, self::$synchronization_success_message ) !== false ) {
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
	private static function synchronize_order( $order, $action ) {
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

				if ( ! is_wp_error( $result ) && strpos( $body, self::$synchronization_success_message ) !== false ) {
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
	public static function synchronize_all_stock() {
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

				if ( strpos( $body, self::$synchronization_success_message ) !== false ) {
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
	 * Receives all synchronization requests and handles them if source and password are correct.
	 */
	public static function maybe_synchronize() {
		if ( ! filter_has_var( INPUT_POST, 'woocommerce_stock_sync' ) ) {
			return;
		}

		set_time_limit( 0 );

		$source   = filter_input( INPUT_POST, 'source', FILTER_SANITIZE_STRING );
		$password = filter_input( INPUT_POST, 'password', FILTER_SANITIZE_STRING );
		$action   = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		$skus     = filter_input( INPUT_POST, 'skus', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );

		if ( ! in_array( trailingslashit( $source ), Stock_Synchronization::$synced_sites ) ) {
			return;
		}

		if ( $password != Stock_Synchronization::$synced_sites_password ) {
			return;
		}

		if ( ! is_array( $skus ) ) {
			$skus = array();
		}

		if ( empty( $skus ) ) {
			return;
		}

		global $wpdb;

		$sql_query = "
			SELECT
				{$wpdb->posts}.ID
			FROM
				{$wpdb->posts}
			WHERE
				{$wpdb->posts}.post_type = 'product'
					OR
				{$wpdb->posts}.post_type = 'product_variant'
					OR
				{$wpdb->posts}.post_type = 'product_variation'
			ORDER BY
				{$wpdb->posts}.ID ASC
			;
		";

		// Get all products and product variations by SKU
		$products = $wpdb->get_results( $sql_query, OBJECT );

		// Loop through query results, increase or decrease stock according to given stock quantities
		foreach ( $products as $query ) {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
				if ( $query->post_type == 'product' ) {
					$product = new WC_Product( $query->ID );
				} else if ( $query->post_type == 'product_variation' ) {
					$product = new WC_Product_Variation( $query->ID );
				}
			} else {
				$product = get_product( $query->ID );
			}

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$sku = $product->get_sku();

			if ( empty( $sku ) ) {
				continue;
			}

			if ( ! array_key_exists( $sku, $skus ) ) {
				continue;
			}

			$qty = $skus[ $sku ];

			// Choose action
			$name = __( 'unknown', 'woocommerce_stock_sync' );

			switch ( $action ) {
				case self::$reduce_stock_action_name:
					$name = __( 'reduce', 'woocommerce_stock_sync' );

					$product->reduce_stock( $qty );

					break;
				case self::$restore_stock_action_name:
					$name = __( 'restore', 'woocommerce_stock_sync' );

					$product->increase_stock( $qty );

					break;
				case self::$synchronize_all_stock_action_name:
					$name = __( 'synchronization', 'woocommerce_stock_sync' );

					$qty = $qty - $product->get_stock_quantity();

					if ( $qty > 0 ) {
						$product->increase_stock( $qty );
					} else if ( $qty < 0 ) {
						$product->reduce_stock( abs( $qty ) );
					}

					break;
			}
		}

		// Log
		self::log_message( sprintf(
			__( 'Stock %s request by %s granted.', 'woocommerce_stock_sync' ),
			$name,
			$source
		) );

		// TODO Check more?
		echo esc_html( self::$synchronization_success_message );

		die;
	}

	/**
	 * Can be used by support staff to determine some of the useful information
	 * that relates to common Stock Sync problems.
	 *
	 * A typical response would come back in json form with the following keys.
	 * This details what those keys mean and what the values are.
	 *
	 * {
	 *		url: // the site url. This must match the other synced sites [sites]
	 *		sites: // an array of all sites connected
	 *		log: // the recent log of syncs
	 * }
	 */
	public static function debug_response() {
		if ( ! filter_has_var( INPUT_POST, 'stock_sync_debug' ) ) {
			return;
		}

		// Get the password posted
		$stock_sync_debug = filter_input( INPUT_POST, 'stock_sync_debug', FILTER_SANITIZE_STRING );

		// Verify the password matches
		if ( $stock_sync_debug !== Stock_Synchronization::$synced_sites_password ) {
			wp_send_json( array( 'failed' => 'Invalid Password' ) );
		}

		// Hold the response array
		$response = array();

		// The current sites WP URL
		$response['url'] = site_url( '/' );

		// Get all sites connected
		$response['sites'] = Stock_Synchronization::$synced_sites;

		// The log
		$response['log'] = self::get_log();

		wp_send_json( $response );
	}

	/**
	 * Get message log
	 *
	 * @return mixed
	 */
	public static function get_log(){
		return get_option( Stock_Synchronization::$log_option_name, array() );
	}

	/**
	 * Logs a message as a comment that can be read back from the admin screen
	 *
	 * @param string $message
	 */
	public static function log_message( $message ) {
		$log = self::get_log();

		$message = date( 'd-m-o H:i:s' ) . ' - ' . $message;

		array_unshift( $log, $message );

		// Slice to maximum size
		$log = array_slice( $log, 0, Stock_Synchronization::$max_log_length );

		// Write to log
		update_option(
			Stock_Synchronization::$log_option_name,
			$log
		);
	}
}
