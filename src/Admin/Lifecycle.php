<?php
/**
 * Plugin Lifecycle Handlers
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

class Lifecycle {

    /**
     * Run on plugin activation
     *
     * @return void
     */
    public static function activate(): void {
        // Set default options if not present
        if ( false === get_option( 'gf_wc_debug_mode', false ) ) {
            add_option( 'gf_wc_debug_mode', '0' );
        }

        // Store current plugin version for diagnostics/migrations
        if ( defined( 'BENHUGHES_GF_WC_VERSION' ) ) {
            update_option( 'gf_wc_version', BENHUGHES_GF_WC_VERSION );
        }

        // Flush rewrite rules (safe, covers REST routes if ever needed)
        flush_rewrite_rules(false);
    }

    /**
     * Run on plugin deactivation
     *
     * @return void
     */
    public static function deactivate(): void {
        // Flush rewrite rules
        flush_rewrite_rules(false);

        // No persistent scheduled events to clear
    }
}

