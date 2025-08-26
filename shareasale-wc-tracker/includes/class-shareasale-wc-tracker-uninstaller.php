<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Uninstaller {

	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		global $wpdb;
		//remove settings
		unregister_setting( 'shareasale_wc_tracker_options', 'shareasale_wc_tracker_options' );
		delete_option( 'shareasale_wc_tracker_options' );
		delete_option( 'shareasale_wc_tracker_version' );
		delete_option( 'shareasale_wc_tracker_mastertag' );
	}

	public static function disable() {
		//when plugin deactivated (not uninstalled though), cleanup
		$options = get_option( 'shareasale_wc_tracker_options' );
		update_option( 'shareasale_wc_tracker_options', $options );
	}
}
