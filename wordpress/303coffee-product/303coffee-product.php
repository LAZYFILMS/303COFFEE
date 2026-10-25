<?php
/**
 * Plugin Name: 303 Coffee Product Update
 * Description: Writes the Influence blend into WooCommerce product 10: title, short description, description, and the media file named influence as the product image.
 * Version: 1.0.0
 * Author: 303 Coffee
 *
 * Install: WP Admin → Plugins → Add New → Upload Plugin, choose
 * wordpress/303coffee-product.zip, then activate 303 Coffee Product Update.
 * The product editor does not need to be opened or saved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product that receives the Influence blend.
 */
function coffee_303_product_id() {
	return 10;
}

/**
 * Version of the copy below. Reactivate the plugin to write it again.
 */
function coffee_303_product_version() {
	return '1.0.0';
}

/**
 * Attachment whose file or title is influence.
 *
 * @return int
 */
function coffee_303_product_image_id() {
	$found = get_posts(
		array(
			'post_type'              => 'attachment',
			'name'                   => 'influence',
			'post_status'            => 'inherit',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	if ( $found ) {
		return (int) $found[0];
	}

	global $wpdb;
	$like = '%' . $wpdb->esc_like( 'influence' ) . '%';
	$id   = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
			$like
		)
	);
	return $id ? (int) $id : 0;
}

/**
 * Product short description, as stored in the editor.
 *
 * @return string
 */
function coffee_303_product_excerpt() {
	return <<<'HTML'
<p><strong>Origin:</strong> Coorg<br><strong>Variety:</strong> SLN 13, Excelsa<br><strong>Process:</strong> CM Naturals<br><strong>Altitude:</strong> 1000mASL</p>
<p>Plum, Apricots, Red Grapes, Sweet Resins</p>
<p>100g</p>
<p><em>Note: These coffees will be roasted on --- and will be shipped on ----</em></p>
HTML;
}

/**
 * Product description, as stored in the editor.
 * The About the Coffee section is wrapped so the product page can draw a box around it.
 *
 * @return string
 */
function coffee_303_product_content() {
	return <<<'HTML'
<h2>What is Brewers Cup?</h2>
<p>The Brewers Cup is a platform where coffee professionals come together to showcase the art and science of manual brewing. Competitors present a coffee of their choice, carefully crafting every variable — from grind size and water to pouring technique and extraction — to bring out its finest qualities.</p>
<p>But it's about more than just brewing a great cup. It's about understanding the coffee, communicating its story, and creating an experience that connects the judges to what's in the cup.</p>
<p>At the Mumbai Regionals, our approach, our coffee, and our story came together to earn us the win.</p>
<div class="coffee-highlight">
<h2>About the Coffee</h2>
<p>A blend of two remarkable coffees — Four Strain Yeast and Excelsa — crafted to bring together sweetness, bright acidity, body, and complexity.</p>
<p>Four strain yeast is a Selection 13, also known as Chandragiri. Fermented inside a tank using a mixed culture of four carefully selected yeast strains for sweetness and complexity. The cherries are fermented with these yeasts for 60 hours by a method called carbonic maceration, which influences the cup into becoming vivid, fruity, and aromatic.</p>
<p>And then Excelsa, known for its naturally low acidity and fuller body, brings a weight and depth the Arabica alone doesn't have. The coffee cherries were fermented for 60 hours as well by the same process of carbonic maceration.</p>
<p>Expect an expressive cup with notes of plum, apricot, red grapes, and sweet resins, complemented by a juicy, rounded mouthfeel and a lingering finish.</p>
<p>This is the coffee we brewed to win the National Brewers Cup Mumbai Regionals. Now, we're bringing that same experience to your cup.</p>
</div>
<h2>About the Estate</h2>
<p>Nestled in Nakoor village in North Coorg, Karnataka, Mooleh Manay Estate is a family-run coffee farm with roots dating back to the early 1800s. Today, it is led by Akshay Dashrath and Komal Sable, who also co-founded South India Coffee Company (SICC), working at the intersection of coffee cultivation, processing, and research.</p>
<p>What makes the estate special is its commitment to exploring coffee beyond conventional boundaries, growing Arabica, Robusta, and the relatively uncommon Excelsa. Through experimental fermentation and a research-driven approach to processing, Mooleh Manay continues to explore new possibilities in Indian coffee.</p>
<p class="estate-links"><a href="https://www.instagram.com/sicc.coffee/">Instagram</a><br><a href="https://www.sicc.coffee/">Website</a></p>
HTML;
}

/**
 * Write the Influence blend onto product 10 through WooCommerce.
 */
function coffee_303_product_apply() {
	if ( coffee_303_product_version() === get_option( 'coffee_303_product_applied' ) ) {
		return;
	}
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$product = wc_get_product( coffee_303_product_id() );
	if ( ! $product ) {
		set_transient( 'coffee_303_product_notice', 'missing', DAY_IN_SECONDS );
		return;
	}

	$image_id = coffee_303_product_image_id();
	$product->set_name( 'The winning coffee — INFLUENCE BLEND' );
	$product->set_short_description( coffee_303_product_excerpt() );
	$product->set_description( coffee_303_product_content() );
	if ( $image_id ) {
		$product->set_image_id( $image_id );
		if ( ! get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) {
			update_post_meta( $image_id, '_wp_attachment_image_alt', 'Influence blend' );
		}
	}

	$saved = $product->save();
	if ( ! $saved ) {
		set_transient( 'coffee_303_product_notice', 'failed', DAY_IN_SECONDS );
		return;
	}

	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( coffee_303_product_id() );
	}

	update_option( 'coffee_303_product_applied', coffee_303_product_version() );
	set_transient( 'coffee_303_product_notice', $image_id ? 'ok' : 'no-image', DAY_IN_SECONDS );
}
add_action( 'woocommerce_init', 'coffee_303_product_apply' );

/**
 * Activating again writes the product once more on the next request.
 */
function coffee_303_product_activate() {
	delete_option( 'coffee_303_product_applied' );
}
register_activation_hook( __FILE__, 'coffee_303_product_activate' );

/**
 * Report what was written to product 10.
 */
function coffee_303_product_notice() {
	$notice = get_transient( 'coffee_303_product_notice' );
	if ( ! $notice ) {
		return;
	}
	delete_transient( 'coffee_303_product_notice' );

	if ( 'ok' === $notice ) {
		echo '<div class="notice notice-success"><p>Product 10 is updated in WooCommerce. The title is “The winning coffee — INFLUENCE BLEND”, the short description and description are the Influence blend, and the product image is the media file named influence. Open the product to see those fields. You do not need to press Update.</p></div>';
		return;
	}
	if ( 'no-image' === $notice ) {
		echo '<div class="notice notice-warning"><p>Product 10 title and descriptions were saved. No media file named influence was found, so the product image was left as it was.</p></div>';
		return;
	}
	if ( 'missing' === $notice ) {
		echo '<div class="notice notice-error"><p>WooCommerce product 10 was not found, so nothing was changed.</p></div>';
		return;
	}
	echo '<div class="notice notice-error"><p>Product 10 could not be saved.</p></div>';
}
add_action( 'admin_notices', 'coffee_303_product_notice' );
