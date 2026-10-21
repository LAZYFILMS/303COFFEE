<?php
/**
 * Plugin Name: 303 Coffee Storefront
 * Description: Lets the 303 Coffee landing page read public price and stock from the WooCommerce Store API.
 * Version: 1.0.0
 * Author: 303 Coffee
 *
 * WooCommerce only shares Store API data (price, stock, add to cart) with
 * sites listed in WordPress's allowed HTTP origins. Title, description, and
 * the product image already load without this plugin.
 *
 * Install: upload this file to wp-content/plugins/ and activate it
 * in WP Admin → Plugins. Then add your domain to the list below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'allowed_http_origins', 'coffee_303_storefront_origins' );

/**
 * Origins allowed to read the public Store API.
 *
 * @param string[] $origins Origins WordPress already allows.
 * @return string[]
 */
function coffee_303_storefront_origins( $origins ) {
	$storefronts = array(
		'https://lazyfilms.github.io',
		// Add each domain exactly as it appears in the browser bar.
		// Include https:// and leave off any trailing slash or path.
		// 'https://example.com',
		// 'https://www.example.com',
	);

	$local = array(
		'http://localhost:8080',
		'http://127.0.0.1:8080',
		'http://localhost:5500',
		'http://127.0.0.1:5500',
	);

	return array_values( array_unique( array_merge( $origins, $storefronts, $local ) ) );
}
