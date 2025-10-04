# Gravity Forms → WooCommerce Cart: Fix Plan and Decoupling Roadmap

This document outlines:

- A prioritized plan to address identified issues (security, quality, DX)
- A phased roadmap to fully decouple WooCommerce cart integration from the Price Calculator field, enabling flexible pricing sources

Audience: plugin maintainers and contributors.

Last updated: 2025-10-04

## Goals

- Make the WooCommerce cart integration work on any form without requiring the Price Calculator field.
- Introduce a flexible “Price Source” abstraction so forms/feeds can choose pricing from: Calculator field, a specific form field, fixed value, or other logic.
- Tighten security (sanitization and CSRF) and improve diagnostics without noisy logs in production.

---

## Immediate Fixes (Priority)

Timeframe: 1–3 days. Risk: Low; Scope: Focused improvements.

1) Fix Alpine “Invalid or unexpected token” from typographic quotes
- Problem:
  - Console shows Alpine Expression Error (e.g., `x-show="savingsPercent > 0″>`), caused by typographic quotes (curly/double-prime) being introduced into attribute values.
- Where:
  - `src/Fields/PriceCalculator.php` markup in `get_text_display()` (x-attributes), and more generally any `gform_field_content` that WordPress/filters might texturize.
- Change:
  - Expand `clean_smart_quotes_from_output()` replacement map to normalize additional Unicode characters to ASCII:
    - U+201C/U+201D (curly double), U+2018/U+2019 (curly single), U+2032 (prime ′), U+2033 (double prime ″), U+00AB/U+00BB (angle quotes « »), and common dashes/ellipsis.
  - Increase filter priority to `PHP_INT_MAX` to ensure we run after any late texturize/filters.
  - Prefer single quotes around Alpine expressions in attributes (e.g., `x-show='savingsPercent > 0'`) to reduce interference.
  - Optional last-resort guard: enqueue a tiny inline script that runs before Alpine and normalizes typographic quotes within `x-` attributes (use `alpine:initializing` hook) — only if issues persist in specific environments.
- Acceptance:
  - No Alpine expression parsing errors in console; attributes contain plain ASCII quotes; UI behaves as expected.

Implementation details:
- Update: `src/Fields/PriceCalculator.php`
  - Method: `clean_smart_quotes_from_output()` → extend `$replacements` to include `\xE2\x80\xB2` (U+2032), `\xE2\x80\xB3` (U+2033), `\xC2\xAB` (U+00AB), `\xC2\xBB` (U+00BB) and any other quotes if observed. Keep existing replacements.
  - Registration: change `add_filter('gform_field_content', ..., 999, 5)` to use `PHP_INT_MAX`.
  - Template: in `get_text_display()` ensure Alpine attributes use single quotes: `x-show='...'`, `x-text='...'`.
- Also scan: `src/Fields/MeasurementUnit.php` if any Alpine attributes (injected via `add_alpine_attributes()`) contain expressions — switch to single quotes there too.

2) Sanitize user-supplied values before storing to cart/order meta
- Where:
  - `src/Integration/WooCommerceCart.php` in both `ajax_add_to_basket()` and `add_to_cart()` when building `$custom_data`.
  - Helper methods `get_parsed_value()`/`get_image_choice_value()` return user inputs that should be sanitized per value before saving to cart meta.
- Change:
  - Apply `sanitize_text_field()` to all string metadata values (e.g., `style`, `louvre_size`, `bar_type`, `frame_type`, `frame_options`, `position`, `color`). Keep numeric casts for numeric values (e.g., width/drop floats already cast).
- Acceptance:
  - All string values added to `$custom_data` are sanitized.
  - No functional behavior change; existing displays still work.

Implementation details:
- Update: `src/Integration/WooCommerceCart.php`
  - In `ajax_add_to_basket()`: after building `$custom_data`, loop and sanitize string values, or sanitize at assignment time; explicitly cast floats for numeric strings.
  - In `add_to_cart()`: sanitize values added to `$custom_data` and again before `add_meta_data` in `save_order_item_meta()` to be defensive.
  - Where values are displayed in cart via `woocommerce_get_item_data`, pass already-sanitized values; WooCommerce will escape on render, but inputs should be clean at the source.

3) Don’t sanitize the entire form-encoded string prior to `parse_str`
- Where: `WooCommerceCart::ajax_add_to_basket()` currently does `sanitize_text_field(wp_unslash($_POST['form_data']))` before `parse_str()`.
- Change:
  - Use `wp_unslash()` only, then `parse_str()` to array, and sanitize each parsed value only as needed before use or storage.
- Acceptance:
  - Complex field values are not corrupted; per-field sanitization still applied before adding to cart.

Implementation details:
- Update: `ajax_add_to_basket()`
  - `$form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : '';`
  - `parse_str($form_data, $parsed_data);`
  - Sanitize when reading individual `input_` keys.
  - For numeric values, normalize with Woo helpers: `wc_clean()`, `wc_format_decimal()`, honoring store locale (`wc_get_price_decimal_separator()` / `wc_get_price_thousand_separator()`).

4) Replace raw `error_log()` calls with `Logger` and guard by debug mode
- Where:
  - `src/Container/ServiceProvider.php` (manual includes, feed registration messages),
  - `src/Integration/WooCommerceCart.php` (debug traces in AJAX and form submit flows),
  - Anywhere else using `error_log()`.
- Change:
  - Route logs to `Logging\Logger` (`gf_wc_log()` helper is available), and optionally gate verbose messages behind `SettingsPage::is_debug_mode()`.
- Acceptance:
  - No raw `error_log()` in production paths except via `Logger` (which already respects `WP_DEBUG_LOG`).

5) REST: Harden add-to-basket endpoint against CSRF (or prefer AJAX-only)
- Where: `src/API/CalculatorController.php` registers both `/calculate-price` and `/add-to-basket` with `permission_callback => '__return_true'`.
- Options:
  - A) Keep `/calculate-price` public (read-like) but require a nonce for `/add-to-basket` (state change): accept a header `X-WP-Nonce` and verify with `wp_verify_nonce()`.
  - B) Remove/disable `/add-to-basket` route and rely on the already nonce-protected AJAX path (`gf_wc_add_to_basket`).
- Recommendation: Start with Option B (simplify) unless REST is needed by a headless client. If needed, implement Option A and localize a `restNonce` to the frontend.
- Acceptance:
  - State-changing REST calls cannot be triggered cross-origin without a valid nonce.

Implementation details:
- Option A:
  - In `CalculatorController::register_routes()`, change `permission_callback` for `/add-to-basket` to a method that verifies `wp_verify_nonce( $_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest' )` and current user/session.
  - Localize `restNonce` via `wp_create_nonce('wp_rest')` for consistency with core REST.
- Option B:
  - Comment out or guard registration of `/add-to-basket` route behind a filter `gf_wc_enable_rest_cart` (default false).

6) Version consistency + helper bootstrap
- Where:
  - Plugin header shows `Version: 2.3.0` while `src/helpers.php` bootstraps `Plugin::get_instance(__FILE__, '2.4.0')`.
- Change:
  - Keep a single version source of truth (plugin header constant) and avoid booting a duplicate instance from `helpers.php`. Fetch the already-instantiated container via a static entrypoint or pass the header version to `Plugin` on first init only.
- Acceptance:
  - No mismatched versions in runtime logs or admin displays; `helpers.php` does not instantiate a second `Plugin`.

Implementation details:
- Update: `src/helpers.php`
  - Replace `Plugin::get_instance(__FILE__, '2.4.0')` with a call that returns the existing instance. For example, add `Plugin::get_instance(BENHUGHES_GF_WC_FILE, BENHUGHES_GF_WC_VERSION)` or expose a static `Plugin::instance()` that returns the singleton without re-initialization.

7) CDN dependency for Alpine.js
- Where: `src/Assets/AssetManager.php` enqueues from jsDelivr.
- Options:
  - A) Ship a pinned local copy in `assets/vendor/alpinejs/` and enqueue locally.
  - B) Keep CDN as default but add a filter `gf_wc_alpine_src` to override URL (and optionally add SRI attributes via `wp_enqueue_scripts` hook).
- Acceptance:
  - Site owners can run without CDN; CSP-conscious sites can self-host.

Implementation details:
- Update: `src/Assets/AssetManager.php`
  - Wrap Alpine URL in `apply_filters('gf_wc_alpine_src', 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js')`.
  - If shipping local copy, enqueue it conditionally when filter returns local.
  - Optional: add integrity/crossorigin attributes via `wp_script_add_data()` if using CDN.

8) i18n for Dual Submit UI strings
- Where: `assets/dual-submit.js` contains hard-coded English strings (e.g., “Quantity:”, “🛒 Add to Basket”, “⏳ Adding...”).
- Change:
  - Pass all UI strings via `wp_localize_script('gf-wc-dual-submit', ...)` and reference them instead of literals. Already has `addToBasketText`/`payNowText`; add the rest.
- Acceptance:
  - All visible strings are translatable and replace literal English text.

Implementation details:
- Update: `src/Assets/AssetManager.php`
  - Localize: quantity label, adding/loading text, success and error messages.
- Update: `assets/dual-submit.js`
  - Replace hard-coded strings with `this.config.*` values.

---

## Decoupling Roadmap (Pricing Calculator → Optional Field)

Objective: The core WooCommerce cart integration should not depend on the Price Calculator field. Introduce a price provider abstraction and UI to select a price source per form/feed.

Timeframe: 2–4 weeks, incremental; backwards compatible.

### Phase 1: Introduce Price Provider Abstraction

- Add an interface:
  - `src/Services/Contracts/PriceProviderInterface.php`
    - Method: `public function resolvePrice(array $form, array $entry, array $context = []): float;`
    - Optionally: `public function explain(array $form, array $entry): array` (for UI/diagnostics – area, breakdown text, etc.).

- Implement providers:
  - `PriceCalculatorProvider` (wraps current `Calculation\PriceCalculator` usage when a calculator field exists).
  - `FieldValueProvider` (reads a user-selected numeric/currency form field as the price).
  - Optional future: `FixedAmountProvider`, `ProductAreaProvider` (uses width×drop×m² pricing without requiring a dedicated field).

- Register providers in the container and expose a resolver:
  - `src/Services/PriceResolver.php`: picks the active provider for a given form using config (feed settings or per-form settings if no feed).

- Update `WooCommerceCart` to use `PriceResolver`:
  - In `add_to_cart()` and `ajax_add_to_basket()`, instead of directly invoking `PriceCalculator`, ask `PriceResolver` for the price and any display meta (e.g., calculation text if available). Merge results into `$custom_data` as today.

Pricing and tax behavior:
- When setting custom item prices, use Woo price APIs and rounding:
  - Respect `wc_get_price_decimals()`; normalize via `wc_format_decimal()`.
  - Set price on the cart item’s product object (`$cart_item['data']->set_price( $price )`) and, if needed for display, set regular/sale prices accordingly so totals, taxes, and discounts apply correctly.
  - Ensure the logic runs in `woocommerce_before_calculate_totals` to persist across session/cart reload.

Implementation details:
- New: `src/Services/Contracts/PriceProviderInterface.php`
  - `resolvePrice(array $form, array $entry, array $context = []): array{price: float, display?: array, meta?: array}`
- New: `src/Services/PriceResolver.php`
  - Accepts configuration (feed meta/per-form) and a registry of providers. Precedence: Feed mapping > Calculator present > Fixed amount (if set) > Error.
- Providers:
  - `PriceCalculatorProvider` uses `Calculation\PriceCalculator` when calculator field is present; emits price + breakdown text.
  - `FieldValueProvider` locates the mapped field in `$entry`, normalizes numeric value (strip currency symbol, thousands separators), casts to float, validates non-negative.
  - Optional `FixedAmountProvider` for trivial pricing.
- Register in `Container/ServiceProvider.php` and inject resolver into `WooCommerceCart`.

Acceptance:
- With a calculator field present, behavior is unchanged.
- Without a calculator field, if a “price field” is mapped in settings, the item is added with that price.

Note on Alpine normalization:
- As part of this phase, update `clean_smart_quotes_from_output()` and audit any other Alpine-bearing markup (measurement unit radio) to ensure attributes use plain ASCII quotes and are not re-texturized.

### Phase 2: Admin UX for Price Source

- Feed settings (preferred):
  - Extend `Addons/WooCommerceFeedAddon` → `feed_settings_fields()` to add a “Price Source” selector with choices:
    - “Price Calculator Field (auto)” – default, auto-detect if present
    - “Form Field (select)” – reveals a dropdown of numeric/currency fields
    - “Fixed Amount” – reveals an amount input
    - [Future] “Product Area (m²) from Dimensions”
  - Persist in feed meta. Adapt `process_feed()` to delegate to `PriceResolver` based on this meta.

- No-feed fallback (legacy):
  - Add per-form integration settings (if no feed exists) via existing SettingsPage or a new small per-form settings page that stores a minimal mapping: `price_source`, `price_field_id`.
  - `WooCommerceCart` checks feed first; if none, uses per-form fallback via `PriceResolver`.

Implementation details:
- Extend `Addons/WooCommerceFeedAddon::feed_settings_fields()` with a new “Price Source” fieldset + conditional sub-fields.
- Add a helper to enumerate numeric/currency fields for dropdown.
- Update `Validation/ConfigValidator` to consider either a calculator field or a mapped price field as valid configuration.

Acceptance:
- An admin can set price behavior even without the calculator field.
- Validator updates: `ConfigValidator` validates either “calculator present” or “price field mapped” to mark form “ready”.

### Phase 3: Frontend and API Alignment

- `AssetManager`:
  - Only enqueue `price-calculator.js` when calculator field is present.
  - Dual-submit remains always available (uses AJAX nonce, not price calculation script).
  - MeasurementUnit script only when that field exists (already implemented).

- AJAX/REST endpoints:
- `/calculate-price` can return either calculator-based price (if available) or a pass-through value for field-based provider (which may just echo the value from the mapped field), so the UI displays something consistent.
  - Consider removing `/add-to-basket` REST (keep AJAX with nonce) unless headless is in scope.

Acceptance:
- JS continues to work with or without calculator field.
- No unnecessary scripts when not needed.

Implementation details:
- `AssetManager`: only enqueue `price-calculator.js` if a calculator field exists on the form (current behavior for config; verify detection).
- `CalculatorController`: when using `FieldValueProvider`, return minimal structure `{ price }` and omit breakdown fields; front-end should handle absence gracefully.

### Phase 4: Cleanup, Back-Compat and Docs

- Optional: Move Calculator field and calculation classes into a submodule namespace (remain in repo but clearly optional), or load conditionally only when used.
- Keep public filters/actions stable. Document deprecations if any.
- Update README with “Price Source” docs + examples.

Acceptance:
- Users can opt-in to calculator or a simple price field without code changes.
- Backwards compatibility maintained for existing forms.

---

## Migration Notes

- Existing sites using the Price Calculator field: no action required; default provider remains the calculator.
- Sites without the calculator: configure a feed (or per-form fallback) and select “Form Field” as price source.
- Security hardening is backward-compatible.

---

## Acceptance Criteria (Quick Checklist)

- [ ] All cart/order meta values derived from user input are sanitized.
- [ ] No Alpine expression errors (quote normalization + single quotes in x-attributes).
- [ ] REST add-to-basket protected (nonce) or removed; AJAX path remains nonce-protected.
- [ ] No raw `error_log()` in production paths; logs use `Logger` and respect debug mode.
- [ ] Version is consistent and not bootstrapped twice from helpers.
- [ ] Alpine.js can be self-hosted or overridden via filter.
- [ ] All frontend strings are translatable.
- [ ] PriceResolver selects price from either calculator or configured field.
- [ ] Feed UI exposes “Price Source” and validates configuration.
 - [ ] Custom price persists across mini-cart, checkout, and page reload; order totals match expected.
 - [ ] Taxes/discounts apply correctly; rounding matches `wc_get_price_decimals()`.

---

## Task Breakdown (Granular)

1) Security & Quality
- [ ] Sanitize `$custom_data` in `WooCommerceCart::ajax_add_to_basket()` and `add_to_cart()`
- [ ] Replace `sanitize_text_field()` on `form_data` with `wp_unslash()` + per-field sanitization
- [ ] Swap `error_log()` calls → `gf_wc_log()` or `$logger->debug()` (guard by `SettingsPage::is_debug_mode()` as needed)
- [ ] REST harden or remove `/add-to-basket` route
- [ ] Fix helpers version/instance usage
- [ ] Alpine.js: local vendor copy or filter for source
- [ ] Localize Dual Submit strings
- [ ] Normalize curly/prime quotes in `gform_field_content` and switch Alpine x-attributes to single quotes
 - [ ] Add SRI/crossorigin metadata for CDN Alpine; verify filter override works

2) Abstraction & Admin
- [ ] Add `PriceProviderInterface`
- [ ] Add `PriceResolver` with provider registration
- [ ] Implement `PriceCalculatorProvider` and `FieldValueProvider`
- [ ] Feed settings: add “Price Source” and any sub-settings (field selector)
- [ ] Update `ConfigValidator` to validate either path
 - [ ] Per-form fallback configuration when no feed is configured

3) Frontend & API
- [ ] Conditional script enqueues based on field presence
- [ ] `/calculate-price` supports both providers (return useful display data)
 - [ ] Dual-submit continues to work without calculator; gracefully handles no breakdown
 - [ ] Mini-cart/cart fragments update correctly after AJAX add-to-basket

4) Docs
- [ ] README updates for “Price Source”
- [ ] Changelog entries
 - [ ] Add manual test checklist to docs (see below)

---

## Manual Test Checklist

- Calculator present:
  - Live calculation updates; add-to-basket sets custom price; mini-cart and checkout totals correct; order meta populated.
- Calculator absent + price field mapped:
  - Price read from selected field; add-to-basket works; totals correct; no breakdown UI.
- Multi-page form (page breaks):
  - Values persist; calculation (if present) runs; dual-submit available on final page.
- Validation failures:
  - Missing width/drop or price field gracefully blocks add with message; no JS errors.
- Tax settings (incl/excl tax):
  - Cart totals match store settings; no double tax.
- Session restore:
  - Custom price persists after reload; mini-cart shows correct price.
- i18n:
  - All user-facing strings in dual-submit and calculator UI translatable.

---

## Open Questions / To Decide

- Numeric normalization for field-based price: allow currency symbols? Proposal: strip non-numeric except decimal/thousands separators, then `floatval`. Respect WooCommerce decimal and thousand separators if available.
- Taxes and price inclusivity: When setting custom item price, ensure Woo applies tax rules as per store config; avoid double-tax. Current `update_cart_item_price` path should be reviewed after provider changes.
- Multi-currency support: Out of scope now; consider compatibility with multi-currency plugins later.

---

## File Pointers (for quick navigation)

- Integration & flows:
  - `src/Integration/WooCommerceCart.php`
  - `src/Assets/AssetManager.php`
- Admin:
  - `src/Admin/SettingsPage.php`, `src/Admin/AdminNotices.php`, `src/Admin/AdminToolbar.php`
- Calculator & fields (optional):
  - `src/Calculation/PriceCalculator.php`
  - `src/Fields/PriceCalculator.php`, `src/Fields/MeasurementUnit.php`
- API & Services:
  - `src/API/CalculatorController.php`
  - `src/Services/CartService.php`
  - (New) `src/Services/Contracts/PriceProviderInterface.php`, `src/Services/PriceResolver.php`
- Repositories & Cache:
  - `src/Repositories/*`, `src/Cache/*`
- Utilities:
  - `src/helpers.php`, `src/Logging/Logger.php`

---

## Notes

- Keep the “single source of truth” principle: server-side computation or resolution should determine final price; the frontend merely displays.
- Favor feed-based configuration for flexibility and clarity; provide a minimal fallback for no-feed forms.
