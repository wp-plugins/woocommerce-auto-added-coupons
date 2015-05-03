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
		
		//Title
		echo "<h3 style='display:inline'>" . __( 'Extended Coupon Features', 'woocommerce-jos-autocoupon' ) . "</h3>\n";
		printf( '<a href="%s" title="Support the development" target="_blank">', $this->get_donate_url() );
		_e('Donate to the developer', 'woocommerce-jos-autocoupon' );
		echo  "</a>\n";
		
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
		
		// Payment methods
		?>
		<p class="form-field"><label for="wjecf_payment_methods"><?php _e( 'Payment methods', 'woocommerce-jos-autocoupon' ); ?></label>
		<select id="wjecf_payment_methods" name="wjecf_payment_methods[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Any shipping method', 'woocommerce-jos-autocoupon' ); ?>">
			<?php
				$payment_method_ids = (array) get_post_meta( $thepostid, '_wjecf_payment_methods', true );
				$payment_methods = WC()->payment_gateways->get_available_payment_gateways();

				if ( $payment_methods ) foreach ( $payment_methods as $payment_method ) {
					echo '<option value="' . esc_attr( $payment_method->id ) . '"' . selected( in_array( $payment_method->id, $payment_method_ids ), true, false ) . '>' . esc_html( $payment_method->title ) . '</option>';
				}
			?>
		</select> <img class="help_tip" data-tip='<?php _e( 'One of these payment methods must be selected in order for this coupon to be valid.', 'woocommerce-jos-autocoupon' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
		<?php		
		
	}
	
	public function process_shop_coupon_meta( $post_id, $post ) {
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
