<?php
/**
 * Calculator Configuration Value Object
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\ValueObjects;

use GF_Field;

/**
 * Immutable calculator configuration
 *
 * Value object for Price Calculator field configuration with type safety
 */
readonly class CalculatorConfig {

	/**
	 * Constructor with promoted readonly properties
	 *
	 * @param int    $formId             Gravity Forms form ID.
	 * @param int    $productId          WooCommerce product ID.
	 * @param int    $widthFieldId       Width field ID.
	 * @param int    $dropFieldId        Drop/height field ID.
	 * @param int    $priceFieldId       Price calculator field ID.
	 * @param int    $unitFieldId        Measurement unit field ID (0 if not set).
	 * @param bool   $showCalculation    Whether to show calculation breakdown.
	 * @param bool   $showSaleComparison Whether to show sale price comparison.
	 * @param string $currency           Currency symbol.
	 * @param string $pricePrefix        Text before price.
	 * @param string $priceSuffix        Text after price.
	 */
	public function __construct(
		public int $formId,
		public int $productId,
		public int $widthFieldId,
		public int $dropFieldId,
		public int $priceFieldId,
		public int $unitFieldId = 0,
		public bool $showCalculation = false,
		public bool $showSaleComparison = false,
		public string $currency = '£',
		public string $pricePrefix = '',
		public string $priceSuffix = '',
	) {}

	/**
	 * Create from Gravity Forms field
	 *
	 * @param GF_Field $field   Price Calculator field.
	 * @param int      $form_id Form ID.
	 * @return self
	 */
	public static function fromField( GF_Field $field, int $form_id ): self {
		return new self(
			formId: $form_id,
			productId: (int) ( $field->wcProductId ?? 0 ),
			widthFieldId: (int) ( $field->widthFieldId ?? 0 ),
			dropFieldId: (int) ( $field->dropFieldId ?? 0 ),
			priceFieldId: (int) $field->id,
			unitFieldId: (int) ( $field->unitFieldId ?? 0 ),
			showCalculation: (bool) ( $field->showCalculation ?? false ),
			showSaleComparison: (bool) ( $field->showSaleComparison ?? false ),
			currency: (string) ( $field->currencySymbol ?? '£' ),
			pricePrefix: (string) ( $field->pricePrefix ?? '' ),
			priceSuffix: (string) ( $field->priceSuffix ?? '' ),
		);
	}

	/**
	 * Check if unit field is configured
	 *
	 * @return bool
	 */
	public function hasUnitField(): bool {
		return $this->unitFieldId > 0;
	}

	/**
	 * Check if all required fields are set
	 *
	 * @return bool
	 */
	public function isComplete(): bool {
		return $this->formId > 0
			&& $this->productId > 0
			&& $this->widthFieldId > 0
			&& $this->dropFieldId > 0
			&& $this->priceFieldId > 0;
	}

	/**
	 * Convert to array for JavaScript/JSON
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'formId'             => $this->formId,
			'productId'          => $this->productId,
			'widthFieldId'       => $this->widthFieldId,
			'dropFieldId'        => $this->dropFieldId,
			'priceFieldId'       => $this->priceFieldId,
			'unitFieldId'        => $this->unitFieldId,
			'showCalculation'    => $this->showCalculation,
			'showSaleComparison' => $this->showSaleComparison,
			'currency'           => $this->currency,
			'pricePrefix'        => $this->pricePrefix,
			'priceSuffix'        => $this->priceSuffix,
		];
	}
}
