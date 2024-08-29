<?php

namespace Airalo\Admin;

use WC_Order_Item;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class OrderItem {
	private const SKU_PREFIX = 'xiloxf-jpf-';

	/* @var WC_Order_Item[] $airalo_order_items */
	private $airalo_order_items;

	/* @var WC_Order_Item[] $all_order_items */
	private $all_order_items;

	public function __construct( array $order_items ) {
		$this->all_order_items = $order_items;
	}

	public function get_airalo_order_items(): array {
		$this->airalo_order_items = array_filter($this->all_order_items, function ( WC_Order_Item $order_item ) {
			return strpos( $order_item->get_product()->get_sku(), self::SKU_PREFIX ) === 0;
		});

		return $this->airalo_order_items;
	}
}
