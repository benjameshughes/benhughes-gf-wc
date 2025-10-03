<?php
/**
 * PSR-3 Compatible Logger
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Logging;

/**
 * Simple PSR-3 compatible logger
 *
 * Logs to WordPress debug.log when WP_DEBUG_LOG is enabled
 */
class Logger {

	/**
	 * Log prefix
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Constructor
	 *
	 * @param string $prefix Log prefix (default: 'GF-WC').
	 */
	public function __construct( string $prefix = 'GF-WC' ) {
		$this->prefix = $prefix;
	}

	/**
	 * System is unusable
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function emergency( string $message, array $context = [] ): void {
		$this->log( 'EMERGENCY', $message, $context );
	}

	/**
	 * Action must be taken immediately
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function alert( string $message, array $context = [] ): void {
		$this->log( 'ALERT', $message, $context );
	}

	/**
	 * Critical conditions
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function critical( string $message, array $context = [] ): void {
		$this->log( 'CRITICAL', $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function error( string $message, array $context = [] ): void {
		$this->log( 'ERROR', $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function warning( string $message, array $context = [] ): void {
		$this->log( 'WARNING', $message, $context );
	}

	/**
	 * Normal but significant events
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function notice( string $message, array $context = [] ): void {
		$this->log( 'NOTICE', $message, $context );
	}

	/**
	 * Interesting events
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function info( string $message, array $context = [] ): void {
		$this->log( 'INFO', $message, $context );
	}

	/**
	 * Detailed debug information
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function debug( string $message, array $context = [] ): void {
		$this->log( 'DEBUG', $message, $context );
	}

	/**
	 * Log with arbitrary level
	 *
	 * @param string               $level   Log level.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	public function log( string $level, string $message, array $context = [] ): void {
		// Only log if WP_DEBUG_LOG is enabled
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		// Interpolate context values into message
		$message = $this->interpolate( $message, $context );

		// Format log entry
		$log_entry = sprintf(
			'[%s] %s: %s',
			$this->prefix,
			strtoupper( $level ),
			$message
		);

		// Add context if present
		if ( ! empty( $context ) ) {
			$log_entry .= ' ' . wp_json_encode( $context, JSON_UNESCAPED_SLASHES );
		}

		// Write to error_log
		error_log( $log_entry );
	}

	/**
	 * Interpolate context values into message placeholders
	 *
	 * @param string               $message Message with {placeholders}.
	 * @param array<string, mixed> $context Context values.
	 * @return string
	 */
	private function interpolate( string $message, array $context ): string {
		$replace = [];

		foreach ( $context as $key => $value ) {
			// Handle exceptions specially
			if ( $value instanceof \Throwable ) {
				$value = sprintf(
					'%s: %s in %s:%d',
					get_class( $value ),
					$value->getMessage(),
					$value->getFile(),
					$value->getLine()
				);
			} elseif ( ! is_scalar( $value ) && ! is_null( $value ) ) {
				$value = wp_json_encode( $value );
			}

			$replace[ '{' . $key . '}' ] = $value;
		}

		return strtr( $message, $replace );
	}

	/**
	 * Log exception with full stack trace
	 *
	 * @param \Throwable $exception Exception.
	 * @param string     $level     Log level (default: error).
	 * @return void
	 */
	public function logException( \Throwable $exception, string $level = 'error' ): void {
		$context = [
			'exception' => get_class( $exception ),
			'file'      => $exception->getFile(),
			'line'      => $exception->getLine(),
			'trace'     => $exception->getTraceAsString(),
		];

		// Add exception context if it's our custom exception
		if ( method_exists( $exception, 'getContext' ) ) {
			$context['context'] = $exception->getContext();
		}

		$this->log( $level, $exception->getMessage(), $context );
	}
}
