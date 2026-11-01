<?php
/**
 * Plugin Name: 303 Coffee Homepage
 * Description: Shows the 303 Coffee product page at the front of this site. Checkout and cart stay in WooCommerce.
 * Version: 1.7.0
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
 * Page files in the GitHub repo. A push there updates the shop without
 * replacing files in the file manager. The copy inside this plugin is the fallback.
 *
 * @param string $relative Path inside the repository.
 * @return string
 */
function coffee_303_front_github_url( $relative ) {
	return 'https://raw.githubusercontent.com/LAZYFILMS/303COFFEE/cursor/influence-blend-c68a/' . ltrim( $relative, '/' );
}

/**
 * Latest page from GitHub, or the copy shipped with this plugin.
 *
 * @param string $relative Path inside the repository.
 * @param string $local    Absolute path to the bundled file.
 * @return string
 */
function coffee_303_front_body( $relative, $local ) {
	$key    = 'coffee_303_front_file_' . md5( $relative );
	$cached = get_transient( $key );
	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}

	$response = wp_remote_get(
		coffee_303_front_github_url( $relative ),
		array(
			'timeout' => 8,
		)
	);
	if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
		$body = wp_remote_retrieve_body( $response );
		if ( is_string( $body ) && '' !== $body ) {
			set_transient( $key, $body, MINUTE_IN_SECONDS );
			return $body;
		}
	}

	if ( is_readable( $local ) ) {
		$fallback = file_get_contents( $local );
		if ( is_string( $fallback ) ) {
			return $fallback;
		}
	}
	return '';
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

	$relative = $routes[ $path ];
	$local    = plugin_dir_path( __FILE__ ) . 'site/' . $relative;
	$body     = coffee_303_front_body( $relative, $local );
	if ( '' === $body ) {
		return;
	}

	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404 = false;
	}
	status_header( 200 );
	nocache_headers();
	coffee_303_front_do_not_cache();

	if ( 'site.css' === $path ) {
		header( 'Content-Type: text/css; charset=UTF-8' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled stylesheet.
		exit;
	}

	$origin = untrailingslashit( home_url() );
	$body   = str_replace( 'https://303coffee.in', $origin, $body );
	$body   = coffee_303_front_apply_logo( $body );
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled static page.
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
 * Grind chosen on the product page.
 *
 * @return string
 */
function coffee_303_front_requested_grind() {
	if ( empty( $_REQUEST['grind'] ) ) {
		return '';
	}
	$grind = sanitize_text_field( wp_unslash( $_REQUEST['grind'] ) );
	if ( ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9 \\-]{0,40}$/', $grind ) ) {
		return '';
	}
	return $grind;
}

/**
 * Keep the grind with this cart line.
 *
 * @param array $cart_item_data Cart line data.
 * @return array
 */
function coffee_303_front_requested_note() {
	$grind = coffee_303_front_requested_grind();
	if ( ! preg_match( '/^other$/i', $grind ) || empty( $_REQUEST['grind_note'] ) ) {
		return '';
	}
	$note = sanitize_textarea_field( wp_unslash( $_REQUEST['grind_note'] ) );
	$note = trim( preg_replace( '/[ \t]+/', ' ', $note ) );
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $note, 0, 400 );
	}
	return substr( $note, 0, 400 );
}

function coffee_303_front_add_grind( $cart_item_data ) {
	$grind = coffee_303_front_requested_grind();
	if ( $grind ) {
		$cart_item_data['coffee_303_grind'] = $grind;
	}
	$note = coffee_303_front_requested_note();
	if ( $note ) {
		$cart_item_data['coffee_303_grind_note'] = $note;
	}
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'coffee_303_front_add_grind' );

/**
 * Show the grind in the cart and at checkout.
 *
 * @param array $item_data Existing line details.
 * @param array $cart_item Cart line.
 * @return array
 */
function coffee_303_front_show_grind( $item_data, $cart_item ) {
	if ( ! empty( $cart_item['coffee_303_grind'] ) ) {
		$item_data[] = array(
			'key'   => 'Grind',
			'value' => $cart_item['coffee_303_grind'],
		);
	}
	if ( ! empty( $cart_item['coffee_303_grind_note'] ) ) {
		$item_data[] = array(
			'key'   => 'Notes',
			'value' => $cart_item['coffee_303_grind_note'],
		);
	}
	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'coffee_303_front_show_grind', 10, 2 );

/**
 * Save the grind on the order.
 *
 * @param WC_Order_Item_Product $item          Order line.
 * @param string                $cart_item_key Cart key.
 * @param array                 $values        Cart line values.
 */
function coffee_303_front_save_grind( $item, $cart_item_key, $values ) {
	if ( ! empty( $values['coffee_303_grind'] ) ) {
		$item->add_meta_data( 'Grind', $values['coffee_303_grind'], true );
	}
	if ( ! empty( $values['coffee_303_grind_note'] ) ) {
		$item->add_meta_data( 'Notes', $values['coffee_303_grind_note'], true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'coffee_303_front_save_grind', 10, 3 );

/**
 * Drop the cached blog page so the product page can show.
 */
function coffee_303_front_purge() {
	do_action( 'litespeed_purge_all' );
	set_transient( 'coffee_303_front_notice', 1, DAY_IN_SECONDS );
}
register_activation_hook( __FILE__, 'coffee_303_front_purge' );

/**
 * Keep LiteSpeed from storing these pages for days.
 * A stored copy is why the phone kept the wide canvas.
 */
function coffee_303_front_do_not_cache() {
	do_action( 'litespeed_control_set_nocache', '303 coffee front page' );
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );
}

/**
 * Drop a stored homepage or stylesheet once, including after this file is replaced.
 */
function coffee_303_front_maybe_purge() {
	if ( ! is_admin() ) {
		$routes = coffee_303_front_routes();
		$path   = coffee_303_front_path();
		if ( array_key_exists( $path, $routes ) ) {
			coffee_303_front_do_not_cache();
		}
	}
	if ( '1.7.0' === get_option( 'coffee_303_front_version' ) ) {
		return;
	}
	update_option( 'coffee_303_front_version', '1.7.0' );
	do_action( 'litespeed_purge_all' );
	header( 'X-LiteSpeed-Purge: *', false );
}
add_action( 'init', 'coffee_303_front_maybe_purge', 0 );

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
