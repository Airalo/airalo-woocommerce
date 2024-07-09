<?php

namespace Airalo\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Option {

	const AUTO_PUBLISH = 'airalo_auto_publish';
	const AUTO_PUBLISH_AFTER_UPDATE = 'airalo_auto_publish_after_update';
	const USE_SANDBOX = 'airalo_use_sandbox';
	const SYNC_IMAGES = 'airalo_sync_images';

	const LAST_SYNC = 'airalo_last_sync';
	const LAST_SUCCESSFUL_SYNC = 'airalo_last_successful_sync';
	const SYNC_ERROR = 'airalo_sync_error';

	const ENVIRONMENT_SWITCHED = 'airalo_environment_switched';

	const ENABLED = 'on';
	const DISABLED = 'off';

	const LANGUAGE = 'airalo_language';

	const USE_AIRALO_SIM_NAME = 'use_airalo_sim_name';

	public function insert_option( string $name, $value ): void {
		update_option( $name, $value );
	}

	public function fetch_option( string $name ) {
		return get_option($name);
	}

	public function fetch_option_for_settings_page( string $name ): string {
		return $this->fetch_option($name)  == 'on' ? 'checked' : '';
	}

	public function get_environment(): string {
		return $this->fetch_option( self::USE_SANDBOX ) == self::ENABLED ? 'sandbox' : 'production';
	}
}
