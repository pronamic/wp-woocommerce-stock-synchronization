<?php

$tabs = array(
	'settings' => __( 'Settings', 'woocommerce_stock_sync' ),
	'websites' => __( 'Websites', 'woocommerce_stock_sync' ),
	'stock'    => __( 'Stock', 'woocommerce_stock_sync' ),
	'log'      => __( 'Log', 'woocommerce_stock_sync' ),
);

$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
$current_tab = empty( $current_tab ) ? key( $tabs ) : $current_tab;

$page_url = add_query_arg( 'page', 'woocommerce_stock_sync', admin_url( 'admin.php' ) );

printf( '<div class="wrap">' );

if ( empty( $tabs ) ) {
	printf( '<h2>%s</h2>', esc_html( get_admin_page_title() ) );
} else {
	printf( '<h2 class="nav-tab-wrapper">' );

	foreach ( $tabs as $tab => $title ) {
		$classes = array( 'nav-tab' );
		if ( $current_tab === $tab ) {
			$classes[] = 'nav-tab-active';
		}

		$url = add_query_arg( 'tab', $tab, $page_url );

		printf(
			'<a class="nav-tab %s" href="%s">%s</a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $url ),
			esc_html( $title )
		);
	}

	printf( '</h2>' );
}

include 'tab-' . $current_tab . '.php';

printf( '</div>' );
