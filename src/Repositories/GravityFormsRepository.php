<?php
/**
 * Gravity Forms Repository
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Repositories;

use GF_Field;
use GFAPI;

/**
 * Concrete implementation using Gravity Forms API
 *
 * Provides abstraction layer over GFAPI for dependency injection
 */
class GravityFormsRepository implements FormRepositoryInterface {

	/**
	 * Get all forms
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function findAll(): array {
		return GFAPI::get_forms();
	}

	/**
	 * Find form by ID
	 *
	 * @param int $id Form ID.
	 * @return array<string, mixed>|null
	 */
	public function findById( int $id ): ?array {
		$form = GFAPI::get_form( $id );
		return $form ?: null;
	}

	/**
	 * Find field by form ID and field ID
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return GF_Field|null
	 */
	public function findField( int $form_id, int $field_id ): ?GF_Field {
		$form = $this->findById( $form_id );

		if ( ! $form ) {
			return null;
		}

		$field = GFAPI::get_field( $form, $field_id );
		return $field ?: null;
	}

	/**
	 * Find all forms containing a specific field type
	 *
	 * @param string $field_type Field type to search for.
	 * @return array<int, array{form: array<string, mixed>, field: GF_Field}>
	 */
	public function findByFieldType( string $field_type ): array {
		$forms   = $this->findAll();
		$results = [];

		foreach ( $forms as $form ) {
			if ( empty( $form['fields'] ) ) {
				continue;
			}

			foreach ( $form['fields'] as $field ) {
				if ( $field->type === $field_type ) {
					$results[] = [
						'form'  => $form,
						'field' => $field,
					];
				}
			}
		}

		return $results;
	}

	/**
	 * Check if field exists on form
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return bool
	 */
	public function fieldExists( int $form_id, int $field_id ): bool {
		return null !== $this->findField( $form_id, $field_id );
	}

	/**
	 * Get field label
	 *
	 * @param int $form_id  Form ID.
	 * @param int $field_id Field ID.
	 * @return string|null
	 */
	public function getFieldLabel( int $form_id, int $field_id ): ?string {
		$field = $this->findField( $form_id, $field_id );

		if ( ! $field ) {
			return null;
		}

		return $field->get_field_label( false, '' );
	}
}
