<?php
/**
 * Plugin Name: Allow 303 Coffee landing page
 * Description: Lets the public product page read price and stock from the WooCommerce Store API.
 *
 * Upload this file to wp-content/mu-plugins/allow-landing-origin.php
 * on the WooCommerce site. Create the mu-plugins folder if it is missing.
 *
 * WooCommerce only sends product price data to origins it already trusts.
 * Add each public address of the landing page below.
 */

add_filter(
	'allowed_http_origins',
	function ( $origins ) {
		$extra = array(
			'https://lazyfilms.github.io',
			// 'https://yourdomain.com',
			// 'https://www.yourdomain.com',
		);

		return array_values( array_unique( array_merge( $origins, $extra ) ) );
	}
);
