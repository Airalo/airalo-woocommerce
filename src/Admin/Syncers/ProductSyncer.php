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

        if ( $options->fetch_option( Option::ENVIRONMENT_SWITCHED ) == 'true' ) {
            // remove airalo products from the user's page when environment is switched
            $this->removeAllAiraloProducts( $options );
        }

        $environment = $options->get_environment();

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
                        $product->update_or_create( $package, $operator, $item, $setting_create, $setting_update, $image_id, $environment );

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

    private function removeAllAiraloProducts( Option $option ) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_sku',
                    'value' => Product::SKU_PREFIX,
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product( $product_id );

                $taxonomy_name = Term::IMAGE_NAME_PREFIX . $product->get_attribute( 'operator_id' );

                $labels = [
                    'name' => _x( $taxonomy_name, 'taxonomy general name', 'textdomain' ),
                    'singular_name' => _x( $taxonomy_name.'_singular', 'taxonomy singular name', 'textdomain' ),
                ];

                $args = [
                    'labels' => $labels,
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'query_var' => true,
                    'rewrite' => [ 'slug' => $taxonomy_name ],
                ];

                register_taxonomy( $taxonomy_name, ['post'], $args );

                $term_name = $taxonomy_name .'_id';
                $term = get_term_by( 'slug', $term_name,  $taxonomy_name );

                $image_id = get_term_meta( $term->term_id, Term::IMAGE_METADATA_KEY, true );

                wp_delete_term( $term->term_id, $taxonomy_name );

                wp_delete_post( $product_id, true );
                wp_delete_post( $image_id, true );
            }
            wp_reset_postdata();
        }

        $option->insert_option( Option::ENVIRONMENT_SWITCHED, 'false' );
    }
}
