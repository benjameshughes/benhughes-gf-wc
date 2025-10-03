<?php
/**
 * Measurement Unit Field
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.2.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Fields;

use BenHughes\GravityFormsWC\Enums\MeasurementUnit as MeasurementUnitEnum;
use GF_Field_Radio;

/**
 * Custom Gravity Forms Field: Measurement Unit Selector
 *
 * Provides a radio button selector for measurement units (mm, cm, in)
 * with hardcoded, non-configurable choices to ensure consistency.
 */
class MeasurementUnit extends GF_Field_Radio {

	/**
	 * Field type identifier
	 *
	 * @var string
	 */
	public $type = 'measurement_unit';

	/**
	 * Width field ID to update label
	 *
	 * @var int|string
	 */
	public $widthFieldId = 0;

	/**
	 * Drop field ID to update label
	 *
	 * @var int|string
	 */
	public $dropFieldId = 0;

	/**
	 * Constructor
	 *
	 * Ensures choices are set on field instantiation
	 */
	public function __construct( $data = [] ) {
		parent::__construct( $data );
		$this->ensure_choices();

		// Add filter to inject Alpine attributes after field is fully rendered
		add_filter( 'gform_field_content', [ $this, 'add_alpine_attributes' ], 10, 5 );
	}

	/**
	 * Get default choices for measurement units
	 *
	 * Uses the MeasurementUnit enum for single source of truth
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_default_choices(): array {
		return MeasurementUnitEnum::choices();
	}

	/**
	 * Ensure choices are set
	 *
	 * Called by various methods to guarantee choices exist
	 *
	 * @return void
	 */
	private function ensure_choices(): void {
		if ( empty( $this->choices ) || count( $this->choices ) !== 3 ) {
			$this->choices = $this->get_default_choices();
		}
	}

	/**
	 * Get field title for form editor
	 *
	 * @return string
	 */
	public function get_form_editor_field_title(): string {
		return esc_attr__( 'Measurement Unit', 'gf-wc-bridge' );
	}

	/**
	 * Get default field label
	 *
	 * @return string
	 */
	public function get_form_editor_field_label(): string {
		return esc_attr__( 'Measurement Unit', 'gf-wc-bridge' );
	}

	/**
	 * Get default value for the field
	 *
	 * Uses the MeasurementUnit enum default
	 *
	 * @return string
	 */
	public function get_value_default(): string {
		return MeasurementUnitEnum::default()->value;
	}

	/**
	 * Get button configuration for form editor
	 *
	 * @return array<string, string>
	 */
	public function get_form_editor_button(): array {
		return [
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
		];
	}

	/**
	 * Get field settings for form editor
	 *
	 * Only expose minimal settings - choices are hardcoded and not configurable
	 *
	 * @return array<int, string>
	 */
	public function get_form_editor_field_settings(): array {
		// Ensure choices are set when field is loaded in editor
		$this->ensure_choices();

		return [
			'label_setting',
			'description_setting',
			'css_class_setting',
			'admin_label_setting',
			'visibility_setting',
			'conditional_logic_field_setting',
			'rules_setting',
			'measurement_width_field_setting',
			'measurement_drop_field_setting',
		];
	}

	/**
	 * Add Alpine.js attributes to field content after it's fully rendered
	 *
	 * @param string $content The field content.
	 * @param object $field   The field object.
	 * @param string $value   The field value.
	 * @param int    $entry_id Entry ID.
	 * @param int    $form_id  Form ID.
	 * @return string Modified content with Alpine attributes.
	 */
	public function add_alpine_attributes( string $content, $field, $value, $entry_id, $form_id ): string {
		// Only process this field type
		if ( $field->type !== 'measurement_unit' ) {
			return $content;
		}

		// Don't add in editor or entry detail
		if ( $this->is_entry_detail() || $this->is_form_editor() ) {
			return $content;
		}

		$width_field_id = absint( $field->widthFieldId ?? 0 );
		$drop_field_id  = absint( $field->dropFieldId ?? 0 );

		// Only add Alpine if width and drop are configured
		if ( $width_field_id > 0 && $drop_field_id > 0 ) {
			// Add Alpine attributes to the ginput_container div (which we CAN access in this filter)
			$alpine_attrs = sprintf(
				' x-data="measurementUnit" data-form-id="%s" data-field-id="%s" data-width-field-id="%s" data-drop-field-id="%s"',
				esc_attr( $form_id ),
				esc_attr( $field->id ),
				esc_attr( $width_field_id ),
				esc_attr( $drop_field_id )
			);

			// Inject into the ginput_container div
			$content = str_replace(
				"<div class='ginput_container ginput_container_radio'>",
				"<div class='ginput_container ginput_container_radio'" . $alpine_attrs . ">",
				$content
			);
		}

		return $content;
	}

	/**
	 * Get field input HTML
	 *
	 * @param array      $form  Form object.
	 * @param string     $value Field value.
	 * @param array|null $entry Entry object.
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ): string {
		// Ensure choices are always set
		$this->ensure_choices();

		// If a value is set, mark the appropriate choice as selected
		if ( ! empty( $value ) ) {
			foreach ( $this->choices as &$choice ) {
				$choice['isSelected'] = ( $choice['value'] === $value );
			}
		}

		// Use parent's radio rendering
		return parent::get_field_input( $form, $value, $entry );
	}

	/**
	 * Validate that submitted value is one of the allowed units
	 *
	 * Uses MeasurementUnit enum for validation
	 *
	 * @param string|array $value Submitted field value.
	 * @param array        $form  Form object.
	 * @return void
	 */
	public function validate( $value, $form ): void {
		if ( $this->isRequired && empty( $value ) ) {
			$this->failed_validation  = true;
			$this->validation_message = empty( $this->errorMessage )
				? esc_html__( 'Please select a measurement unit.', 'gf-wc-bridge' )
				: $this->errorMessage;
			return;
		}

		// Validate using enum - tryFrom returns null if invalid
		if ( ! empty( $value ) && null === MeasurementUnitEnum::tryFrom( $value ) ) {
			$this->failed_validation  = true;
			$this->validation_message = esc_html__( 'Invalid measurement unit selected.', 'gf-wc-bridge' );
		}
	}
}
