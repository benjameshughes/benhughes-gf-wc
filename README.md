# Cart Integration for Gravity Forms

A WordPress plugin that seamlessly connects Gravity Forms with WooCommerce, enabling dynamic price calculations based on customer measurements and instant cart integration.

**Version:** 2.3.6
**Author:** Ben Hughes
**Requires:** WordPress 5.0+, PHP 8.2+
**Dependencies:** Gravity Forms 2.5+, WooCommerce 5.0+, Alpine.js 3.x (auto-loaded)

## Features

### 🧮 Price Calculator Field
- Custom Gravity Forms field type for real-time price calculations
- **Modern Alpine.js reactive framework** - Clean, declarative JavaScript with no jQuery dependencies
- Calculate prices based on width × height measurements
- Support for WooCommerce product pricing (regular and sale prices)
- Live price updates as customers enter measurements
- **Beautiful ecommerce-style price display** - Strikethrough regular price, bold red sale price
- Customizable display formats (text or input field)
- Multiple currency options (GBP, USD, EUR, JPY, INR, AUD, CAD, CHF, SEK, ZAR)
- Custom price prefix/suffix text (e.g., "Your Price:", "inc. VAT")
- Optional calculation breakdown display
- Sale price comparison display with visual urgency

### 📏 Measurement Unit Field
- **Dynamic unit selector** - Radio buttons for millimeters (mm), centimeters (cm), or inches (in)
- **Automatic label updates** - Width and drop field labels update with selected unit (e.g., "Width (cm)" → "Width (mm)")
- **Smart constraint conversion** - Min/max/step values automatically convert for each unit
- **Real-time instruction updates** - Helper text shows correct ranges with units (e.g., "Please enter a value between 20cm and 200cm")
- **Self-contained configuration** - Configure which width/drop fields to control in the editor
- **Alpine.js reactive updates** - Instant UI updates without page reload
- **Consistent default choices** - Hardcoded mm/cm/in options ensure reliability

### 🛒 Smart Cart Integration
- **Quantity Selector** — Add multiple items of the same configuration (1-99)
- **Add to Basket** — AJAX add with instant cart updates (no page reload)
- **Add Another Configuration** — Clears form completely for new configuration
- **Proceed to Checkout** — One-click navigation to checkout
- Seamless WooCommerce cart integration (works with blocks and legacy widgets)
- Automatic Mini Cart updates using WooCommerce Store API
- Preserves form entry data in Gravity Forms
- Intelligent cart item grouping (same config = quantity increase, different config = separate items)
- Race condition protection prevents duplicate submissions
- Instant feedback with success messages
- Clears browser storage on form reset for true fresh start

### 📊 Configuration Dashboard
- Visual status overview of all configured forms
- Real-time validation of form configurations
- Product and field mapping verification
- Quick access to form editing
- Color-coded status indicators (✓ valid, ⚠ issues)
- Access via **Forms → Cart Integration**

### 🔔 Smart Notifications
- Admin notices for missing dependencies (Gravity Forms, WooCommerce)
- Configuration issue alerts with specific details
- Admin toolbar status indicator showing form health
- Actionable links to fix issues
- Auto-dismisses when issues are resolved

### ⚙️ Flexible Configuration
- Link any WooCommerce product to form submissions
- Map form fields to width and height calculations
- Field dropdowns show labels + IDs for clarity
- Real-time validation in form editor
- Contextual help text under each setting
- User-friendly labels (no technical jargon)

### 🎨 Modern Form Theme
- Clean, minimal design using Gravity Forms Theme Layer API
- Responsive design with mobile-first approach
- WCAG accessibility compliant
- Customizable via CSS custom properties

## Installation

### 1. Upload Plugin
Place the plugin directory in `/wp-content/plugins/benhughes-gf-wc/`

### 2. Install Dependencies (if using Composer)
```bash
cd wp-content/plugins/benhughes-gf-wc
composer install
```

### 3. Activate
Activate the plugin through the WordPress 'Plugins' menu.

### 4. Verify Dependencies
Ensure these plugins are installed and active:
- Gravity Forms
- WooCommerce

If dependencies are missing, you'll see admin notices with links to install them.

## Quick Start Guide

### 1. Create or Edit a Form
Add number fields for measurements that customers will enter:
- Width field (e.g., "Width")
- Height/Drop field (e.g., "Drop")

### 2. (Optional) Add a Measurement Unit Field
1. In the Gravity Forms editor, click **Add Fields**
2. Find **Measurement Unit** in the **Advanced Fields** section
3. Drag it to your form
4. Configure which fields it controls:
   - **Width Field to Update**: Select the width number field
   - **Drop Field to Update**: Select the drop/height number field

When customers select a unit (mm/cm/in), the width and drop field labels and constraints will update automatically.

### 3. Add a Price Calculator Field
1. In the Gravity Forms editor, click **Add Fields**
2. Find **Price Calculator** in the **Advanced Fields** section
3. Drag it to your form

### 4. Configure the Price Calculator
Click on the Price Calculator field to access settings:

#### Product to Sell
Select which WooCommerce product will be added to the cart when the form is submitted.

#### Width Field
Select the form field that contains the width measurement (number fields only).

#### Drop/Height Field
Select the form field that contains the height measurement (number fields only).

#### Display Options
- **Display Format**: Show price as text or input field
- **Text Size**: Small, medium, or large
- **Currency**: Choose from 10+ currency symbols
- **Price Text**:
  - Prefix: Custom text before price (e.g., "Your Price:")
  - Suffix: Custom text after price (e.g., "inc. VAT")
- **Show Calculation**: Display width × height breakdown
- **Show Sale Comparison**: Display original price when product is on sale

### 5. Save and Test
1. Save your form
2. Preview it on the frontend
3. (If using Measurement Unit field) Select a unit and watch labels/constraints update
4. Enter measurements and watch the price calculate in real-time
5. Test both submit buttons:
   - **Add to Basket** — Adds to cart without leaving the page
   - **Pay Now** — Goes straight to checkout

## Usage Examples

### Example 1: Custom Blinds Calculator with Unit Selection
```
Form Fields:
- Measurement Unit (Measurement Unit Field, ID: 1)
- Width (Number Field, ID: 2, min: 20, max: 300, step: 0.1)
- Drop/Height (Number Field, ID: 3, min: 20, max: 300, step: 0.1)
- Fabric Choice (Dropdown, ID: 4)
- Price Calculator (ID: 5)

Measurement Unit Settings:
- Width Field to Update: "Width (ID: 2)"
- Drop Field to Update: "Drop/Height (ID: 3)"

Price Calculator Settings:
- Product to Sell: "Roller Blind" (WooCommerce product)
- Width Field: "Width (ID: 2)"
- Drop Field: "Drop/Height (ID: 3)"
- Currency: £ GBP
- Price Prefix: "Your Price:"
- Price Suffix: "inc. VAT"
- Show calculation breakdown: Yes
- Show sale comparison: Yes

How it works:
- Customer selects "mm" → Labels show "Width (mm)", constraints become min: 200, max: 3000, step: 1
- Customer selects "cm" → Labels show "Width (cm)", constraints become min: 20, max: 300, step: 0.1
- Customer selects "in" → Labels show "Width (in)", constraints become min: 8, max: 118, step: 0.25
```

### Example 2: Custom Printing Service
```
Form Fields:
- Print Width (Number Field, ID: 1)
- Print Height (Number Field, ID: 2)
- Material Type (Radio Buttons, ID: 3)
- Quantity (Number Field, ID: 4)
- Price Calculator (ID: 5)

Price Calculator Settings:
- Product to Sell: "Custom Print"
- Width Field: "Print Width (ID: 1)"
- Drop Field: "Print Height (ID: 2)"
- Currency: $ USD
- Display as text: Yes
- Display Size: Large
```

## Admin Dashboard

Access the dashboard at **Forms → Cart Integration**

### System Status
Displays the health of your installation:
- ✓ Gravity Forms Active
- ✓ WooCommerce Active
- Plugin Version

### Configured Forms
Shows all forms with Price Calculator fields:

**Valid Configuration** (Green indicator)
- All settings properly configured
- Product exists in WooCommerce
- Field IDs are valid
- Form is ready to use

**Configuration Issues** (Yellow indicator)
- Specific error details listed
- Common issues:
  - Missing product configuration
  - Invalid field mappings
  - Product no longer exists
  - Field deleted from form

Click **Edit Form** to fix issues or **View Dashboard** for overview.

### Admin Toolbar
When forms are configured, a status indicator appears in the WordPress admin toolbar:

- ✓ **Cart Integration: X forms ready** — All forms valid
- ⚠ **Cart Integration: X of Y forms need attention** — Some forms have issues

Click the toolbar item to:
- View the configuration dashboard
- Access forms with issues directly
- See all configured forms at a glance

## Pricing Calculation

The plugin calculates prices using this formula:

```
Base Price = Product Price (from WooCommerce)
Width Value = Customer's width entry (in mm)
Height Value = Customer's height entry (in mm)

Calculated Price = Base Price × (Width / 1000) × (Height / 1000)
```

The division by 1000 converts millimeters to meters for the calculation.

### Example Calculation
- Product Price: £50.00 per square meter
- Customer Width: 1200mm
- Customer Height: 1800mm

```
Price = £50.00 × (1200 / 1000) × (1800 / 1000)
Price = £50.00 × 1.2 × 1.8
Price = £108.00
```

If the product is on sale, the calculation uses the sale price automatically.

## Cart Integration Workflow

When a form includes a Price Calculator field, the plugin provides an intuitive multi-step cart integration:

### Step 1: Initial Form State
```
Quantity: [1 ▼]  [🛒 Add to Basket]
```
- Quantity selector (1-99)
- Single "Add to Basket" button

### Step 2: After Adding to Cart (AJAX)
```
✅ Product added to cart! Configure another or checkout.

[➕ Add Another Configuration]  [Proceed to Checkout →]
```

**What happens behind the scenes:**
1. Form data sent via AJAX (no page reload)
2. Product added to cart with specified quantity
3. Cart widgets update instantly (WooCommerce fragments)
4. Success message displayed
5. Form resets to blank state
6. User presented with clear next actions

### Step 3: Add Another Configuration
Clicking "Add Another Configuration":
- Clears Gravity Forms browser storage (localStorage/sessionStorage)
- Redirects to clean URL with cache-busting parameter
- Returns to page 1 (for multi-page forms)
- All field values completely cleared
- Quantity selector reset to 1
- Original "Add to Basket" button restored
- Gives truly fresh form state for new configuration

### Step 4: Proceed to Checkout
Clicking "Proceed to Checkout":
- Navigates directly to WooCommerce checkout
- No form submission
- No page reload flash
- Cart already contains all configured items

## Cart Item Uniqueness & Quantity

### How WooCommerce Identifies "Same" vs "Different" Items

WooCommerce generates a unique cart item key by hashing all custom data:
```
product_id + width + height + style + louvre_size + bar_type +
frame_type + frame_options + position + color + gf_total + product_id
```

**If ALL values match** → Quantity increases on existing line item
**If ANY value differs** → New separate line item created

### Example Scenarios

**Scenario 1: Same Configuration (Quantity Increases)**
```
First Add:
- Width: 1200mm, Height: 1800mm, Color: White
- Quantity: 2
Cart: 1 line item, Qty: 2

Second Add (same config):
- Width: 1200mm, Height: 1800mm, Color: White
- Quantity: 3
Cart: 1 line item, Qty: 5 ← Combined!
```

**Scenario 2: Different Configuration (Separate Items)**
```
First Add:
- Width: 1200mm, Height: 1800mm, Color: White
Cart: Item 1, Qty: 2

Second Add (different width):
- Width: 1500mm, Height: 1800mm, Color: White
Cart: Item 1, Qty: 2 + Item 2, Qty: 1 ← Separate!
```

**Why This Matters:**
- Each shutter configuration is physically unique
- Customers can see exactly what they're ordering
- Production needs individual specifications for each item
- Prevents confusion when ordering multiple different sizes

## Validation & Error Handling

The plugin automatically validates:
- ✓ Required dependencies (Gravity Forms, WooCommerce)
- ✓ Product exists in WooCommerce
- ✓ Field IDs exist on the form
- ✓ Field mappings are complete
- ✓ All required settings configured

### Common Issues & Solutions

**"Cart Integration: Gravity Forms is required but not installed."**
- Gravity Forms is not active
- Solution: Click **Get Gravity Forms** to visit gravityforms.com

**"Cart Integration: WooCommerce is required but not installed."**
- WooCommerce is not active
- Solution: Click **Install WooCommerce** to install from WordPress.org

**"Product ID X does not exist in WooCommerce"**
- The linked product has been deleted
- Solution: Edit the form and select a different product

**"Width Field (ID: X) does not exist on this form"**
- The mapped field has been deleted from the form
- Solution: Add a new number field and update the mapping

**"Drop Field (ID: X) does not exist on this form"**
- The mapped field has been deleted from the form
- Solution: Add a new number field and update the mapping

**"Missing required configuration: productId"**
- No product has been selected
- Solution: Edit the Price Calculator field and select a product

## Code Structure

### Directory Layout
```
benhughes-gf-wc/
├── assets/
│   ├── css/
│   │   ├── price-calculator-field.css    # Price display styles
│   │   └── shutters-theme.css            # Form theme
│   └── js/
│       ├── dual-submit.js                # Dual button handler
│       ├── price-calculator-editor.js    # Editor enhancements
│       └── price-calculator-field.js     # Frontend calculations
├── src/
│   ├── Admin/
│   │   ├── AdminNotices.php              # Dependency/config notices
│   │   ├── AdminToolbar.php              # Toolbar status indicator
│   │   ├── EditorScript.php              # GF editor JavaScript
│   │   ├── FieldSettings.php             # GF editor field settings
│   │   └── SettingsPage.php              # Configuration dashboard
│   ├── Assets/
│   │   └── AssetManager.php              # Script/style enqueuing
│   ├── Fields/
│   │   ├── MeasurementUnit.php           # Measurement Unit field
│   │   └── PriceCalculator.php           # Price Calculator field
│   ├── Integration/
│   │   └── WooCommerceCart.php           # WooCommerce cart handler
│   ├── Theme/
│   │   └── ShuttersTheme.php             # GF Theme Layer
│   ├── Validation/
│   │   └── ConfigValidator.php           # Configuration validation
│   └── Plugin.php                        # Main plugin class
├── vendor/                                # Composer dependencies
├── benhughes-gf-wc.php                   # Plugin bootstrap
├── composer.json                         # PHP dependencies
└── README.md                             # This file
```

### Key Classes

#### `Plugin.php`
Main plugin class that initializes all components and manages dependency injection.

#### `Admin\SettingsPage.php`
Renders the configuration dashboard at **Forms → Cart Integration**. Shows:
- System status (Gravity Forms, WooCommerce active)
- All configured forms with validation status
- Quick Start Guide
- Links to edit forms

#### `Admin\FieldSettings.php`
Adds custom settings to the Gravity Forms editor for the Price Calculator field:
- Product selector
- Width/height field selectors
- Display options
- Currency settings
- Price text customization

#### `Admin\AdminNotices.php`
Displays admin notices:
- Missing dependency warnings
- Configuration issue alerts
- Helper methods for success/error messages

#### `Admin\AdminToolbar.php`
Adds configuration status to WordPress admin toolbar:
- Shows count of configured forms
- Warns about forms with issues
- Quick links to dashboard and forms

#### `Fields\PriceCalculator.php`
Custom Gravity Forms Price Calculator field:
- Registers field with GF
- Renders field HTML on frontend
- Handles field settings in editor
- Provides JavaScript configuration

#### `Fields\MeasurementUnit.php`
Custom Gravity Forms Measurement Unit field:
- Extends GF_Field_Radio for radio button functionality
- Hardcoded choices (mm, cm, in) that cannot be changed
- Configurable width/drop field IDs in editor
- Injects Alpine.js attributes via gform_field_content filter
- Validates submitted values

#### `Integration\WooCommerceCart.php`
Handles WooCommerce cart integration:
- AJAX endpoint for "Add to Basket"
- Adds products to cart with custom data
- Handles "Pay Now" redirects
- Security (nonces, sanitization)
- Race condition protection

#### `Validation\ConfigValidator.php`
Validates form configurations:
- Check dependencies active
- Validate product IDs exist
- Validate field IDs exist on forms
- Get all configured forms
- Get forms with errors

### Namespaces
- `BenHughes\GravityFormsWC\` — Root namespace
- `BenHughes\GravityFormsWC\Admin\` — Admin UI components
- `BenHughes\GravityFormsWC\Fields\` — Custom field types
- `BenHughes\GravityFormsWC\Integration\` — WooCommerce integration
- `BenHughes\GravityFormsWC\Validation\` — Configuration validation

## Hooks & Filters Reference

### Actions

#### `gform_field_standard_settings`
Used to add custom settings to the Gravity Forms editor.

#### `gform_editor_js`
Enqueue JavaScript for field editor enhancements.

#### `gform_enqueue_scripts`
Enqueue frontend scripts and styles for forms.

#### `gform_after_submission`
Handle form submission and WooCommerce cart integration.

#### `admin_menu`
Add settings page to WordPress admin menu.

#### `admin_notices`
Display configuration and dependency notices.

#### `admin_bar_menu`
Add status indicator to WordPress admin toolbar.

#### `wp_ajax_gf_wc_add_to_basket`
AJAX endpoint for "Add to Basket" functionality.

### Filters

#### `gform_add_field_buttons`
Add Price Calculator field to Gravity Forms field picker.

#### `gform_field_content`
Render custom field HTML on frontend.

#### `gform_submit_button`
Modify submit button markup to add dual submit functionality.

## JavaScript API

### Frontend Scripts

#### `price-calculator.js` (Alpine.js)
Modern reactive price calculator using Alpine.js:

**Dependencies:** Alpine.js 3.x (auto-loaded from CDN)

**Architecture:**
- **PHP Template**: Renders complete HTML structure with Alpine directives
- **Reactive State**: JavaScript only updates data values
- **No DOM Manipulation**: Alpine handles all UI updates automatically

**Alpine Component:**
```javascript
Alpine.data('priceCalculator', function () {
  return {
    // Reactive state
    finalPrice: '0.00',
    regularPrice: '0.00',
    showRegularPrice: false,
    showCalculation: false,
    calculationText: '',

    // Auto-calculates on init and input changes
    // Updates display via x-text, x-show, x-model directives
  }
})
```

**Configuration:**
Reads configuration from `window.gfWcPriceCalc`:
```javascript
{
  formId: 1,
  priceFieldId: 4,
  widthFieldId: 1,
  dropFieldId: 2,
  unitFieldId: 5,
  productId: 123,
  regularPrice: 150.00,
  salePrice: 100.00,
  isOnSale: true,
  currency: '£',
  showCalculation: true,
  showSaleComparison: true
}
```

#### `measurement-unit.js` (Alpine.js)
Measurement Unit field Alpine.js component:

**Dependencies:** Alpine.js 3.x (auto-loaded from CDN)

**Architecture:**
- **Self-contained component** - Reads config from data attributes on field container
- **Reactive updates** - Changes to unit selection trigger automatic label/constraint updates
- **No wp_localize_script** - Uses HTML data attributes for configuration

**Alpine Component:**
```javascript
Alpine.data('measurementUnit', function () {
  return {
    selectedUnit: 'cm',
    formId: null,
    fieldId: null,
    widthFieldId: null,
    dropFieldId: null,

    init() {
      // Read config from data-* attributes
      // Set up radio button listeners
    },

    updateFieldLabels(unit) {
      // Updates "Width" → "Width (cm)"
    },

    updateFieldConstraints(unit) {
      // Converts min/max/step for selected unit
      // mm: ×10, cm: ×1, in: ÷2.54
    },

    updateRangeInstructions(fieldId, min, max) {
      // Updates helper text with unit
    }
  }
})
```

**Data Attributes:**
Injected on the `<div class='ginput_container ginput_container_radio'>` element:
- `x-data="measurementUnit"` - Alpine component
- `data-form-id` - Gravity Forms form ID
- `data-field-id` - Measurement Unit field ID
- `data-width-field-id` - Width field to update
- `data-drop-field-id` - Drop field to update

**Unit Conversion:**
- **Millimeters (mm)**: Multiply cm values by 10, step: 1
- **Centimeters (cm)**: Base unit, step: 0.1
- **Inches (in)**: Divide cm values by 2.54, step: 0.25

**Template Directives:**
- `x-data="priceCalculator"` - Initializes Alpine component
- `x-text="finalPrice"` - Displays final price (reactive)
- `x-show="showRegularPrice"` - Shows/hides strikethrough price
- `x-model="hiddenValue"` - Binds to hidden form input

#### `dual-submit.js`
Manages dual submit button functionality:

**Dependencies:** jQuery, WooCommerce scripts

**Events:**
- Handles button clicks
- Prevents duplicate submissions via `isProcessing` flag
- Manages button states and loading indicators
- Shows success/error messages
- Triggers WooCommerce events for cart updates:
  - `added_to_cart` — Legacy widget support
  - `wc-blocks_added_to_cart` — Modern block support
- Updates WooCommerce Store API via `window.wp.data`
- Clears browser storage on form reset
- Redirects with cache-busting on "Add Another"

**AJAX Response Format:**
```javascript
{
  success: true,
  data: {
    message: "Product added to cart!",
    cart_url: "https://example.com/cart",
    fragments: {
      // WooCommerce cart fragments
    }
  }
}
```

#### `price-calculator-editor.js`
Handles field configuration in Gravity Forms editor:

**Dependencies:** jQuery, Gravity Forms editor scripts

**Functions:**
- Populates field dropdowns with available form fields
- Stores field settings in GF field object
- Handles property changes
- Updates UI in real-time

## Modern Architecture

### Alpine.js Reactive Framework

Version 2.1.2 introduced a complete refactor from jQuery to Alpine.js for a cleaner, more maintainable codebase.

#### Why Alpine.js?

**Before (jQuery):**
```javascript
// Imperative - manually manipulate DOM
$container.find('.gf-final-amount').text(finalPrice.toFixed(2));
if (isOnSale) {
    $regularPrice.show();
} else {
    $regularPrice.hide();
}
```

**After (Alpine.js):**
```html
<!-- Declarative - template defines behavior -->
<span x-text="finalPrice">0.00</span>
<span x-show="showRegularPrice" x-text="regularPrice">0.00</span>
```

```javascript
// Just update state - Alpine handles DOM
this.finalPrice = finalPriceValue.toFixed(2);
this.showRegularPrice = isOnSale && showSaleComparison;
```

**Benefits:**

## Admin Tools & Advanced Options

- Tools on the settings page:
  - View in Site Health → Info
  - Copy diagnostics (JSON of versions/config counts)
  - Clear confirmation messages
- Advanced settings:
  - Enable/disable REST add-to-basket endpoint (AJAX recommended)
  - Choose Alpine.js source (CDN by default, Local if bundled)

## Site Health

Find plugin diagnostics under Tools → Site Health → Info tab in the “GF → WC Cart” section.
- ✅ **Declarative** - HTML clearly shows what displays when
- ✅ **Reactive** - Change data, UI updates automatically
- ✅ **No jQuery** - Modern vanilla JavaScript
- ✅ **Maintainable** - 90 lines vs 180+ lines
- ✅ **Type Safe** - Clear state management
- ✅ **Debuggable** - No DOM hunting, just check state

### Template-Based Rendering

**PHP renders complete structure:**
```php
<div x-data="priceCalculator">
    <span class="gf-regular-price" x-show="showRegularPrice">
        <span class="gf-currency">£</span>
        <span x-text="regularPrice">0.00</span>
    </span>
    <strong class="gf-final-price">
        <span class="gf-currency">£</span>
        <span x-text="finalPrice">0.00</span>
    </strong>
</div>
```

**JavaScript only updates values:**
```javascript
calculate() {
    // Calculate
    const finalPrice = area * (isOnSale ? salePrice : regularPrice);

    // Update state (template auto-updates)
    this.finalPrice = finalPrice.toFixed(2);
    this.showRegularPrice = isOnSale;
}
```

No `.html()`, no `.append()`, no `.find()` - just clean reactive state.

## Customization

### CSS Custom Properties

The plugin uses CSS custom properties for easy theming:

```css
/* Override in your theme's CSS */
:root {
    --gf-color-primary: #3b82f6;
    --gf-ctrl-border-radius: 0.5rem;
    --gf-button-bg-color: #3b82f6;
    --gf-field-spacing: 1.5rem;
}
```

### Price Calculator Field Styles

Custom styles for the price calculator field:

```css
.gf-wc-price-calculator {
    /* Container styles */
}

.gf-wc-price-display {
    /* Price display styles */
}

.gf-wc-calculation-breakdown {
    /* Calculation breakdown styles */
}

.gf-wc-sale-comparison {
    /* Sale price comparison styles */
}
```

## Troubleshooting

### Price Not Calculating
**Symptom:** Price shows as £0.00 or doesn't update.

**Solutions:**
1. Check that width and height fields are number field types
2. Verify field IDs are correctly mapped in field settings
3. Open browser console and check for JavaScript errors
4. Ensure `price-calculator-field.js` is loading (check Network tab)
5. Verify the WooCommerce product has a valid price
6. Check that field IDs haven't changed

### Product Not Added to Cart
**Symptom:** "Add to Basket" shows error or nothing happens.

**Solutions:**
1. Verify WooCommerce is active
2. Check that the product exists and is published
3. Review form entry to ensure it was submitted successfully
4. Check browser console for errors
5. Check PHP error logs for detailed error messages
6. Verify nonce is valid (clear cache if using caching plugin)
7. Ensure WooCommerce cart is enabled

### Configuration Dashboard Shows Issues
**Symptom:** Forms appear with yellow warning indicator.

**Solutions:**
1. Click **Edit Form** to view specific errors
2. Follow the error message guidance
3. Common fixes:
   - Re-select the WooCommerce product
   - Re-map the width and height fields
   - Add missing number fields to the form
4. Re-save the form after making corrections
5. Refresh the dashboard to verify issues are resolved

### Admin Notices Won't Dismiss
**Symptom:** Warning notices keep appearing.

**Solution:**
- Configuration notices will automatically disappear when issues are resolved
- Dependency notices (missing Gravity Forms or WooCommerce) will persist until the required plugins are activated
- These notices are helpful and indicate real issues that need attention

### Buttons Not Appearing
**Symptom:** Dual submit buttons don't show up on the form.

**Solutions:**
1. Verify the form has a Price Calculator field configured
2. Check that WooCommerce is active
3. Ensure field IDs are correctly set in Price Calculator settings
4. Check browser console for JavaScript errors
5. Try clearing site cache

### AJAX Add to Basket Fails
**Symptom:** "Add to Basket" shows error message.

**Solutions:**
1. Check PHP error logs for detailed error messages
2. Verify nonce is valid (clear cache if using caching plugin)
3. Ensure WooCommerce cart is enabled
4. Check that product ID exists in WooCommerce
5. Verify form field IDs match configuration
6. Test with default WordPress theme to rule out theme conflicts

### Form Not Resetting After Add
**Symptom:** Form keeps previous values after clicking "Add Another Configuration".

**Solutions:**
1. Clear browser cache and cookies
2. Check browser console for JavaScript errors
3. Ensure localStorage/sessionStorage are not blocked by browser
4. Verify `dual-submit.js` is loading (check Network tab)
5. Test in incognito/private browsing mode
6. Check that page redirects with `?nocache=` parameter

### Mini Cart Not Updating
**Symptom:** Cart icon doesn't update after adding to basket.

**Solutions:**
1. **Verify Mini Cart Block**: Ensure WooCommerce Mini Cart block is in your header
   - Edit site header in Appearance → Editor
   - Look for `<!-- wp:woocommerce/mini-cart /-->`
2. Check browser console for JavaScript errors
3. Verify WooCommerce scripts are loading (`wc-add-to-cart-params`)
4. Test adding a regular WooCommerce product to isolate issue
5. Check if theme has custom cart implementation
6. For block themes: Mini Cart updates via WooCommerce Store API
7. For classic themes: Add traditional mini cart widget to sidebar

### Styling Issues
**Symptom:** Form or buttons look broken or unstyled.

**Solutions:**
1. Clear browser and server cache
2. Check that CSS files are loading (Network tab)
3. Verify no theme CSS conflicts (inspect element)
4. Ensure Gravity Forms CSS is not disabled
5. Try disabling theme custom styles temporarily

## Best Practices

### Security
- All AJAX requests are nonce-protected
- Form data is sanitized before processing
- WooCommerce security checks are maintained
- No raw `$_POST` access without validation
- Current user capabilities are checked for admin features

### Performance
- Race condition protection prevents duplicate requests
- Scripts only enqueue on pages with configured forms
- CSS uses native Gravity Forms Theme Layer (minimal HTTP requests)
- AJAX responses include WooCommerce fragments for efficient updates
- No unnecessary database queries

### Accessibility
- All interactive elements are keyboard accessible
- Focus states clearly visible
- Screen reader compatible
- Proper ARIA labels where needed
- WCAG 2.1 AA compliant

### Development
- Modern PHP 7.4+ features (typed properties, arrow functions)
- Composer autoloading (PSR-4)
- Clean, readable code with comprehensive comments
- Follows WordPress coding standards
- Proper namespacing and class organization

## Changelog

### Version 2.3.1 (2025-10-04)
- **Security Hardening**: All user input sanitized before cart/order storage (XSS prevention)
- **CSRF Protection**: Disabled REST `/add-to-basket` endpoint, AJAX remains nonce-protected
- **Alpine.js Expression Fix**: Removed `html_entity_decode()` breaking HTML attributes
- **Smart Quote Handling**: Extended Unicode replacement map for proper quote conversion
- **PSR-3 Logging**: Replaced all `error_log()` with structured Logger (respects `WP_DEBUG_LOG`)
- **Input Sanitization Fix**: Moved `sanitize_text_field()` after `parse_str()` to preserve complex values
- **Version Consistency**: Uses `BENHUGHES_GF_WC_VERSION` constant throughout
- **Alpine CDN Filter**: Added `gf_wc_alpine_src` filter for self-hosting Alpine.js (CSP support)
- **i18n Support**: All dual submit UI strings translatable via WordPress i18n
- **GF 2.9 Compatibility**: Updated CSS for native image choice fields
- **JavaScript Quality**: ESLint configured and passing (0 errors, 0 warnings)
- **Code Cleanup**: Removed all debug `console.log()` statements
- **Comprehensive Documentation**: Added MERGE-SUMMARY.md, ROADMAP.md, ROADMAP-FIXES-COMPLETED.md

### Version 2.3.0 (2025-10-03)
- **Stacked Badge-Style Price Display**: Ecommerce-optimized price layout with "Normal Price" and "Your Price" rows
- **Savings Percentage Badge**: Eye-catching "You Save X%!" badge with orange gradient
- **Enhanced Visual Hierarchy**: Green gradient for sale prices, strikethrough for regular prices
- **Dynamic Placeholder Updates**: Measurement unit changes automatically update input placeholders (100cm → 1000mm → 39.4in)
- **Responsive Price Display**: Mobile-optimized stacked layout with improved readability
- **CSS Organization**: Consolidated price calculator styles in shutters-theme.css

### Version 2.2.0 (2025-10-02)
- **Measurement Unit Field**: New custom field for dynamic unit selection (mm/cm/in)
- **Automatic Label Updates**: Width/drop field labels update with selected unit
- **Smart Constraint Conversion**: Min/max/step values convert automatically for each unit
- **Real-time Instructions**: Helper text updates with correct ranges and units
- **Self-contained Configuration**: Configure width/drop fields to control in the editor
- **Alpine.js Integration**: Reactive UI updates using data attributes

### Version 2.1.2 (2025-10-02)
- **Alpine.js Refactor**: Migrated from jQuery to modern Alpine.js reactive framework
- **Clean Template System**: PHP renders complete HTML template, JavaScript only updates values
- **Declarative UI**: No DOM manipulation - uses Alpine directives (`x-text`, `x-show`, `x-model`)
- **Improved Price Styling**: Bold red sale price (#dc2626), muted strikethrough regular price
- **Multi-Page Form Support**: Fixed price calculation on final page of multi-step forms
- **Production Ready**: Removed all debug console logs for clean production code
- **Better Maintainability**: 90 lines of clean Alpine.js vs 180+ lines of jQuery spaghetti

### Version 2.1.1 (2025-10-01)
- **Sale Price Display**: Show strikethrough regular price when product is on sale
- **Visual Price Comparison**: Customers see savings at a glance (e.g., ~~£120.00~~ **£108.00**)
- **Calculation Breakdown**: Optional display of width × height = area
- **Client-side Price Calculation**: Real-time price updates without AJAX calls
- **Improved Performance**: Removed unnecessary AJAX for price calculations
- **Enhanced Price Display**: Better formatting with prefix/suffix support

### Version 2.1.0 (2025-09-30)
- **Configuration Dashboard**: New admin page under Forms → Cart Integration
- **Real-time Validation**: Comprehensive validation for product/field IDs
- **Admin Notices**: Helpful warnings for missing dependencies and configuration issues
- **System Health**: Dashboard showing all configured forms with status indicators
- **Admin Toolbar**: Quick status indicator showing configuration health
- **User-Friendly UI**: Improved labels, removed abbreviations and technical jargon
- **Field Dropdowns**: Show labels + IDs for clarity, not just numbers
- **Visual Indicators**: Green checkmarks for valid, yellow warnings for issues
- **Contextual Help**: Helpful descriptions under each setting
- **Quick Start Guide**: Step-by-step instructions in dashboard
- **Quantity Selector**: Add multiple items (1-99) of same configuration
- **Smart Form Reset**: Clears browser storage for truly fresh form state
- **Block Theme Support**: Works with WooCommerce Mini Cart block and Store API
- **Dual Cart System**: Supports both modern blocks and legacy widgets
- **Cache-Busting Redirects**: Forces fresh page loads on form reset
- **Enhanced Cart Updates**: Triggers multiple WooCommerce events for reliability

### Version 2.0.0
- Complete rewrite using modern PHP 8.2+ features
- Implemented Gravity Forms Theme Layer API
- Added dual submit button functionality (Add to Basket / Pay Now)
- AJAX cart integration with fragments support
- Race condition protection for duplicate submissions
- Comprehensive error logging
- Security hardening (sanitization, nonces, capability checks)
- Responsive design improvements
- Accessibility enhancements (WCAG 2.1 AA)
- Custom Price Calculator field type
- Real-time price calculations
- Sale price support

## License

Proprietary — Copyright © Ben Hughes

## Support

For issues, questions, or feature requests, please contact the plugin developer.

---

**Made with ❤️ for custom WooCommerce integrations**
