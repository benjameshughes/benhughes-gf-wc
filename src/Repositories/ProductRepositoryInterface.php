<?php
/**
 * Product Repository Interface
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Repositories;

use WC_Product;

/**
 * Interface for product data access
 *
 * Abstracts WooCommerce API for testability and flexibility
 */
interface ProductRepositoryInterface {

	/**
	 * Find product by ID
	 *
	 * @param int $id Product ID.
	 * @return WC_Product|null Product object or null if not found.
	 */
	public function find( int $id ): ?WC_Product;

	/**
	 * Check if product exists
	 *
	 * @param int $id Product ID.
	 * @return bool
	 */
	public function exists( int $id ): bool;

	/**
	 * Get product name
	 *
	 * @param int $id Product ID.
	 * @return string|null Product name or null if not found.
	 */
	public function getName( int $id ): ?string;

	/**
	 * Get all products (for admin dropdowns)
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, WC_Product>
	 */
	public function getAll( array $args = [] ): array;
}
