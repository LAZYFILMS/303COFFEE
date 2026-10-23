<?php
/**
 * Plugin Name: 303 Coffee Homepage
 * Description: Shows the 303 Coffee product page at the front of this site. Checkout and cart stay in WooCommerce.
 * Version: 1.3.0
 * Author: 303 Coffee
 *
 * Install: WP Admin → Plugins → Add New → Upload Plugin, choose
 * wordpress/303coffee-front.zip, then activate 303 Coffee Homepage.
 * If the old blog is still visible, purge the Hostinger cache.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paths this plugin serves, relative to the site root.
 *
 * @return array<string, string>
 */
function coffee_303_front_routes() {
	return array(
		''                       => 'index.html',
		'site.css'               => 'site.css',
		'refund-policy'          => 'refund-policy/index.html',
		'shipping-policy'        => 'shipping-policy/index.html',
		'terms-and-conditions'   => 'terms-and-conditions/index.html',
		'contact'                => 'contact/index.html',
	);
}

/**
 * Request path under the WordPress home URL, without a trailing slash.
 *
 * @return string
 */
function coffee_303_front_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path = parse_url( $uri, PHP_URL_PATH );
	$home = parse_url( home_url(), PHP_URL_PATH );
	if ( is_string( $path ) && is_string( $home ) && '/' !== $home && '' !== $home && 0 === strpos( $path, $home ) ) {
		$path = substr( $path, strlen( $home ) );
	}
	$path = trim( (string) $path, '/' );
	if ( 'index.php' === $path ) {
		return '';
	}
	return $path;
}

/**
 * Print one bundled page and stop WordPress from rendering the theme.
 */
function coffee_303_front_serve() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$routes = coffee_303_front_routes();
	$path   = coffee_303_front_path();
	if ( ! array_key_exists( $path, $routes ) ) {
		return;
	}

	$file = plugin_dir_path( __FILE__ ) . 'site/' . $routes[ $path ];
	$root = realpath( plugin_dir_path( __FILE__ ) . 'site' );
	$real = realpath( $file );
	if ( false === $root || false === $real || 0 !== strpos( $real, $root . DIRECTORY_SEPARATOR ) || ! is_readable( $real ) ) {
		return;
	}

	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404 = false;
	}
	status_header( 200 );
	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );

	if ( 'site.css' === $path ) {
		header( 'Content-Type: text/css; charset=UTF-8' );
		readfile( $real );
		exit;
	}

	$html   = file_get_contents( $real );
	$origin = untrailingslashit( home_url() );
	if ( is_string( $html ) ) {
		$html = str_replace( 'https://303coffee.in', $origin, $html );
		$html = coffee_303_front_apply_logo( $html );
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled static page.
	}
	exit;
}
add_action( 'template_redirect', 'coffee_303_front_serve', 0 );

/**
 * Logo for the top left. Prefer the uploaded file 303_Wordmark_Black.
 *
 * @return string
 */
function coffee_303_front_logo_url() {
	$names = array( '303_Wordmark_Black', '303coffee-logo' );
	foreach ( $names as $name ) {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'     => '_wp_attached_file',
						'value'   => $name,
						'compare' => 'LIKE',
					),
				),
			)
		);
		if ( empty( $ids ) ) {
			continue;
		}
		$url = wp_get_attachment_image_url( (int) $ids[0], 'medium_large' );
		if ( ! $url ) {
			$url = wp_get_attachment_image_url( (int) $ids[0], 'full' );
		}
		if ( $url ) {
			return $url;
		}
	}

	$custom = (int) get_theme_mod( 'custom_logo' );
	if ( $custom ) {
		$url = wp_get_attachment_image_url( $custom, 'medium_large' );
		if ( ! $url ) {
			$url = wp_get_attachment_image_url( $custom, 'full' );
		}
		if ( $url ) {
			return $url;
		}
	}
	return '';
}

/**
 * Put the uploaded logo in the header. Keep the wordmark until one exists.
 *
 * @param string $html Page HTML.
 * @return string
 */
function coffee_303_front_apply_logo( $html ) {
	$tag  = '<img class="logo" alt="303 Coffee" src="__COFFEE_LOGO__" hidden />';
	$logo = coffee_303_front_logo_url();
	if ( $logo ) {
		$img  = '<img class="logo" alt="303 Coffee" src="' . esc_url( $logo ) . '" />';
		$html = str_replace( $tag, $img, $html );
		$html = str_replace( 'class="wordmark mark"', 'class="wordmark mark" hidden', $html );
		return $html;
	}
	return str_replace( $tag, '', $html );
}

/**
 * Buy now should land on checkout with only this product in the cart.
 *
 * @param bool $passed Whether WooCommerce will add the product.
 * @return bool
 */
function coffee_303_front_buy_now_replaces_cart( $passed ) {
	if ( $passed && isset( $_REQUEST['buy_now'] ) && function_exists( 'WC' ) && WC()->cart ) {
		WC()->cart->empty_cart();
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'coffee_303_front_buy_now_replaces_cart', 20 );

/**
 * Keep Buy now on checkout when the shop is set to return to the cart.
 *
 * @param string|false $url Redirect URL WooCommerce already chose.
 * @return string|false
 */
function coffee_303_front_buy_now_checkout( $url ) {
	if ( isset( $_REQUEST['buy_now'] ) && function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'coffee_303_front_buy_now_checkout' );

/**
 * Drop the cached blog page so the product page can show.
 */
function coffee_303_front_purge() {
	do_action( 'litespeed_purge_all' );
	set_transient( 'coffee_303_front_notice', 1, DAY_IN_SECONDS );
}
register_activation_hook( __FILE__, 'coffee_303_front_purge' );

/**
 * Clear a cached missing stylesheet after this plugin is replaced.
 */
function coffee_303_front_maybe_purge() {
	if ( '1.3.0' === get_option( 'coffee_303_front_version' ) ) {
		return;
	}
	update_option( 'coffee_303_front_version', '1.3.0' );
	do_action( 'litespeed_purge_all' );
}
add_action( 'init', 'coffee_303_front_maybe_purge' );

/**
 * Tell the store owner to clear Hostinger's cache if the blog is still cached.
 */
function coffee_303_front_notice() {
	if ( ! get_transient( 'coffee_303_front_notice' ) ) {
		return;
	}
	delete_transient( 'coffee_303_front_notice' );
	echo '<div class="notice notice-success"><p>303 Coffee Homepage is on. The top-left logo is the media file 303_Wordmark_Black.png. If the old page is still visible, purge the cache in Hostinger hPanel.</p></div>';
}
add_action( 'admin_notices', 'coffee_303_front_notice' );
