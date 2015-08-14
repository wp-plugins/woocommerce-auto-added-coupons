<?php

 
defined('ABSPATH') or die();

class WC_Jos_Extended_Coupon_Features_Controller {
	
	public function __construct() {    
		add_action('init', array( &$this, 'controller_init' ));
	}

	public function controller_init() {
		if ( ! class_exists('WC_Coupon') ) {
			return;
		}

		//Admin hooks
		add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'admin_coupon_options_tabs' ), 10, 1);
		add_action( 'woocommerce_coupon_data_panels', array( $this, 'admin_coupon_options_panels' ), 10, 0 );
		
		add_action( 'wjecf_woocommerce_coupon_options_extended_features', array( $this, 'admin_coupon_options_extended_features' ), 10, 0 );
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ), 10, 2 );		
		
		//Frontend hooks
		add_filter('woocommerce_coupon_is_valid', array( &$this, 'coupon_is_valid' ), 10, 2 );		
	}
	
/* ADMIN HOOKS */

	//Add panels to the coupon option page
	public function admin_coupon_options_panels() {
		
		echo '<div id="wjecf_extended_features_coupon_data" class="panel woocommerce_options_panel">';
		
		//Title
		echo "<h3 style='display:inline'>" . esc_html( __( 'Extended Coupon Features', 'woocommerce-jos-autocoupon' ) ). "</h3>\n";
		//Beg for money
		printf( '<a href="%s" title="Support the development" target="_blank">', $this->get_donate_url() );
		echo esc_html( __('Donate to the developer', 'woocommerce-jos-autocoupon' ) );		
		echo  "</a><br><hr>\n";
		
		
		//Feed the panel with options
		do_action( 'wjecf_woocommerce_coupon_options_extended_features' );
		
		echo '</div>';

	}
	
	//Add tabs to the coupon option page
	public function admin_coupon_options_tabs( $tabs ) {
		
		$tabs['extended_features'] = array(
			'label'  => __( 'Extended features', 'woocommerce-jos-autocoupon' ),
			'target' => 'wjecf_extended_features_coupon_data',
			'class'  => 'wjecf_extended_features_coupon_data',
		);
		return $tabs;
	}

	//Tab 'extended features'
	public function admin_coupon_options_extended_features() {
		global $thepostid, $post;
		$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
		
		//See WooCommerce class-wc-meta-box-coupon-data.php function ouput
		
		
		//=============================
		// AND in stead of OR the products
		woocommerce_wp_checkbox( array( 
			'id' => '_wjecf_products_and', 
			'label' => __( 'AND Products (not OR)', 'woocommerce-jos-autocoupon' ), 
			'description' => __( 'Check this box if ALL of the products (see tab \'usage restriction\') must be in the cart to use this coupon (in stead of only one of the products).', 'woocommerce-jos-autocoupon' )
		) );
		
		// Minimum quantity of matching products (product/category)
		woocommerce_wp_text_input( array( 
			'id' => '_wjecf_matching_product_qty', 
			'label' => __( 'Minimum quantity of matching products', 'woocommerce' ), 
			'placeholder' => __( 'No minimum', 'woocommerce' ), 
			'description' => __( 'Minimum quantity of the products that match the given product or category restrictions (see tab \'usage restriction\'). If no product or category restrictions are specified, the total number of products is used.', 'woocommerce-jos-autocoupon' ), 
			'data_type' => 'decimal', 
			'desc_tip' => true
		) );
		
		//=============================
		//Trick to show AND or OR next to the product_ids field 		
		$label_and = __( '(AND)', 'woocommerce-jos-autocoupon' );
		$label_or  = __( '(OR)',  'woocommerce-jos-autocoupon' );
		$label = get_post_meta( $thepostid, '_wjecf_products_and', true ) == 'yes' ? $label_and : $label_or;
		?>		
		<script type="text/javascript">
			//Update AND or OR in product_ids label when checkbox value changes
			jQuery("#_wjecf_products_and").click( 
				function() { 
					jQuery("#wjecf_products_and_label").html( 
						jQuery("#_wjecf_products_and").attr('checked') ? '<?php echo esc_js( $label_and ); ?>' : '<?php echo esc_js( $label_or ); ?>'
					);
			} );
			//Append AND/OR to the product_ids label
			jQuery(".form-field:has('[name=\"product_ids\"]') label").append( ' <strong><span id="wjecf_products_and_label"><?php echo esc_html( $label ); ?></span></strong>' );
		</script>
		<?php //End of the AND/OR trick

		
		//=============================
		// Shipping methods
		?>
		<p class="form-field"><label for="wjecf_shipping_methods"><?php _e( 'Shipping methods', 'woocommerce-jos-autocoupon' ); ?></label>
		<select id="wjecf_shipping_methods" name="wjecf_shipping_methods[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Any shipping method', 'woocommerce-jos-autocoupon' ); ?>">
			<?php
				$coupon_shipping_method_ids = $this->get_coupon_shipping_method_ids( $thepostid );
				$shipping_methods = WC()->shipping->load_shipping_methods();

				if ( $shipping_methods ) foreach ( $shipping_methods as $shipping_method ) {
					echo '<option value="' . esc_attr( $shipping_method->id ) . '"' . selected( in_array( $shipping_method->id, $coupon_shipping_method_ids ), true, false ) . '>' . esc_html( $shipping_method->method_title ) . '</option>';
				}
			?>
		</select> <img class="help_tip" data-tip='<?php _e( 'One of these shipping methods must be selected in order for this coupon to be valid.', 'woocommerce-jos-autocoupon' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
		<?php		
		
		//=============================
		// Payment methods
		?>
		<p class="form-field"><label for="wjecf_payment_methods"><?php _e( 'Payment methods', 'woocommerce-jos-autocoupon' ); ?></label>
		<select id="wjecf_payment_methods" name="wjecf_payment_methods[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Any payment method', 'woocommerce-jos-autocoupon' ); ?>">
			<?php
				$coupon_payment_method_ids = $this->get_coupon_payment_method_ids( $thepostid );
				//DONT USE: CAN CRASH IN UNKNOWN OCCASIONS // $payment_methods = WC()->payment_gateways->available_payment_gateways();
				$payment_methods = WC()->payment_gateways->payment_gateways();
				if ( $payment_methods ) foreach ( $payment_methods as $payment_method ) {
					if ('yes' === $payment_method->enabled) {
						echo '<option value="' . esc_attr( $payment_method->id ) . '"' . selected( in_array( $payment_method->id, $coupon_payment_method_ids ), true, false ) . '>' . esc_html( $payment_method->title ) . '</option>';
					}
				}
			?>
		</select> <img class="help_tip" data-tip='<?php _e( 'One of these payment methods must be selected in order for this coupon to be valid.', 'woocommerce-jos-autocoupon' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
		<?php		

		
		//=============================
		//Title: "CUSTOMER RESTRICTIONS"
		echo "<h3 style='display:inline'>" . esc_html( __( 'Customer restrictions', 'woocommerce-jos-autocoupon' ) ). "</h3>\n";
		echo "<p><span class='description'>" . __( 'If both a customer and a role restriction are supplied, matching either one of them will suffice.' , 'woocommerce-jos-autocoupon' ) . "</span></p>\n";
		
		//=============================
		// User ids
		?>
		<p class="form-field"><label><?php _e( 'Customers', 'woocommerce' ); ?></label>
		<input type="hidden" class="wc-customer-search" data-multiple="true" style="width: 50%;" name="wjecf_customer_ids" data-placeholder="<?php _e( 'Any customer', 'woocommerce' ); ?>" data-action="woocommerce_json_search_customers" data-selected="<?php
			$coupon_customer_ids = $this->get_coupon_customer_ids( $thepostid );
			$json_ids    = array();
			
			foreach ( $coupon_customer_ids as $customer_id ) {
				$customer = get_userdata( $customer_id );
				if ( is_object( $customer ) ) {
					$json_ids[ $customer_id ] = $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . sanitize_email( $customer->user_email ) . ')';
				}
			}

			echo esc_attr( json_encode( $json_ids ) );
		?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" /> <img class="help_tip" data-tip='<?php _e( 'Coupon only applies to these customers.', 'woocommerce' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
		<?php

		//=============================
		// User roles
		?>
		<p class="form-field"><label for="wjecf_customer_roles"><?php _e( 'Customer roles', 'woocommerce-jos-autocoupon' ); ?></label>
		<select id="wjecf_customer_roles" name="wjecf_customer_roles[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Any role', 'woocommerce-jos-autocoupon' ); ?>">
			<?php			
				$coupon_customer_roles = $this->get_coupon_customer_roles( $thepostid );

				$available_customer_roles = array_reverse( get_editable_roles() );
				foreach ( $available_customer_roles as $role_id => $role ) {
					$role_name = translate_user_role($role['name'] );
	
					echo '<option value="' . esc_attr( $role_id ) . '"'
					. selected( in_array( $role_id, $coupon_customer_roles ), true, false ) . '>'
					. esc_html( $role_name ) . '</option>';
				}
			?>
		</select> <img class="help_tip" data-tip='<?php _e( 'The customer must have one of these roles in order for this coupon to be valid.', 'woocommerce-jos-autocoupon' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
		<?php	

		//=============================
		// Excluded user roles
		?>
		<p class="form-field"><label for="wjecf_excluded_customer_roles"><?php _e( 'Excluded customer roles', 'woocommerce-jos-autocoupon' ); ?></label>
		<select id="wjecf_customer_roles" name="wjecf_excluded_customer_roles[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Any role', 'woocommerce-jos-autocoupon' ); ?>">
			<?php			
				$coupon_excluded_customer_roles = $this->get_coupon_excluded_customer_roles( $thepostid );

				foreach ( $available_customer_roles as $role_id => $role ) {
					$role_name = translate_user_role($role['name'] );
	
					echo '<option value="' . esc_attr( $role_id ) . '"'
					. selected( in_array( $role_id, $coupon_excluded_customer_roles ), true, false ) . '>'
					. esc_html( $role_name ) . '</option>';
				}
			?>
		</select> <img class="help_tip" data-tip='<?php _e( 'The customer must not have one of these roles in order for this coupon to be valid.', 'woocommerce-jos-autocoupon' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
		<?php	
	}
	
	public function process_shop_coupon_meta( $post_id, $post ) {
		$wjecf_matching_product_qty = isset( $_POST['_wjecf_matching_product_qty'] ) ? $_POST['_wjecf_matching_product_qty'] : '';
		update_post_meta( $post_id, '_wjecf_matching_product_qty', $wjecf_matching_product_qty );
				
		$wjecf_products_and = isset( $_POST['_wjecf_products_and'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wjecf_products_and', $wjecf_products_and );
		
		$wjecf_shipping_methods = isset( $_POST['wjecf_shipping_methods'] ) ? $_POST['wjecf_shipping_methods'] : '';
		update_post_meta( $post_id, '_wjecf_shipping_methods', $wjecf_shipping_methods );		
		
		$wjecf_payment_methods = isset( $_POST['wjecf_payment_methods'] ) ? $_POST['wjecf_payment_methods'] : '';
		update_post_meta( $post_id, '_wjecf_payment_methods', $wjecf_payment_methods );		
		
		$wjecf_customer_ids    = implode(",", array_filter( array_map( 'intval', explode(",", $_POST['wjecf_customer_ids']) ) ) );
		update_post_meta( $post_id, '_wjecf_customer_ids', $wjecf_customer_ids );	

		$wjecf_customer_roles    = isset( $_POST['wjecf_customer_roles'] ) ? $_POST['wjecf_customer_roles'] : '';
		update_post_meta( $post_id, '_wjecf_customer_roles', $wjecf_customer_roles );	

		$wjecf_excluded_customer_roles    = isset( $_POST['wjecf_excluded_customer_roles'] ) ? $_POST['wjecf_excluded_customer_roles'] : '';
		update_post_meta( $post_id, '_wjecf_excluded_customer_roles', $wjecf_excluded_customer_roles );	
		
	}	

/* FRONTEND HOOKS */

/* Extra validation rules for coupons */
	function coupon_is_valid ( $valid, $coupon ) {
		//Not valid? Then it will never validate, so get out of here
		if ( ! $valid ) {
			return false;
		}
		
		//============================
		//Test if ALL products are in the cart (if AND-operator selected in stead of the default OR)
		$products_and = get_post_meta( $coupon->id, '_wjecf_products_and', true ) == 'yes';
		if ( $products_and && sizeof( $coupon->product_ids ) > 1 ) { // We use > 1, because if size == 1, 'AND' makes no difference		
			//Get array of all cart product and variation ids
			$cart_item_ids = array();
			foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$cart_item_ids[] = $cart_item['product_id'];
				$cart_item_ids[] = $cart_item['variation_id'];
			}
			//check if every single product is in the cart
			foreach( $coupon->product_ids as $product_id ) {
				if ( ! in_array( $product_id, $cart_item_ids ) ) {
					return false;
				}
			}		
		}
		
		
		//============================
		//Test quantity of matching products
		//
		//For all items in the cart:
		//  If coupon contains both a product AND category inclusion filter: the item is counted if it matches either one of them
		//  If coupon contains either a product OR category exclusion filter: the item will NOT be counted if it matches either one of them
		//  If sale items are excluded by the coupon: the item will NOT be counted if it is a sale item
		//  If no filter exist, all items will be counted
		$matching_product_qty = intval(get_post_meta( $coupon->id, '_wjecf_matching_product_qty', true ));
		if ( $matching_product_qty > 0 ) { 
			$qty = 0;
			foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {				
				$_product = $cart_item['data'];				
				if ($this->coupon_is_valid_for_product( $coupon, $_product )) {
					$qty += $cart_item['quantity'];
				}
			}
			if ($qty < $matching_product_qty) return false;
		}	


		//============================
		//Test restricted shipping methods
		$shipping_method_ids = $this->get_coupon_shipping_method_ids( $coupon->id );
		if ( sizeof( $shipping_method_ids ) > 0 ) {
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
			$chosen_shipping = $chosen_shipping_methods[0]; 
			
			if ( ! in_array( $chosen_shipping, $shipping_method_ids ) ) {
				return false;
			}
		}
		
		//============================
		//Test restricted payment methods
		$payment_method_ids = $this->get_coupon_payment_method_ids( $coupon->id );
		if ( sizeof( $payment_method_ids ) > 0 ) {			
			$chosen_payment_method = isset( WC()->session->chosen_payment_method ) ? WC()->session->chosen_payment_method : array();	
			
			if ( ! in_array( $chosen_payment_method, $payment_method_ids ) ) {
				return false;
			}
		}			


		//============================
		//Test restricted user ids and roles
		//NOTE: If both customer id and role restrictions are provided, the coupon matches if either the id or the role matches
		$coupon_customer_ids = $this->get_coupon_customer_ids( $coupon->id );
		$coupon_customer_roles = $this->get_coupon_customer_roles( $coupon->id );
		if ( sizeof( $coupon_customer_ids ) > 0 || sizeof( $coupon_customer_roles ) > 0 ) {		
			$user = wp_get_current_user();

			//If both fail we invalidate. Otherwise it's ok
			if ( ! in_array( $user->ID, $coupon_customer_ids ) && ! array_intersect( $user->roles, $coupon_customer_roles ) ) {
				return false;
			}
		}
		
		//============================
		//Test excluded user roles
		$coupon_excluded_customer_roles = $this->get_coupon_excluded_customer_roles( $coupon->id );
		if ( sizeof( $coupon_excluded_customer_roles ) > 0 ) {		
			$user = wp_get_current_user();

			//Excluded customer roles
			if ( array_intersect( $user->roles, $coupon_excluded_customer_roles ) ) {
				return false;
			}
		}
		
		return true;			
	}
	
	
	
	//Test if coupon is valid for the product 
	// (this function is used to count the quantity of matching products)
	private function coupon_is_valid_for_product( $coupon, $product, $values = array() ) {
		//===================================================================
		//This is almost a duplicate of function is_valid_for_product in WooCommerce class-wc-coupon.php 
		//The only differences are: 
		// - I removed the verification for fixed_product or percent_product 
		// - I replaced $this with $coupon
		//===================================================================
		
		//if ( ! $this->is_type( array( 'fixed_product', 'percent_product' ) ) ) {
		//	return apply_filters( 'woocommerce_coupon_is_valid_for_product', false, $product, $this, $values );
		//}

		$valid        = false;
		$product_cats = wp_get_post_terms( $product->id, 'product_cat', array( "fields" => "ids" ) );

		// Specific products get the discount
		if ( sizeof( $coupon->product_ids ) > 0 ) {
			if ( in_array( $product->id, $coupon->product_ids ) || ( isset( $product->variation_id ) && in_array( $product->variation_id, $coupon->product_ids ) ) || in_array( $product->get_parent(), $coupon->product_ids ) ) {
				$valid = true;
			}
		}

		// Category discounts
		if ( sizeof( $coupon->product_categories ) > 0 ) {
			if ( sizeof( array_intersect( $product_cats, $coupon->product_categories ) ) > 0 ) {
				$valid = true;
			}
		}

		if ( ! sizeof( $coupon->product_ids ) && ! sizeof( $coupon->product_categories ) ) {
			// No product ids - all items discounted
			$valid = true;
		}

		// Specific product ID's excluded from the discount
		if ( sizeof( $coupon->exclude_product_ids ) > 0 ) {
			if ( in_array( $product->id, $coupon->exclude_product_ids ) || ( isset( $product->variation_id ) && in_array( $product->variation_id, $coupon->exclude_product_ids ) ) || in_array( $product->get_parent(), $coupon->exclude_product_ids ) ) {
				$valid = false;
			}
		}

		// Specific categories excluded from the discount
		if ( sizeof( $coupon->exclude_product_categories ) > 0 ) {
			if ( sizeof( array_intersect( $product_cats, $coupon->exclude_product_categories ) ) > 0 ) {
				$valid = false;
			}
		}

		// Sale Items excluded from discount
		if ( $coupon->exclude_sale_items == 'yes' ) {
			$product_ids_on_sale = wc_get_product_ids_on_sale();

			if ( isset( $product->variation_id ) ) {
				if ( in_array( $product->variation_id, $product_ids_on_sale, true ) ) {
					$valid = false;
				}
			} elseif ( in_array( $product->id, $product_ids_on_sale, true ) ) {
				$valid = false;
			}
		}

		return apply_filters( 'woocommerce_coupon_is_valid_for_product', $valid, $product, $coupon, $values );
	}	


	
//

/**
 * Get array of the selected shipping methods ids.
 * @param  int $coupon_id The coupon id
 * @return array Id's of the shipping methods or an empty array.
 */	
	private function get_coupon_shipping_method_ids($coupon_id) {
		$v = get_post_meta( $coupon_id, '_wjecf_shipping_methods', true );
		if ($v == '') {
			$v = array();
		}
		
		return $v;
	}

/**
 * Get array of the selected payment method ids.
 * @param  int $coupon_id The coupon id
 * @return array  Id's of the payment methods or an empty array.
 */	
	private function get_coupon_payment_method_ids($coupon_id) {
		$v = get_post_meta( $coupon_id, '_wjecf_payment_methods', true );
		if ($v == '') {
			$v = array();
		}
		
		return $v;
	}
	
/**
 * Get array of the selected customer ids.
 * @param  int $coupon_id The coupon id
 * @return array  Id's of the customers (users) or an empty array.
 */	
	private function get_coupon_customer_ids($coupon_id) {
		$v = get_post_meta( $coupon_id, '_wjecf_customer_ids', true );
		//$v = array_map( 'intval', explode(",", get_post_meta( $coupon_id, '_wjecf_customer_ids', true ) ) );
		if ($v == '') {
			$v = array();
		} else {
			$v = array_map( 'intval', explode(",", $v ) );
		}
		
		return $v;
	}
	
/**
 * Get array of the selected customer role ids.
 * @param  int $coupon_id The coupon id
 * @return array  Id's (string) of the customer roles or an empty array.
 */	
	private function get_coupon_customer_roles($coupon_id) {
		$v = get_post_meta( $coupon_id, '_wjecf_customer_roles', true );
		if ($v == '') {
			$v = array();
		}
		
		return $v;
	}	

/**
 * Get array of the excluded customer role ids.
 * @param  int $coupon_id The coupon id
 * @return array  Id's (string) of the excluded customer roles or an empty array.
 */	
	private function get_coupon_excluded_customer_roles($coupon_id) {
		$v = get_post_meta( $coupon_id, '_wjecf_excluded_customer_roles', true );
		if ($v == '') {
			$v = array();
		}
		
		return $v;
	}	
	
	public static function get_donate_url() {
		return "https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=5T9XQBCS2QHRY&lc=NL&item_name=Jos%20Koenis&item_number=wordpress%2dplugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted";
	}
	
}
