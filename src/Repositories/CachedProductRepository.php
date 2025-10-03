<?php
/**
 * Cached Product Repository
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Repositories;

use BenHughes\GravityFormsWC\Cache\CacheInterface;
use WC_Product;

/**
 * Decorator that adds caching to product repository
 */
class CachedProductRepository implements ProductRepositoryInterface {

	/**
	 * Underlying repository
	 *
	 * @var ProductRepositoryInterface
	 */
	private ProductRepositoryInterface $repository;

	/**
	 * Cache instance
	 *
	 * @var CacheInterface
	 */
	private CacheInterface $cache;

	/**
	 * Cache expiration in seconds
	 *
	 * @var int
	 */
	private int $expiration;

	/**
	 * Constructor
	 *
	 * @param ProductRepositoryInterface $repository  Product repository.
	 * @param CacheInterface             $cache       Cache instance.
	 * @param int                        $expiration  Cache expiration in seconds (default: 1 hour).
	 */
	public function __construct(
		ProductRepositoryInterface $repository,
		CacheInterface $cache,
		int $expiration = 3600
	) {
		$this->repository = $repository;
		$this->cache      = $cache;
		$this->expiration = $expiration;
	}

	/**
	 * Find product by ID
	 *
	 * @param int $id Product ID.
	 * @return WC_Product|null
	 */
	public function find( int $id ): ?WC_Product {
		$key = $this->makeKey( 'product', $id );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->find( $id ),
			$this->expiration
		);
	}

	/**
	 * Check if product exists
	 *
	 * @param int $id Product ID.
	 * @return bool
	 */
	public function exists( int $id ): bool {
		$key = $this->makeKey( 'product_exists', $id );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->exists( $id ),
			$this->expiration
		);
	}

	/**
	 * Get product name
	 *
	 * @param int $id Product ID.
	 * @return string|null
	 */
	public function getName( int $id ): ?string {
		$key = $this->makeKey( 'product_name', $id );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->getName( $id ),
			$this->expiration
		);
	}

	/**
	 * Get all products (for admin dropdowns)
	 *
	 * Note: This is not cached due to potential large dataset
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, WC_Product>
	 */
	public function getAll( array $args = [] ): array {
		// Don't cache large datasets
		return $this->repository->getAll( $args );
	}

	/**
	 * Make cache key
	 *
	 * @param string $prefix Prefix.
	 * @param mixed  ...$parts Key parts.
	 * @return string
	 */
	private function makeKey( string $prefix, ...$parts ): string {
		return $prefix . ':' . implode( ':', array_map( 'strval', $parts ) );
	}

	/**
	 * Clear cache for a product
	 *
	 * @param int $id Product ID.
	 * @return void
	 */
	public function clearCache( int $id ): void {
		$this->cache->delete( $this->makeKey( 'product', $id ) );
		$this->cache->delete( $this->makeKey( 'product_exists', $id ) );
		$this->cache->delete( $this->makeKey( 'product_name', $id ) );
	}
}
