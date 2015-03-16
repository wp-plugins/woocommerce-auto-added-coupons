=== Plugin Name ===
Contributors: josk79
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=5T9XQBCS2QHRY&lc=NL&item_name=Jos%20Koenis&item_number=wordpress%2dplugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: woocommerce, coupons, discount
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 1.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow applying coupons through an url.
Allow discounts to be automatically added to the WooCommerce cart when it's restrictions are met.

== Description ==

"WooCommerce auto added coupons" allows you to select coupons that will automatically be added to
the users cart if it's restrictions are met. The coupon will be removed when the restrictions are no longer met.

The discount will be presented to the user by a descriptive text. No coupon code will be shown.

No programming required.

Since version 1.1.0 it's also possible to apply coupons to the cart via an url.

**Example**: Let the customer have a discount of $ 5.00 when the cart reaches $ 50.00. 

1. Create a coupon, let's name it *auto_50bucks* and enter a short description e.g. *$ 50.00 order discount*
2. On the General tab: Select discount type *Cart discount*, and set the coupon amount to $ 5.00
3. On the Usage restrictions tab: Set minimum spend to $ 50.00 and check the *Auto coupon*-box

Voila! The discount will be applied when the customer reaches $ 50.00.

**Example**: Apply coupon through an url.

1. Use the url www.example.com/url-to-shop?apply_coupon=my_coupon

Voila! Any coupon can be applied this way.

This plugin has been tested with WordPress 3.9.2 and WooCommerce 2.1.11 and 2.1.12. Also in combination with WPML.

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

= Can I make a donation? =

Sure! [This](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=5T9XQBCS2QHRY&lc=NL&item_name=Jos%20Koenis&item_number=wordpress%2dplugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) is the link. Greatly appreciated!

== Screenshots ==

1. Simply use the WooCommerce Coupons menu to make a coupon an "auto coupon".

== Changelog ==
= 1.1.4 =
* Translation support through .mo / .po files
* Included translations: Dutch, German, Spanish

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

