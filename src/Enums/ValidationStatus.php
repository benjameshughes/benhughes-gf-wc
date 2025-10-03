<?php
/**
 * Validation Status Enum
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Enums;

/**
 * Validation result status codes
 *
 * Using backed enums for type safety and IDE autocomplete
 */
enum ValidationStatus: string {
	case VALID = 'valid';
	case ERROR_NO_PRODUCT = 'no_product';
	case ERROR_NO_WC = 'no_woocommerce';
	case ERROR_NO_GF = 'no_gravity_forms';
	case ERROR_INVALID_FIELD = 'invalid_field';
	case ERROR_MISSING_CONFIG = 'missing_config';

	/**
	 * Get human-readable label for this status
	 *
	 * @return string Translated label.
	 */
	public function label(): string {
		return match ( $this ) {
			self::VALID => __( 'Valid', 'gf-wc-bridge' ),
			self::ERROR_NO_PRODUCT => __( 'Product not found', 'gf-wc-bridge' ),
			self::ERROR_NO_WC => __( 'WooCommerce not active', 'gf-wc-bridge' ),
			self::ERROR_NO_GF => __( 'Gravity Forms not active', 'gf-wc-bridge' ),
			self::ERROR_INVALID_FIELD => __( 'Invalid field', 'gf-wc-bridge' ),
			self::ERROR_MISSING_CONFIG => __( 'Missing configuration', 'gf-wc-bridge' ),
		};
	}

	/**
	 * Check if this status indicates an error
	 *
	 * @return bool
	 */
	public function isError(): bool {
		return $this !== self::VALID;
	}

	/**
	 * Check if this status is valid
	 *
	 * @return bool
	 */
	public function isValid(): bool {
		return $this === self::VALID;
	}
}
