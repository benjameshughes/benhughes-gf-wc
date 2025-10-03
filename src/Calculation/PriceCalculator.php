<?php
/**
 * Price Calculator
 *
 * Single source of truth for all price calculations.
 * Used by both frontend (via AJAX) and backend (cart validation).
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.2.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Calculation;

use BenHughes\GravityFormsWC\Enums\MeasurementUnit;
use BenHughes\GravityFormsWC\ValueObjects\PriceCalculation;

/**
 * Handles all price calculations with unit conversions
 */
class PriceCalculator {

	/**
	 * Calculate price based on dimensions and unit
	 *
	 * @param float           $width      Width value as entered by customer.
	 * @param float           $drop       Drop/height value as entered by customer.
	 * @param MeasurementUnit $unit       Unit of measurement enum.
	 * @param int             $product_id WooCommerce product ID.
	 * @return PriceCalculation Immutable calculation result.
	 */
	public function calculate( float $width, float $drop, MeasurementUnit $unit, int $product_id ): PriceCalculation {
		// Validate inputs
		if ( $width <= 0 || $drop <= 0 ) {
			return PriceCalculation::empty();
		}

		// Convert to centimeters (our base unit) using enum method
		$width_cm = $unit->toCentimeters( $width );
		$drop_cm  = $unit->toCentimeters( $drop );

		// Calculate area in square meters
		$area_m2 = ( $width_cm / 100 ) * ( $drop_cm / 100 );

		// Get product pricing
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return PriceCalculation::empty();
		}

		$regular_price_m2 = (float) $product->get_regular_price();
		$sale_price_m2    = $product->get_sale_price() ? (float) $product->get_sale_price() : 0.0;
		$is_on_sale       = $sale_price_m2 > 0 && $sale_price_m2 < $regular_price_m2;

		// Calculate both prices
		$regular_price = round( $area_m2 * $regular_price_m2, 2 );
		$sale_price    = $is_on_sale ? round( $area_m2 * $sale_price_m2, 2 ) : 0.0;

		// Final price (sale if on sale, otherwise regular)
		$price = $is_on_sale ? $sale_price : $regular_price;

		return new PriceCalculation(
			price: $price,
			regularPrice: $regular_price,
			salePrice: $sale_price,
			isOnSale: $is_on_sale,
			widthCm: $width_cm,
			dropCm: $drop_cm,
			areaM2: $area_m2,
			regularPriceM2: $regular_price_m2,
			salePriceM2: $sale_price_m2,
			unit: $unit,
		);
	}

	/**
	 * Get product pricing details for frontend display
	 *
	 * @param int $product_id Product ID.
	 * @return array<string, mixed> Product pricing data for JavaScript.
	 */
	public function get_product_pricing( int $product_id ): array {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return [
				'regular_price' => 100.0,
				'sale_price'    => 0.0,
				'is_on_sale'    => false,
			];
		}

		$regular_price = (float) $product->get_regular_price();
		$sale_price    = $product->get_sale_price() ? (float) $product->get_sale_price() : 0.0;
		$is_on_sale    = $sale_price > 0 && $sale_price < $regular_price;

		return [
			'regular_price' => $regular_price,
			'sale_price'    => $sale_price,
			'is_on_sale'    => $is_on_sale,
		];
	}
}
