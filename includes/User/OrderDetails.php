<?php

namespace Airalo\User;

use Airalo\Admin\Settings\Option;
use Airalo\Services\Airalo\AiraloClient;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OrderDetails {

    /** @var \Airalo\Airalo  */
    private $airalo_client;

    public function __construct() {
        $this->airalo_client = ( new AiraloClient( new Option() ) )->getClient();
    }

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

        $query_params = isset ( $_SERVER['QUERY_STRING'] ) ? wp_parse_args( sanitize_text_field( $_SERVER['QUERY_STRING'] ) ) : [];

        $page_id = isset( $query_params['page_id'] ) ? intval( $query_params['page_id'] ) : 0;
        $order_id = isset( $query_params['view-order'] ) ? intval( $query_params['view-order'] ) : 0;

        foreach ( $iccids as $iccid => $order_lines ) {
            echo '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">';
            echo '<tr><th>ICCID:</th><td><a href="' . esc_html( esc_url ( home_url( '/?action_method=airalo_instructions&iccid=' . $iccid . '&rp=' . $page_id . '&op=' . $order_id ) ) ) . '">' . esc_html( $iccid ) . '</a></td></tr>';

            foreach ( $order_lines as $values ) {
                list( $title, $val ) = explode( ':', $values );

                if ( 'Package ID' == $title ) {
                    continue;
                }

                echo '<tr><th>' . esc_html( $title ) . ' :</th><td>' . esc_html($val) . '</td></tr>';
            }

            $this->add_data_usage_details( $iccid );
        }

        echo '</table>';
        echo '</section>';

        echo '<style>' . esc_attr( file_get_contents( __DIR__ . '/../../assets/css/dataUsageModalStyle.css' ) ) . '</style>';
    }

    /**
     * Shows data usage of sim
     *
     * @param string $iccids
     * @return void
     */
    private function add_data_usage_details( $iccid ) {
        echo '<tr><td colspan="2"><button class="wp-block-button wp-block-button__link" onclick="document.getElementById(\'usageModal-' . esc_attr( $iccid ) . '\').style.display=\'block\'">Show Usage</button></td></tr>';

        $data = $this->airalo_client->simUsage($iccid);
        $usage_data = $data ? $data->data : null;

        echo '<div id="usageModal-' . esc_attr( $iccid ) . '" class="usage-modal">';
        echo '<div class="modal-content">';
        echo '<span class="close" onclick="document.getElementById(\'usageModal-' . esc_attr( $iccid ) . '\').style.display=\'none\'">&times;</span>';
        echo '<h2>Usage Details for ICCID: ' . esc_html( $iccid ) . '</h2>';
        echo '<hr>';

        if ( !$usage_data ) {
            echo '<p>Data usage for the eSIM is currently not available. Please try it again later</p>';

            echo '</div></div>';

            return;
        }
        
        foreach ( $usage_data as $key => $value ) {
            if ( ! $value ) {
                continue;
            }

            if ( 'total' == $key ) {
                $value = (int) $value / 1000 . ( (int) $value > 1000 ? ' GB' : ' MB' );
            }

            if ( 'remaining' == $key ) {
                $value = (int) $value > 1000
                    ? (int) $value / 1000 . ' GB'
                    : (int) $value . ' MB';
            }

            echo '<p>'
                . esc_html( ucwords( str_replace( '_', ' ', $key ) ) ) . ': <b>' . esc_html( $value ) . '</b>'
                . '</p>';
        }

        echo '</div></div>';
    }
}
