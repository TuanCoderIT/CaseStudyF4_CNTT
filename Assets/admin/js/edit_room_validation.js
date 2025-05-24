// Edit room validation script
$(document).ready(function () {
  // Extract coordinates from the latlng field if available
  var latlng = $("#latlng").val();
  if (latlng) {
    var coordinates = latlng.split(",");
    if (coordinates.length === 2) {
      var lat = parseFloat(coordinates[0].trim());
      var lng = parseFloat(coordinates[1].trim());

      if (!isNaN(lat) && !isNaN(lng)) {
        $("#lat").val(lat);
        $("#lng").val(lng);
        if (typeof initMap === "function") {
          initMap(lat, lng);
        }
      }
    }
  }

  // Add form validation for coordinates
  $("#roomForm").on("submit", function (e) {
    // Check if latitude and longitude are set
    var latitude = $("#lat").val().trim();
    var longitude = $("#lng").val().trim();

    if (!latitude || !longitude) {
      e.preventDefault(); // Prevent form submission
      $("#location_error")
        .html(
          '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            '<i class="fas fa-exclamation-triangle mr-1"></i>' +
            "<strong>Lỗi!</strong> Vui lòng chọn vị trí trên bản đồ." +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            "</button>" +
            "</div>"
        )
        .show();

      // Scroll to the map
      $("html, body").animate(
        {
          scrollTop: $("#map-container").offset().top - 100,
        },
        200
      );

      return false;
    }

    // Check if the location is within Vinh city using the function from vinh_location_validator.js
    if (!isLocationInVinh(parseFloat(latitude), parseFloat(longitude))) {
      e.preventDefault(); // Prevent form submission

      // Sử dụng hàm showLocationError từ file vinh_location_validator.js
      showLocationError("location_error", true);

      return false;
    }

    return true; // Allow form submission
  });

  // Function to check if coordinates are within Vinh city (15km radius)
  function isLocationInVinh(lat, lng) {
    if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
      return false;
    }

    var vinhLat = 18.6667;
    var vinhLng = 105.6667;
    var R = 6371; // Earth radius in km
    var dLat = ((lat - vinhLat) * Math.PI) / 180;
    var dLon = ((lng - vinhLng) * Math.PI) / 180;
    var a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((vinhLat * Math.PI) / 180) *
        Math.cos((lat * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    var distance = R * c;

    return distance <= 15; // Within 15km of Vinh city center
  }
});
