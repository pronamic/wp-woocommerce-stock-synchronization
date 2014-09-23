<?php

// Synchronize all
if ( filter_has_var( INPUT_POST, 'stock-synchronization-synchroniza-all-nonce' ) &&
	wp_verify_nonce( filter_input( INPUT_POST, 'stock-synchronization-synchroniza-all-nonce', FILTER_SANITIZE_STRING ), 'stock-synchronization-synchronize-all' ) ) {

	// Stock_Synchronization_Synchronizer::synchronize_all_stock();
}

// Get log
$log = get_option( 'wc_stock_sync_log', array() );

?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form action="options.php" method="post">
		<?php settings_fields( 'woocommerce_stock_sync' ); ?>

		<?php do_settings_sections( 'woocommerce_stock_sync' ); ?>

		<?php submit_button(); ?>
	</form>

	<h3>
		<?php _e( 'Synchronize all', 'woocommerce_stock_sync' ) ?>
	</h3>

	<form method="post" action="">
		<?php wp_nonce_field( 'stock-synchronization-synchronize-all', 'stock-synchronization-synchroniza-all-nonce' ); ?>

		<p>
			<?php _e( "This will push the stock if all the WooCommerce products on this website to the URL's specified.", 'woocommerce_stock_sync' ); ?>
		</p>

		<?php submit_button( __( 'Synchronize all', 'woocommerce_stock_sync' ) ); ?>
	</form>

	<h3>
		<?php _e( 'Activity log', 'woocommerce_stock_sync' ) ?>
	</h3>

	<p>
		<?php

		if ( is_array( $log ) && ! empty( $log ) ) {
			foreach ( $log as $message ) {
				echo $message, '<br />';
			}
		} else {
			_e( 'No entries found.', 'woocommerce_stock_sync' );
		}

		?>
	</p>

	<form method="post" action="">
		<?php submit_button( __( 'Empty Log', 'woocommerce_stock_sync' ), 'delete', 'pronamic_wc_stock_sync_empty_log' ); ?>
	</form>
</div>
