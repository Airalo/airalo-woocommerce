<?php

namespace Airalo\Admin\Syncers;

use Airalo\Admin\Product;
use Airalo\Admin\Settings\Options;

class ProductSyncer {

    public function __construct(public string $products) {
    }

    public function handle() {
        $productArray = json_decode( $this->products, true );
        $data = $productArray['data'];
        $options = new Options();
        $options->insert_option(Options::LAST_SYNC, date('Y-m-d H:i:s'));
        $error = '';

        try {
            foreach ( $data as $item ) {

                foreach ( $item['operators'] as $operator ) {

                    foreach ( $operator['packages'] as $package ) {

                        $product = new Product();
                        $product->update_or_create($package, $operator, $item);

                    }

                }

            }
            $options->insert_option(Options::LAST_SUCCESSFUL_SYNC, date('Y-m-d H:i:s'));
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
        }

        $options->insert_option(Options::SYNC_ERROR, $error);
    }
}