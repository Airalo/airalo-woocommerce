<?php

namespace Airalo\Admin\Syncers;

use Airalo\Admin\Product;

class ProductSyncer {

    public function __construct(public string $products) {
    }

    public function handle() {
        $productArray = json_decode( $this->products, true );
        $data = $productArray['data'];

        foreach ( $data as $item ) {

            foreach ( $item['operators'] as $operator ) {

                foreach ( $operator['packages'] as $package ) {

                    $product = new Product();
                    $product->update_or_create($package, $operator, $item);

                }

            }

        }
    }
}