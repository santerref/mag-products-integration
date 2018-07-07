=== Mag products integration for WordPress ===
Contributors: santerref
Tags: magento, product, listing, wordpress, rest, api, e-commerce, webshop, shortcode, integration, post, posts, admin, page, commerce, products, free
Requires at least: 4.6
Tested up to: 4.9
Stable tag: 1.2.12
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Magento products integration for WordPress use the Magento REST API to list products on your WordPress.

== Description ==

This plugin use the Magento REST API to list products on your page or blog post.

Use the configuration page to link your Magento store to your WordPress and the shortcode to display the products.

The plugin works out of the box, but I also provide a free Magento extension to give you more functionalities. Find more details on the [plugin's website page](http://magentowp.santerref.com "Magento products integration for WordPress").

= Plugin features =

* Show product title, short description, price and buy now button
* Cache to reduce page load time
* Shortcode to list products on your page or blog post

= Magento extension features =

* Reduced page load time: only 1 request to fetch all data
* Thumbnails generation (by default images are natural size and resized using img width/height attributes)

= Actions and filters =

For developers: [actions and filters documentation](http://magentowp.santerref.com/documentation.html "Actions and filters documentation").

= Coming soon =

* Show only one product in your posts or your pages with a shortcode (1.3.0)
* OAuth authentication (1.3.0)
* Possibility to set custom thumbnail for products without images (1.3.0)
* Magento 2 compatibility (2.0.0)
* PHPUnit tests on [github](https://github.com/santerref/magento-products-integration "Github Repository")

== Installation ==

1. Extract `magentowp.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create REST API user and role in your magento store (see our [documentation](http://magentowp.santerref.com/documentation.html "Magento documentation"))
1. Configure the plugin through the 'Magento' menu in WordPress
1. Place `[magento]` shortcode in one of your page or blog posts

== Screenshots ==

1. Products listing
2. Plugin's configuration page
3. Page shortcode example
4. Customizer settings

== Changelog ==

= 1.2.12 =
* Fix responsive breakpoints (0-575px 1 column, 576-767px 2 columns, > 768px 3 columns)
* NEW Action hook mag_products_integration_before_product to show content before each product
* NEW Action hook mag_products_integration_after_product to show content after each product
* NEW Filter hook mag_products_integration_product to add additional product infos

= 1.2.11 =
* CAUTION The store attribute of the magento shortcode is now mandatory.
* NEW Magento extension has been updated to 1.0.3 and is now on [github](https://github.com/santerref/beeweb-wordpressproducts "Github Repository")
* Fix wrong products links like /product/view/id/82797/...
* Remove translations from the plugin and move them to [Translating WordPress](https://translate.wordpress.org/ "Translating WordPress")
* DEVELOPER Clean up PHP code to respect WordPress Coding Standards

= 1.2.10 =
* Fix wrong store parameters that prevents store filter to works
* NEW Shortcode attribute "description" to control the product description length (can be numeric, true or false)

= 1.2.9 =
* PLEASE NOTE If you are using the Magento module, you must update it to 1.0.2 !
* NEW Add new setting to disable customizer colors
* Fix wrong product links with multiple stores
* Fix PHP fatal error if wp_remote_get() returns WP_Error instance
* DEVELOPER Refactoring PHP code for better readability

= 1.2.8 =
* Improve errors handling when the REST API URL is not valid
* Code optimization
* Fix CSS flexbox Safari issue
* Fix image_width and image_height attributes that were missing on the <img/> tag
* Remove inline style attribute on the <img/> tag
* Add a minified version of style.css
* Add a minified version of the three JS scripts
* PLEASE NOTE There are no media queries by default (breakpoints for mobile devices)
* DEVELOPER Updated version on [github](https://github.com/santerref/magento-products-integration "Github Repository")

= 1.2.7 =
* Fix PHP static function warning on debug mode
* Remove JQuery script and replace with CSS flexbox
* NEW Add customizer settings to modify the colours without rewriting the CSS

= 1.2.6 =
* Fix missing link on "Buy it now" when using the Magento extension
* Strip all HTML tags on product name and product short_description by default

= 1.2.5 =
* Fix missing link on "Buy it now" button when "buy_now_url" is missing from REST API response.

= 1.2.4 =
* Fix Magento module requests when Magento is in a subdirectory.

= 1.2.3 =
* Fix cache to work with multiple shortcodes. Currently, the cache was only working with one shortcode which prevents users to show different categories of products on different pages.
* Test plugin with WordPress 4.5

= 1.2.2 =
* Fix missing product image (If you are using the Magento module, you must update it to 1.0.1)
* NEW Hide products image via shortcode (use hide_image="true", default is false)
* NEW Add flush cache button
* Update cache to use WordPress Transients API
* Replace CURL functions with WordPress HTTP API
* Update POT file and French translation

= 1.2.1 =
* Fix undismissable notice on other admin pages
* Update POT file and French translation

= 1.2.0 =
* NEW Cache for better performance (reduced page load time)
* NEW Possibility to disable the provided jQuery script
* Default CSS style improvements
* Clearer error messages and notices

= 1.1.1 =
* Fix missing product URL and buy it now button for those who are not using the Magento module
* Add french (fr_FR) translation
* Add PHPDoc on methods and properties
* Update POT file

= 1.1.0 =
* Add new hooks
* Add 13 new actions
* Add 7 new filters

= 1.0.0 =
First stable version.
