<?php
/**
 * WooCommerce Cart Integration
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Integration;

use BenHughes\GravityFormsWC\Calculation\PriceCalculator;
use BenHughes\GravityFormsWC\Enums\MeasurementUnit;
use BenHughes\GravityFormsWC\Services\CartService;
use GFFormDisplay;
use WC;

/**
 * Bridges Gravity Forms submissions to WooCommerce cart
 */
class WooCommerceCart {

    /**
     * Maximum number of sub-inputs to check for image choices
     *
     * @var int
     */
    private const MAX_SUB_INPUTS = 10;

    /**
     * Default product ID
     *
     * @var int
     */
    private int $product_id = 14;

    /**
     * Whether to recalculate price server-side
     *
     * @var bool
     */
    private bool $recalculate_price = true;

    /**
     * Price calculator instance
     *
     * @var PriceCalculator
     */
    private PriceCalculator $calculator;

    /**
     * Cart service instance
     *
     * @var CartService
     */
    private CartService $cart_service;

    /**
     * Initialize hooks
     *
     * @param PriceCalculator $calculator   Price calculator.
     * @param CartService     $cart_service Cart service.
     */
    public function __construct( PriceCalculator $calculator, CartService $cart_service ) {
        $this->calculator   = $calculator;
        $this->cart_service = $cart_service;
        // Dynamic form submission hooks - work with any form
        add_action( 'gform_after_submission', [ $this, 'add_to_cart' ], 10, 2 );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 4 );
        add_filter( 'woocommerce_add_cart_item', [ $this, 'set_cart_item_price' ], 10, 2 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_cart_item_data' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_order_item_meta' ], 10, 4 );
        add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_product_exists' ], 10, 3 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'update_cart_item_price' ], 10, 1 );

        // Calculate price dynamically - work with any form
        add_filter( 'gform_pre_render', [ $this, 'populate_calculated_price' ] );
        add_filter( 'gform_pre_validation', [ $this, 'populate_calculated_price' ] );
        add_filter( 'gform_pre_submission_filter', [ $this, 'populate_calculated_price' ] );

        // AJAX price calculation
        add_action( 'wp_ajax_gf_wc_calculate_price', [ $this, 'ajax_calculate_price' ] );
        add_action( 'wp_ajax_nopriv_gf_wc_calculate_price', [ $this, 'ajax_calculate_price' ] );

        // AJAX add to basket
        add_action( 'wp_ajax_gf_wc_add_to_basket', [ $this, 'ajax_add_to_basket' ] );
        add_action( 'wp_ajax_nopriv_gf_wc_add_to_basket', [ $this, 'ajax_add_to_basket' ] );

        // Display confirmation messages - work with any form
        add_filter( 'gform_pre_render', [ $this, 'display_confirmation_message' ] );
    }

    /**
     * Calculate and populate the price field
     *
     * @param array $form Form array.
     * @return array
     */
    public function populate_calculated_price( array $form ): array {
        foreach ( $form['fields'] as &$field ) {
            if ( $field->type === 'wc_price_calculator' ) {
                // Get dynamic field IDs from field settings
                $width_field_id = $field->widthFieldId ?? 30;
                $drop_field_id  = $field->dropFieldId ?? 23;
                $unit_field_id  = $field->unitFieldId ?? 0;
                $product_id     = (int) ( $field->wcProductId ?? $this->product_id );

                // Get width, drop, and unit from POST if available
                $width = rgpost( 'input_' . $width_field_id );
                $drop  = rgpost( 'input_' . $drop_field_id );
                $unit  = $unit_field_id > 0 ? rgpost( 'input_' . $unit_field_id ) : 'cm';

                if ( $width && $drop ) {
                    // Use the centralized calculator
                    $unit_enum   = MeasurementUnit::tryFrom( (string) $unit ) ?? MeasurementUnit::default();
                    $calculation = $this->calculator->calculate( (float) $width, (float) $drop, $unit_enum, $product_id );
                    $price       = $calculation->price;

                    $_POST[ 'input_' . $field->id ] = $price;
                    $field->defaultValue             = $price;
                }
            }
        }
        return $form;
    }

    /**
     * AJAX handler for price calculation
     *
     * @return void
     */
    public function ajax_calculate_price(): void {
        // Verify nonce
        check_ajax_referer( 'gf_wc_price_calc', 'nonce' );

        // Get parameters
        $width      = isset( $_POST['width'] ) ? (float) $_POST['width'] : 0;
        $drop       = isset( $_POST['drop'] ) ? (float) $_POST['drop'] : 0;
        $unit       = isset( $_POST['unit'] ) ? sanitize_text_field( wp_unslash( $_POST['unit'] ) ) : 'cm';
        $product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : $this->product_id;

        // Validate inputs
        if ( $width <= 0 || $drop <= 0 ) {
            wp_send_json_error( [ 'message' => 'Invalid dimensions' ] );
        }

        // Calculate price using our centralized calculator
        $unit_enum = MeasurementUnit::tryFrom( $unit ) ?? MeasurementUnit::default();
        $result    = $this->calculator->calculate( $width, $drop, $unit_enum, $product_id );

        // Return response with all calculation details - use toArray() for compatibility
        wp_send_json_success( [
            'price'         => number_format( $result->price, 2, '.', '' ),
            'regular_price' => number_format( $result->regularPrice, 2, '.', '' ),
            'sale_price'    => number_format( $result->salePrice, 2, '.', '' ),
            'is_on_sale'    => $result->isOnSale,
            'area'          => number_format( $result->areaM2, 2, '.', '' ),
            'width_cm'      => number_format( $result->widthCm, 2, '.', '' ),
            'drop_cm'       => number_format( $result->dropCm, 2, '.', '' ),
        ] );
    }

    /**
     * AJAX handler for adding to basket without form submission
     *
     * @return void
     */
    public function ajax_add_to_basket(): void {
        // Verify nonce
        check_ajax_referer( 'gf_wc_dual_submit', 'nonce' );

        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            error_log( 'GF-WC: AJAX add to basket failed - WooCommerce not active' );
            wp_send_json_error( [ 'message' => __( 'WooCommerce is not active', 'gf-wc-bridge' ) ] );
        }

        // Get form data and sanitize
        $form_data = isset( $_POST['form_data'] ) ? sanitize_text_field( wp_unslash( $_POST['form_data'] ) ) : '';

        if ( empty( $form_data ) ) {
            error_log( 'GF-WC: AJAX add to basket failed - No form data provided' );
            wp_send_json_error( [ 'message' => __( 'No form data provided', 'gf-wc-bridge' ) ] );
        }

        // Parse form data
        parse_str( $form_data, $parsed_data );

        // Get product and field IDs
        $product_id     = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : $this->product_id;
        $width_field_id = isset( $_POST['width_field_id'] ) ? (int) $_POST['width_field_id'] : 30;
        $drop_field_id  = isset( $_POST['drop_field_id'] ) ? (int) $_POST['drop_field_id'] : 23;
        $price_field_id = isset( $_POST['price_field_id'] ) ? (int) $_POST['price_field_id'] : null;
        $unit_field_id  = isset( $_POST['unit_field_id'] ) ? (int) $_POST['unit_field_id'] : 0;
        $quantity       = isset( $_POST['quantity'] ) ? max( 1, (int) $_POST['quantity'] ) : 1;

        // Get measurement values
        $width_raw = isset( $parsed_data[ 'input_' . $width_field_id ] ) ? floatval( $parsed_data[ 'input_' . $width_field_id ] ) : 0;
        $drop_raw  = isset( $parsed_data[ 'input_' . $drop_field_id ] ) ? floatval( $parsed_data[ 'input_' . $drop_field_id ] ) : 0;
        $unit      = $unit_field_id > 0 && isset( $parsed_data[ 'input_' . $unit_field_id ] ) ? $parsed_data[ 'input_' . $unit_field_id ] : 'cm';

        // Calculate using our centralized calculator (SINGLE SOURCE OF TRUTH)
        $unit_enum   = MeasurementUnit::tryFrom( $unit ) ?? MeasurementUnit::default();
        $calculation = $this->calculator->calculate( $width_raw, $drop_raw, $unit_enum, $product_id );

        // Debug logging
        error_log( sprintf(
            'GF-WC AJAX: Calculating price - Width: %s%s, Drop: %s%s, Product: %d, Result: £%s',
            $width_raw,
            $unit,
            $drop_raw,
            $unit,
            $product_id,
            number_format( $calculation->price, 2 )
        ) );

        // Get the frontend-submitted price for validation
        $frontend_price = $price_field_id && isset( $parsed_data[ 'input_' . $price_field_id ] )
            ? floatval( $parsed_data[ 'input_' . $price_field_id ] )
            : 0;

        // Security validation: Compare frontend vs backend price
        if ( $frontend_price > 0 && abs( $frontend_price - $calculation->price ) > 0.01 ) {
            // Price mismatch - frontend may have been tampered with
            error_log( sprintf(
                'GF-WC: Price mismatch detected. Frontend: £%s, Backend: £%s',
                number_format( $frontend_price, 2 ),
                number_format( $calculation->price, 2 )
            ) );
        }

        // Collect custom data
        $custom_data = [
            'width'         => $width_raw . $unit,  // Display as entered (e.g., "60in")
            'drop'          => $drop_raw . $unit,   // Display as entered (e.g., "72in")
            'style'         => $this->get_parsed_value( $parsed_data, '31' ),
            'louvre_size'   => $this->get_parsed_value( $parsed_data, '33' ),
            'bar_type'      => $this->get_parsed_value( $parsed_data, '36' ),
            'frame_type'    => $this->get_parsed_value( $parsed_data, '39' ),
            'frame_options' => $this->get_parsed_value( $parsed_data, '41' ),
            'position'      => $this->get_parsed_value( $parsed_data, '45' ),
            'color'         => $this->get_parsed_value( $parsed_data, '49' ),
            // Hidden meta data for production (prefixed with _ to hide from customer)
            '_measurement_unit' => $unit,
            '_width_cm'         => number_format( $calculation->widthCm, 2, '.', '' ),
            '_drop_cm'          => number_format( $calculation->dropCm, 2, '.', '' ),
            '_area_m2'          => number_format( $calculation->areaM2, 4, '.', '' ),
            // Store the calculated prices (single source of truth)
            'gf_total'          => number_format( $calculation->price, 2, '.', '' ),
            'gf_regular_price'  => number_format( $calculation->regularPrice, 2, '.', '' ),
            'gf_sale_price'     => $calculation->isOnSale ? number_format( $calculation->salePrice, 2, '.', '' ) : '',
            'gf_is_on_sale'     => $calculation->isOnSale ? '1' : '0',
        ];

        // Remove empty values
        $custom_data = array_filter( $custom_data, function ( $value ) {
            return ! empty( $value );
        } );

        $custom_data['product_id'] = $product_id;

        // Add to cart with quantity
        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            $quantity,
            0,
            [],
            $custom_data
        );

        if ( ! $cart_item_key ) {
            wp_send_json_error( [ 'message' => __( 'Failed to add product to cart', 'gf-wc-bridge' ) ] );
        }

        // Calculate cart totals
        WC()->cart->calculate_totals();

        // Get cart count for response
        $cart_count = WC()->cart->get_cart_contents_count();

        // Get cart hash for WooCommerce
        $cart_hash = WC()->cart->get_cart_hash();

        // Let WooCommerce and theme handle fragment generation via their filter
        // We don't hardcode selectors - WooCommerce's get_refreshed_fragments endpoint handles this
        $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', [] );

        // Return success with all cart data
        wp_send_json_success( [
            'message'    => __( 'Product added to cart! Configure another or checkout.', 'gf-wc-bridge' ),
            'cart_count' => $cart_count,
            'cart_url'   => wc_get_cart_url(),
            'cart_hash'  => $cart_hash,
            'fragments'  => $fragments,
        ] );
    }

    /**
     * Get parsed value from form data (handles sub-inputs)
     *
     * @param array $parsed_data Parsed form data.
     * @param mixed $field_id    Field ID.
     * @return string
     */
    private function get_parsed_value( array $parsed_data, $field_id ): string {
        // Try parent field first
        if ( isset( $parsed_data[ 'input_' . $field_id ] ) && ! empty( $parsed_data[ 'input_' . $field_id ] ) ) {
            return $parsed_data[ 'input_' . $field_id ];
        }

        // Check sub-inputs (31.1, 31.2, etc.)
        for ( $i = 1; $i <= 10; $i++ ) {
            $key = 'input_' . $field_id . '_' . $i;
            if ( isset( $parsed_data[ $key ] ) && ! empty( $parsed_data[ $key ] ) ) {
                return $parsed_data[ $key ];
            }
        }

        return '';
    }

    /**
     * Get calculator field configuration for JavaScript
     *
     * @param array $form Form array.
     * @return array|null Configuration array or null if no calculator field found.
     */
    public function get_calculator_config( array $form ): ?array {
        // Find the price calculator field
        foreach ( $form['fields'] as $field ) {
            if ( $field->type === 'wc_price_calculator' ) {
                $product_id = (int) ( $field->wcProductId ?? $this->product_id );

                // Get product pricing from calculator
                $pricing = $this->calculator->get_product_pricing( $product_id );

                return [
                    'formId'             => $form['id'],
                    'priceFieldId'       => $field->id,
                    'widthFieldId'       => $field->widthFieldId ?? 30,
                    'dropFieldId'        => $field->dropFieldId ?? 23,
                    'unitFieldId'        => $field->unitFieldId ?? 0,
                    'productId'          => $product_id,
                    'currency'           => $field->currencySymbol ?? '£',
                    'regularPrice'       => $pricing['regular_price'],
                    'salePrice'          => $pricing['sale_price'],
                    'isOnSale'           => $pricing['is_on_sale'],
                    'showSaleComparison' => $field->showSaleComparison ?? false,
                    'showCalculation'    => $field->showCalculation ?? false,
                    'pricePrefix'        => $field->pricePrefix ?? '',
                    'priceSuffix'        => $field->priceSuffix ?? '',
                ];
            }
        }

        return null;
    }

    /**
     * Validate product exists before adding to cart
     *
     * @param bool $passed     Whether validation passed.
     * @param int  $product_id Product ID.
     * @param int  $quantity   Quantity.
     * @return bool
     */
    public function validate_product_exists( bool $passed, int $product_id, int $quantity ): bool {
        if ( $product_id === $this->product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                wc_add_notice( __( 'Product does not exist. Please configure the product ID in the plugin.', 'gf-wc-bridge' ), 'error' );
                return false;
            }
        }
        return $passed;
    }

    /**
     * Get image choice field value (handles sub-inputs)
     *
     * @param array $entry    Entry array.
     * @param mixed $field_id Field ID.
     * @return string
     */
    private function get_image_choice_value( array $entry, $field_id ): string {
        // Try parent field first
        $value = rgar( $entry, $field_id );
        if ( ! empty( $value ) ) {
            return $value;
        }

        // Check sub-inputs (31.1, 31.2, etc.)
        for ( $i = 1; $i <= 10; $i++ ) {
            $value = rgar( $entry, $field_id . '.' . $i );
            if ( ! empty( $value ) ) {
                return $value;
            }
        }

        return '';
    }

    /**
     * Add form submission to WooCommerce cart
     *
     * @param array $entry Entry array.
     * @param array $form  Form array.
     * @return void
     */
    public function add_to_cart( array $entry, array $form ): void {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Find the price calculator field to get dynamic settings
        $price_calculator_field = null;
        foreach ( $form['fields'] as $field ) {
            if ( $field->type === 'wc_price_calculator' ) {
                $price_calculator_field = $field;
                break;
            }
        }

        // Get dynamic field IDs and product ID
        $width_field_id = $price_calculator_field->widthFieldId ?? 30;
        $drop_field_id  = $price_calculator_field->dropFieldId ?? 23;
        $unit_field_id  = $price_calculator_field->unitFieldId ?? 0;
        $product_id     = (int) ( $price_calculator_field->wcProductId ?? $this->product_id );

        // Get measurement values
        $width_raw = floatval( rgar( $entry, $width_field_id ) );
        $drop_raw  = floatval( rgar( $entry, $drop_field_id ) );
        $unit      = $unit_field_id > 0 ? rgar( $entry, $unit_field_id ) : 'cm';

        // Calculate using our centralized calculator
        $unit_enum   = MeasurementUnit::tryFrom( $unit ) ?? MeasurementUnit::default();
        $calculation = $this->calculator->calculate( $width_raw, $drop_raw, $unit_enum, $product_id );

        // Debug logging
        error_log( sprintf(
            'GF-WC Form Submission: Calculating price - Width: %s%s, Drop: %s%s, Product: %d, Result: £%s',
            $width_raw,
            $unit,
            $drop_raw,
            $unit,
            $product_id,
            number_format( $calculation->price, 2 )
        ) );

        // Collect shutter configuration data with actual field IDs from your form
        $custom_data = [
            'width'         => $width_raw . $unit,  // Store with unit (e.g., "73.8cm" or "29in")
            'drop'          => $drop_raw . $unit,
            'style'         => $this->get_image_choice_value( $entry, '31' ),
            'louvre_size'   => $this->get_image_choice_value( $entry, '33' ),
            'bar_type'      => $this->get_image_choice_value( $entry, '36' ),
            'frame_type'    => $this->get_image_choice_value( $entry, '39' ),
            'frame_options' => $this->get_image_choice_value( $entry, '41' ),
            'position'      => $this->get_image_choice_value( $entry, '45' ),
            'color'         => $this->get_image_choice_value( $entry, '49' ),
            // Hidden meta data for production (prefixed with _ to hide from customer)
            '_measurement_unit' => $unit,
            '_width_cm'         => number_format( $calculation->widthCm, 2, '.', '' ),
            '_drop_cm'          => number_format( $calculation->dropCm, 2, '.', '' ),
            '_area_m2'          => number_format( $calculation->areaM2, 4, '.', '' ),
        ];

        // Quantity is always 1 (field 46 is for panel count display, not cart quantity)
        $quantity = 1;

        // Remove empty values from custom data
        $custom_data = array_filter(
            $custom_data,
            function ( $value ) {
                return ! empty( $value );
            }
        );

        // Add entry ID and total for reference
        $custom_data['gf_entry_id'] = $entry['id'];

        // Use the calculated prices from our calculator (SINGLE SOURCE OF TRUTH)
        $custom_data['gf_total']         = number_format( $calculation->price, 2, '.', '' );
        $custom_data['gf_regular_price'] = number_format( $calculation->regularPrice, 2, '.', '' );
        $custom_data['gf_sale_price']    = $calculation->isOnSale ? number_format( $calculation->salePrice, 2, '.', '' ) : '';
        $custom_data['gf_is_on_sale']    = $calculation->isOnSale ? '1' : '0';
        $custom_data['product_id']       = $product_id;

        // Add to cart
        WC()->cart->add_to_cart(
            $product_id,
            $quantity,
            0,
            [],
            $custom_data
        );

        // Check which action was requested (pay_now or add_to_basket)
        $submit_action = rgar( $entry, 'submit_action' ) ?: 'pay_now';

        if ( $submit_action === 'add_to_basket' ) {
            // Stay on page and show confirmation
            $this->add_confirmation_message( $form );
            $this->redirect_to_form_page( $form );
        } else {
            // Redirect to cart (default behavior)
            wp_redirect( wc_get_cart_url() );
            exit;
        }
    }

    /**
     * Add confirmation message after adding to cart
     *
     * @param array $form Form array.
     * @return void
     */
    private function add_confirmation_message( array $form ): void {
        // Set transient for confirmation message
        set_transient(
            'gf_wc_cart_confirmation_' . $form['id'],
            [
                'message' => __( 'Product added to cart! Configure another or checkout.', 'gf-wc-bridge' ),
                'type'    => 'success',
            ],
            30 // 30 seconds
        );
    }

    /**
     * Redirect back to form page
     *
     * @param array $form Form array.
     * @return void
     */
    private function redirect_to_form_page( array $form ): void {
        // Get the current page URL
        $redirect_url = wp_get_referer();

        if ( ! $redirect_url ) {
            $redirect_url = home_url();
        }

        // Add success parameter
        $redirect_url = add_query_arg( 'gf_wc_added', '1', $redirect_url );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Display confirmation message on form
     *
     * @param array $form Form array.
     * @return array
     */
    public function display_confirmation_message( array $form ): array {
        // Check for confirmation transient
        $confirmation = get_transient( 'gf_wc_cart_confirmation_' . $form['id'] );

        if ( $confirmation && isset( $_GET['gf_wc_added'] ) ) {
            // Delete transient so it doesn't show again
            delete_transient( 'gf_wc_cart_confirmation_' . $form['id'] );

            // Add inline script to show message
            add_action(
                'wp_footer',
                function () use ( $confirmation ) {
                    ?>
                    <script>
                    (function($) {
                        $(document).ready(function() {
                            // Create message element
                            var message = $('<div></div>')
                                .addClass('gf-wc-cart-message gf-wc-cart-message--success')
                                .html('<?php echo esc_js( $confirmation['message'] ); ?>');

                            // Insert before form
                            $('.gform_wrapper').first().before(message);

                            // Scroll to top
                            $('html, body').animate({ scrollTop: 0 }, 300);

                            // Auto-hide after 5 seconds
                            setTimeout(function() {
                                message.fadeOut(300, function() {
                                    $(this).remove();
                                });
                            }, 5000);
                        });
                    })(jQuery);
                    </script>
                    <?php
                }
            );
        }

        return $form;
    }

    /**
     * Add custom data to cart item (needed for cart persistence)
     *
     * @param array $cart_item_data Cart item data.
     * @param int   $product_id     Product ID.
     * @param int   $variation_id   Variation ID.
     * @param int   $quantity       Quantity.
     * @return array
     */
    public function add_cart_item_data( array $cart_item_data, int $product_id, int $variation_id, int $quantity ): array {
        return $cart_item_data;
    }

    /**
     * Set the custom price when item is added to cart
     *
     * Simple now - just use the pre-calculated and validated price from AJAX handler
     * The PriceCalculator class already did the work in ajax_add_to_basket()
     *
     * @param array  $cart_item     Cart item.
     * @param string $cart_item_key Cart item key.
     * @return array
     */
    public function set_cart_item_price( array $cart_item, string $cart_item_key ): array {
        // Only process items with our calculated price (works for both AJAX and form submission)
        if ( empty( $cart_item['gf_total'] ) ) {
            return $cart_item;
        }

        // Get calculated prices
        $is_on_sale    = ! empty( $cart_item['gf_is_on_sale'] ) && $cart_item['gf_is_on_sale'] === '1';
        $regular_price = ! empty( $cart_item['gf_regular_price'] ) ? (float) $cart_item['gf_regular_price'] : 0.0;
        $sale_price    = ! empty( $cart_item['gf_sale_price'] ) ? (float) $cart_item['gf_sale_price'] : 0.0;
        $price         = (float) $cart_item['gf_total'];

        // Debug logging
        error_log( sprintf(
            'GF-WC set_cart_item_price: regular=%s, sale=%s, final=%s, is_on_sale=%s',
            number_format( $regular_price, 2 ),
            number_format( $sale_price, 2 ),
            number_format( $price, 2 ),
            $is_on_sale ? 'yes' : 'no'
        ) );

        if ( $price > 0 ) {
            // Set prices on product to display strikethrough in cart
            if ( $is_on_sale && $sale_price > 0 ) {
                // Product on sale: set both regular and sale price
                $cart_item['data']->set_regular_price( $regular_price );
                $cart_item['data']->set_sale_price( $sale_price );
                $cart_item['data']->set_price( $sale_price );
                $cart_item['custom_price']         = $sale_price;
                $cart_item['custom_regular_price'] = $regular_price;
            } else {
                // Product not on sale: just set regular price
                $cart_item['data']->set_price( $regular_price );
                $cart_item['custom_price'] = $regular_price;
            }
        }

        return $cart_item;
    }

    /**
     * Update cart item prices (called during cart calculation)
     *
     * @param \WC_Cart $cart Cart object.
     * @return void
     */
    public function update_cart_item_price( $cart ): void {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            // Only process items with our custom price
            if ( ! empty( $cart_item['custom_price'] ) ) {
                // If we have both regular and sale price, set them
                if ( ! empty( $cart_item['custom_regular_price'] ) ) {
                    $cart_item['data']->set_regular_price( $cart_item['custom_regular_price'] );
                    $cart_item['data']->set_sale_price( $cart_item['custom_price'] );
                }
                $cart_item['data']->set_price( $cart_item['custom_price'] );
            }
        }
    }

    /**
     * Display custom data in cart
     *
     * @param array $item_data Item data.
     * @param array $cart_item Cart item.
     * @return array
     */
    public function display_cart_item_data( array $item_data, array $cart_item ): array {
        // Define human-readable labels for your fields
        $field_labels = [
            'width'         => 'Width',
            'drop'          => 'Drop',
            'style'         => 'Shutter Style',
            'louvre_size'   => 'Louvre Size',
            'bar_type'      => 'Bar Type',
            'frame_type'    => 'Frame Type',
            'frame_options' => 'Frame Options',
            'position'      => 'Position',
            'color'         => 'Color',
        ];

        foreach ( $field_labels as $key => $label ) {
            if ( ! empty( $cart_item[ $key ] ) ) {
                $item_data[] = [
                    'key'     => $label,
                    'value'   => $cart_item[ $key ],
                    'display' => $cart_item[ $key ],
                ];
            }
        }

        return $item_data;
    }

    /**
     * Save custom data to order meta
     *
     * @param \WC_Order_Item_Product $item          Order item.
     * @param string                 $cart_item_key Cart item key.
     * @param array                  $values        Cart item values.
     * @param \WC_Order              $order         Order object.
     * @return void
     */
    public function save_order_item_meta( $item, string $cart_item_key, array $values, $order ): void {
        $fields_to_save = [
            'width',
            'drop',
            'style',
            'louvre_size',
            'bar_type',
            'frame_type',
            'frame_options',
            'position',
            'color',
        ];

        foreach ( $fields_to_save as $key ) {
            if ( ! empty( $values[ $key ] ) ) {
                // Use human-readable labels
                $labels = [
                    'width'         => 'Width (cm)',
                    'drop'          => 'Drop (cm)',
                    'style'         => 'Shutter Style',
                    'louvre_size'   => 'Louvre Size',
                    'bar_type'      => 'Bar Type',
                    'frame_type'    => 'Frame Type',
                    'frame_options' => 'Frame Options',
                    'position'      => 'Position',
                    'color'         => 'Color',
                ];

                $item->add_meta_data( $labels[ $key ] ?? $key, $values[ $key ], true );
            }
        }

        // Save Gravity Forms entry ID as hidden meta (visible to admin, not customer)
        if ( ! empty( $values['gf_entry_id'] ) ) {
            $entry_id = $values['gf_entry_id'];
            $item->add_meta_data( '_gf_entry_id', $entry_id, true );

            // Get form ID from entry (will be used for entry URL)
            if ( class_exists( 'GFAPI' ) ) {
                $entry = \GFAPI::get_entry( $entry_id );
                if ( $entry && ! is_wp_error( $entry ) ) {
                    $form_id = $entry['form_id'];
                    $entry_url = admin_url( 'admin.php?page=gf_entries&view=entry&id=' . $form_id . '&lid=' . $entry_id );
                    $item->add_meta_data( '_gf_entry_url', $entry_url, true );
                }
            }
        }
    }
}