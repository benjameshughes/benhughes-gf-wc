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
        add_filter( 'plugin_action_links_' . $plugin, [ $this, 'add_check_updates_link' ] );
        add_filter( 'plugin_row_meta', [ $this, 'add_row_meta' ], 10, 2 );
        add_action( 'admin_notices', [ $this, 'maybe_show_update_notice' ] );
        add_action( 'admin_footer-plugins.php', [ $this, 'plugins_footer_script' ] );
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
     * Add a "Check for updates" action link under the plugin row on Plugins page.
     */
    public function add_check_updates_link( array $links ): array {
        $url = wp_nonce_url( admin_url( 'admin-post.php?action=gf_wc_check_updates&return=plugins' ), 'gf_wc_check_updates' );
        $label = esc_html__( 'Check for updates', 'gf-wc-bridge' );
        $links[] = '<a id="gf-wc-check-updates-plugin" href="' . esc_url( $url ) . '">' . $label . '</a>';
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

    /**
     * Show an admin notice on the Plugins page after a forced update check.
     */
    public function maybe_show_update_notice(): void {
        global $pagenow;
        if ( 'plugins.php' !== $pagenow ) {
            return;
        }
        if ( ! isset( $_GET['gf_wc_updates_checked'] ) ) {
            return;
        }
        $plugin_file = plugin_basename( dirname( __DIR__, 2 ) . '/benhughes-gf-wc.php' );
        $transient   = get_site_transient( 'update_plugins' );
        $message     = '';
        $class       = 'notice-info';
        if ( is_object( $transient ) && isset( $transient->response[ $plugin_file ] ) ) {
            $info        = $transient->response[ $plugin_file ];
            $new_version = isset( $info->new_version ) ? (string) $info->new_version : '';
            $message     = sprintf(
                /* translators: %s: version number */
                esc_html__( 'Update check complete. An update to version %s is available.', 'gf-wc-bridge' ),
                esc_html( $new_version )
            );
            $class = 'notice-success';
        } else {
            $message = esc_html__( 'Update check complete. No updates available right now.', 'gf-wc-bridge' );
        }
        echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /**
     * Add a tiny script to show a spinner/disabled state when clicking the plugin row link.
     */
    public function plugins_footer_script(): void {
        ?>
        <script>
        (function(){
            const link = document.getElementById('gf-wc-check-updates-plugin');
            if (!link) return;
            link.addEventListener('click', function(){
                if (link.dataset.loading === '1') return;
                link.dataset.loading = '1';
                link.textContent = '<?php echo esc_js( __( 'Checking…', 'gf-wc-bridge' ) ); ?>';
            });
        })();
        </script>
        <?php
    }
}
