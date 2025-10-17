<?php
/**
 * Plugin Name: Gravity Forms to WooCommerce Cart
 * Description: Adds Gravity Forms submissions to WooCommerce cart with custom data for the shutter form
 * Version: 2.4.2
 * Author: Ben Hughes
 * Requires at least: 5.0
 * Requires PHP: 8.2
 * Text Domain: gf-wc-bridge
 *
 * @package BenHughes\GravityFormsWC
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'BENHUGHES_GF_WC_VERSION', '2.4.2' );
define( 'BENHUGHES_GF_WC_FILE', __FILE__ );
define( 'BENHUGHES_GF_WC_PATH', plugin_dir_path( __FILE__ ) );
define( 'BENHUGHES_GF_WC_URL', plugin_dir_url( __FILE__ ) );

// Composer autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
    add_action(
        'admin_notices',
        function () {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>Gravity Forms to WooCommerce Cart:</strong>
                    Composer autoloader not found. Please run <code>composer install</code> in the plugin directory.
                </p>
            </div>
            <?php
        }
    );
    return;
}

require_once $autoloader;

// Lifecycle handlers
\add_action(
    'plugins_loaded',
    static function () {
        // Defer class loading until autoloader is available
        if ( ! class_exists( '\\BenHughes\\GravityFormsWC\\Admin\\Lifecycle' ) ) {
            return;
        }
    }
);

// Initialize plugin
Plugin::get_instance( __FILE__, BENHUGHES_GF_WC_VERSION );

// Load text domain for translations
add_action(
    'init',
    static function () {
        \load_plugin_textdomain(
            'gf-wc-bridge',
            false,
            dirname( \plugin_basename( __FILE__ ) ) . '/languages'
        );
    },
    1 // Priority 1 to ensure translations load before components initialize
);

// Auto-update opt-in for this plugin (toggle via option 'gf_wc_auto_update')
add_filter(
    'auto_update_plugin',
    static function ( $update, $item ) {
        try {
            $target = plugin_basename( __FILE__ );
            if ( isset( $item->plugin ) && $item->plugin === $target ) {
                $opt_in = ( '1' === get_option( 'gf_wc_auto_update', '0' ) );
                return (bool) ( $update || $opt_in );
            }
        } catch ( \Throwable $e ) {
            // Fail-safe: do not force updates on errors
        }
        return $update;
    },
    10,
    2
);

// Register activation/deactivation hooks
\register_activation_hook(
    __FILE__,
    [ '\\BenHughes\\GravityFormsWC\\Admin\\Lifecycle', 'activate' ]
);

\register_deactivation_hook(
    __FILE__,
    [ '\\BenHughes\\GravityFormsWC\\Admin\\Lifecycle', 'deactivate' ]
);

// GitHub updater (Plugin Update Checker) - optional
add_action(
    'init',
    static function () {
        // Only proceed if PUC is installed and a repo is configured
        if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
            return;
        }
        // Use a baked-in public repository URL (no configuration needed)
        $repo = 'https://github.com/benjameshughes/benhughes-gf-wc';

        try {
            $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                $repo,
                __FILE__,
                \plugin_basename( __FILE__ )
            );

            // Use release assets if zips are uploaded to Releases
            $api = $update_checker->getVcsApi();
            if ( method_exists( $api, 'enableReleaseAssets' ) ) {
                $api->enableReleaseAssets();
            }

            // No token required for public repos
        } catch ( \Throwable $e ) {
            // Swallow errors silently; updater is optional
        }
    }
);
