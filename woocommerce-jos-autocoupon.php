<?php
/**
 * Plugin Name: WooCommerce Extended Coupon Features
 * Plugin URI: http://wordpress.org/plugins/woocommerce-auto-added-coupons
 * Description: Additional functionality for WooCommerce Coupons: Apply certain coupons automatically, allow applying coupons via an url, etc...
 * Version: 2.1.0-b5
 * Author: Jos Koenis
 * License: GPL2
 */
 
// Change history: see readme.txt

 
defined('ABSPATH') or die();

require_once( 'includes/wjecf-autocoupon.php' );
require_once( 'includes/wjecf-coupon-extensions.php' );

/**
 * Create the plugin if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! function_exists( 'wjecf_load_plugin_textdomain' ) ) {
		function wjecf_load_plugin_textdomain() {
			load_plugin_textdomain('woocommerce-jos-autocoupon', false, basename(dirname(__FILE__)) . '/languages/' );
		}
		add_action('plugins_loaded', 'wjecf_load_plugin_textdomain');
	}

	$wjecf_extended_coupon_features = new WC_Jos_Extended_Coupon_Features_Controller();
	$wjecf_autocoupon = new WC_Jos_AutoCoupon_Controller();
		
}

/**
 * Add donate-link to plugin page
 */
if ( ! function_exists( 'wjecf_plugin_meta' ) ) {
	function wjecf_plugin_meta( $links, $file ) {
		if ( strpos( $file, 'woocommerce-jos-autocoupon.php' ) !== false ) {
			$links = array_merge( $links, array( '<a href="' . WC_Jos_Extended_Coupon_Features_Controller::get_donate_url() . '" title="Support the development" target="_blank">Donate</a>' ) );
		}
		return $links;
	}
	add_filter( 'plugin_row_meta', 'wjecf_plugin_meta', 10, 2 );
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
				//$.ajax( $fragment_refresh );
			});
		});
	</script><?php 
} );
// */
