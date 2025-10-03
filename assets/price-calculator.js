/**
 * Gravity Forms WooCommerce Price Calculator
 * Modern Alpine.js implementation
 *
 * @package BenHughes\GravityFormsWC
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('priceCalculator', function () {
        return {
            // Reactive state
            finalPrice: '0.00',
            regularPrice: '0.00',
            hiddenValue: '0',
            showRegularPrice: false,
            showCalculation: false,
            calculationText: '',
            savingsPercent: 0,
            selectedUnit: 'cm',

            // Configuration from PHP
            config: {},

            init() {
                // Get configuration from global scope
                this.config = window.gfWcPriceCalc || {};

                // Convert string values from PHP to proper types
                this.config.regularPrice = parseFloat(this.config.regularPrice) || 0;
                this.config.salePrice = parseFloat(this.config.salePrice) || 0;
                this.config.isOnSale = this.config.isOnSale === "1" || this.config.isOnSale === true;
                this.config.showSaleComparison = this.config.showSaleComparison === "1" || this.config.showSaleComparison === true;
                this.config.showCalculation = this.config.showCalculation === "1" || this.config.showCalculation === true;
                this.config.unitFieldId = parseInt(this.config.unitFieldId) || 0;

                // Set up listeners for width and drop fields after a brief delay
                // to ensure DOM is fully ready
                this.$nextTick(() => {
                    this.setupListeners();
                });
            },

            setupListeners() {
                const { formId, widthFieldId, dropFieldId, unitFieldId } = this.config;

                // Find width and drop input fields
                const widthInput = document.querySelector(`#input_${formId}_${widthFieldId}`);
                const dropInput = document.querySelector(`#input_${formId}_${dropFieldId}`);

                if (widthInput && dropInput) {
                    // Attach event listeners for real-time calculation
                    widthInput.addEventListener('input', () => this.calculate());
                    dropInput.addEventListener('input', () => this.calculate());
                }

                // Set up unit field listener if configured
                // Note: Measurement Unit field handles label and constraint updates
                // Price Calculator only needs to know selected unit for conversions
                if (unitFieldId > 0) {
                    // Get the selected unit radio button
                    const checkedUnit = document.querySelector(`input[name="input_${unitFieldId}"]:checked`);
                    if (checkedUnit) {
                        this.selectedUnit = checkedUnit.value;
                    }

                    // Listen for unit changes (for price recalculation only)
                    document.querySelectorAll(`input[name="input_${unitFieldId}"]`).forEach(radio => {
                        radio.addEventListener('change', (e) => {
                            this.selectedUnit = e.target.value;
                            this.calculate();
                        });
                    });
                }

                // Calculate immediately with current values
                // In multi-page forms, inputs may already have values from previous pages
                this.calculate();
            },

            calculate() {
                const { formId, widthFieldId, dropFieldId, productId, regularPrice, salePrice, isOnSale, showSaleComparison, showCalculation } = this.config;

                // Get width and drop input fields
                const widthInput = document.querySelector(`#input_${formId}_${widthFieldId}`);
                const dropInput = document.querySelector(`#input_${formId}_${dropFieldId}`);

                const widthRaw = parseFloat(widthInput?.value) || 0;
                const dropRaw = parseFloat(dropInput?.value) || 0;

                if (widthRaw === 0 || dropRaw === 0) {
                    this.clear();
                    return;
                }

                // Call backend AJAX to calculate price (SINGLE SOURCE OF TRUTH)
                fetch(this.config.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'gf_wc_calculate_price',
                        nonce: this.config.nonce,
                        width: widthRaw,
                        drop: dropRaw,
                        unit: this.selectedUnit,
                        product_id: productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const price = parseFloat(data.data.price);
                        const regularPriceCalc = parseFloat(data.data.regular_price);
                        const salePriceCalc = parseFloat(data.data.sale_price);
                        const area = parseFloat(data.data.area);

                        // Calculate savings percentage
                        let savingsPercent = 0;
                        if (regularPriceCalc > 0 && salePriceCalc > 0 && salePriceCalc < regularPriceCalc) {
                            savingsPercent = Math.round(((regularPriceCalc - salePriceCalc) / regularPriceCalc) * 100);
                        }

                        // Update reactive state (template auto-updates via Alpine.js)
                        this.finalPrice = price.toFixed(2);
                        this.regularPrice = regularPriceCalc.toFixed(2);
                        this.hiddenValue = price.toFixed(2);
                        this.showRegularPrice = isOnSale && showSaleComparison;
                        this.savingsPercent = savingsPercent;
                        this.showCalculation = showCalculation;
                        this.calculationText = showCalculation ? `(${widthRaw}${this.selectedUnit} × ${dropRaw}${this.selectedUnit} = ${area}m²)` : '';
                    }
                })
                .catch(error => {
                    console.error('Price calculation error:', error);
                    this.clear();
                });
            },

            clear() {
                this.finalPrice = '0.00';
                this.regularPrice = '0.00';
                this.hiddenValue = '0';
                this.showRegularPrice = false;
                this.savingsPercent = 0;
                this.showCalculation = false;
                this.calculationText = '';
            }
        };
    });
});
