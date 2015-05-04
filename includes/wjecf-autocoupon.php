<?php

class WC_Jos_AutoCoupon_Controller{

	private $_autocoupon_codes = null;
	
	private $_user_emails = null;
	
	private $_check_already_performed = false;
	
	public function __construct() {    
		add_action('init', array( &$this, 'controller_init' ));
	}
	
	public function controller_init() {
		if ( ! class_exists('WC_Coupon') ) {
			return;
		}
		
		$this->log( $_SERVER['REQUEST_URI'] );

		//Admin hooks
		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_options' ), 10, 0 );
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ), 10, 2 );

		//Frontend hooks
		add_action( 'woocommerce_check_cart_items',  array( &$this, 'update_matched_autocoupons' ) , 0 ); //Remove coupon before WC does it and shows a message
		add_action( 'woocommerce_before_cart_totals',  array( &$this, 'update_matched_autocoupons' ) ); //When cart is updated after changing shipping method
		add_action( 'woocommerce_review_order_before_cart_contents',  array( &$this, 'update_matched_autocoupons' ) ); //When cart is updated after changing shipping or payment method
		
		add_filter('woocommerce_cart_totals_coupon_label', array( &$this, 'coupon_label' ), 10, 2 );
		add_filter('woocommerce_cart_totals_coupon_html', array( &$this, 'coupon_html' ), 10, 2 );		
		
		//Last check for coupons with restricted_emails
		//DONT USE: Breaks cart preview update //add_action( 'woocommerce_checkout_update_order_review', array( $this, 'checkout_update_order_review' ), 10 ); // AJAX One page checkout  // 
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'after_checkout_validation' ), 0 ); // After checkout / before payment

		add_action( 'wp_loaded', array( &$this, 'coupon_by_url' )); //Coupon through url

	}
	
/* ADMIN HOOKS */

	public function coupon_options() {
		
		// Auto coupon checkbox
		woocommerce_wp_checkbox( array(
			'id'          => 'woocommerce-jos-autocoupon',
			'label'       => __( 'Auto coupon', 'woocommerce-jos-autocoupon' ),
			'description' => __( "Automatically add the coupon to the cart if the restrictions are met. Please enter a description when you check this box, the description will be shown in the customers cart if the coupon is applied.", 'woocommerce-jos-autocoupon' )
		) );
	
	}
	
	public function process_shop_coupon_meta( $post_id, $post ) {
		$autocoupon = isset( $_POST['woocommerce-jos-autocoupon'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, 'woocommerce-jos-autocoupon', $autocoupon );
	}	

/* FRONTEND HOOKS */

/**
 * Add coupon through url
*/
	public function coupon_by_url() {
		if (isset($_GET['apply_coupon'])) {
			$split = explode( ",", $_GET['apply_coupon'] );
			
			global $woocommerce;
			foreach ( $split as $coupon_code ) {
				$woocommerce->cart->add_discount( $coupon_code );
			}
		}
	}
	
/**
 * Overwrite the html created by wc_cart_totals_coupon_label() so a descriptive text will be shown for the discount.
 * @param  string $originaltext The default text created by wc_cart_totals_coupon_label()
 * @param  WC_Coupon $coupon The coupon data
 * @return string The overwritten text
*/	
	function coupon_label( $originaltext, $coupon ) {
		
		if ( $this->is_auto_coupon($coupon) ) {
			
			return $this->coupon_excerpt($coupon); //__($this->autocoupons[$coupon->code], 'woocommerce-jos-autocoupon');
		} else {
			return $originaltext;
		}
	}
	
/**
 * Overwrite the html created by wc_cart_totals_coupon_html(). This function is required to remove the "Remove" link.
 * @param  string $originaltext The html created by wc_cart_totals_coupon_html()
 * @param  WC_Coupon $coupon The coupon data
 * @return string The overwritten html
*/
	function coupon_html( $originaltext, $coupon ) {
		if ( $this->is_auto_coupon($coupon) ) {
				$value  = array();

				if ( $amount = WC()->cart->get_coupon_discount_amount( $coupon->code, WC()->cart->display_cart_ex_tax ) ) {
					$discount_html = '-' . wc_price( $amount );
				} else {
					$discount_html = '';
				}

				$value[] = apply_filters( 'woocommerce_coupon_discount_amount_html', $discount_html, $coupon );

				if ( $coupon->enable_free_shipping() ) {
					$value[] = __( 'Free shipping coupon', 'woocommerce' );
				}

				return implode(', ', array_filter($value)); //Remove empty array elements
		} else {
			return $originaltext;
		}
	}	
	
/**
 * Apply matched autocoupons and remove unmatched autocoupons.
 * @return void
 */	
	function update_matched_autocoupons() {
		$this->log ( 'update_matched_autocoupons' );
		if ( $this->_check_already_performed ) return;
		
		global $woocommerce;
		$this->remove_unmatched_autocoupons();
		foreach ( $this->get_all_auto_coupons() as $coupon_code ) {
			if ( ! $woocommerce->cart->has_discount( $coupon_code ) ) {
				$coupon = new WC_Coupon($coupon_code);
				if ( $this->coupon_can_be_applied($coupon) ) {
					$this->log( sprintf( "Applying %s", $coupon_code ) );
					$woocommerce->cart->add_discount( $coupon_code );				
					$this->overwrite_success_message( $coupon );
				} else {
					$this->log( sprintf( "Not applicable: %s", $coupon_code ) );
				}
			}
		}
		$this->_check_already_performed = true;
	}
	
/**
 * Test whether the coupon is valid and has a discount > 0 
 * @return bool
 */
	function coupon_can_be_applied($coupon) {
		global $woocommerce;
		
		//Test validity
		if ( ! $coupon->is_valid() ) {
			return false;
		}
		//Test individual use
		if ( $coupon->individual_use == 'yes' &&  count( $woocommerce->cart->applied_coupons ) != 0 ) {
			return false;
		}

		//Test restricted emails
		//See WooCommerce: class-wc-cart.php function check_customer_coupons
		if ( is_array( $coupon->customer_email ) && sizeof( $coupon->customer_email ) > 0 ) {
			$user_emails = array_map( 'sanitize_email', array_map( 'strtolower', $this->get_user_emails() ) );
			$coupon_emails = array_map( 'sanitize_email', array_map( 'strtolower', $coupon->customer_email ) );
			
			if ( 0 == sizeof( array_intersect( $user_emails, $coupon_emails ) ) ) {
				return false;
			}
		}
		
		//===========================================
		//Can be applied. Now test if it has a value
		
		if ( $coupon->enable_free_shipping() ) {
			return true;
		}
		//Test whether discount > 0
		//See WooCommerce: class-wc-cart.php function get_discounted_price
		foreach ( $woocommerce->cart->get_cart() as $cart_item) {
			if  ( $coupon->is_valid_for_cart() || $coupon->is_valid_for_product( $cart_item['data'], $cart_item ) ) {
				if ( $coupon->get_discount_amount( $cart_item['data']->price, $cart_item ) > 0 ) {
					return true;
				}
			}
		}
		
		return false;		
	}
	
	
	
	
/**
 * Remove unmatched autocoupons. No message will be shown. 
 * NOTE: This function must be called before WooCommerce removes the coupon, to inhibit WooCommerces "coupon not valid"-message!
 * @return void
 */
	function remove_unmatched_autocoupons() {
		global $woocommerce;

		foreach ( $this->get_all_auto_coupons() as $coupon_code ) {		
			if ( $woocommerce->cart->has_discount( $coupon_code ) ) {
				$coupon = new WC_Coupon($coupon_code);
				if ( ! $this->coupon_can_be_applied($coupon) ) {
					$this->log( sprintf( "Removing %s", $coupon_code ) );
					WC()->cart->remove_coupon( $coupon_code );  
				}
			}
		}
	}	
	
/**
 * Overwrite the default "Coupon added" notice with a more descriptive message.
 * @param  WC_Coupon $coupon The coupon data
 * @return void
 */
	private function overwrite_success_message( $coupon ) {
		$succss_msg = $coupon->get_coupon_message( WC_Coupon::WC_COUPON_SUCCESS );
		
		$new_succss_msg = sprintf(
			__("Discount applied: %s", 'woocommerce-jos-autocoupon'), 
			__($this->coupon_excerpt($coupon), 'woocommerce-jos-autocoupon')
		); 
		
		//Compatibility woocommerce-2-1-notice-api
		if ( function_exists('wc_get_notices') ) {
			$all_notices = wc_get_notices();
			$messages = $all_notices['success'];
		} else {
			$messages = $woocommerce->messages;
		}
		
		$sizeof_messages = sizeof($messages);
		for( $y=0; $y < $sizeof_messages; $y++ ) { 
			if ( $messages[$y] == $succss_msg ) {
				if ( isset($all_notices) ) {
					if (defined('DOING_AJAX') && DOING_AJAX) {
						unset ( $all_notices['success'][$y] );
					} else {
						$all_notices['success'][$y] = $new_succss_msg;
					}

					WC()->session->set( 'wc_notices', $all_notices );
				} else {
					if (defined('DOING_AJAX') && DOING_AJAX) {
						unset ( $messages[$y] );
					} else {
						$messages[$y] = $new_succss_msg;
					}
				}
				
				break;
			}
		}
	}
	
/**
 * Check wether the coupon is an "Auto coupon".
 * @param  WC_Coupon $coupon The coupon data
 * @return bool true if it is an "Auto coupon"
 */	
	private function is_auto_coupon($coupon) {
		return get_post_meta( $coupon->id, 'woocommerce-jos-autocoupon', true ) == 'yes';
	}
	

/**
 * Get the coupon excerpt (description)
 * @param  WC_Coupon $coupon The coupon data
 * @return string The excerpt (translated)
 */	
	private function coupon_excerpt($coupon) {
		$my_post = get_post($coupon->id);
		return __($my_post->post_excerpt, 'woocommerce-jos-autocoupon');
	}	

/**
 * Get a list of the users' known email addresses
 *
 */
	private function get_user_emails() {
		if ( ! is_array($this->_user_emails) ) {
			$this->_user_emails = array();
			//Email of the logged in user
			if ( is_user_logged_in() ) {
				$current_user   = wp_get_current_user();
				$this->_user_emails[] = $current_user->user_email;
			}
		}
		$this->log( "User emails: ", join( ",", $this->_user_emails ) );
		return $this->_user_emails;		
	}

/**
 * Append a single or an array of email addresses.
 * @param  array|string $append_emails The email address(es) to be added
 * @return void
 */
	private function append_user_emails($append_emails) {
		//$append_emails must be an array
		if ( ! is_array( $append_emails ) ) {
			$append_emails = array( $append_emails );
		}
		
		$this->_user_emails = array_merge( $this->get_user_emails(), $append_emails );
	}
	
/**
 * After checkout validation
 * Get billing email for coupon with restricted_emails
 * Append matching coupons with restricted emails
 * @param $posted array Billing data 
 * @return void
 */
	public function after_checkout_validation( $posted ) {
		$this->log ( 'update_matched_autocoupons' );
		// $this->log ( sprintf( "After checkout: %s", print_r ( $posted, true ) ) );
		$this->get_user_emails();
		if ( isset ( $posted['billing_email'] ) ) {
			$this->append_user_emails( $posted['billing_email'] );
		}
		
		$this->update_matched_autocoupons();
	}

	//Same as after_checkout_validation, only post_data is a query=string&like=this that must be converted to an array
	public function checkout_update_order_review( $post_data ) {
		$posted = array();
		parse_str( $post_data, $posted );
		$this->after_checkout_validation( $posted );
	}
	
/**
 * Get a list of all auto coupon codes
 * @return array All auto coupon codes
 */		
	private function get_all_auto_coupons() {
	
		if ( !is_array($this->_autocoupon_codes) ) {
			$this->_autocoupon_codes = array();
			
			$query_args = array(
				'posts_per_page' => -1,			
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
				'orderby' => 'title',				
				'meta_query' => array(
					array(
						'key' => 'woocommerce-jos-autocoupon',
						'value' => 'yes',
						'compare' => '=',
					),
				)
			);
		
			$query = new WP_Query($query_args);
			foreach ($query->posts as $post) {
				$coupon = new WC_Coupon($post->post_title);
				if ( $this->is_auto_coupon($coupon) ) {
					$this->_autocoupon_codes[] = $post->post_title;
				}
			}			
		}
		return $this->_autocoupon_codes;
	}	
	
	private function log ( $string ) {
		// file_put_contents ( "/lamp/www/logfile.log", current_filter() . ": " . $string . "\n" , FILE_APPEND );
	}
}