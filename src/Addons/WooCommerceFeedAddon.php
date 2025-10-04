<?php
/**
 * WooCommerce Feed Addon
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Addons;

use BenHughes\GravityFormsWC\Admin\FeedDescriptionRenderer;
use BenHughes\GravityFormsWC\Enums\FeedField;
use BenHughes\GravityFormsWC\Enums\MeasurementUnit;
use BenHughes\GravityFormsWC\Logging\Logger;
use BenHughes\GravityFormsWC\Repositories\ProductRepositoryInterface;
use BenHughes\GravityFormsWC\Services\CartService;
use BenHughes\GravityFormsWC\ValueObjects\FeedSettings;
use BenHughes\GravityFormsWC\ValueObjects\ProductChoice;
use GFFeedAddOn;

/**
 * Feed-based addon for WooCommerce cart integration
 *
 * Allows flexible form-to-cart mapping without requiring Price Calculator field
 */
class WooCommerceFeedAddon extends GFFeedAddOn {

	/**
	 * Singleton instance
	 *
	 * @var WooCommerceFeedAddon|null
	 */
	private static ?WooCommerceFeedAddon $_instance = null;

	/**
	 * Addon version
	 *
	 * @var string
	 */
	protected $_version = '2.4.0';

	/**
	 * Minimum Gravity Forms version
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '2.5';

	/**
	 * Addon slug
	 *
	 * @var string
	 */
	protected $_slug = 'gravityforms-woocommerce-cart';

	/**
	 * Addon path
	 *
	 * @var string
	 */
	protected $_path = 'benhughes-gf-wc/benhughes-gf-wc.php';

	/**
	 * Addon full path
	 *
	 * @var string
	 */
	protected $_full_path = BENHUGHES_GF_WC_FILE;

	/**
	 * Addon title
	 *
	 * @var string
	 */
	protected $_title = 'Gravity Forms WooCommerce Cart';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'WooCommerce Cart';

	/**
	 * Allow multiple feeds
	 *
	 * @var bool
	 */
	protected $_multiple_feeds = true;

	/**
	 * Cart service
	 *
	 * @var CartService
	 */
	private CartService $cart_service;

	/**
	 * Product repository
	 *
	 * @var ProductRepositoryInterface
	 */
	private ProductRepositoryInterface $product_repository;

	/**
	 * Feed description renderer
	 *
	 * @var FeedDescriptionRenderer
	 */
	private FeedDescriptionRenderer $description_renderer;

	/**
	 * Logger
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor
	 *
	 * @param CartService                $cart_service         Cart service.
	 * @param ProductRepositoryInterface $product_repository   Product repository.
	 * @param FeedDescriptionRenderer    $description_renderer Description renderer.
	 * @param Logger                     $logger               Logger.
	 */
	public function __construct(
		CartService $cart_service,
		ProductRepositoryInterface $product_repository,
		FeedDescriptionRenderer $description_renderer,
		Logger $logger
	) {
		parent::__construct();
		$this->cart_service         = $cart_service;
		$this->product_repository   = $product_repository;
		$this->description_renderer = $description_renderer;
		$this->logger               = $logger;
		self::$_instance            = $this;
	}

	/**
	 * Get singleton instance
	 *
	 * @return WooCommerceFeedAddon Instance.
	 */
	public static function get_instance(): WooCommerceFeedAddon {
		if ( null === self::$_instance ) {
			// Retrieve from container if not yet instantiated
			self::$_instance = gf_wc_service( self::class );
		}

		return self::$_instance;
	}

	/**
	 * Initialize addon
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();

		// Log addon initialization for debugging
		$this->logger->info( 'WooCommerce Feed Addon initialized' );
	}

	/**
	 * Check if feed can be created
	 *
	 * @param int|null $form_id Form ID.
	 * @return bool True if feed can be created.
	 */
	public function can_create_feed( $form_id = null ): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Configure feed list columns
	 *
	 * @return array<string, string> Column configuration.
	 */
	public function feed_list_columns(): array {
		return [
			FeedField::FEED_NAME->value => esc_html__( 'Feed Name', 'gf-wc-bridge' ),
			'product'                   => esc_html__( 'Product', 'gf-wc-bridge' ),
			FeedField::QUANTITY->value  => esc_html__( 'Quantity', 'gf-wc-bridge' ),
		];
	}

	/**
	 * Get value for product column
	 *
	 * @param array<string, mixed> $feed Feed configuration.
	 * @return string Product display text.
	 */
	public function get_column_value_product( $feed ): string {
		$settings = FeedSettings::fromMeta( $feed['meta'] ?? [] );

		if ( ! $settings->hasProductId() ) {
			return '<em>' . esc_html__( 'From Price Calculator', 'gf-wc-bridge' ) . '</em>';
		}

		$product = $this->product_repository->find( $settings->productId );

		if ( ! $product ) {
			$errorText = esc_html__( 'Product not found', 'gf-wc-bridge' );
			return "<span style=\"color: #dc3232;\">{$errorText}</span>";
		}

		$productName = esc_html( $product->get_name() );
		$productId   = $product->get_id();

		return "{$productName} <span style=\"color: #999;\">(ID: {$productId})</span>";
	}

	/**
	 * Get value for quantity column
	 *
	 * @param array<string, mixed> $feed Feed configuration.
	 * @return string Quantity display text.
	 */
	public function get_column_value_quantity( $feed ): string {
		$settings = FeedSettings::fromMeta( $feed['meta'] ?? [] );
		return esc_html( (string) $settings->quantity );
	}

	/**
	 * Define feed settings fields
	 *
	 * @return array<int, array<string, mixed>> Feed settings configuration.
	 */
	public function feed_settings_fields(): array {
		return [
			[
				'title'       => esc_html__( 'WooCommerce Cart Feed Settings', 'gf-wc-bridge' ),
				'description' => $this->description_renderer->render(),
				'fields'      => [
					[
						'label'   => esc_html__( 'Feed Name', 'gf-wc-bridge' ),
						'type'    => 'text',
						'name'    => FeedField::FEED_NAME->value,
						'tooltip' => esc_html__( 'Enter a name to identify this feed', 'gf-wc-bridge' ),
						'class'   => 'medium',
					],
					[
						'label'    => esc_html__( 'WooCommerce Product', 'gf-wc-bridge' ),
						'type'     => 'select',
						'name'     => FeedField::PRODUCT_ID->value,
						'tooltip'  => esc_html__( 'Select the WooCommerce product to add to cart. If your form has a Price Calculator field with a product configured, that will be used automatically and this setting will be ignored.', 'gf-wc-bridge' ),
						'choices'  => $this->getProductChoices(),
						'required' => false,
					],
					[
						'label'         => esc_html__( 'Quantity', 'gf-wc-bridge' ),
						'type'          => 'text',
						'name'          => FeedField::QUANTITY->value,
						'tooltip'       => esc_html__( 'Product quantity (default: 1)', 'gf-wc-bridge' ),
						'class'         => 'small',
						'default_value' => '1',
					],
				],
			],
			[
				'title'  => esc_html__( 'Feed Conditions', 'gf-wc-bridge' ),
				'fields' => [
					[
						'name'           => FeedField::CONDITION->value,
						'label'          => esc_html__( 'Condition', 'gf-wc-bridge' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable Condition', 'gf-wc-bridge' ),
						'instructions'   => esc_html__( 'Add to cart only when these conditions are met', 'gf-wc-bridge' ),
					],
				],
			],
		];
	}

	/**
	 * Get product choices for dropdown
	 *
	 * @return array<int, array<string, string>> Product choices.
	 */
	private function getProductChoices(): array {
		$choices = [ ProductChoice::placeholder()->toArray() ];

		if ( ! class_exists( 'WooCommerce' ) ) {
			return $choices;
		}

		$products = $this->product_repository->getAll();

		foreach ( $products as $product ) {
			$choices[] = ProductChoice::fromProduct(
				productId: $product->get_id(),
				productName: $product->get_name()
			)->toArray();
		}

		return $choices;
	}

	/**
	 * Process feed - add to WooCommerce cart
	 *
	 * @param array<string, mixed> $feed  Feed configuration.
	 * @param array<string, mixed> $entry Form entry.
	 * @param array<string, mixed> $form  Form configuration.
	 * @return bool True if successful.
	 */
	public function process_feed( $feed, $entry, $form ): bool {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->add_feed_error( esc_html__( 'WooCommerce is not active', 'gf-wc-bridge' ), $feed, $entry, $form );
			return false;
		}

		// Parse feed settings
		$settings = FeedSettings::fromMeta( $feed['meta'] ?? [] );

		// Check if form has Price Calculator field (for area-based pricing)
		$price_calculator_field = $this->get_price_calculator_field( $form );

		// Determine product ID: Price Calculator takes precedence over feed setting
		$product_id = $this->determineProductId( $price_calculator_field, $settings );

		// Validate product ID was provided
		if ( ! $product_id ) {
			$this->add_feed_error(
				esc_html__( 'No product ID configured. Either add a Price Calculator field with a product, or select a product in the feed settings.', 'gf-wc-bridge' ),
				$feed,
				$entry,
				$form
			);
			return false;
		}

		// Validate product exists
		if ( ! $this->cart_service->productExists( $product_id ) ) {
			$this->add_feed_error(
				__( "Product ID {$product_id} not found", 'gf-wc-bridge' ),
				$feed,
				$entry,
				$form
			);
			return false;
		}

		$quantity = $settings->quantity;

		// Build cart data differently based on whether we have Price Calculator
		if ( $price_calculator_field ) {
			$cart_data = $this->build_calculated_cart_data( $price_calculator_field, $product_id, $entry, $form );
		} else {
			$cart_data = $this->build_cart_data( $form, $entry );
		}

		// Add entry ID for reference
		$cart_data['gf_entry_id'] = $entry['id'];
		$cart_data['gf_form_id']  = $entry['form_id'];

		// Add to cart
		$cart_item_key = WC()->cart->add_to_cart(
			$product_id,
			$quantity,
			0,
			[],
			$cart_data
		);

		if ( ! $cart_item_key ) {
			$this->add_feed_error( esc_html__( 'Failed to add product to cart', 'gf-wc-bridge' ), $feed, $entry, $form );
			return false;
		}

		// Log success
		$this->logger->info( 'Product added to cart via feed', [
			'product_id'      => $product_id,
			'quantity'        => $quantity,
			'cart_item_key'   => $cart_item_key,
			'feed_id'         => $feed['id'],
			'entry_id'        => $entry['id'],
			'has_calculator'  => (bool) $price_calculator_field,
		] );

		// Add note to entry
		$note_text = $price_calculator_field
			? __( "Product #{$product_id} added to cart with calculated pricing (Quantity: {$quantity})", 'gf-wc-bridge' )
			: __( "Product #{$product_id} added to cart (Quantity: {$quantity})", 'gf-wc-bridge' );

		$this->add_note( $entry['id'], $note_text, 'success' );

		return true;
	}

	/**
	 * Determine product ID from Price Calculator field or feed settings
	 *
	 * @param object|null  $price_calculator_field Price Calculator field if exists.
	 * @param FeedSettings $settings               Feed settings.
	 * @return int Product ID (0 if not found).
	 */
	private function determineProductId( ?object $price_calculator_field, FeedSettings $settings ): int {
		if ( $price_calculator_field && ! empty( $price_calculator_field->wcProductId ) ) {
			$productId = (int) $price_calculator_field->wcProductId;
			$this->logger->debug( 'Using product ID from Price Calculator field', [ 'product_id' => $productId ] );
			return $productId;
		}

		if ( $settings->hasProductId() ) {
			$this->logger->debug( 'Using product ID from feed settings', [ 'product_id' => $settings->productId ] );
			return $settings->productId;
		}

		return 0;
	}

	/**
	 * Build cart data by auto-grabbing all form field values
	 *
	 * @param array<string, mixed> $form  Form configuration.
	 * @param array<string, mixed> $entry Form entry.
	 * @return array<string, mixed> Cart data with all field values.
	 */
	private function build_cart_data( array $form, array $entry ): array {
		$cart_data = [];

		// Loop through all form fields
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $cart_data;
		}

		foreach ( $form['fields'] as $field ) {
			// Skip certain field types that shouldn't be in cart data
			$skip_types = [ 'page', 'section', 'html', 'captcha', 'wc_price_calculator' ];
			if ( isset( $field->type ) && in_array( $field->type, $skip_types, true ) ) {
				continue;
			}

			// Get field value from entry
			$field_id = $field->id ?? null;
			if ( ! $field_id ) {
				continue;
			}

			$value = rgar( $entry, (string) $field_id );

			// Skip empty values
			if ( empty( $value ) && '0' !== $value ) {
				continue;
			}

			// Use admin label if available, otherwise use label
			$label = ! empty( $field->adminLabel ) ? $field->adminLabel : ( $field->label ?? "field_{$field_id}" );

			// Sanitize label for use as array key (lowercase, replace spaces with underscores)
			$key = strtolower( preg_replace( '/[^a-z0-9]+/i', '_', $label ) );
			$key = trim( $key, '_' );

			// Add to cart data
			$cart_data[ $key ] = $value;
		}

		return $cart_data;
	}

	/**
	 * Find Price Calculator field in form
	 *
	 * @param array<string, mixed> $form Form configuration.
	 * @return object|null Price Calculator field or null.
	 */
	private function get_price_calculator_field( array $form ): ?object {
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return null;
		}

		foreach ( $form['fields'] as $field ) {
			if ( isset( $field->type ) && 'wc_price_calculator' === $field->type ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Build cart data with calculated pricing from Price Calculator field
	 *
	 * @param object               $price_calculator_field Price Calculator field.
	 * @param int                  $product_id             WooCommerce product ID.
	 * @param array<string, mixed> $entry                  Form entry.
	 * @param array<string, mixed> $form                   Form configuration.
	 * @return array<string, mixed> Cart data with calculation.
	 */
	private function build_calculated_cart_data( object $price_calculator_field, int $product_id, array $entry, array $form ): array {
		// Get field IDs from Price Calculator configuration
		$width_field_id = $price_calculator_field->widthFieldId ?? 30;
		$drop_field_id  = $price_calculator_field->dropFieldId ?? 23;
		$unit_field_id  = $price_calculator_field->unitFieldId ?? 0;

		// Get measurement values from entry
		$width_raw = floatval( rgar( $entry, $width_field_id ) );
		$drop_raw  = floatval( rgar( $entry, $drop_field_id ) );
		$unit      = $unit_field_id > 0 ? rgar( $entry, $unit_field_id ) : 'cm';

		// Parse unit enum
		$unit_enum = MeasurementUnit::tryFrom( $unit ) ?? MeasurementUnit::default();

		// Use CartService to prepare cart item data with calculation
		$cart_item_data = $this->cart_service->prepareCartItemData(
			$product_id,
			$width_raw,
			$drop_raw,
			$unit_enum
		);

		// Add all form field values automatically
		$custom_fields = $this->build_cart_data( $form, $entry );

		// Merge calculated data with custom fields
		$cart_data = array_merge( $cart_item_data, $custom_fields );

		$this->logger->debug( 'Built calculated cart data', [
			'width'      => $width_raw,
			'drop'       => $drop_raw,
			'unit'       => $unit,
			'product_id' => $product_id,
			'price'      => $cart_data['gf_wc_calculation']->price ?? 'N/A',
		] );

		return $cart_data;
	}
}
