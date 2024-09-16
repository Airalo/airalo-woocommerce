<?php

namespace Airalo\Admin\Syncers;

use Airalo\Admin\InstallationInstruction;
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
		set_time_limit( self::AIRALO_MAX_EXECUTION );
		$options = new Option();

		if ( 'true' == $options->fetch_option( Option::ENVIRONMENT_SWITCHED ) ) {
			// remove airalo products from the user's page when environment is switched
			$this->remove_all_airalo_products( $options );
		}

		$environment = $options->get_environment();

		$setting_create = $options->fetch_option( Option::AUTO_PUBLISH );
		$setting_update = $options->fetch_option( Option::AUTO_PUBLISH_AFTER_UPDATE );
		$sync_images = $options->fetch_option( Option::SYNC_IMAGES );
		$setting_name = $options->fetch_option( Option::USE_AIRALO_SIM_NAME );

		try {
			$airalo_products = $this->fetch_airalo_products();

			$client = ( new AiraloClient( $options ) )->getClient();

			$allPackages = $client->getSimPackages();
			$data = $allPackages->data;

			$options->insert_option( Option::LAST_SYNC, gmdate( 'Y-m-d H:i:s' ) );

			$error = '';
			if ( ! $data ) {
				$error = 'No data fetched. Please check your credentials.';
			}

			foreach ( $data as $item ) {

				foreach ( $item->operators as $operator ) {

					$image_id = null;
					if ( Option::ENABLED == $sync_images ) {
						$term = new Term();
						$term = $term->fetch_or_create_image_term( $operator );
						$image_id = get_term_meta( $term->term_id, Term::IMAGE_METADATA_KEY, true );
					}

					foreach ( $operator->packages as $package ) {

						$product = new Product();
						$product->update_or_create( $package, $operator, $item, $setting_create, $setting_update, $image_id, $environment, $setting_name, $airalo_products );

					}

				}

			}

			$this->check_stock( $airalo_products );

			$options->insert_option( Option::LAST_SUCCESSFUL_SYNC, gmdate( 'Y-m-d H:i:s' ) );
		} catch ( \Exception $ex ) {
			$error_message = wp_strip_all_tags( $ex->getMessage() );
			$error = " failed due to: `$error_message` on `$environment` environment";

			error_log( $ex->getMessage() );
		}

		$options->insert_option( Option::SYNC_ERROR, $error );
	}

	/**
	 * We do not return out of stock packages so this function
	 * Checks if the sku was marked as processed and sets the product out of stock.
	 *
	 * @param $airalo_products
	 * @return void
	 */
	private function check_stock( $airalo_products ) {
		foreach ( $airalo_products as $airalo_product ) {
			$product = $airalo_product['product'];
			$stock_status = Product::IN_STOCK;

			if ( ! isset ( $airalo_product['processed'] ) ) {
				$stock_status = Product::OUT_OF_STOCK;
			}

			$product->set_stock_status( $stock_status );
			$product->save();
		}
	}

	private function fetch_airalo_products() {
		$products_by_sku = [];
		$query = $this->get_airalo_products_query();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );
				$products_by_sku[$product->get_sku()] = ['product' => $product];
			}
		}

		return $products_by_sku;
	}

	private function get_airalo_products_query(): \WP_Query {
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

		return new \WP_Query( $args );
	}

	private function remove_all_airalo_products( Option $option ) {
		$query = $this->get_airalo_products_query();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product_id = get_the_ID();
				$product = wc_get_product( $product_id );

				$taxonomy_name = Term::IMAGE_NAME_PREFIX . $product->get_attribute( 'operator_id' );

				$labels = [
				    'name'=> sprintf(
				        _x( '%s', 'taxonomy general name', 'airalo' ),
				        esc_html( $taxonomy_name )
                    ),
                    'singular_name' => sprintf(
                        _x( '%s_singular', 'taxonomy singular name', 'airalo' ),
                        esc_html( $taxonomy_name )
                    ),
                ];

				$args = [
					'labels' => $labels,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true,
					'rewrite' => [ 'slug' => $taxonomy_name ],
				];

				register_taxonomy( $taxonomy_name, ['post'], $args );

				$term_name = $taxonomy_name . '_id';
				$term = get_term_by( 'slug', $term_name, $taxonomy_name );

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
