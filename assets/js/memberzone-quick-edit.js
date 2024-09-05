jQuery(document).ready(function($) {
  $(".memberzone-quick-edit").on("click", function(e) {
    e.preventDefault();

    var $row = $(this).closest("tr");
    var userID = $(this).data("user-id");

    // Open Quick Edit form (for example, show an inline form under the row)
    // For simplicity, you could toggle visibility of an existing form
    $row.find(".quick-edit-row").toggle();

    // Populate the form with existing data
    // Implement AJAX to save data when the user submits the form
  });

  // Handle form submission
  $(document).on("submit", ".memberzone-quick-edit-form", function(e) {
    e.preventDefault();

    var $form = $(this);
    var data = $form.serialize();

    $.post(MemberZoneQuickEdit.ajax_url, data, function(response) {
      if (response.success) {
        // Update the row with new data
        location.reload();
      } else {
        alert(response.data.message);
      }
    });
  });
});
