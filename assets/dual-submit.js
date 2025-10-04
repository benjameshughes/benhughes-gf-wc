/**
 * Dual Submit Buttons Handler
 * Handles "Add to Basket" (AJAX) and "Pay Now" (form submission) actions
 *
 * @package BenHughes\GravityFormsWC
 */

(function ($) {
    'use strict';

    /**
     * Dual Submit Handler
     */
    const DualSubmit = {
        /**
         * Flag to prevent multiple simultaneous AJAX requests
         */
        isProcessing: false,

        /**
         * Initialize
         */
        init() {
            if (typeof gfWcDualSubmit === 'undefined') {
                return;
            }

            this.config = gfWcDualSubmit;
            this.setupDualButtons();
        },

        /**
         * Setup dual submit buttons
         */
        setupDualButtons() {
            const { formId } = this.config;
            const $form = $(`#gform_${formId}`);

            if (!$form.length) {
                return;
            }

            // Find the submit button container
            const $footer = $form.find('.gform_footer, .gform_page_footer').last();
            const $submitButton = $footer.find('input[type="submit"]');

            if (!$submitButton.length) {
                return;
            }

            // Hide the original submit button
            $submitButton.hide();

            // Create button container
            const $buttonContainer = $('<div></div>')
                .addClass('gf-wc-dual-submit')
                .insertAfter($submitButton);

            // Add quantity selector
            const $quantityWrapper = $('<div></div>')
                .addClass('gf-wc-quantity-wrapper')
                .html(`
                    <label for="gf-wc-quantity-${formId}">${this.config.quantityLabel}</label>
                    <input type="number"
                           id="gf-wc-quantity-${formId}"
                           class="gf-wc-quantity"
                           min="1"
                           max="99"
                           value="1"
                           step="1" />
                `);

            // Create initial "Add to Basket" button
            const $addToBasket = $('<button></button>')
                .attr('type', 'button')
                .addClass('gform_button gf-wc-button-add')
                .html(`🛒 ${this.config.addToBasketText}`)
                .on('click', (e) => this.handleAddToBasket(e, $form));

            // Append quantity and button
            $buttonContainer.append($quantityWrapper).append($addToBasket);

            // Store references
            this.$buttonContainer = $buttonContainer;
            this.$submitButton = $submitButton;
            this.$form = $form;

            // Show cart item count if available
            if (this.config.cartCount > 0) {
                this.showCartCount($buttonContainer);
            }
        },

        /**
         * Handle "Add to Basket" click (AJAX - no page reload)
         */
        handleAddToBasket(e, $form) {
            e.preventDefault();

            // Prevent multiple simultaneous requests
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            // Show loading state
            const $button = $(e.currentTarget);
            const originalText = $button.html();
            $button.prop('disabled', true).html(this.config.addingText);

            // Get quantity value
            const quantity = parseInt($form.find('.gf-wc-quantity').val()) || 1;

            // Serialize form data
            const formData = $form.serialize();

            // Send AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gf_wc_add_to_basket',
                    nonce: this.config.nonce,
                    form_data: formData,
                    product_id: this.config.productId,
                    width_field_id: this.config.widthFieldId,
                    drop_field_id: this.config.dropFieldId,
                    price_field_id: this.config.priceFieldId,
                    unit_field_id: this.config.unitFieldId || 0,
                    quantity: quantity,
                },
                success: (response) => {
                    if (response.success) {
                        // Update our own cart count display
                        this.updateCartCount(response.data.cart_count, response.data.cart_url);

                        // Show success message
                        this.showMessage(response.data.message, 'success');

                        // Switch to post-add buttons (Add Another / Checkout)
                        this.showPostAddButtons($form, response.data.cart_url);

                        // Scroll to top of form
                        $('html, body').animate({ scrollTop: $form.offset().top - 100 }, 300);

                        // Trigger events for WooCommerce to update its UI
                        // This works for both legacy widgets and modern React blocks
                        $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash, $button]);
                        $(document.body).trigger('wc-blocks_added_to_cart');

                        // For block-based Mini Cart, invalidate the cart store to force refresh
                        if (window.wp?.data?.dispatch('wc/store/cart')) {
                            try {
                                // Invalidate resolution to force re-fetch from API
                                window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore();
                            } catch (e) {
                                // Fallback: just let the events handle it
                            }
                        }
                    } else {
                        this.showMessage(response.data.message || this.config.errorAddToCart, 'error');
                        // Reset button on error
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: (_xhr, _status, _error) => {
                    this.showMessage(this.config.errorTryAgain, 'error');
                    // Reset button on error
                    $button.prop('disabled', false).html(originalText);
                },
                complete: () => {
                    // Reset processing flag
                    this.isProcessing = false;
                },
            });
        },

        /**
         * Show post-add buttons (Add Another / Proceed to Checkout)
         */
        showPostAddButtons($form, cartUrl) {
            const $container = this.$buttonContainer;

            // Hide quantity selector
            $container.find('.gf-wc-quantity-wrapper').hide();

            // Clear existing buttons
            $container.find('button').remove();

            // Create "Add Another Configuration" button
            const $addAnother = $('<button></button>')
                .attr('type', 'button')
                .addClass('gform_button gf-wc-button-secondary')
                .html('➕ Add Another Configuration')
                .on('click', (e) => {
                    e.preventDefault();
                    this.resetToInitialState($form);
                });

            // Create "Proceed to Checkout" button
            const $checkout = $('<button></button>')
                .attr('type', 'button')
                .addClass('gform_button gf-wc-button-primary')
                .html('Proceed to Checkout →')
                .on('click', (e) => {
                    e.preventDefault();
                    window.location.href = cartUrl;
                });

            // Append new buttons
            $container.append($addAnother).append($checkout);
        },

        /**
         * Reset to initial state (reload page for fresh form)
         */
        resetToInitialState($form) {
            const formId = $form.attr('id').replace('gform_', '');

            // Clear Gravity Forms stored state from localStorage/sessionStorage
            try {
                // Clear all GF-related storage for this form
                Object.keys(localStorage).forEach(key => {
                    if (key.includes('gform') || key.includes(`form_${formId}`)) {
                        localStorage.removeItem(key);
                    }
                });

                Object.keys(sessionStorage).forEach(key => {
                    if (key.includes('gform') || key.includes(`form_${formId}`)) {
                        sessionStorage.removeItem(key);
                    }
                });
            } catch (e) {
                console.error('Failed to clear storage:', e);
            }

            // Use replace instead of reload to avoid keeping cache
            window.location.replace(window.location.pathname + '?nocache=' + Date.now());
        },

        /**
         * Show success/error message
         */
        showMessage(message, type) {
            // Remove existing messages
            $('.gf-wc-cart-message').remove();

            // Create message element
            const $message = $('<div></div>')
                .addClass(`gf-wc-cart-message gf-wc-cart-message--${type}`)
                .html(message);

            // Insert before form
            $('.gform_wrapper').first().before($message);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                $message.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Update cart count display
         */
        updateCartCount(count, cartUrl) {
            const $cartInfo = $('.gf-wc-cart-info');

            if (count > 0) {
                if ($cartInfo.length) {
                    $cartInfo.html(`<a href="${cartUrl}">${count} item(s) in cart</a>`);
                } else {
                    const $newCartInfo = $('<div></div>')
                        .addClass('gf-wc-cart-info')
                        .html(`<a href="${cartUrl}">${count} item(s) in cart</a>`);
                    $('.gf-wc-dual-submit').after($newCartInfo);
                }
            }

            // Update config for next add
            this.config.cartCount = count;
            this.config.cartUrl = cartUrl;
        },

        /**
         * Show cart item count
         */
        showCartCount($container) {
            const { cartCount, cartUrl } = this.config;

            const $cartInfo = $('<div></div>')
                .addClass('gf-wc-cart-info')
                .html(`<a href="${cartUrl}">${cartCount} item(s) in cart</a>`);

            $container.after($cartInfo);
        },
    };

    // Initialize on document ready
    $(document).ready(() => {
        DualSubmit.init();
    });
})(jQuery);