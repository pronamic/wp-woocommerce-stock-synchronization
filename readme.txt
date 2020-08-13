=== WooCommerce Stock Synchronization ===
Contributors: pronamic, remcotolsma
Tags: woocommerce, stock, sync, synchronization
Requires at least: 3.8
Tested up to: 5.5
Stable tag: 2.5.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Synchronizes stock with sites that are connected to one another, using WooCommerce Stock Synchronization.


== Description ==


== Installation ==


== F.A.Q. ==


== Screenshots ==


== Changelog ==

= 2.5.0 - 2020-08-13 =
*	Require `manage_woocommerce` capability (instead of `manage_options`).
*	Improved stock quantity display.

= 2.4.0 - 2018-10-29 =
*	Improved support for newer WooCommerce versions.
*	Update product stock quantity independent of post status.

= 2.3.0 - 2018-06-06 =
*	Fix - Fixed syncing zero stock through quick edit.
*	Fix - Fixed syncing issue with numbers only SKUs.
*	Fix - Fixed limited products notice on Stock tab not displayed.
*	Fix - Fixed ability to use single or double quotes in passwords.
*	Tweak - Added WooCommerce Multilingual support.
*	Tweak - Added 'lang' parameter to query all Polylang languages.
*	Tweak - Added WooCommerce version check headers.
*	Test - Tested up to WooCommerce version 3.4.2.
*	Test - Tested up to WordPress version 4.9.6.

= 2.2.0 - 2016-12-08 =
*	Make `Push Stock` capable of syncing an unlimited number of products.

= 2.1.0 - 2016-06-22 =
*	Fixed incorrect check for undefined property process_sync.
*	Fixed PHP notices undefined variables 'alternate' and 'stock'.
*	Added 'suppress_filters' for WPML compatibility.
*	Increased POST request timeout to 45 seconds.
*	Added note that displayed number of products is limited to 100.

= 2.0.3 - 2014-11-24 =
*	Fix - Fixed an issue with the sync all stock function.

= 2.0.2 - 2014-11-24 =
*	Tweak - No longer send full site URL, instead only sent the hostname of the site URL.

= 2.0.1 - 2014-10-06 =
*	Fix - Make sure we URL encode some parameters in the synchronize URL's.

= 2.0.0 - 2014-09-24 =
*	Tweak - Refactored all code.
*	Test - Tested up to WordPress version 4.0.
*	Test - Test up to WooCommerce version 2.1.12.
*	Feature - Added overview of all the synchronization websites with status and version.
*	Feature - Added overview of all the products with SKU and stock quantity.
*	Tweak - Improved the log overview.
*	Feature - Added an empty log button.
*	Tweak - Removed the synchronize stock meta box on edit product page. 

= 1.1.2 - 2014-01-07 =
*	Hotfix - Missing notes and incorrect version number.

= 1.1.1 - 2014-01-07 =
*	Hotfix - Fixed variable products not correctly syncing.

= 1.1.0 - 2014-01-07 =
*	Feature - Developer Debug Request. If you have the password from the user, you can request additional information by making a POST request to site.ext?stock_sync_debug=password

= 1.0.0 - 2013-10-07 =
*	Feature - Individual Synchronization Option
*	Improvement - Handling the synchronization better for very large requests.
*	Fix - No longer required to have URL's with/without slashes. Everything has added slashes now.

= 0.1 =
*	Initial release
