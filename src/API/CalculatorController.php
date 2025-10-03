<?php
/**
 * Calculator REST API Controller
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\API;

use BenHughes\GravityFormsWC\Enums\MeasurementUnit;
use BenHughes\GravityFormsWC\Services\CartService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;

/**
 * REST API controller for price calculations and cart operations
 */
class CalculatorController extends WP_REST_Controller {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	protected $namespace = 'gf-wc/v1';

	/**
	 * Cart service
	 *
	 * @var CartService
	 */
	private CartService $cart_service;

	/**
	 * Constructor
	 *
	 * @param CartService $cart_service Cart service.
	 */
	public function __construct( CartService $cart_service ) {
		$this->cart_service = $cart_service;
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Calculate price endpoint
		register_rest_route(
			$this->namespace,
			'/calculate-price',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'calculate_price' ],
				'permission_callback' => '__return_true', // Public endpoint
				'args'                => [
					'width'      => [
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					],
					'drop'       => [
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					],
					'unit'       => [
						'required' => false,
						'type'     => 'string',
						'default'  => 'cm',
						'enum'     => [ 'mm', 'cm', 'in' ],
					],
					'product_id' => [
						'required'          => true,
						'type'              => 'integer',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					],
				],
			]
		);

		// Add to basket endpoint
		register_rest_route(
			$this->namespace,
			'/add-to-basket',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'add_to_basket' ],
				'permission_callback' => '__return_true', // Public endpoint
				'args'                => [
					'product_id'     => [
						'required' => true,
						'type'     => 'integer',
					],
					'width'          => [
						'required' => true,
						'type'     => 'number',
					],
					'drop'           => [
						'required' => true,
						'type'     => 'number',
					],
					'unit'           => [
						'required' => false,
						'type'     => 'string',
						'default'  => 'cm',
					],
					'quantity'       => [
						'required' => false,
						'type'     => 'integer',
						'default'  => 1,
					],
					'custom_data'    => [
						'required' => false,
						'type'     => 'object',
						'default'  => [],
					],
				],
			]
		);
	}

	/**
	 * Calculate price endpoint handler
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function calculate_price( WP_REST_Request $request ) {
		$width      = (float) $request->get_param( 'width' );
		$drop       = (float) $request->get_param( 'drop' );
		$unit       = (string) $request->get_param( 'unit' );
		$product_id = (int) $request->get_param( 'product_id' );

		// Validate product exists
		if ( ! $this->cart_service->productExists( $product_id ) ) {
			return new WP_Error(
				'product_not_found',
				__( 'Product not found', 'gf-wc-bridge' ),
				[ 'status' => 404 ]
			);
		}

		// Parse unit enum
		$unit_enum = MeasurementUnit::tryFrom( $unit ) ?? MeasurementUnit::default();

		// Prepare cart item data (this includes the calculation)
		$cart_data = $this->cart_service->prepareCartItemData(
			$product_id,
			$width,
			$drop,
			$unit_enum
		);

		// Get the calculation object
		$calculation = $cart_data['gf_wc_calculation'];

		// Format display data
		$display = $this->cart_service->formatCartItemDisplay(
			$calculation,
			$width,
			$drop,
			$unit_enum
		);

		return new WP_REST_Response(
			[
				'price'         => number_format( $calculation->price, 2, '.', '' ),
				'regular_price' => number_format( $calculation->regularPrice, 2, '.', '' ),
				'sale_price'    => number_format( $calculation->salePrice, 2, '.', '' ),
				'is_on_sale'    => $calculation->isOnSale,
				'area'          => number_format( $calculation->areaInMeters, 2, '.', '' ),
				'width_cm'      => number_format( $calculation->widthCm, 2, '.', '' ),
				'drop_cm'       => number_format( $calculation->dropCm, 2, '.', '' ),
				'display'       => $display,
			],
			200
		);
	}

	/**
	 * Add to basket endpoint handler
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_to_basket( WP_REST_Request $request ) {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_inactive',
				__( 'WooCommerce is not active', 'gf-wc-bridge' ),
				[ 'status' => 503 ]
			);
		}

		$product_id  = (int) $request->get_param( 'product_id' );
		$width       = (float) $request->get_param( 'width' );
		$drop        = (float) $request->get_param( 'drop' );
		$unit        = (string) $request->get_param( 'unit' );
		$quantity    = max( 1, (int) $request->get_param( 'quantity' ) );
		$custom_data = $request->get_param( 'custom_data' ) ?? [];

		// Validate product exists
		if ( ! $this->cart_service->productExists( $product_id ) ) {
			return new WP_Error(
				'product_not_found',
				__( 'Product not found', 'gf-wc-bridge' ),
				[ 'status' => 404 ]
			);
		}

		// Parse unit enum
		$unit_enum = MeasurementUnit::tryFrom( $unit ) ?? MeasurementUnit::default();

		// Prepare cart item data with calculation
		$cart_item_data = $this->cart_service->prepareCartItemData(
			$product_id,
			$width,
			$drop,
			$unit_enum,
			$custom_data
		);

		// Add to WooCommerce cart
		$cart_item_key = WC()->cart->add_to_cart(
			$product_id,
			$quantity,
			0,
			[],
			$cart_item_data
		);

		if ( ! $cart_item_key ) {
			return new WP_Error(
				'add_to_cart_failed',
				__( 'Failed to add product to cart', 'gf-wc-bridge' ),
				[ 'status' => 500 ]
			);
		}

		// Calculate cart totals
		WC()->cart->calculate_totals();

		// Get cart fragments for frontend updates
		$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', [] );

		return new WP_REST_Response(
			[
				'success'    => true,
				'message'    => __( 'Product added to cart! Configure another or checkout.', 'gf-wc-bridge' ),
				'cart_count' => WC()->cart->get_cart_contents_count(),
				'cart_url'   => wc_get_cart_url(),
				'cart_hash'  => WC()->cart->get_cart_hash(),
				'fragments'  => $fragments,
			],
			200
		);
	}
}
