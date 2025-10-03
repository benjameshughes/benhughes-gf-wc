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

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php $this->render_system_status(); ?>

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

		?>
		<div class="gf-wc-system-status">
			<h3><?php esc_html_e( 'System Requirements', 'gf-wc-bridge' ); ?></h3>
			<table class="widefat striped" style="margin: 0;">
				<tbody>
					<tr>
						<td style="width: 200px;"><strong><?php esc_html_e( 'Gravity Forms', 'gf-wc-bridge' ); ?></strong></td>
						<td>
							<?php if ( $gf_active ) : ?>
								<span style="color: #00a32a;">✓ <?php esc_html_e( 'Active', 'gf-wc-bridge' ); ?></span>
								<?php if ( defined( 'GF_VERSION' ) ) : ?>
									<span style="color: #666;"> (v<?php echo esc_html( GF_VERSION ); ?>)</span>
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
}
