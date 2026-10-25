<?php
/**
 * Plugin Name: 303 Coffee Homepage
 * Description: Shows the 303 Coffee product page at the front of this site. Checkout and cart stay in WooCommerce.
 * Version: 1.5.0
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
function coffee_303_front_add_grind( $cart_item_data ) {
	$grind = coffee_303_front_requested_grind();
	if ( $grind ) {
		$cart_item_data['coffee_303_grind'] = $grind;
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
}
add_action( 'woocommerce_checkout_create_order_line_item', 'coffee_303_front_save_grind', 10, 3 );

/**
 * Media item named influence, used as the product photo.
 *
 * @return int
 */
function coffee_303_front_influence_id() {
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'name'           => 'influence',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! $found ) {
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				's'              => 'influence',
			)
		);
	}
	return $found ? (int) $found[0] : 0;
}

/**
 * Short description stored on product 10.
 *
 * @return string
 */
function coffee_303_front_excerpt() {
	return '<p><strong>Origin:</strong> Coorg<br><strong>Variety:</strong> SLN 13, Excelsa<br><strong>Process:</strong> CM Naturals<br><strong>Altitude:</strong> 1000mASL</p>'
		. '<p>Plum, Apricots, Red Grapes, Sweet Resins</p>'
		. '<p>100g</p>'
		. '<p><em>Note: These coffees will be roasted on --- and will be shipped on ----</em></p>';
}

/**
 * Long description stored on product 10.
 *
 * @return string
 */
function coffee_303_front_content() {
	return '<h2>What is Brewers Cup?</h2>'
		. '<p>The Brewers Cup is a platform where coffee professionals come together to showcase the art and science of manual brewing. Competitors present a coffee of their choice, carefully crafting every variable — from grind size and water to pouring technique and extraction — to bring out its finest qualities.</p>'
		. '<p>But it\'s about more than just brewing a great cup. It\'s about understanding the coffee, communicating its story, and creating an experience that connects the judges to what\'s in the cup.</p>'
		. '<p>At the Mumbai Regionals, our approach, our coffee, and our story came together to earn us the win.</p>'
		. '<aside class="coffee-highlight"><h2>About the Coffee</h2>'
		. '<p>A blend of two remarkable coffees — Four Strain Yeast and Excelsa — crafted to bring together sweetness, bright acidity, body, and complexity.</p>'
		. '<p>Four strain yeast is a Selection 13, also known as Chandragiri. Fermented inside a tank using a mixed culture of four carefully selected yeast strains for sweetness and complexity. The cherries are fermented with these yeasts for 60 hours by a method called carbonic maceration, which influences the cup into becoming vivid, fruity, and aromatic.</p>'
		. '<p>And then Excelsa, known for its naturally low acidity and fuller body, brings a weight and depth the Arabica alone doesn\'t have. The coffee cherries were fermented for 60 hours as well by the same process of carbonic maceration.</p>'
		. '<p>Expect an expressive cup with notes of plum, apricot, red grapes, and sweet resins, complemented by a juicy, rounded mouthfeel and a lingering finish.</p>'
		. '<p>This is the coffee we brewed to win the National Brewers Cup Mumbai Regionals. Now, we\'re bringing that same experience to your cup.</p>'
		. '</aside>'
		. '<h2>About the Estate</h2>'
		. '<p>Nestled in Nakoor village in North Coorg, Karnataka, Mooleh Manay Estate is a family-run coffee farm with roots dating back to the early 1800s. Today, it is led by Akshay Dashrath and Komal Sable, who also co-founded South India Coffee Company (SICC), working at the intersection of coffee cultivation, processing, and research.</p>'
		. '<p>What makes the estate special is its commitment to exploring coffee beyond conventional boundaries, growing Arabica, Robusta, and the relatively uncommon Excelsa. Through experimental fermentation and a research-driven approach to processing, Mooleh Manay continues to explore new possibilities in Indian coffee.</p>'
		. '<p><a href="https://www.instagram.com/sicc.coffee/">Instagram</a><br><a href="https://www.sicc.coffee/">Website</a></p>';
}

/**
 * Write the Influence blend onto product 10 without opening the product editor.
 */
function coffee_303_front_sync_product() {
	$product_id = 10;
	$product    = get_post( $product_id );
	if ( ! $product || 'product' !== $product->post_type ) {
		return;
	}

	wp_update_post(
		array(
			'ID'           => $product_id,
			'post_title'   => 'The winning coffee',
			'post_excerpt' => coffee_303_front_excerpt(),
			'post_content' => coffee_303_front_content(),
		)
	);

	$image_id = coffee_303_front_influence_id();
	if ( $image_id ) {
		set_post_thumbnail( $product_id, $image_id );
		if ( ! get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) {
			update_post_meta( $image_id, '_wp_attachment_image_alt', 'Influence blend' );
		}
	}

	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $product_id );
	}
}

/**
 * Drop the cached blog page so the product page can show.
 */
function coffee_303_front_purge() {
	coffee_303_front_sync_product();
	do_action( 'litespeed_purge_all' );
	set_transient( 'coffee_303_front_notice', 1, DAY_IN_SECONDS );
}
register_activation_hook( __FILE__, 'coffee_303_front_purge' );

/**
 * Clear a cached missing stylesheet after this plugin is replaced.
 */
function coffee_303_front_maybe_purge() {
	if ( '1.5.0' === get_option( 'coffee_303_front_version' ) ) {
		return;
	}
	update_option( 'coffee_303_front_version', '1.5.0' );
	coffee_303_front_sync_product();
	do_action( 'litespeed_purge_all' );
}
add_action( 'init', 'coffee_303_front_maybe_purge' );

/**
 * Tell the store owner the product page was updated.
 */
function coffee_303_front_notice() {
	if ( ! get_transient( 'coffee_303_front_notice' ) ) {
		return;
	}
	delete_transient( 'coffee_303_front_notice' );
	echo '<div class="notice notice-success"><p>303 Coffee Homepage updated product 10 to The winning coffee and set the media file named influence as its photo. If the old page is still visible, purge the cache in Hostinger hPanel.</p></div>';
}
add_action( 'admin_notices', 'coffee_303_front_notice' );
