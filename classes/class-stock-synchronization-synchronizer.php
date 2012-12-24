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
		
		self::log_message( sprintf( __( 'Reduced order stock - %d out of %d sites responded with success.', 'synchronize-stock' ),
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
		
		self::log_message( sprintf( __( 'Restored order stock - %d out of %d sites responded with success.', 'synchronize-stock' ),
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
		$skus = array();
		if( is_array( $items ) && count( $items ) > 0 ) {
			
			foreach( $items as $item ) {
				
				$product = new WC_Product( $item[ 'id' ] );
				
				$skus[ $product->get_sku() ] = $item[ 'qty' ];
			}
		}
		else return 0;
		
		// Notify synced websites
		if( count( Stock_Synchronization::$synced_sites ) > 0 ) {
			
			foreach( Stock_Synchronization::$synced_sites as $site ) {
				
				// Remote post
				$result = wp_remote_post( $site, array( 'body' => array(
					'source'	=>	get_bloginfo( 'wpurl' ),
					'password'	=>	Stock_Synchronization::$synced_sites_password,
					'action'	=>	$action,
					'skus'		=>	$skus
				) ) );
				
				if( ! ( $result instanceof WP_Error ) && strpos( $result[ 'body' ], self::$synchronization_success_message ) !== false )
					$success++;
			}
		}
		else return 0;
		
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
		$skus = array();
		
		// Get all products and product variations
		$query = new WP_Query( array(
			'posts_per_page' => -1,
			'post_type' => array(
				'product',
				'product_variation'
			)
		) );
		
		// Loop through query results, building the SKUs array
		while( $query->have_posts() ) {
			
			$query->next_post();
			
			$skus[ get_post_meta( $query->post->ID, '_sku', true ) ] = get_post_meta( $query->post->ID, '_stock', true );
		}
		
		// Notify synced websites
		if( count( Stock_Synchronization::$synced_sites ) > 0 ) {
			
			foreach( Stock_Synchronization::$synced_sites as $site ) {
				
				// Remote post
				$result = wp_remote_post( $site, array( 'body' => array(
					'source'	=>	get_bloginfo( 'wpurl' ),
					'password'	=>	Stock_Synchronization::$synced_sites_password,
					'action'	=>	self::$synchronize_all_stock_action_name,
					'skus'		=>	$skus
				) ) );
				
				if( strpos( $result[ 'body' ], self::$synchronization_success_message ) !== false )
					$success++;
			}
		}
		else return 0;
		
		self::log_message( sprintf( __( 'Synchronized all stock - %d out of %d sites responded with success.', 'synchronize-stock' ),
			$success,
			count( Stock_Synchronization::$synced_sites )
		) );
	}
	
	/**
	 * Receives all synchronization requests and handles them if source and password are correct.
	 */
	public static function maybe_synchronize() {
		if( ! isset( $_POST[ 'source' ] )	||	! in_array( $_POST[ 'source' ], Stock_Synchronization::$synced_sites )	||
			! isset( $_POST[ 'password' ] )	||	$_POST[ 'password' ] != Stock_Synchronization::$synced_sites_password	||
			! isset( $_POST[ 'action' ] )	||	( $_POST[ 'action' ] != self::$reduce_stock_action_name 				&&	$_POST[ 'action' ] != self::$restore_stock_action_name	&&	$_POST[ 'action' ] != self::$synchronize_all_stock_action_name ) ||
			! isset( $_POST[ 'skus' ] )		||	empty( $_POST[ 'skus' ] )												||	! is_array( $_POST[ 'skus' ] ) )
			return;
		
		// Get all products and product variations by SKU
		$query = new WP_Query( array(
			'posts_per_page' => -1,
			'post_type' => array(
				'product',
				'product_variation'
			),
			'meta_query' => array(
				array(
					'key' => '_sku',
					'value' => array_keys( $_POST[ 'skus' ] )
				)
			)
		) );
		
		// Loop through query results, increase or decrease stock according to given stock quantities
		while( $query->have_posts() ) {
			
			$query->next_post();
			
			if( $query->post->post_type == 'product' )
				$product = new WC_Product( $query->post->ID );
			else if( $query->post->post_type == 'product_variation' )
				$product = new WC_Product_Variation( $query->post->ID );
			else
				continue;
			
			$sku = $product->get_sku();
			
			if( empty( $sku ) )
				continue;
			
			$qty = $_POST[ 'skus' ][ $sku ];
			
			// Choose action
			if( $_POST[ 'action' ]			==	self::$reduce_stock_action_name )
				$product->reduce_stock( $qty );
			else if ( $_POST[ 'action' ]	==	self::$restore_stock_action_name )
				$product->increase_stock( $qty );
			else if ( $_POST[ 'action' ]	==	self::$synchronize_all_stock_action_name ) {
				
				$qty = $qty - $product->get_stock_quantity();
				
				if( $qty > 0 )
					$product->increase_stock( $qty );
				else if( $qty < 0 )
					$product->reduce_stock( abs( $qty ) );
			}
		}
		
		// Log
		self::log_message( sprintf( __( 'Stock %s request by %s granted.', 'synchronize-stock' ),
			( $_POST[ 'action' ] == self::$reduce_stock_action_name ? 'reduce' : '' ) .
			( $_POST[ 'action' ] == self::$restore_stock_action_name ? 'restore' : '' ) .
			( $_POST[ 'action' ] == self::$synchronize_all_stock_action_name ? 'synchronization' : '' ),
			$_POST[ 'source' ]
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
		$log = maybe_unserialize( get_option( Stock_Synchronization::$log_option_name, '' ) );
		
		if( is_array( $log ) )
			return $log;
		return array();
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