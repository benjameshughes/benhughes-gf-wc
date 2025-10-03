<?php
/**
 * Main Plugin Class
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC;

use BenHughes\GravityFormsWC\Admin\AdminNotices;
use BenHughes\GravityFormsWC\Admin\AdminToolbar;
use BenHughes\GravityFormsWC\Admin\EditorScript;
use BenHughes\GravityFormsWC\Admin\FieldSettings;
use BenHughes\GravityFormsWC\Admin\SettingsPage;
use BenHughes\GravityFormsWC\Assets\AssetManager;
use BenHughes\GravityFormsWC\Fields\MeasurementUnit;
use BenHughes\GravityFormsWC\Fields\PriceCalculator;
use BenHughes\GravityFormsWC\Integration\WooCommerceCart;
use BenHughes\GravityFormsWC\Theme\ShuttersTheme;
use BenHughes\GravityFormsWC\Validation\ConfigValidator;
use GF_Fields;

/**
 * Main plugin bootstrap class
 */
class Plugin {

    /**
     * Plugin version
     *
     * @var string
     */
    private string $version;

    /**
     * Plugin directory path
     *
     * @var string
     */
    private string $plugin_path;

    /**
     * Plugin directory URL
     *
     * @var string
     */
    private string $plugin_url;

    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     *
     * @param string $plugin_file Main plugin file path.
     * @param string $version     Plugin version.
     * @return self
     */
    public static function get_instance( string $plugin_file, string $version ): self {
        if ( null === self::$instance ) {
            self::$instance = new self( $plugin_file, $version );
        }

        return self::$instance;
    }

    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file path.
     * @param string $version     Plugin version.
     */
    private function __construct( string $plugin_file, string $version ) {
        $this->version     = $version;
        $this->plugin_path = plugin_dir_path( $plugin_file );
        $this->plugin_url  = plugin_dir_url( $plugin_file );

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'gform_loaded', [ $this, 'register_field' ], 5 );
        add_action( 'plugins_loaded', [ $this, 'init_components' ] );
    }

    /**
     * Register custom Gravity Forms field
     *
     * @return void
     */
    public function register_field(): void {
        if ( ! class_exists( 'GF_Field_Number' ) || ! class_exists( 'GF_Field_Radio' ) ) {
            return;
        }

        GF_Fields::register( new PriceCalculator() );
        GF_Fields::register( new MeasurementUnit() );
    }

    /**
     * Initialize plugin components
     *
     * @return void
     */
    public function init_components(): void {
        // Initialize validator (used by multiple components)
        $validator = new ConfigValidator();

        // Initialize Gravity Forms theme
        new ShuttersTheme( $this->plugin_url, $this->version );

        // Initialize admin components
        if ( is_admin() ) {
            new AdminNotices( $validator );
            new SettingsPage( $validator );
            new FieldSettings();
            new EditorScript();
        }

        // Initialize admin toolbar (shows on both admin and frontend)
        new AdminToolbar( $validator );

        // Initialize WooCommerce integration
        $cart_integration = null;
        if ( class_exists( 'WooCommerce' ) ) {
            $cart_integration = new WooCommerceCart();
        }

        // Initialize asset management (requires cart integration for JS)
        if ( $cart_integration ) {
            new AssetManager( $this->plugin_url, $this->version, $cart_integration );
        }
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version(): string {
        return $this->version;
    }

    /**
     * Get plugin path
     *
     * @return string
     */
    public function get_plugin_path(): string {
        return $this->plugin_path;
    }

    /**
     * Get plugin URL
     *
     * @return string
     */
    public function get_plugin_url(): string {
        return $this->plugin_url;
    }
}