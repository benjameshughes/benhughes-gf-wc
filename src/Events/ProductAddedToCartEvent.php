<?php
/**
 * Product Added To Cart Event
 *
 * @package BenHughes\GravityFormsWC
 * @since   2.4.0
 */

declare(strict_types=1);

namespace BenHughes\GravityFormsWC\Events;

/**
 * Event dispatched when a product is added to cart
 */
class ProductAddedToCartEvent extends AbstractEvent {

	/**
	 * Product ID
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Cart item key
	 *
	 * @var string
	 */
	private string $cart_item_key;

	/**
	 * Cart item data
	 *
	 * @var array<string, mixed>
	 */
	private array $cart_item_data;

	/**
	 * Quantity
	 *
	 * @var int
	 */
	private int $quantity;

	/**
	 * Constructor
	 *
	 * @param int                  $product_id     Product ID.
	 * @param string               $cart_item_key  Cart item key.
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $quantity       Quantity.
	 */
	public function __construct(
		int $product_id,
		string $cart_item_key,
		array $cart_item_data,
		int $quantity
	) {
		$this->product_id     = $product_id;
		$this->cart_item_key  = $cart_item_key;
		$this->cart_item_data = $cart_item_data;
		$this->quantity       = $quantity;
	}

	/**
	 * Get product ID
	 *
	 * @return int
	 */
	public function getProductId(): int {
		return $this->product_id;
	}

	/**
	 * Get cart item key
	 *
	 * @return string
	 */
	public function getCartItemKey(): string {
		return $this->cart_item_key;
	}

	/**
	 * Get cart item data
	 *
	 * @return array<string, mixed>
	 */
	public function getCartItemData(): array {
		return $this->cart_item_data;
	}

	/**
	 * Get quantity
	 *
	 * @return int
	 */
	public function getQuantity(): int {
		return $this->quantity;
	}
}
