<?php
/**
 * Event Dispatcher
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Events;

/**
 * Event dispatcher for decoupled event handling
 *
 * Provides an abstraction over WordPress hooks for better testability
 */
class EventDispatcher {

	/**
	 * Registered event listeners
	 *
	 * @var array<string, array<int, array<callable>>>
	 */
	private array $listeners = [];

	/**
	 * Add event listener
	 *
	 * @param string   $event_name Event name or class.
	 * @param callable $listener   Listener callback.
	 * @param int      $priority   Priority (lower runs first).
	 * @return void
	 */
	public function addListener( string $event_name, callable $listener, int $priority = 10 ): void {
		if ( ! isset( $this->listeners[ $event_name ] ) ) {
			$this->listeners[ $event_name ] = [];
		}

		if ( ! isset( $this->listeners[ $event_name ][ $priority ] ) ) {
			$this->listeners[ $event_name ][ $priority ] = [];
		}

		$this->listeners[ $event_name ][ $priority ][] = $listener;
	}

	/**
	 * Remove event listener
	 *
	 * @param string   $event_name Event name or class.
	 * @param callable $listener   Listener callback.
	 * @return void
	 */
	public function removeListener( string $event_name, callable $listener ): void {
		if ( ! isset( $this->listeners[ $event_name ] ) ) {
			return;
		}

		foreach ( $this->listeners[ $event_name ] as $priority => $listeners ) {
			$key = array_search( $listener, $listeners, true );
			if ( false !== $key ) {
				unset( $this->listeners[ $event_name ][ $priority ][ $key ] );
			}
		}
	}

	/**
	 * Dispatch event to all registered listeners
	 *
	 * @param Event $event Event object.
	 * @return Event The event (possibly modified by listeners).
	 */
	public function dispatch( Event $event ): Event {
		$event_name = $event->getName();

		if ( ! isset( $this->listeners[ $event_name ] ) ) {
			return $event;
		}

		// Sort by priority
		$listeners = $this->listeners[ $event_name ];
		ksort( $listeners );

		// Execute listeners
		foreach ( $listeners as $priority_listeners ) {
			foreach ( $priority_listeners as $listener ) {
				if ( $event->isPropagationStopped() ) {
					break 2;
				}

				$listener( $event );
			}
		}

		return $event;
	}

	/**
	 * Check if event has listeners
	 *
	 * @param string $event_name Event name or class.
	 * @return bool
	 */
	public function hasListeners( string $event_name ): bool {
		return isset( $this->listeners[ $event_name ] ) && ! empty( $this->listeners[ $event_name ] );
	}

	/**
	 * Get all listeners for an event
	 *
	 * @param string $event_name Event name or class.
	 * @return array<callable>
	 */
	public function getListeners( string $event_name ): array {
		if ( ! isset( $this->listeners[ $event_name ] ) ) {
			return [];
		}

		$listeners = $this->listeners[ $event_name ];
		ksort( $listeners );

		return array_merge( ...$listeners );
	}
}
