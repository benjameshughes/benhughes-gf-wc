<?php
/**
 * Shutters Theme Layer
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.0.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Theme;

use Gravity_Forms\Gravity_Forms\Theme_Layers\API\Fluent\Theme_Layer_Builder;

/**
 * Registers a custom Gravity Forms theme using the Fluent API
 */
class ShuttersTheme {

    /**
     * Plugin directory URL
     *
     * @var string
     */
    private string $plugin_url;

    /**
     * Plugin version
     *
     * @var string
     */
    private string $version;

    /**
     * Constructor
     *
     * @param string $plugin_url Plugin directory URL.
     * @param string $version    Plugin version.
     */
    public function __construct( string $plugin_url, string $version ) {
        $this->plugin_url = $plugin_url;
        $this->version    = $version;

        add_action( 'gform_loaded', [ $this, 'register_theme' ], 20 );
    }

    /**
     * Register the custom theme layer
     *
     * @return void
     */
    public function register_theme(): void {
        // Check if Theme_Layer_Builder exists (GF 2.7+)
        if ( ! class_exists( 'Gravity_Forms\Gravity_Forms\Theme_Layers\API\Fluent\Theme_Layer_Builder' ) ) {
            return;
        }

        $layer = new Theme_Layer_Builder();
        $layer->set_name( 'shutters_clean' )
              ->set_short_title( __( 'Shutters Clean', 'gf-wc-bridge' ) )
              ->set_icon( 'gform-icon--window' )
              ->set_form_css_properties( [ $this, 'get_css_properties' ] )
              ->set_styles(
                  [
                      'handle' => 'gf-shutters-theme',
                      'src'    => $this->plugin_url . 'assets/shutters-theme.css',
                      'deps'   => [],
                      'ver'    => $this->version,
                      'media'  => 'all',
                  ]
              )
              ->register();
    }

    /**
     * Get CSS properties for the theme
     *
     * @param array $form     Form array.
     * @param array $settings Form settings.
     * @return array CSS custom properties.
     */
    public function get_css_properties( array $form, array $settings ): array {
        return [
            // Primary Colors
            '--gf-color-primary'              => '#3b82f6',
            '--gf-color-primary-rgb'          => '59, 130, 246',
            '--gf-color-primary-contrast'     => '#ffffff',
            '--gf-color-primary-darker'       => '#2563eb',
            '--gf-color-primary-lighter'      => '#eff6ff',

            // Control Styles (Inputs, Textareas, Selects)
            '--gf-ctrl-bg-color'              => '#ffffff',
            '--gf-ctrl-border-color'          => '#e2e8f0',
            '--gf-ctrl-border-color-focus'    => '#3b82f6',
            '--gf-ctrl-border-radius'         => '0.5rem',
            '--gf-ctrl-border-width'          => '1px',
            '--gf-ctrl-padding-block'         => '0.625rem',
            '--gf-ctrl-padding-inline'        => '0.875rem',
            '--gf-ctrl-font-size'             => '0.875rem',
            '--gf-ctrl-line-height'           => '1.5',
            '--gf-ctrl-color'                 => '#0f172a',
            '--gf-ctrl-box-shadow'            => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            '--gf-ctrl-box-shadow-focus'      => '0 0 0 3px rgba(59, 130, 246, 0.1)',

            // Labels
            '--gf-label-color'                => '#0f172a',
            '--gf-label-font-size'            => '0.875rem',
            '--gf-label-font-weight'          => '500',
            '--gf-label-margin-bottom'        => '0.5rem',

            // Descriptions
            '--gf-description-color'          => '#475569',
            '--gf-description-font-size'      => '0.75rem',
            '--gf-description-line-height'    => '1.5',

            // Field Spacing
            '--gf-field-spacing'              => '1.5rem',

            // Buttons
            '--gf-button-bg-color'            => '#3b82f6',
            '--gf-button-bg-color-hover'      => '#2563eb',
            '--gf-button-color'               => '#ffffff',
            '--gf-button-border-radius'       => '0.5rem',
            '--gf-button-padding-block'       => '0.625rem',
            '--gf-button-padding-inline'      => '1.25rem',
            '--gf-button-font-size'           => '0.875rem',
            '--gf-button-font-weight'         => '500',
            '--gf-button-box-shadow'          => 'none',

            // Validation
            '--gf-validation-error-color'     => '#ef4444',
            '--gf-validation-success-color'   => '#10b981',

            // Progress Bar
            '--gf-progressbar-bg-color'       => '#f1f5f9',
            '--gf-progressbar-fill-color'     => '#3b82f6',
            '--gf-progressbar-border-radius'  => '9999px',
            '--gf-progressbar-height'         => '0.5rem',

            // Transitions
            '--gf-transition-duration'        => '150ms',
            '--gf-transition-timing'          => 'cubic-bezier(0.4, 0, 0.2, 1)',
        ];
    }
}