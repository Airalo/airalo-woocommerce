<?php

function get_usage_data($defaultIccid) {
    $details_client = new \Airalo\User\MySimsDetails();

    $all_orders_details = $details_client->get_all_user_order_details();
    $current_iccid = $_GET['iccid'] ?? $defaultIccid;

    $data_usage_item = [];

    foreach ( $all_orders_details as $esim ) {
        if ( $esim['iccid'] == $current_iccid ) {
            $data_usage_item = $esim;

            $details_client->append_usage_details_to_iccid( $current_iccid, $data_usage_item );
        }
    }

    if ( empty( $data_usage_item ) ) {
        wp_die( 'You are not authorized to view this page.', 'Forbidden', 403 );
    }

    if ( $data_usage_item['remaining'] > 0 && $data_usage_item['total'] > 0 ) {
        $remaining_data = round( ( $data_usage_item['remaining'] / 1024 ), 2 );
        $remaining_percentage = ( $remaining_data / ( $data_usage_item['total'] / 1024 ) ) * 100;
    } else {
        $remaining_data = 0;
        $remaining_percentage = 0;
    }

    $parts = explode( ' - ', $data_usage_item['sim_name'] );
    $validity_and_data = explode( ' ', $parts[1] );

    return '<div class="usage-wrapper-items">
                <div>
                    <p class="trail-title-5">Coverage</p>
                    <p class="trail-body-2">' . ( $data_usage_item['sim_name'] ?? null ) . '</p>
                </div>
                <div>
                    <p class="trail-title-5">ICCID</p>
                    <p class="trail-body-2">' . ( $data_usage_item['iccid'] ?? null ) . '</p>
                </div>
                <div>
                    <p class="trail-title-5">Package</p>
                    <p class="trail-body-2">' . ( $data_usage_item['coverage'] ?? null ) . '</p>
                </div>
            </div>
            <div class="data-usage-packages-wrapper">
                <div class="data-usage-package-title">
                    <p class="trail-title-4">Package</p>
                </div>
                <div class="data-usage-packages-date-wrapper">
                <div class="data-usage-package-date-left">
                    <p class="trail-badge trail-title-4 data-usage-package-date-status">' . $data_usage_item['status'] . '</p>
                    <div class="data-usage-package-date">
                        <p class="trail-title-5">Date</p>
                        <p class="trail-body-2">' . $data_usage_item['expired_at'] . '</p>
                    </div>
                </div>
                <div class="data-usage-package-date-right">
                    <div>
                        <p class="trail-title-5 trail-color-title-highlight">Valid</p>
                        <p class="trail-body-2">' . implode( ' ', [$validity_and_data[1], $validity_and_data[2]] ) . '</p>
                    </div>
                    <div>
                        <p class="trail-title-5 trail-color-title-highlight">Data</p>
                        <p class="trail-body-2">' . implode( ' ', [$validity_and_data[3], $validity_and_data[4]] ) . '</p>
                    </div>
                </div>
            </div>
                <div class="data-usage-package-remaining-data">
                    <div class="data-usage-package-remaining-data-title">
                        <p class="trail-caption">Remaining data</p>
                        <p class="trail-caption">' . $remaining_data . ' GB</p>
                    </div>
                    <div class="progress-bar">
                        <span class="progress-bar-fill" style="width: ' . $remaining_percentage . '%;"></span>
                    </div>
                </div>
            </div>';
}

function get_installation_form_content($type) {
    return '<div class="qr-code-right">
                <div class="qr-code-right-item">
                    <p class="trail-body-3">Select platform *</p>
                    <div class="select-wrapper">
                        <select class="select" name="platform" id="'.$type.'-select-platform" onchange="'.$type.'CheckPlatform(this.value)">
                            <option value="ios">IOS Device</option>
                            <option value="android">Android</option>
                        </select>
                    </div>
                </div>
                <div class="qr-code-right-item none">
                    <p class="trail-body-3">Select device *</p>
                    <div class="select-wrapper">
                        <select class="select" name="device" id="select-device">
                            <option value="ios-17">iOS 17</option>
                            <option value="ios-16">iOS 16</option>
                            <option value="ios-15">iOS 15</option>
                        </select>
                    </div>
                </div>
                <div class="qr-code-right-item">
                    <p class="trail-title-4">Installation instructions:</p>
                    <div class="qr-code-installation-instructions">
                        <div class="installation-instruction-card-title">
                            <p class="trail-body-2">The validity period starts when the eSIM connects to any supported network/s.</p>
                        </div>
                        <div class="installation-instruction-card-content">
                            <ul class="installation-instruction-card-list" id="' . $type . '-installation-instruction-steps"></ul>
                        </div>
                    </div>
                </div>
            </div>';
}

function get_qr_and_manual_tabs($defaultIccid) {
    //$static_instruction = file_get_contents( __DIR__ . '/instructions.json' );
    //$json_data = json_decode( $static_instruction, true );

    $iccid = $_GET['iccid'] ?? $defaultIccid;
    if ( ! $iccid ) {
        return;
    }

    $language = ( new \Airalo\Admin\Settings\Option() )->fetch_option( \Airalo\Admin\Settings\Option::LANGUAGE );

    try {
      $instructions = new \Airalo\Admin\InstallationInstruction();
      $response = $instructions->handle( $iccid, $language );
    } catch ( Exception $e ) {
      echo 'Error loading installation instructions. Please try again later.';

      return;
    }

    $iosData = $response->data->instructions->ios[0];
    $androidData = $response->data->instructions->android[0];

    $setScriptValues = '<script>
        var iosData = ' . json_encode( $iosData ) . ';
        var androidData = ' . json_encode( $androidData ) . ';
        
        var iosInstallationQrSteps = ' . json_encode( $iosData->installation_via_qr_code->steps ) . ';
        var iosInstallationManualSteps = ' . json_encode( $iosData->installation_manual->steps ) . ';
        var androidInstallationQrSteps = ' . json_encode( $androidData->installation_via_qr_code->steps ) . ';
        var androidInstallationManualSteps = ' . json_encode( $androidData->installation_manual->steps ) . ';
        
        var iosQrCodeUrl = ' . json_encode( $iosData->installation_via_qr_code->qr_code_url ) . ';
        var androidQrCodeUrl = ' . json_encode( $androidData->installation_via_qr_code->qr_code_url ) . ';
        
        var iosManualSMDPAddressAndActivationCode = ' . json_encode( $iosData->installation_manual->smdp_address_and_activation_code ) . ';
        var androidManualSMDPAddressAndActivationCode = ' . json_encode( $androidData->installation_manual->smdp_address_and_activation_code ) . ';
    </script>';

    return $setScriptValues . '
        <div class="qr-and-manual-wrapper">
            <div class="tabs">
                <input type="radio" class="tabs__radio" name="installation-type" id="tab1" checked>
                <label for="tab1" class="tabs__label">QR code</label>
                <div class="tabs__content">
                    <div class="qr-code-wrapper">
                        <div class="qr-code-second-layer">
                            <div class="qr-code-first-layer">
                                <img src="https://sandbox.airalo.com/qr?expires=1810056551&id=184759&signature=c81ed50882501d4deb2e5cc19edf40ba2ccf04a5f7f6d7f1d461524c9b7d47ea?test=true" width="170" height="165" id="qrCodeUrl"  alt="qr-code"/>
                            </div>
                        </div>
                        <div class="vertical-divider"></div>
                        ' . get_installation_form_content("qr") . '
                    </div>
                </div>
                <input type="radio" class="tabs__radio" name="installation-type" id="tab2">
                <label for="tab2" class="tabs__label">Manual</label>
                <div class="tabs__content">
                    <div class="installation-manual-wrapper">
                        <div class="installation-manual-left-content">
                            <div class="installation-manual-smdp-activation">
                                <p class="trail-title-5">SM-DP+ Address and Activation code</p>
                                <p class="trail-body-2" id="manualSMDPAddressAndActivationCode"></p>
                            </div>
                        </div>
                        <div class="vertical-divider"></div>
                        ' . get_installation_form_content("manual") . '
                    </div>
                </div>
            </div>
        </div>';
}

function main() {
    echo '<style>' . esc_attr( file_get_contents(__DIR__ . '/../assets/css/myEsimPageStyle.css') ) . '</style>';
    wp_enqueue_script( 'my-esim-page', plugin_dir_url(__FILE__) . '../includes/airalo-js/my_esim_page.js', array(), '1.0.0', true );

    $all_orders_details = ( new \Airalo\User\MySimsDetails() )->get_all_user_order_details();

    $iccid = $_GET['iccid'] ?? $all_orders_details[0]['iccid'];

    $esim_list = [];
    $current_url_path = strtok( $_SERVER["REQUEST_URI"], '&' );

    foreach ( $all_orders_details as $esim ) {
        //$iccid = $_GET['iccid'];
        $sim_name_class = "esim-list-title";
        if ( $iccid == $esim['iccid'] ) {
            $sim_name_class .= " active";
        }

        $sim_name_element = '<p class="' . $sim_name_class . '">' . $esim['sim_name'] . '</p>';

        $esim_list[] = '<a class="esim-list-link link-clear" href="' . $current_url_path . '&iccid=' . $esim['iccid'] . '">' . $sim_name_element . '</a>';
    }

    return '<div class="wrapper">
                <div class="left-menu">
                    <div>
                        <p class="left-menu-title">Available eSIMs</p>
                        <div class="esims-list">
                            ' . implode('', $esim_list) . '
                        </div>
                    </div>
                </div>
                <div class="content">
                    <ul class="list">
                        <li>
                            <input type="checkbox" class="list-checkbox" id="list-input1" />
                            <label for="list-input1" class="title">Usage</label>
                            <div class="desc">
                            ' . get_usage_data($iccid) . '
                            </div>
                        </li>
                        <li>
                            <input type="checkbox" class="list-checkbox" id="list-input2" />
                            <label for="list-input2" class="title">Installation</label>
                            <div class="desc">
                                ' . get_qr_and_manual_tabs($iccid) . '
                            </div>
                        </li>
                    </ul>
                </div>
            </div>';
}

add_shortcode( 'woocommerce_my_esim', 'main' );
