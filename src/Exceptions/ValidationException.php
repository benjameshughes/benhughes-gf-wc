<?php
/**
 * Validation Exception
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Exceptions;

use BenHughes\GravityFormsWC\ValueObjects\ValidationResult;

/**
 * Thrown when validation fails
 */
class ValidationException extends AbstractException {

	/**
	 * Validation result
	 *
	 * @var ValidationResult|null
	 */
	private ?ValidationResult $validation_result = null;

	/**
	 * Create exception from validation result
	 *
	 * @param ValidationResult $result   Validation result.
	 * @param \Throwable|null  $previous Previous exception.
	 * @return self
	 */
	public static function fromValidationResult( ValidationResult $result, ?\Throwable $previous = null ): self {
		$errors  = $result->getErrors();
		$message = ! empty( $errors )
			? $errors[0]->getMessage()
			: 'Validation failed';

		$exception = new self(
			$message,
			[ 'errors' => $errors ],
			400,
			$previous
		);

		$exception->validation_result = $result;

		return $exception;
	}

	/**
	 * Create exception for invalid dimensions
	 *
	 * @param float           $width    Width value.
	 * @param float           $drop     Drop value.
	 * @param \Throwable|null $previous Previous exception.
	 * @return self
	 */
	public static function forInvalidDimensions( float $width, float $drop, ?\Throwable $previous = null ): self {
		return new self(
			'Invalid dimensions: width and drop must be greater than zero',
			[
				'width' => $width,
				'drop'  => $drop,
			],
			400,
			$previous
		);
	}

	/**
	 * Get validation result
	 *
	 * @return ValidationResult|null
	 */
	public function getValidationResult(): ?ValidationResult {
		return $this->validation_result;
	}
}
