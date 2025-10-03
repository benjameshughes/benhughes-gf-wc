<?php
/**
 * Editor JavaScript Handler
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

/**
 * Handles JavaScript injection for Gravity Forms editor
 */
class EditorScript {

    /**
     * Initialize hooks
     */
    public function __construct() {
        add_action( 'gform_editor_js', [ $this, 'render_script' ] );
    }

    /**
     * Render editor JavaScript
     *
     * @return void
     */
    public function render_script(): void {
        ?>
        <style>
            .gf-wc-validation-status {
                display: inline-block;
                margin-left: 10px;
                font-size: 14px;
                font-weight: 500;
            }
            .gf-wc-validation-status.valid {
                color: #00a32a;
            }
            .gf-wc-validation-status.invalid {
                color: #d63638;
            }
            .gf-wc-help-text {
                display: block;
                margin-top: 5px;
                color: #646970;
                font-size: 12px;
                line-height: 1.5;
            }
            .gf-wc-help-text strong {
                color: #1d2327;
            }
            #unit-field-validation {
                padding: 8px 12px;
                background: #f0f0f1;
                border-radius: 4px;
                border-left: 4px solid #646970;
            }
            #unit-field-validation span[style*="color: #00a32a"] {
                border-left-color: #00a32a !important;
            }
            #unit-field-validation span[style*="color: #d63638"] {
                border-left-color: #d63638 !important;
            }
            #unit-field-validation code {
                background: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 11px;
                color: #1d2327;
            }
        </style>
        <script type='text/javascript'>
        jQuery(document).ready(function($) {
            // Add custom field to supported settings (standard + custom)
            fieldSettings.wc_price_calculator = '.label_setting, .label_placement_setting, .admin_label_setting, .description_setting, .css_class_setting, .size_setting, .default_value_setting, .placeholder_setting, .visibility_setting, .duplicate_setting, .conditional_logic_field_setting, .prepopulate_field_setting, .error_message_setting, .rules_setting, .wc_product_setting, .width_field_setting, .drop_field_setting, .unit_field_setting, .display_format_setting, .currency_setting, .price_prefix_suffix_setting, .show_calculation_setting, .show_sale_setting';

            // Add measurement_unit field settings
            fieldSettings.measurement_unit = '.label_setting, .description_setting, .css_class_setting, .admin_label_setting, .visibility_setting, .conditional_logic_field_setting, .rules_setting, .measurement_width_field_setting, .measurement_drop_field_setting';

            // Bind to field selection change for Price Calculator
            $(document).on('gform_load_field_settings', function(event, field, form) {
                // Handle wc_price_calculator field type
                if (field.type === 'wc_price_calculator') {
                    handlePriceCalculatorSettings(field, form);
                }

                // Handle measurement_unit field type
                if (field.type === 'measurement_unit') {
                    handleMeasurementUnitSettings(field, form);
                }
            });

            function handlePriceCalculatorSettings(field, form) {

                // Populate field selectors with number fields from current form
                var widthSelect = $('#width-field-id');
                var dropSelect = $('#drop-field-id');
                var unitSelect = $('#unit-field-id');

                // Clear existing options except first
                widthSelect.find('option:not(:first)').remove();
                dropSelect.find('option:not(:first)').remove();
                unitSelect.find('option:not(:first)').remove();

                // Add number fields as options
                $.each(form.fields, function(index, formField) {
                    if (formField.type === 'number') {
                        var option = $('<option></option>')
                            .val(formField.id)
                            .text(formField.label + ' (ID: ' + formField.id + ')');

                        widthSelect.append(option.clone());
                        dropSelect.append(option.clone());
                    }

                    // Add measurement_unit fields to unit selector
                    if (formField.type === 'measurement_unit') {
                        var option = $('<option></option>')
                            .val(formField.id)
                            .text(formField.label + ' (ID: ' + formField.id + ')');

                        unitSelect.append(option);
                    }
                });

                // Set current values
                $('#wc-product-id').val(field.wcProductId || '');
                $('#width-field-id').val(field.widthFieldId || '');
                $('#drop-field-id').val(field.dropFieldId || '');
                $('#unit-field-id').val(field.unitFieldId || '');
                $('#display-as-text').prop('checked', field.displayAsText == true);
                $('#display-size').val(field.displaySize || 'medium');
                $('#currency-symbol').val(field.currencySymbol || '£');
                $('#price-prefix').val(field.pricePrefix || '');
                $('#price-suffix').val(field.priceSuffix || '');
                $('#show-calculation').prop('checked', field.showCalculation == true);
                $('#show-sale-comparison').prop('checked', field.showSaleComparison == true);

                // Add validation indicators
                updateValidationStatus();
            }

            function handleMeasurementUnitSettings(field, form) {
                // Populate field selectors with number fields from current form
                var measurementWidthSelect = $('#measurement-width-field-id');
                var measurementDropSelect = $('#measurement-drop-field-id');

                // Clear existing options except first
                measurementWidthSelect.find('option:not(:first)').remove();
                measurementDropSelect.find('option:not(:first)').remove();

                // Add number fields as options
                $.each(form.fields, function(index, formField) {
                    if (formField.type === 'number') {
                        var option = $('<option></option>')
                            .val(formField.id)
                            .text(formField.label + ' (ID: ' + formField.id + ')');

                        measurementWidthSelect.append(option.clone());
                        measurementDropSelect.append(option.clone());
                    }
                });

                // Set current values
                $('#measurement-width-field-id').val(field.widthFieldId || '');
                $('#measurement-drop-field-id').val(field.dropFieldId || '');
            }

            // Update validation status when fields change
            $('#wc-product-id, #width-field-id, #drop-field-id').on('change', function() {
                updateValidationStatus();
            });

            function updateValidationStatus() {
                // Remove existing status indicators
                $('.gf-wc-validation-status').remove();

                // Get current form
                var form = window.form;

                // Check product ID
                var productId = $('#wc-product-id').val();
                if (productId) {
                    $('#wc-product-id').after('<span class="gf-wc-validation-status valid">✓</span>');
                } else {
                    $('#wc-product-id').after('<span class="gf-wc-validation-status invalid">⚠ Required</span>');
                }

                // Check width field
                var widthFieldId = $('#width-field-id').val();
                if (widthFieldId) {
                    $('#width-field-id').after('<span class="gf-wc-validation-status valid">✓</span>');
                } else {
                    $('#width-field-id').after('<span class="gf-wc-validation-status invalid">⚠ Required</span>');
                }

                // Check drop field
                var dropFieldId = $('#drop-field-id').val();
                if (dropFieldId) {
                    $('#drop-field-id').after('<span class="gf-wc-validation-status valid">✓</span>');
                } else {
                    $('#drop-field-id').after('<span class="gf-wc-validation-status invalid">⚠ Required</span>');
                }
            }
        });
        </script>
        <?php
    }
}