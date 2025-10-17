# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.2] - 2025-10-17

### Fixed
- Correct `add_action()` syntax for component initialization priority parameter

## [2.4.1] - 2025-10-17

### Fixed
- Translation loading now happens at correct time (WordPress 6.7+ compatibility)
- Move component initialization from `plugins_loaded` to `init` hook
- Set text domain loading priority to ensure translations load before components

## [2.3.2] - 2025-10-05

### Added
- Settings → Tools: View in Site Health, Copy diagnostics, Clear confirmation messages
- Settings → Advanced: Toggle REST add-to-basket (off by default), choose Alpine.js source (CDN/Local)
- Site Health → Info: “GF → WC Cart” section with plugin/GF/WC versions and configured form count
- Lifecycle: activation/deactivation hooks; uninstall cleanup for options and transients

### Changed
- Load text domain on `init` to avoid “loaded too early” notices
- More robust version detection on settings and Site Health pages

### Fixed
- Dependency injection of WooCommerceCart (ensure Logger is passed)

## [2.3.0] - 2025-10-03

### Added
- **Stacked Badge-Style Price Display** - Ecommerce-optimized layout with separate "Normal Price" and "Your Price" rows
- **Savings Percentage Badge** - Eye-catching "You Save X%!" badge with orange gradient background
- **Dynamic Placeholder Updates** - Input placeholders automatically update when unit changes (100cm → 1000mm → 39.4in)
- **Savings Calculation** - JavaScript calculates and displays percentage saved on sale items
- **Enhanced Price Template** - New HTML structure in PriceCalculator.php with conditional display logic

### Changed
- **Price Display Styling** - Moved all price calculator CSS to shutters-theme.css for proper loading
- **Visual Hierarchy** - Green gradient for "Your Price" row, white background for "Normal Price" row
- **Sale Price Emphasis** - Bold large white text on gradient background for maximum visibility
- **Measurement Unit Component** - Added updateFieldPlaceholders() method to handle dynamic placeholder updates
- **Mobile Responsive** - Stacked price rows convert to centered column layout on small screens

### Fixed
- CSS styles now properly enqueued via Gravity Forms Theme Layer API
- Price calculator styles load correctly on frontend

### Technical Details
- **Placeholder Conversion**: 100cm base value converts to 1000mm (×10) or 39.4in (÷2.54)
- **Savings Formula**: `((regularPrice - salePrice) / regularPrice) * 100`
- **Conditional Display**: Uses Alpine.js `x-show` directives for sale vs. regular price layouts
- **CSS Variables**: Uses `var(--gf-color-primary)` for theme consistency

## [2.2.0] - 2025-10-02

### Added
- **Measurement Unit Field** - New custom Gravity Forms field for dynamic unit selection (mm/cm/in)
- **Automatic Label Updates** - Width and drop field labels update with selected unit (e.g., "Width (cm)" → "Width (mm)")
- **Smart Constraint Conversion** - Min/max/step values automatically convert for each unit
- **Real-time Instructions** - Helper text updates with correct ranges and units (e.g., "Please enter a value between 20cm and 200cm")
- **Self-contained Configuration** - Configure which width/drop fields to control directly in the editor
- **Alpine.js Component for Units** - Reactive UI updates using data attributes (no wp_localize_script)
- **Editor Settings for Measurement Unit** - Custom field settings for width/drop field mapping
- **Field Settings JavaScript** - EditorScript.php handles dropdown population and value loading

### Changed
- **Asset Enqueuing** - measurement-unit.js conditionally loaded only when field is present
- **Alpine Dependencies** - measurement-unit script loads before Alpine.js for component registration
- **Script Organization** - Separated editor JavaScript into dedicated EditorScript class
- **Field Validation** - Measurement Unit field validates submitted values (mm/cm/in only)

### Technical Details
- **Unit Conversion**: mm ×10, cm ×1, in ÷2.54 from base cm values
- **Data Attributes**: Configuration passed via HTML data-* attributes on ginput_container div
- **Field Injection**: Alpine attributes injected via gform_field_content filter
- **Hardcoded Choices**: mm/cm/in choices cannot be modified to ensure consistency

## [2.1.2] - 2025-10-02

### Added
- **Alpine.js Integration** - Modern reactive JavaScript framework for price calculations
- **Template-Based Rendering** - PHP outputs complete HTML structure with Alpine directives
- **Multi-Page Form Support** - Price calculator works correctly on final page of multi-step forms
- **Improved Price Styling** - Ecommerce-style display with bold red sale price and muted strikethrough

### Changed
- **JavaScript Refactor** - Migrated from jQuery DOM manipulation to Alpine.js reactive state
- **Code Simplification** - Reduced JavaScript from 180+ lines to 90 clean lines
- **Template Approach** - PHP template with `x-text`, `x-show`, `x-model` instead of `.html()` injection
- **Price Display** - Sale price in bold red (#dc2626), regular price in gray with strikethrough
- **Field Base Class** - Changed from `GF_Field_Number` to `GF_Field` for full control

### Fixed
- Price calculator now calculates immediately on component init (multi-page forms)
- Gravity Forms no longer overwrites custom HTML with built-in field behaviors
- Price display shows correctly on all form pages

### Removed
- All debug console.log statements for production-ready code
- jQuery dependency for price calculator (still used for dual-submit)
- Legacy DOM manipulation patterns

## [2.1.1] - 2025-10-01

### Added
- **Sale Price Display** - Show strikethrough regular price when product is on sale
- **Visual Price Comparison** - Customers see savings at a glance (e.g., ~~£120.00~~ **£108.00**)
- **Calculation Breakdown** - Optional display of width × height = area
- **Client-side Price Calculation** - Real-time price updates without AJAX calls
- **Improved Price Display** - Better formatting with prefix/suffix support

### Changed
- Price calculator now uses WooCommerce product prices directly from config
- Removed unnecessary AJAX call for price calculation (faster performance)
- Enhanced CSS styling for price display with proper size variations
- Price display now supports all field settings (prefix, suffix, calculation, sale comparison)

### Fixed
- Price display properly shows strikethrough regular price when sale is active
- Calculation breakdown now displays correctly alongside prices
- Price formatting consistent across all display sizes (small, medium, large)

## [2.1.0] - 2025-09-30

### Added
- **Configuration Dashboard** - New admin page under Forms → Cart Integration
- **Real-time Validation** - Comprehensive validation for product/field IDs
- **Admin Notices** - Helpful warnings for missing dependencies and configuration issues
- **System Health Dashboard** - Shows all configured forms with status indicators
- **Admin Toolbar Status** - Quick status indicator showing configuration health
- **User-Friendly UI** - Improved labels, removed abbreviations and technical jargon
- **Field Dropdowns** - Show labels + IDs for clarity, not just numbers
- **Visual Indicators** - Green checkmarks for valid configs, yellow warnings for issues
- **Contextual Help** - Helpful descriptions under each setting
- **Quick Start Guide** - Step-by-step instructions in dashboard
- **Quantity Selector** - Add multiple items (1-99) of same configuration
- **Smart Form Reset** - Clears browser storage for truly fresh form state
- **Block Theme Support** - Works with WooCommerce Mini Cart block and Store API
- **Dual Cart System** - Supports both modern blocks and legacy widgets
- **Cache-Busting Redirects** - Forces fresh page loads on form reset
- **Enhanced Cart Updates** - Triggers multiple WooCommerce events for reliability

### Changed
- Improved "Add Another Configuration" to clear localStorage/sessionStorage
- Enhanced cart fragment updates to work with block themes
- Better WooCommerce integration using Store API for modern themes

### Fixed
- Form reset now properly clears all Gravity Forms browser storage
- Mini cart updates work with both block-based and legacy themes
- Cart count updates instantly after AJAX add to basket

## [2.0.0] - 2024-09-01

### Added
- Complete rewrite using modern PHP 8.2+ features
- Implemented Gravity Forms Theme Layer API
- Dual submit button functionality (Add to Basket / Pay Now)
- AJAX cart integration with fragments support
- Race condition protection for duplicate submissions
- Comprehensive error logging
- Security hardening (sanitization, nonces, capability checks)
- Responsive design improvements
- Accessibility enhancements (WCAG 2.1 AA)
- Custom Price Calculator field type
- Real-time price calculations
- Sale price support

### Changed
- Complete codebase modernization
- PSR-4 autoloading with Composer
- Proper namespacing and class organization
- Clean separation of concerns

## [1.0.0] - 2024-01-15

### Added
- Initial release
- Basic Gravity Forms to WooCommerce integration
- Simple price calculations
- Form submission to cart

---

**Legend:**
- `Added` - New features
- `Changed` - Changes in existing functionality
- `Deprecated` - Soon-to-be removed features
- `Removed` - Removed features
- `Fixed` - Bug fixes
- `Security` - Security improvements
## [2.3.4] - 2025-10-05

### Added
- GitHub update integration settings (repo/token), auto-update toggle, and force update check
- GitHub Actions release workflow (build production zip on tag)

### Changed
- Improve CI packaging step to avoid recursive copy; add manual trigger
- Bump plugin version to 2.3.4
## [2.3.5] - 2025-10-05

### Changed
- Bump plugin version to 2.3.5 (no functional changes)

## [2.3.6] - 2025-10-05

### Changed
- Bump plugin version to 2.3.6

## [2.4.0] - 2025-10-05

### Changed
- Bump plugin version to 2.4.0
