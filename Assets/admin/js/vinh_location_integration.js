/**
 * Vinh Location Integration for Edit Room Page
 * This file handles location validation and geocoding for the edit room form
 */

$(document).ready(function () {
  // Override form submission to include Vinh location validation
  $("#roomForm").on("submit", function (e) {
    const lat = parseFloat($("#lat").val());
    const lng = parseFloat($("#lng").val());

    // Only validate if coordinates are provided
    if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
      // Use the validation function from vinh_location_validator.js
      if (typeof validateVinhLocation === "function") {
        if (!validateVinhLocation(lat, lng, "location_error")) {
          e.preventDefault();

          // Scroll to error
          const errorElement = $("#location_error");
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
      } else {
        // Fallback to inline validation if validator not loaded
        if (typeof isLocationInVinh === "function") {
          if (!isLocationInVinh(lat, lng)) {
            e.preventDefault();

            $("#location_error")
              .html(
                `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <strong>Lỗi!</strong> Vị trí không nằm trong thành phố Vinh. 
                                Vui lòng chọn lại vị trí trong phạm vi thành phố Vinh.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `
              )
              .show();

            // Scroll to map
            const mapElement = $("#map").length
              ? $("#map")
              : $("#location_error");
            $("html, body").animate(
              {
                scrollTop: mapElement.offset().top - 100,
              },
              500
            );

            return false;
          }
        }
      }
    }

    return true;
  });

  // Enhanced browser location functionality
  $("#get_browser_location").on("click", function () {
    if (!navigator.geolocation) {
      $("#location_error")
        .html(
          `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Trình duyệt không hỗ trợ tính năng lấy vị trí.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `
        )
        .show();
      return;
    }

    const button = $(this);
    button
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Đang tìm vị trí...'
      );

    const successCallback = function (position) {
      button
        .prop("disabled", false)
        .html(
          '<i class="fas fa-crosshairs mr-1"></i> Lấy vị trí từ trình duyệt'
        );

      const lat = position.coords.latitude;
      const lng = position.coords.longitude;

      // Update coordinates
      $("#lat").val(lat);
      $("#lng").val(lng);
      $("#coordinates_display").val("Vĩ độ: " + lat + ", Kinh độ: " + lng);

      // Validate location is in Vinh
      if (typeof isLocationInVinh === "function") {
        if (!isLocationInVinh(lat, lng)) {
          $("#location_error")
            .html(
              `
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Cảnh báo!</strong> Vị trí hiện tại không nằm trong thành phố Vinh. 
                            Vui lòng chọn một vị trí khác trong thành phố Vinh.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `
            )
            .show();
        } else {
          $("#location_error")
            .html(
              `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-1"></i>
                            Đã lấy vị trí thành công! Vị trí nằm trong thành phố Vinh.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `
            )
            .show();
        }
      }

      // Update map if available
      if (
        typeof map !== "undefined" &&
        map &&
        typeof marker !== "undefined" &&
        marker
      ) {
        map.setView([lat, lng], 16);
        marker.setLatLng([lat, lng]);
      } else if (typeof initMap === "function") {
        initMap(lat, lng);
      }
    };

    const errorCallback = function (error) {
      button
        .prop("disabled", false)
        .html(
          '<i class="fas fa-crosshairs mr-1"></i> Lấy vị trí từ trình duyệt'
        );

      let errorMsg = "Không thể lấy vị trí từ trình duyệt.";
      switch (error.code) {
        case error.PERMISSION_DENIED:
          errorMsg += " Bạn đã từ chối cho phép truy cập vị trí.";
          break;
        case error.POSITION_UNAVAILABLE:
          errorMsg += " Thông tin vị trí không khả dụng.";
          break;
        case error.TIMEOUT:
          errorMsg += " Yêu cầu vị trí đã hết thời gian.";
          break;
        default:
          errorMsg += " Đã xảy ra lỗi không xác định.";
          break;
      }

      $("#location_error")
        .html(
          `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    ${errorMsg}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `
        )
        .show();
    };

    const options = {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 60000, // Cache for 1 minute
    };

    navigator.geolocation.getCurrentPosition(
      successCallback,
      errorCallback,
      options
    );
  });
});
