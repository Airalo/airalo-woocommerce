<?php

namespace Airalo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Attribute {
	private const HIDDEN_ATTRIBUTES = [
		'net_price',
		'price',
		'operator_gradient_start',
		'operator_gradient_end',
		'operator_id',
		'is_airalo',
		'country_codes',
	];

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
