<?php
namespace Airalo\Admin;

use Airalo\Admin\Settings\Option;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Product {

	private const STATUS_DRAFT = 'draft';
	private const STATUS_PUBLISH = 'publish';
	const OUT_OF_STOCK = 'outofstock';
	const IN_STOCK = 'instock';
	private const EMPTY_SERVICE_FIELD_DEFAULT = 'No';

	const SKU_PREFIX = 'xiloxf-jpf-';

	private $product;

	public function get_product_by_sku( string $sku ): ?\WC_Product {
		$product_id = wc_get_product_id_by_sku( $sku );
		if ( ! $product_id) {
			return null;
		}

		$this->product = wc_get_product( $product_id );
		return $this->product;
	}

	public function update_or_create( $package, $operator, $item, $setting_create, $setting_update, $image_id, $environment, $setting_name, &$airalo_products ): void {
		$sku = self::SKU_PREFIX . $package->id;
		$product = $this->get_product_by_sku( $sku );
		$product = $product ?? new \WC_Product();
		$status = $product->get_status();

		if ( isset( $airalo_products[$sku] ) ) {
			// mark product as processed to not set it as out of stock
			$airalo_products[$sku]['processed'] = 1;
		}

		$is_update = false;
		$is_create = false;

		if ( ! $product->get_sku() ) {
			$product->set_sku( $sku );
			$status = self::STATUS_DRAFT;
			$is_create = true;
		}

		$info = implode( "\n", (array) $operator->info );
		if ( isset( $operator->other_info ) ) {
			$info.= "\n" . $operator->other_info;
		}

		if ( isset( $package->short_info ) ) {
			$info.= "\n" . $package->short_info;
		}

		if ( ! $is_create && floatval( $product->get_attribute( 'price' ) ) != $package->price ) {
			$is_update = true;
		}

		if ( $image_id ) {
			$product->set_image_id( $image_id );
		}

		$this->set_product_name( $product, $package, $item, $operator, $setting_name, $environment );

		$product->set_description( $info ?? '' );


		$this->set_product_status( $product, $status, $is_create, $is_update, $setting_create, $setting_update );

		$product->set_virtual( true );

		$this->add_operator_attributes( $operator, $product, $package );

		$product->save();
	}

	private function set_product_name( $product, $package, $item, $operator, $setting_name, $environment ) {
		// Operator title is the airalo esim name while the item title is the country
		$main_title = Option::ENABLED == $setting_name ? $operator->title : $item->title;

		$name = $main_title . ' ' . $package->title;

		if ( 'sandbox' == $environment ) {
			$name = strtoupper( $environment ) . ' - ' . $name;
		}

		$product->set_name( $name );
	}

	private function add_operator_attributes(  $operator, \WC_Product $product,  $package ): void {
		$operator_coverage = $operator->coverages ?? [];
		$network_coverage = '';

		foreach ( $operator_coverage as $coverage ) {
			foreach ( $coverage->networks as $network ) {
				$network_coverage .= $network->name . ':' . implode( ', ', (array) $network->types ) . "\n\n";
			}
		}

		$countries = [];
		$countries_iso = [];

		foreach ( $operator->countries as $country ) {
			$countries[] = $country->title;
			$countries_iso[] = $country->country_code;
		}

		$operator_attributes = [
			'operator_gradient_start' => $operator->gradient_start ?? null,
			'operator_gradient_end' => $operator->gradient_end ?? null,
			'voice' => $package->voice ? $package->voice : self::EMPTY_SERVICE_FIELD_DEFAULT,
			'text' => $package->text ? $package->text : self::EMPTY_SERVICE_FIELD_DEFAULT,
			'data' => $package->amount ? $package->amount : null,
			'apn_type' => $operator->apn_type ?? null,
			'apn_value' => $operator->apn_value ?? null,
			'is_roaming' => $operator->is_roaming ?? null,
			'network_coverage' => $network_coverage,
			'net_price' => $package->net_price ?? null,
			'price' => $package->price ?? null,
			'operator_id' => $operator->id ?? null,
			'is_airalo' => 1,
			'country' => implode( ', ', $countries ),
			'country_codes' => implode( ', ', $countries_iso ),
		];

		$attributes = ( new Attribute() )->create_attributes( $operator_attributes );

		$product->set_attributes( $attributes );
	}

	private function set_product_status(\WC_Product $product, $status, $is_created, $is_updated, $setting_create, $setting_update ): void {
		$new_status = $status;

		if ( self::STATUS_DRAFT == $status && $is_created && Option::ENABLED == $setting_create ) {
			$new_status = self::STATUS_PUBLISH;
		} elseif ( self::STATUS_PUBLISH == $status && $is_updated && Option::DISABLED == $setting_update ) {
			$new_status = self::STATUS_DRAFT;
		}

		$product->set_status( $new_status );
	}
}
