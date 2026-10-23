<?php
/**
 * Plugin Name: 303 Coffee Storefront
 * Description: Lets the 303 Coffee landing page read public price and stock from the WooCommerce Store API.
 * Version: 1.2.0
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
		'https://303coffee.in',
		'https://www.303coffee.in',
	);

	$local = array(
		'http://localhost:8080',
		'http://127.0.0.1:8080',
		'http://localhost:5500',
		'http://127.0.0.1:5500',
	);

	return array_values( array_unique( array_merge( $origins, $storefronts, $local ) ) );
}

/**
 * Buy now from the landing page should check out this product on its own.
 * The button sends buy_now=1 with add-to-cart and quantity.
 *
 * @param bool $passed    Whether WooCommerce will add the product.
 * @return bool
 */
function coffee_303_buy_now_replaces_cart( $passed ) {
	if ( $passed && isset( $_REQUEST['buy_now'] ) && function_exists( 'WC' ) && WC()->cart ) {
		WC()->cart->empty_cart();
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'coffee_303_buy_now_replaces_cart', 20 );

/**
 * Send Buy now straight to checkout, including when the shop is set to return to the cart.
 *
 * @param string|false $url Redirect URL WooCommerce already chose.
 * @return string|false
 */
function coffee_303_buy_now_checkout( $url ) {
	if ( isset( $_REQUEST['buy_now'] ) && function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'coffee_303_buy_now_checkout' );
