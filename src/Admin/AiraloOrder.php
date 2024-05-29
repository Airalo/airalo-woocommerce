<?php

namespace Airalo\Admin;

use Airalo\Services\Airalo\AiraloClient;
use Airalo\Admin\Settings\Option;
use Airalo\Airalo;
use Airalo\Helpers\Cached;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AiraloOrder {

    private Airalo $airalo_client;

    public function __construct()
    {
        $this->airalo_client = ( new AiraloClient( new Option ) )->getClient();
    }

    /**
     * @param mixed $order
     * @return void
     */
    public function handle( $wc_order ) {
        try {
            $result = $this->airalo_client
                ->orderBulk( $this->get_order_payload( $wc_order ), 'Bulk order placed via Airalo Plugin' );

            if ( !$result ) {
                $wc_order->update_status( 'on-hold', 'Empty Airalo response, please contact support' );

                return;
            }

            $failed_packages = [];

            foreach ( $result as $slug => $response ) {
                if ( $response->meta->message != 'success' ) {
                    $failed_packages[$slug] = $response;

                    continue;
                }

                $this->add_order_meta( $wc_order, $slug, $response );
            }

            if (count($failed_packages)) {
                $wc_order->update_status( 'on-hold', 'There are Airalo package order failures. Response: ' . (string)$result );
            }

            Cached::get( function() {
                return true; // this order is done
            }, $wc_order->get_id() );
        } catch ( \Exception $ex ) {
            error_log( $ex->getMessage() );

            $wc_order->update_status( 'on-hold', 'There are Airalo package order failures. Error: ' . $ex->getMessage() );
        }
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

            $bulk_payload[str_replace(Product::SKU_PREFIX, '', $product->get_sku())] = $airalo_order_item['quantity'];
        }

        return $bulk_payload;
    }

    /**
     * @param mixed $wc_order
     * @param string $slug
     * @param mixed $response
     * @return void
     */
    private function add_order_meta( $wc_order, string $slug, $response ) {
        $package_data = [];

        try {
            $packages = $this->airalo_client->getAllPackages( true );

            foreach ($packages['data'] as $package) {
                if ($package->package_id != $slug) {
                    continue;
                }
                
                $package_data['location'] = ucfirst( $package->slug );
            }

            $sims = $response->data->sims ?? [];

            foreach ($sims as $sim) {
                $wc_order->add_meta_data( $sim->iccid, implode(PHP_EOL, [
                    'Coverage: ' . $package_data['location'],
                    'Package ID: ' . $package->package_id,
                    'Validity: ' . $response->data->validity . ' days',
                    'Data: ' . $response->data->data,
                    'Minutes: ' . ( $response->data->voice ? $response->data->voice : 'N/A' ),
                    'SMS: ' . ( $response->data->text ? $response->data->text : 'N/A' ),
                ]));

                $wc_order->save();
            }
        } catch ( \Exception $ex ) {
            error_log( $ex->getMessage() );
        }
    }
}
