<?php
/**
 * Cache Interface
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Cache;

/**
 * Interface for caching implementations
 */
interface CacheInterface {

	/**
	 * Get item from cache
	 *
	 * @param string $key Cache key.
	 * @return mixed|null Cached value or null if not found.
	 */
	public function get( string $key );

	/**
	 * Store item in cache
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Expiration in seconds (0 = no expiration).
	 * @return bool True on success.
	 */
	public function set( string $key, $value, int $expiration = 0 ): bool;

	/**
	 * Delete item from cache
	 *
	 * @param string $key Cache key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool;

	/**
	 * Check if item exists in cache
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function has( string $key ): bool;

	/**
	 * Clear all items from cache group
	 *
	 * @return bool True on success.
	 */
	public function flush(): bool;

	/**
	 * Get or set (remember) a cache value
	 *
	 * @param string   $key        Cache key.
	 * @param callable $callback   Callback to generate value if not cached.
	 * @param int      $expiration Expiration in seconds.
	 * @return mixed
	 */
	public function remember( string $key, callable $callback, int $expiration = 0 );
}
