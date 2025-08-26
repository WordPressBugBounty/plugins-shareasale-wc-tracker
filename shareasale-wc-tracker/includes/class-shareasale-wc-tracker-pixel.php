<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ShareASale_WC_Tracker_Pixel {

	/**
	* @var WC_Order $order WooCommere order object https://docs.woothemes.com/wc-apidocs/class-WC_Order.html
	* @var string $version Plugin version
	*/

	private $order, $version;

	public function __construct( $version ) {
		$this->version = $version;

		return $this;
	}

	public function script_loader_tag( $tag, $handle, $src ) {
		$defer_scripts = array( 'shareasale-wc-tracker-mastertag' );
		$other_scripts = array(
			'shareasale-wc-tracker-admin-js',
			'shareasale-wc-tracker-analytics',
			'shareasale-wc-tracker-analytics-cart-observer',
			'shareasale-wc-tracker-analytics-add-to-cart',
			'shareasale-wc-tracker-analytics-begin-checkout',
			'shareasale-wc-tracker-analytics-applied-coupon',
			'shareasale-wc-tracker-analytics-conversion',
			'shareasale-wc-tracker-triggered',
			'shareasale-wc-tracker-pixel',
			'shareasale-wc-tracker-cookie-setter',
		);

		if ( in_array( $handle, $defer_scripts, true ) ) {
			return '<script type="text/javascript" src="' . $src . '" defer data-noptimize></script>' . "\n";
		}

		if ( in_array( $handle, $other_scripts, true ) ) {
			return '<script type="text/javascript" src="' . $src . '" data-noptimize></script>' . "\n";
		}

		return $tag;
	}

	public function woocommerce_thankyou( $order_id ) {
		// Handle case where no order_id is passed (WooCommerce Blocks)
		if ( ! $order_id ) {
			// Try to get order_id from URL parameters (WooCommerce Blocks)
			if ( isset( $_GET['key'] ) && ! empty( $_GET['key'] ) ) {
				$order_key = wc_clean( wp_unslash( $_GET['key'] ) );
				$order_id = wc_get_order_id_by_order_key( $order_key );
			}
			if ( ! $order_id ) {
				echo '<!-- ShareASale: No order ID available -->';
				return;
			}
		}

		$merchant_id    = ShareASale_WC_Tracker_Config::get_merchant_id();
		$store_id       = ShareASale_WC_Tracker_Config::get_store_id();
		$xtype          = ShareASale_WC_Tracker_Config::get_xtype();
		$xtype_hidden   = ShareASale_WC_Tracker_Config::get_xtype_hidden();
        $order          = wc_get_order( $order_id );
		
		// Additional check for valid order
		if ( ! $order ) {
			echo '<!-- ShareASale: Invalid order -->';
			return;
		}
		
		$prev_triggered = $order->get_meta( 'shareasale-wc-tracker-triggered', true );

		if ( ! $order_id || ! $merchant_id ) {
			echo '<!-- no ShareASale merchant ID entered or order ID doesn\'t exist-->';
			return;
		}
		//allow &troubleshooting=1 so tech/launch team can view past referrer URLs and check for pixel presence.
		if ( $prev_triggered && ! isset( $_GET['troubleshooting'] ) ) {
			echo '<!-- ShareASale pixel was previously triggered -->';
			return;
		}

		if ( $store_id ) {
			$store_id = '&storeID=' . $store_id;
		}
		
		$this->order = new WC_Order( $order_id );

		switch ( $xtype ) {
			case 'customer_billing_country_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_billing_country() : $this->order->billing_country );
				break;

			case 'customer_billing_state_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_billing_state() : $this->order->billing_state );
				break;

			case 'customer_billing_city_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_billing_city() : $this->order->billing_city );
				break;

			case 'customer_shipping_country_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_country() : $this->order->shipping_country );
				break;

			case 'customer_shipping_state_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_state() : $this->order->shipping_state );
				break;

			case 'customer_shipping_city_code':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_city() : $this->order->shipping_city );
				break;

			case 'customer_id':
				$xtype = '&xtype=' . $this->order->get_user_id();
				break;

			case 'customer_device_type':
				$xtype = '&xtype=' . ( wp_is_mobile() ? 'mobile' : 'desktop' );
				break;

			case 'payment_type':
				$xtype = '&xtype=' . ( version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_payment_method_title() : $this->order->payment_method_title );
				break;

			case 'payment_shipping':
				$xtype = '&xtype=' . $this->order->get_shipping_method();
				break;

			case 'user_defined':
				$xtype = '&xtype=' . urlencode($xtype_hidden);
				break;

			default:
				$xtype = '&xtype=';
		}

		// removed in version 1.4.5 in favor of Awin's master tag
		// Brought back in 1.6.0 
		if( ! empty( $_COOKIE['shareasaleWcTrackerSSCID'] ) && ! isset( $_GET['troubleshooting'] ) ) {
			$sscid = '&sscid=' . $_COOKIE['shareasaleWcTrackerSSCID'] . '&sscidmode=6';
		}else {
			$sscid = '';
		}
		
		$product_data = $this->get_product_data();

		$params = array(
				'amount'       => $this->get_order_amount(),
				'tracking'     => $this->order->get_order_number(),
				'transtype'    => 'sale',
				'merchantID'   => $merchant_id,
				'skulist'      => $product_data->skulist,
				'quantitylist' => $product_data->quantitylist,
				'pricelist'    => $product_data->pricelist,
				'couponcode'   => $this->get_coupon_codes(),
				'currency'     => $this->get_currency(),
				'newcustomer'  => $this->get_customer_status(),
				'v'            => $this->version,
			);

		$query_string = '?' . http_build_query( $params );

		$url          = 'https://shareasale.com/sale.cfm' . $query_string . $store_id . $xtype /* . $sscid */;

		//updates post meta client-side for this order to mark it as pixel displayed
		$src = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-triggered.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-triggered',
			$src,
			array(),
			$this->version
		);

		wp_localize_script(
			'shareasale-wc-tracker-triggered',
			'shareasaleWcTrackerTriggeredData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_ajax_shareasale_wc_tracker_triggered' ),
				'post_id' => $order_id,
			)
		);

		//add the actual tracking pixel to the page via JS
		$src2 = esc_url( plugin_dir_url( __FILE__ ) . 'js/shareasale-wc-tracker-pixel.js' );
		wp_enqueue_script(
			'shareasale-wc-tracker-pixel',
			$src2,
			array( 'shareasale-wc-tracker-triggered' ),
			$this->version
		);

		wp_localize_script(
			'shareasale-wc-tracker-pixel',
			'shareasaleWcTrackerPixel',
			array(
				'src'    => $url,
				'onload' => 'shareasaleWcTrackerTriggered()',
				'id'     => '_SHRSL_img_1',
			)
		);

		// Awin server-to-server conversion tracking (always fire for backup tracking)
		$this->perform_server_to_server_call( $order_id );

	}

	public function wp_ajax_nopriv_shareasale_wc_tracker_triggered() {
		$this->wp_ajax_shareasale_wc_tracker_triggered();
	}

	public function wp_ajax_shareasale_wc_tracker_triggered() {
		$nonce    = wp_verify_nonce( $_POST['nonce'], 'wp_ajax_shareasale_wc_tracker_triggered' );
		$order_id = intval( $_POST['post_id'] );
		if ( $nonce && $order_id ) {
            $order = wc_get_order( $order_id );
			$order->add_meta_data( 'shareasale-wc-tracker-triggered', date( 'Y-m-d H:i:s' ), true );
			$order->save();
            wp_send_json( array( 'order_id' => $order_id ) );
		} else {
			wp_send_json( array( 'order_id' => false ) );
		}
	}

	private function get_order_amount() {

		$grand_total    = $this->order->get_total();
		$total_shipping = version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_shipping_total() : $this->order->get_total_shipping();
		$total_taxes    = $this->order->get_total_tax();
		$subtotal       = $grand_total - ( $total_shipping + $total_taxes );

		if ( $subtotal < 0 ) {
			$subtotal = 0;
		}

		return $subtotal;
	}

	private function get_product_data() {

		$product_data = new stdClass();

		$items = $this->order->get_items();
		$last_index = array_search( end( $items ), $items, true );

		foreach ( $items as $index => $item ) {
			$delimiter = $index === $last_index ? '' : ',';
			$product   = 0 != $item['variation_id'] ? new WC_Product_Variation( $item['variation_id'] ) : new WC_Product( $item['product_id'] );
			$sku       = $product->get_sku();

			isset( $product_data->skulist ) ? $product_data->skulist .= $sku . $delimiter : $product_data->skulist = $sku . $delimiter;

			isset( $product_data->pricelist ) ? $product_data->pricelist .= round( ( $item['line_total'] / $item['qty'] ), 2 ) . $delimiter : $product_data->pricelist = round( ( $item['line_total'] / $item['qty'] ), 2 ) . $delimiter;

			isset( $product_data->quantitylist ) ? $product_data->quantitylist .= $item['qty'] . $delimiter : $product_data->quantitylist = $item['qty'] . $delimiter;
		}

		return $product_data;

	}

	private function get_customer_status() {
		$newcustomer = '';
		if ( method_exists( $this->order, 'get_user_id' ) ) {

			$customer_user_id = $this->order->get_user_id();
			// Debug: Log customer user ID for troubleshooting
			error_log("ShareASale Debug: Customer User ID = " . $customer_user_id);
			
			if ( 0 !== $customer_user_id ) {
				// Try multiple methods to get order count for this customer
				$user_orders = wc_get_orders(
					array(
						'customer_id' => $customer_user_id,
						'limit'       => 2,
						'status'      => array_keys( wc_get_order_statuses() ),
					)
				);
				$order_count = count( $user_orders );
				
				// If first method doesn't work, try the legacy method
				if ( $order_count === 0 ) {
					$user_orders = wc_get_orders(
						array(
							'post_type'   => wc_get_order_types(),
							'meta_key'    => '_customer_user',
							'meta_value'  => $customer_user_id,
							'numberposts' => 2,
							'post_status' => array_keys( wc_get_order_statuses() ),
						)
					);
					$order_count = count( $user_orders );
				}
				
				// Debug: Log order count for troubleshooting
				error_log("ShareASale Debug: Order count for user $customer_user_id = " . $order_count);
				
				$newcustomer = ($order_count > 1 ? 'RETURNING' : '');
			} else {
				// Guest checkout - also check by email if available
				$billing_email = $this->order->get_billing_email();
				if ( $billing_email ) {
					error_log("ShareASale Debug: Guest checkout, checking by email: " . $billing_email);
					$email_orders = wc_get_orders(
						array(
							'billing_email' => $billing_email,
							'limit'         => 2,
							'status'        => array_keys( wc_get_order_statuses() ),
						)
					);
					$email_order_count = count( $email_orders );
					error_log("ShareASale Debug: Email order count = " . $email_order_count);
					$newcustomer = ($email_order_count > 1 ? 'RETURNING' : '');
				} else {
					error_log("ShareASale Debug: Guest checkout detected (user_id = 0, no email)");
				}
			}
		}

		// Debug: Log final customer status
		error_log("ShareASale Debug: Final customer status = '" . $newcustomer . "'");
		return $newcustomer;

	}

	private function get_coupon_codes() {

		$couponcode = version_compare( WC()->version, '3.7' ) >= 0 ? implode( ',', $this->order->get_coupon_codes() ) : implode( ',', $this->order->get_used_coupons() );

		return $couponcode;
	}

	private function get_currency() {

		$currency = version_compare( WC()->version, '3.0' ) >= 0 ? $this->order->get_currency() : $this->order->get_order_currency();

		return $currency;
	}



	/**
	 * Perform server-to-server conversion tracking to Awin
	 * 
	 * @param int $order_id WooCommerce order ID
	 * @return bool Success status
	 */
	public function perform_server_to_server_call( $order_id ) {
		// Check if S2S conversion was already sent
		$s2s_sent = get_post_meta( $order_id, '_s2s_conversion_sent', true );
		if ( $s2s_sent && ! isset( $_GET['troubleshooting'] ) ) {
			return false; // Already sent, avoid duplicate
		}

		$merchant_id = ShareASale_WC_Tracker_Config::get_merchant_id();
		$store_id = ShareASale_WC_Tracker_Config::get_store_id();

		if ( ! $merchant_id ) {
			return false; // No merchant ID configured
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false; // Invalid order
		}

		// Set the order property for methods that depend on it
		$this->order = $order;

		// Only send S2S for valid, completed orders
		$valid_statuses = array( 'completed', 'processing', 'on-hold' );
		if ( ! in_array( $order->get_status(), $valid_statuses ) ) {
			return false; // Order not in valid status for conversion tracking
		}

		// Don't send for cancelled or refunded orders
		$invalid_statuses = array( 'cancelled', 'refunded', 'failed', 'trash' );
		if ( in_array( $order->get_status(), $invalid_statuses ) ) {
			return false; // Order has been cancelled/refunded
		}

		// Additional validation: Order must have a total amount > 0
		$order_total = $order->get_total();
		if ( $order_total <= 0 ) {
			return false; // No value to track
		}

		// Only track orders placed by customers (not admin-created)
		$created_via = $order->get_created_via();
		if ( in_array( $created_via, array( 'admin', 'rest-api' ) ) && ! isset( $_GET['troubleshooting'] ) ) {
			return false; // Skip admin/API created orders unless troubleshooting
		}

		// Build S2S parameters similar to client-side pixel
		$product_data = $this->get_product_data();
		$order_amount = $this->get_order_amount();

		// Handle store ID
		$store_param = '';
		if ( $store_id ) {
			$store_param = '&storeID=' . $store_id;
		}

		$total_price = number_format( (float) $order->get_total() - $order->get_total_tax() - $order->get_total_shipping(), 2, '.', '' );

		// Get voucher/coupon code (first one if multiple)
		$voucher = '';
		$coupons = $order->get_coupon_codes();
		if ( count( $coupons ) > 0 ) {
			$voucher = $coupons[0];
		}

		// get shareASale's cookie
		if( ! empty( $_COOKIE['shareasaleWcTrackerSSCID'] ) && ! isset( $_GET['troubleshooting'] ) ) {
			$sscid = '&sscid=' . $_COOKIE['shareasaleWcTrackerSSCID'] . '&sscidmode=6';
		}else {
			$sscid = '';
		}

		$xtype = ShareASale_WC_Tracker_Config::get_xtype() ?? ShareASale_WC_Tracker_Config::get_xtype_hidden();

		$params = array(
			'amount'       => $total_price,
			'tracking'     => $order->get_order_number(),
			'transtype'    => 'sale',
			'merchantID'   => $merchant_id,
			'skulist'      => $product_data->skulist,
			'quantitylist' => $product_data->quantitylist,
			'pricelist'    => $product_data->pricelist,
			'couponcode'   => $voucher,
			'currency'     => $this->get_currency(),
			'newcustomer'  => $this->get_customer_status(),
			'v'            => $this->version,
			'xtype'        => $xtype,
		);
		
		// Build the URL with parameters
		$query_string = '?' . http_build_query( $params );
		$url          = 'https://shareasale.com/q.cfm' . $query_string . $store_id . $sscid;

		// Make the server-to-server request
		$response = wp_remote_get( $url, array(
			'timeout'     => 30,
			'sslverify'   => true,
		) );

		// Handle response
		if ( is_wp_error( $response ) ) {
			return false;
		} else {
			// Mark as sent to prevent duplicates
			update_post_meta( $order_id, '_s2s_conversion_sent', date( 'Y-m-d H:i:s' ) );
			return true;
		}
	}
}
