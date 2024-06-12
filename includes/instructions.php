<?php

require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

// Ensure the file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}


// Load JSON data for the dropdown
$path = __DIR__ . '/sim_instructions.json';
$raw_data = file_get_contents($path);
$json_data = json_decode($raw_data);

$language = $_POST['language'] ?? '';
$iccid = $_POST['iccid'] ?? '';

// Hook form submission
add_action('admin_post_submit_airalo_settings', 'handle_airalo_instructions_form');
add_action('admin_post_nopriv_submit_airalo_settings', 'handle_airalo_instructions_form');

// Handle form submission
function handle_airalo_instructions_form() {
    $iccid = sanitize_text_field($_POST['iccid']);
    $language = sanitize_text_field($_POST['language'] ?? 'en');
    $selectedMethod = $_POST['installation-select'] ?? 'installation_manual';
    // Call the external PHP function here
    $response = call_external_function($iccid, $language);

?>

<style><?php require plugin_dir_path(__FILE__) . '../assets/css/resetStyle.css'; ?></style>
<style><?php require plugin_dir_path(__FILE__) . '../assets/css/instructionsStyle.css'; ?></style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1">

<div id="airalo-container">
    <h1>eSIM Installation</h1>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="submit_airalo_settings">

        <select id="installation-select" name="installation-select">
            <option value='installation_via_qr_code' <?php echo $selectedMethod == 'installation_via_qr_code' ? 'selected' : ''  ?>>Installation via QR Code</option>
            <option value='installation_manual' <?php echo $selectedMethod == 'installation_via_qr_code' ? 'selected' : ''  ?>>Installation Manual</option>
        </select>

        <div class="container">
            <h3>ICCID</h3>
            <input type="text" required name="iccid" class="input-iccid" value="<?php echo esc_attr($iccid); ?>" minlength="1" id="iccid">

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
                foreach ($json_data->data->instructions->ios as $ios) {
                    $version = $ios->version;
                    if (in_array($version, ['15.0,14.0,13.0,12.0', '15.0,14.0.,13.0,12.0'])) {
                        $versionName = 'iOS ≤ 15';
                    } elseif ($version === '16.0') {
                        $versionName = 'iOS 16';
                    } else {
                        $versionName = 'iOS 17';
                    }
                    echo "<option value='ios-" . esc_attr($version) . "'>" . esc_html($versionName) . "</option>";
                }

                foreach ($json_data->data->instructions->android as $android) {
                    $version = $android->version;
                    $model = $android->model;
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

            <div id="qr-code-container"></div>

            <input type="submit" name="get_airalo_instructions" value="Instructions" class="airaloButton"/>
        </div>
    </form>

    <?php
    // Display instructions if response is set
    if (isset($_POST['get_airalo_instructions']) && !empty($response)) {
        $result = update_response($response, $selectedMethod);
        ?>
        <h2 class="step-title">Step 1: Install eSIM</h2>
        <div id="steps-container" class="steps-container">
            <?php
                echo $result['stepsHtml'];
            ?>
        </div>

        <h2 class="step-title">Step 2: Access data</h2>
        <div id="network-steps-container" class="steps-container">
            <?php
            echo $result['networkStepsHtml'];
            ?>
        </div>
        <div id="network-container">
            <?php
            echo $result['qrCodeUrl'];
            ?>
        </div>
        <?php
    }
    ?>
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

function update_response($jsonData,$selectedMethod,$selectedVersion = "ios")
{

    $steps = [];
    $networkSteps = [];
    $qrCodeUrl = '';
    $apnType = '';
    $apnValue = '';
    $isRoaming = false;
    $jsonData = json_decode($jsonData,true);
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
