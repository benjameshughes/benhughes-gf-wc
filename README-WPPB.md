# Gravity Forms → WooCommerce Cart

Adds Gravity Forms submissions to WooCommerce Cart with rich configuration, a custom Price Calculator field, and an optional feed-based integration. Built with modern PHP (8.2+), a DI container, repositories with caching, and an Alpine.js UI.

This README follows WordPress Plugin Boilerplate (WPPB) conventions and calls out alignment gaps with recommended additions.

## Features

- Price Calculator field (area × product price/m²) with sale comparison, breakdown text, and currency prefix/suffix.
- Measurement Unit field (mm / cm / in) with dynamic label updates and unit-aware constraints.
- Dual Submit UI: “Add to Basket” (AJAX, stays on page) and “Pay Now” (standard submit) with mini-cart updates.
- WooCommerce Cart integration from GF submissions (line-item meta, custom price setting, entry linkage).
- Feed-based Addon (GF Add-On Framework) to configure cart mapping without requiring the calculator field.
- REST API: calculate price and add to basket endpoints (public by default; see security notes).
- Admin UI: settings page, toolbar status, validation notices and configuration dashboard.
- Gravity Forms Theme Layer (ShuttersTheme) for a polished form UI.
- Caching (WP object cache) for products/forms; Logger (WP_DEBUG_LOG-aware).

## Requirements

- WordPress 5.0+
- PHP 8.2+
- Gravity Forms 2.5+ (for add-on feed and theme layer features)
- WooCommerce 7.0+

## Installation

1) Install dependencies (if developing locally):
- In `wp-content/plugins/benhughes-gf-wc/`, run `composer install`.

2) Activate plugin:
- WordPress Admin → Plugins → “Gravity Forms to WooCommerce Cart”.

3) Configure:
- Add a “Price Calculator” field to your form, link the width/drop fields, and select a WooCommerce product.
- Or, use the “WooCommerce Cart” Feed to map a product and (optionally) a price source (see Roadmap for provider decoupling).

## Quick Start

1) Create a form with numeric fields for Width and Drop (Height).
2) Add the “Price Calculator” field (Advanced fields), select the product and link Width/Drop/Unit.
3) Publish the form on a page.
4) Use “Add to Basket” (AJAX) to add the configured product to the Woo cart; see mini-cart update.

## Admin UI

- Settings → Forms → Cart Integration: system status, debug toggle, configured forms and validation results.
- Admin Toolbar: live status summary with quick links to problematic forms.
- Form Editor Enhancements: clean validation indicators, field settings for product mapping, display format, currency, and breakdown.

## Gravity Forms Fields

### Price Calculator (type: `wc_price_calculator`)
- Calculates price using area (width × drop) and the Woo product’s regular/sale price per m².
- Supports display as read-only text (with sale comparison and “You save %” badge) or as a hidden input.
- JS: Alpine.js component (`price-calculator.js`) updates display in real-time; server is the single source of truth via AJAX.

### Measurement Unit (type: `measurement_unit`)
- Radio field for mm/cm/in with enum-backed conversions.
- Optionally updates target number field labels to include current unit.

## Integration Modes

- On submission: `WooCommerceCart::add_to_cart()` maps entry data into a Woo cart item, sets custom price, and adds readable meta.
- AJAX Dual Submit: `gf_wc_add_to_basket` adds to cart without leaving the page and refreshes fragments.
- Feed Addon: Configure form → product mapping using GF Add-On Framework (supports calculator or generic mapping).

## REST API

Namespace: `gf-wc/v1`

- POST `/calculate-price`
  - Body: `width` (number), `drop` (number), `unit` (mm|cm|in), `product_id` (int)
  - Returns: formatted `price`, `regular_price`, `sale_price`, `area`, and display hints.

- POST `/add-to-basket` (Public by default; see Security)
  - Body: `product_id`, `width`, `drop`, `unit`, `quantity`, `custom_data` (object)
  - Returns: `success`, cart info (count, hash, fragments).

Note: In production, prefer the nonce-protected AJAX path or protect REST with a REST nonce (see Security).

## AJAX Endpoints

- `gf_wc_calculate_price` (nonce: `gf_wc_price_calc`)
- `gf_wc_add_to_basket` (nonce: `gf_wc_dual_submit`)

## Cart Item Meta

Saved human-readable attributes: width, drop, style, louvre size, bar type, frame, position, color.
Hidden meta for internal use: `_gf_entry_id`, `_gf_entry_url`, `_width_cm`, `_drop_cm`, `_area_m2`, `gf_total`, `gf_regular_price`, `gf_sale_price`, `gf_is_on_sale`.

## Logging & Debugging

- WP_DEBUG_LOG-aware `Logger` class. Toggle “Debug Mode” in Settings to increase verbosity.
- Logs include calculation attempts, add-to-cart success/failure, and feed processing.

## Internationalization (i18n)

- Text Domain: `gf-wc-bridge`
- Recommended additions (WPPB alignment):
  - Add `languages/` with a `.pot` file, and call `load_plugin_textdomain('gf-wc-bridge', false, dirname(plugin_basename(__FILE__)) . '/languages');` on `plugins_loaded`.

## Security

- Admin settings and actions require `manage_options` and use nonces.
- AJAX endpoints are nonce-protected.
- REST endpoints are public in current code; recommended to either remove the `add-to-basket` REST route or require a REST nonce (see Roadmap).
- Sanitization: Admin output is escaped; ensure cart meta uses `sanitize_text_field()` (planned fix) and numeric normalization (`wc_format_decimal`).

## Performance

- Cached repositories (forms/products) via WP Object Cache; 1-hour TTL by default.
- Server authoritative price calculation; frontend updates are display-only.

## Developer Guide

Codebase overview:

- `src/Plugin.php` – bootstrap, container setup, and hooks
- `src/Container/*` – DI container and service provider registration
- `src/Fields/*` – Gravity Forms custom fields
- `src/Integration/WooCommerceCart.php` – main GF ↔ WC integration
- `src/Addons/WooCommerceFeedAddon.php` – feed-based integration
- `src/API/CalculatorController.php` – REST endpoints
- `src/Calculation/*` – pricing logic and value objects
- `src/Assets/AssetManager.php` – script enqueue + localization
- `src/Admin/*` – settings, notices, toolbar, editor helpers
- `src/Repositories/*` + `src/Cache/*` – data access and caching
- `src/Theme/ShuttersTheme.php` – GF theme layer
- `assets/*` – JS/CSS for UI

Local development:

- Composer: autoload and PHPStan
- Node/ESLint: lint JS with provided config
- PHP: strict types; follow WordPress PHPCS where applicable

## WordPress Plugin Boilerplate (WPPB) Alignment

Your plugin already exceeds WPPB in several areas (DI, testability, modularity). To align with WPPB conventions and improve maintainability:

Present/Aligned
- Namespaced classes; single-responsibility services
- Separation of admin/public responsibilities
- Asset enqueue and localization encapsulated in a class
- Clear bootstrap entry (Plugin class)

Recommended Additions (Missing vs WPPB)
- Activation/Deactivation/Uninstall
  - Add registration hooks in bootstrap to run activation/deactivation routines (capability setup, cache priming, clean-up).
  - Provide `uninstall.php` to delete options/transients (e.g., debug mode option).
- Internationalization
  - Provide `languages/` directory and call `load_plugin_textdomain` early (plugins_loaded).
- Text Domain Consistency
  - Confirm all `__()`/`_e()` calls consistently use `gf-wc-bridge`.
- Admin/Public Assets Structure
  - Consider organizing assets in `admin/` and `public/` subfolders to mirror WPPB’s structure (optional; current `assets/` is acceptable).
- Loader/Hook Orchestration
  - WPPB uses a Loader class to register hooks. Your DI approach is solid; document this choice in README so contributors understand where hooks live (ServiceProvider + constructors).
- Sample Templates/Partials
  - Extract any repeat admin HTML into partials for clarity (optional).
- Coding Standards
  - Include PHPCS config and a quick-start section to run linting for PHP to complement the ESLint setup.

Security Hardening (Recommended)
- Sanitize all cart meta values and numeric inputs (see Roadmap Immediate Fixes).
- For REST: require `X-WP-Nonce` with `wp_rest` nonce for `/add-to-basket` or disable it (prefer AJAX path).

## Roadmap

See `ROADMAP.md` for:
- Immediate fixes (Alpine quote normalization, sanitization, parse_str handling, REST hardening, logging cleanup, version consistency, CDN override, i18n in JS)
- Decoupling plan (PriceProvider/PriceResolver, admin “Price Source” UI, validator updates)
- Acceptance criteria and manual test checklist

## Contributing

- Issues and PRs welcome. Please:
  - Follow PHP 8+ types and WordPress best practices.
  - Keep changes minimal and focused; respect existing DI and structure.
  - Add/update docs and acceptance criteria where relevant.

## License

GPL-2.0-or-later

