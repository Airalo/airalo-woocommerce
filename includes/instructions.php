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
$encodedResult = '';
if ( $_GET['action'] === 'airalo_instructions' ) {
    // Render the form and instructions
render_airalo_form( $iccid, $encodedResult );
}


function render_airalo_form($iccid = '', $language = '', $selectedMethod = 'installation_manual', $encodedResult = '') {
    // Load JSON data for the dropdown
    $path = __DIR__ . '/instructions.json';
    $raw_data = file_get_contents($path);
    $json_data = json_decode($raw_data, true);
    $page_id = $_GET['p'] ?? 0;
    $order_id = $_GET['op'] ?? 0;

    $oder_detail_url = home_url('?page_id='.$page_id.'&view-order='.$order_id);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        $selectedMethod = sanitize_text_field($_POST['installation-select'] ?? 'installation_manual');

        // Call the external PHP function here
        $response = call_external_function($iccid, $language);

        if (!empty($response)) {
            $result = update_response($response, $selectedMethod);
            $encodedResult = json_encode($result);
            // Return JSON response for AJAX request
            if (defined('DOING_AJAX') && DOING_AJAX) {
                wp_send_json_success($result);
            }
        }
    }

    // Decode response if available
    $response = $encodedResult ? json_decode($encodedResult, true) : null;

    ?>
    <title>Instructions - WooCommerce </title>
    <style><?php require plugin_dir_path(__FILE__) . '../assets/css/resetStyle.css'; ?></style>
    <style><?php require plugin_dir_path(__FILE__) . '../assets/css/instructionsStyle.css'; ?></style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <div id="airalo-container">
        <h1>eSIM Installation</h1>

        <form id="options.php" method="post">
            <input type="hidden" name="action" value="submit_airalo_settings">

            <select id="installation-select" name="installation-select">
                <option value='installation_via_qr_code' <?php echo $selectedMethod == 'installation_via_qr_code' ? 'selected' : '' ?>>Installation via QR Code</option>
                <option value='installation_manual' <?php echo $selectedMethod == 'installation_manual' ? 'selected' : '' ?>>Installation Manual</option>
            </select>

            <div class="container">
                <h3>ICCID</h3>
                <input type="text" required disabled name="iccid" class="input-iccid" value="<?php echo esc_attr($iccid); ?>" minlength="1" id="iccid">

                <h3>Select Language</h3>
                <select id="language" name="language">
                    <option disabled selected value> -- Select A Language -- </option>
                    <?php
                    foreach (\Airalo\Admin\Settings\Language::get_all_languages() as $key => $value) {
                        $selected = $language == $key ? 'selected' : '';
                        echo "<option value='" . esc_attr($key) . "' " . esc_attr($selected) . ">" . esc_html($value) . "</option>";
                    }
                    ?>
                </select>

                <h3>Select device</h3>
                <select id="version-select" name="device">
                    <?php
                    foreach ($json_data['data']['instructions']['ios'] as $ios) {
                        $version = $ios['version'];
                        if (in_array($version, ['15.0', '14.0', '13.0', '12.0'])) {
                            $versionName = 'iOS ≤ 15';
                        } elseif ($version === '16.0') {
                            $versionName = 'iOS 16';
                        } else {
                            $versionName = 'iOS 17';
                        }
                        echo "<option value='ios-" . esc_attr($version) . "'>" . esc_html($versionName) . "</option>";
                    }

                    foreach ($json_data['data']['instructions']['android'] as $android) {
                        $version = $android['version'];
                        $model = $android['model'];
                        if ($model === 'Galaxy') {
                            $versionName = 'Samsung Galaxy';
                        } elseif ($model === 'Samsung') {
                            $versionName = 'Samsung';
                        } else {
                            $versionName = 'Google Pixel';
                        }
                        echo "<option value='android-" . esc_attr($version) . "'>" . esc_html($versionName) . "</option>";
                    }
                    ?>
                </select>

                <input type="submit" name="get_airalo_instructions" value="Instructions" class="airaloButton"/>
            </div>
        </form>
        <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button airaloButton" href="<?php echo $oder_detail_url;?>">Back</a>
        <div id="instructions-container">
            <?php
            // Display instructions if response is set
            if (!empty($response)) {
                ?>
                <div id="qr-code-container">
                    <?php
                    if (!empty($response) && $selectedMethod == 'installation_via_qr_code') {
                        ?>
                        <img src="<?php echo $response['qrCodeUrl'] ?>" alt="QR Code">
                        <?php
                    }
                    ?>
                </div>

                <h2 class="step-title">Step 1: Install eSIM</h2>
                <div id="steps-container" class="steps-container">
                    <?php print_r($response['stepsHtml']); ?>
                </div>

                <h2 class="step-title">Step 2: Access data</h2>
                <div id="network-steps-container" class="steps-container">
                    <?php echo $response['networkStepsHtml']; ?>
                </div>
                <div id="network-container">
                    <?php echo $response['networkInfoHtml']; ?>
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
        echo 'Exception caught: ' . $e->getMessage();
        return false;
    }
}

function update_response($jsonData, $selectedMethod, $selectedVersion = "ios") {
    $steps = [];
    $networkSteps = [];
    $qrCodeUrl = '';
    $apnType = '';
    $apnValue = '';
    $isRoaming = false;
    $jsonData = json_decode($jsonData, true);

    if ($selectedVersion === "ios") {
        $steps = array_values($jsonData['data']['instructions']['ios'][0][$selectedMethod]['steps']);
        $networkSteps = array_values($jsonData['data']['instructions']['ios'][0]['network_setup']['steps']);
        $qrCodeUrl = $jsonData['data']['instructions']['ios'][0][$selectedMethod]['qr_code_url'];
        $apnType = $jsonData['data']['instructions']['ios'][0]['network_setup']['apn_type'];
        $apnValue = $jsonData['data']['instructions']['ios'][0]['network_setup']['apn_value'];
        $isRoaming = $jsonData['data']['instructions']['ios'][0]['network_setup']['is_roaming'];
    } elseif ($selectedVersion === "android") {
        $steps = array_values($jsonData['data']['instructions']['android'][0][$selectedMethod]['steps']);
        $networkSteps = array_values($jsonData['data']['instructions']['android'][0]['network_setup']['steps']);
        $qrCodeUrl = $jsonData['data']['instructions']['android'][0][$selectedMethod]['qr_code_url'];
        $apnType = $jsonData['data']['instructions']['android'][0]['network_setup']['apn_type'];
        $apnValue = $jsonData['data']['instructions']['android'][0]['network_setup']['apn_value'];
        $isRoaming = $jsonData['data']['instructions']['android'][0]['network_setup']['is_roaming'];
    }

    $stepsHtml = '<ol>';
    foreach ($steps as $step) {
        $stepsHtml .= '<li>' . htmlspecialchars($step) . '</li>';
    }
    $stepsHtml .= '</ol>';

    $networkStepsHtml = '<ol>';
    foreach ($networkSteps as $step) {
        $networkStepsHtml .= '<li>' . htmlspecialchars($step) . '</li>';
    }
    $networkStepsHtml .= '</ol>';

    $networkInfoHtml = '<div class="network-item-details">
        <h3>Data roaming</h3>
        <p>' . (isset($isRoaming) ? "On" : "Off") . '</p>
    </div>
    <div class="network-item-details">
        <h3>APN</h3>
        <p>' . capitalize($apnType) . '</p>
    </div>';

    $output = [
        'stepsHtml' => $stepsHtml,
        'networkStepsHtml' => $networkStepsHtml,
        'networkInfoHtml' => $networkInfoHtml,
        'qrCodeUrl' => $selectedMethod === "installation_via_qr_code" ? $qrCodeUrl : '',
    ];

    return $output;
}

function capitalize($str) {
    return ucwords(strtolower($str));
}
