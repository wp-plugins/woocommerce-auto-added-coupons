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
		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_options' ), 10, 0 );
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ), 10, 2 );		
		
		//Frontend hooks
		add_filter('woocommerce_coupon_is_valid', array( &$this, 'coupon_is_valid' ), 10, 2 );		
	}
	
/* ADMIN HOOKS */

	public function coupon_options() {
		global $thepostid, $post;
		$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
		
		//See WooCommerce class-wc-meta-box-coupon-data.php function ouput
		
		//=============================
		//Title
		echo "<h3 style='display:inline'>" . esc_html( __( 'Extended Coupon Features', 'woocommerce-jos-autocoupon' ) ). "</h3>\n";
		printf( '<a href="%s" title="Support the development" target="_blank">', $this->get_donate_url() );
		echo esc_html( __('Donate to the developer', 'woocommerce-jos-autocoupon' ) );
		echo  "</a>\n";
		
		//=============================
		// AND in stead of OR the products
		woocommerce_wp_checkbox( array( 
			'id' => '_wjecf_products_and', 
			'label' => __( 'AND Products (not OR)', 'woocommerce-jos-autocoupon' ), 
			'description' => __( 'Check this box if ALL of the products (see above) must be in the cart to use this coupon (in stead of only one of the products).', 'woocommerce-jos-autocoupon' )
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
				$shipping_method_ids = (array) get_post_meta( $thepostid, '_wjecf_shipping_methods', true );
				$shipping_methods = WC()->shipping->load_shipping_methods();

				if ( $shipping_methods ) foreach ( $shipping_methods as $shipping_method ) {
					echo '<option value="' . esc_attr( $shipping_method->id ) . '"' . selected( in_array( $shipping_method->id, $shipping_method_ids ), true, false ) . '>' . esc_html( $shipping_method->method_title ) . '</option>';
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
				$payment_method_ids = (array) get_post_meta( $thepostid, '_wjecf_payment_methods', true );
				//DONT USE: CAN CRASH IN UNKNOWN OCCASIONS // $payment_methods = WC()->payment_gateways->available_payment_gateways();
				$payment_methods = WC()->payment_gateways->payment_gateways();
				if ( $payment_methods ) foreach ( $payment_methods as $payment_method ) {
					if ('yes' === $payment_method->enabled) {
						echo '<option value="' . esc_attr( $payment_method->id ) . '"' . selected( in_array( $payment_method->id, $payment_method_ids ), true, false ) . '>' . esc_html( $payment_method->title ) . '</option>';
					}
				}
			?>
		</select> <img class="help_tip" data-tip='<?php _e( 'One of these payment methods must be selected in order for this coupon to be valid.', 'woocommerce-jos-autocoupon' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
		<?php		
		
	}
	
	public function process_shop_coupon_meta( $post_id, $post ) {
		$wjecf_products_and = isset( $_POST['_wjecf_products_and'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wjecf_products_and', $wjecf_products_and );
		
		$wjecf_shipping_methods = isset( $_POST['wjecf_shipping_methods'] ) ? $_POST['wjecf_shipping_methods'] : '';
		update_post_meta( $post_id, '_wjecf_shipping_methods', $wjecf_shipping_methods );		
		
		$wjecf_payment_methods = isset( $_POST['wjecf_payment_methods'] ) ? $_POST['wjecf_payment_methods'] : '';
		update_post_meta( $post_id, '_wjecf_payment_methods', $wjecf_payment_methods );		
		
	}	

/* FRONTEND HOOKS */

/* Extra validation rules for coupons */
	function coupon_is_valid ( $valid, $coupon ) {
		//Not valid? Then it will never validate, so get out of here
		if ( ! $valid ) {
			return false;
		}
		
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
		
		
		//Test restricted shipping methods
		$shipping_method_ids = $this->get_shipping_method_ids( $coupon );
		if ( sizeof( $shipping_method_ids ) > 0 ) {
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
			$chosen_shipping = $chosen_shipping_methods[0]; 
			
			if ( ! in_array( $chosen_shipping, $shipping_method_ids ) ) {
				return false;
			}
		}
		
		//Test restricted payment methods
		$payment_method_ids = $this->get_payment_method_ids( $coupon );
		if ( sizeof( $payment_method_ids ) > 0 ) {			
				$chosen_payment_method = isset( WC()->session->chosen_payment_method ) ? WC()->session->chosen_payment_method : array();	
				
				if ( ! in_array( $chosen_payment_method, $payment_method_ids ) ) {
					return false;
				}
		}
		
		return true;			
	}	
	
//

/**
 * Get array of the selected shipping methods ids.
 * @param  WC_Coupon $coupon The coupon data
 * @return array Id's of the shipping methods or an empty array.
 */	
	private function get_shipping_method_ids($coupon) {
		$v = get_post_meta( $coupon->id, '_wjecf_shipping_methods', true );
		if ($v == '') {
			$v = array();
		}
		
		return $v;
	}

/**
 * Get array of the selected payment method ids.
 * @param  WC_Coupon $coupon The coupon data
 * @return array  Id's of the payment methods or an empty array.
 */	
	private function get_payment_method_ids($coupon) {
		$v = get_post_meta( $coupon->id, '_wjecf_payment_methods', true );
		if ($v == '') {
			$v = array();
		}
		
		return $v;
	}
	
	public static function get_donate_url() {
		return "https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=5T9XQBCS2QHRY&lc=NL&item_name=Jos%20Koenis&item_number=wordpress%2dplugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted";
	}
	
}
