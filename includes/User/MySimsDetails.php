<?php

namespace Airalo\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MySimsDetails {

	/**
	 * @return string
	 */
	public function handle() {
        if ( !is_user_logged_in() ) {
            return;
        }

        $customer_orders = $this->get_all_user_orders(get_current_user_id() );

        $sims_details = [];
    
        foreach ( $customer_orders as $customer_order ) {
            $order_meta = $customer_order->get_meta_data();
    
            foreach ( $order_meta as $meta ) {
                if ( !$this->is_iccid( $meta->key ) ) {
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

                $sims_details[] = [
                    'sim_name' => $sim_name,
                    'coverage' => $coverage,
                    'iccid' => $iccid,
                ];
            }
        }
    
        return json_encode( $sims_details );
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
        if ( !$meta_key || !$meta_field ) {
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
