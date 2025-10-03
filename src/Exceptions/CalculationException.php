<?php
/**
 * Calculation Exception
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Exceptions;

/**
 * Thrown when a price calculation fails
 */
class CalculationException extends AbstractException {

	/**
	 * Create exception for invalid product pricing
	 *
	 * @param int             $product_id Product ID.
	 * @param \Throwable|null $previous   Previous exception.
	 * @return self
	 */
	public static function invalidProductPricing( int $product_id, ?\Throwable $previous = null ): self {
		return new self(
			sprintf( 'Invalid product pricing for product ID %d', $product_id ),
			[ 'product_id' => $product_id ],
			500,
			$previous
		);
	}

	/**
	 * Create exception for calculation failure
	 *
	 * @param string          $reason   Reason for failure.
	 * @param array           $context  Context data.
	 * @param \Throwable|null $previous Previous exception.
	 * @return self
	 */
	public static function calculationFailed( string $reason, array $context = [], ?\Throwable $previous = null ): self {
		return new self(
			sprintf( 'Price calculation failed: %s', $reason ),
			$context,
			500,
			$previous
		);
	}
}
