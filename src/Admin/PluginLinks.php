<?php
/**
 * Plugin action links (Settings link on Plugins screen)
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

class PluginLinks {

    public function __construct() {
        $plugin = plugin_basename( dirname( __DIR__, 2 ) . '/benhughes-gf-wc.php' );
        add_filter( 'plugin_action_links_' . $plugin, [ $this, 'add_settings_link' ] );
        add_filter( 'plugin_row_meta', [ $this, 'add_row_meta' ], 10, 2 );
    }

    /**
     * Add Settings link to plugin row.
     *
     * @param array $links Existing links.
     * @return array
     */
    public function add_settings_link( array $links ): array {
        $url   = admin_url( 'admin.php?page=gf-wc-settings' );
        $label = esc_html__( 'Settings', 'gf-wc-bridge' );
        array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( $url ), $label ) );
        return $links;
    }

    /**
     * Add extra links under plugin row (Docs, Roadmap)
     *
     * @param array  $links  Existing meta links.
     * @param string $file   Plugin file.
     * @return array
     */
    public function add_row_meta( array $links, string $file ): array {
        $plugin_file = plugin_basename( dirname( __DIR__, 2 ) . '/benhughes-gf-wc.php' );
        if ( $file !== $plugin_file ) {
            return $links;
        }

        $docs    = '<a href="' . esc_url( admin_url( 'plugin-editor.php?file=benhughes-gf-wc/README-WPPB.md' ) ) . '">' . esc_html__( 'Developer README', 'gf-wc-bridge' ) . '</a>';
        $roadmap = '<a href="' . esc_url( admin_url( 'plugin-editor.php?file=benhughes-gf-wc/ROADMAP.md' ) ) . '">' . esc_html__( 'Roadmap', 'gf-wc-bridge' ) . '</a>';
        $links[] = $docs;
        $links[] = $roadmap;
        return $links;
    }
}
