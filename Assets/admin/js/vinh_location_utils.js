/**
 * Utility functions for validating Vinh city locations
 * This file works with the haversine.php utility in the backend
 */

// Tọa độ trung tâm thành phố Vinh (sử dụng tọa độ của Đại học Vinh)
const VINH_LAT = 18.65782;
const VINH_LNG = 105.69636;

/**
 * Kiểm tra xem một vị trí có nằm trong thành phố Vinh hay không
 * sử dụng công thức Haversine để tính khoảng cách
 *
 * @param {number} lat - Vĩ độ của điểm cần kiểm tra
 * @param {number} lng - Kinh độ của điểm cần kiểm tra
 * @param {number} radius - Bán kính giới hạn tính từ trung tâm thành phố Vinh (mặc định: 15km)
 * @returns {boolean} - true nếu vị trí nằm trong bán kính của Vinh, false nếu không
 */
function isLocationInVinh(lat, lng, radius = 15) {
  // Kiểm tra đầu vào
  if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
    console.warn("isLocationInVinh: Invalid coordinates provided", lat, lng);
    return false;
  }

  // Hằng số
  const R = 6371; // Bán kính Trái Đất (km)

  // Chuyển đổi độ sang radian
  const dLat = ((lat - VINH_LAT) * Math.PI) / 180;
  const dLon = ((lng - VINH_LNG) * Math.PI) / 180;

  // Công thức Haversine
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((VINH_LAT * Math.PI) / 180) *
      Math.cos((lat * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  const distance = R * c;

  console.log("Khoảng cách đến trung tâm Vinh:", distance.toFixed(2) + "km");

  // Trả về true nếu trong bán kính của Vinh
  return distance <= radius;
}

/**
 * Hiển thị cảnh báo khi vị trí không nằm trong Vinh
 *
 * @param {string} elementId - ID của phần tử HTML để hiển thị cảnh báo
 * @param {boolean} scrollTo - Có cuộn trang đến vị trí cảnh báo hay không
 */
function showLocationError(elementId = "location_error", scrollTo = true) {
  const errorElement = $("#" + elementId);

  errorElement
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

  // Cuộn trang đến vị trí cảnh báo
  if (scrollTo) {
    const mapElement = $("#map").length ? $("#map") : errorElement;
    $("html, body").animate(
      {
        scrollTop: mapElement.offset().top - 100,
      },
      500
    );
  }
}
