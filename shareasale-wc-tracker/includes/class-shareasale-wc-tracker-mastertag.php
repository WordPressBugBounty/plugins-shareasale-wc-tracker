<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Mastertag {

	/**
	* @var string $version Plugin version
	*/

	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	public function enqueue_scripts( $hook ) {

		$src = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-cookie-setter.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-cookie-setter',
			$src,
			is_order_received_page() ? array( 'shareasale-wc-tracker-pixel' ) : array(),
			$this->version
		);

		//required mastertag on every page
		$awin_id = ShareASale_WC_Tracker_Config::get_awin_id();

		$src = esc_url( 'https://www.dwin1.com/' . $awin_id . '.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-mastertag',
			$src,
			is_order_received_page() ? array( 'shareasale-wc-tracker-pixel' ) : array(),
			$this->version
		);
	}
}
