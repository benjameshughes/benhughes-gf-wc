<?php
/**
 * Settings Page
 *
 * Admin dashboard for managing plugin configuration
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.1.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

use BenHughes\GravityFormsWC\Validation\ConfigValidator;

/**
 * Manages the plugin settings page
 */
class SettingsPage {

	/**
	 * Config validator instance
	 *
	 * @var ConfigValidator
	 */
	private ConfigValidator $validator;

	/**
	 * Option name for debug mode
	 */
    private const DEBUG_OPTION = 'gf_wc_debug_mode';
    private const REST_ADD_OPTION = 'gf_wc_rest_add_to_basket';
    private const ALPINE_SRC_OPTION = 'gf_wc_alpine_source';
    private const AUTO_UPDATE_OPTION = 'gf_wc_auto_update';

	/**
	 * Constructor
	 *
	 * @param ConfigValidator $validator Config validator.
	 */
	public function __construct( ConfigValidator $validator ) {
		$this->validator = $validator;

		// Use priority 20 to ensure Gravity Forms menu is registered first (they use default 10)
		add_action( 'admin_menu', [ $this, 'add_menu_page' ], 20 );
		add_action( 'admin_post_gf_wc_save_settings', [ $this, 'save_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Enqueue admin styles
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_styles( string $hook_suffix ): void {
		// Only load on our settings page
		if ( 'forms_page_gf-wc-settings' !== $hook_suffix ) {
			return;
		}

		// Add inline styles for our admin page
		wp_add_inline_style(
			'common',
			'
			.gf-wc-system-status {
				background: #fff;
				border: 1px solid #c3c4c7;
				padding: 15px;
				margin: 20px 0;
				border-radius: 4px;
			}
			.gf-wc-system-status h3 {
				margin-top: 0;
			}
			.gf-wc-getting-started {
				background: #f0f6fc;
				border: 1px solid #c3c4c7;
				padding: 20px;
				margin: 30px 0;
				border-radius: 4px;
			}
			.gf-wc-getting-started h2 {
				margin-top: 0;
			}
			.gf-wc-getting-started ol {
				line-height: 2;
			}
			'
		);
	}

	/**
	 * Add settings page to Forms menu
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		if ( ! $this->validator->is_gravity_forms_active() ) {
			return;
		}

		$hook_suffix = add_submenu_page(
			'gf_edit_forms',
			__( 'Form to Cart Settings', 'gf-wc-bridge' ),
			__( 'Cart Integration', 'gf-wc-bridge' ),
			'manage_options',
			'gf-wc-settings',
			[ $this, 'render_page' ]
		);

		// Store hook suffix for later use
		if ( $hook_suffix ) {
			add_action( "load-{$hook_suffix}", [ $this, 'on_page_load' ] );
		}
	}

	/**
	 * Fires when the settings page is loaded
	 *
	 * @return void
	 */
	public function on_page_load(): void {
		// Add help tabs or perform page-specific setup if needed
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
    public function render_page(): void {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'gf-wc-bridge' ) );
        }

        $configured_forms = $this->validator->get_configured_forms();
        $debug_mode       = $this->is_debug_mode();
        $rest_enabled     = '1' === get_option( self::REST_ADD_OPTION, '0' );
        $alpine_source    = get_option( self::ALPINE_SRC_OPTION, 'cdn' );
        $auto_update      = '1' === get_option( self::AUTO_UPDATE_OPTION, '0' );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php
            // Inline admin notices for actions
            if ( isset( $_GET['updated'] ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'gf-wc-bridge' ) . '</p></div>';
            }

            if ( isset( $_GET['cleared'] ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Confirmation messages cleared.', 'gf-wc-bridge' ) . '</p></div>';
            }

            if ( isset( $_GET['updates_checked'] ) ) {
                // Try to detect whether an update is available right now
                $plugin_file = plugin_basename( dirname( dirname( __DIR__ ) ) . '/benhughes-gf-wc.php' );
                $transient   = get_site_transient( 'update_plugins' );
                $update_html = '';
                if ( is_object( $transient ) && isset( $transient->response ) && is_array( $transient->response ) && isset( $transient->response[ $plugin_file ] ) ) {
                    $info        = $transient->response[ $plugin_file ];
                    $new_version = isset( $info->new_version ) ? (string) $info->new_version : '';
                    $update_html = sprintf(
                        /* translators: %s: version number */
                        esc_html__( 'Update check complete. An update is available to version %s. Visit the Plugins page to install.', 'gf-wc-bridge' ),
                        esc_html( $new_version )
                    );
                } else {
                    $update_html = esc_html__( 'Update check complete. No updates available right now.', 'gf-wc-bridge' );
                }
                $plugins_url = admin_url( 'plugins.php' );
                echo '<div class="notice notice-info is-dismissible"><p>' . wp_kses_post( $update_html ) . ' <a href="' . esc_url( $plugins_url ) . '">' . esc_html__( 'Open Plugins', 'gf-wc-bridge' ) . '</a></p></div>';
            }
            ?>

            <?php $this->render_system_status(); ?>

            <?php $this->render_tools_section( $configured_forms ); ?>

            <?php $this->render_advanced_settings( $rest_enabled, $alpine_source, $auto_update ); ?>

			<h2><?php esc_html_e( 'Debug Settings', 'gf-wc-bridge' ); ?></h2>
			<?php $this->render_debug_settings( $debug_mode ); ?>

			<h2><?php esc_html_e( 'Configured Forms', 'gf-wc-bridge' ); ?></h2>
			<?php $this->render_forms_table( $configured_forms ); ?>

			<?php $this->render_getting_started(); ?>
		</div>
		<?php
	}

	/**
	 * Render system status section
	 *
	 * @return void
	 */
    private function render_system_status(): void {
        $gf_active = $this->validator->is_gravity_forms_active();
        $wc_active = $this->validator->is_woocommerce_active();
        // Resolve plugin version from header (fallback to constant)
        $plugin_ver = 'unknown';
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_file = dirname( dirname( __DIR__ ) ) . '/benhughes-gf-wc.php';
        if ( file_exists( $plugin_file ) ) {
            $data = get_plugin_data( $plugin_file, false, false );
            if ( ! empty( $data['Version'] ) ) {
                $plugin_ver = $data['Version'];
            }
        }
        if ( 'unknown' === $plugin_ver && defined( 'BENHUGHES_GF_WC_VERSION' ) ) {
            $plugin_ver = BENHUGHES_GF_WC_VERSION;
        }

        ?>
        <div class="gf-wc-system-status">
            <h3><?php esc_html_e( 'System Requirements', 'gf-wc-bridge' ); ?></h3>
            <table class="widefat striped" style="margin: 0;">
                <tbody>
                    <tr>
                        <td style="width: 200px;"><strong><?php esc_html_e( 'Plugin Version', 'gf-wc-bridge' ); ?></strong></td>
                        <td><span><?php echo esc_html( $plugin_ver ); ?></span></td>
                    </tr>
                    <tr>
                        <td style="width: 200px;"><strong><?php esc_html_e( 'Gravity Forms', 'gf-wc-bridge' ); ?></strong></td>
                        <td>
                            <?php if ( $gf_active ) : ?>
                                <span style="color: #00a32a;">✓ <?php esc_html_e( 'Active', 'gf-wc-bridge' ); ?></span>
                                <?php
                                $gf_version = '';
                                if ( class_exists( '\\GFForms' ) && method_exists( '\\GFForms', 'get_version' ) ) {
                                    $gf_version = (string) \GFForms::get_version();
                                } elseif ( defined( 'GF_VERSION' ) ) {
                                    $gf_version = (string) GF_VERSION;
                                } else {
                                    // Fallback: read plugin header
                                    if ( ! function_exists( 'get_plugins' ) ) {
                                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                                    }
                                    $plugins = get_plugins();
                                    if ( isset( $plugins['gravityforms/gravityforms.php']['Version'] ) ) {
                                        $gf_version = (string) $plugins['gravityforms/gravityforms.php']['Version'];
                                    }
                                }
                                if ( $gf_version ) : ?>
                                    <span style="color: #666;"> (v<?php echo esc_html( $gf_version ); ?>)</span>
                                <?php endif; ?>
                            <?php else : ?>
                                <span style="color: #d63638;">✗ <?php esc_html_e( 'Not Installed', 'gf-wc-bridge' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce', 'gf-wc-bridge' ); ?></strong></td>
						<td>
							<?php if ( $wc_active ) : ?>
								<span style="color: #00a32a;">✓ <?php esc_html_e( 'Active', 'gf-wc-bridge' ); ?></span>
								<?php if ( defined( 'WC_VERSION' ) ) : ?>
									<span style="color: #666;"> (v<?php echo esc_html( WC_VERSION ); ?>)</span>
								<?php endif; ?>
							<?php else : ?>
								<span style="color: #d63638;">✗ <?php esc_html_e( 'Not Installed', 'gf-wc-bridge' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version', 'gf-wc-bridge' ); ?></strong></td>
						<td>
							<?php
							$php_version = phpversion();
							$php_ok      = version_compare( $php_version, '8.2', '>=' );
							?>
							<?php if ( $php_ok ) : ?>
								<span style="color: #00a32a;">✓ <?php echo esc_html( $php_version ); ?></span>
							<?php else : ?>
								<span style="color: #d63638;">⚠ <?php echo esc_html( $php_version ); ?></span>
								<span style="color: #666;"> (<?php esc_html_e( 'PHP 8.2+ recommended', 'gf-wc-bridge' ); ?>)</span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
        <?php
    }

    /**
     * Render tools section (diagnostics, clear confirmations)
     */
    private function render_tools_section( array $configured_forms ): void {
        $gf_version = defined( 'GF_VERSION' ) ? GF_VERSION : ( class_exists( 'GFForms' ) && method_exists( 'GFForms', 'get_version' ) ? \GFForms::get_version() : 'N/A' );
        $wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : ( class_exists( 'WooCommerce' ) ? \WC()->version : 'N/A' );
        $plugin_ver = defined( 'BENHUGHES_GF_WC_VERSION' ) ? BENHUGHES_GF_WC_VERSION : 'N/A';
        $diag = [
            'Plugin'          => $plugin_ver,
            'WordPress'       => get_bloginfo( 'version' ),
            'PHP'             => phpversion(),
            'Gravity Forms'   => $gf_version,
            'WooCommerce'     => $wc_version,
            'ConfiguredForms' => is_array( $configured_forms ) ? (string) count( $configured_forms ) : '0',
        ];
        $diag_json = wp_json_encode( $diag, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        ?>
        <div class="gf-wc-system-status">
            <h3><?php esc_html_e( 'Tools', 'gf-wc-bridge' ); ?></h3>
            <p>
                <a class="button" href="<?php echo esc_url( admin_url( 'site-health.php?tab=debug' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View in Site Health → Info', 'gf-wc-bridge' ); ?></a>
                <button type="button" class="button" id="gf-wc-copy-diag"><?php esc_html_e( 'Copy diagnostics', 'gf-wc-bridge' ); ?></button>
                <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gf_wc_clear_confirmations' ), 'gf_wc_clear_confirmations' ) ); ?>"><?php esc_html_e( 'Clear confirmation messages', 'gf-wc-bridge' ); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gf_wc_check_updates' ), 'gf_wc_check_updates' ) ); ?>"><?php esc_html_e( 'Check for updates now', 'gf-wc-bridge' ); ?></a>
            </p>
            <textarea id="gf-wc-diag-src" style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true"><?php echo esc_textarea( (string) $diag_json ); ?></textarea>
            <script>
                (function(){
                    const btn = document.getElementById('gf-wc-copy-diag');
                    if (!btn) return;
                    btn.addEventListener('click', function(){
                        const ta = document.getElementById('gf-wc-diag-src');
                        if (!ta) return;
                        ta.select();
                        ta.setSelectionRange(0, ta.value.length);
                        try { document.execCommand('copy'); btn.textContent = '<?php echo esc_js( __( 'Copied!', 'gf-wc-bridge' ) ); ?>'; } catch(e) {}
                        setTimeout(function(){ btn.textContent = '<?php echo esc_js( __( 'Copy diagnostics', 'gf-wc-bridge' ) ); ?>'; }, 2000);
                    });
                })();
            </script>
        </div>
        <?php
    }

    /**
     * Render advanced settings (REST toggle, Alpine source)
     */
    private function render_advanced_settings( bool $rest_enabled, string $alpine_source, bool $auto_update ): void {
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gf-wc-system-status">
            <?php wp_nonce_field( 'gf_wc_settings', 'gf_wc_settings_nonce' ); ?>
            <input type="hidden" name="action" value="gf_wc_save_settings" />

            <h3><?php esc_html_e( 'Advanced', 'gf-wc-bridge' ); ?></h3>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="rest_add"><?php esc_html_e( 'Enable REST add-to-basket', 'gf-wc-bridge' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="rest_add" id="rest_add" value="1" <?php checked( $rest_enabled ); ?> />
                                <?php esc_html_e( 'Allow the /gf-wc/v1/add-to-basket endpoint (AJAX recommended)', 'gf-wc-bridge' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="auto_update"><?php esc_html_e( 'Auto-update this plugin', 'gf-wc-bridge' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_update" id="auto_update" value="1" <?php checked( $auto_update ); ?> />
                                <?php esc_html_e( 'Install updates for this plugin automatically when available', 'gf-wc-bridge' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="alpine_source"><?php esc_html_e( 'Alpine.js Source', 'gf-wc-bridge' ); ?></label></th>
                        <td>
                            <select name="alpine_source" id="alpine_source">
                                <option value="cdn" <?php selected( $alpine_source, 'cdn' ); ?>><?php esc_html_e( 'CDN (default)', 'gf-wc-bridge' ); ?></option>
                                <option value="local" <?php selected( $alpine_source, 'local' ); ?>><?php esc_html_e( 'Local (if bundled)', 'gf-wc-bridge' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( 'Save Advanced Settings', 'gf-wc-bridge' ) ); ?>
        </form>
        <?php
    }

	/**
	 * Render debug settings form
	 *
	 * @param bool $debug_mode Current debug mode state.
	 * @return void
	 */
	private function render_debug_settings( bool $debug_mode ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gf-wc-system-status">
			<?php wp_nonce_field( 'gf_wc_settings', 'gf_wc_settings_nonce' ); ?>
			<input type="hidden" name="action" value="gf_wc_save_settings" />

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="debug_mode"><?php esc_html_e( 'Debug Mode', 'gf-wc-bridge' ); ?></label>
						</th>
						<td>
							<label>
								<input
									type="checkbox"
									name="debug_mode"
									id="debug_mode"
									value="1"
									<?php checked( $debug_mode ); ?>
								/>
								<?php esc_html_e( 'Enable verbose logging for troubleshooting', 'gf-wc-bridge' ); ?>
							</label>
							<p class="description">
								<?php
								esc_html_e(
									'When enabled, detailed debug information will be logged to browser console and PHP error logs. Disable in production.',
									'gf-wc-bridge'
								);
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Save Settings', 'gf-wc-bridge' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Render forms configuration table
	 *
	 * @param array $configured_forms Configured forms from validator.
	 * @return void
	 */
	private function render_forms_table( array $configured_forms ): void {
		if ( empty( $configured_forms ) ) {
			?>
			<div class="notice notice-info inline">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: link to create new form */
							__( 'No forms with Price Calculator fields found. <a href="%s">Create a form</a> and add a Price Calculator field to get started.', 'gf-wc-bridge' ),
							admin_url( 'admin.php?page=gf_new_form' )
						)
					);
					?>
				</p>
			</div>
			<?php
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" style="width: 60px;"><?php esc_html_e( 'Status', 'gf-wc-bridge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Form', 'gf-wc-bridge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Product', 'gf-wc-bridge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Configuration', 'gf-wc-bridge' ); ?></th>
					<th scope="col" style="width: 150px;"><?php esc_html_e( 'Actions', 'gf-wc-bridge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $configured_forms as $item ) : ?>
					<?php
					$form       = $item['form'];
					$config     = $item['config'];
					$validation = $item['validation'];
					$is_valid   = $validation['valid'];
					?>
					<tr>
						<td class="gf-wc-status">
							<?php if ( $is_valid ) : ?>
								<span style="font-size: 24px; color: #00a32a;" title="<?php esc_attr_e( 'Configuration Valid', 'gf-wc-bridge' ); ?>">✓</span>
							<?php else : ?>
								<span style="font-size: 24px; color: #d63638;" title="<?php esc_attr_e( 'Configuration Issues', 'gf-wc-bridge' ); ?>">⚠</span>
							<?php endif; ?>
						</td>
						<td>
							<strong><?php echo esc_html( $config['formTitle'] ); ?></strong>
							<div style="color: #666; font-size: 12px;">
								<?php
								printf(
									/* translators: %d: form ID */
									esc_html__( 'Form ID: %d', 'gf-wc-bridge' ),
									(int) $config['formId']
								);
								?>
							</div>
						</td>
						<td>
							<?php if ( $config['productId'] ) : ?>
								<?php
								$product_name = $this->validator->get_product_name( $config['productId'] );
								if ( $product_name ) :
									?>
									<strong><?php echo esc_html( $product_name ); ?></strong>
									<div style="color: #666; font-size: 12px;">
										<?php
										printf(
											/* translators: %d: product ID */
											esc_html__( 'Product ID: %d', 'gf-wc-bridge' ),
											(int) $config['productId']
										);
										?>
									</div>
								<?php else : ?>
									<span style="color: #d63638;">
										<?php
										printf(
											/* translators: %d: product ID */
											esc_html__( 'Product %d not found', 'gf-wc-bridge' ),
											(int) $config['productId']
										);
										?>
									</span>
								<?php endif; ?>
							<?php else : ?>
								<span style="color: #666;"><?php esc_html_e( 'Not configured', 'gf-wc-bridge' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $is_valid ) : ?>
								<span style="color: #00a32a;"><?php esc_html_e( 'All settings valid', 'gf-wc-bridge' ); ?></span>
							<?php else : ?>
								<ul style="margin: 0; padding-left: 20px; color: #d63638;">
									<?php foreach ( $validation['errors'] as $error ) : ?>
										<li><?php echo esc_html( $error['message'] ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=gf_edit_forms&id=' . $config['formId'] ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit Form', 'gf-wc-bridge' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render getting started section
	 *
	 * @return void
	 */
	private function render_getting_started(): void {
		?>
		<div class="gf-wc-getting-started">
			<h2><?php esc_html_e( '🚀 Quick Start Guide', 'gf-wc-bridge' ); ?></h2>
			<p><?php esc_html_e( 'Connect your forms to WooCommerce in 4 simple steps:', 'gf-wc-bridge' ); ?></p>
			<ol>
				<li>
					<strong><?php esc_html_e( 'Create or edit a form', 'gf-wc-bridge' ); ?></strong>
					<span style="color: #666;"> — <?php esc_html_e( 'Add number fields for measurements (width, height, etc.)', 'gf-wc-bridge' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Add a Price Calculator field', 'gf-wc-bridge' ); ?></strong>
					<span style="color: #666;"> — <?php esc_html_e( 'Located in the "Advanced Fields" section of the form editor', 'gf-wc-bridge' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Link your fields', 'gf-wc-bridge' ); ?></strong>
					<span style="color: #666;"> — <?php esc_html_e( 'Select which fields calculate the price and choose the WooCommerce product to sell', 'gf-wc-bridge' ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Test it out', 'gf-wc-bridge' ); ?></strong>
					<span style="color: #666;"> — <?php esc_html_e( 'Fill in your form and try "Add to Basket" or "Pay Now"', 'gf-wc-bridge' ); ?></span>
				</li>
			</ol>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create New Form', 'gf-wc-bridge' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=gf_edit_forms' ) ); ?>" class="button">
					<?php esc_html_e( 'View All Forms', 'gf-wc-bridge' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Save settings
	 *
	 * @return void
	 */
    public function save_settings(): void {
		// Verify nonce
		if ( ! isset( $_POST['gf_wc_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gf_wc_settings_nonce'] ) ), 'gf_wc_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'gf-wc-bridge' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action', 'gf-wc-bridge' ) );
		}

        // Save debug mode
        $debug_mode = isset( $_POST['debug_mode'] ) ? '1' : '0';
        update_option( self::DEBUG_OPTION, $debug_mode );

        // Save REST add-to-basket toggle
        $rest_add = isset( $_POST['rest_add'] ) ? '1' : '0';
        update_option( self::REST_ADD_OPTION, $rest_add );

        // Save Alpine source
        $alpine_src = isset( $_POST['alpine_source'] ) && in_array( $_POST['alpine_source'], [ 'cdn', 'local' ], true )
            ? sanitize_text_field( wp_unslash( $_POST['alpine_source'] ) )
            : 'cdn';
        update_option( self::ALPINE_SRC_OPTION, $alpine_src );

        // Save auto-update preference
        $auto_update = isset( $_POST['auto_update'] ) ? '1' : '0';
        update_option( self::AUTO_UPDATE_OPTION, $auto_update );

        // No GitHub repo/token settings required for public updates

		// Redirect back with success message
		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'gf-wc-settings',
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Check if debug mode is enabled
	 *
	 * @return bool
	 */
    public static function is_debug_mode(): bool {
        return '1' === get_option( self::DEBUG_OPTION, '0' );
    }

    /**
     * Handle clear confirmations action
     */
    public static function handle_clear_confirmations(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'gf-wc-bridge' ) );
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gf_wc_clear_confirmations' ) ) {
            wp_die( esc_html__( 'Security check failed', 'gf-wc-bridge' ) );
        }

        global $wpdb;
        $like_key     = $wpdb->esc_like( '_transient_gf_wc_cart_confirmation_' ) . '%';
        $like_timeout = $wpdb->esc_like( '_transient_timeout_gf_wc_cart_confirmation_' ) . '%';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$like_key}'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$like_timeout}'" );
        // phpcs:enable

        wp_safe_redirect( add_query_arg( [ 'page' => 'gf-wc-settings', 'cleared' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Force a plugin update check
     */
    public static function handle_check_updates(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'gf-wc-bridge' ) );
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gf_wc_check_updates' ) ) {
            wp_die( esc_html__( 'Security check failed', 'gf-wc-bridge' ) );
        }
        // Clear cached plugin update data and force refresh
        delete_site_transient( 'update_plugins' );
        // Fire a background update check
        if ( function_exists( 'wp_update_plugins' ) ) {
            wp_update_plugins();
        }
        wp_safe_redirect( add_query_arg( [ 'page' => 'gf-wc-settings', 'updates_checked' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
