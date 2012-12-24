<?php 

// Synchronize all
if( ! empty( $_POST ) &&
	isset( $_POST[ 'stock-synchronization-synchroniza-all-nonce' ] ) &&
	wp_verify_nonce( $_POST[ 'stock-synchronization-synchroniza-all-nonce' ], 'stock-synchronization-synchronize-all' ) ) {

	Stock_Synchronization_Synchronizer::synchronize_all_stock();
}

// Get log
$log = Stock_Synchronization_Synchronizer::get_log();

// Settings
$synced_sites = get_option( 'stock-synchronization-synced-sites' );
$synced_sites_password = get_option( 'stock-synchronization-synced-sites-password' );

?>
<h3>
	<?php _e( 'Settings', 'stock-synchronizer' ); ?>
</h3>

<form method="post" action="options.php">
	<?php settings_fields('stock-synchronization-settings'); ?>

	<table class="form-table">
		<tr>
			<td>
				<?php _e( 'URLs to synchronize and be synchronized with, one site per line.', 'stock-synchronizer' ); ?>
			</td>
			<td>
				<textarea
					id="stock-synchronization-synced-sites"
					name="stock-synchronization-synced-sites"
					cols="40"
					rows="10"
				><?php echo $synced_sites; ?></textarea>
			</td>
			<td>
				<i><?php _e( 'Example: http://www.pronamic.eu', 'stock-synchronizer' ); ?></i>
			</td>
		</tr>
		<tr>
			<td>
				<?php _e( 'Password shared amongst synchronized sites', 'stock-synchronizer' ); ?>
			</td>
			<td>
				<input
					type="text"
					id="stock-synchronization-synced-sites-password"
					name="stock-synchronization-synced-sites-password"
					value="<?php echo $synced_sites_password; ?>"
				/>
			</td>
		</tr>
	</table>

	<?php submit_button(); ?>
</form>

<h3>
	<?php _e( 'Synchronize all', 'stock-synchronization' ) ?>
</h3>

<form method="post" action="">
	<?php wp_nonce_field( 'stock-synchronization-synchronize-all', 'stock-synchronization-synchroniza-all-nonce' ); ?>
	
	<?php submit_button( __( 'Synchronize all', 'stock-synchronization' ) ); ?>
</form>

<h3>
	<?php _e( 'Activity log', 'stock-synchronization' ) ?>
</h3>
<p>
	<?php if( count( $log ) > 0 ): ?>
	
	<?php foreach( $log as $message ): ?>
	
	<?php echo $message; ?><br />
	
	<?php endforeach; ?>
	
	<?php else: ?>
	
	<?php _e( 'No entries found', 'stock-synchronization' ); ?>
	
	<?php endif; ?>
</p>