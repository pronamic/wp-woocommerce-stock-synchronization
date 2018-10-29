<h3><?php esc_html_e( 'Overview', 'woocommerce_stock_sync' ); ?></h3>

<?php

// Get log
$log = get_option( 'wc_stock_sync_log', array() );

if ( ! is_array( $log ) ) {
	$log = array();
}

if ( filter_has_var( INPUT_GET, 'deleted' ) ) {
	printf(
		'<div id="message" class="updated"><p>%s</p></div>',
		esc_html( __( 'Log deleted.', 'woocommerce_stock_sync' ) )
	);
}

?>
<table class="wp-list-table widefat" cellspacing="0">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Time', 'woocommerce_stock_sync' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Message', 'woocommerce_stock_sync' ); ?></th>
		</tr>
	</thead>

	<tbody>

		<?php if ( empty( $log ) ) : ?>

			<tr class="no-items">
				<td colspan="4">
					<?php esc_html_e( 'No logs found.', 'woocommerce_stock_sync' ); ?>
				</td>
			</tr>

		<?php else : ?>

			<?php $alternate = ''; ?>

			<?php foreach ( $log as $item ) : ?>

				<?php $alternate = 'alternate' === $alternate ? '' : 'alternate'; ?>

				<tr class="<?php echo esc_attr( $alternate ); ?>">
					<td>
						<?php

						if ( isset( $item->time ) ) {
							/* translators: %s: time difference */
							echo esc_html( sprintf( __( '%s ago', 'woocommerce_stock_sync' ), human_time_diff( $item->time ) ) );
						} else {
							echo '—';
						}

						?>
					</td>
					<td>
						<?php

						if ( isset( $item->message ) ) {
							echo wp_kses_data( $item->message );
						} else {
							echo wp_kses_data( $item );
						}

						?>
					</td>
				</tr>

			<?php endforeach; ?>

		<?php endif; ?>

	</tbody>
</table>

<form method="post" action="">
	<?php wp_nonce_field( 'woocommerce_stock_sync_empty_log', 'woocommerce_stock_sync_nonce' ); ?>

	<?php submit_button( __( 'Empty Log', 'woocommerce_stock_sync' ), 'delete', 'woocommerce_stock_sync_empty_log' ); ?>
</form>
