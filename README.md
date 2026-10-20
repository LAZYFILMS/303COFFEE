# 303 Coffee

One landing page. One WooCommerce product.

The page reads product **10** (“Yash winning coffee”) from the shop at
`https://darksalmon-wallaby-671725.hostingersite.com`. The title, description,
and image come from that product. Change the id on the `<body>` tag in
`index.html` (`data-product-id`) to point at a different published product.
A `?product=10` query on the page does the same thing for a single visit.

Open `index.html` in a browser, or publish the repository with GitHub Pages.

## Publish on GitHub

1. Merge this branch into `main`.
2. On GitHub, open the [303COFFEE](https://github.com/LAZYFILMS/303COFFEE) repository.
3. Go to **Settings → Pages**.
4. Under **Build and deployment**, set the source to **Deploy from a branch**.
5. Choose branch `main` and folder `/` (root), then save.

The site will be at `https://lazyfilms.github.io/303COFFEE/`.

## Connect a domain you own

Do this at the place where the domain is registered (GoDaddy, Namecheap, Cloudflare, Google Domains, Hostinger, and so on). GitHub hosts the page. The domain only needs DNS records that point at GitHub.

1. Turn on GitHub Pages first, using the steps above.
2. In **Settings → Pages**, under **Custom domain**, enter the domain (`example.com` or `www.example.com`) and save. GitHub adds a `CNAME` file to the repository. Leave that file in place.
3. Add the DNS records below. Remove any old “parking” or website records for the same name so they do not conflict.
4. Wait for DNS to update, then turn on **Enforce HTTPS** in the Pages settings.

For the apex domain (`example.com`), add four **A** records, all named `@`:

| Type | Name | Value |
| --- | --- | --- |
| A | `@` | `185.199.108.153` |
| A | `@` | `185.199.109.153` |
| A | `@` | `185.199.110.153` |
| A | `@` | `185.199.111.153` |

Also add four **AAAA** records on `@` if the registrar supports IPv6:

- `2606:50c0:8000::153`
- `2606:50c0:8001::153`
- `2606:50c0:8002::153`
- `2606:50c0:8003::153`

For `www.example.com`, add one **CNAME** record:

| Type | Name | Value |
| --- | --- | --- |
| CNAME | `www` | `lazyfilms.github.io` |

Point the CNAME at `lazyfilms.github.io`, not at the repository name and not at a `*.pages.github.io` address. Use both the apex records and the `www` record. In Pages, enter the address you want visitors to see. GitHub redirects the other one.

If the domain is only a subdomain such as `shop.example.com`, skip the A records. One CNAME from `shop` to `lazyfilms.github.io` is enough, and the custom domain in Pages should be `shop.example.com`.

## Price and the buy button

Title, description, and image load from the public WordPress product route, which already allows this page.

Price and stock come from the WooCommerce Store API. That API answers other websites only after the shop trusts them. Upload `wordpress/allow-landing-origin.php` to `wp-content/mu-plugins/` on the WooCommerce site (create the `mu-plugins` folder if needed). Before you do, uncomment the line for the real domain and add `https://` in front of it.

The button links to the product in the shop. The shop is still on its “launching soon” screen, so the product page there will not show until that mode is turned off in WooCommerce or Hostinger.

There is no description or price on the product yet. Add those in **Products** in the shop admin and reload this page.
