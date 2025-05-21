// Edit room validation script
$(document).ready(function() {
    // Extract coordinates from the latlng field if available
    var latlng = $('#latlng').val();
    if (latlng) {
        var coordinates = latlng.split(',');
        if (coordinates.length === 2) {
            var lat = parseFloat(coordinates[0].trim());
            var lng = parseFloat(coordinates[1].trim());
            
            if (!isNaN(lat) && !isNaN(lng)) {
                $('#latitude').val(lat);
                $('#longitude').val(lng);
                showMap(lat, lng);
            }
        }
    }
    
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
