<?php
// Test script for reverse geocoding functionality
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Reverse Geocoding</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <h1>Test Reverse Geocoding</h1>

    <div>
        <h3>Test Coordinates:</h3>
        <p>Vinh City Center: <button onclick="testCoordinates(18.6667, 105.6667)">Test (18.6667, 105.6667)</button></p>
        <p>Ben Thuy District: <button onclick="testCoordinates(18.6825, 105.6951)">Test (18.6825, 105.6951)</button></p>
        <p>Hung Dung Ward: <button onclick="testCoordinates(18.6745, 105.6892)">Test (18.6745, 105.6892)</button></p>
        <p>Quang Trung Ward: <button onclick="testCoordinates(18.67089, 105.67216)">Test (18.67089, 105.67216)</button></p>
        <p>Le Loi Street: <button onclick="testCoordinates(18.663333, 105.666667)">Test (18.663333, 105.666667)</button></p>

        <div style="margin-top: 20px;">
            <h4>Custom Test:</h4>
            <input type="number" id="custom_lat" placeholder="Latitude" step="any" style="width: 120px; margin-right: 10px;">
            <input type="number" id="custom_lng" placeholder="Longitude" step="any" style="width: 120px; margin-right: 10px;">
            <button onclick="testCustomCoordinates()">Test Custom</button>
        </div>
    </div>

    <div id="results" style="margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
        <h4>Results will appear here...</h4>
    </div>

    <script>
        function normalizeText(text) {
            return text.toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '') // Remove accents
                .replace(/[đĐ]/g, 'd') // Replace đ with d
                .trim();
        }

        function testCoordinates(lat, lng) {
            $('#results').html('<h4>Testing coordinates: ' + lat + ', ' + lng + '</h4><p>Loading...</p>');

            $.ajax({
                url: '../api/location/reverse-here.php',
                method: 'POST',
                data: {
                    lat: lat,
                    lng: lng
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Full response:', response);

                    let html = '<h4>Results for: ' + lat + ', ' + lng + '</h4>';

                    if (response.success && response.raw) {
                        const addressData = response.raw.address;

                        html += '<h5>Address Components:</h5>';
                        html += '<ul>';
                        html += '<li><strong>Label:</strong> ' + (addressData.label || 'N/A') + '</li>';
                        html += '<li><strong>House Number:</strong> ' + (addressData.houseNumber || 'N/A') + '</li>';
                        html += '<li><strong>Street:</strong> ' + (addressData.street || 'N/A') + '</li>';
                        html += '<li><strong>District:</strong> ' + (addressData.district || 'N/A') + '</li>';
                        html += '<li><strong>City District:</strong> ' + (addressData.cityDistrict || 'N/A') + '</li>';
                        html += '<li><strong>Subdistrict:</strong> ' + (addressData.subdistrict || 'N/A') + '</li>';
                        html += '<li><strong>City:</strong> ' + (addressData.city || 'N/A') + '</li>';
                        html += '<li><strong>County:</strong> ' + (addressData.county || 'N/A') + '</li>';
                        html += '<li><strong>State:</strong> ' + (addressData.state || 'N/A') + '</li>';
                        html += '<li><strong>Country:</strong> ' + (addressData.country || 'N/A') + '</li>';
                        html += '</ul>';

                        // Test district extraction logic
                        let districtName = addressData.district || addressData.cityDistrict || addressData.subdistrict;
                        if (!districtName && addressData.label) {
                            const labelParts = addressData.label.split(',').map(part => part.trim());
                            for (let part of labelParts) {
                                if (part.match(/^(Phường|Xã|Thị trấn|Quận|Huyện)\s+/iu)) {
                                    districtName = part;
                                    break;
                                }
                            }
                        }

                        if (districtName) {
                            html += '<h5>District Matching Test:</h5>';
                            const cleanDistrictName = districtName.replace(/^(Phường|Xã|Thị trấn|Quận|Huyện)\s+/iu, '').trim();
                            const normalizedDistrictName = normalizeText(cleanDistrictName);

                            html += '<p><strong>Original:</strong> ' + districtName + '</p>';
                            html += '<p><strong>Cleaned:</strong> ' + cleanDistrictName + '</p>';
                            html += '<p><strong>Normalized:</strong> ' + normalizedDistrictName + '</p>';
                        }

                        html += '<h5>Street Address Construction:</h5>';
                        let streetAddress = '';
                        if (addressData.houseNumber) {
                            streetAddress += addressData.houseNumber + ' ';
                        }
                        if (addressData.street) {
                            streetAddress += addressData.street;
                        } else if (addressData.label) {
                            const labelParts = addressData.label.split(',');
                            streetAddress = labelParts[0].trim();
                        }
                        html += '<p><strong>Constructed Street Address:</strong> ' + (streetAddress || 'N/A') + '</p>';

                        html += '<h5>Raw Response:</h5>';
                        html += '<pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; max-height: 300px; overflow-y: auto;">' + JSON.stringify(response, null, 2) + '</pre>';
                    } else {
                        html += '<p style="color: red;">Error: ' + (response.message || 'Unknown error') + '</p>';
                    }

                    $('#results').html(html);
                },
                error: function(xhr, status, error) {
                    $('#results').html('<h4>Error</h4><p style="color: red;">AJAX Error: ' + error + '</p><p>Status: ' + status + '</p>');
                }
            });
        }

        function testCustomCoordinates() {
            const lat = parseFloat($('#custom_lat').val());
            const lng = parseFloat($('#custom_lng').val());

            if (isNaN(lat) || isNaN(lng)) {
                alert('Please enter valid latitude and longitude values');
                return;
            }

            testCoordinates(lat, lng);
        }
    </script>
</body>

</html>