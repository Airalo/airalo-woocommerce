<?php

namespace Airalo\User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MySimsPageBuilder {

    private $translations;
    private $language_texts;

    public function __construct() {
        $language = ( new \Airalo\Admin\Settings\Option() )->fetch_option( \Airalo\Admin\Settings\Option::LANGUAGE );
        $translations = file_get_contents( __DIR__ . '../../../languages/translations.json' );
        $this->translations = json_decode( $translations, true );
        $this->language_texts = $this->translations[$language];

        $this->enqueue_assets();
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'my-esim-page-style', plugin_dir_url( __FILE__ ) . '../../assets/css/myEsimPageStyle.css', [], AIRALO_PLUGIN_VERSION );
        wp_enqueue_script( 'my-esim-page-script', plugin_dir_url( __FILE__ ) . '../airalo-js/my_esim_page.js', [], AIRALO_PLUGIN_VERSION, true );
    }

    /**
     * @param string $text
     * @return string
     */
    private function t( string $text ) {
        return $this->language_texts[$text] ?? $text;
    }

    /**
     * @return string
     */
    public function build_html() {
        $all_orders_details = ( new \Airalo\User\MySimsDetails() )->get_all_user_order_details();

        if ( empty( $all_orders_details ) ) {
            return '<div><p>' . $this->t( 'my.esims.no.esims' ) . '</p></div>';
        }

        $iccid = isset( $_GET['iccid'] ) ? sanitize_text_field( $_GET['iccid'] ) : $all_orders_details[0]['iccid'];

        $esim_list = [];
        $current_url_path = esc_url_raw( strtok( $_SERVER["REQUEST_URI"], '&' ) );

        foreach ( $all_orders_details as $esim ) {
            $sim_name_class = "esim-list-title";

            if ( $iccid == $esim['iccid'] ) {
                $sim_name_class .= " active";
            }

            $sim_name_element = '<p class="' . $sim_name_class . '">' . $esim['sim_name'] . '</p>';

            if ( strpos( $current_url_path, '?' ) === false ) {
                $current_url_path = rtrim( $current_url_path, '/' );
                $iccid_slug = '?iccid=' . $esim['iccid'];
            } else {
                $iccid_slug = '&iccid=' . $esim['iccid'];
            }

            $esim_list[] = '<a class="esim-list-link link-clear" href="' . $current_url_path . $iccid_slug . '">' . $sim_name_element . '</a>';
        }

        return '<div class="my-esim-page-wrapper">
                    <div class="left-menu">
                        <div>
                            <p class="left-menu-title">' . $this->t( 'my.esims.available.esims' ) . '</p>
                            <div class="esims-list" id="esims-list">
                                ' . implode( '', $esim_list ) . '
                            </div>
                        </div>
                    </div>
                    <div class="my-esims-page-content">
                        <ul class="my-esims-page-content-list" id="my-esims-page-content-list">
                            <li>
                                <input type="checkbox" class="my-esims-page-content-list-checkbox" name="usage-content" id="my-esims-page-list-usage" />
                                <label for="my-esims-page-list-usage" class="my-esims-page-list-title">' . $this->t( 'my.esims.usage' ) . '</label>
                                <div class="my-esims-page-list-desc">
                                ' . $this->get_usage_data( $iccid ) . '
                                </div>
                            </li>
                            <li>
                                <input type="checkbox" class="my-esims-page-content-list-checkbox" name="installation-content" id="my-esims-page-list-installation" />
                                <label for="my-esims-page-list-installation" class="my-esims-page-list-title">' . $this->t( 'my.esims.installation' ) . '</label>
                                <div class="my-esims-page-list-desc">
                                    ' . $this->get_qr_and_manual_tabs( $iccid ) . '
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>';
    }

    /**
     * @param string $default_iccid
     * @return string
     */
    private function get_usage_data( string $default_iccid ) {
        $details_client = new \Airalo\User\MySimsDetails();

        $all_orders_details = $details_client->get_all_user_order_details();
        $current_iccid = isset( $_GET['iccid'] ) ? sanitize_text_field( $_GET['iccid'] ) : $default_iccid;

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
                        <p class="trail-title-5">' . $this->t('my.esims.coverage') . '</p>
                        <p class="trail-body-2">' . ( $data_usage_item['sim_name'] ?? null ) . '</p>
                    </div>
                    <div>
                        <p class="trail-title-5">' . $this->t('my.esims.iccid') . '</p>
                        <p class="trail-body-2">' . ( $data_usage_item['iccid'] ?? null ) . '</p>
                    </div>
                    <div>
                        <p class="trail-title-5">' . $this->t('my.esims.package') . '</p>
                        <p class="trail-body-2">' . ( $data_usage_item['coverage'] ?? null ) . '</p>
                    </div>
                </div>
                <div class="data-usage-packages-wrapper">
                    <div class="data-usage-package-title">
                        <p class="trail-title-4">' . $this->t('my.esims.package') . '</p>
                    </div>
                    <div class="data-usage-packages-date-wrapper">
                    <div class="data-usage-package-date-left">
                        <p class="trail-badge trail-title-4 data-usage-package-date-status">' . $data_usage_item['status'] . '</p>
                        <div class="data-usage-package-date">
                            <p class="trail-title-5">' . $this->t('my.esims.date') . '</p>
                            <p class="trail-body-2">' . $data_usage_item['expired_at'] . '</p>
                        </div>
                    </div>
                    <div class="data-usage-package-date-right">
                        <div>
                            <p class="trail-title-5 trail-color-title-highlight">' . $this->t('my.esims.package.valid') . '</p>
                            <p class="trail-body-2">' . implode( ' ', [$validity_and_data[1], $validity_and_data[2]] ) . '</p>
                        </div>
                        <div>
                            <p class="trail-title-5 trail-color-title-highlight">' . $this->t('my.esims.package.data') . '</p>
                            <p class="trail-body-2">' . implode( ' ', [$validity_and_data[3], $validity_and_data[4]] ) . '</p>
                        </div>
                    </div>
                </div>
                    <div class="data-usage-package-remaining-data">
                        <div class="data-usage-package-remaining-data-title">
                            <p class="trail-caption">' . $this->t('my.esims.remaining-data') . '</p>
                            <p class="trail-caption">' . $remaining_data . ' GB</p>
                        </div>
                        <div class="progress-bar">
                            <span class="progress-bar-fill" style="width: ' . $remaining_percentage . '%;"></span>
                        </div>
                    </div>
                </div>';
    }

    /**
     * @param string $type
     * @return string
     */
    private function get_installation_form_content( string $type ) {
        return '<div class="qr-code-right">
                    <div class="qr-code-right-item">
                        <p class="trail-body-3">' . $this->t( 'my.esims.select-platform' ) . '</p>
                        <div class="select-wrapper">
                            <select class="select" name="platform" id="' . $type . '-select-platform" onchange="' . $type . 'CheckPlatform(this.value)">
                                <option value="ios">' . $this->t( 'my.esims.ios-device' ) . '</option>
                                <option value="android">' . $this->t( 'my.esims.android' ) . '</option>
                            </select>
                        </div>
                    </div>
                    <div class="qr-code-right-item none">
                        <p class="trail-body-3">' . $this->t( 'my.esims.select-device' ) . '</p>
                        <div class="select-wrapper">
                            <select class="select" name="device" id="select-device">
                                <option value="ios-17">iOS 17</option>
                                <option value="ios-16">iOS 16</option>
                                <option value="ios-15">iOS 15</option>
                            </select>
                        </div>
                    </div>
                    <div class="qr-code-right-item">
                        <p class="trail-title-4">' . $this->t( 'my.esims.installation-instructions' ) . '</p>
                        <div class="qr-code-installation-instructions">
                            <div class="installation-instruction-card-title">
                                <img alt="airalo-instruction-check" width="24" height="24"  src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjUiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNSAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgaWQ9IkNoYW5nZSB0byI+CjxwYXRoIGlkPSJjb250ZW50IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTEyLjUgMy43NUM3Ljk0MzY1IDMuNzUgNC4yNSA3LjQ0MzY1IDQuMjUgMTJDNC4yNSAxNi41NTYzIDcuOTQzNjUgMjAuMjUgMTIuNSAyMC4yNUMxNy4wNTYzIDIwLjI1IDIwLjc1IDE2LjU1NjMgMjAuNzUgMTJDMjAuNzUgNy40NDM2NSAxNy4wNTYzIDMuNzUgMTIuNSAzLjc1Wk0yLjc1IDEyQzIuNzUgNi42MTUyMiA3LjExNTIyIDIuMjUgMTIuNSAyLjI1QzE3Ljg4NDggMi4yNSAyMi4yNSA2LjYxNTIyIDIyLjI1IDEyQzIyLjI1IDE3LjM4NDggMTcuODg0OCAyMS43NSAxMi41IDIxLjc1QzcuMTE1MjIgMjEuNzUgMi43NSAxNy4zODQ4IDIuNzUgMTJaTTE1LjkzNTkgOS4xMzk3QzE2LjI3MyA5LjM4MDQ2IDE2LjM1MTEgOS44NDg4NyAxNi4xMTAzIDEwLjE4NTlMMTIuMzYwMyAxNS40MzU5QzEyLjIzMjIgMTUuNjE1MyAxMi4wMzE2IDE1LjcyOTMgMTEuODExOSAxNS43NDc0QzExLjU5MjEgMTUuNzY1NiAxMS4zNzU2IDE1LjY4NjIgMTEuMjE5NyAxNS41MzAzTDguOTY5NjcgMTMuMjgwM0M4LjY3Njc4IDEyLjk4NzQgOC42NzY3OCAxMi41MTI2IDguOTY5NjcgMTIuMjE5N0M5LjI2MjU2IDExLjkyNjggOS43Mzc0NCAxMS45MjY4IDEwLjAzMDMgMTIuMjE5N0wxMS42NTQzIDEzLjg0MzZMMTQuODg5NyA5LjMxNDA3QzE1LjEzMDUgOC45NzcwMSAxNS41OTg5IDguODk4OTQgMTUuOTM1OSA5LjEzOTdaIiBmaWxsPSIjMTExOTI4Ii8+CjwvZz4KPC9zdmc+Cg==">
                                <p class="trail-body-2">' . $this->t( 'my.esims.installation.instructions.description' ) . '</p>
                            </div>
                            <div class="installation-instruction-card-content">
                                <ul class="installation-instruction-card-list" id="' . $type . '-installation-instruction-steps"></ul>
                            </div>
                        </div>
                    </div>
                </div>';
    }

    /**
     * @param string $default_iccid
     * @return string
     */
    private function get_qr_and_manual_tabs( string $default_iccid ) {
        $iccid = isset( $_GET['iccid'] ) ? sanitize_text_field( $_GET['iccid'] ) : $default_iccid;
        if ( ! $iccid ) {
            return;
        }

        $language = ( new \Airalo\Admin\Settings\Option() )->fetch_option( \Airalo\Admin\Settings\Option::LANGUAGE );

        try {
            $instructions = new \Airalo\Admin\InstallationInstruction();
            $response = $instructions->handle( $iccid, $language );
        } catch ( \Exception $e ) {
            echo 'Error loading installation instructions. Please try again later.';

            return;
        }

        $ios_data = $response->data->instructions->ios[0];
        $android_data = $response->data->instructions->android[0];

        $localize_data = [
            'iosData' => $ios_data,
            'androidData' => $android_data,
            'iosInstallationQrSteps' => $ios_data->installation_via_qr_code->steps,
            'androidInstallationQrSteps' => $android_data->installation_via_qr_code->steps,
            'iosInstallationManualSteps' => $ios_data->installation_manual->steps,
            'androidInstallationManualSteps' => $android_data->installation_manual->steps,
            'iosQrCodeUrl' => $ios_data->installation_via_qr_code->qr_code_url,
            'androidQrCodeUrl' => $android_data->installation_via_qr_code->qr_code_url,
            'iosManualSMDPAddressAndActivationCode' => $ios_data->installation_manual->smdp_address_and_activation_code,
            'androidManualSMDPAddressAndActivationCode' => $android_data->installation_manual->smdp_address_and_activation_code,
        ];

        wp_localize_script( 'my-esim-page-script', 'myEsimData', $localize_data );

        return '
            <div class="qr-and-manual-wrapper">
                <div class="my-esim-page-tabs">
                    <input type="radio" class="my-esim-page-tabs-radio" name="installation-type" id="my-esim-page-tab-qr" checked>
                    <label for="my-esim-page-tab-qr" class="my-esim-page-tabs_label">' . $this->t('my.esims.installation.instructions.qr-code') . '</label>
                    <div class="my-esim-page-tabs_content">
                        <div class="qr-code-wrapper">
                            <div class="qr-code-second-layer">
                                <div class="qr-code-first-layer">
                                    <img src="https://sandbox.airalo.com/qr?expires=1810056551&id=184759&signature=c81ed50882501d4deb2e5cc19edf40ba2ccf04a5f7f6d7f1d461524c9b7d47ea?test=true" width="170" height="165" id="qrCodeUrl"  alt="qr-code"/>
                                </div>
                            </div>
                            <div class="vertical-divider"></div>
                            ' . $this->get_installation_form_content( "qr" ) . '
                        </div>
                    </div>
                    <input type="radio" class="my-esim-page-tabs-radio" name="installation-type" id="my-esim-page-tab-manual">
                    <label for="my-esim-page-tab-manual" class="my-esim-page-tabs_label">' . $this->t( 'my.esims.installation.instructions.manual' ) . '</label>
                    <div class="my-esim-page-tabs_content">
                        <div class="installation-manual-wrapper">
                            <div class="installation-manual-left-content">
                                <div class="installation-manual-smdp-activation">
                                    <p class="trail-title-5">' . $this->t( 'my.esims.installation.manual.sm.dp.activation' ) . '</p>
                                    <p class="trail-body-2" id="manualSMDPAddressAndActivationCode"></p>
                                </div>
                            </div>
                            <div class="vertical-divider"></div>
                            ' . $this->get_installation_form_content( "manual" ) . '
                        </div>
                    </div>
                </div>
            </div>';
    }
}
