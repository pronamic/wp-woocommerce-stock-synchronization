<form action="options.php" method="post">
	<?php settings_fields( 'woocommerce_stock_sync' ); ?>

	<?php do_settings_sections( 'woocommerce_stock_sync' ); ?>

	<?php submit_button(); ?>
</form>
