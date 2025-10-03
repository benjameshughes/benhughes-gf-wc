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

/**
 * Handles all price calculations with unit conversions
 */
class PriceCalculator {

	/**
	 * Calculate price based on dimensions and unit
	 *
	 * @param float  $width      Width value as entered by customer.
	 * @param float  $drop       Drop/height value as entered by customer.
	 * @param string $unit       Unit of measurement (mm, cm, in).
	 * @param int    $product_id WooCommerce product ID.
	 * @return array {
	 *     @type float  $price              Final calculated price (sale if on sale, otherwise regular).
	 *     @type float  $regular_price      Calculated regular price (area × regular price/m²).
	 *     @type float  $sale_price         Calculated sale price (area × sale price/m²) - 0 if not on sale.
	 *     @type bool   $is_on_sale         Whether product is on sale.
	 *     @type float  $width_cm           Width converted to cm.
	 *     @type float  $drop_cm            Drop converted to cm.
	 *     @type float  $area_m2            Area in square meters.
	 *     @type float  $regular_price_m2   Regular price per square meter from product.
	 *     @type float  $sale_price_m2      Sale price per square meter from product.
	 *     @type string $unit               Original unit.
	 * }
	 */
	public function calculate( float $width, float $drop, string $unit, int $product_id ): array {
		// Validate inputs
		if ( $width <= 0 || $drop <= 0 ) {
			return $this->empty_result();
		}

		// Convert to centimeters (our base unit)
		$width_cm = $this->convert_to_cm( $width, $unit );
		$drop_cm  = $this->convert_to_cm( $drop, $unit );

		// Calculate area in square meters
		$area_m2 = ( $width_cm / 100 ) * ( $drop_cm / 100 );

		// Get product pricing
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $this->empty_result();
		}

		$regular_price_m2 = (float) $product->get_regular_price();
		$sale_price_m2    = $product->get_sale_price() ? (float) $product->get_sale_price() : 0.0;
		$is_on_sale       = $sale_price_m2 > 0 && $sale_price_m2 < $regular_price_m2;

		// Calculate both prices
		$regular_price = round( $area_m2 * $regular_price_m2, 2 );
		$sale_price    = $is_on_sale ? round( $area_m2 * $sale_price_m2, 2 ) : 0.0;

		// Final price (sale if on sale, otherwise regular)
		$price = $is_on_sale ? $sale_price : $regular_price;

		return [
			'price'            => $price,
			'regular_price'    => $regular_price,
			'sale_price'       => $sale_price,
			'is_on_sale'       => $is_on_sale,
			'width_cm'         => $width_cm,
			'drop_cm'          => $drop_cm,
			'area_m2'          => $area_m2,
			'regular_price_m2' => $regular_price_m2,
			'sale_price_m2'    => $sale_price_m2,
			'unit'             => $unit,
		];
	}

	/**
	 * Convert measurement value to centimeters
	 *
	 * @param float  $value Value to convert.
	 * @param string $unit  Unit (mm, cm, or in).
	 * @return float Value in centimeters.
	 */
	private function convert_to_cm( float $value, string $unit ): float {
		switch ( $unit ) {
			case 'mm':
				return $value / 10;     // 100mm = 10cm
			case 'in':
				return $value * 2.54;   // 100in = 254cm
			case 'cm':
			default:
				return $value;          // 100cm = 100cm
		}
	}

	/**
	 * Get product pricing details for frontend display
	 *
	 * @param int $product_id Product ID.
	 * @return array {
	 *     @type float $regular_price Regular price per m².
	 *     @type float $sale_price    Sale price per m² (0 if not on sale).
	 *     @type bool  $is_on_sale    Whether product is on sale.
	 * }
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

	/**
	 * Return empty result array
	 *
	 * @return array
	 */
	private function empty_result(): array {
		return [
			'price'            => 0.0,
			'regular_price'    => 0.0,
			'sale_price'       => 0.0,
			'is_on_sale'       => false,
			'width_cm'         => 0.0,
			'drop_cm'          => 0.0,
			'area_m2'          => 0.0,
			'regular_price_m2' => 0.0,
			'sale_price_m2'    => 0.0,
			'unit'             => 'cm',
		];
	}
}
