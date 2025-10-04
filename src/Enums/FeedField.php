<?php
/**
 * Feed Field Enum
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Enums;

/**
 * Feed field names
 *
 * Type-safe field name constants for feed configuration
 */
enum FeedField: string {
	case FEED_NAME = 'feedName';
	case PRODUCT_ID = 'productId';
	case QUANTITY = 'quantity';
	case CONDITION = 'condition';

	/**
	 * Get all field names as array
	 *
	 * @return array<string>
	 */
	public static function values(): array {
		return array_map(
			static fn( self $field ): string => $field->value,
			self::cases()
		);
	}
}
