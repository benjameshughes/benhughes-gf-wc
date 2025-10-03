<?php
/**
 * Validation Error Value Object
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\ValueObjects;

use BenHughes\GravityFormsWC\Enums\ValidationStatus;

/**
 * Immutable validation error
 *
 * Value object representing a single validation error
 */
readonly class ValidationError {

	/**
	 * Constructor with promoted readonly properties
	 *
	 * @param ValidationStatus $code    Error code enum.
	 * @param string           $message Error message.
	 * @param string           $field   Field that caused the error (optional).
	 */
	public function __construct(
		public ValidationStatus $code,
		public string $message,
		public string $field = '',
	) {}

	/**
	 * Convert to array
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$data = [
			'code'    => $this->code->value,
			'message' => $this->message,
		];

		if ( ! empty( $this->field ) ) {
			$data['field'] = $this->field;
		}

		return $data;
	}

	/**
	 * Create from array (for backward compatibility)
	 *
	 * @param array<string, mixed> $data Error data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		return new self(
			code: ValidationStatus::from( $data['code'] ?? 'missing_config' ),
			message: $data['message'] ?? '',
			field: $data['field'] ?? '',
		);
	}
}
