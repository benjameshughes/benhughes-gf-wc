<?php
/**
 * Event Interface
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Events;

/**
 * Base interface for all events
 *
 * Events are immutable data containers that represent something that happened
 */
interface Event {

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Check if event propagation should stop
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool;

	/**
	 * Stop event propagation
	 *
	 * @return void
	 */
	public function stopPropagation(): void;
}
