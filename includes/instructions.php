<?php

// Ensure dependencies are loaded
require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';

// Ensure the file is not accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle the custom endpoint
function handle_airalo_instructions_endpoint() {
    if ( get_query_var( 'airalo_instructions', false ) !== false ) {
        require_once plugin_dir_path( __FILE__ ) . 'instructions.php';
        exit;
    }
}
add_action( 'template_redirect', 'handle_airalo_instructions_endpoint' );

$iccid = isset( $_GET['iccid'] ) ? sanitize_text_field( $_GET['iccid'] ) : '';
$encoded_result = '';
if ( $_GET['action'] === 'airalo_instructions' ) {
    // Render the form and instructions
render_airalo_form( $iccid, $encoded_result );
}


function render_airalo_form( $iccid = '', $language = '', $selected_method = 'installation_manual', $encoded_result = '' ) {
    // Load JSON data for the dropdown
    $path = __DIR__ . '/instructions.json';
    $raw_data = file_get_contents( $path );
    $json_data = json_decode( $raw_data, true );
    $page_id = $_GET['p'] ?? 0;
    $order_id = $_GET['op'] ?? 0;

    $order_detail_url = home_url( '?page_id=' . $page_id . '&view-order=' . $order_id );
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        $language = sanitize_text_field( $_POST['language'] ?? 'en' );
        $selected_method = sanitize_text_field( $_POST['installation-select'] ?? 'installation_manual' );

        // Call the external PHP function here
        $response = call_external_function( $iccid, $language );

        if ( ! empty( $response ) ) {

            $result = update_response( $response, $selected_method );
            $encoded_result = wp_json_encode( $result );
            // Return JSON response for AJAX request
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                wp_send_json_success( $result );
            }
        }
    }

    // Decode response if available
    $response = $encoded_result ? json_decode( $encoded_result, true ) : null;

    ?>
    <title>Instructions - WooCommerce </title>
    <style><?php require plugin_dir_path( __FILE__ ) . '../assets/css/resetStyle.css'; ?></style>
    <style><?php require plugin_dir_path( __FILE__ ) . '../assets/css/instructionsStyle.css'; ?></style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <div id="airalo-container">
        <h1>eSIM Installation</h1>

        <form id="options.php" method="post">
            <input type="hidden" name="action" value="submit_airalo_settings">

            <select id="installation-select" name="installation-select">
                <option value='installation_via_qr_code' <?php echo $selected_method == 'installation_via_qr_code' ? 'selected' : ''; ?>>Installation via QR Code</option>
                <option value='installation_manual' <?php echo $selected_method == 'installation_manual' ? 'selected' : ''; ?>>Installation Manual</option>
            </select>

            <div class="container">
                <h3>ICCID</h3>
                <input type="text" required disabled name="iccid" class="input-iccid" value="<?php echo esc_attr( $iccid ); ?>" minlength="1" id="iccid">

                <h3>Select Language</h3>
                <select id="language" name="language">
                    <option disabled selected value> -- Select A Language -- </option>
                    <?php
                    foreach ( \Airalo\Admin\Settings\Language::get_all_languages() as $key => $value ) {
                        $selected = $language == $key ? 'selected' : '';
                        echo "<option value='" . esc_attr( $key ) . "' " . esc_attr( $selected ) . ">" . esc_html( $value ) . "</option>";
                    }
                    ?>
                </select>

                <h3>Select device</h3>
                <select id="version-select" name="device">
                    <?php
                    foreach ($json_data['data']['instructions']['ios'] as $ios) {
                        $version = $ios['version'];
                        if (in_array($version, ['15.0', '14.0', '13.0', '12.0'])) {
                            $version_name = 'iOS ≤ 15';
                        } elseif ($version === '16.0') {
                            $version_name = 'iOS 16';
                        } else {
                            $version_name = 'iOS 17';
                        }
                        echo "<option value='ios-" . esc_attr( $version ) . "'>" . esc_html( $version_name ) . "</option>";
                    }

                    foreach ( $json_data['data']['instructions']['android'] as $android ) {
                        $version = $android['version'];
                        $model = $android['model'];
                        if ($model === 'Galaxy') {
                            $version_name = 'Samsung Galaxy';
                        } elseif ($model === 'Samsung') {
                            $version_name = 'Samsung';
                        } else {
                            $version_name = 'Google Pixel';
                        }
                        echo "<option value='android-" . esc_attr( $version ) . "'>" . esc_html( $version_name ) . "</option>";
                    }
                    ?>
                </select>

                <input type="submit" name="get_airalo_instructions" value="Instructions" class="airaloButton"/>
            </div>
        </form>
        <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button airaloButton" href="<?php echo esc_html( $order_detail_url );?>">Back</a>
        <div id="instructions-container">
            <?php
            // Display instructions if response is set
            if (!empty($response)) {
                ?>
                <div id="qr-code-container">
                    <?php
                    if (!empty($response) && $selected_method == 'installation_via_qr_code') {
                        ?>
                        <img src="<?php echo esc_html( $response['qrCodeUrl'] ); ?>" alt="QR Code">
                        <?php
                    }
                    ?>
                </div>

                <h2 class="step-title">Step 1: Install eSIM</h2>
                <div id="steps-container" class="steps-container">
                    <?php print_r( $response['stepsHtml'] ); ?>
                </div>

                <h2 class="step-title">Step 2: Access data</h2>
                <div id="network-steps-container" class="steps-container">
                    <?php print_r( $response['networkStepsHtml'] ); ?>
                </div>
                <div id="network-container">
                    <?php print_r( $response['networkInfoHtml'] ); ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}

// Define the external function call
function call_external_function($iccid, $language) {
    try {
        $instructions = new \Airalo\Admin\InstallationInstruction();
        $response = $instructions->handle($iccid, $language);
        return $response;
    } catch (Exception $e) {
        echo 'Exception caught: ' . esc_html( $e->getMessage() );
        return false;
    }
}

function update_response($json_data, $selected_method, $selected_version = "ios") {
    $steps = [];
    $network_steps = [];
    $qr_code_url = '';
    $apn_type = '';
    $apn_value = '';
    $is_roaming = false;
    $json_data = json_decode( $json_data, true );

    if ($selected_version === "ios") {
        $steps = array_values( $json_data['data']['instructions']['ios'][0][$selected_method]['steps'] );
        $network_steps = array_values( $json_data['data']['instructions']['ios'][0]['network_setup']['steps'] );
        $qr_code_url = $json_data['data']['instructions']['ios'][0][$selected_method]['qr_code_url'];
        $apn_type = $json_data['data']['instructions']['ios'][0]['network_setup']['apn_type'];
        $apn_value = $json_data['data']['instructions']['ios'][0]['network_setup']['apn_value'];
        $is_roaming = $json_data['data']['instructions']['ios'][0]['network_setup']['is_roaming'];
    } elseif ($selected_version === "android") {
        $steps = array_values( $json_data['data']['instructions']['android'][0][$selected_method]['steps'] );
        $network_steps = array_values( $json_data['data']['instructions']['android'][0]['network_setup']['steps'] );
        $qr_code_url = $json_data['data']['instructions']['android'][0][$selected_method]['qr_code_url'];
        $apn_type = $json_data['data']['instructions']['android'][0]['network_setup']['apn_type'];
        $apn_value = $json_data['data']['instructions']['android'][0]['network_setup']['apn_value'];
        $is_roaming = $json_data['data']['instructions']['android'][0]['network_setup']['is_roaming'];
    }

    $steps_html = '<ol>';
    foreach ( $steps as $step ) {
        $steps_html .= '<li>' . htmlspecialchars( $step, ENT_QUOTES ) . '</li>';
    }
    $steps_html .= '</ol>';

    $network_steps_html = '<ol>';
    foreach ( $network_steps as $step ) {
        $network_steps_html .= '<li>' . htmlspecialchars( $step, ENT_QUOTES ) . '</li>';
    }
    $network_steps_html .= '</ol>';

    $network_info_html = '<div class="network-item-details">
        <h3>Data roaming</h3>
        <p>' . ( isset( $is_roaming ) ? "On" : "Off" ) . '</p>
    </div>
    <div class="network-item-details">
        <h3>APN</h3>
        <p>' . capitalize ($apn_type ) . '</p>
    </div>';

    $output = [
        'stepsHtml' => $steps_html,
        'networkStepsHtml' => $network_steps_html,
        'networkInfoHtml' => $network_info_html,
        'qrCodeUrl' => $selected_method === "installation_via_qr_code" ? $qr_code_url : '',
    ];

    return $output;
}

function capitalize( $str ) {
    return ucwords( strtolower( $str ) );
}
