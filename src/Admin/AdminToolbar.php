<?php
/**
 * Admin Toolbar Status
 *
 * Adds configuration status to WordPress admin toolbar
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.1.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

use BenHughes\GravityFormsWC\Validation\ConfigValidator;

/**
 * Displays configuration status in admin toolbar
 */
class AdminToolbar {

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

		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items' ], 100 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Add items to admin toolbar
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public function add_toolbar_items( \WP_Admin_Bar $wp_admin_bar ): void {
		// Only show to admins
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show if dependencies are met
		if ( ! $this->validator->is_gravity_forms_active() || ! $this->validator->is_woocommerce_active() ) {
			return;
		}

		// Get configuration status
		$configured_forms  = $this->validator->get_configured_forms();
		$forms_with_errors = $this->validator->get_forms_with_errors();

		if ( empty( $configured_forms ) ) {
			return; // No forms configured, don't show toolbar item
		}

		$total_forms = count( $configured_forms );
		$error_count = count( $forms_with_errors );
		$valid_count = $total_forms - $error_count;

		// Determine status
		if ( $error_count > 0 ) {
			$status = 'warning';
			$icon   = '⚠';
			$title  = sprintf(
				/* translators: 1: number of forms with issues, 2: total forms */
				__( 'Cart Integration: %1$d of %2$d forms need attention', 'gf-wc-bridge' ),
				$error_count,
				$total_forms
			);
		} else {
			$status = 'success';
			$icon   = '✓';
			$title  = sprintf(
				/* translators: %d: number of forms */
				_n(
					'Cart Integration: %d form ready',
					'Cart Integration: %d forms ready',
					$total_forms,
					'gf-wc-bridge'
				),
				$total_forms
			);
		}

		// Add parent menu item
		$wp_admin_bar->add_node(
			[
				'id'    => 'gf-wc-status',
				'title' => sprintf(
					'<span class="gf-wc-toolbar-icon gf-wc-toolbar-%s">%s</span> %s',
					esc_attr( $status ),
					esc_html( $icon ),
					esc_html( $title )
				),
				'href'  => admin_url( 'admin.php?page=gf-wc-settings' ),
				'meta'  => [
					'title' => __( 'View settings dashboard', 'gf-wc-bridge' ),
				],
			]
		);

		// Add submenu items for forms with issues
		if ( ! empty( $forms_with_errors ) ) {
			foreach ( $forms_with_errors as $item ) {
				$form_title = $item['config']['formTitle'];
				$form_id    = $item['config']['formId'];

				$wp_admin_bar->add_node(
					[
						'parent' => 'gf-wc-status',
						'id'     => 'gf-wc-form-' . $form_id,
						'title'  => sprintf(
							'⚠ %s',
							esc_html( $form_title )
						),
						'href'   => admin_url( 'admin.php?page=gf_edit_forms&id=' . $form_id ),
						'meta'   => [
							'title' => __( 'Edit this form', 'gf-wc-bridge' ),
						],
					]
				);
			}

			// Add link to settings page
			$wp_admin_bar->add_node(
				[
					'parent' => 'gf-wc-status',
					'id'     => 'gf-wc-view-all',
					'title'  => __( 'View all forms', 'gf-wc-bridge' ),
					'href'   => admin_url( 'admin.php?page=gf-wc-settings' ),
				]
			);
		}
	}

	/**
	 * Enqueue toolbar styles
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Inline styles for toolbar icons
		$css = '
			.gf-wc-toolbar-icon {
				display: inline-block;
				margin-right: 5px;
			}
			.gf-wc-toolbar-success .gf-wc-toolbar-icon {
				color: #00a32a;
			}
			.gf-wc-toolbar-warning .gf-wc-toolbar-icon {
				color: #dba617;
			}
			#wpadminbar #wp-admin-bar-gf-wc-status .ab-item {
				font-weight: 500;
			}
		';

		// Check if admin-bar style is registered before adding inline styles
		if ( wp_style_is( 'admin-bar', 'registered' ) ) {
			wp_add_inline_style( 'admin-bar', $css );
		} else {
			// Fallback: add styles to head
			add_action(
				'admin_head',
				function () use ( $css ) {
					echo '<style>' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			);
			add_action(
				'wp_head',
				function () use ( $css ) {
					echo '<style>' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			);
		}
	}
}
