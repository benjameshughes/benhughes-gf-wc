<?php
/**
 * Service Provider
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Container;

use BenHughes\GravityFormsWC\Admin\AdminNotices;
use BenHughes\GravityFormsWC\Admin\AdminToolbar;
use BenHughes\GravityFormsWC\Admin\EditorScript;
use BenHughes\GravityFormsWC\Admin\FieldSettings;
use BenHughes\GravityFormsWC\Admin\SettingsPage;
use BenHughes\GravityFormsWC\Assets\AssetManager;
use BenHughes\GravityFormsWC\Calculation\PriceCalculator;
use BenHughes\GravityFormsWC\Integration\WooCommerceCart;
use BenHughes\GravityFormsWC\Repositories\FormRepositoryInterface;
use BenHughes\GravityFormsWC\Repositories\GravityFormsRepository;
use BenHughes\GravityFormsWC\Repositories\ProductRepositoryInterface;
use BenHughes\GravityFormsWC\Repositories\WooCommerceProductRepository;
use BenHughes\GravityFormsWC\Services\CartService;
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
		// Form repository
		$this->container->register(
			FormRepositoryInterface::class,
			fn() => new GravityFormsRepository()
		);

		// Product repository
		$this->container->register(
			ProductRepositoryInterface::class,
			fn() => new WooCommerceProductRepository()
		);
	}

	/**
	 * Register core services
	 *
	 * @return void
	 */
	private function registerCoreServices(): void {
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
					$c->get( CartService::class )
				)
			);

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
		}

		// Boot admin toolbar
		$this->container->get( AdminToolbar::class );

		// Boot WooCommerce integration
		if ( class_exists( 'WooCommerce' ) ) {
			$this->container->get( WooCommerceCart::class );
			$this->container->get( AssetManager::class );
		}
	}
}
