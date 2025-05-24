/**
 * Vinh Location Validator - Form validation for Vinh city locations
 * This file works with vinh_location_utils.js for location validation
 */

/**
 * Validates location inputs and shows appropriate messages
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {string} errorElementId - ID of error display element
 * @returns {boolean} - true if location is valid for Vinh city
 */
function validateVinhLocation(lat, lng, errorElementId = "location_error") {
  // Clear previous errors
  $("#" + errorElementId)
    .empty()
    .hide();

  // Check if coordinates are provided
  if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
    showValidationError("Vui lòng cung cấp tọa độ hợp lệ.", errorElementId);
    return false;
  }

  // Check if location is within Vinh city using utility function
  if (typeof isLocationInVinh === "function") {
    if (!isLocationInVinh(lat, lng)) {
      showLocationError(errorElementId, true);
      return false;
    } else {
      showValidationSuccess(
        "Vị trí hợp lệ trong thành phố Vinh.",
        errorElementId
      );
      return true;
    }
  } else {
    console.error(
      "isLocationInVinh function not found. Please include vinh_location_utils.js"
    );
    showValidationError(
      "Lỗi hệ thống: Không thể kiểm tra vị trí.",
      errorElementId
    );
    return false;
  }
}

/**
 * Shows validation error message
 * @param {string} message - Error message to display
 * @param {string} elementId - ID of element to show error in
 */
function showValidationError(message, elementId = "location_error") {
  const errorElement = $("#" + elementId);

  errorElement
    .html(
      `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Lỗi!</strong> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `
    )
    .show();
}

/**
 * Shows validation success message
 * @param {string} message - Success message to display
 * @param {string} elementId - ID of element to show message in
 */
function showValidationSuccess(message, elementId = "location_error") {
  const errorElement = $("#" + elementId);

  errorElement
    .html(
      `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `
    )
    .show();
}

/**
 * Validates form before submission
 * @param {string} latInputId - ID of latitude input field
 * @param {string} lngInputId - ID of longitude input field
 * @param {string} errorElementId - ID of error display element
 * @returns {boolean} - true if validation passes
 */
function validateFormLocation(
  latInputId = "lat",
  lngInputId = "lng",
  errorElementId = "location_error"
) {
  const lat = parseFloat($("#" + latInputId).val());
  const lng = parseFloat($("#" + lngInputId).val());

  return validateVinhLocation(lat, lng, errorElementId);
}

/**
 * Sets up form validation on submit
 * @param {string} formId - ID of the form to validate
 * @param {string} latInputId - ID of latitude input
 * @param {string} lngInputId - ID of longitude input
 * @param {string} errorElementId - ID of error display element
 */
function setupFormValidation(
  formId = "room_form",
  latInputId = "lat",
  lngInputId = "lng",
  errorElementId = "location_error"
) {
  $("#" + formId).on("submit", function (e) {
    const lat = parseFloat($("#" + latInputId).val());
    const lng = parseFloat($("#" + lngInputId).val());

    // Only validate if coordinates are provided
    if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
      if (!validateVinhLocation(lat, lng, errorElementId)) {
        e.preventDefault(); // Stop form submission

        // Scroll to error message
        const errorElement = $("#" + errorElementId);
        if (errorElement.length) {
          $("html, body").animate(
            {
              scrollTop: errorElement.offset().top - 100,
            },
            500
          );
        }

        return false;
      }
    }

    return true;
  });
}

// Auto-setup when document is ready
$(document).ready(function () {
  // Check if we're on a room form page
  if ($("#room_form").length || $("#lat").length || $("#lng").length) {
    setupFormValidation();
  }
});
