<?php

namespace Airalo\User;

use Airalo\Admin\Settings\Option;
use Airalo\Services\Airalo\AiraloClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MySimsDetails {

	/**
	 * @return array
	 */
    public function get_all_user_order_details() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $customer_orders = $this->get_all_user_orders( get_current_user_id() );

        $sims_details = $iccids = [];
    
        foreach ( $customer_orders as $customer_order ) {
            $order_meta = $customer_order->get_meta_data();
    
            foreach ( $order_meta as $meta ) {
                if ( ! $this->is_iccid( $meta->key ) ) {
                    continue;
                }

                $iccid = $meta->key;
                $meta = explode( PHP_EOL, $meta->value );

                $sim_name = $coverage = '';

                foreach ( $meta as $each ) {
                    $each = explode( ': ', $each );

                    $meta_key = $each[0] ?? null;
                    $meta_field = $each[1] ?? null;

                    $this->parse_sim_name_and_coverage( $meta_key, $meta_field, $sim_name, $coverage );
                }

                $iccids[] = $iccid;

                $sims_details[] = [
                    'sim_name' => $sim_name,
                    'coverage' => $coverage,
                    'iccid' => $iccid,
                ];
            }
        }
    
        return $sims_details;
    }

        /**
     * @param string $iccid
     * @param array $sim_detail
     * @return void
     */
    public function append_usage_details_to_iccid( string $iccid, array &$sim_detail ) {
        $airalo_client = ( new AiraloClient( new Option() ) )->getClient();
        $usage = $airalo_client->simUsage( $iccid );

        $sim_detail['total'] = $usage->data->total ?? null;
        $sim_detail['remaining'] = $usage->data->remaining ?? null;
        $sim_detail['status'] = $usage->data->status ?? null;
        $sim_detail['expired_at'] = $usage->data->expired_at ?? null;
    }

    /**
     * @param mixed $val
     * @return boolean
     */
    private function is_iccid( $val ) {
        return is_numeric( $val )
            && strlen( $val ) >= 18
            && strlen( $val ) <= 22;
    }

    /**
     * @param int $user_id
     * @param array $statuses
     * @param int $limit
     * @return array
     */
    private function get_all_user_orders( $user_id, $statuses = [], $limit = -1 ) {
        if ( empty( $statuses ) ) {
            $statuses = ['completed', 'processing', 'on-hold'];
        }

        return wc_get_orders([
            'customer_id' => $user_id,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => $statuses,
            'limit' => $limit,
        ]);
    }

    /**
     * @param mixed $meta_key
     * @param mixed $meta_field
     * @param string $sim_name
     * @param string $coverage
     * @return void
     */
    private function parse_sim_name_and_coverage( $meta_key, $meta_field, &$sim_name, &$coverage ) {
        if ( ! $meta_key || ! $meta_field ) {
            return;
        }

        if ( $meta_key == 'Coverage' ) {
            $parts = explode( '-', $meta_field );

            if ( count( $parts ) ==  1 ) {
                $sim_name = ucwords( $parts[0] );
            } elseif ( count( $parts ) > 1 ) {
                $parts = array_map( 'ucwords', $parts );
                $sim_name = implode( ' ', $parts );
            }

            $coverage = $sim_name;
            $sim_name .= ' - ';
        }

        if ( $meta_key == 'Validity' || $meta_key == 'Data' ) {
            $sim_name .= " $meta_field";
        }
    }
}
