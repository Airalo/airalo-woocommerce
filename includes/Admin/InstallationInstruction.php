<?php

namespace Airalo\Admin;

use Airalo\Services\Airalo\AiraloClient;
use Airalo\Admin\Settings\Option;
use Airalo\Airalo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class InstallationInstruction {
	private $airalo_client;

	public function __construct() {
		$this->airalo_client = ( new AiraloClient( new Option() ) )->getClient();
	}


	/**
	 * Fetches sim instructions
	 *
	 * @param mixed $iccid
	 * @param string $language
	 * @return void
	 */
	public function handle( $iccid, $language ) {
		try {

			if ( '' == $language ) {
				$language = 'en';
			}

			$result = $this->airalo_client->getSimInstructions( $iccid, $language );
			return $result;
		} catch ( \Exception $ex ) {
			error_log( $ex->getMessage() );
		}
	}
}
