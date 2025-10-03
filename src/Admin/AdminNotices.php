<?php
/**
 * Admin Notices
 *
 * Displays helpful notices about plugin configuration and dependencies
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.1.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

use BenHughes\GravityFormsWC\Validation\ConfigValidator;

/**
 * Manages admin notices for plugin health and configuration
 */
class AdminNotices {

	/**
	 * Config validator instance
	 *
	 * @var ConfigValidator
	 */
	private ConfigValidator $validator;

	/**
	 * Constructor
	 *
	 * @param ConfigValidator $validator Config validator.
	 */
	public function __construct( ConfigValidator $validator ) {
		$this->validator = $validator;

		add_action( 'admin_notices', [ $this, 'show_dependency_notices' ] );
		add_action( 'admin_notices', [ $this, 'show_configuration_notices' ] );
	}

	/**
	 * Show notices for missing dependencies
	 *
	 * @return void
	 */
	public function show_dependency_notices(): void {
		// Only show to admins
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check Gravity Forms
		if ( ! $this->validator->is_gravity_forms_active() ) {
			$this->render_notice(
				__( '<strong>Cart Integration:</strong> Gravity Forms is required but not installed.', 'gf-wc-bridge' ),
				'error',
				[
					[
						'text' => __( 'Get Gravity Forms', 'gf-wc-bridge' ),
						'url'  => 'https://www.gravityforms.com/',
					],
				]
			);
			return; // Don't check further if GF is missing
		}

		// Check WooCommerce
		if ( ! $this->validator->is_woocommerce_active() ) {
			$this->render_notice(
				__( '<strong>Cart Integration:</strong> WooCommerce is required but not installed.', 'gf-wc-bridge' ),
				'error',
				[
					[
						'text' => __( 'Install WooCommerce', 'gf-wc-bridge' ),
						'url'  => admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ),
					],
				]
			);
		}
	}

	/**
	 * Show notices for configuration issues
	 *
	 * @return void
	 */
	public function show_configuration_notices(): void {
		// Only show to admins and only if dependencies are met
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->validator->is_gravity_forms_active() || ! $this->validator->is_woocommerce_active() ) {
			return;
		}

		// Get forms with errors
		$forms_with_errors = $this->validator->get_forms_with_errors();

		if ( empty( $forms_with_errors ) ) {
			return;
		}

		// Build message
		$count   = count( $forms_with_errors );
		$message = sprintf(
			/* translators: %d: number of forms with issues */
			_n(
				'%d form has Price Calculator configuration issues that need attention.',
				'%d forms have Price Calculator configuration issues that need attention.',
				$count,
				'gf-wc-bridge'
			),
			$count
		);

		// Add specific form details
		$details = '<ul style="margin: 0.5em 0 0 1.5em;">';
		foreach ( $forms_with_errors as $item ) {
			$form_title = $item['config']['formTitle'];
			$errors     = $item['validation']['errors'];
			$form_id    = $item['config']['formId'];

			$error_messages = array_map(
				function ( $error ) {
					return esc_html( $error['message'] );
				},
				$errors
			);

			$details .= sprintf(
				'<li><strong>%s:</strong> %s</li>',
				esc_html( $form_title ),
				implode( ', ', $error_messages )
			);
		}
		$details .= '</ul>';

		$this->render_notice(
			$message . $details,
			'warning',
			[
				[
					'text' => __( 'View Configuration Dashboard', 'gf-wc-bridge' ),
					'url'  => admin_url( 'admin.php?page=gf-wc-settings' ),
				],
			]
		);
	}

	/**
	 * Render an admin notice
	 *
	 * @param string $message Notice message. Can contain safe HTML (links, emphasis).
	 *                        Calling methods must use wp_kses_post() for HTML content.
	 * @param string $type    Notice type: 'error', 'warning', 'success', 'info'.
	 * @param array  $actions Optional action buttons with 'text' and 'url' keys.
	 * @return void
	 */
	private function render_notice( string $message, string $type = 'info', array $actions = [] ): void {
		$allowed_types = [ 'error', 'warning', 'success', 'info' ];
		$type          = in_array( $type, $allowed_types, true ) ? $type : 'info';

		$class = sprintf( 'notice notice-%s', $type );

		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<p>
				<?php
				// Message contains safe HTML from calling methods (already escaped with wp_kses_post)
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $message;
				?>
			</p>
			<?php if ( ! empty( $actions ) ) : ?>
				<p>
					<?php foreach ( $actions as $action ) : ?>
						<a href="<?php echo esc_url( $action['url'] ); ?>" class="button button-primary">
							<?php echo esc_html( $action['text'] ); ?>
						</a>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Show success notice (helper for other components)
	 *
	 * @param string $message Success message.
	 * @return void
	 */
	public static function show_success( string $message ): void {
		add_action(
			'admin_notices',
			function () use ( $message ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Show error notice (helper for other components)
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	public static function show_error( string $message ): void {
		add_action(
			'admin_notices',
			function () use ( $message ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
				<?php
			}
		);
	}
}
