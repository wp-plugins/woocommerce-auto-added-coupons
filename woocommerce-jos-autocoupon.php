<?php
/**
 * Plugin Name: WooCommerce Extended Coupon Features
 * Plugin URI: http://wordpress.org/plugins/woocommerce-auto-added-coupons
 * Description: Additional functionality for WooCommerce Coupons: Apply certain coupons automatically, allow applying coupons via an url, etc...
 * Version: 2.1.0-b1
 * Author: Jos Koenis
 * License: GPL2
 */
 
 /*
 Change history:
  2.1.0-b1:
    - FEATURE: Allow auto coupons to be applied silently (without displaying a message)
	- FIX: Changed the hooks used for application/removal of auto coupons
	- FIX: 2.0.0 broke compatibility with PHP versions older than 5.3
  2.0.0:
    - RENAME: Renamed plugin from "WooCommerce auto added coupons" to "WooCommerce Extended Coupon Features"
    - FEATURE: Restrict coupons by payment method
    - FEATURE: Restrict coupons by shipping method	
	- FEATURE: Use AND-operator for the selected products (default is OR)
    - FIX: Validate email restrictions for auto coupons
	- Norwegian translation added (Thanks to Anders Zorensen)
  1.1.5:
    - FIX: Cart total discount amount showing wrong discount value in newer WooCommerce versions (tax)
    - Performance: get_all_auto_coupons select only where meta woocommerce_jos_autocoupon = yes
  1.1.4:
    - Translation support through .mo / .po files
	- Included translations: Dutch, German, Spanish (Thanks to stephan.sperling for the german translation)
  1.1.3.1:
    - FIX: Apply auto coupon if discount is 0.00 and free shipping is ticked	
  1.1.3:
    - Don't apply an auto coupon if the discount is 0.00
    - Allow applying multiple coupons via an url using *?apply_coupon=coupon_code1,coupon_code2
 1.1.2:
    - Minor change to make the plugin compatible with WooCommerce 2.3.1
	- Loop through coupons in ascending order
 1.1.1:
    - Tested with Wordpress 4.0
 1.1.0:
    - Allow applying coupon via an url using *?apply_coupon=coupon_code*
 1.0.1: 
	- Don't apply an autocoupon if the coupon is for individual_use and another coupon is already applied.
	
 
 */
 
defined('ABSPATH') or die();

require_once( 'includes/wjecf-autocoupon.php' );
require_once( 'includes/wjecf-coupon-extensions.php' );

/**
 * Create the plugin if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! function_exists( 'wjce_load_plugin_textdomain' ) ) {
		function wjce_load_plugin_textdomain() {
			load_plugin_textdomain('woocommerce-jos-autocoupon', false, basename(dirname(__FILE__)) . '/languages/' );
		}
		add_action('plugins_loaded', 'wjce_load_plugin_textdomain');
	}

	$wjce_extended_coupon_features = new WC_Jos_Extended_Coupon_Features_Controller();
	$wjce_autocoupon = new WC_Jos_AutoCoupon_Controller();
		
}

/**
 * Add donate-link to plugin page
 */
if ( ! function_exists( 'woocommerce_jos_autocoupon_plugin_meta' ) ) {
	function woocommerce_jos_autocoupon_plugin_meta( $links, $file ) {
		if ( strpos( $file, 'woocommerce-jos-autocoupon.php' ) !== false ) {
			$links = array_merge( $links, array( '<a href="' . WC_Jos_Extended_Coupon_Features_Controller::get_donate_url() . '" title="Support the development" target="_blank">Donate</a>' ) );
		}
		return $links;
	}
	add_filter( 'plugin_row_meta', 'woocommerce_jos_autocoupon_plugin_meta', 10, 2 );
}



// =========================================================================================================
// Some snippets that might be useful
// =========================================================================================================

/* // HINT: Use this snippet in your theme if you use coupons with restricted emails and AJAX enabled one-page-checkout.

//Update the cart preview when the billing email is changed by the customer
add_filter( 'woocommerce_checkout_fields', function( $checkout_fields ) {
	$checkout_fields['billing']['billing_email']['class'][] = 'update_totals_on_change';
	return $checkout_fields;	
} );
 
// */ 
 

/* // HINT: Use this snippet in your theme if you want to update cart preview after changing payment method.
//Even better: In your theme add class "update_totals_on_change" to the container that contains the payment method radio buttons.
//Do this by overriding woocommerce/templates/checkout/payment.php

//Update the cart preview when payment method is changed by the customer
add_action( 'woocommerce_review_order_after_submit' , function () {
	?><script type="text/javascript">
		jQuery(document).ready(function($){
			$(document.body).on('change', 'input[name="payment_method"]', function() {
				$('body').trigger('update_checkout');
				$.ajax( $fragment_refresh );
			});
		});
	</script><?php 
} );
// */
