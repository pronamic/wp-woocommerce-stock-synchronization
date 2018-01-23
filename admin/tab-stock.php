<h3><?php esc_html_e( 'Overview', 'woocommerce_stock_sync' ); ?></h3>

<?php

// Message

if ( filter_has_var( INPUT_GET, 'synced' ) && ! filter_has_var( INPUT_POST, 'woocommerce_stock_sync_push' ) ) {
	printf(
		'<div id="message" class="updated"><p>%s</p></div>',
		esc_html( __( 'Stock synchronized.', 'woocommerce_stock_sync' ) )
	);
}

if ( ( filter_has_var( INPUT_POST, 'woocommerce_stock_sync_push' ) || filter_has_var( INPUT_GET, 'push_stock' ) ) && check_admin_referer( 'woocommerce_stock_sync_push', 'woocommerce_stock_sync_nonce' ) ) {
	$stock_pushed = filter_input( INPUT_GET, 'push_stock', FILTER_SANITIZE_NUMBER_INT );

	if ( ! $stock_pushed ) {
		$stock_pushed = 0;
	}

	printf(
		'<div id="message" class="notice notice-info"><p><strong>%s</strong> %s %s</p></div>',
		esc_html( __( 'Pushing stock, please wait.', 'woocommerce_stock_sync' ) ),
		esc_html( $stock_pushed ),
		esc_html( __( 'products synchronized.', 'woocommerce_stock_sync' ) )
	);
}

// Products

global $wpdb;

$query = "
	SELECT
		post.ID AS id,
		post.post_title AS title,
		meta_sku.meta_value AS sku,
		meta_qty.meta_value AS qty
	FROM
		$wpdb->posts AS post
			INNER JOIN
		$wpdb->postmeta AS meta_sku
				ON post.ID = meta_sku.post_id AND meta_sku.meta_key = '_sku' AND meta_sku.meta_value != ''
			INNER JOIN
		$wpdb->postmeta AS meta_qty
				ON post.ID = meta_qty.post_id AND meta_qty.meta_key = '_stock'
	WHERE
		post.post_type IN ( 'product', 'product_variation' )
	LIMIT
		0, 100
	;
";

$products = $wpdb->get_results( $query ); // WPCS: unprepared SQL ok.

?>

<table class="wp-list-table widefat" cellspacing="0">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'ID', 'woocommerce_stock_sync' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Title', 'woocommerce_stock_sync' ); ?></th>
			<th scope="col"><?php esc_html_e( 'SKU', 'woocommerce_stock_sync' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Stock', 'woocommerce_stock_sync' ); ?></th>
		</tr>
	</thead>

	<tbody>

		<?php if ( empty( $products ) ) : ?>

			<tr class="no-items">
				<td colspan="4">
					<?php esc_html_e( 'No stock found.', 'woocommerce_stock_sync' ); ?>
				</td>
			</tr>

		<?php else : ?>

			<?php $alternate = ''; ?>

			<?php foreach ( $products as $product ) : ?>

				<?php $alternate = 'alternate' === $alternate ? '' : 'alternate'; ?>

				<tr class="<?php echo esc_attr( $alternate ); ?>">
					<td>
						<?php echo esc_html( $product->id ); ?>
					</td>
					<td>
						<?php echo esc_html( $product->title ); ?>
					</td>
					<td>
						<?php echo esc_html( $product->sku ); ?>
					</td>
					<td>
						<?php echo esc_html( $product->qty ); ?>
					</td>
				</tr>

			<?php endforeach; ?>

		<?php endif; ?>

	</tbody>
</table>

<?php

$query = "
	SELECT
		COUNT( post.ID )
	FROM
		$wpdb->posts AS post
			INNER JOIN
		$wpdb->postmeta AS meta_sku
				ON post.ID = meta_sku.post_id AND meta_sku.meta_key = '_sku' AND meta_sku.meta_value != ''
			INNER JOIN
		$wpdb->postmeta AS meta_qty
				ON post.ID = meta_qty.post_id AND meta_qty.meta_key = '_stock'
	WHERE
		post.post_type IN ( 'product', 'product_variation' )
	;
";

$count_products = $wpdb->get_var( $query ); // WPCS: unprepared SQL ok.

if ( $count_products > 100 ) :

?>

	<p class="description"><?php esc_html_e( 'The number of displayed products is limited to 100. Stock quantites for all products will be synchronized during synchronization.', 'woocommerce_stock_sync' ); ?></p>

<?php endif; ?>

<form method="post" action="">
	<?php wp_nonce_field( 'woocommerce_stock_sync_push', 'woocommerce_stock_sync_nonce' ); ?>

	<?php submit_button( __( 'Push Stock', 'woocommerce_stock_sync' ), 'primary', 'woocommerce_stock_sync_push' ); ?>
</form>
