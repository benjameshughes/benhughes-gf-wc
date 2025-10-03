<?php
/**
 * Price Calculation Value Object
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\ValueObjects;

use BenHughes\GravityFormsWC\Enums\MeasurementUnit;

/**
 * Immutable price calculation result
 *
 * Value object containing all calculation results with type safety
 */
readonly class PriceCalculation {

	/**
	 * Constructor with promoted readonly properties
	 *
	 * @param float           $price            Final calculated price (sale if on sale, otherwise regular).
	 * @param float           $regularPrice     Calculated regular price (area × regular price/m²).
	 * @param float           $salePrice        Calculated sale price (area × sale price/m²) - 0 if not on sale.
	 * @param bool            $isOnSale         Whether product is on sale.
	 * @param float           $widthCm          Width converted to centimeters.
	 * @param float           $dropCm           Drop/height converted to centimeters.
	 * @param float           $areaM2           Area in square meters.
	 * @param float           $regularPriceM2   Regular price per square meter from product.
	 * @param float           $salePriceM2      Sale price per square meter from product.
	 * @param MeasurementUnit $unit             Original measurement unit.
	 */
	public function __construct(
		public float $price,
		public float $regularPrice,
		public float $salePrice,
		public bool $isOnSale,
		public float $widthCm,
		public float $dropCm,
		public float $areaM2,
		public float $regularPriceM2,
		public float $salePriceM2,
		public MeasurementUnit $unit,
	) {}

	/**
	 * Calculate savings percentage
	 *
	 * @return int Percentage saved (0-100).
	 */
	public function getSavingsPercent(): int {
		if ( ! $this->isOnSale || $this->regularPrice <= 0 ) {
			return 0;
		}

		return (int) round( ( ( $this->regularPrice - $this->salePrice ) / $this->regularPrice ) * 100 );
	}

	/**
	 * Get formatted price with currency
	 *
	 * @param string $currency Currency symbol.
	 * @return string Formatted price string.
	 */
	public function getFormattedPrice( string $currency = '£' ): string {
		return $currency . number_format( $this->price, 2 );
	}

	/**
	 * Get calculation breakdown text
	 *
	 * @return string Human-readable calculation (e.g., "120cm × 180cm = 2.16m²").
	 */
	public function getCalculationText(): string {
		$width_display  = $this->unit->fromCentimeters( $this->widthCm );
		$drop_display   = $this->unit->fromCentimeters( $this->dropCm );
		$unit_abbr      = $this->unit->value;

		return sprintf(
			'%s%s × %s%s = %.2fm²',
			number_format( $width_display, 1 ),
			$unit_abbr,
			number_format( $drop_display, 1 ),
			$unit_abbr,
			$this->areaM2
		);
	}

	/**
	 * Convert to array for JSON responses
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'price'            => $this->price,
			'regular_price'    => $this->regularPrice,
			'sale_price'       => $this->salePrice,
			'is_on_sale'       => $this->isOnSale,
			'width_cm'         => $this->widthCm,
			'drop_cm'          => $this->dropCm,
			'area'             => $this->areaM2,
			'regular_price_m2' => $this->regularPriceM2,
			'sale_price_m2'    => $this->salePriceM2,
			'unit'             => $this->unit->value,
			'savings_percent'  => $this->getSavingsPercent(),
		];
	}

	/**
	 * Create empty calculation result
	 *
	 * @return self
	 */
	public static function empty(): self {
		return new self(
			price: 0.0,
			regularPrice: 0.0,
			salePrice: 0.0,
			isOnSale: false,
			widthCm: 0.0,
			dropCm: 0.0,
			areaM2: 0.0,
			regularPriceM2: 0.0,
			salePriceM2: 0.0,
			unit: MeasurementUnit::default(),
		);
	}
}
