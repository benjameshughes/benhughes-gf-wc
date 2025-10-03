<?php
/**
 * Cached Form Repository
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Repositories;

use BenHughes\GravityFormsWC\Cache\CacheInterface;
use GF_Field;

/**
 * Decorator that adds caching to form repository
 */
class CachedFormRepository implements FormRepositoryInterface {

	/**
	 * Underlying repository
	 *
	 * @var FormRepositoryInterface
	 */
	private FormRepositoryInterface $repository;

	/**
	 * Cache instance
	 *
	 * @var CacheInterface
	 */
	private CacheInterface $cache;

	/**
	 * Cache expiration in seconds
	 *
	 * @var int
	 */
	private int $expiration;

	/**
	 * Constructor
	 *
	 * @param FormRepositoryInterface $repository  Form repository.
	 * @param CacheInterface          $cache       Cache instance.
	 * @param int                     $expiration  Cache expiration in seconds (default: 1 hour).
	 */
	public function __construct(
		FormRepositoryInterface $repository,
		CacheInterface $cache,
		int $expiration = 3600
	) {
		$this->repository = $repository;
		$this->cache      = $cache;
		$this->expiration = $expiration;
	}

	/**
	 * Get all forms
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function findAll(): array {
		$key = 'forms:all';

		return $this->cache->remember(
			$key,
			fn() => $this->repository->findAll(),
			$this->expiration
		);
	}

	/**
	 * Find form by ID
	 *
	 * @param int $id Form ID.
	 * @return array<string, mixed>|null
	 */
	public function findById( int $id ): ?array {
		$key = $this->makeKey( 'form', $id );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->findById( $id ),
			$this->expiration
		);
	}

	/**
	 * Find field by form ID and field ID
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return GF_Field|null
	 */
	public function findField( int $form_id, int $field_id ): ?GF_Field {
		$key = $this->makeKey( 'field', $form_id, $field_id );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->findField( $form_id, $field_id ),
			$this->expiration
		);
	}

	/**
	 * Find all forms containing a specific field type
	 *
	 * @param string $field_type Field type to search for.
	 * @return array<int, array{form: array<string, mixed>, field: GF_Field}>
	 */
	public function findByFieldType( string $field_type ): array {
		$key = $this->makeKey( 'forms_by_field_type', $field_type );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->findByFieldType( $field_type ),
			$this->expiration
		);
	}

	/**
	 * Check if field exists on form
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return bool
	 */
	public function fieldExists( int $form_id, int $field_id ): bool {
		$key = $this->makeKey( 'field_exists', $form_id, $field_id );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->fieldExists( $form_id, $field_id ),
			$this->expiration
		);
	}

	/**
	 * Get field label
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return string|null
	 */
	public function getFieldLabel( int $form_id, int $field_id ): ?string {
		$key = $this->makeKey( 'field_label', $form_id, $field_id );

		return $this->cache->remember(
			$key,
			fn() => $this->repository->getFieldLabel( $form_id, $field_id ),
			$this->expiration
		);
	}

	/**
	 * Make cache key
	 *
	 * @param string $prefix Prefix.
	 * @param mixed  ...$parts Key parts.
	 * @return string
	 */
	private function makeKey( string $prefix, ...$parts ): string {
		return $prefix . ':' . implode( ':', array_map( 'strval', $parts ) );
	}

	/**
	 * Clear cache for a form
	 *
	 * @param int $form_id Form ID.
	 * @return void
	 */
	public function clearCache( int $form_id ): void {
		// Clear specific form cache
		$this->cache->delete( $this->makeKey( 'form', $form_id ) );

		// Clear all forms cache (since it includes this form)
		$this->cache->delete( 'forms:all' );

		// Note: We can't easily clear all field caches for a form
		// without knowing all field IDs, so we rely on TTL expiration
	}

	/**
	 * Clear all form caches
	 *
	 * @return void
	 */
	public function clearAllCaches(): void {
		$this->cache->delete( 'forms:all' );
		// Individual caches will expire based on TTL
	}
}
