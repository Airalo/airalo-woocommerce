<?php
namespace Airalo\Admin;

use Airalo\Admin\Settings\Options;

class Product {

    private const STATUS_DRAFT = 'draft';
    private const STATUS_PUBLISH = 'publish';
    private const SKU_PREFIX = 'airalo-';

    private ?\WC_Product $product;

    public function get_product_by_sku( string $sku ): ?\WC_Product {
        $productId = wc_get_product_id_by_sku( $sku );
        if ( ! $productId) {
            return null;
        }

        $this->product = wc_get_product( $productId );
        return $this->product;
    }

    public function update_or_create( $package, $operator, $item ): void
    {
        $sku = self::SKU_PREFIX . $package['id'];
        $product = $this->get_product_by_sku( $sku );
        $product = $product ?? new \WC_Product();
        $status = $product->get_status();
        $this->add_operator_image( $operator, $product );
        $is_update = false;
        $is_create = false;
        if (! $product->get_sku()) {
            $product->set_sku( $sku );
            $status = self::STATUS_DRAFT;
            $is_create = true;
        }

        $info = implode("\n", $operator['info']);
        if ( isset( $operator['other_info'] ) ) {
            $info.= "\n" . $operator['other_info'];
        }

        if ( isset( $package['short_info'] ) ) {
            $info.= "\n" . $package['short_info'];
        }

        if ( ! $is_create && floatval($product->get_regular_price()) != $package['price'] ) {
            $is_update = true;
        }

        $product->set_price( $package['price'] );
        $product->set_regular_price( $package['price'] );
        $product->set_description( $info ?? '');
        $product->set_name( $item['title']. ' ' .$package['title'] );

        $this->set_product_status( $product, $status, $is_create, $is_update );

        $stockStatus = 'instock';
        if ( $package['amount'] <= 0 ) {
            $stockStatus = 'outofstock';
        }

        $product->set_stock_status( $stockStatus );

        $product->set_stock_quantity( $package['amount'] );
        $product->set_virtual( true );

        $this->add_operator_attributes( $operator, $product, $package );

        $product->save();
    }

    private function add_operator_image( array $operator, \WC_Product $product ) {
        // fetch term using operator name
        $term = new Term();
        $term = $term->fetch_or_create_image_term( $operator );
        $image_id = get_term_meta( $term->term_id, Term::IMAGE_METADATA_KEY, true );
        $product->set_image_id( $image_id );
    }

    private function add_operator_attributes( array $operator, \WC_Product $product, array $package ): void {
        $operatorCoverage = $operator['coverages'] ?? [];
        $networkCoverage = '';
        foreach ( $operatorCoverage as $coverage ) {
            foreach ( $coverage['networks'] as $network ) {
                $networkCoverage .= $network['name'] . ':' . implode( ', ', $network['types'] ) ."\n\n";
            }
        }

        $operatorAttributes = [
            'operator_gradient_start' => $operator['gradient_start'] ?? null,
            'operator_gradient_end' => $operator['gradient_end'] ?? null,
            'apn_type' => $operator['apn_type'] ?? null,
            'apn_value' => $operator['apn_value'] ?? null,
            'is_roaming' => $operator['is_roaming'] ?? null,
            'network_coverage' => $networkCoverage,
            'net_price' => $package['net_price'] ?? null,
            'price' => $package['price'] ?? null,
        ];

        $attributes = ( new Attribute() )->create_attributes( $operatorAttributes );

        $product->set_attributes( $attributes );
    }

    private function set_product_status(\WC_Product $product, $status, $is_created, $is_updated ): void {
        $options = new Options();
        $setting_create = $options->fetch_option(Options::AUTO_PUBLISH);
        $setting_update = $options->fetch_option(Options::AUTO_PUBLISH_AFTER_UPDATE);
        $new_status = $status;

        if ( $status == self::STATUS_DRAFT && $is_created && $setting_create == Options::ENABLED) {
            $new_status = self::STATUS_PUBLISH;
        } elseif ( $status == self::STATUS_PUBLISH && $is_updated && $setting_update == Options::DISABLED) {
            $new_status = self::STATUS_DRAFT;
        }

        $product->set_status($new_status);
    }
}