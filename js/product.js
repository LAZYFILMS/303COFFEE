(function () {
  var root = document.body;
  var store = (root.dataset.store || "").replace(/\/$/, "");
  var params = new URLSearchParams(window.location.search);
  var productId = params.get("product") || root.dataset.productId || "";

  var page = document.getElementById("page");
  var photo = document.getElementById("photo");
  var title = document.getElementById("title");
  var price = document.getElementById("price");
  var description = document.getElementById("description");
  var buy = document.getElementById("buy");
  var note = document.getElementById("note");
  var status = document.getElementById("status");

  function fail(message) {
    page.classList.remove("is-loading");
    page.classList.add("is-error");
    page.setAttribute("aria-busy", "false");
    status.textContent = message;
  }

  function textFromHtml(html) {
    var doc = new DOMParser().parseFromString(html || "", "text/html");
    return (doc.body.textContent || "").replace(/\s+/g, " ").trim();
  }

  function sanitize(html) {
    var template = document.createElement("template");
    template.innerHTML = html || "";
    template.content
      .querySelectorAll("script, style, iframe, object, embed, link, meta, form")
      .forEach(function (el) {
        el.remove();
      });
    template.content.querySelectorAll("*").forEach(function (el) {
      Array.prototype.slice.call(el.attributes).forEach(function (attr) {
        var name = attr.name.toLowerCase();
        var value = attr.value.trim().toLowerCase();
        if (name.indexOf("on") === 0 || name === "style" || name === "srcdoc") {
          el.removeAttribute(attr.name);
          return;
        }
        if (
          (name === "href" || name === "src") &&
          (value.indexOf("javascript:") === 0 || value.indexOf("data:") === 0)
        ) {
          el.removeAttribute(attr.name);
        }
      });
      if (el.tagName === "A" && el.hasAttribute("href")) {
        el.setAttribute("rel", "noopener noreferrer");
      }
    });
    return template.innerHTML.trim();
  }

  function formatPrice(prices) {
    if (!prices || prices.price === undefined || prices.price === null || prices.price === "") {
      return "";
    }
    var minor = Number(prices.currency_minor_unit);
    var unit = Number.isFinite(minor) ? minor : 2;
    var amount = Number(prices.price) / Math.pow(10, unit);
    if (!Number.isFinite(amount)) return "";
    var code = prices.currency_code || "USD";
    try {
      return new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: code,
      }).format(amount);
    } catch (error) {
      return (prices.currency_prefix || "") + amount.toFixed(unit) + (prices.currency_suffix || "");
    }
  }

  function show(product) {
    document.title = product.name ? product.name + " — 303 Coffee" : "303 Coffee";
    title.textContent = product.name || "Product";

    if (product.image) {
      photo.src = product.image;
      photo.alt = product.imageAlt || product.name || "";
      photo.hidden = false;
    }

    if (product.amount) {
      price.hidden = false;
      price.querySelector(".amount").textContent = product.amount;
      var unit = price.querySelector(".unit");
      if (product.unit) {
        unit.hidden = false;
        unit.textContent = product.unit;
      }
    }

    var clean = sanitize(product.html);
    if (textFromHtml(clean)) {
      description.innerHTML = clean;
      description.hidden = false;
    }

    if (product.href) {
      buy.href = product.href;
      buy.hidden = false;
      buy.textContent = product.purchasable ? "Buy" : "View product";
    }

    if (product.note) {
      note.hidden = false;
      note.textContent = product.note;
    }

    status.textContent = "";
    page.classList.remove("is-loading");
    page.classList.add("is-ready");
    page.setAttribute("aria-busy", "false");
  }

  function readJson(url) {
    var joiner = url.indexOf("?") === -1 ? "?" : "&";
    return fetch(url + joiner + "_=" + Date.now(), {
      headers: { Accept: "application/json" },
    }).then(function (response) {
      if (!response.ok) throw new Error(String(response.status));
      return response.json();
    });
  }

  function load() {
    if (!store || !/^\d+$/.test(productId)) {
      fail("Set the shop address and a numeric product id on this page.");
      return;
    }

    var postUrl = store + "/wp-json/wp/v2/product/" + productId + "?_embed=1";
    var storeUrl = store + "/wp-json/wc/store/v1/products/" + productId;

    Promise.allSettled([readJson(postUrl), readJson(storeUrl)]).then(function (results) {
      var post = results[0].status === "fulfilled" ? results[0].value : null;
      var storeProduct = results[1].status === "fulfilled" ? results[1].value : null;

      if (!post && !storeProduct) {
        fail("This product could not be loaded. Check that the id is published in the shop.");
        return;
      }

      var media = post && post._embedded && post._embedded["wp:featuredmedia"]
        ? post._embedded["wp:featuredmedia"][0]
        : null;
      var storeImage = storeProduct && storeProduct.images ? storeProduct.images[0] : null;
      var html =
        (storeProduct && (storeProduct.description || storeProduct.short_description)) ||
        (post && post.content && post.content.rendered) ||
        (post && post.excerpt && post.excerpt.rendered) ||
        "";

      show({
        name: (storeProduct && storeProduct.name) || textFromHtml(post && post.title ? post.title.rendered : ""),
        html: html,
        image: (storeImage && storeImage.src) || (media && media.source_url) || "",
        imageAlt: (storeImage && storeImage.alt) || (media && media.alt_text) || "",
        amount: formatPrice(storeProduct && storeProduct.prices),
        unit: storeProduct && storeProduct.stock_availability ? storeProduct.stock_availability.text : "",
        href: (storeProduct && storeProduct.permalink) || (post && post.link) || "",
        note: "",
        purchasable: Boolean(storeProduct && storeProduct.is_purchasable),
      });
    });
  }

  load();
})();
