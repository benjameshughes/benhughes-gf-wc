# Optional Linting Fixes

These are **non-critical warnings** that can be fixed to achieve a 100% clean lint result.

## Current Status: 0 Errors, 5 Warnings

---

## Fix 1: dual-submit.js Line 167

### Issue
Unused parameters in AJAX error callback

### Current Code
```javascript
error: (xhr, status, error) => {
    this.showMessage('Failed to add to cart. Please try again.', 'error');
    // Reset button on error
    $button.prop('disabled', false).html(originalText);
},
```

### Fixed Code (Option A - Prefix unused params)
```javascript
error: (_xhr, _status, _error) => {
    this.showMessage('Failed to add to cart. Please try again.', 'error');
    // Reset button on error
    $button.prop('disabled', false).html(originalText);
},
```

### Fixed Code (Option B - Use the error param)
```javascript
error: (xhr, status, error) => {
    console.error('AJAX error:', error);
    this.showMessage('Failed to add to cart. Please try again.', 'error');
    // Reset button on error
    $button.prop('disabled', false).html(originalText);
},
```

**Recommendation**: Option A (prefix with underscore) to indicate intentionally unused

---

## Fix 2: price-calculator.js Line 81

### Issue
Unused destructured variables `regularPrice` and `salePrice`

### Current Code
```javascript
const { formId, widthFieldId, dropFieldId, productId, regularPrice, salePrice, isOnSale, showSaleComparison, showCalculation } = this.config;
```

### Fixed Code (Remove unused variables)
```javascript
const { formId, widthFieldId, dropFieldId, productId, isOnSale, showSaleComparison, showCalculation } = this.config;
```

**Note**: These values are already available from `this.config` if needed later, and they're recalculated from the backend response anyway.

---

## How to Apply Fixes

### Option 1: Manual Edit
Edit the files directly with the changes above.

### Option 2: Run Auto-fix (won't fix these warnings)
```bash
npm run lint:js:fix
```

### Option 3: Leave As-Is
These are warnings, not errors. The code functions perfectly fine as-is.

---

## Verification

After applying fixes, run:
```bash
npm run lint:js
```

Expected result:
```
✨  Done in 0.5s
(no output = all checks passed)
```

---

## Priority Level: LOW

These warnings do not affect:
- ✅ Code functionality
- ✅ Alpine.js parsing
- ✅ Browser compatibility
- ✅ WordPress integration

They are purely for code cleanliness and best practices.
