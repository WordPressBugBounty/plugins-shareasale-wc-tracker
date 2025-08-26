<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get stored options
$options = get_option( 'shareasale_wc_tracker_options', array() );
$mastertag = get_option( 'shareasale_wc_tracker_mastertag', array() );
?>

<div id="shareasale-wc-tracker">
	<div class="wrap">
		<h1>ShareASale is now Awin — please upgrade your WooCommerce plugin</h1>
		
		<div class="upgrade-notice">
			<p>Tracking functionality continues for existing installs. However, advanced features such as Merchant-Defined Type, Data Feed, and Automatic Reconciliation are no longer supported. To benefit from these features, please install the <strong>Awin – Advertiser Tracking for WooCommerce</strong> plugin.</p>
		</div>

		<h2>How to upgrade</h2>
		
		<ol class="upgrade-steps">
			<li>
				<strong>Install the Awin – Advertiser Tracking for WooCommerce plugin</strong> from WordPress Plugin Directory.
			</li>
			<li>
				<strong>Type your Awin advertiser ID</strong> in the plugin and Save.<br>
				<em>Need help finding your Awin Advertiser ID? Guidance can be found in the <a href="https://advertiser-success.awin.com/s/article/Upgrade-Guide-for-E-commerce-Plugins?language=en_US#moving-from-ShareASale-WooCommerce-plugin-to-Awin-WooCommerce-plugin" target="_blank">Awin Tracking Installation Guide</a>.</em>
			</li>
			<li>
				<strong>Place a test order</strong> and verify the transaction appears in Awin, confirming it is tracked as expected.<br>
				<em>Need help with testing? Visit the <a href="https://advertiser-success.awin.com/s/article/Upgrade-Guide-for-E-commerce-Plugins?language=en_US#moving-from-ShareASale-WooCommerce-plugin-to-Awin-WooCommerce-plugin" target="_blank">Awin Tracking Installation Guide</a> for detailed test instructions.</em>
			</li>
			<li>
				<strong>Remove ShareASale tracking</strong> (uninstall this plugin and delete any legacy ShareASale scripts/pixels) to avoid discrepancies.<br>
				<em>More information on this can be found in the <a href="https://advertiser-success.awin.com/s/article/Upgrade-Guide-for-E-commerce-Plugins?language=en_US#moving-from-ShareASale-WooCommerce-plugin-to-Awin-WooCommerce-plugin" target="_blank">Upgrade Guide for E-commerce Plugins</a> under "Completing the setup: uninstalling ShareASale's WooCommerce plugin".</em>
			</li>
		</ol>

		<h2>Actions</h2>
		<div class="action-buttons">
			<a href="https://wordpress.org/plugins/awin-advertiser-tracking/" class="button button-primary" target="_blank">Install Awin – Advertiser Tracking for WooCommerce</a>
			<a href="https://advertiser-success.awin.com/s/article/Upgrade-Guide-for-E-commerce-Plugins?language=en_US#moving-from-ShareASale-WooCommerce-plugin-to-Awin-WooCommerce-plugin" class="button" target="_blank">Open Upgrade Guide</a>
			<a href="https://advertiser-success.awin.com/s/contactsupport?language=en_US" class="button" target="_blank">Contact Support</a>
		</div>

		<div class="current-settings">
			<h2 class="settings-toggle" onclick="toggleSettings()">
				<span class="dashicons dashicons-arrow-right-alt2" id="settings-arrow"></span>
				Current Plugin Settings (Read-only)
			</h2>
			<div id="settings-content" class="settings-content" style="display: none;">
				<p><em>New installs of the ShareASale WooCommerce Tracker plugin are closed.</em></p>
				
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">ShareASale Merchant ID</th>
							<td><?php echo esc_html( !empty( $options['merchant-id'] ) ? $options['merchant-id'] : 'Not set' ); ?></td>
						</tr>
						<tr>
							<th scope="row">AWIN Advertiser ID</th>
							<td>
								<?php 
								$awin_id = '';
								if ( !empty( $options['awin-id'] ) ) {
									$awin_id = $options['awin-id'];
								} elseif ( !empty( $mastertag['id'] ) ) {
									$awin_id = $mastertag['id'];
								} else {
									$awin_id = 'Not set';
								}
								echo esc_html( $awin_id );
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">Store ID</th>
							<td><?php echo esc_html( !empty( $options['store-id'] ) ? $options['store-id'] : 'Not set' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<script>
		function toggleSettings() {
			var content = document.getElementById('settings-content');
			var arrow = document.getElementById('settings-arrow');
			
			if (content.style.display === 'none') {
				content.style.display = 'block';
				arrow.className = 'dashicons dashicons-arrow-down-alt2';
			} else {
				content.style.display = 'none';
				arrow.className = 'dashicons dashicons-arrow-right-alt2';
			}
		}
		</script>
	</div>
</div>
