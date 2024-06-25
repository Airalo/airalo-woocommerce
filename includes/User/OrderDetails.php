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
        echo '<h2>' . esc_html( __( 'eSIM Details' ) ) . '</h2>';

        $query_params = wp_parse_args( $_SERVER['QUERY_STRING'] );
        $page_id = isset( $query_params['page_id'] ) ? intval( $query_params['page_id'] ) : 0;
        $order_id = isset( $query_params['view-order'] ) ? intval( $query_params['view-order'] ) : 0;

        foreach ( $iccids as $iccid => $order_lines ) {
            echo '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">';
            echo '<tr><th>ICCID:</th><td><a href="' . esc_html( esc_url ( home_url( '/?action_method=airalo_instructions&iccid=' . $iccid . '&rp=' . $page_id . '&op=' . $order_id ) ) ). '">' . esc_html( $iccid ) . '</a></td></tr>';

            foreach ( $order_lines as $values ) {
                list( $title, $val ) = explode( ':', $values );
                if ( $title == 'Package ID' ) {
                    continue;
                }
                echo "<tr><th>" . esc_html( $title ) . " :</th><td>" . esc_html($val) . "</td></tr>";
            }
        }

        echo '</table>';
        echo '</section>';
    }
}
