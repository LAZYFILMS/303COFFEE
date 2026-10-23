# 303 Coffee

One product page. The title, description, and photograph are read from the WooCommerce product when the page opens, using product id **10** on this shop:

`https://303coffee.in`

Edit that product in WordPress (the product whose address ends in `post=10`) and refresh this page. The headline, short description, long description, and every product photograph update from the shop. No redeploy is required for those changes.

The photographs sit in a gallery under the description and slide on their own when the product has more than one. Add photos in the product gallery in WooCommerce and they join that carousel.

**Buy now** opens the WooCommerce checkout with the quantity chosen on the page. Set a price on the product so WooCommerce will accept the purchase. With `wordpress/303coffee-storefront.php` active, Buy now also replaces whatever else was in the cart and still lands on checkout when the shop is set to return to the cart.

The footer links only to four pages on this site: refund policy, shipping policy, terms and conditions, and contact. They use the same design as the product page and do not open the WooCommerce store. Buy now still opens WooCommerce checkout.

## Publish the page

This folder is a static site. GitHub Pages can host it from this repository.

1. On GitHub, open **Settings → Pages**.
2. Under **Build and deployment**, set **Source** to **Deploy from a branch**.
3. Choose branch `main` and folder `/ (root)`, then save.
4. The page is published at `https://lazyfilms.github.io/303COFFEE/`.

To preview it on your own computer, from this folder run:

```bash
python3 -m http.server 8080
```

Then open `http://127.0.0.1:8080/`.

## Open the product page on 303coffee.in

The domain already uses Hostinger's nameservers, and `https://303coffee.in/` is the WordPress shop. WordPress is still using its default blog as the front page, which is why the domain shows "Hello world" instead of the product.

Upload `wordpress/303coffee-front.zip` in WP Admin → Plugins → Add New → Upload Plugin, then activate **303 Coffee Homepage**. The front of the site becomes this product page. Refund, shipping, terms, and contact stay in the same design. Checkout, cart, and the product in WooCommerce stay on `303coffee.in`.

If the blog is still there after activating, open Hostinger hPanel → Websites → 303coffee.in → Cache Manager and purge the cache. The old front page is stored there.

## Let the page read price and stock

Title, description, and the image load with no extra setup. WooCommerce hides price and stock from other websites until you name them.

1. In Hostinger file manager, upload `wordpress/303coffee-storefront.php` to `wp-content/plugins/`.
2. In WP Admin, open **Plugins** and activate **303 Coffee Storefront**.
3. Put your live domain in the `$storefronts` list in that file, then upload it again.

On the product, set a regular price and leave it in stock. The landing page then shows the price and its button adds the product to the WooCommerce cart.

## Turn the shop on

Checkout on `https://303coffee.in/checkout/` is the live WooCommerce checkout. The temporary Hostinger address can still show the launching page ("Something big is brewing"). Buy now on this product page uses `303coffee.in`.

## Use a different product

In `index.html`, change `productId` inside `STORE`. Use the same origin as the shop. The `preconnect` link in the page head should use that origin too.
