<?php
/**
 * Plugin Name: 303 Coffee Pages
 * Description: Creates the refund policy, shipping policy, terms and conditions, and contact pages.
 * Version: 1.0.0
 * Author: 303 Coffee
 *
 * Upload this plugin in WP Admin → Plugins → Add New → Upload Plugin, then activate it.
 * Activation publishes the four pages. Leave the plugin active so the contact form can send mail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, 'coffee_303_pages_activate' );
add_action( 'admin_init', 'coffee_303_pages_ensure' );
add_shortcode( 'coffee_303_contact_form', 'coffee_303_pages_contact_form' );
add_action( 'admin_post_nopriv_coffee_303_contact', 'coffee_303_pages_handle_contact' );
add_action( 'admin_post_coffee_303_contact', 'coffee_303_pages_handle_contact' );
add_action( 'admin_notices', 'coffee_303_pages_admin_notice' );

/**
 * Publish any of the four pages that are missing.
 */
function coffee_303_pages_activate() {
	coffee_303_pages_ensure();
	set_transient( 'coffee_303_pages_notice', 1, MINUTE_IN_SECONDS );
	flush_rewrite_rules();
}

/**
 * Page definitions, keyed by slug.
 *
 * @return array<string, array{title: string, content: string}>
 */
function coffee_303_pages_definitions() {
	$updated = 'October 21, 2026';

	return array(
		'refund-policy'        => array(
			'title'   => 'Refund policy',
			'content' => coffee_303_pages_refund( $updated ),
		),
		'shipping-policy'      => array(
			'title'   => 'Shipping policy',
			'content' => coffee_303_pages_shipping( $updated ),
		),
		'terms-and-conditions' => array(
			'title'   => 'Terms and conditions',
			'content' => coffee_303_pages_terms( $updated ),
		),
		'contact'              => array(
			'title'   => 'Contact',
			'content' => coffee_303_pages_contact_content(),
		),
	);
}

/**
 * Create a missing page. Existing pages are left as they are.
 */
function coffee_303_pages_ensure() {
	if ( ! function_exists( 'get_page_by_path' ) ) {
		return;
	}

	foreach ( coffee_303_pages_definitions() as $slug => $page ) {
		$existing = get_page_by_path( $slug );
		if ( $existing instanceof WP_Post ) {
			continue;
		}

		wp_insert_post(
			array(
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $page['content'],
			)
		);
	}

	$terms = get_page_by_path( 'terms-and-conditions' );
	if ( $terms instanceof WP_Post && function_exists( 'WC' ) && ! get_option( 'woocommerce_terms_page_id' ) ) {
		update_option( 'woocommerce_terms_page_id', $terms->ID );
	}
}

/**
 * @param string $updated Display date.
 */
function coffee_303_pages_refund( $updated ) {
	return '<!-- 303-coffee-page -->
<p><em>Updated ' . esc_html( $updated ) . '</em></p>
<h2>No refunds</h2>
<p>All sales are final. 303 Coffee does not accept refunds.</p>
<ul>
<li>No refund is given after an order is placed. That includes a change of mind, ordering the wrong quantity, or deciding you no longer want the product.</li>
<li>No refund is given for the product price or for any shipping charge.</li>
<li>Returns, exchanges, and cancellations are not accepted.</li>
<li>Review the product, the quantity, and the total at checkout before you pay. Paying for the order means you accept this policy.</li>
</ul>';
}

/**
 * @param string $updated Display date.
 */
function coffee_303_pages_shipping( $updated ) {
	return '<!-- 303-coffee-page -->
<p><em>Updated ' . esc_html( $updated ) . '</em></p>
<p>303 Coffee ships the product to the delivery address entered at checkout.</p>
<ul>
<li>The shipping cost is calculated and shown on the checkout page before you pay.</li>
<li>You are responsible for a complete and accurate shipping address. An order sent to the address you enter is treated as delivered to you.</li>
<li>Orders are prepared for shipment after payment is received. A delivery date is not guaranteed. Delivery time depends on the destination and the carrier.</li>
<li>After an order is placed, it cannot be cancelled, changed, or redirected.</li>
<li>Risk of loss passes when the order is handed to the carrier. A delay, a carrier problem, customs, or an incorrect address does not create a refund.</li>
</ul>
<p>Questions about a shipment can be sent from the <a href="/contact/">contact page</a>. A message does not change this policy.</p>';
}

/**
 * @param string $updated Display date.
 */
function coffee_303_pages_terms( $updated ) {
	return '<!-- 303-coffee-page -->
<p><em>Updated ' . esc_html( $updated ) . '</em></p>
<p>These terms cover use of this store and every order placed here. Placing an order means you agree to these terms, the <a href="/refund-policy/">refund policy</a>, and the <a href="/shipping-policy/">shipping policy</a>.</p>
<ul>
<li>The product title, description, and photographs on the store are the description of what is sold.</li>
<li>The price, quantity, and shipping cost confirmed at checkout are the terms of that sale.</li>
<li>Payment is due in full at checkout. An order that cannot be fulfilled may be declined before it ships.</li>
<li>All sales are final. No refunds are accepted.</li>
<li>Shipping follows the shipping policy.</li>
<li>You are responsible for the name, shipping address, and payment details you submit.</li>
<li>The store may refuse or cancel an order that cannot be completed.</li>
<li>To the extent the law allows, the store is not liable for indirect or consequential losses from an order or from use of the site. Liability for an order is limited to the amount paid for that order.</li>
</ul>
<p>Questions about these terms can be sent from the <a href="/contact/">contact page</a>.</p>';
}

/**
 * Contact page body. The form is rendered by the shortcode while this plugin is active.
 */
function coffee_303_pages_contact_content() {
	return '<!-- 303-coffee-page -->
<p>Send a message about an order or this store. Messages go to the email address saved in WordPress under Settings → General.</p>
[coffee_303_contact_form]';
}

/**
 * Render the contact form.
 */
function coffee_303_pages_contact_form() {
	$status = isset( $_GET['coffee_303_sent'] ) ? sanitize_text_field( wp_unslash( $_GET['coffee_303_sent'] ) ) : '';
	$notice = '';
	if ( '1' === $status ) {
		$notice = '<p class="coffee-303-notice" role="status">Your message was sent.</p>';
	} elseif ( '0' === $status ) {
		$notice = '<p class="coffee-303-notice" role="alert">The message could not be sent. Check the name, email, and message, then try again.</p>';
	}

	ob_start();
	echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed strings only.
	echo '<style>
		.coffee-303-form { max-width: 36rem; display: grid; gap: 0.9rem; }
		.coffee-303-form p { margin: 0; display: grid; gap: 0.35rem; }
		.coffee-303-form input, .coffee-303-form textarea { width: 100%; font: inherit; padding: 0.65rem 0.75rem; }
		.coffee-303-form button { font: inherit; padding: 0.75rem 1.2rem; cursor: pointer; }
		.coffee-303-honey { position: absolute; left: -9999px; height: 0; overflow: hidden; }
		.coffee-303-notice { max-width: 36rem; }
	</style>';
	?>
	<form class="coffee-303-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="coffee_303_contact" />
		<?php wp_nonce_field( 'coffee_303_contact', 'coffee_303_contact_nonce' ); ?>
		<p class="coffee-303-honey">
			<label for="coffee-303-company">Company</label>
			<input id="coffee-303-company" name="company_website" type="text" tabindex="-1" autocomplete="off" />
		</p>
		<p>
			<label for="coffee-303-name">Name</label>
			<input id="coffee-303-name" name="sender_name" type="text" required maxlength="120" autocomplete="name" />
		</p>
		<p>
			<label for="coffee-303-email">Email</label>
			<input id="coffee-303-email" name="sender_email" type="email" required maxlength="160" autocomplete="email" />
		</p>
		<p>
			<label for="coffee-303-message">Message</label>
			<textarea id="coffee-303-message" name="sender_message" required maxlength="4000" rows="7"></textarea>
		</p>
		<p>
			<button type="submit">Send message</button>
		</p>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Mail the store administrator and return to the contact page.
 */
function coffee_303_pages_handle_contact() {
	$contact = get_page_by_path( 'contact' );
	$back    = $contact instanceof WP_Post ? get_permalink( $contact ) : home_url( '/' );

	$nonce = isset( $_POST['coffee_303_contact_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['coffee_303_contact_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'coffee_303_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'coffee_303_sent', '0', $back ) );
		exit;
	}

	$honeypot = isset( $_POST['company_website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['company_website'] ) ) ) : '';
	if ( '' !== $honeypot ) {
		wp_safe_redirect( add_query_arg( 'coffee_303_sent', '1', $back ) );
		exit;
	}

	$name    = isset( $_POST['sender_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sender_name'] ) ) : '';
	$email   = isset( $_POST['sender_email'] ) ? sanitize_email( wp_unslash( $_POST['sender_email'] ) ) : '';
	$message = isset( $_POST['sender_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sender_message'] ) ) : '';

	$sent = false;
	if ( '' !== $name && is_email( $email ) && '' !== $message ) {
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key     = 'coffee_303_contact_' . md5( $ip );
		$count   = (int) get_transient( $key );
		if ( $count < 5 ) {
			$body = "Name: {$name}\nEmail: {$email}\n\n{$message}\n";
			$sent = wp_mail(
				get_option( 'admin_email' ),
				'Message from the 303 Coffee contact page',
				$body,
				array( 'Reply-To: ' . $email )
			);
			set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		}
	}

	wp_safe_redirect( add_query_arg( 'coffee_303_sent', $sent ? '1' : '0', $back ) );
	exit;
}

/**
 * Tell the person who activated the plugin where the new pages are.
 */
function coffee_303_pages_admin_notice() {
	if ( ! get_transient( 'coffee_303_pages_notice' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	delete_transient( 'coffee_303_pages_notice' );

	$links = array();
	foreach ( coffee_303_pages_definitions() as $slug => $page ) {
		$existing = get_page_by_path( $slug );
		if ( ! $existing instanceof WP_Post ) {
			continue;
		}
		$links[] = '<a href="' . esc_url( get_permalink( $existing ) ) . '">' . esc_html( $page['title'] ) . '</a>';
	}
	if ( ! $links ) {
		return;
	}

	echo '<div class="notice notice-success"><p>303 Coffee pages are published: ' . wp_kses_post( implode( ', ', $links ) ) . '.</p></div>';
}
