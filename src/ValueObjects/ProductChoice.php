<?php
/**
 * Product Choice Value Object
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\ValueObjects;

/**
 * Immutable product choice for dropdown
 *
 * Value object representing a product option in feed settings
 */
readonly class ProductChoice {

	/**
	 * Constructor with promoted readonly properties
	 *
	 * @param string $label Display label.
	 * @param string $value Product ID as string (empty for placeholder).
	 */
	public function __construct(
		public string $label,
		public string $value,
	) {}

	/**
	 * Create placeholder option
	 *
	 * @return self
	 */
	public static function placeholder(): self {
		return new self(
			label: __( 'Select a Product', 'gf-wc-bridge' ),
			value: '',
		);
	}

	/**
	 * Create from product ID and name
	 *
	 * @param int    $productId   Product ID.
	 * @param string $productName Product name.
	 * @return self
	 */
	public static function fromProduct( int $productId, string $productName ): self {
		return new self(
			label: "{$productName} (ID: {$productId})",
			value: (string) $productId,
		);
	}

	/**
	 * Convert to Gravity Forms choice array
	 *
	 * @return array<string, string>
	 */
	public function toArray(): array {
		return [
			'label' => $this->label,
			'value' => $this->value,
		];
	}
}
