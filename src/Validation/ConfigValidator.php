<?php
/**
 * Configuration Validator
 *
 * Validates Price Calculator field configurations
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.1.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Validation;

/**
 * Validates form and product configurations
 */
class ConfigValidator {

	/**
	 * Validation result codes
	 */
	public const VALID              = 'valid';
	public const ERROR_NO_PRODUCT   = 'no_product';
	public const ERROR_NO_WC        = 'no_woocommerce';
	public const ERROR_NO_GF        = 'no_gravity_forms';
	public const ERROR_INVALID_FIELD = 'invalid_field';
	public const ERROR_MISSING_CONFIG = 'missing_config';

	/**
	 * Check if WooCommerce is active
	 *
	 * @return bool
	 */
	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check if Gravity Forms is active
	 *
	 * @return bool
	 */
	public function is_gravity_forms_active(): bool {
		return class_exists( 'GFForms' );
	}

	/**
	 * Validate a product ID exists
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return bool
	 */
	public function validate_product_id( int $product_id ): bool {
		if ( ! $this->is_woocommerce_active() ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		return $product && $product->exists();
	}

	/**
	 * Get product name by ID
	 *
	 * @param int $product_id Product ID.
	 * @return string|null Product name or null if not found.
	 */
	public function get_product_name( int $product_id ): ?string {
		if ( ! $this->is_woocommerce_active() ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		return $product && $product->exists() ? $product->get_name() : null;
	}

	/**
	 * Validate a field ID exists on a form
	 *
	 * @param int   $form_id  Gravity Forms form ID.
	 * @param mixed $field_id Field ID (can be int or string for sub-fields).
	 * @return bool
	 */
	public function validate_field_id( int $form_id, $field_id ): bool {
		if ( ! $this->is_gravity_forms_active() ) {
			return false;
		}

		$form = \GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return false;
		}

		// Check if field exists
		$field = \GFAPI::get_field( $form, $field_id );
		return null !== $field;
	}

	/**
	 * Get field label by ID
	 *
	 * @param int   $form_id  Form ID.
	 * @param mixed $field_id Field ID.
	 * @return string|null Field label or null if not found.
	 */
	public function get_field_label( int $form_id, $field_id ): ?string {
		if ( ! $this->is_gravity_forms_active() ) {
			return null;
		}

		$form  = \GFAPI::get_form( $form_id );
		$field = \GFAPI::get_field( $form, $field_id );

		return $field ? $field->get_field_label( false, '' ) : null;
	}

	/**
	 * Validate complete Price Calculator field configuration
	 *
	 * @param array $config Configuration array with keys: formId, productId, widthFieldId, dropFieldId, priceFieldId.
	 * @return array Validation result with 'valid' bool and 'errors' array.
	 */
	public function validate_configuration( array $config ): array {
		$errors = [];

		// Check dependencies
		if ( ! $this->is_gravity_forms_active() ) {
			$errors[] = [
				'code'    => self::ERROR_NO_GF,
				'message' => __( 'Gravity Forms is not active', 'gf-wc-bridge' ),
			];
			return [ 'valid' => false, 'errors' => $errors ];
		}

		if ( ! $this->is_woocommerce_active() ) {
			$errors[] = [
				'code'    => self::ERROR_NO_WC,
				'message' => __( 'WooCommerce is not active', 'gf-wc-bridge' ),
			];
			return [ 'valid' => false, 'errors' => $errors ];
		}

		// Check required fields
		$required = [ 'formId', 'productId', 'widthFieldId', 'dropFieldId', 'priceFieldId' ];
		foreach ( $required as $key ) {
			if ( empty( $config[ $key ] ) ) {
				$errors[] = [
					'code'    => self::ERROR_MISSING_CONFIG,
					'message' => sprintf(
						/* translators: %s: configuration key name */
						__( 'Missing required configuration: %s', 'gf-wc-bridge' ),
						$key
					),
					'field'   => $key,
				];
			}
		}

		if ( ! empty( $errors ) ) {
			return [ 'valid' => false, 'errors' => $errors ];
		}

		$form_id    = (int) $config['formId'];
		$product_id = (int) $config['productId'];

		// Validate product
		if ( ! $this->validate_product_id( $product_id ) ) {
			$errors[] = [
				'code'    => self::ERROR_NO_PRODUCT,
				'message' => sprintf(
					/* translators: %d: product ID */
					__( 'Product ID %d does not exist in WooCommerce', 'gf-wc-bridge' ),
					$product_id
				),
				'field'   => 'productId',
			];
		}

		// Validate field IDs
		$field_map = [
			'widthFieldId' => __( 'Width Field', 'gf-wc-bridge' ),
			'dropFieldId'  => __( 'Drop Field', 'gf-wc-bridge' ),
			'priceFieldId' => __( 'Price Field', 'gf-wc-bridge' ),
		];

		foreach ( $field_map as $key => $label ) {
			if ( ! $this->validate_field_id( $form_id, $config[ $key ] ) ) {
				$errors[] = [
					'code'    => self::ERROR_INVALID_FIELD,
					'message' => sprintf(
						/* translators: 1: field label, 2: field ID */
						__( '%1$s (ID: %2$s) does not exist on this form', 'gf-wc-bridge' ),
						$label,
						$config[ $key ]
					),
					'field'   => $key,
				];
			}
		}

		return [
			'valid'  => empty( $errors ),
			'errors' => $errors,
		];
	}

	/**
	 * Get all forms that have Price Calculator fields
	 *
	 * @return array Array of forms with their calculator configs.
	 */
	public function get_configured_forms(): array {
		if ( ! $this->is_gravity_forms_active() ) {
			return [];
		}

		$forms            = \GFAPI::get_forms();
		$configured_forms = [];

		foreach ( $forms as $form ) {
			// Look for Price Calculator fields
			foreach ( $form['fields'] as $field ) {
				if ( 'wc_price_calculator' === $field->type ) {
					$config = [
						'formId'       => $form['id'],
						'formTitle'    => $form['title'],
						'fieldId'      => $field->id,
						'productId'    => (int) ( $field->wcProductId ?? 0 ),
						'widthFieldId' => $field->widthFieldId ?? '',
						'dropFieldId'  => $field->dropFieldId ?? '',
						'priceFieldId' => $field->id,
					];

					// Validate configuration
					$validation = $this->validate_configuration( $config );

					$configured_forms[] = [
						'form'       => $form,
						'field'      => $field,
						'config'     => $config,
						'validation' => $validation,
					];
				}
			}
		}

		return $configured_forms;
	}

	/**
	 * Get forms with configuration errors
	 *
	 * @return array Array of forms that have validation errors.
	 */
	public function get_forms_with_errors(): array {
		$configured = $this->get_configured_forms();

		return array_filter(
			$configured,
			function ( $item ) {
				return ! $item['validation']['valid'];
			}
		);
	}
}
