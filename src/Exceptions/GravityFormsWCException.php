<?php
/**
 * Base Exception Interface
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Exceptions;

use Throwable;

/**
 * Base exception interface for all plugin exceptions
 *
 * Allows catching all plugin exceptions with a single catch block
 */
interface GravityFormsWCException extends Throwable {

	/**
	 * Get error context data
	 *
	 * @return array<string, mixed>
	 */
	public function getContext(): array;
}
