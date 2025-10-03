<?php
/**
 * Field Settings UI
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

/**
 * Renders custom field settings in Gravity Forms editor
 */
class FieldSettings {

    /**
     * Initialize hooks
     */
    public function __construct() {
        add_action( 'gform_field_standard_settings', [ $this, 'render_settings' ], 10, 2 );
    }

    /**
     * Render custom field settings
     *
     * @param int $position Setting position.
     * @param int $form_id  Form ID.
     * @return void
     */
    public function render_settings( int $position, int $form_id ): void {
        // Position 25 = after label placement
        if ( $position !== 25 ) {
            return;
        }

        $this->render_product_setting();
        $this->render_width_field_setting();
        $this->render_drop_field_setting();
        $this->render_unit_field_setting();
        $this->render_display_format_setting();
        $this->render_currency_setting();
        $this->render_price_prefix_suffix_setting();
        $this->render_calculation_setting();
        $this->render_sale_comparison_setting();
        $this->render_measurement_width_field_setting();
        $this->render_measurement_drop_field_setting();
    }

    /**
     * Render WooCommerce product selector
     *
     * @return void
     */
    private function render_product_setting(): void {
        ?>
        <li class="wc_product_setting field_setting">
            <label for="wc-product-id" class="section_label">
                <?php esc_html_e( 'Product to Sell', 'gravityforms' ); ?>
                <?php gform_tooltip( 'wc_product_id' ); ?>
            </label>
            <select id="wc-product-id" onchange="SetFieldProperty('wcProductId', this.value);">
                <option value=""><?php esc_html_e( 'Choose a product...', 'gravityforms' ); ?></option>
                <?php
                // Get all WooCommerce products
                if ( class_exists( 'WooCommerce' ) ) {
                    $products = wc_get_products(
                        [
                            'limit'   => -1,
                            'status'  => 'publish',
                            'orderby' => 'title',
                            'order'   => 'ASC',
                        ]
                    );

                    foreach ( $products as $product ) {
                        printf(
                            '<option value="%d">%s (£%s)</option>',
                            esc_attr( $product->get_id() ),
                            esc_html( $product->get_name() ),
                            esc_html( $product->get_price() )
                        );
                    }
                }
                ?>
            </select>
            <span class="gf-wc-help-text">
                <?php esc_html_e( 'This product will be added to the cart when customers submit the form', 'gravityforms' ); ?>
            </span>
        </li>
        <?php
    }

    /**
     * Render width field selector
     *
     * @return void
     */
    private function render_width_field_setting(): void {
        ?>
        <li class="width_field_setting field_setting">
            <label for="width-field-id" class="section_label">
                <?php esc_html_e( 'Width Field', 'gravityforms' ); ?>
                <?php gform_tooltip( 'width_field_id' ); ?>
            </label>
            <select id="width-field-id" onchange="SetFieldProperty('widthFieldId', this.value);">
                <option value=""><?php esc_html_e( 'Select width field...', 'gravityforms' ); ?></option>
                <!-- Will be populated dynamically with form fields -->
            </select>
            <span class="gf-wc-help-text">
                <?php esc_html_e( 'Select the field that contains width measurement (number fields only)', 'gravityforms' ); ?>
            </span>
        </li>
        <?php
    }

    /**
     * Render drop field selector
     *
     * @return void
     */
    private function render_drop_field_setting(): void {
        ?>
        <li class="drop_field_setting field_setting">
            <label for="drop-field-id" class="section_label">
                <?php esc_html_e( 'Drop/Height Field', 'gravityforms' ); ?>
                <?php gform_tooltip( 'drop_field_id' ); ?>
            </label>
            <select id="drop-field-id" onchange="SetFieldProperty('dropFieldId', this.value);">
                <option value=""><?php esc_html_e( 'Select drop/height field...', 'gravityforms' ); ?></option>
                <!-- Will be populated dynamically with form fields -->
            </select>
            <span class="gf-wc-help-text">
                <?php esc_html_e( 'Select the field that contains drop/height measurement (number fields only)', 'gravityforms' ); ?>
            </span>
        </li>
        <?php
    }

    /**
     * Render unit field selector
     *
     * @return void
     */
    private function render_unit_field_setting(): void {
        ?>
        <li class="unit_field_setting field_setting">
            <label for="unit-field-id" class="section_label">
                <?php esc_html_e( 'Measurement Unit Field (Optional)', 'gravityforms' ); ?>
                <?php gform_tooltip( 'unit_field_id' ); ?>
            </label>
            <select id="unit-field-id" onchange="SetFieldProperty('unitFieldId', this.value);">
                <option value=""><?php esc_html_e( 'None - use centimeters (cm) only', 'gravityforms' ); ?></option>
                <!-- Will be populated dynamically with form fields -->
            </select>
            <span class="gf-wc-help-text">
                <strong><?php esc_html_e( 'Allow customers to choose their measurement unit.', 'gravityforms' ); ?></strong><br>
                <?php esc_html_e( 'Select a Measurement Unit field from the form. If none selected, centimeters (cm) will be used.', 'gravityforms' ); ?><br>
                <?php esc_html_e( 'Prices will calculate correctly regardless of unit - all conversions happen automatically.', 'gravityforms' ); ?>
            </span>
        </li>
        <?php
    }

    /**
     * Render display format setting
     *
     * @return void
     */
    private function render_display_format_setting(): void {
        ?>
        <li class="display_format_setting field_setting">
            <label for="display-as-text" class="section_label">
                <?php esc_html_e( 'Display Format', 'gravityforms' ); ?>
            </label>
            <input type="checkbox" id="display-as-text" onclick="SetFieldProperty('displayAsText', this.checked);" />
            <label for="display-as-text" class="inline">
                <?php esc_html_e( 'Display as text (not input)', 'gravityforms' ); ?>
            </label>
            <br/>
            <label for="display-size" style="margin-top: 10px; display: block;">
                <?php esc_html_e( 'Text Size', 'gravityforms' ); ?>
            </label>
            <select id="display-size" onchange="SetFieldProperty('displaySize', this.value);">
                <option value="small"><?php esc_html_e( 'Small', 'gravityforms' ); ?></option>
                <option value="medium"><?php esc_html_e( 'Medium', 'gravityforms' ); ?></option>
                <option value="large"><?php esc_html_e( 'Large', 'gravityforms' ); ?></option>
            </select>
        </li>
        <?php
    }

    /**
     * Render currency setting
     *
     * @return void
     */
    private function render_currency_setting(): void {
        ?>
        <li class="currency_setting field_setting">
            <label for="currency-symbol" class="section_label">
                <?php esc_html_e( 'Currency', 'gravityforms' ); ?>
                <?php gform_tooltip( 'currency_symbol' ); ?>
            </label>
            <select id="currency-symbol" onchange="SetFieldProperty('currencySymbol', this.value);">
                <option value="£">£ GBP (Pound)</option>
                <option value="$">$ USD (Dollar)</option>
                <option value="€">€ EUR (Euro)</option>
                <option value="¥">¥ JPY (Yen)</option>
                <option value="₹">₹ INR (Rupee)</option>
                <option value="A$">A$ AUD (Australian Dollar)</option>
                <option value="C$">C$ CAD (Canadian Dollar)</option>
                <option value="CHF">CHF (Swiss Franc)</option>
                <option value="kr">kr SEK (Swedish Krona)</option>
                <option value="R">R ZAR (South African Rand)</option>
            </select>
        </li>
        <?php
    }

    /**
     * Render price prefix/suffix setting
     *
     * @return void
     */
    private function render_price_prefix_suffix_setting(): void {
        ?>
        <li class="price_prefix_suffix_setting field_setting">
            <label for="price-prefix" class="section_label">
                <?php esc_html_e( 'Price Text', 'gravityforms' ); ?>
            </label>
            <div style="margin-bottom: 8px;">
                <label for="price-prefix" class="inline">
                    <?php esc_html_e( 'Prefix', 'gravityforms' ); ?>
                </label>
                <input type="text" id="price-prefix" class="field-size-medium"
                       onkeyup="SetFieldProperty('pricePrefix', this.value);"
                       placeholder="<?php esc_attr_e( 'Your Price:', 'gravityforms' ); ?>" />
            </div>
            <div>
                <label for="price-suffix" class="inline">
                    <?php esc_html_e( 'Suffix', 'gravityforms' ); ?>
                </label>
                <input type="text" id="price-suffix" class="field-size-medium"
                       onkeyup="SetFieldProperty('priceSuffix', this.value);"
                       placeholder="<?php esc_attr_e( 'inc. VAT', 'gravityforms' ); ?>" />
            </div>
        </li>
        <?php
    }

    /**
     * Render calculation breakdown setting
     *
     * @return void
     */
    private function render_calculation_setting(): void {
        ?>
        <li class="show_calculation_setting field_setting">
            <input type="checkbox" id="show-calculation" onclick="SetFieldProperty('showCalculation', this.checked);" />
            <label for="show-calculation" class="inline">
                <?php esc_html_e( 'Show calculation breakdown', 'gravityforms' ); ?>
            </label>
        </li>
        <?php
    }

    /**
     * Render sale comparison setting
     *
     * @return void
     */
    private function render_sale_comparison_setting(): void {
        ?>
        <li class="show_sale_setting field_setting">
            <input type="checkbox" id="show-sale-comparison" onclick="SetFieldProperty('showSaleComparison', this.checked);" />
            <label for="show-sale-comparison" class="inline">
                <?php esc_html_e( 'Show sale price comparison (when product is on sale)', 'gravityforms' ); ?>
            </label>
        </li>
        <?php
    }

    /**
     * Render measurement width field setting
     *
     * @return void
     */
    private function render_measurement_width_field_setting(): void {
        ?>
        <li class="measurement_width_field_setting field_setting">
            <label for="measurement-width-field-id" class="section_label">
                <?php esc_html_e( 'Width Field to Update', 'gravityforms' ); ?>
                <?php gform_tooltip( 'measurement_width_field_id' ); ?>
            </label>
            <select id="measurement-width-field-id" onchange="SetFieldProperty('widthFieldId', this.value);">
                <option value=""><?php esc_html_e( 'Select width field...', 'gravityforms' ); ?></option>
                <!-- Will be populated dynamically with form fields -->
            </select>
            <span class="gf-wc-help-text">
                <?php esc_html_e( 'Select the width number field whose label will be updated with the unit (e.g., "Width (cm)")', 'gravityforms' ); ?>
            </span>
        </li>
        <?php
    }

    /**
     * Render measurement drop field setting
     *
     * @return void
     */
    private function render_measurement_drop_field_setting(): void {
        ?>
        <li class="measurement_drop_field_setting field_setting">
            <label for="measurement-drop-field-id" class="section_label">
                <?php esc_html_e( 'Drop/Height Field to Update', 'gravityforms' ); ?>
                <?php gform_tooltip( 'measurement_drop_field_id' ); ?>
            </label>
            <select id="measurement-drop-field-id" onchange="SetFieldProperty('dropFieldId', this.value);">
                <option value=""><?php esc_html_e( 'Select drop/height field...', 'gravityforms' ); ?></option>
                <!-- Will be populated dynamically with form fields -->
            </select>
            <span class="gf-wc-help-text">
                <?php esc_html_e( 'Select the drop/height number field whose label will be updated with the unit (e.g., "Drop (in)")', 'gravityforms' ); ?>
            </span>
        </li>
        <?php
    }
}