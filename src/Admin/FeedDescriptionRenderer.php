<?php
/**
 * Feed Description Renderer
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Admin;

/**
 * Renders feed description HTML
 *
 * Separates view logic from business logic
 */
class FeedDescriptionRenderer {

	/**
	 * Render feed description box
	 *
	 * @return string HTML markup.
	 */
	public function render(): string {
		$heading = esc_html__( 'What happens when this feed is active:', 'gf-wc-bridge' );
		$point1  = esc_html__( 'All form field values (text, selections, etc.) are automatically captured', 'gf-wc-bridge' );
		$point2  = esc_html__( 'If a Price Calculator field exists, it will calculate the product price based on dimensions', 'gf-wc-bridge' );
		$point3  = esc_html__( 'The selected product is added to the WooCommerce cart on form submission', 'gf-wc-bridge' );
		$footer  = esc_html__( 'To enable/disable this feed, use the Active checkbox in the feed list.', 'gf-wc-bridge' );

		return $this->buildInfoBox( $heading, [ $point1, $point2, $point3 ], $footer );
	}

	/**
	 * Build info box HTML
	 *
	 * @param string        $heading Heading text.
	 * @param array<string> $points  List of bullet points.
	 * @param string        $footer  Footer text.
	 * @return string HTML markup.
	 */
	private function buildInfoBox( string $heading, array $points, string $footer ): string {
		$bulletPoints = $this->buildBulletList( $points );

		return <<<HTML
		<div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 12px 15px; margin: 10px 0 20px 0;">
			<h4 style="margin: 0 0 10px 0; color: #0073aa;">{$heading}</h4>
			{$bulletPoints}
			<p style="margin: 10px 0 0 0; font-style: italic; color: #666;">{$footer}</p>
		</div>
		HTML;
	}

	/**
	 * Build bullet list HTML
	 *
	 * @param array<string> $points List items.
	 * @return string HTML markup.
	 */
	private function buildBulletList( array $points ): string {
		$items = array_map(
			static fn( string $point ): string => "<li>{$point}</li>",
			$points
		);

		$itemsHtml = implode( "\n", $items );

		return <<<HTML
		<ul style="margin: 5px 0; padding-left: 20px;">
			{$itemsHtml}
		</ul>
		HTML;
	}
}
