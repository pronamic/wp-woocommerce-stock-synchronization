<h3><?php esc_html_e( 'Overview', 'woocommerce_stock_sync' ); ?></h3>

<?php

$sites_urls = get_option( 'woocommerce_stock_sync_urls', array() );
$password   = get_option( 'woocommerce_stock_sync_password' );

if ( ! is_array( $sites_urls ) ) {
	$sites_urls = array();
}

$requests = array();

foreach ( $sites_urls as $key => $site_url ) {
	$requests[ $key ] = array(
		'url'  => $this->plugin->synchronizer->get_sync_url( $site_url ),
		'type' => 'POST',
	);
}

$responses = \Requests::request_multiple( $requests );

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

		<?php if ( empty( $sites_urls ) ) : ?>

			<tr class="no-items">
				<td colspan="4">
					<?php esc_html_e( 'No websites found.', 'woocommerce_stock_sync' ); ?>
				</td>
			</tr>

		<?php else : ?>

			<?php $alternate = ''; ?>

			<?php foreach ( $responses as $key => $response ) : ?>

				<?php $alternate = 'alternate' === $alternate ? '' : 'alternate'; ?>

				<tr class="<?php echo esc_attr( $alternate ); ?>">
					<?php

					$status_code = null;
					$version     = null;

					if ( $response instanceof \Requests_Response ) {
						$status_code = $response->status_code;

						$data = json_decode( $response->body );

						if ( $data && isset( $data->version ) ) {
							$version = $data->version;
						}
					}

					?>
					<td>
						<?php echo esc_html( $sites_urls[ $key ] ); ?>
					</td>
					<td>
						<?php

						$dashicon = 200 === intval( $status_code ) ? 'yes' : 'no';

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

						if ( $response instanceof \Requests_Exception ) {
							echo esc_html( $response->getMessage() );
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
