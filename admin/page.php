<?php

$settings_tabs = array(
	'settings' => __( 'Settings', 'woocommerce_stock_sync' ),
	'websites' => __( 'Websites', 'woocommerce_stock_sync' ),
	'stock'    => __( 'Stock', 'woocommerce_stock_sync' ),
	'log'      => __( 'Log', 'woocommerce_stock_sync' ),
);

$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
$current_tab = empty( $current_tab ) ? key( $settings_tabs ) : $current_tab;

$page_url = add_query_arg( 'page', 'woocommerce_stock_sync', admin_url( 'admin.php' ) );

printf( '<div class="wrap">' );

if ( empty( $settings_tabs ) ) {
	printf( '<h2>%s</h2>', esc_html( get_admin_page_title() ) );
} else {
	printf( '<h2 class="nav-tab-wrapper">' );

	foreach ( $settings_tabs as $name => $tab_title ) {
		$classes = array( 'nav-tab' );
		if ( $current_tab === $name ) {
			$classes[] = 'nav-tab-active';
		}

		$url = add_query_arg( 'tab', $name, $page_url );

		printf(
			'<a class="nav-tab %s" href="%s">%s</a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $url ),
			esc_html( $tab_title )
		);
	}

	printf( '</h2>' );
}

require 'tab-' . $current_tab . '.php';

printf( '</div>' );
