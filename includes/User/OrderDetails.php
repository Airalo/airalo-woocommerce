<?php

namespace Airalo\User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OrderDetails {

    /**
     * Takes an order and fetches the iccids from it then parses the data
     *
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
     * Fetches metadata from the order to fetch the iccid
     *
     * @param mixed $order
     * @return WC_Order
     */
    private function get_meta_data( $order ) {
        return wc_get_order( $order )->get_meta_data();
    }

    /**
     * Adds order details table to the order line page.
     *
     * @param array $iccids
     * @return void
     */
    private function parse_details( $iccids ) {
        echo '<section class="woocommerce-order-details__custom-fields">';
        echo '<h2>' . esc_html( __( 'eSIM Details' ) ) . '</h2>';

        $query_params = isset ( $_SERVER['QUERY_STRING'] ) ? wp_parse_args( $_SERVER['QUERY_STRING'] ) : [];
        $page_id = isset( $query_params['page_id'] ) ? intval( $query_params['page_id'] ) : 0;
        $order_id = isset( $query_params['view-order'] ) ? intval( $query_params['view-order'] ) : 0;


        foreach ( $iccids as $iccid => $order_lines ) {
            echo '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">';
            echo '<tr><th>ICCID:</th><td><a href="' . esc_html( home_url( '/?action=airalo_instructions&iccid=' . $iccid .'&p=' . $page_id . '&op=' . $order_id ) ) . '">' . esc_html( $iccid ) . '</a></td></tr>';

            foreach ( $order_lines as $values ) {
                list( $title, $val ) = explode( ':', $values );

                if ( 'Package ID' == $title ) {
                    continue;
                }

                echo '<tr><th>' . esc_html( $title ) . ' :</th><td>' . esc_html($val) . '</td></tr>';
            }
        }

        echo '</table>';
        echo '</section>';
    }
}
