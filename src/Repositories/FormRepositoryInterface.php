<?php
/**
 * Form Repository Interface
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Repositories;

use GF_Field;

/**
 * Interface for form data access
 *
 * Abstracts Gravity Forms API for testability and flexibility
 */
interface FormRepositoryInterface {

	/**
	 * Get all forms
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function findAll(): array;

	/**
	 * Find form by ID
	 *
	 * @param int $id Form ID.
	 * @return array<string, mixed>|null Form array or null if not found.
	 */
	public function findById( int $id ): ?array;

	/**
	 * Find field by form ID and field ID
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return GF_Field|null Field object or null if not found.
	 */
	public function findField( int $form_id, int $field_id ): ?GF_Field;

	/**
	 * Find all forms containing a specific field type
	 *
	 * @param string $field_type Field type to search for.
	 * @return array<int, array{form: array<string, mixed>, field: GF_Field}>
	 */
	public function findByFieldType( string $field_type ): array;

	/**
	 * Check if field exists on form
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return bool
	 */
	public function fieldExists( int $form_id, int $field_id ): bool;

	/**
	 * Get field label
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return string|null Field label or null if not found.
	 */
	public function getFieldLabel( int $form_id, int $field_id ): ?string;
}
