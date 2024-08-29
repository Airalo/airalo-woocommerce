<?php

namespace Airalo\Services\Airalo;

use Airalo\Admin\Settings\Credential;
use Airalo\Admin\Settings\Option;
use Airalo\Airalo;

class AiraloClient {
	private $environment = '';
	private $language = 'en';

	public function __construct( Option $option ) {
		$this->environment = $option->get_environment();
		$this->language = $option->fetch_option( Option::LANGUAGE ) ?? $this->language;
	}

	public function getClient(): Airalo {

		$credential = new Credential();

		if ( $this->is_sandbox() ) {
			$client_id = $credential->get_credential( Credential::CLIENT_ID_SANDBOX );
			$client_secret = $credential->get_credential( Credential::CLIENT_SECRET_SANDBOX );
		} else {
			$client_id = $credential->get_credential( Credential::CLIENT_ID );
			$client_secret = $credential->get_credential( Credential::CLIENT_SECRET );
		}


		return new Airalo( [
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'env' => $this->environment,
			'http_headers' => [
				'woocommerce-plugin: ' . AIRALO_PLUGIN_VERSION,
				'Accept-Language: ' . $this->language,
			],
		] );
	}

	public function is_sandbox(): bool {
		return 'sandbox' == $this->environment;
	}
}
