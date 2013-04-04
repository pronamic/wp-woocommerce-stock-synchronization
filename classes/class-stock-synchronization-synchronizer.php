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
	public static function Bootstrap() {
		add_action( 'init',								array( __CLASS__, 'maybe_synchronize' ) );

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
				$product = $order->get_product_from_item($item);
				$skus[ $product->get_sku() ] = $item[ 'qty' ];
			}
		} else {
			return 0;
		}

		// Notify synced websites
		if ( count( Stock_Synchronization::$synced_sites ) > 0 ) {

			foreach( Stock_Synchronization::$synced_sites as $site ) {

				// Remote post
				$result = wp_remote_post( $site, array( 'body' => array(
					'woocommerce_stock_sync' => true,
					'source'   => site_url('/'),
					'password' => Stock_Synchronization::$synced_sites_password,
					'action'   => $action,
					'skus'     => $skus
				) ) );

				if( ! is_wp_error( $result ) && strpos( $result[ 'body' ], self::$synchronization_success_message ) !== false ) {
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

		// Get all products and product variations
		$query = new WP_Query( array(
			'posts_per_page' => -1,
			'post_type'      => array(
				'product',
				'product_variation'
			)
		) );

		// Loop through query results, building the SKUs array
		while ( $query->have_posts() ) {
			$query->next_post();

			$skus[ get_post_meta( $query->post->ID, '_sku', true ) ] = get_post_meta( $query->post->ID, '_stock', true );
		}

		// Notify synced websites
		if ( count( Stock_Synchronization::$synced_sites ) > 0 ) {

			foreach ( Stock_Synchronization::$synced_sites as $site ) {

				// Remote post
				$result = wp_remote_post( $site, array( 'body' => array(
					'woocommerce_stock_sync' => true,
					'source'                 => site_url( '/' ),
					'password'               => Stock_Synchronization::$synced_sites_password,
					'action'                 => self::$synchronize_all_stock_action_name,
					'skus'                   => $skus
				) ) );

				$body = wp_remote_retrieve_body( $result );

				if( strpos( $result[ 'body' ], self::$synchronization_success_message ) !== false ) {
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
		if ( ! isset( $_POST['woocommerce_stock_sync'] ) ) {
			return;
		}



		$source   = filter_input( INPUT_POST, 'source', FILTER_SANITIZE_STRING );
		$password = filter_input( INPUT_POST, 'password', FILTER_SANITIZE_STRING );
		$action   = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		$skus     = filter_input( INPUT_POST, 'skus', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );

		self::log_message(serialize($skus));

		if ( ! in_array( $source, Stock_Synchronization::$synced_sites ) ) {
			return;
		}

		if ( $password != Stock_Synchronization::$synced_sites_password ) {
			return;
		}

		if ( ! is_array( $skus ) ) {
			$skus = array();
		}

		// Get all products and product variations by SKU
		$query = new WP_Query( array(
			'posts_per_page' => -1,
			'post_type'      => array(
				'product',
				'product_variation'
			),
			'meta_query'     => array(
				array(
					'key'   => '_sku',
					'value' => array_keys( $skus )
				)
			)
		) );

		// Loop through query results, increase or decrease stock according to given stock quantities
		while ( $query->have_posts() ) {
			$query->next_post();

			if ( $query->post->post_type == 'product' )
				$product = new WC_Product( $query->post->ID );
			else if( $query->post->post_type == 'product_variation' )
				$product = new WC_Product_Variation( $query->post->ID );
			else
				continue;

			$sku = $product->get_sku();

			if ( empty( $sku ) )
				continue;

			$qty = $skus[$sku];

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
		echo self::$synchronization_success_message;

		die;
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

		// Reverse array, add to end and reverse again to have added the message to the front.
		$log = array_reverse( $log );
		$log[] = date('d-m-o H:i:s') . ' - ' . $message;
		$log = array_reverse( $log );

		// Slice to maximum size
		$log = array_slice( $log, 0, Stock_Synchronization::$max_log_length );

		// Write to log
		update_option(
			Stock_Synchronization::$log_option_name,
			$log
		);
	}
}
