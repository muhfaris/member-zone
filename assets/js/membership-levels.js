jQuery(document).ready(function($) {
  // Monitor form changes
  let formChanged = false;
  $(
    'select[name="bulk_action"], select[name="bulk_status"], input[name="bulk_duration"], select[name="bulk_role"]',
  ).change(function() {
    formChanged = true;
  });

  function showLoading() {
    $("#loading-overlay").show();
  }

  function hideLoading() {
    $("#loading-overlay").hide();
  }

  $("form").on("submit", function() {
    showLoading();
  });

  $("#doaction").on("click", function() {
    // If no changes were made, reset bulk_action to empty
    if (!formChanged) {
      $('select[name="bulk_action"]').val("-1");
      $('select[name="bulk_status"]').val("");
      $('input[name="bulk_duration"]').val("");
      $('select[name="bulk_role"]').val("");
    }
    showLoading();
  });

  $("#bulk-action-selector-top, #bulk-action-selector-bottom").on(
    "change",
    function() {
      var selected = $(this).val();
      $(
        'select[name="bulk_status"], input[name="bulk_duration"], select[name="bulk_role"]',
      ).hide();
      if (selected === "update_status") {
        $('select[name="bulk_status"]').show();
      } else if (selected === "update_duration") {
        $('input[name="bulk_duration"]').show();
      } else if (selected === "update_role") {
        $('select[name="bulk_role"]').show();
      }
    },
  );

  // Handle quick edit
  $(document).on("click", ".quick-edit", function(e) {
    e.preventDefault();
    var $row = $(this).closest("tr");
    var $quickEditRow = $("#quick-edit-row");

    // Populate quick edit fields
    var levelId = $row.find('input[name="level_ids[]"]').val();
    var levelName = $row.find("td:eq(0) strong").text();
    var levelDuration = $row.find("td:eq(1)").text();
    var levelRole = $row.find("td:eq(2)").text();
    var levelStatus = $row.find("td:eq(3)").text();
    var levelPrice = $row.find("td:eq(4)").text();

    // Populate quick edit fields
    $quickEditRow.find('input[name="quick_edit_name"]').val(levelName);
    $quickEditRow.find('input[name="quick_edit_duration"]').val(levelDuration);
    $quickEditRow
      .find('input[name="quick_edit_price"]')
      .val(levelPrice.replace("$", ""));

    // Set dropdowns
    $quickEditRow
      .find('select[name="quick_edit_role"] option')
      .each(function() {
        if ($(this).text() === levelRole) {
          $(this).prop("selected", true);
        }
      });

    $quickEditRow
      .find('select[name="quick_edit_status"] option')
      .each(function() {
        if ($(this).text() === levelStatus) {
          $(this).prop("selected", true);
        }
      });
    $quickEditRow.find('input[name="level_id"]').val(levelId);

    $quickEditRow.insertAfter($row).show();
  });

  $("#cancel-quick-edit").on("click", function(e) {
    e.preventDefault();
    $("#quick-edit-row").hide();
  });
});
