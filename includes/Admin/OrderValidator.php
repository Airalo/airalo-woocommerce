<?php

namespace Airalo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrderValidator {

	private const MAX_QUANTITY = 50;

	/**
	 * Validates the amount of allowed airalo esims in the cart
	 *
	 * @param mixed $orders
	 * @param int $quantity
	 * @return mixed
	 */
	public function handle( $passed, $quantity ) {
		if ( $quantity > self::MAX_QUANTITY ) {
			wc_add_notice( sprintf('You cannot add more than %d items to the cart.', self::MAX_QUANTITY), 'error' );

			return false;
		}

		$cart = WC()->cart->get_cart();

		$total_quantity_in_cart = 0;
		$bulk_packages_total = 0;

		foreach ( $cart as $cart_item ) {
			$product_name = $cart_item['data']->get_sku();

			if ( strpos( $product_name, Product::SKU_PREFIX ) !== false ) {
				++$bulk_packages_total;
				$total_quantity_in_cart += $cart_item['quantity'];
			}
		}

		if ( $bulk_packages_total > self::MAX_QUANTITY ) {
			wc_add_notice( sprintf('You cannot add more than %d different eSIM products to the cart.', self::MAX_QUANTITY, $cart_item['data']->get_name()), 'error' );

			return false;
		}

		if (
			( $total_quantity_in_cart > self::MAX_QUANTITY )
			|| ( $total_quantity_in_cart + $quantity > self::MAX_QUANTITY )
		) {
			wc_add_notice( sprintf('You cannot add more than %d items containing "%s" to the cart.', self::MAX_QUANTITY, $cart_item['data']->get_name()), 'error' );

			return false;
		}

		return $passed;
	}
}
