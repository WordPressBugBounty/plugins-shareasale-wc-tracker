<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Admin {
	/**
	* @var string $version Plugin version
	*/
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/class-shareasale-wc-tracker-installer.php';
	}

	public function enqueue_styles( $hook ) {
		$hooks = array(
			'toplevel_page_shareasale_wc_tracker',
			'shareasale-wc-tracker_page_shareasale_wc_tracker_advanced_analytics',
			'toplevel_page_shareasale_wc_tracker_advanced_settings',
		);

		if ( in_array( $hook, $hooks, true ) ) {
			wp_enqueue_style(
				'shareasale-wc-tracker-admin',
				esc_url( plugin_dir_url( __FILE__ ) . 'css/shareasale-wc-tracker-admin.css' ),
				array(),
				$this->version
			);
		}
	}

	public function enqueue_scripts( $hook ) {

		$hooks = array(
			'toplevel_page_shareasale_wc_tracker',
			'shareasale-wc-tracker_page_shareasale_wc_tracker_advanced_analytics',
			'toplevel_page_shareasale_wc_tracker_advanced_settings',
		);

		if ( in_array( $hook, $hooks, true ) ) {
			wp_enqueue_script(
				'shareasale-wc-tracker-admin-js',
				esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-admin.js' ),
				array( 'jquery' ),
				$this->version
			);
		}
	}

	public function admin_notices() {
		//see https://wordpress.stackexchange.com/questions/23701/how-should-one-implement-add-settings-error-on-custom-menu-pages
		global $post_id;
		global $wp_settings_errors;

		if ( 'shop_coupon' !== OrderUtil::get_order_type( $post_id ) ) {
			return;
		}
		//make sure to use the settings_errors transient that lasts between pages and not just $wp_settings_errors global from memory gone between page loads
		//do this by simulating the same &settings-updated=1 WordPress behavior found in wp-admin/includes/template.php without having that GET parameter actually set...
		$wp_settings_errors = array_merge( (array) $wp_settings_errors, array_filter( (array) get_transient( 'settings_errors' ) ) );
		delete_transient( 'settings_errors' );
	}

	public function admin_init() {
		// Register the setting globally so WordPress can save it, but move field registration to page-specific methods
		register_setting( 'shareasale_wc_tracker_options', 'shareasale_wc_tracker_options', array( $this, 'sanitize_settings' ) );
	}
	//this is here because it runs on admin_init hook unfortunately
	public function plugin_upgrade() {
		$current_version = get_option( 'shareasale_wc_tracker_version' );
		$latest_version  = $this->version;
		//at first installation, shareasale_wc_tracker_version actually gets defined here even if not an upgrade
		//only run if there's version difference at all, and then in the upgrade() method figure out which version we're exactly upgrading from and to...
		if ( -1 === version_compare( $current_version, $latest_version ) ) {
			ShareASale_WC_Tracker_Installer::upgrade( $current_version, $latest_version );
		}
	}

	public function admin_menu() {

		/** Add the top-level admin menu */
		$page_title = 'ShareASale WooCommerce Tracker Settings';
		$menu_title = 'ShareASale WC Tracker';
		$capability = 'manage_options';
		$menu_slug  = 'shareasale_wc_tracker';
		$callback   = array( $this, 'render_settings_page' );
		$icon_url   = 'dashicons-star-filled';
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url );
	}

	public function render_settings_page() {
		//must be included so regular setting saves show general 'Settings saved' notice
		//and also any setting errors on the stack without a slug (first arg in add_settings_error() function) are displayed using settings_errors()
		include_once 'options-head.php';
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_woocommerce_warning',
				esc_attr( 'woocommerce-warning' ),
				'WooCommerce plugin must be installed and activated to use this plugin.'
			);
			settings_errors( 'shareasale_wc_tracker_woocommerce_warning', false, true );
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings.php';
	}

	private function register_settings() {
		$options = get_option( 'shareasale_wc_tracker_options' );
		
		add_settings_section( 'shareasale_wc_tracker_required', 'Required Merchant Info', array( $this, 'render_settings_required_section_text' ), 'shareasale_wc_tracker' );
		add_settings_field( 'merchant-id', 'Merchant ID*', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_required',
			array(
				'label_for'   => 'merchant-id',
				'id'          => 'merchant-id',
				'name'        => 'merchant-id',
				'value'       => ! empty( $options['merchant-id'] ) ? $options['merchant-id'] : '',
				'status'      => "required='required' autocomplete='off' pattern=\d* maxlength=6",
				'size'        => 22,
				'type'        => 'text',
				'placeholder' => 'ShareASale Merchant ID',
				'class'       => 'shareasale-wc-tracker-option',
			)
		);

		$mastertag = get_option( 'shareasale_wc_tracker_mastertag', array() );

		add_settings_field( 'awin-id', 'AWIN ID', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_required',
			array(
				'label_for'   => 'awin-id',
				'id'          => 'awin-id',
				'name'        => 'awin-id',
				'value'       => ! empty( $options['awin-id'] ) ? $options['awin-id'] : ( ! empty( $mastertag['id'] ) ? $mastertag['id'] : '' ),
				'status'      => "autocomplete='off' pattern=\d* maxlength=6",
				'size'        => 22,
				'type'        => 'text',
				'placeholder' => 'AWIN ID',
				'class'       => 'shareasale-wc-tracker-option',
			)
		);

		add_settings_section( 'shareasale_wc_tracker_optional', 'Optional Pixel Info', array( $this, 'render_settings_optional_section_text' ), 'shareasale_wc_tracker' );
		add_settings_field( 'store-id', 'Store ID', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_optional',
			array(
				'label_for'   => 'store-id',
				'id'          => 'store-id',
				'name'        => 'store-id',
				'value'       => ! empty( $options['store-id'] ) ? $options['store-id'] : '',
				'status'      => '',
				'size'        => '',
				'type'        => 'number',
				'placeholder' => 'ID',
				'class'       => 'shareasale-wc-tracker-option shareasale-wc-tracker-option-number',
			)
		);
		add_settings_field( 'xtype', 'Merchant-Defined Type', array( $this, 'render_settings_select' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_optional',
			array(
				'label_for'   => 'xtype',
				'id'          => 'xtype',
				'name'        => 'xtype',
				'value'       => ! empty( $options['xtype'] ) ? $options['xtype'] : '',
				'status'      => '',
				'size'        => '',
				'type'        => 'select',
				'placeholder' => '',
				'class'       => 'shareasale-wc-tracker-option',
			)
		);
		add_settings_field( 'xtype-hidden', '', array( $this, 'render_settings_input' ), 'shareasale_wc_tracker', 'shareasale_wc_tracker_optional',
			array(
				'label_for'   => 'xtype-hidden',
				'id'          => 'xtype-hidden',
				'name'        => 'xtype-hidden',
				'value'       => ! empty( $options['xtype-hidden'] ) ? $options['xtype-hidden'] : '',
				'status'      => '',
				'size'        => '',
				'type'        => @$options['xtype'] == 'user_defined' ? 'text' : 'hidden',
				'placeholder' => 'Enter your own value',
				'class'       => 'shareasale-wc-tracker-option',
			)
		);
	}

	public function render_settings_page_submenu() {
		//must be included so regular setting saves show general 'Settings saved' notice
		//and also any setting errors on the stack without a slug (first arg in add_settings_error() function) are displayed using settings_errors()
		include_once 'options-head.php';
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_woocommerce_warning',
				esc_attr( 'woocommerce-warning' ),
				'WooCommerce plugin must be installed and activated to use this plugin.'
			);
			settings_errors( 'shareasale_wc_tracker_woocommerce_warning', false, true );
			return;
		}


	}

	public function render_settings_page_subsubsubmenu() {
		//must be included so regular setting saves show general 'Settings saved' notice
		//and also any setting errors on the stack without a slug (first arg in add_settings_error() function) are displayed using settings_errors()
		include_once 'options-head.php';
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_settings_error(
				'shareasale_wc_tracker_woocommerce_warning',
				esc_attr( 'woocommerce-warning' ),
				'WooCommerce plugin must be installed and activated to use this plugin.'
			);
			settings_errors( 'shareasale_wc_tracker_woocommerce_warning', false, true );
			return;
		}
	}
	
	public function render_settings_required_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-required-section-text.php';
	}

	public function render_settings_optional_section_text() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-optional-section-text.php';
	}

	//to do: remove this in favor of using woocommerce_wp_checkbox(), woocommerce_wp_text_input(), and/or woocommerce_wp_hidden_input ?
	public function render_settings_input( $attributes ) {
		$template      = file_get_contents( plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-input.php' );
		$template_data = array_map( 'esc_attr', $attributes );

		foreach ( $template_data as $macro => $value ) {
			$template = str_replace( "!!$macro!!", $value, $template );
		}

		echo wp_kses( $template, array(
									'input' => array(
										'autocomplete' => true,
										'checked'      => true,
										'class'        => true,
										'disabled'     => true,
										'height'       => true,
										'id'           => true,
										'max'          => true,
										'maxlength'    => true,
										'min'          => true,
										'name'         => true,
										'pattern'      => true,
										'placeholder'  => true,
										'required'     => true,
										'size'         => true,
										'type'         => true,
										'value'        => true,
										'width'        => true,
                                        'readonly'     => true
									),
								)
		);
        if (array_key_exists("append_text", $template_data) && $template_data['append_text'] != '') {
            echo $template_data['append_text'];
        }
	}
	
	//to do: remove this in favor of using woocommerce_wp_select() ?
	public function render_settings_select( $attributes ) {
		$template      = file_get_contents( plugin_dir_path( __FILE__ ) . 'templates/shareasale-wc-tracker-settings-select.php' );
		$template_data = array_map( 'esc_attr', $attributes );

		foreach ( $template_data as $macro => $value ) {
			$template = str_replace( "!!$macro!!", $value, $template );
		}
		//find the current saved option and replace its value to add the selected attribute
		$template = str_replace( '"' . $template_data['value'] . '"', '"' . $template_data['value'] . '" selected="selected"', $template );

		echo wp_kses( $template, array(
									'select' => array(
										'autofocus' => true,
										'class'     => true,
										'disabled'  => true,
										'form'      => true,
										'id'        => true,
										'multiple'  => true,
										'name'      => true,
										'required'  => true,
										'size'      => true,
									),
									'optgroup' => array(
										'class'    => true,
										'disabled' => true,
										'id'       => true,
										'label'    => true,
									),
									'option' => array(
										'class'    => true,
										'disabled' => true,
										'id'       => true,
										'label'    => true,
										'selected' => true,
										'value'    => true,
									),
								)
		);

	}

	//add shortcut to settings page from the plugin admin entry for dealsbar
	public function render_settings_shortcut( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=shareasale_wc_tracker' ) ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	//to do: maybe break this into its own class? The method is getting a bit dense...
	public function sanitize_settings( $new_settings = array() ) {
		$old_settings      = get_option( 'shareasale_wc_tracker_options' ) ? get_option( 'shareasale_wc_tracker_options' ) : array();
		//$diff_new_settings is necessary to check whether API credentials have actually changed or not
		$diff_new_settings = array_diff_assoc( $new_settings, $old_settings );
		$final_settings    = array_merge( $old_settings, $new_settings );

		if ( empty( $final_settings['merchant-id'] ) ) {
			add_settings_error(
				'shareasale_wc_tracker_merchant_id',
				esc_attr( 'merchant-id' ),
				'You must enter a ShareASale Merchant ID in the <a href="' . esc_url( admin_url( 'admin.php?page=shareasale_wc_tracker' ) ) . '">Tracking Settings</a> tab.'
			);
		}

		if ( isset( $final_settings['awin-id'] ) && ( $final_settings['merchant-id'] == $final_settings['awin-id'] ) ) {
			add_settings_error(
				'shareasale_wc_tracker_awin_id',
				esc_attr( 'awin-id' ),
				'Your AWIN ID is not the same as your ShareASale Merchant ID. <a href="mailto:shareasale@shareasale.com?Subject=Need%20AWIN%20ID%20Value" target="_blank">Contact support</a> if you are unsure of your AWIN ID value.'
			);

			$final_settings['awin-id'] = '';
		}

		if ( isset( $final_settings['awin-id'] ) ) {
			$mastertag_before = get_option( 'shareasale_wc_tracker_mastertag', array() );
			//in case the option exists but has somehow been nullified or set to something besides an array
			if(!is_array($mastertag_before)){
				$mastertag_before = array();
			}
			$mastertag_before['id'] = $final_settings['awin-id'];
			$mastertag_after = $mastertag_before;
			update_option( 'shareasale_wc_tracker_mastertag', $mastertag_after );
		}

		return $final_settings;
	}
}
