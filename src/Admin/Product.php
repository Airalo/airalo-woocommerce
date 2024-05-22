<?php
namespace Airalo\Admin;

use Airalo\Admin\Settings\Option;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Product {

    private const STATUS_DRAFT = 'draft';
    private const STATUS_PUBLISH = 'publish';
    private const SKU_PREFIX = 'airalo-';

    private ?\WC_Product $product;

    public function get_product_by_sku( string $sku ): ?\WC_Product {
        $product_id = wc_get_product_id_by_sku( $sku );
        if ( ! $product_id) {
            return null;
        }

        $this->product = wc_get_product( $product_id );
        return $this->product;
    }

    public function update_or_create( $package, $operator, $item, $setting_create, $setting_update, $image_id, $environment ): void
    {
        $sku = self::SKU_PREFIX . $package->id;
        $product = $this->get_product_by_sku( $sku );
        $product = $product ?? new \WC_Product();
        $status = $product->get_status();

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

        if ( ! $is_create && floatval($product->get_regular_price()) != $package->price ) {
            $is_update = true;
        }

        if ( $image_id ) {
            $product->set_image_id( $image_id );
        }

        $product->set_description( $info ?? '');
        $name = $item->title. ' ' .$package->title;
        if ( $environment == 'sandbox' ) {
            $name = strtoupper($environment) . ' - ' . $name;
        }

        $product->set_name($name);

        $this->set_product_status( $product, $status, $is_create, $is_update, $setting_create, $setting_update );

        $stock_status = 'instock';
        if ( $package->amount <= 0 ) {
            $stock_status = 'outofstock';
        }

        $product->set_stock_status( $stock_status );

        $product->set_stock_quantity( $package->amount );
        $product->set_virtual( true );

        $this->add_operator_attributes( $operator, $product, $package );

        $product->save();
    }

    private function add_operator_attributes(  $operator, \WC_Product $product,  $package ): void {
        $operator_coverage = $operator->coverages ?? [];
        $network_coverage = '';
        foreach ( $operator_coverage as $coverage ) {
            foreach ( $coverage->networks as $network ) {
                $network_coverage .= $network->name . ':' . implode( ', ', (array) $network->types ) ."\n\n";
            }
        }

        $operator_attributes = [
            'operator_gradient_start' => $operator->gradient_start ?? null,
            'operator_gradient_end' => $operator->gradient_end ?? null,
            'apn_type' => $operator->apn_type ?? null,
            'apn_value' => $operator->apn_value ?? null,
            'is_roaming' => $operator->is_roaming ?? null,
            'network_coverage' => $network_coverage,
            'net_price' => $package->net_price ?? null,
            'price' => $package->price ?? null,
            'operator_id' => $operator->id ?? null,
        ];

        $attributes = ( new Attribute() )->create_attributes( $operator_attributes );

        $product->set_attributes( $attributes );
    }

    private function set_product_status(\WC_Product $product, $status, $is_created, $is_updated, $setting_create, $setting_update ): void {
        $new_status = $status;

        if ( $status == self::STATUS_DRAFT && $is_created && $setting_create == Option::ENABLED) {
            $new_status = self::STATUS_PUBLISH;
        } elseif ( $status == self::STATUS_PUBLISH && $is_updated && $setting_update == Option::DISABLED) {
            $new_status = self::STATUS_DRAFT;
        }

        $product->set_status($new_status);
    }
}