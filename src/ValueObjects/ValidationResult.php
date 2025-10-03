<?php
/**
 * Validation Result Value Object
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\ValueObjects;

/**
 * Immutable validation result
 *
 * Value object containing validation result with typed errors
 */
readonly class ValidationResult {

	/**
	 * Constructor with promoted readonly properties
	 *
	 * @param bool                      $isValid Whether configuration is valid.
	 * @param array<int, ValidationError> $errors  Array of validation errors.
	 */
	public function __construct(
		public bool $isValid,
		public array $errors = [],
	) {}

	/**
	 * Check if validation passed
	 *
	 * @return bool
	 */
	public function passed(): bool {
		return $this->isValid;
	}

	/**
	 * Check if validation failed
	 *
	 * @return bool
	 */
	public function failed(): bool {
		return ! $this->isValid;
	}

	/**
	 * Get error count
	 *
	 * @return int
	 */
	public function errorCount(): int {
		return count( $this->errors );
	}

	/**
	 * Get error messages as strings
	 *
	 * @return array<int, string>
	 */
	public function errorMessages(): array {
		return array_map(
			fn( ValidationError $error ) => $error->message,
			$this->errors
		);
	}

	/**
	 * Convert to array (for backward compatibility)
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'valid'  => $this->isValid,
			'errors' => array_map(
				fn( ValidationError $error ) => $error->toArray(),
				$this->errors
			),
		];
	}

	/**
	 * Create success result
	 *
	 * @return self
	 */
	public static function success(): self {
		return new self( isValid: true, errors: [] );
	}

	/**
	 * Create failure result with errors
	 *
	 * @param array<int, ValidationError> $errors Validation errors.
	 * @return self
	 */
	public static function failure( array $errors ): self {
		return new self( isValid: false, errors: $errors );
	}
}
