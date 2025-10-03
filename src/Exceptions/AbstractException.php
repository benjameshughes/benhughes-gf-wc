<?php
/**
 * Abstract Exception
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Exceptions;

use Exception;

/**
 * Base exception implementation
 */
abstract class AbstractException extends Exception implements GravityFormsWCException {

	/**
	 * Error context data
	 *
	 * @var array<string, mixed>
	 */
	protected array $context = [];

	/**
	 * Constructor
	 *
	 * @param string                $message  Error message.
	 * @param array<string, mixed>  $context  Context data.
	 * @param int                   $code     Error code.
	 * @param \Throwable|null       $previous Previous exception.
	 */
	public function __construct(
		string $message = '',
		array $context = [],
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->context = $context;
	}

	/**
	 * Get error context data
	 *
	 * @return array<string, mixed>
	 */
	public function getContext(): array {
		return $this->context;
	}
}
