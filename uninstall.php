<?php
/**
 * Plugin uninstall cleanup
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options and transient data
delete_option( 'gf_wc_debug_mode' );
delete_option( 'gf_wc_version' );

// Attempt to remove transient rows used for on-page confirmations
// This uses direct SQL to delete matching transients and their timeouts
global $wpdb;
if ( isset( $wpdb->options ) ) {
    $like_key      = $wpdb->esc_like( '_transient_gf_wc_cart_confirmation_' ) . '%';
    $like_timeout  = $wpdb->esc_like( '_transient_timeout_gf_wc_cart_confirmation_' ) . '%';
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$like_key}'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$like_timeout}'" );
    // phpcs:enable
}

// Note: Transients set per form (gf_wc_cart_confirmation_*) cannot be reliably bulk-deleted
// without a direct DB query. They are short-lived (30s) and will expire naturally.
