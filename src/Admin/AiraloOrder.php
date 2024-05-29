<?php

namespace Airalo\Admin;

use Airalo\Services\Airalo\AiraloClient;
use Airalo\Admin\Settings\Option;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AiraloOrder {
    private const MAX_QUANTITY = 50;

    /**
     * @param mixed $order
     * @return void
     */
    public function handle_order( $order ) {
        $wc_order = wc_get_order( $order );

        try {
            $result = ( new AiraloClient( ( new Option ) ) )
                ->getClient()
                ->orderBulk( $this->get_order_payload( $wc_order ), 'Bulk order placed via Airalo Plugin' );

            if ( !$result ) {
                $wc_order->update_status( 'on-hold', 'Empty Airalo response, please contact support' );

                return;
            }

            $failed_packages = [];

            foreach ( $result as $slug => $response ) {
                if ($response->meta->message != 'success') {
                    $failed_packages[$slug] = $response;
                }
            }

            if ( count( $failed_packages ) ) {
                $wc_order->update_status( 'on-hold', 'There are Airalo package order failures. Response: ' . (string)$result );
            }
        } catch ( \Exception $ex ) {
            error_log( $ex->getMessage() );

            $wc_order->update_status( 'on-hold', 'There are Airalo package order failures. Error: ' . $ex->getMessage() );
        }
    }

    /**
     * @param mixed $orders
     * @param int $quantity
     * @return mixed
     */
    public function handle_validation( $passed, $quantity ) {
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
                $bulk_packages_total += 1;
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
            wc_add_notice( sprintf( 'You cannot add more than %d items containing "%s" to the cart.', self::MAX_QUANTITY, $cart_item['data']->get_name() ), 'error' );

            return false;
        }

        return $passed;
    }

    /**
     * @param mixed $order
     * @return array
     */
    private function get_order_payload( $order ) {
        $items = $order->get_items();

        $order_items = new \Airalo\Admin\OrderItem( $items );

        $airalo_order_items = $order_items->get_airalo_order_items();

        $bulk_payload = [];

        foreach ( $airalo_order_items as $airalo_order_item ) {
            $product = $airalo_order_item->get_product();

            $bulk_payload[str_replace( Product::SKU_PREFIX, '', $product->get_sku() )] = $airalo_order_item['quantity'];
        }

        return $bulk_payload;
    }
}
