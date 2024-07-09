<?php

namespace Airalo\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Credential {

	const CLIENT_ID = 'airalo_client_id';
	const CLIENT_SECRET = 'airalo_client_secret';

	const CLIENT_ID_SANDBOX = 'airalo_client_id_sandbox';
	const CLIENT_SECRET_SANDBOX= 'airalo_client_secret_sandbox';

	public function insert_credential( string $value, string $name) {
		$method = 'aes-256-ctr';
		$ivlen  = openssl_cipher_iv_length( $method );
		$iv     = openssl_random_pseudo_bytes( $ivlen );

		$key =  AUTH_KEY;
		$salt = SECURE_AUTH_SALT;

		$encrypted_value = openssl_encrypt( $value . $salt, $method, $key, 0, $iv );
		$encoded_value = base64_encode( $iv . $encrypted_value );

		update_option( $name, $encoded_value);
	}

	public function get_credential( string $name): string {
		$credential = get_option($name);
		$decoded_value = base64_decode( $credential, true );

		$method = 'aes-256-ctr';
		$ivlen  = openssl_cipher_iv_length( $method );
		$iv     = substr( $decoded_value, 0, $ivlen );

		$encrypted_value = substr( $decoded_value, $ivlen );

		$key =  AUTH_KEY;
		$salt = SECURE_AUTH_SALT;

		$value = openssl_decrypt( $encrypted_value, $method, $key, 0, $iv );
		if ( ! $value || substr( $value, - strlen( $salt ) ) !== $salt ) {
			return false;
		}

		return substr( $value, 0, - strlen( $salt ) );
	}
}
