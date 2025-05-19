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
