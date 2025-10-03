<?php
/**
 * WooCommerce Price Calculator Field
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Fields;

use GF_Field;
use GFCommon;

/**
 * Custom Gravity Forms Field: WooCommerce Price Calculator
 *
 * Modern PHP 8.4 implementation with full type declarations
 */
class PriceCalculator extends GF_Field {

    /**
     * Field type identifier
     *
     * @var string
     */
    public $type = 'wc_price_calculator';

    /**
     * WooCommerce product ID for pricing
     *
     * @var int|string
     */
    public $wcProductId = 0;

    /**
     * Width field ID
     *
     * @var int|string
     */
    public $widthFieldId = 30;

    /**
     * Drop field ID
     *
     * @var int|string
     */
    public $dropFieldId = 23;

    /**
     * Unit field ID (radio field for mm/cm/in)
     *
     * @var int|string
     */
    public $unitFieldId = 0;

    /**
     * Display as text instead of input
     *
     * @var bool
     */
    public $displayAsText = true;

    /**
     * Display size (large, medium, small)
     *
     * @var string
     */
    public $displaySize = 'medium';

    /**
     * Price prefix text
     *
     * @var string
     */
    public $pricePrefix = '';

    /**
     * Price suffix text
     *
     * @var string
     */
    public $priceSuffix = '';

    /**
     * Show calculation breakdown
     *
     * @var bool
     */
    public $showCalculation = false;

    /**
     * Show sale price comparison
     *
     * @var bool
     */
    public $showSaleComparison = false;

    /**
     * Currency symbol
     *
     * @var string
     */
    public $currencySymbol = '£';

    /**
     * Get field title for form editor
     *
     * @return string
     */
    public function get_form_editor_field_title(): string {
        return esc_attr__( 'WC Price Calculator', 'gravityforms' );
    }

    /**
     * Get button configuration for form editor
     *
     * @return array<string, string>
     */
    public function get_form_editor_button(): array {
        return [
            'group' => 'pricing_fields',
            'text'  => $this->get_form_editor_field_title(),
        ];
    }

    /**
     * Get field settings for form editor
     *
     * @return array<int, string>
     */
    public function get_form_editor_field_settings(): array {
        return [
            // Standard Gravity Forms settings
            'label_setting',
            'label_placement_setting',
            'admin_label_setting',
            'description_setting',
            'css_class_setting',
            'size_setting',
            'default_value_setting',
            'placeholder_setting',
            'visibility_setting',
            'duplicate_setting',
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'rules_setting',

            // Custom settings for this field
            'wc_product_setting',
            'width_field_setting',
            'drop_field_setting',
            'unit_field_setting',
            'display_format_setting',
            'currency_setting',
            'price_prefix_suffix_setting',
            'show_calculation_setting',
            'show_sale_setting',
        ];
    }

    /**
     * Check if value submission is empty
     *
     * @param int $form_id Form ID.
     * @return bool
     */
    public function is_value_submission_empty( $form_id ): bool {
        $value = rgpost( 'input_' . $this->id );
        return empty( $value ) && $value !== '0';
    }

    /**
     * Get value to save to entry
     *
     * @param string     $value      Field value.
     * @param array      $form       Form object.
     * @param string     $input_name Input name.
     * @param int        $lead_id    Entry ID.
     * @param array|null $lead       Entry object.
     * @return string
     */
    public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ): string {
        // Clean and format as decimal number (always use decimal point)
        return GFCommon::clean_number( $value, 'decimal_dot' );
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
        $form_id         = absint( $form['id'] );
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor  = $this->is_form_editor();

        $id       = (int) $this->id;
        $field_id = $is_entry_detail || $is_form_editor || $form_id === 0
            ? "input_{$id}"
            : "input_{$form_id}_{$id}";

        $value = esc_attr( $value );

        // Display as text/HTML output
        if ( $this->displayAsText && ! $is_entry_detail && ! $is_form_editor ) {
            return $this->get_text_display( $value, $form_id );
        }

        // Display as input field
        $size         = $this->size ?? 'medium';
        $class_suffix = $is_entry_detail ? '_admin' : '';
        $class        = $size . $class_suffix;
        $tabindex     = $this->get_tabindex();
        $disabled     = $is_form_editor ? 'disabled="disabled"' : 'readonly="readonly"';

        $input = sprintf(
            "<input name='input_%d' id='%s' type='text' value='%s' class='%s' %s %s />",
            $id,
            esc_attr( $field_id ),
            $value,
            esc_attr( $class ),
            $tabindex,
            $disabled
        );

        return sprintf( "<div class='ginput_container ginput_container_number'>%s</div>", $input );
    }

    /**
     * Get text display HTML
     *
     * Renders a complete template structure with all elements.
     * JavaScript will only update text values, not manipulate HTML.
     *
     * @param string $value   Field value.
     * @param int    $form_id Form ID.
     * @return string
     */
    private function get_text_display( string $value, int $form_id ): string {
        $id         = (int) $this->id;
        $size_class = 'gf-price-display-' . esc_attr( $this->displaySize );
        $currency   = ! empty( $this->currencySymbol ) ? esc_html( $this->currencySymbol ) : '£';

        ob_start();
        ?>
        <div class="ginput_container ginput_container_price_text <?php echo esc_attr( $size_class ); ?>"
             x-data="priceCalculator"
             data-form-id="<?php echo esc_attr( $form_id ); ?>"
             data-field-id="<?php echo esc_attr( $id ); ?>">

            <div class="gf-calculated-price">

                <?php if ( ! empty( $this->pricePrefix ) ) : ?>
                    <span class="gf-price-prefix"><?php echo esc_html( $this->pricePrefix ); ?> </span>
                <?php endif; ?>

                <!-- Sale Price Display: Stacked with Badges -->
                <div class="gf-price-stack" x-show="showRegularPrice">
                    <div class="gf-price-row gf-price-was">
                        <span class="gf-price-label">Normal Price</span>
                        <del class="gf-price-amount">
                            <span class="gf-currency"><?php echo $currency; ?></span><span x-text="regularPrice">0.00</span>
                        </del>
                    </div>
                    <div class="gf-price-row gf-price-now">
                        <span class="gf-price-label gf-label-highlight">Your Price</span>
                        <strong class="gf-price-amount gf-price-highlight">
                            <span class="gf-currency"><?php echo $currency; ?></span><span x-text="finalPrice">0.00</span>
                        </strong>
                    </div>
                    <div class="gf-savings-badge" x-show="savingsPercent > 0">
                        <span class="gf-savings-text">You Save <span x-text="savingsPercent">0</span>%!</span>
                    </div>
                </div>

                <!-- Regular Price Display: No Sale -->
                <div class="gf-price-regular-only" x-show="!showRegularPrice">
                    <strong class="gf-final-price">
                        <span class="gf-currency"><?php echo $currency; ?></span><span x-text="finalPrice">0.00</span>
                    </strong>
                </div>

                <?php if ( ! empty( $this->priceSuffix ) ) : ?>
                    <span class="gf-price-suffix"> <?php echo esc_html( $this->priceSuffix ); ?></span>
                <?php endif; ?>

                <span class="gf-calculation-breakdown" x-show="showCalculation" x-text="calculationText"></span>

            </div>

            <input type="hidden"
                   name="input_<?php echo $id; ?>"
                   id="input_<?php echo $form_id; ?>_<?php echo $id; ?>"
                   x-model="hiddenValue"
                   data-currency="<?php echo esc_attr( $currency ); ?>" />
        </div>
        <?php
        return ob_get_clean();
    }
}