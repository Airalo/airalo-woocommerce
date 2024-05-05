<?php

namespace Airalo\Admin;
use WC_Order_Item;

class OrderItem
{
    private const SKU_PREFIX = 'airalo-';

    /* @var WC_Order_Item[] */
    private $airaloOrderItems;

    /**
     * @var WC_Order_Item[]
     */
    private $allOrderItems;

    public function __construct(array $orderItems)
    {
        $this->allOrderItem = $orderItems;
    }

    public function getAiraloOrderItems(): array
    {
        $this->airaloOrderItems = array_filter($this->allOrderItems, function (WC_Order_Item $orderItem) {
            return strpos($orderItem->get_product()->get_sku(), self::SKU_PREFIX) === 0;
        });

        return $this->airaloOrderItems;
    }
}