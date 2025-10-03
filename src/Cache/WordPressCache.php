<?php
/**
 * WordPress Object Cache Implementation
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Cache;

/**
 * Cache implementation using WordPress object cache
 */
class WordPressCache implements CacheInterface {

	/**
	 * Cache group
	 *
	 * @var string
	 */
	private string $group;

	/**
	 * Constructor
	 *
	 * @param string $group Cache group (default: 'gf-wc').
	 */
	public function __construct( string $group = 'gf-wc' ) {
		$this->group = $group;
	}

	/**
	 * Get item from cache
	 *
	 * @param string $key Cache key.
	 * @return mixed|null Cached value or null if not found.
	 */
	public function get( string $key ) {
		$value = wp_cache_get( $key, $this->group );
		return false === $value ? null : $value;
	}

	/**
	 * Store item in cache
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Expiration in seconds (0 = no expiration).
	 * @return bool True on success.
	 */
	public function set( string $key, $value, int $expiration = 0 ): bool {
		return wp_cache_set( $key, $value, $this->group, $expiration );
	}

	/**
	 * Delete item from cache
	 *
	 * @param string $key Cache key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool {
		return wp_cache_delete( $key, $this->group );
	}

	/**
	 * Check if item exists in cache
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function has( string $key ): bool {
		return null !== $this->get( $key );
	}

	/**
	 * Clear all items from cache group
	 *
	 * @return bool True on success.
	 */
	public function flush(): bool {
		// WordPress doesn't have a native flush by group
		// This is a limitation we document
		return false;
	}

	/**
	 * Get or set (remember) a cache value
	 *
	 * @param string   $key        Cache key.
	 * @param callable $callback   Callback to generate value if not cached.
	 * @param int      $expiration Expiration in seconds.
	 * @return mixed
	 */
	public function remember( string $key, callable $callback, int $expiration = 0 ) {
		$value = $this->get( $key );

		if ( null !== $value ) {
			return $value;
		}

		$value = $callback();
		$this->set( $key, $value, $expiration );

		return $value;
	}

	/**
	 * Generate cache key with prefix
	 *
	 * @param string $identifier Identifier.
	 * @param mixed  ...$parts   Additional parts to include in key.
	 * @return string
	 */
	public function makeKey( string $identifier, ...$parts ): string {
		if ( empty( $parts ) ) {
			return $identifier;
		}

		return $identifier . ':' . implode( ':', array_map( 'strval', $parts ) );
	}
}
