<h3><?php esc_html_e( 'Overview', 'woocommerce_stock_sync' ); ?></h3>

<?php

$urls     = get_option( 'woocommerce_stock_sync_urls', array() );
$password = get_option( 'woocommerce_stock_sync_password' );

if ( ! is_array( $urls ) ) {
	$urls = array();
}

?>

<table class="wp-list-table widefat" cellspacing="0">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'URL', 'woocommerce_stock_sync' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Status', 'woocommerce_stock_sync' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Version', 'woocommerce_stock_sync' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Error', 'woocommerce_stock_sync' ); ?></th>
		</tr>
	</thead>

	<tbody>

		<?php if ( empty( $urls ) ) : ?>

			<tr class="no-items">
				<td colspan="4">
					<?php esc_html_e( 'No websites found.', 'woocommerce_stock_sync' ); ?>
				</td>
			</tr>

		<?php else : ?>

			<?php $alternate = ''; ?>

			<?php foreach ( $urls as $url ) : ?>

				<?php $alternate = 'alternate' === $alternate ? '' : 'alternate'; ?>

				<tr class="<?php echo esc_attr( $alternate ); ?>">
					<?php

					$request_url = $this->plugin->synchronizer->get_sync_url( $url );

					$result = wp_remote_post( $request_url );

					// @see https://github.com/WordPress/WordPress/blob/4.0/wp-includes/http.php#L241-L256https://github.com/WordPress/WordPress/blob/4.0/wp-includes/http.php#L241-L256
					$response_code = wp_remote_retrieve_response_code( $result );

					$body = wp_remote_retrieve_body( $result );

					$data = json_decode( $body );

					$version = null;

					if ( $data ) {
						if ( isset( $data->version ) ) {
							$version = $data->version;
						}
					}

					?>
					<td>
						<?php echo esc_html( $url ); ?>
					</td>
					<td>
						<?php

						$dashicon = 'no';
						if ( 200 == $response_code ) { // WPCS: loose comparison ok.
							$dashicon = 'yes';
						}

						?>
						<div class="dashicons dashicons-<?php echo esc_attr( $dashicon ); ?>"></div>
					</td>
					<td>
						<?php

						if ( null === $version ) {
							echo '&mdash;';
						} else {
							echo esc_html( $version );
						}

						?>
					</td>
					<td>
						<?php

						if ( is_wp_error( $result ) ) {
							echo esc_html( $result->get_error_message() );
						} else {
							echo '&mdash;';
						}

						?>
					</td>
				</tr>

			<?php endforeach; ?>

		<?php endif; ?>

	</tbody>
</table>
