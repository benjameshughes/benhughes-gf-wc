<?php
/**
 * WooCommerce Product Repository
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Repositories;

use WC_Product;

/**
 * Concrete implementation using WooCommerce API
 *
 * Provides abstraction layer over wc_get_product for dependency injection
 */
class WooCommerceProductRepository implements ProductRepositoryInterface {

	/**
	 * Find product by ID
	 *
	 * @param int $id Product ID.
	 * @return WC_Product|null
	 */
	public function find( int $id ): ?WC_Product {
		$product = wc_get_product( $id );

		if ( ! $product || ! $product->exists() ) {
			return null;
		}

		return $product;
	}

	/**
	 * Check if product exists
	 *
	 * @param int $id Product ID.
	 * @return bool
	 */
	public function exists( int $id ): bool {
		return null !== $this->find( $id );
	}

	/**
	 * Get product name
	 *
	 * @param int $id Product ID.
	 * @return string|null
	 */
	public function getName( int $id ): ?string {
		$product = $this->find( $id );

		if ( ! $product ) {
			return null;
		}

		return $product->get_name();
	}

	/**
	 * Get all products (for admin dropdowns)
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, WC_Product>
	 */
	public function getAll( array $args = [] ): array {
		$defaults = [
			'limit'   => -1,
			'status'  => 'publish',
			'orderby' => 'title',
			'order'   => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );

		return wc_get_products( $args );
	}
}
