function generateStepsHtml(steps) {
    var stepsHtml = '';
    Object.entries(steps).map(([key, value]) => {
        stepsHtml += '<li class="installation-instruction-card-list-item">' +
                '<p class="trail-body-2">'+key+'. '+value+'</p>' +
            '</li>';
    });
    return stepsHtml;
}

function manualCheckPlatform(platform) {
    var manualSMDPAddressAndActivationCode = document.getElementById("manualSMDPAddressAndActivationCode");
    var manualInstallationInstructionSteps = document.getElementById("manual-installation-instruction-steps");

    if (platform === "ios") {
        manualSMDPAddressAndActivationCode.innerHTML = iosManualSMDPAddressAndActivationCode;
        manualInstallationInstructionSteps.innerHTML = generateStepsHtml(iosInstallationManualSteps);
    } else {
        manualSMDPAddressAndActivationCode.innerHTML = androidManualSMDPAddressAndActivationCode;
        manualInstallationInstructionSteps.innerHTML = generateStepsHtml(androidInstallationManualSteps);
    }
}

function qrCheckPlatform(platform) {
    var qrCodeUrl = document.getElementById("qrCodeUrl");
    var qrInstallationInstructionSteps = document.getElementById("qr-installation-instruction-steps");
    if (platform === "ios") {
        qrCodeUrl.src = iosQrCodeUrl;
        qrInstallationInstructionSteps.innerHTML = generateStepsHtml(iosInstallationQrSteps);
    }
    else {
        qrCodeUrl.src = androidQrCodeUrl;
        qrInstallationInstructionSteps.innerHTML = generateStepsHtml(androidInstallationQrSteps);
    }
}

function onLoad(platform) {
    qrCheckPlatform(platform);
    manualCheckPlatform(platform);
}

onLoad('ios');