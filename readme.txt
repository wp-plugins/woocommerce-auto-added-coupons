=== Plugin Name ===
Contributors: josk79
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=5T9XQBCS2QHRY&lc=NL&item_name=Jos%20Koenis&item_number=wordpress%2dplugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: woocommerce, coupons, discount
Requires at least: 4.0.0
Tested up to: 4.2.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Additional functionality for WooCommerce Coupons: Allow discounts to be automatically applied, applying coupons via url, etc...

== Description ==

"WooCommerce Extended Coupon Features" (formerly known as: WooCommerce auto added coupons) adds functionality to the WooCommerce coupons. 
Very easy to use, the functionality is conveniently integrated to the WooCommerce Edit Coupon panel.

* *Auto coupons*: Allow coupons to be automatically added to the users cart if it's restrictions are met,
* Apply coupon via an url,
* Restrict coupon by shipping method,
* Restrict coupon by payment method,
* Restrict coupon by a combination of products

= Example: Auto coupon =

Let the customer have a discount of $ 5.00 when the cart reaches $ 50.00. 

1. Create a coupon, let's name it *auto_50bucks* and enter a short description e.g. *$ 50.00 order discount*
2. On the General tab: Select discount type *Cart discount*, and set the coupon amount to $ 5.00
3. On the Usage restrictions tab: Set minimum spend to $ 50.00 and check the *Auto coupon*-box

Voila! The discount will be applied when the customer reaches $ 50.00 and a descriptive message will be shown.

If the restrictions are no longer met, it will silently be removed from the cart.

= Example: Apply coupon via an URL =

Apply coupon through an url like this:

1. Use the url www.example.com/url-to-shop?apply_coupon=my_coupon

Voila! Any coupon can be applied this way.


This plugin has been tested with WordPress 4.2.4 and WooCommerce 2.4.1. Also in combination with WPML and qTranslate-X.

== Installation ==

1. Upload the plugin in the `/wp-content/plugins/` directory, or automatically install it through the 'New Plugin' menu in WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress

= How to create an automatically added coupon? =

1. Create a coupon through the 'Coupons' menu in WooCommerce. TIP: Name it auto_'whatever' so it will be easy to recognize the auto coupons
2. Setup the coupon as you'd normally would. Make sure you enter a description for the coupon and set usage restrictions
3. In the "Usage Restriction" tab, check the box *Auto coupon*
4. Voila! That's it

== Frequently Asked Questions ==

= Is the plugin translatable? =

Yes, all frontend string values are translatable with WPML. Translatable items appear in the context `woocommerce-jos-autocoupon` in "String Translations".

= Why isn't my coupon applied using www.example.com?apply_coupon=my_coupon ? =

The coupon will only be applied if the url links to a WooCommerce page (e.g. product loop / cart / product detail ).

= The cart is not updated after changing the payment method =

In your theme add class "update_totals_on_change" to the container (div / p / whatever) that holds the payment method radio buttons.
You can do this by overriding woocommerce/templates/checkout/payment.php (place it in your_template/woocommerce/checkout/).

= The cart is not updated after changing the billing email address =

Paste this snippet in your theme's functions.php:
`
//Update the cart preview when the billing email is changed by the customer
add_filter( 'woocommerce_checkout_fields', function( $checkout_fields ) {
	$checkout_fields['billing']['billing_email']['class'][] = 'update_totals_on_change';
	return $checkout_fields;	
} );
`

= Can I make a donation? =

Sure! [This](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=5T9XQBCS2QHRY&lc=NL&item_name=Jos%20Koenis&item_number=wordpress%2dplugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) is the link. Greatly appreciated!

== Screenshots ==

1. Simply use the WooCommerce Coupons menu to make a coupon an "auto coupon".

== Changelog ==
= 2.1.0-b4 =
* FIX: Lowered execution priority for apply_coupon by url for combinations with add-to-cart.
* FEATURE: New coupon feature: Excluded customer role restriction
* FEATURE: New coupon feature: Customer / customer role restriction
* FEATURE: New coupon feature: Minimum quantity of matching products
* TWEAK: Moved all settings to the 'Extended features'-tab on the admin page.
* FEATURE: New coupon feature: Allow auto coupons to be applied silently (without displaying a message)
* FIX: 2.0.0 broke compatibility with PHP versions older than 5.3
* FIX: Changed method to fetch email addresses for auto coupon with email address restriction
* ADDED: Filter wjecf_coupon_has_a_value (An auto coupon will not be applied if this returns false)
* ADDED: Filter wjecf_coupon_can_be_applied (An auto coupon will not be applied if this returns false)

= 2.0.0 =
* RENAME: Renamed plugin from "WooCommerce auto added coupons" to "WooCommerce Extended Coupon Features"
* FEATURE: Restrict coupons by payment method
* FEATURE: Restrict coupons by shipping method	
* FEATURE: Use AND-operator for the selected products (default is OR)
* FIX: Validate email restrictions for auto coupons
* Norwegian translation added (Thanks to Anders Zorensen)

= 1.1.5 =
* FIX: Cart total discount amount showing wrong discount value in newer WooCommerce versions (tax)
* Performance: get_all_auto_coupons select only where meta woocommerce_jos_autocoupon = yes (Thanks to ircary)

= 1.1.4 =
* Translation support through .mo / .po files
* Included translations: Dutch, German, Spanish (Thanks to stephan.sperling for the german translation)

= 1.1.3.1 =
* FIX: Apply auto coupon if discount is 0.00 and free shipping is ticked	

= 1.1.3 =
* Don't apply coupon if the discount is 0.00
* Allow applying multiple coupons via an url using *?apply_coupon=coupon_code1,coupon_code2

= 1.1.2 =
* Minor change to make the plugin compatible with WooCommerce 2.3.1
* Loop through coupons in ascending order

= 1.1.1 =
* Tested with Wordpress 4.0

= 1.1.0 =
* Allow applying coupon via an url using *?apply_coupon=coupon_code*

= 1.0.1 =
* Don't add the coupon if *Individual use only* is checked and another coupon is already applied.

= 1.0 =
* First version ever!
== Upgrade Notice ==

= 2.0.0 =
New name, extended functionality! 
Additional features are added to the coupon: 
Restrict by shipping method, restrict by payment method, restrict by a combination of products.


