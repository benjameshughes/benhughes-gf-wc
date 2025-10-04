<?php
/**
 * Feed Settings Value Object
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\ValueObjects;

use BenHughes\GravityFormsWC\Enums\FeedField;

/**
 * Immutable feed settings
 *
 * Value object representing feed configuration
 */
readonly class FeedSettings {

	/**
	 * Constructor with promoted readonly properties
	 *
	 * @param string $feedName  Feed name.
	 * @param int    $productId Product ID (0 if from Price Calculator).
	 * @param int    $quantity  Product quantity.
	 */
	public function __construct(
		public string $feedName,
		public int $productId,
		public int $quantity,
	) {}

	/**
	 * Create from feed meta array
	 *
	 * @param array<string, mixed> $meta Feed meta data.
	 * @return self
	 */
	public static function fromMeta( array $meta ): self {
		return new self(
			feedName: (string) ( $meta[ FeedField::FEED_NAME->value ] ?? '' ),
			productId: (int) ( $meta[ FeedField::PRODUCT_ID->value ] ?? 0 ),
			quantity: max( 1, (int) ( $meta[ FeedField::QUANTITY->value ] ?? 1 ) ),
		);
	}

	/**
	 * Check if product ID is configured in feed
	 *
	 * @return bool
	 */
	public function hasProductId(): bool {
		return $this->productId > 0;
	}

	/**
	 * Convert to array
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			FeedField::FEED_NAME->value => $this->feedName,
			FeedField::PRODUCT_ID->value => $this->productId,
			FeedField::QUANTITY->value => $this->quantity,
		];
	}
}
