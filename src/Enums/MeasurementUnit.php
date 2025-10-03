<?php
/**
 * Measurement Unit Enum
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Enums;

/**
 * Measurement unit types with conversion logic
 *
 * Backed enum for type safety with built-in conversion methods
 */
enum MeasurementUnit: string {
	case MILLIMETERS = 'mm';
	case CENTIMETERS = 'cm';
	case INCHES = 'in';

	/**
	 * Get human-readable label
	 *
	 * @return string Translated label with abbreviation.
	 */
	public function label(): string {
		return match ( $this ) {
			self::MILLIMETERS => __( 'Millimeters (mm)', 'gf-wc-bridge' ),
			self::CENTIMETERS => __( 'Centimeters (cm)', 'gf-wc-bridge' ),
			self::INCHES => __( 'Inches (in)', 'gf-wc-bridge' ),
		};
	}

	/**
	 * Convert value to centimeters (our base unit)
	 *
	 * @param float $value Value in this unit.
	 * @return float Value in centimeters.
	 */
	public function toCentimeters( float $value ): float {
		return match ( $this ) {
			self::MILLIMETERS => $value / 10,       // 100mm = 10cm
			self::CENTIMETERS => $value,            // 100cm = 100cm
			self::INCHES => $value * 2.54,          // 100in = 254cm
		};
	}

	/**
	 * Convert value from centimeters to this unit
	 *
	 * @param float $centimeters Value in centimeters.
	 * @return float Value in this unit.
	 */
	public function fromCentimeters( float $centimeters ): float {
		return match ( $this ) {
			self::MILLIMETERS => $centimeters * 10,
			self::CENTIMETERS => $centimeters,
			self::INCHES => $centimeters / 2.54,
		};
	}

	/**
	 * Convert value to meters
	 *
	 * @param float $value Value in this unit.
	 * @return float Value in meters.
	 */
	public function toMeters( float $value ): float {
		return match ( $this ) {
			self::MILLIMETERS => $value / 1000,
			self::CENTIMETERS => $value / 100,
			self::INCHES => ( $value * 2.54 ) / 100,
		};
	}

	/**
	 * Get step value for input fields
	 *
	 * @return string Step value for HTML number inputs.
	 */
	public function step(): string {
		return match ( $this ) {
			self::MILLIMETERS => '1',
			self::CENTIMETERS => '0.1',
			self::INCHES => '0.25',
		};
	}

	/**
	 * Convert placeholder value (100cm base) to this unit
	 *
	 * @return string Placeholder value in this unit.
	 */
	public function placeholder(): string {
		return match ( $this ) {
			self::MILLIMETERS => '1000',
			self::CENTIMETERS => '100',
			self::INCHES => '39.4',
		};
	}

	/**
	 * Get all available units as array (for choice fields)
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function choices(): array {
		return [
			[
				'text'       => self::MILLIMETERS->label(),
				'value'      => self::MILLIMETERS->value,
				'isSelected' => false,
			],
			[
				'text'       => self::CENTIMETERS->label(),
				'value'      => self::CENTIMETERS->value,
				'isSelected' => true,
			],
			[
				'text'       => self::INCHES->label(),
				'value'      => self::INCHES->value,
				'isSelected' => false,
			],
		];
	}

	/**
	 * Get default unit
	 *
	 * @return self
	 */
	public static function default(): self {
		return self::CENTIMETERS;
	}
}
