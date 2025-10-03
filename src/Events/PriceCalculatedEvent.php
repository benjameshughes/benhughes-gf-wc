<?php
/**
 * Price Calculated Event
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Events;

use BenHughes\GravityFormsWC\ValueObjects\PriceCalculation;

/**
 * Event dispatched when a price is calculated
 */
class PriceCalculatedEvent extends AbstractEvent {

	/**
	 * Price calculation result
	 *
	 * @var PriceCalculation
	 */
	private PriceCalculation $calculation;

	/**
	 * Product ID
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Width value
	 *
	 * @var float
	 */
	private float $width;

	/**
	 * Drop value
	 *
	 * @var float
	 */
	private float $drop;

	/**
	 * Constructor
	 *
	 * @param PriceCalculation $calculation Price calculation.
	 * @param int              $product_id  Product ID.
	 * @param float            $width       Width value.
	 * @param float            $drop        Drop value.
	 */
	public function __construct(
		PriceCalculation $calculation,
		int $product_id,
		float $width,
		float $drop
	) {
		$this->calculation = $calculation;
		$this->product_id  = $product_id;
		$this->width       = $width;
		$this->drop        = $drop;
	}

	/**
	 * Get calculation
	 *
	 * @return PriceCalculation
	 */
	public function getCalculation(): PriceCalculation {
		return $this->calculation;
	}

	/**
	 * Get product ID
	 *
	 * @return int
	 */
	public function getProductId(): int {
		return $this->product_id;
	}

	/**
	 * Get width
	 *
	 * @return float
	 */
	public function getWidth(): float {
		return $this->width;
	}

	/**
	 * Get drop
	 *
	 * @return float
	 */
	public function getDrop(): float {
		return $this->drop;
	}
}
