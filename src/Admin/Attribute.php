<?php

namespace Airalo\Admin;

class Attribute
{
    private const HIDDEN_ATTRIBUTES = [ 'net_price', 'price', 'operator_gradient_start', 'operator_gradient_end' ];

    public function create_attributes( array $attributeData ) {
        $attributes = [];
        foreach ( $attributeData as $key => $value ) {
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id( $key );
            $attribute->set_name( $key );
            $attribute->set_options( [$value] );
            $visible = ! in_array( $key, self::HIDDEN_ATTRIBUTES );
            $attribute->set_visible( $visible );

            $attributes[] = $attribute;
        }

        return $attributes;
    }
}