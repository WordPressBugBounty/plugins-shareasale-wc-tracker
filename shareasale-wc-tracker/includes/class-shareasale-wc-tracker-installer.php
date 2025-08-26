<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Installer {
	public static function install() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		add_option( 'shareasale_wc_tracker_options', '' );
		//version will be purposely empty at first install even if up to date.
		//can't pass version arg to register_activation_hook...
		add_option( 'shareasale_wc_tracker_version', '' );

		// Clean up legacy scheduled events from previous versions
		self::cleanup_legacy_scheduled_events();

		global $wpdb;
	}

	/**
	* @var string $old_version what's stored in the db for wp_option
	* @var string $latest_version what's been instantiated in the plugin's class
	*/
	public static function upgrade( $old_version, $latest_version ) {
		global $wpdb;
		update_option( 'shareasale_wc_tracker_version', $latest_version );
	}

	/**
	 * Clean up legacy scheduled events from previous plugin versions
	 */
	private static function cleanup_legacy_scheduled_events() {
		// Check if the legacy scheduled event exists before attempting to unschedule
		$timestamp = wp_next_scheduled( 'shareasale_wc_tracker_generate_scheduled_datafeed' );
		
		if ( $timestamp !== false ) {
			// Unschedule the legacy automated product datafeed FTP upload event
			wp_unschedule_event( $timestamp, 'shareasale_wc_tracker_generate_scheduled_datafeed' );
		}
	}
}
