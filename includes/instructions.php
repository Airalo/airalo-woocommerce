<html>
    <head>

        <style><?php require plugin_dir_path( __FILE__ ) . '../assets/css/resetStyle.css' ?></style>
        <style><?php require plugin_dir_path( __FILE__ ) . '../assets/css/instructionsStyle.css' ?></style>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1">

    </head>
    <body>

        <div id="airalo-container">
            <?php
                $path = __DIR__ . '/sim_instructions.json';
                $raw_data = file_get_contents($path);
                $json_data = json_decode($raw_data);
            ?>

            <h1>eSIM Installation</h1>

            <select id="installation-select">
                <option value='installation_via_qr_code'>Installation via QR Code</option>
                <option value='installation_manual'>Installation Manual</option>
            </select>

            <div class="container">
                
                <h3>Select device</h3>

                <select id="version-select">
                <?php
                    // equivalent in php for this front-end code
                    // https://github.com/Airalo/airalo-partner-panel-frontend/blob/develop/src/api/transforms/simsTransforms.ts#L72
                    foreach ($json_data->data->instructions->ios as $ios) {
                        $version = $ios->version;
                        if (in_array($version, ['15.0,14.0,13.0,12.0', '15.0,14.0.,13.0,12.0'])) {
                            $versionName = 'iOS ≤ 15';
                        } elseif ($version === '16.0') {
                            $versionName = 'iOS 16';
                        } else {
                            $versionName = 'iOS 17';
                        }
                        echo "<option value='ios-$version'>$versionName</option>";
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
                        echo "<option value='android-$version'>$versionName</option>";
                    }
                ?>
                </select>

                <div id="qr-code-container"></div>

                <h2 class="step-title">Step 1: Install eSIM</h2>
                <div id="steps-container" class="steps-container"></div>

                <h2 class="step-title">Step 2: Access data</h2>
                <div id="network-steps-container" class="steps-container"></div>

            </div>

            <div id="network-container"></div>

            <script>
                var jsonData = <?php echo json_encode($json_data); ?>;
            </script>
            <script>
                <?php require __DIR__ . '/airalo-js/instructions.js' ?>
            </script>
        </div>

    </body>
</html>