<?php
/**
 * Helper Functions
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

use BenHughes\GravityFormsWC\Container\Container;
use BenHughes\GravityFormsWC\Plugin;

if ( ! function_exists( 'gf_wc_container' ) ) {
	/**
	 * Get the plugin's dependency injection container
	 *
	 * @return Container
	 */
	function gf_wc_container(): Container {
		static $container = null;

		if ( null === $container ) {
			$plugin    = Plugin::get_instance( __FILE__, '2.4.0' );
			$container = $plugin->get_container();
		}

		return $container;
	}
}

if ( ! function_exists( 'gf_wc_service' ) ) {
	/**
	 * Get a service from the container
	 *
	 * @template T of object
	 * @param class-string<T> $service_class Service class name.
	 * @return T Service instance.
	 */
	function gf_wc_service( string $service_class ): object {
		return gf_wc_container()->get( $service_class );
	}
}

if ( ! function_exists( 'gf_wc_log' ) ) {
	/**
	 * Quick logging helper
	 *
	 * @param string               $level   Log level.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	function gf_wc_log( string $level, string $message, array $context = [] ): void {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		/** @var \BenHughes\GravityFormsWC\Logging\Logger $logger */
		$logger = gf_wc_service( \BenHughes\GravityFormsWC\Logging\Logger::class );
		$logger->log( $level, $message, $context );
	}
}
