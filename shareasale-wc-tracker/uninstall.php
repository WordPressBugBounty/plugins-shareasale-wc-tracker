<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
    die;
}

global $wpdb;

//no tables to drop (removed in v1.6.0)
//remove settings
unregister_setting( 'shareasale_wc_tracker_options', 'shareasale_wc_tracker_options' );
delete_option( 'shareasale_wc_tracker_options' );
delete_option( 'shareasale_wc_tracker_version' );
delete_option( 'shareasale_wc_tracker_mastertag' );