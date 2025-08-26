<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
class ShareASale_WC_Tracker_Config {

	private static $cached_options = null;
	private static $cached_mastertag = null;
	public static function get_awin_id() {
		$options = self::get_options();
		$mastertag = self::get_mastertag();

		if ( ! empty( $options['awin-id'] ) && is_numeric( $options['awin-id'] ) ) {
			return $options['awin-id'];
		}

		if ( ! empty( $mastertag['id'] ) && is_numeric( $mastertag['id'] ) ) {
			return $mastertag['id'];
		}

		return '19038';
	}
	public static function get_merchant_id() {
		$options = self::get_options();
		return isset( $options['merchant-id'] ) ? $options['merchant-id'] : '';
	}

	public static function get_store_id() {
		$options = self::get_options();
		return isset( $options['store-id'] ) ? $options['store-id'] : '';
	}

	public static function get_xtype() {
		$options = self::get_options();
		return isset( $options['xtype'] ) ? $options['xtype'] : '';
	}

	public static function get_xtype_hidden() {
		$options = self::get_options();
		return isset( $options['xtype-hidden'] ) ? $options['xtype-hidden'] : '';
	}
	public static function get_options() {
		if ( self::$cached_options === null ) {
			self::$cached_options = get_option( 'shareasale_wc_tracker_options', array() );
		}
		return self::$cached_options;
	}

	public static function get_mastertag() {
		if ( self::$cached_mastertag === null ) {
			self::$cached_mastertag = get_option( 'shareasale_wc_tracker_mastertag', array() );
		}
		return self::$cached_mastertag;
	}

	public static function clear_cache() {
		self::$cached_options = null;
		self::$cached_mastertag = null;
	}

	public static function is_configured() {
		$merchant_id = self::get_merchant_id();
		$awin_id = self::get_awin_id();
		
		return ! empty( $merchant_id ) && ! empty( $awin_id ) && $awin_id !== self::DEFAULT_AWIN_ID;
	}
	public static function get_debug_info() {
		return array(
			'merchant_id' => self::get_merchant_id(),
			'awin_id' => self::get_awin_id(),
			'store_id' => self::get_store_id(),
			'xtype' => self::get_xtype(),
			'xtype_hidden' => self::get_xtype_hidden(),
			'is_configured' => self::is_configured(),
			'options' => self::get_options(),
			'mastertag' => self::get_mastertag(),
		);
	}
}