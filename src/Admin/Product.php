<?php
namespace Airalo\Admin;

class Product {

    private const STATUS_DRAFT = 'draft';
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
        if (! $product->get_sku()) {
            $product->set_sku( $sku );
            $status = self::STATUS_DRAFT;
        }

        $info = implode("\n", $operator['info']);
        if ( isset( $operator['other_info'] ) ) {
            $info.= "\n" . $operator['other_info'];
        }

        if ( isset( $package['short_info'] ) ) {
            $info.= "\n" . $package['short_info'];
        }

        $product->set_price( $package['price'] );
        $product->set_regular_price( $package['price'] );
        $product->set_description( $info ?? '');
        $product->set_name( $item['title']. ' ' .$package['title'] );

        // Allows user to publish the product instead of it automatically showing in their shop
        if ( $status == self::STATUS_DRAFT ) {
            $product->set_status( $status );
        }

        $stockStatus = 'instock';
        if ( $package['amount'] <= 0 ) {
            $stockStatus = 'outofstock';
        }

        $product->set_stock_status( $stockStatus );

        $product->set_stock_quantity( $package['amount'] );
        $product->set_virtual( true );

        $this->add_operator_attributes( $operator, $product );

        $product->save();
    }

    private function add_operator_image( array $operator, \WC_Product $product ) {
        // fetch term using operator name
        $term = new Term();
        $term = $term->fetch_or_create_image_term( $operator );
        $imageId = get_term_meta( $term->term_id, Term::IMAGE_METADATA_KEY, true );
        $product->set_image_id( $imageId );
    }

    private function add_operator_attributes( array $operator, \WC_Product $product ): void {
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
        ];

        $attributes = [];
        foreach ( $operatorAttributes as $key => $value ) {
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id( $key );
            $attribute->set_name( $key );
            $attribute->set_options( [$value] );

            $attributes[] = $attribute;
        }

        $product->set_attributes( $attributes );
    }
}