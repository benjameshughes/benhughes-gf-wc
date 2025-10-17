<?php
/**
 * Main Plugin Class
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC;

use BenHughes\GravityFormsWC\Container\Container;
use BenHughes\GravityFormsWC\Container\ServiceProvider;
use BenHughes\GravityFormsWC\Fields\MeasurementUnit;
use BenHughes\GravityFormsWC\Fields\PriceCalculator;
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
     * Dependency injection container
     *
     * @var Container
     */
    private Container $container;

    /**
     * Service provider
     *
     * @var ServiceProvider
     */
    private ServiceProvider $service_provider;

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

        // Initialize dependency injection container
        $this->container        = new Container();
        $this->service_provider = new ServiceProvider(
            $this->container,
            $this->plugin_url,
            $this->version
        );

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'gform_loaded', [ $this, 'register_field' ], 5 );
        add_action( 'init', [ $this, 'init_components' ], 10 );
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
        // Register all services in the container
        $this->service_provider->register();

        // Boot services (instantiate those that need to run immediately)
        $this->service_provider->boot();
    }

    /**
     * Get dependency injection container
     *
     * @return Container
     */
    public function get_container(): Container {
        return $this->container;
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