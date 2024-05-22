<?php

namespace Airalo\Admin\Syncers;

use Airalo\Admin\Product;
use Airalo\Admin\Settings\Option;
use Airalo\Admin\Term;
use Airalo\Services\Airalo\AiraloClient;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ProductSyncer {

    private const AIRALO_MAX_EXECUTION = 600;

    public function handle() {
        ini_set( 'max_execution_time', self::AIRALO_MAX_EXECUTION );
        $options = new Option();

        $environment = $options->fetch_option( Option::USE_SANDBOX ) == Option::ENABLED ? 'sandbox' : 'production';

        $setting_create = $options->fetch_option( Option::AUTO_PUBLISH );
        $setting_update = $options->fetch_option( Option::AUTO_PUBLISH_AFTER_UPDATE );


        try {
            $client = ( new AiraloClient( $environment ) )->getClient();

            $allPackages = $client->getSimPackages();
            $data = $allPackages->data;

            $options->insert_option( Option::LAST_SYNC, date( 'Y-m-d H:i:s' ) );

            $error = '';
            if ( ! $data ) {
                $error = 'No data fetched. Please check your credentials.';
            }

            foreach ( $data as $item ) {

                foreach ( $item->operators as $operator ) {

                    $term = new Term();
                    $term = $term->fetch_or_create_image_term( $operator );
                    $image_id = get_term_meta( $term->term_id, Term::IMAGE_METADATA_KEY, true );

                    foreach ( $operator->packages as $package ) {

                        $product = new Product();
                        $product->update_or_create( $package, $operator, $item, $setting_create, $setting_update, $image_id );

                    }

                }

            }

            $options->insert_option( Option::LAST_SUCCESSFUL_SYNC, date( 'Y-m-d H:i:s' ) );

        } catch ( \Exception $ex ) {
            $error_message = strip_tags( $ex->getMessage() );
            $error = $error_message;

            if  ( stripos( $error_message, 'Airalo SDK initialization failed') !== false ) {
                $error = 'Airalo SDK initialization failed, please check ' . ucfirst( $environment ) . ' credentials';
            }

            error_log( $ex->getMessage() );
        }

        $options->insert_option( Option::SYNC_ERROR, $error );
    }
}