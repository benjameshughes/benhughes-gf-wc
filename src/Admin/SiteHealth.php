<?php
/**
 * Site Health Debug Info
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

use BenHughes\GravityFormsWC\Validation\ConfigValidator;

class SiteHealth {

    private ConfigValidator $validator;

    public function __construct( ConfigValidator $validator ) {
        $this->validator = $validator;
        add_filter( 'debug_information', [ $this, 'add_debug_info' ] );
    }

    /**
     * Add plugin debug information to Site Health → Info.
     *
     * @param array $info
     * @return array
     */
    public function add_debug_info( array $info ): array {
        // Resolve GF version robustly
        $gf_version = 'not detected';
        if ( class_exists( '\\GFForms' ) && method_exists( '\\GFForms', 'get_version' ) ) {
            $gf_version = (string) \GFForms::get_version();
        } elseif ( defined( 'GF_VERSION' ) ) {
            $gf_version = (string) GF_VERSION;
        } else {
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $plugins = get_plugins();
            if ( isset( $plugins['gravityforms/gravityforms.php']['Version'] ) ) {
                $gf_version = (string) $plugins['gravityforms/gravityforms.php']['Version'];
            }
        }

        // Resolve WC version
        $wc_version = 'not detected';
        if ( defined( 'WC_VERSION' ) ) {
            $wc_version = (string) WC_VERSION;
        } elseif ( class_exists( '\\WooCommerce' ) && function_exists( '\\WC' ) && \WC() ) {
            $wc_version = (string) \WC()->version;
        } else {
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $plugins = get_plugins();
            if ( isset( $plugins['woocommerce/woocommerce.php']['Version'] ) ) {
                $wc_version = (string) $plugins['woocommerce/woocommerce.php']['Version'];
            }
        }

        $configured_forms = [];
        if ( $this->validator->is_gravity_forms_active() ) {
            $configured_forms = $this->validator->get_configured_forms();
        }

        $info['gf_wc_cart'] = [
            'label'  => __( 'GF → WC Cart', 'gf-wc-bridge' ),
            'fields' => [
                'plugin_version'   => [
                    'label' => __( 'Plugin version', 'gf-wc-bridge' ),
                    'value' => $this->get_plugin_version(),
                ],
                'gravity_forms'    => [
                    'label' => __( 'Gravity Forms', 'gf-wc-bridge' ),
                    'value' => $gf_version,
                ],
                'woocommerce'      => [
                    'label' => __( 'WooCommerce', 'gf-wc-bridge' ),
                    'value' => $wc_version,
                ],
                'configured_forms' => [
                    'label' => __( 'Configured forms (calculator fields found)', 'gf-wc-bridge' ),
                    'value' => is_array( $configured_forms ) ? (string) count( $configured_forms ) : '0',
                ],
            ],
        ];

        return $info;
    }

    private function get_plugin_version(): string {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_file = dirname( dirname( __DIR__ ) ) . '/benhughes-gf-wc.php';
        if ( file_exists( $plugin_file ) ) {
            $data = get_plugin_data( $plugin_file, false, false );
            if ( ! empty( $data['Version'] ) ) {
                return (string) $data['Version'];
            }
        }
        return defined( 'BENHUGHES_GF_WC_VERSION' ) ? (string) BENHUGHES_GF_WC_VERSION : 'unknown';
    }
}
