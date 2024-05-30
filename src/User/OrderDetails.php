<?php

namespace Airalo\User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OrderDetails {

    /**
     * @param mixed $order
     * @return void
     */
    public function handle( $order ) {
        $meta = $this->get_meta_data( $order );

        $iccids = [];

        foreach ( $meta as $objects ) {
            if ( is_numeric( $objects->get_data()['key'] ) ) {
                $iccids[$objects->get_data()['key']] = explode( PHP_EOL, $objects->get_data()['value'] );
            }
        }

        if ( empty( $iccids ) ) {
            return;
        }

        $this->parse_details( $iccids );
    }

    /**
     * @param mixed $order
     * @return WC_Order
     */
    private function get_meta_data( $order ) {
        return wc_get_order( $order )->get_meta_data();
    }

    /**
     * @param array $iccids
     * @return void
     */
    private function parse_details( $iccids ) {
        echo '<section class="woocommerce-order-details__custom-fields">';
        echo '<h2>' . __( 'eSIM Details' ) . '</h2>';

        foreach ( $iccids as $iccid => $order_lines ) {
            echo '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">';
            echo '<tr><th>ICCID:</th><td>' . $iccid . '</td></tr>';

            foreach ( $order_lines as $values ) {
                list( $title, $val ) = explode( ':', $values );

                echo "<tr><th>$title:</th><td>$val</td></tr>";
            }
        }

        echo '</table>';
        echo '</section>';
    }
}
