document.addEventListener("DOMContentLoaded", function () {
  var versionSelect = document.getElementById("version-select");
  var installationSelect = document.getElementById("installation-select");
  var stepsDiv = document.getElementById("steps-container");
  var networkStepsDiv = document.getElementById("network-steps-container");
  var qrCodeDiv = document.getElementById("qr-code-container");
  var networkContainer = document.getElementById("network-container");

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function displaySteps() {
    var selectedVersion = versionSelect.value.split("-")[0];
    var selectedMethod = installationSelect.value;

    var steps;
    var networkSteps;
    var qrCodeUrl;
    var apnType;
    var apnValue;
    var isRoaming;

    if (selectedVersion === "ios") {
      steps = Object.values(
        jsonData.data.instructions.ios[0][selectedMethod].steps
      );
      networkSteps = Object.values(
        jsonData.data.instructions.ios[0].network_setup.steps
      );
      qrCodeUrl = jsonData.data.instructions.ios[0][selectedMethod].qr_code_url;
      apnType = jsonData.data.instructions.ios[0].network_setup.apn_type;
      apnValue = jsonData.data.instructions.ios[0].network_setup.apn_value;
      isRoaming = jsonData.data.instructions.ios[0].network_setup.is_roaming;
    } else if (selectedVersion === "android") {
      steps = Object.values(
        jsonData.data.instructions.android[0][selectedMethod].steps
      );
      networkSteps = Object.values(
        jsonData.data.instructions.android[0].network_setup.steps
      );
      qrCodeUrl =
        jsonData.data.instructions.android[0][selectedMethod].qr_code_url;
      apnType = jsonData.data.instructions.android[0].network_setup.apn_type;
      apnValue = jsonData.data.instructions.android[0].network_setup.apn_value;
      isRoaming =
        jsonData.data.instructions.android[0].network_setup.is_roaming;
    }

    var ol = document.createElement("ol");
    for (var i = 0; i < steps.length; i++) {
      var li = document.createElement("li");
      li.textContent = steps[i];
      ol.appendChild(li);
    }

    stepsDiv.innerHTML = "";
    stepsDiv.appendChild(ol);

    var olNetwork = document.createElement("ol");
    for (var i = 0; i < networkSteps.length; i++) {
      var li = document.createElement("li");
      li.textContent = networkSteps[i];
      olNetwork.appendChild(li);
    }
    networkStepsDiv.innerHTML = "";
    networkStepsDiv.appendChild(olNetwork);

    networkContainer.innerHTML = "";
    var apnInfo = document.createElement("p");
    apnInfo.innerHTML = `
      <div class="network-item-details">
        <h3>Data roaming</h3>
        <p>${isRoaming ? "On" : "Off"}</p>
      </div>
      <div class="network-item-details">
        <h3>APN</h3>
        <p>${capitalize(apnType)}</p>
      </div>
    `;
    networkContainer.appendChild(apnInfo);

    if (selectedMethod === "installation_via_qr_code") {
      var img = document.createElement("img");
      img.src = qrCodeUrl;
      img.alt = "QR Code";
      qrCodeDiv.innerHTML = "";
      qrCodeDiv.appendChild(img);
    } else {
      qrCodeDiv.innerHTML = "";
    }
  }

  versionSelect.addEventListener("change", displaySteps);
  installationSelect.addEventListener("change", displaySteps);

  displaySteps();
});
