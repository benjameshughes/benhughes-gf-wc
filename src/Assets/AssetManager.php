<?php
/**
 * Asset Manager
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Assets;

use BenHughes\GravityFormsWC\Integration\WooCommerceCart;

/**
 * Manages JavaScript enqueuing for Gravity Forms
 *
 * Note: CSS is now handled by the ShuttersTheme class via GF Theme Layer API
 */
class AssetManager {

    /**
     * Plugin directory URL
     *
     * @var string
     */
    private string $plugin_url;

    /**
     * Plugin version
     *
     * @var string
     */
    private string $version;

    /**
     * WooCommerce cart integration instance
     *
     * @var WooCommerceCart
     */
    private WooCommerceCart $cart_integration;

    /**
     * Constructor
     *
     * @param string            $plugin_url       Plugin directory URL.
     * @param string            $version          Plugin version.
     * @param WooCommerceCart   $cart_integration WooCommerce cart integration.
     */
    public function __construct( string $plugin_url, string $version, WooCommerceCart $cart_integration ) {
        $this->plugin_url        = $plugin_url;
        $this->version           = $version;
        $this->cart_integration  = $cart_integration;

        add_action( 'gform_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10, 2 );
    }

    /**
     * Enqueue scripts for Gravity Forms
     *
     * @param array $form        Form array.
     * @param bool  $is_ajax     Whether form is being submitted via AJAX.
     * @return void
     */
    public function enqueue_scripts( array $form, bool $is_ajax ): void {
        // Get calculator configuration for this form
        $config = $this->cart_integration->get_calculator_config( $form );

        if ( null === $config ) {
            return;
        }

        // Check if form has measurement_unit field
        $has_measurement_unit = $this->form_has_measurement_unit( $form );

        // Enqueue price calculator script FIRST (before Alpine.js)
        wp_enqueue_script(
            'gf-wc-price-calculator',
            $this->plugin_url . 'assets/price-calculator.js',
            [],
            $this->version,
            true
        );

        // Enqueue measurement unit script if field is present (before Alpine.js)
        if ( $has_measurement_unit ) {
            wp_enqueue_script(
                'gf-wc-measurement-unit',
                $this->plugin_url . 'assets/measurement-unit.js',
                [],
                $this->version,
                true
            );
        }

        // Enqueue Alpine.js AFTER our component definitions
        $alpine_dependencies = [ 'gf-wc-price-calculator' ];
        if ( $has_measurement_unit ) {
            $alpine_dependencies[] = 'gf-wc-measurement-unit';
        }

        /**
         * Filter Alpine.js source URL
         *
         * Allows self-hosting Alpine.js instead of using CDN.
         *
         * @since 2.3.0
         *
         * @param string $alpine_src Alpine.js script URL.
         *
         * @example
         * // Self-host Alpine.js
         * add_filter( 'gf_wc_alpine_src', function( $src ) {
         *     return plugin_dir_url( __FILE__ ) . 'assets/vendor/alpinejs/cdn.min.js';
         * } );
         */
        // Determine Alpine source from option (cdn|local), fallback to CDN
        $source_preference = get_option( 'gf_wc_alpine_source', 'cdn' );
        $default_src       = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
        $alpine_src        = $default_src;

        if ( 'local' === $source_preference ) {
            // Provide a local path if bundled; otherwise, fallback
            $local_candidate = $this->plugin_url . 'assets/vendor/alpinejs/cdn.min.js';
            $alpine_src      = $local_candidate;
        }

        // Allow filter override
        $alpine_src = apply_filters( 'gf_wc_alpine_src', $alpine_src );

        wp_enqueue_script(
            'alpinejs',
            $alpine_src,
            $alpine_dependencies,
            '3.x.x',
            true
        );
        // No defer needed - scripts load in footer with dependencies already set

        // Localize script with configuration and AJAX data
        wp_localize_script(
            'gf-wc-price-calculator',
            'gfWcPriceCalc',
            [
                'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
                'nonce'              => wp_create_nonce( 'gf_wc_price_calc' ),
                'formId'             => $config['formId'],
                'priceFieldId'       => $config['priceFieldId'],
                'widthFieldId'       => $config['widthFieldId'],
                'dropFieldId'        => $config['dropFieldId'],
                'unitFieldId'        => $config['unitFieldId'] ?? 0,
                'productId'          => $config['productId'],
                'currency'           => $config['currency'],
                'regularPrice'       => $config['regularPrice'] ?? 0.0,
                'salePrice'          => $config['salePrice'] ?? 0.0,
                'isOnSale'           => $config['isOnSale'] ?? false,
                'showSaleComparison' => $config['showSaleComparison'] ?? false,
                'showCalculation'    => $config['showCalculation'] ?? false,
                'pricePrefix'        => $config['pricePrefix'] ?? '',
                'priceSuffix'        => $config['priceSuffix'] ?? '',
            ]
        );

        // Enqueue dual submit buttons script
        wp_enqueue_script(
            'gf-wc-dual-submit',
            $this->plugin_url . 'assets/dual-submit.js',
            [ 'jquery' ],
            $this->version,
            true
        );

        // Localize dual submit script
        wp_localize_script(
            'gf-wc-dual-submit',
            'gfWcDualSubmit',
            [
                'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
                'nonce'             => wp_create_nonce( 'gf_wc_dual_submit' ),
                'formId'            => $config['formId'],
                'priceFieldId'      => $config['priceFieldId'],
                'widthFieldId'      => $config['widthFieldId'],
                'dropFieldId'       => $config['dropFieldId'],
                'unitFieldId'       => $config['unitFieldId'] ?? 0,
                'productId'         => $config['productId'],
                'addToBasketText'   => __( 'Add to Basket', 'gf-wc-bridge' ),
                'payNowText'        => __( 'Pay Now', 'gf-wc-bridge' ),
                'quantityLabel'     => __( 'Quantity:', 'gf-wc-bridge' ),
                'addingText'        => __( '⏳ Adding...', 'gf-wc-bridge' ),
                'errorAddToCart'    => __( 'Failed to add to cart', 'gf-wc-bridge' ),
                'errorTryAgain'     => __( 'Failed to add to cart. Please try again.', 'gf-wc-bridge' ),
                'cartCount'         => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
                'cartUrl'           => wc_get_cart_url(),
            ]
        );

    }

    /**
     * Check if form has measurement_unit field
     *
     * @param array $form Form array.
     * @return bool True if form has measurement_unit field.
     */
    private function form_has_measurement_unit( array $form ): bool {
        if ( empty( $form['fields'] ) ) {
            return false;
        }

        foreach ( $form['fields'] as $field ) {
            if ( 'measurement_unit' === $field->type ) {
                return true;
            }
        }

        return false;
    }
}
