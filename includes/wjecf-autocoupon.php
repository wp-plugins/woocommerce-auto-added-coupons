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
		
		//Admin hooks
        add_action( 'admin_init', array( &$this, 'admin_init' ) );

		//Frontend hooks
		//add_action( 'woocommerce_cart_updated',  array( &$this, 'update_matched_autocoupons' ) ); //experiment 2.1.0-b1
		add_action( 'woocommerce_check_cart_items',  array( &$this, 'update_matched_autocoupons' ) , 0 ); //Remove coupon before WC does it and shows a message
		add_action( 'woocommerce_before_cart_totals',  array( &$this, 'update_matched_autocoupons' ) ); //When cart is updated after changing shipping method
		add_action( 'woocommerce_review_order_before_cart_contents',  array( &$this, 'update_matched_autocoupons' ) ); //When cart is updated after changing shipping or payment method
		
		//Last check for coupons with restricted_emails
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'fetch_billing_email' ), 10 ); // AJAX One page checkout 

		add_filter('woocommerce_cart_totals_coupon_label', array( &$this, 'coupon_label' ), 10, 2 );
		add_filter('woocommerce_cart_totals_coupon_html', array( &$this, 'coupon_html' ), 10, 2 );		

		add_action( 'wp_loaded', array( &$this, 'coupon_by_url' ), 23); //Coupon through url

	}
	
/* ADMIN HOOKS */
	public function admin_init() {
		add_action( 'wjecf_woocommerce_coupon_options_extended_features', array( $this, 'admin_coupon_options_extended_features' ), 20, 0 );
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ), 10, 2 );
	}
	
	public function admin_coupon_options_extended_features() {
		
		//=============================
		//Title
		echo "<h3 style='display:inline'>" . esc_html( __( 'Auto coupon', 'woocommerce-jos-autocoupon' ) ). "</h3>\n";

		
		//=============================
		// Auto coupon checkbox
		woocommerce_wp_checkbox( array(
			'id'          => '_wjecf_is_auto_coupon',
			'label'       => __( 'Auto coupon', 'woocommerce-jos-autocoupon' ),
			'description' => __( "Automatically add the coupon to the cart if the restrictions are met. Please enter a description when you check this box, the description will be shown in the customer's cart if the coupon is applied.", 'woocommerce-jos-autocoupon' )
		) );

		//=============================
		// Apply without notice
		woocommerce_wp_checkbox( array(
			'id'          => '_wjecf_apply_silently',
			'label'       => __( 'Apply silently', 'woocommerce-jos-autocoupon' ),
			'description' => __( "Don't display a message when this coupon is automatically applied.", 'woocommerce-jos-autocoupon' ),
		) );
		
		?>		
		<script type="text/javascript">
			//Hide/show when AUTO-COUPON value changes
			function update_wjecf_apply_silently_field( animation ) { 
					if ( animation === undefined ) animation = 'slow';
					
					if (jQuery("#_wjecf_is_auto_coupon").prop('checked')) {
						jQuery("._wjecf_apply_silently_field").show( animation );
					} else {
						jQuery("._wjecf_apply_silently_field").hide( animation );
					}
			}
			update_wjecf_apply_silently_field( 0 );	
			
			jQuery("#_wjecf_is_auto_coupon").click( update_wjecf_apply_silently_field );
		</script>
		<?php
		
	}
	
	public function process_shop_coupon_meta( $post_id, $post ) {
		update_post_meta( $post_id, '_wjecf_is_auto_coupon', isset( $_POST['_wjecf_is_auto_coupon'] ) ? 'yes' : 'no' );
		update_post_meta( $post_id, '_wjecf_apply_silently', isset( $_POST['_wjecf_apply_silently'] ) ? 'yes' : 'no' );
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
		//$this->log ( 'update_matched_autocoupons' );
		if ( $this->_check_already_performed ) {
			//$this->log ( 'check already performed' );
			return;
		}
		
		global $woocommerce;
		$calc_needed = $this->remove_unmatched_autocoupons();
		foreach ( $this->get_all_auto_coupons() as $coupon_code ) {
			if ( ! $woocommerce->cart->has_discount( $coupon_code ) ) {
				$coupon = new WC_Coupon($coupon_code);
				if ( $this->coupon_can_be_applied($coupon) && $this->coupon_has_a_value( $coupon ) ) {
					//$this->log( sprintf( "Applying %s", $coupon_code ) );
					$woocommerce->cart->add_discount( $coupon_code );				
					$calc_needed = false; //Already done by adding the discount
					$apply_silently = get_post_meta( $coupon->id, '_wjecf_apply_silently', true ) == 'yes';
					$this->overwrite_success_message( $coupon, $apply_silently );
				} else {
					//$this->log( sprintf( "Not applicable: %s", $coupon_code ) );
				}
			}
		}
		$this->_check_already_performed = true;
		
		if ( $calc_needed ) {
			$woocommerce->cart->calculate_totals();
		}
		
	}
	
/**
 * Test whether the coupon is valid and has a discount > 0 
 * @return bool
 */
	function coupon_can_be_applied($coupon) {
		global $woocommerce;
		
		$can_be_applied = true;
		
		//Test validity
		if ( ! $coupon->is_valid() ) {
			$can_be_applied = false;
		}
		//Test individual use
		 elseif ( $coupon->individual_use == 'yes' &&  count( $woocommerce->cart->applied_coupons ) != 0 ) {
			$can_be_applied = false;
		}
		//Test restricted emails
		//See WooCommerce: class-wc-cart.php function check_customer_coupons
		elseif ( is_array( $coupon->customer_email ) && sizeof( $coupon->customer_email ) > 0 ) {
			$user_emails = array_map( 'sanitize_email', array_map( 'strtolower', $this->get_user_emails() ) );
			$coupon_emails = array_map( 'sanitize_email', array_map( 'strtolower', $coupon->customer_email ) );
			
			if ( 0 == sizeof( array_intersect( $user_emails, $coupon_emails ) ) ) {
				$can_be_applied = false;
			}
		}
		
		return apply_filters( 'wjecf_coupon_can_be_applied', $can_be_applied, $coupon );
		
	}

	/**
	 * Does the coupon have a value? (autocoupon should not be applied if it has no value)
	 * @param  WC_Coupon $coupon The coupon data
	 * @return bool True if it has a value (discount, free shipping, whatever) otherwise false)
	 **/
	function coupon_has_a_value($coupon) {
		
		$has_a_value = false;
		
		if ( $coupon->enable_free_shipping() ) {
			$has_a_value = true;
		} else {
			//Test whether discount > 0
			//See WooCommerce: class-wc-cart.php function get_discounted_price
			global $woocommerce;
			foreach ( $woocommerce->cart->get_cart() as $cart_item) {
				if  ( $coupon->is_valid_for_cart() || $coupon->is_valid_for_product( $cart_item['data'], $cart_item ) ) {
					if ( $coupon->get_discount_amount( $cart_item['data']->price, $cart_item ) > 0 ) {
						$has_a_value = true;
						break;
					}
				}
			}
		}
		
		return apply_filters( 'wjecf_coupon_has_a_value', $has_a_value, $coupon );
		
	}
	
	
/**
 * Remove unmatched autocoupons. No message will be shown. 
 * NOTE: This function must be called before WooCommerce removes the coupon, to inhibit WooCommerces "coupon not valid"-message!
 * @return bool True if coupons were removed, otherwise False;
 */
	function remove_unmatched_autocoupons() {
		global $woocommerce;

		$calc_needed = false;
		foreach ( $this->get_all_auto_coupons() as $coupon_code ) {		
			if ( $woocommerce->cart->has_discount( $coupon_code ) ) {
				$coupon = new WC_Coupon($coupon_code);
				if ( ! $this->coupon_can_be_applied($coupon) ) {
					//$this->log( sprintf( "Removing %s", $coupon_code ) );
					WC()->cart->remove_coupon( $coupon_code );  
					$calc_needed = true;
				}
			}
		}
		return $calc_needed;
	}	
	
/**
 * Overwrite the default "Coupon added" notice with a more descriptive message.
 * @param  WC_Coupon $coupon The coupon data
 * @param  bool      $remove_message_only If true the notice will be removed, and no notice will be shown.
 * @return void
 */
	private function overwrite_success_message( $coupon, $remove_message_only = false ) {
		$succss_msg = $coupon->get_coupon_message( WC_Coupon::WC_COUPON_SUCCESS );
		
		//If ajax, remove only
		$remove_message_only |= defined('DOING_AJAX') && DOING_AJAX;
		
		$new_succss_msg = sprintf(
			__("Discount applied: %s", 'woocommerce-jos-autocoupon'), 
			__($this->coupon_excerpt($coupon), 'woocommerce-jos-autocoupon')
		); 
		
		//Compatibility woocommerce-2-1-notice-api
		if ( function_exists('wc_get_notices') ) {
			$all_notices = wc_get_notices();
			if ( ! isset( $all_notices['success'] ) ) {
				$all_notices['success'] = array();
			}
			$messages = $all_notices['success'];
		} else {
			$messages = $woocommerce->messages;
		}
		
		$sizeof_messages = sizeof($messages);
		for( $y=0; $y < $sizeof_messages; $y++ ) { 
			if ( $messages[$y] == $succss_msg ) {
				if ( isset($all_notices) ) {
					if ( $remove_message_only ) {
						unset ( $all_notices['success'][$y] );
					} else {
						$all_notices['success'][$y] = $new_succss_msg;
					}

					WC()->session->set( 'wc_notices', $all_notices );
				} else {
					if ( $remove_message_only ) {
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
		return get_post_meta( $coupon->id, '_wjecf_is_auto_coupon', true ) == 'yes';
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
			
			if ( isset( $_POST['billing_email'] ) )
				$this->_user_emails[] = $_POST['billing_email'];
		}
		//$this->log( "User emails: " . join( ",", $this->_user_emails ) );
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
		$this->_user_emails = array_unique( array_merge( $this->get_user_emails(), $append_emails ) );
		//$this->log('Append emails: ' . join( ',', $append_emails ) );
	}
	
	public function fetch_billing_email( $post_data ) {
		//post_data can be an array, or a query=string&like=this
		if ( ! is_array( $post_data ) ) {
			parse_str( $post_data, $posted );
		} else {
			$posted = $post_data;
		}
		
		if ( isset ( $posted['billing_email'] ) ) {
			$this->append_user_emails( $posted['billing_email'] );
		}
		
	}	
	
/**
 * Get a list of all auto coupon codes
 * @return array All auto coupon codes
 */		
	private function get_all_auto_coupons() {
	
		if ( ! is_array( $this->_autocoupon_codes ) ) {
			$this->_autocoupon_codes = array();
			
			$query_args = array(
				'posts_per_page' => -1,			
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
				'orderby' => 'title',				
				'meta_query' => array(
					array(
						'key' => '_wjecf_is_auto_coupon',
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
	
	//FOR DEBUGGING ONLY
	private function log ( $string ) {
		// file_put_contents ( "/lamp/www/logfile.log", date("Y-m-d | h:i:sa") . " " . current_filter() . ": " . $string . "\n" , FILE_APPEND );
	}
}