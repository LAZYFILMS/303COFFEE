# 303 Coffee

One product page. The title, description, and photograph are read from the WooCommerce product when the page opens, using product id **10** on this shop:

`https://darksalmon-wallaby-671725.hostingersite.com`

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

## Connect a domain you own

Do this at the registrar where you bought the domain (or wherever its DNS is managed). Add the domain in GitHub **before** changing DNS.

1. In the repository, open **Settings → Pages**.
2. Under **Custom domain**, enter the domain (`example.com` or `www.example.com`) and save. GitHub writes a `CNAME` file for you.
3. At your DNS host, point the domain at GitHub Pages.

For a subdomain such as `www.example.com` or `shop.example.com`, add a `CNAME` record:

| Name | Type | Value |
| --- | --- | --- |
| `www` | `CNAME` | `lazyfilms.github.io` |

For the apex domain (`example.com` itself), add these `A` records on `@`, and these `AAAA` records if your host supports IPv6:

| Type | Value |
| --- | --- |
| `A` | `185.199.108.153` |
| `A` | `185.199.109.153` |
| `A` | `185.199.110.153` |
| `A` | `185.199.111.153` |
| `AAAA` | `2606:50c0:8000::153` |
| `AAAA` | `2606:50c0:8001::153` |
| `AAAA` | `2606:50c0:8002::153` |
| `AAAA` | `2606:50c0:8003::153` |

If your DNS host offers an `ALIAS` or `ANAME` record, you can point `@` at `lazyfilms.github.io` instead of the `A` records. Do not add a wildcard such as `*.example.com`.

4. Wait for DNS to update. In **Settings → Pages**, turn on **Enforce HTTPS** once GitHub offers it.
5. Open `wordpress/303coffee-storefront.php` and add both `https://example.com` and `https://www.example.com` to the `$storefronts` list. Upload that file again if the plugin is already installed.

GitHub's own write-up of these records: [Managing a custom domain for your GitHub Pages site](https://docs.github.com/en/pages/configuring-a-custom-domain-for-your-github-pages-site/managing-a-custom-domain-for-your-github-pages-site).

## Let the page read price and stock

Title, description, and the image load with no extra setup. WooCommerce hides price and stock from other websites until you name them.

1. In Hostinger file manager, upload `wordpress/303coffee-storefront.php` to `wp-content/plugins/`.
2. In WP Admin, open **Plugins** and activate **303 Coffee Storefront**.
3. Put your live domain in the `$storefronts` list in that file, then upload it again.

On the product, set a regular price and leave it in stock. The landing page then shows the price and its button adds the product to the WooCommerce cart.

## Turn the shop on

The public shop is still on Hostinger's launching page ("Something big is brewing"). The product data is available, which is why this page can show it, but cart and checkout stay behind that screen until you turn the launching page off in Hostinger. After that, **Add to cart** on this page lands in the WooCommerce cart.

## Use a different product

In `index.html`, change `productId` inside `STORE`. Use the same origin as the shop. The `preconnect` link in the page head should use that origin too.
