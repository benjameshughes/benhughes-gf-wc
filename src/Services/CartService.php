<?php
/**
 * Cart Service
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Services;

use BenHughes\GravityFormsWC\Calculation\PriceCalculator;
use BenHughes\GravityFormsWC\Enums\MeasurementUnit;
use BenHughes\GravityFormsWC\Repositories\ProductRepositoryInterface;
use BenHughes\GravityFormsWC\ValueObjects\PriceCalculation;

/**
 * Handles cart-related business logic
 *
 * Encapsulates logic for preparing product data with measurements
 * and calculations for the WooCommerce cart.
 */
class CartService {

	/**
	 * Price calculator
	 *
	 * @var PriceCalculator
	 */
	private PriceCalculator $calculator;

	/**
	 * Product repository
	 *
	 * @var ProductRepositoryInterface
	 */
	private ProductRepositoryInterface $product_repository;

	/**
	 * Constructor
	 *
	 * @param PriceCalculator            $calculator          Price calculator.
	 * @param ProductRepositoryInterface $product_repository Product repository.
	 */
	public function __construct(
		PriceCalculator $calculator,
		ProductRepositoryInterface $product_repository
	) {
		$this->calculator         = $calculator;
		$this->product_repository = $product_repository;
	}

	/**
	 * Prepare cart item data with measurements
	 *
	 * @param int                  $product_id Product ID.
	 * @param float                $width      Width measurement.
	 * @param float                $drop       Drop measurement.
	 * @param MeasurementUnit      $unit       Measurement unit.
	 * @param array<string, mixed> $extra_data Additional cart data.
	 * @return array<string, mixed> Cart item data.
	 */
	public function prepareCartItemData(
		int $product_id,
		float $width,
		float $drop,
		MeasurementUnit $unit,
		array $extra_data = []
	): array {
		$calculation = $this->calculator->calculate( $width, $drop, $unit, $product_id );

		return array_merge(
			[
				'gf_wc_width'       => $width,
				'gf_wc_drop'        => $drop,
				'gf_wc_unit'        => $unit->value,
				'gf_wc_price'       => $calculation->price,
				'gf_wc_calculation' => $calculation,
			],
			$extra_data
		);
	}

	/**
	 * Format cart item display data
	 *
	 * @param PriceCalculation $calculation Price calculation.
	 * @param float            $width       Width value.
	 * @param float            $drop        Drop value.
	 * @param MeasurementUnit  $unit        Measurement unit.
	 * @return array<string, string> Display data for cart.
	 */
	public function formatCartItemDisplay(
		PriceCalculation $calculation,
		float $width,
		float $drop,
		MeasurementUnit $unit
	): array {
		return [
			'dimensions'  => sprintf(
				'%s x %s %s',
				number_format( $width, 2 ),
				number_format( $drop, 2 ),
				$unit->label()
			),
			'calculation' => $calculation->getCalculationText(),
			'area'        => sprintf(
				'%s m²',
				number_format( $calculation->areaInMeters, 2 )
			),
		];
	}

	/**
	 * Validate product exists
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function productExists( int $product_id ): bool {
		return $this->product_repository->exists( $product_id );
	}

	/**
	 * Get product name
	 *
	 * @param int $product_id Product ID.
	 * @return string|null
	 */
	public function getProductName( int $product_id ): ?string {
		return $this->product_repository->getName( $product_id );
	}
}
