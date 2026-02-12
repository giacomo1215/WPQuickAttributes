![GitHub release](https://img.shields.io/github/v/release/giacomo1215/WPQuickAttributes)
![GitHub downloads](https://img.shields.io/github/downloads/giacomo1215/WPQuickAttributes/total)
![GitHub stars](https://img.shields.io/github/stars/giacomo1215/WPQuickAttributes)
![GitHub issues](https://img.shields.io/github/issues/giacomo1215/WPQuickAttributes)
![GitHub last commit](https://img.shields.io/github/last-commit/giacomo1215/WPQuickAttributes)
![License](https://img.shields.io/github/license/giacomo1215/WPQuickAttributes)

![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-7f54b3?logo=woocommerce&logoColor=white)
![Polylang](https://img.shields.io/badge/Polylang-Supported-2c9cff)
![WordPress](https://img.shields.io/badge/WordPress-Plugin-21759b?logo=wordpress&logoColor=white)

# WPQuickAttributes

WooCommerce attribute quick-finder plugin — displays product attribute terms as filterable links in a responsive column/card layout.

---

## How to Install

1. Copy the entire `wpquickattributes/` folder into `wp-content/plugins/`.
2. Go to **Plugins → Installed Plugins** and activate **WPQuickAttributes**.
3. WooCommerce must be active (the plugin shows a notice and disables itself otherwise).

---

## How to Configure Attributes

1. Navigate to **WooCommerce → WPQuickAttributes** in the admin sidebar.
2. **General**
   - Set an optional **Container Title** (e.g. "FIND THE RIGHT GEAR").
   - Choose the number of **Desktop Columns** (1–6).
3. **Attribute Columns**
   - For each of the 3 columns, select a WooCommerce product attribute taxonomy from the dropdown (e.g. `pa_color`, `pa_size`, `pa_what-for`).
   - Optionally set a custom **Heading** for each column; if left blank the attribute label is used.
4. **Link Target**
   - Choose **Shop page** (default) or a specific **Product category page** as the base URL that term links will point to.
5. **Display Options**
   - Toggle **Show Term Counts** to display `(n)` next to each term.
   - Toggle **Hide Empty Terms** to exclude terms with zero products.
   - Choose **Order Terms By**: Name, Menu Order, or Count.
6. **Term Label Overrides**
   - After selecting taxonomies and saving once, a list of all terms appears.
   - You can override the displayed label for any term while keeping the underlying slug for building filter URLs.
   - If Polylang is active, separate override fields appear per language.
7. Click **Save Changes**.

---

## How to Insert on a Page

### Shortcode

Add anywhere in a post, page, or widget:

```
[wpquickattributes]
```

### Gutenberg Block

1. Open the block editor on any page/post.
2. Click **+** (Add Block) and search for **WPQuickAttributes**.
3. Insert the block. It renders a live server-side preview.

---

## Polylang Compatibility

- **UI strings** are translation-ready via `__()` / `_e()` with the `wpquickattributes` text domain.
- **Term names** automatically display the Polylang-translated version when translations exist.
- **Per-language label overrides**: when Polylang is active, the admin settings page shows one override set per language. When Polylang is not active, a single "All Languages" set is shown.
- **URLs**: term links use the Polylang-translated shop/category page URL for the current language, via `pll_get_post()` / `pll_get_term()`.
- All Polylang function calls are guarded with `function_exists()` — the plugin works perfectly without Polylang installed.

---

## Filter URL Format

Each term link uses WooCommerce's standard layered-nav query vars:

```
https://example.com/shop/?filter_color=blue
https://example.com/shop/?filter_size=large
```

Where `filter_{attribute}` uses the sanitized attribute name (without the `pa_` prefix) and the value is the term slug.

---

## Caching

Term lists are cached using WordPress transients (1-hour TTL), keyed by taxonomy + language + settings hash. Caches are automatically flushed when:

- Plugin settings are saved.
- A `pa_*` taxonomy term is created, edited, or deleted.

---

## File Structure

```
wpquickattributes/
├── wpquickattributes.php        Main plugin bootstrap
├── includes/
│   ├── Admin.php                Settings page under WooCommerce menu
│   ├── Frontend.php             Renders the front-end columns HTML
│   ├── Shortcode.php            [wpquickattributes] shortcode
│   ├── Block.php                Gutenberg block (server-side render)
│   ├── Polylang.php             Polylang compatibility wrapper
│   └── Helpers.php              Shared utilities (settings, caching, URLs)
├── assets/
│   ├── css/
│   │   ├── frontend.css         Front-end layout styles
│   │   └── admin.css            Admin page styles
│   └── js/
│       └── block.js             Gutenberg block editor script
└── README.md                    This file
```

---

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 6.0+
- Polylang (optional, for multilingual support)
