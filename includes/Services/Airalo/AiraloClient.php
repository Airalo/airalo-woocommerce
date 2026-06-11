<?php

namespace Airalo\Services\Airalo;

use Airalo\Admin\Settings\Credential;
use Airalo\Admin\Settings\Option;
use Airalo\Airalo;
use Airalo\Exceptions\AiraloException;

class AiraloClient {
	private $environment = '';
	private $language = 'en';

	public function __construct( Option $option ) {
		$this->environment = $option->get_environment();
		$this->language = $option->fetch_option( Option::LANGUAGE ) ?? $this->language;
	}

	/**
	 * Returns true when both client_id and client_secret are configured for
	 * the current environment.
	 */
	public function has_credentials(): bool {
		[ $client_id, $client_secret ] = $this->fetch_credentials();

		return ! empty( $client_id ) && ! empty( $client_secret );
	}

	public function getClient(): Airalo {
		[ $client_id, $client_secret ] = $this->fetch_credentials();

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			throw new AiraloException(
				'Airalo SDK cannot be initialized: client_id and/or client_secret are not configured.'
			);
		}

		return new Airalo( self::build_sdk_config( $client_id, $client_secret, $this->environment, $this->language ) );
	}

	public function is_sandbox(): bool {
		return false;
		//return 'sandbox' == $this->environment;
	}

	/**
	 * Verifies a candidate client_id / client_secret pair by attempting to
	 * obtain an access token from the Airalo API. Instantiating the SDK
	 * triggers an OAuth token request, so a successful construction proves
	 * the credentials are valid for the given environment.
	 *
	 * This does NOT touch stored options/credentials, so it is safe to call
	 * before persisting newly submitted values.
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $environment 'production' or 'sandbox'
	 * @param string $language    Accept-Language value to forward to the API
	 * @return array{ok: bool, error: string|null}
	 */
	public static function verify( string $client_id, string $client_secret, string $environment = 'production', string $language = 'en' ): array {
		if ( '' === $client_id || '' === $client_secret ) {
			return [
				'ok'    => false,
				'error' => 'client_id and client_secret are required.',
			];
		}

		try {
			// Constructing the SDK triggers an OAuth token request against the
			// Airalo API; a successful construction is therefore proof that
			// the supplied credentials are valid for the given environment.
			$sdk = new Airalo( self::build_sdk_config( $client_id, $client_secret, $environment, $language ) );

			unset( $sdk );

			return [ 'ok' => true, 'error' => null ];
		} catch ( \Throwable $ex ) {
			return [ 'ok' => false, 'error' => $ex->getMessage() ];
		}
	}

	/**
	 * Builds the configuration array consumed by the Airalo SDK constructor.
	 * Centralised so that getClient() and verify() stay in sync (e.g. when
	 * adding new headers or config keys).
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $environment
	 * @param string $language
	 * @return array<string, mixed>
	 */
	private static function build_sdk_config( string $client_id, string $client_secret, string $environment, string $language ): array {
		return [
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'env'           => $environment,
			'http_headers'  => [
				'woocommerce-plugin: ' . AIRALO_PLUGIN_VERSION,
				'Accept-Language: ' . $language,
			],
		];
	}

	/**
	 * @return array{0:string|false,1:string|false}
	 */
	private function fetch_credentials(): array {
		$credential = new Credential();

		if ( $this->is_sandbox() ) {
			$client_id     = $credential->get_credential( Credential::CLIENT_ID_SANDBOX );
			$client_secret = $credential->get_credential( Credential::CLIENT_SECRET_SANDBOX );
		} else {
			$client_id     = $credential->get_credential( Credential::CLIENT_ID );
			$client_secret = $credential->get_credential( Credential::CLIENT_SECRET );
		}

		return [ $client_id, $client_secret ];
	}
}
