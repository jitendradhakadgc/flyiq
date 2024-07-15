console.log('Ajax Started!');

jQuery(document).ready(function() {
    // Function to handle form submission
    jQuery('#form').on('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        var formData = new FormData(this); // Create a FormData object from the form

        // Function to send form data via AJAX
        function sendFormData() {
            // Add the action to the FormData
            formData.append('action', 'get_sector_background2');

            // Send the form data via AJAX to admin-ajax.php
            jQuery.ajax({
                url: myAjax.ajax_url, // Defined in your WordPress localized script
                type: 'POST',
                data: formData,
                processData: false, // Prevent jQuery from processing the data
                contentType: false, // Prevent jQuery from setting contentType
                success: function(response) {
                    console.log(response); // Handle the response
                },
                error: function(xhr, status, error) {
                    console.error(error); // Handle errors here
                }
            });
        }

        // Call the function to send form data
        sendFormData();
    });
});
