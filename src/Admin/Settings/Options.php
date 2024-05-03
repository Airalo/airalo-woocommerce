<?php

namespace Airalo\Admin\Settings;

class Options {

    const AUTO_PUBLISH = 'airalo_auto_publish';
    const AUTO_PUBLISH_AFTER_UPDATE = 'airalo_auto_publish_after_update';
    const USE_SANDBOX = 'airalo_use_sandbox';

    public function insert_option( string $name, $value ): void {
        update_option( $name, $value );
    }

    public function fetch_option(string $name) {
        return get_option($name);
    }
}