<?php

namespace Airalo\Admin;
use WC_Order_Item;

class OrderItem
{
    private const SKU_PREFIX = 'airalo-';

    /* @var WC_Order_Item[] */
    private $airalo_order_items;

    /**
     * @var WC_Order_Item[]
     */
    private array $all_order_items;
    public function __construct(array $order_items)
    {
        $this->all_order_items = $order_items;
    }

    public function get_airalo_order_items(): array
    {
        $this->airalo_order_items = array_filter($this->all_order_items, function (WC_Order_Item $orderItem) {
            return strpos($orderItem->get_product()->get_sku(), self::SKU_PREFIX) === 0;
        });

        return $this->airalo_order_items;
    }
}