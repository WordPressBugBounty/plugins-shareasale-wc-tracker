<?php
/*
 Plugin Name:       ShareASale WooCommerce Tracker
 Author:			ShareASale.com, Inc.
 Description:       Setup ShareASale's Affiliate network's tracking in WooCommerce
 Version:           1.6.0
 Depends:  			WooCommerce
 License:           GPL-2.0+
 License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 WC requires at least: 2.6
 WC tested up to: 6.5.4
 */

//don't allow access from a web browser
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SHAREASALE_WC_TRACKER_PLUGIN_FILENAME', plugin_basename( __FILE__ ) );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-shareasale-wc-tracker.php';

function run_shareasale_wc_tracker() {
	$version = '1.6.0';
	$shareasale_wc_tracker = new ShareASale_WC_Tracker( $version );
	$shareasale_wc_tracker->run();
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_action( 'woocommerce_loaded', 'run_shareasale_wc_tracker');