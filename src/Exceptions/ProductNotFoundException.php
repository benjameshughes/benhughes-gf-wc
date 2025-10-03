<?php
/**
 * Product Not Found Exception
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Exceptions;

/**
 * Thrown when a product cannot be found
 */
class ProductNotFoundException extends AbstractException {

	/**
	 * Create exception for product ID
	 *
	 * @param int             $product_id Product ID.
	 * @param \Throwable|null $previous   Previous exception.
	 * @return self
	 */
	public static function forProductId( int $product_id, ?\Throwable $previous = null ): self {
		return new self(
			sprintf( 'Product with ID %d not found', $product_id ),
			[ 'product_id' => $product_id ],
			404,
			$previous
		);
	}
}
