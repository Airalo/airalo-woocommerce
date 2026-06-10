<?php

namespace Airalo\Admin;

use Airalo\Admin\Settings\Option;
use Airalo\Services\Airalo\AiraloClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrderValidator {

	private const MAX_QUANTITY = 50;

	/**
	 * Validates the amount of allowed airalo sims in the cart and ensures
	 * the Airalo API credentials are configured before allowing an Airalo
	 * product into the cart. This prevents customers from completing
	 * checkout for an eSIM that cannot be provisioned.
	 *
	 * @param mixed $passed
	 * @param int   $product_id
	 * @param int   $quantity
	 * @return mixed
	 */
	public function handle( $passed, $product_id, $quantity ) {
		if ( $this->is_airalo_product( $product_id ) && ! $this->has_airalo_credentials() ) {
			wc_add_notice(
				__( 'eSIM service is temporarily unavailable. Please try again later or contact support.', 'airalo' ),
				'error'
			);

			return false;
		}

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

	/**
	 * Returns true when the given product id corresponds to an Airalo eSIM
	 * product (identified by the SKU prefix used by the syncer).
	 *
	 * @param int $product_id
	 * @return bool
	 */
	private function is_airalo_product( int $product_id ): bool {
		if ( ! $product_id ) {
			return false;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return false;
		}

		$sku = (string) $product->get_sku();

		return '' !== $sku && strpos( $sku, Product::SKU_PREFIX ) !== false;
	}

	/**
	 * Returns true when the Airalo API credentials are configured for the
	 * current environment. Any failure while checking is treated as
	 * "credentials unavailable" so the cart is blocked and the user gets
	 * a clear error rather than a silent post-checkout failure.
	 *
	 * @return bool
	 */
	private function has_airalo_credentials(): bool {
		try {
			return ( new AiraloClient( new Option() ) )->has_credentials();
		} catch ( \Throwable $ex ) {
			error_log( '[Airalo] Credential check failed in OrderValidator: ' . $ex->getMessage() );

			return false;
		}
	}
}
