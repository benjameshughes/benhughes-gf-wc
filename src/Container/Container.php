<?php
/**
 * Service Container
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Container;

use RuntimeException;

/**
 * Simple PSR-11 compatible Dependency Injection Container
 *
 * Provides:
 * - Service registration with factory callables
 * - Lazy instantiation (singletons)
 * - Dependency resolution
 * - Type safety
 */
class Container {

	/**
	 * Registered service factories
	 *
	 * @var array<string, callable>
	 */
	private array $factories = [];

	/**
	 * Instantiated services (singletons)
	 *
	 * @var array<string, object>
	 */
	private array $services = [];

	/**
	 * Register a service factory
	 *
	 * @param string   $id      Service identifier (usually class name).
	 * @param callable $factory Factory function that returns service instance.
	 * @return self
	 */
	public function register( string $id, callable $factory ): self {
		$this->factories[ $id ] = $factory;
		return $this;
	}

	/**
	 * Get a service from the container
	 *
	 * @template T of object
	 * @param class-string<T> $id Service identifier.
	 * @return T Service instance.
	 * @throws RuntimeException If service not found.
	 */
	public function get( string $id ): object {
		// Return existing instance if already created
		if ( isset( $this->services[ $id ] ) ) {
			return $this->services[ $id ];
		}

		// Check if factory exists
		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new RuntimeException(
				sprintf( 'Service "%s" not found in container', $id )
			);
		}

		// Create instance using factory (pass container for dependency resolution)
		$service = ( $this->factories[ $id ] )( $this );

		// Store as singleton
		$this->services[ $id ] = $service;

		return $service;
	}

	/**
	 * Check if service is registered
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || isset( $this->services[ $id ] );
	}

	/**
	 * Set an already instantiated service
	 *
	 * Useful for testing or pre-configured instances
	 *
	 * @param string $id      Service identifier.
	 * @param object $service Service instance.
	 * @return self
	 */
	public function set( string $id, object $service ): self {
		$this->services[ $id ] = $service;
		return $this;
	}

	/**
	 * Remove a service from the container
	 *
	 * @param string $id Service identifier.
	 * @return self
	 */
	public function remove( string $id ): self {
		unset( $this->factories[ $id ], $this->services[ $id ] );
		return $this;
	}

	/**
	 * Clear all services and factories
	 *
	 * @return self
	 */
	public function clear(): self {
		$this->factories = [];
		$this->services  = [];
		return $this;
	}
}
