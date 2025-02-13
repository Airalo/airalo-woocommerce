<?php

namespace Airalo\Admin;

use Airalo\Services\Airalo\AiraloClient;
use Airalo\Admin\Settings\Option;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AiraloOrder {

	private $airalo_client;
	private $translations;

	private $description = 'Bulk order placed via Airalo Wordpress Plugin';

	public function __construct() {
		$this->airalo_client = ( new AiraloClient( new Option() ) )->getClient();

		$language = ( new \Airalo\Admin\Settings\Option() )->fetch_option( \Airalo\Admin\Settings\Option::LANGUAGE );
        $translations = file_get_contents( __DIR__ . '../../../languages/translations.json' );
        $translations = json_decode( $translations, true );
        $this->translations = $translations[$language];
	}

	/**
	 * Sends order call to Airalo through the sdk
	 *
	 * @param mixed $order
	 * @return void
	 */
	public function handle( $wc_order ) {
		if ( $this->is_orer_processed( $wc_order ) ) {
			return;
		}

		$use_esim_cloud_share = ( new \Airalo\Admin\Settings\Option() )->fetch_option( \Airalo\Admin\Settings\Option::USE_ESIM_CLOUD_SHARE );

		$payload = $this->get_order_payload( $wc_order );

		try {
			$result = ( $use_esim_cloud_share == \Airalo\Admin\Settings\Option::ENABLED )
				? $this->airalo_client->orderBulkWithEmailSimShare( $payload, $this->get_esim_share_data( $wc_order ), $this->description )
				: $this->airalo_client->orderBulk( $payload, $this->description );

			if ( !$result ) {
				$wc_order->update_status( 'on-hold', 'Empty Airalo response, please contact support' );

				return;
			}

			$failed_packages = [];

			foreach ( $result as $slug => $response ) {
				if ( 'success' != $response->meta->message ) {
					$failed_packages[$slug] = $response;

					continue;
				}

				$this->add_order_meta( $wc_order, $slug, $response );
			}

			if ( count( $failed_packages ) ) {
				$wc_order->update_status( 'on-hold', 'There are Airalo package order failures. Response: ' . (string) $result );
			}
		} catch ( \Exception $ex ) {
			error_log( $ex->getMessage() );

			$wc_order->update_status( 'on-hold', 'There are Airalo package order failures. Error: ' . $ex->getMessage() );
		}
	}

	/**
	 * Fetches iccids to send as the order payload
	 *
	 * @param mixed $order
	 * @return array
	 */
	private function get_order_payload( $order ) {
		$items = $order->get_items();

		$order_items = new \Airalo\Admin\OrderItem( $items );

		$airalo_order_items = $order_items->get_airalo_order_items();

		$bulk_payload = [];

		foreach ( $airalo_order_items as $airalo_order_item ) {
			$product = $airalo_order_item->get_product();

			$bulk_payload[str_replace( Product::SKU_PREFIX, '', $product->get_sku() )] = $airalo_order_item['quantity'];
		}

		return $bulk_payload;
	}

	/**
	 * Adds meta data to order
	 *
	 * @param mixed $wc_order
	 * @param string $slug
	 * @param mixed $response
	 * @return void
	 */
	private function add_order_meta( $wc_order, string $slug, $response ) {
		$package_data = [];

		try {
			$packages = $this->airalo_client->getAllPackages( true );

			foreach ($packages['data'] as $package) {
				if ($package->package_id != $slug) {
					continue;
				}

				$package_data['location'] = ucfirst( $package->slug );
				$package_data['package_id'] = $package->package_id;
			}

			$sims = $response->data->sims ?? [];

			foreach ($sims as $sim) {
				$days_key = 'my.esims.package.' . ( $response->data->validity == 1 ? 'day' : 'days' );

				$wc_order->add_meta_data( $sim->iccid, implode(PHP_EOL, [
					'Coverage: ' . $package_data['location'],
					'Package ID: ' . $package_data['package_id'],
					'Validity: ' . $response->data->validity . ' ' . $this->translations[$days_key],
					'Data: ' . $response->data->data,
					'Minutes: ' . ( $response->data->voice ? $response->data->voice : 'N/A' ),
					'SMS: ' . ( $response->data->text ? $response->data->text : 'N/A' ),
					'QR code link: ' . $sim->qrcode_url ?? 'N/A',
					'Apple direct installation link: ' . $sim->direct_apple_installation_url ?? 'N/A',
					'eSIM sharing passcode: ' . ( $sim->sharing->access_code ?? 'N/A' ),
					'eSIM sharing link: ' . ( $sim->sharing->link ?? 'N/A' ),
				] ) );

				$wc_order->save();
			}
		} catch ( \Exception $ex ) {
			error_log( $ex->getMessage() );
		}
	}

	/**
	 * @param mixed $order
	 * @return array
	 */
	private function get_esim_share_data( $order ) {
		return [
			'to_email' => $order->get_billing_email(),
			'sharing_option' => ['link', 'pdf'],
		];
	}

	/**
	 * @param mixed $val
	 * @return boolean
	 */
	private function is_iccid( $val ) {
        return is_numeric( $val )
            && strlen( $val ) >= 18
            && strlen( $val ) <= 22;
    }

	/**
	 * @param mixed $order
	 * @return boolean
	 */
	private function is_orer_processed( $order ) {
		$meta = $order->get_meta_data();

		if ( ! $meta ) {
			return false;
		}

		foreach ( $meta as $item ) {
			if ( $this->is_iccid( $item->key ) ) {
				// Order already processed
				return true;
			}
		}

		return false;
	}
}
