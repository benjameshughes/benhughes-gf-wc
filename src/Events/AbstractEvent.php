<?php
/**
 * Abstract Event
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Events;

/**
 * Base implementation for events
 */
abstract class AbstractEvent implements Event {

	/**
	 * Whether propagation is stopped
	 *
	 * @var bool
	 */
	private bool $propagation_stopped = false;

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName(): string {
		return static::class;
	}

	/**
	 * Check if event propagation should stop
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool {
		return $this->propagation_stopped;
	}

	/**
	 * Stop event propagation
	 *
	 * @return void
	 */
	public function stopPropagation(): void {
		$this->propagation_stopped = true;
	}
}
