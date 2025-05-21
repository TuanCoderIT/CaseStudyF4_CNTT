// Validation script for room form
$(document).ready(function() {
    // Add form validation for coordinates
    $('#roomForm').on('submit', function(e) {
        // Check if latitude and longitude are set
        var latitude = $('#latitude').val().trim();
        var longitude = $('#longitude').val().trim();
        
        if (!latitude || !longitude) {
            e.preventDefault(); // Prevent form submission
            $('#coordinatesError').show(); // Show error message
            
            // Scroll to the error message
            $('html, body').animate({
                scrollTop: $('#coordinatesError').offset().top - 100
            }, 200);
            
            // Hide the message after 5 seconds
            setTimeout(function() {
                $('#coordinatesError').fadeOut();
            }, 5000);
            
            return false;
        }
        
        return true; // Allow form submission
    });
});

// Biến toàn cục
let map = null;
let marker = null;
let isManualLocation = false;
let quill = null;

// Khởi tạo Quill editor
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo Quill
    quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Mô tả chi tiết về phòng trọ...',
        modules: {
            toolbar: {
                container: [
                    ['bold', 'italic', 'underline'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['image', 'link']
                ],
                handlers: {
                    image: function() {
                        selectLocalImage();
                    }
                }
            }
        }
    });

    // Xử lý form submit
    document.getElementById('roomForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Ngăn form submit mặc định

        // Kiểm tra các trường bắt buộc
        if (!validateForm()) {
            return false;
        }

        // Cập nhật các giá trị ẩn
        updateHiddenValues();

        // Submit form
        this.submit();
    });

    // Xử lý hiển thị tên file đã chọn
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files.length > 1 
                ? `${this.files.length} files selected` 
                : this.files[0].name;
            this.nextElementSibling.textContent = fileName;
        });
    });

    // Xử lý preview ảnh banner
    document.getElementById('banner_image').addEventListener('change', function() {
        previewImage(this.files[0], 'banner_preview', '.banner-preview');
    });

    // Xử lý preview nhiều ảnh
    document.getElementById('additional_images').addEventListener('change', function() {
        const preview = document.getElementById('additional_images_preview');
        preview.innerHTML = '';

        if (this.files) {
            Array.from(this.files).forEach(file => {
                const div = document.createElement('div');
                div.className = 'col-md-4 mb-2';
                preview.appendChild(div);
                previewImage(file, null, div);
            });
        }
    });

    // Xử lý tiện ích
    document.querySelectorAll('input[name="utility_items[]"]').forEach(input => {
        input.addEventListener('change', updateSelectedUtilitiesDisplay);
    });
    updateSelectedUtilitiesDisplay();

    // Khởi tạo tooltips
    document.querySelectorAll('[data-toggle="tooltip"]').forEach(element => {
        new bootstrap.Tooltip(element);
    });

    // Khởi tạo giá trị mặc định
    initializeDefaultValues();

    // Xử lý sự kiện khi chọn phường/xã
    document.getElementById('ward').addEventListener('change', function() {
        handleWardChange(this);
    });

    // Xử lý sự kiện khi nhập địa chỉ chi tiết
    document.getElementById('address_detail').addEventListener('input', function() {
        updateFullAddressPreview();
    });

    // Xử lý sự kiện khi click nút hiển thị bản đồ
    document.getElementById('show_map_manual').addEventListener('click', function() {
        initMap();
        updateStatus('Hãy click vào vị trí phòng trọ trên bản đồ để chọn tọa độ.', 'info');
    });

    // Xử lý sự kiện khi click nút khôi phục tự động
    document.getElementById('reset_coordinates').addEventListener('click', resetCoordinates);
});

// Hàm kiểm tra form
function validateForm() {
    // Kiểm tra phường/xã
    if (!document.getElementById('ward').value) {
        alert('Vui lòng chọn Phường/Xã');
        return false;
    }

    // Kiểm tra tọa độ
    if (!document.getElementById('latitude').value || !document.getElementById('longitude').value) {
        alert('Vui lòng chọn vị trí trên bản đồ');
        document.getElementById('map_manual_select').style.display = 'block';
        return false;
    }

    // Kiểm tra các trường bắt buộc khác
    const requiredFields = ['title', 'price', 'area', 'address_detail', 'phone', 'category_id'];
    for (const field of requiredFields) {
        if (!document.getElementById(field).value.trim()) {
            alert(`Vui lòng nhập ${document.getElementById(field).getAttribute('placeholder')}`);
            document.getElementById(field).focus();
            return false;
        }
    }

    return true;
}

// Hàm cập nhật các giá trị ẩn
function updateHiddenValues() {
    // Cập nhật ward_name
    const selectedWard = document.getElementById('ward').options[document.getElementById('ward').selectedIndex].text;
    document.getElementById('ward_name').value = selectedWard;

    // Cập nhật district_name và province_name
    document.getElementById('district_name').value = 'Thành phố Vinh';
    document.getElementById('province_name').value = 'Nghệ An';

    // Cập nhật utilities
    const selectedUtilities = Array.from(document.querySelectorAll('input[name="utility_items[]"]:checked'))
        .map(input => input.value);
    document.getElementById('utilities').value = selectedUtilities.join(', ');

    // Cập nhật description
    document.getElementById('description').value = quill.root.innerHTML;
}

// Hàm preview ảnh
function previewImage(file, previewId, container) {
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        if (previewId) {
            document.getElementById(previewId).src = e.target.result;
            document.querySelector(container).style.display = 'block';
        } else {
            container.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded additional-image">`;
        }
    };
    reader.readAsDataURL(file);
}

// Hàm xử lý chọn ảnh local
function selectLocalImage() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;

        if (!/^image\//.test(file.type)) {
            alert('Vui lòng chọn file ảnh hợp lệ');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('image', file);

            const response = await fetch('../api/ckeditor_upload/ckeditor_upload.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.url) {
                insertToEditor(result.url);
            } else {
                throw new Error('Upload failed');
            }
        } catch (error) {
            alert('Lỗi khi tải ảnh: ' + error.message);
        }
    };
}

// Hàm chèn ảnh vào editor
function insertToEditor(url) {
    const range = quill.getSelection();
    quill.insertEmbed(range.index, 'image', url);
}

// Hàm cập nhật hiển thị tiện ích đã chọn
function updateSelectedUtilitiesDisplay() {
    const selected = Array.from(document.querySelectorAll('input[name="utility_items[]"]:checked'))
        .map(input => input.value);

    const displayElement = document.getElementById('selected_utilities');
    if (selected.length > 0) {
        displayElement.innerHTML = `<i class="fas fa-check-circle text-success mr-1"></i> Đã chọn: ${selected.join(', ')}`;
    } else {
        displayElement.innerHTML = '<i class="fas fa-info-circle text-muted mr-1"></i> Chưa có tiện ích nào được chọn';
    }

    document.getElementById('utilities').value = selected.join(', ');
}

// Hàm khởi tạo giá trị mặc định
function initializeDefaultValues() {
    document.getElementById('district_id').value = '1';
    document.getElementById('district_name').value = 'Thành phố Vinh';
    document.getElementById('province_name').value = 'Nghệ An';
    document.getElementById('map_manual_select').style.display = 'block';
}

// Hàm xử lý khi thay đổi phường/xã
function handleWardChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    const wardId = selectedOption.value;
    const wardName = selectedOption.text;

    if (!wardId || !wardName) return;

    document.getElementById('ward').value = wardName;
    document.getElementById('district_id').value = wardId;
    isManualLocation = false;

    if (!document.getElementById('district_name').value) {
        document.getElementById('district_name').value = 'Thành phố Vinh';
    }
    if (!document.getElementById('province_name').value) {
        document.getElementById('province_name').value = 'Nghệ An';
    }

    document.getElementById('map_manual_select').style.display = 'block';
    updateFullAddressPreview();
}

// Hàm khởi tạo bản đồ
function initMap(lat = 18.679585, lng = 105.681335) {
    // Xóa bản đồ cũ nếu có
    if (map) {
        map.remove();
    }

    // Hiển thị bản đồ
    document.getElementById('map').style.display = 'block';
    document.getElementById('map_message').style.display = 'none';

    // Khởi tạo bản đồ mới
    map = L.map('map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Thêm marker
    marker = L.marker([lat, lng], {
        draggable: true
    }).addTo(map)
    .bindPopup('Vị trí của phòng trọ. Kéo để thay đổi vị trí.')
    .openPopup();

    // Xử lý sự kiện kéo thả marker
    marker.on('dragend', function(event) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });

    // Xử lý sự kiện click vào bản đồ
    map.on('click', function(e) {
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, {
                draggable: true
            }).addTo(map);
        }
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });
}

// Hàm cập nhật tọa độ
function updateCoordinates(lat, lng) {
    lat = parseFloat(lat).toFixed(6);
    lng = parseFloat(lng).toFixed(6);
    
    document.getElementById('coordinates_display').value = `${lat}, ${lng}`;
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    
    isManualLocation = true;
    updateStatus('Đã cập nhật tọa độ thủ công!', 'success');
    
    // Lấy địa chỉ từ tọa độ
    reverseGeocode(lat, lng);
}

// Hàm cập nhật trạng thái
function updateStatus(message, type = 'info') {
    const statusDiv = document.getElementById('geocode_status');
    const icon = type === 'success' ? 'check-circle' : 
                type === 'warning' ? 'exclamation-circle' : 
                'spinner fa-spin';
    
    statusDiv.innerHTML = `
        <div class="text-${type}">
            <i class="fas fa-${icon}"></i> ${message}
        </div>
    `;
    
    if (type !== 'info') {
        setTimeout(() => {
            statusDiv.innerHTML = '';
        }, 3000);
    }
}

// Hàm lấy tọa độ từ địa chỉ
async function getCoordinatesFromAddress() {
    // Kiểm tra điều kiện
    const addressDetail = document.getElementById('address_detail').value.trim();
    const wardName = document.getElementById('ward').value;
    
    if (!addressDetail || !wardName) {
        updateStatus('Cần nhập địa chỉ chi tiết và chọn phường/xã để tìm tọa độ.', 'warning');
        return;
    }

    if (isManualLocation) {
        updateStatus('Bạn đã chọn vị trí thủ công. Để sử dụng tọa độ tự động, hãy nhấn "Khôi phục tự động".', 'warning');
        return;
    }

    updateStatus('Đang tự động tìm tọa độ...', 'info');

    // Tạo địa chỉ đầy đủ
    const fullAddress = [
        addressDetail,
        wardName,
        document.getElementById('district_name').value,
        document.getElementById('province_name').value,
        'Vietnam'
    ].filter(Boolean).join(', ');

    try {
        const response = await fetch('../../Admin/api/maps/get_coordinates.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `address=${encodeURIComponent(fullAddress)}`
        });

        const data = await response.json();
        
        if (data.success) {
            document.getElementById('coordinates_display').value = `${data.lat}, ${data.lng}`;
            document.getElementById('latitude').value = data.lat;
            document.getElementById('longitude').value = data.lng;
            
            updateStatus(`Đã tìm tọa độ tự động! Địa chỉ: ${data.formatted_address || 'Không có thông tin'}`, 'success');
            
            document.getElementById('map').style.display = 'block';
            document.getElementById('map_message').style.display = 'none';
            
            initMap(data.lat, data.lng);
        } else {
            throw new Error(data.message || 'Không thể tìm thấy tọa độ');
        }
    } catch (error) {
        console.error('Error:', error);
        updateStatus(`Lỗi: ${error.message}. Vui lòng chọn vị trí thủ công trên bản đồ.`, 'warning');
        document.getElementById('map_manual_select').style.display = 'block';
        initMap();
    }
}

// Hàm lấy địa chỉ từ tọa độ
async function reverseGeocode(lat, lng) {
    updateStatus('Đang tìm thông tin địa chỉ từ tọa độ...', 'info');

    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
            headers: {
                'Accept': 'application/json',
                'Accept-Language': 'vi,en-US;q=0.9,en;q=0.8'
            }
        });

        const data = await response.json();
        
        if (data && data.address) {
            let addressDetail = '';
            if (data.address.road) addressDetail += data.address.road;
            if (data.address.house_number) {
                addressDetail = data.address.house_number + (addressDetail ? ', ' + addressDetail : '');
            }

            if (addressDetail) {
                document.getElementById('address_detail').value = addressDetail;
            }

            updateFullAddressPreview();
            updateStatus('Đã cập nhật thông tin địa chỉ!', 'success');
        }
    } catch (error) {
        console.error('Error:', error);
        updateStatus('Không thể lấy thông tin địa chỉ từ tọa độ.', 'warning');
    }
}

// Hàm cập nhật preview địa chỉ đầy đủ
function updateFullAddressPreview() {
    const addressDetail = document.getElementById('address_detail').value.trim();
    const wardName = document.getElementById('ward').value;
    
    const districtName = document.getElementById('district_name').value || 'Thành phố Vinh';
    const provinceName = document.getElementById('province_name').value || 'Nghệ An';

    const parts = [addressDetail, wardName, districtName, provinceName].filter(Boolean);
    const fullAddress = parts.join(', ');

    const previewElement = document.getElementById('full_address_preview');
    previewElement.innerHTML = fullAddress || '<i class="text-muted">Địa chỉ sẽ hiển thị ở đây sau khi chọn đầy đủ thông tin</i>';

    if (wardName && provinceName && !isManualLocation) {
        getCoordinatesFromAddress();
    }
}

// Hàm khôi phục chế độ tự động
function resetCoordinates() {
    isManualLocation = false;
    document.getElementById('coordinates_display').value = '';
    document.getElementById('latitude').value = '';
    document.getElementById('longitude').value = '';
    updateStatus('Đã khôi phục chế độ tự động tìm tọa độ.', 'info');
    updateFullAddressPreview();
}
