jQuery(document).ready(function($) {
    $("#memberzone-registration-form").on("submit", function(e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = {
            action: "memberzone_register", // Action defined in the PHP file
            nonce: memberzone_ajax.nonce, // The nonce for security
            username: $('input[name="username"]').val(),
            email: $('input[name="email"]').val(),
            password: $('input[name="password"]').val(),
        };

        $.ajax({
            type: "POST",
            url: memberzone_ajax.ajax_url, // The AJAX URL defined in PHP
            data: formData,
            dataType: "json",
            beforeSend: function() {
                // Optionally, show a loading spinner or message
                $("#memberzone-registration-form")
                    .find('input[type="submit"]')
                    .attr("disabled", "disabled")
                    .val("Registering...");
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data); // Display success message
                    $("#memberzone-registration-form")[0].reset(); // Reset the form
                } else {
                    alert(response.data); // Display error message
                }
            },
            error: function(xhr, status, error) {
                alert("An error occurred: " + error); // Handle AJAX errors
            },
            complete: function() {
                // Optionally, hide the loading spinner or message
                $("#memberzone-registration-form")
                    .find('input[type="submit"]')
                    .removeAttr("disabled")
                    .val("Register");
            },
        });
    });
});
