# Woo Category Products

A lightweight WordPress plugin that displays published WooCommerce products from one product category in a responsive, Tailwind CSS-friendly listing.

## Install

1. Copy `woo-category-products.php` into a folder named `woo-category-products` inside `wp-content/plugins/` (or zip that folder and upload it in **Plugins → Add New → Upload Plugin**).
2. Activate **Woo Category Products**. WooCommerce must also be active.
3. Make sure Tailwind CSS is already available on the site, as the shortcode output uses Tailwind utility classes (including important utilities).

## Shortcode

Use the product category slug, not its display name:

```text
[woo_category_products category="gmail-accounts-new"]
```

The `slug` alias is also supported:

```text
[woo_category_products slug="gmail-accounts-new"]
```

The category heading links to that category's archive. Its description is shown under the heading when one is set. Each published, visible product in the category is shown with its image (or a fallback icon), non-linked name, unlimited stock label, a stable pseudo-random sold count, starting price, and a Buy link. The Buy link points to the product page with `qc_cart_product_id=<product-id>` in its query string.

## Color settings

Go to **Settings → Woo Category Products** to customize the category header background/text, product card background/text, button accent, and secondary text colors. The default Buy button accent is `#28b487`. The font is Plus Jakarta Sans; layout, typography, spacing, and responsive behavior use Tailwind classes.
