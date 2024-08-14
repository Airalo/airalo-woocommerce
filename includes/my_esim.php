<?php

function getUsageData() {
    $mockJson = file_get_contents('my_esims_mock.json', FILE_USE_INCLUDE_PATH);
    $result = json_decode($mockJson);
    $dataUsageItem = [];
    $currentICCID = $_GET["iccid"];

    foreach ($result->data as $esim) {
        if ($esim->iccid == $currentICCID) {
            $dataUsageItem = $esim;
        }
    }

    return '<div class="usage-wrapper-items">
                <div>
                  <p class="trail-title-5">Coverage</p>
                  <p class="trail-body-2">'.$dataUsageItem->sim_name.'</p>
                </div>
                <div>
                  <p class="trail-title-5">ICCID</p>
                  <p class="trail-body-2">'.$dataUsageItem->iccid.'</p>
                </div>
                <div>
                  <p class="trail-title-5">Package</p>
                  <p class="trail-body-2">'.$dataUsageItem->coverage.'</p>
                </div>
              </div>';
}

function getInstallationFormContent() {
    return '<div class="qr-code-right">
              <div class="qr-code-right-item">
                <p class="trail-body-3">Select platform *</p>
                <div class="select-wrapper">
                  <select class="select" name="platform" id="select-platform">
                    <option value="ios">IOS Device</option>
                    <option value="android">Android</option>
                  </select>
                </div>
              </div>
              <div class="qr-code-right-item">
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
                    <span>Step 1: Install eSIM</p>
                  </div>
                </div>
              </div>
          </div>';
}

function getDataUsagePackageItem($status, $date, $valid, $data) {
    return '<div class="data-usage-packages-date-wrapper">
                <div class="data-usage-package-date-left">
                  <p class="trail-badge trail-title-4 data-usage-package-date-status">'.$status.'</p>
                  <div class="data-usage-package-date">
                    <p class="trail-title-5">Date</p>
                    <p class="trail-body-2">'.$date.'</p>
                  </div>
                </div>
                <div class="data-usage-package-date-right">
                  <div>
                    <p class="trail-title-5 trail-color-title-highlight">Valid</p>
                    <p class="trail-body-2">'.$valid.'</p>
                  </div>
                  <div>
                    <p class="trail-title-5 trail-color-title-highlight">Data</p>
                    <p class="trail-body-2">'.$data.'</p>
                  </div>
                </div>
              </div>';
}

function getActivePackages() {
    $status = 'Expiry';
    $date = '21 Jun 2023 / 16:18 (GMT)';
    $valid = '7 days';
    $data = '1 GB';

    return '<div class="data-usage-packages-wrapper">
              <div class="data-usage-package-title">
                <p class="trail-title-4">Active package</p>
                <p class="data-usage-package-title-badge trail-badge trail-title-4">Active</p>
              </div>
              '.getDataUsagePackageItem($status, $date, $valid, $data).'
              <div class="data-usage-package-remaining-data">
                <div class="data-usage-package-remaining-data-title">
                  <p class="trail-caption">Remaining data</p>
                  <p class="trail-caption">0.5 GB</p>
                </div>
                <div class="progress-bar">
                    <span class="progress-bar-fill" style="width: 50%;"></span>
                </div>
              </div>
            </div>';
}

function getPreviousPackages() {
    $packagesElement = [];
    $packages = [
        ['status' => 'Expired', 'date' => '21 May 2024 / 16:18 (GMT)', 'valid' => '10 days', 'data' => '20 GB'],
        ['status' => 'Expired', 'date' => '7 Jun 2023 / 16:18 (GMT)', 'valid' => '30 days', 'data' => '50 GB'],
        ['status' => 'Expired', 'date' => '31 May 2023 / 16:18 (GMT)', 'valid' => '7 days', 'data' => '12 GB'],
    ];
    foreach ($packages as $package) {
        $packagesElement[] = getDataUsagePackageItem($package['status'], $package['date'], $package['valid'], $package['data']);
    }
    return '<div class="content">
                <ul class="list">
                  <li>
                    <input type="checkbox" class="list-checkbox" id="list-input-previous-packages" />
                    <label for="list-input-previous-packages" class="title data-usage-previous-packages-title">Previous packages</label>
                    <div class="desc data-usage-previous-packages-desc">
                        <div class="data-usage-packages-wrapper">
                            '.implode('', $packagesElement).'
                        </div>
                    </div>
                  </li>
                </ul>
              </div>';
}

function getQrAndManualTabs() {
    $manualSMDPAddress = "h3a.prod.ondemandconnectivity.com";
    $manualActivationCode = "73F48817E08D21A023283E2B1677934C6071C31DF9873A91A562294A0F8471FF";

    return '
        <div class="qr-and-manual-wrapper">
            <div class="tabs">
              <input type="radio" class="tabs__radio" name="tabs-example" id="tab1" checked>
              <label for="tab1" class="tabs__label">QR code</label>
              <div class="tabs__content">
                <div class="qr-code-wrapper">
                    <div class="qr-code-second-layer">
                      <div class="qr-code-first-layer">
                        <img src="https://www.svgrepo.com/show/194568/qr-code.svg" width="170" height="165" />
                      </div>
                    </div>
                    <div class="vertical-divider"></div>
                    '.getInstallationFormContent().'
                </div>
              </div>
              <input type="radio" class="tabs__radio" name="tabs-example" id="tab2">
              <label for="tab2" class="tabs__label">Manual</label>
              <div class="tabs__content">
                <div class="installation-manual-wrapper">
                    <div class="installation-manual-left-content">
                        <div>
                            <p class="trail-title-5">SM-DP+ Address</p>
                            <p class="trail-body-2">'.$manualSMDPAddress.'</p>
                        </div>
                        <div>
                            <p class="trail-title-5">Activation code</p>
                            <p class="trail-body-2">'.$manualActivationCode.'</p>
                        </div>
                    </div>
                    <div class="vertical-divider"></div>
                    '.getInstallationFormContent().'
                </div>
              </div>
            </div>
        </div>';
}

function my_esim_page() {
    echo '<style>' . esc_attr( file_get_contents( __DIR__ . '/../assets/css/myEsimPage.css' ) ) . '</style>';

    $mockJson = file_get_contents('my_esims_mock.json', FILE_USE_INCLUDE_PATH);
    $result = json_decode($mockJson);

    $esimList = [];
    $currentICCID = $_GET["iccid"];
    $currentURLPath = strtok($_SERVER["REQUEST_URI"], '&');
    foreach ($result->data as $esim) {
        $simNameClass = "esim-list-title";
        if ($esim->iccid == $currentICCID) {
            $simNameClass .= ' active';
        }
        $simNameElement = '<p class="'.$simNameClass.'">'.$esim->sim_name.'</p>';
        $esimList[] = '<a class="esim-list-link link-clear" href="'.$currentURLPath.'&iccid='.$esim->iccid.'">'.$simNameElement.'</a>';
    }

    return '<div class="wrapper">
              <div class="left-menu">
                <div>
                  <p class="left-menu-title">Available eSIMs</p>
                  <div class="esims-list">
                    '.implode('', $esimList).'
                  </div>
                </div>
              </div>
              <div class="content">
                <ul class="list">
                  <li>
                    <input type="checkbox" class="list-checkbox" id="list-input1" />
                    <label for="list-input1" class="title">Usage</label>
                    <div class="desc">
                    '.getUsageData().'
                    '.getActivePackages().'
                    '.getPreviousPackages().'
                    </div>
                  </li>
                  <li>
                    <input type="checkbox" class="list-checkbox" id="list-input2" />
                    <label for="list-input2" class="title">Installation</label>
                    <div class="desc">
                        '.getQrAndManualTabs().'
                    </div>
                  </li>
                  <li>
                    <input type="checkbox" class="list-checkbox" id="list-input3" />
                    <label for="list-input3" class="title">Top-up</label>
                    <div class="desc">
                      <div>
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eveniet
                        aperiam autem neque nulla, explicabo pariatur quas, quis facere
                        magnam inventore praesentium temporibus laudantium. Iure illo saepe
                        earum at labore. Ducimus?
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>';

}

add_shortcode( 'woocommerce_my_esim', 'my_esim_page' );

?>