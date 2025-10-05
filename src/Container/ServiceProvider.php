<?php
/**
 * Service Provider
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Container;

use BenHughes\GravityFormsWC\Addons\WooCommerceFeedAddon;
use BenHughes\GravityFormsWC\Admin\AdminNotices;
use BenHughes\GravityFormsWC\Admin\AdminToolbar;
use BenHughes\GravityFormsWC\Admin\PluginLinks;
use BenHughes\GravityFormsWC\Admin\EditorScript;
use BenHughes\GravityFormsWC\Admin\FieldSettings;
use BenHughes\GravityFormsWC\Admin\SettingsPage;
use BenHughes\GravityFormsWC\Admin\SiteHealth;
use BenHughes\GravityFormsWC\API\CalculatorController;
use BenHughes\GravityFormsWC\Assets\AssetManager;
use BenHughes\GravityFormsWC\Cache\CacheInterface;
use BenHughes\GravityFormsWC\Cache\WordPressCache;
use BenHughes\GravityFormsWC\Calculation\PriceCalculator;
use BenHughes\GravityFormsWC\Events\EventDispatcher;
use BenHughes\GravityFormsWC\Integration\WooCommerceCart;
use BenHughes\GravityFormsWC\Logging\Logger;
use BenHughes\GravityFormsWC\Repositories\CachedFormRepository;
use BenHughes\GravityFormsWC\Repositories\CachedProductRepository;
use BenHughes\GravityFormsWC\Repositories\FormRepositoryInterface;
use BenHughes\GravityFormsWC\Repositories\GravityFormsRepository;
use BenHughes\GravityFormsWC\Repositories\ProductRepositoryInterface;
use BenHughes\GravityFormsWC\Repositories\WooCommerceProductRepository;
use BenHughes\GravityFormsWC\Services\CartService;
use BenHughes\GravityFormsWC\Services\CacheInvalidation;
use BenHughes\GravityFormsWC\Theme\ShuttersTheme;
use BenHughes\GravityFormsWC\Validation\ConfigValidator;

/**
 * Registers all services in the container
 *
 * Single place to configure dependency injection
 */
class ServiceProvider {

	/**
	 * Container instance
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Plugin URL
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
	 * Constructor
	 *
	 * @param Container $container  DI container.
	 * @param string    $plugin_url Plugin directory URL.
	 * @param string    $version    Plugin version.
	 */
	public function __construct( Container $container, string $plugin_url, string $version ) {
		$this->container  = $container;
		$this->plugin_url = $plugin_url;
		$this->version    = $version;
	}

	/**
	 * Register all services
	 *
	 * @return void
	 */
	public function register(): void {
		$this->registerRepositories();
		$this->registerCoreServices();
		$this->registerAdminServices();
		$this->registerIntegrations();
	}

	/**
	 * Register repository services
	 *
	 * @return void
	 */
	private function registerRepositories(): void {
		// Form repository with caching decorator
		$this->container->register(
			FormRepositoryInterface::class,
			fn( Container $c ) => new CachedFormRepository(
				new GravityFormsRepository(),
				$c->get( CacheInterface::class ),
				3600 // 1 hour cache
			)
		);

		// Product repository with caching decorator
		$this->container->register(
			ProductRepositoryInterface::class,
			fn( Container $c ) => new CachedProductRepository(
				new WooCommerceProductRepository(),
				$c->get( CacheInterface::class ),
				3600 // 1 hour cache
			)
		);
	}

	/**
	 * Register core services
	 *
	 * @return void
	 */
    private function registerCoreServices(): void {
		// Cache (singleton)
		$this->container->register(
			CacheInterface::class,
			fn() => new WordPressCache( 'gf-wc' )
		);

		// Logger (singleton)
		$this->container->register(
			Logger::class,
			fn() => new Logger( 'GF-WC' )
		);

		// Event dispatcher (singleton)
		$this->container->register(
			EventDispatcher::class,
			fn() => new EventDispatcher()
		);

		// Price calculator (no dependencies)
		$this->container->register(
			PriceCalculator::class,
			fn() => new PriceCalculator()
		);

		// Cart service (uses calculator and product repository)
        $this->container->register(
            CartService::class,
            fn( Container $c ) => new CartService(
                $c->get( PriceCalculator::class ),
                $c->get( ProductRepositoryInterface::class )
            )
        );

        // Cache invalidation hooks could be registered here if desired

		// Config validator (uses repositories)
		$this->container->register(
			ConfigValidator::class,
			fn( Container $c ) => new ConfigValidator(
				$c->get( FormRepositoryInterface::class ),
				$c->get( ProductRepositoryInterface::class )
			)
		);

		// Theme (uses plugin config)
		$this->container->register(
			ShuttersTheme::class,
			fn() => new ShuttersTheme( $this->plugin_url, $this->version )
		);
	}

	/**
	 * Register admin services
	 *
	 * Only registered when is_admin() is true
	 *
	 * @return void
	 */
    private function registerAdminServices(): void {
        if ( ! is_admin() ) {
            return;
        }

		// Admin notices
		$this->container->register(
			AdminNotices::class,
			fn( Container $c ) => new AdminNotices(
				$c->get( ConfigValidator::class )
			)
		);

		// Settings page
		$this->container->register(
			SettingsPage::class,
			fn( Container $c ) => new SettingsPage(
				$c->get( ConfigValidator::class )
			)
		);

		// Field settings
		$this->container->register(
			FieldSettings::class,
			fn() => new FieldSettings()
		);

        // Editor script
        $this->container->register(
            EditorScript::class,
            fn() => new EditorScript()
        );

        // Plugin links (Settings link in Plugins list)
        $this->container->register(
            PluginLinks::class,
            fn() => new PluginLinks()
        );

        // Site Health debug info
        $this->container->register(
            SiteHealth::class,
            fn( Container $c ) => new SiteHealth( $c->get( ConfigValidator::class ) )
        );
    }

	/**
	 * Register integration services
	 *
	 * @return void
	 */
	private function registerIntegrations(): void {
		// Admin toolbar (frontend + admin)
		$this->container->register(
			AdminToolbar::class,
			fn( Container $c ) => new AdminToolbar(
				$c->get( ConfigValidator::class )
			)
		);

		// WooCommerce cart integration (if WC is active)
        if ( class_exists( 'WooCommerce' ) ) {
            $this->container->register(
                WooCommerceCart::class,
                fn( Container $c ) => new WooCommerceCart(
                    $c->get( PriceCalculator::class ),
                    $c->get( CartService::class ),
                    $c->get( Logger::class )
                )
            );

			// REST API controller
			$this->container->register(
				CalculatorController::class,
				fn( Container $c ) => new CalculatorController(
					$c->get( CartService::class ),
					$c->get( Logger::class )
				)
			);

			// Feed-based addon registration deferred until gform_loaded

			// Asset manager (requires cart integration)
			$this->container->register(
				AssetManager::class,
				fn( Container $c ) => new AssetManager(
					$this->plugin_url,
					$this->version,
					$c->get( WooCommerceCart::class )
				)
			);
		}
	}

	/**
	 * Boot all registered services
	 *
	 * Instantiates services that need to run immediately
	 *
	 * @return void
	 */
    public function boot(): void {
		// Boot theme
		$this->container->get( ShuttersTheme::class );

		// Boot admin services
        if ( is_admin() ) {
            $this->container->get( AdminNotices::class );
            $this->container->get( SettingsPage::class );
            $this->container->get( FieldSettings::class );
            $this->container->get( EditorScript::class );
            $this->container->get( PluginLinks::class );
            $this->container->get( SiteHealth::class );

            // Register admin-post handler for clearing confirmations
            add_action( 'admin_post_gf_wc_clear_confirmations', [ SettingsPage::class, 'handle_clear_confirmations' ] );
            // Register admin-post handler for force update check
            add_action( 'admin_post_gf_wc_check_updates', [ SettingsPage::class, 'handle_check_updates' ] );
        }

        // Boot admin toolbar
        $this->container->get( AdminToolbar::class );

		// Boot WooCommerce integration
        if ( class_exists( 'WooCommerce' ) ) {
            $this->container->get( WooCommerceCart::class );
            $this->container->get( AssetManager::class );

			// Register REST API routes
			add_action( 'rest_api_init', function () {
				$this->container->get( CalculatorController::class )->register_routes();
			} );

			// Boot feed addon (requires GF addon framework)
			if ( class_exists( 'GFForms' ) && class_exists( 'GFAddOn' ) ) {
				$register_addon = function () {
					// Manually load feed addon class if not available
					if ( ! class_exists( 'GFFeedAddOn' ) ) {
						$feed_addon_file = WP_PLUGIN_DIR . '/gravityforms/includes/addon/class-gf-feed-addon.php';
						if ( file_exists( $feed_addon_file ) ) {
							require_once $feed_addon_file;
						} else {
							// Silently fail - GFFeedAddOn not available
							return;
						}
					}

					if ( ! class_exists( 'GFFeedAddOn' ) ) {
						// Silently fail - cannot register feed addon
						return;
					}

					// Register feed description renderer
					$this->container->register(
						\BenHughes\GravityFormsWC\Admin\FeedDescriptionRenderer::class,
						fn() => new \BenHughes\GravityFormsWC\Admin\FeedDescriptionRenderer()
					);

					// Register in container
					$this->container->register(
						WooCommerceFeedAddon::class,
						fn( Container $c ) => new WooCommerceFeedAddon(
							$c->get( CartService::class ),
							$c->get( ProductRepositoryInterface::class ),
							$c->get( \BenHughes\GravityFormsWC\Admin\FeedDescriptionRenderer::class ),
							$c->get( Logger::class )
						)
					);

					// Instantiate from container (sets singleton)
					$this->container->get( WooCommerceFeedAddon::class );

					// Register with Gravity Forms
					\GFAddOn::register( WooCommerceFeedAddon::class );
				};

				// Use gform_loaded or run immediately if already fired
				if ( did_action( 'gform_loaded' ) ) {
					$register_addon();
				} else {
					add_action( 'gform_loaded', $register_addon, 5 );
        }

        // Note: Cache invalidation hooks are not booted by default
    }
}
	}
}
