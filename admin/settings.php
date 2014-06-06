<?php

// Synchronize all
if ( filter_has( INPUT_POST, 'stock-synchronization-synchroniza-all-nonce' ) &&
	wp_verify_nonce( filter_input( INPUT_POST, 'stock-synchronization-synchroniza-all-nonce', FILTER_SANITIZE_STRING ), 'stock-synchronization-synchronize-all' ) ) {

	Stock_Synchronization_Synchronizer::synchronize_all_stock();
}

// Get log
$log = Stock_Synchronization_Synchronizer::get_log();

?>
<div class="wrap">
	<?php screen_icon(); ?>

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
		<?php if ( is_array( $log ) && ! empty( $log ) ) : ?>

			<?php foreach ( $log as $message ) : ?>

				<?php echo esc_html( $message ); ?><br />

			<?php endforeach; ?>

		<?php else : ?>

			<?php _e( 'No entries found.', 'woocommerce_stock_sync' ); ?>

		<?php endif; ?>
	</p>
</div>