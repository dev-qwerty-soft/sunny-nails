(function ($) {
  "use strict";

  $(document).ready(function () {
    initServicesStep();
  });

  function initServicesStep() {
    // Category tabs functionality
    $(document).on("click", ".category-tab", function () {
      const categoryId = $(this).data("category-id");

      // Update active tab
      $(".category-tab").removeClass("active");
      $(this).addClass("active");

      // Show only services from selected category
      $(".category-services").hide();
      $('.category-services[data-category-id="' + categoryId + '"]').show();
    });

    // Service checkbox selection
    $(document).on("change", ".service-checkbox", function () {
      const isChecked = $(this).is(":checked");
      const serviceItem = $(this).closest(".service-item");

      if (isChecked) {
        serviceItem.addClass("selected");
      } else {
        serviceItem.removeClass("selected");
      }

      // Update next button state
      updateNextButtonState();
    });

    // Make the entire service item clickable to toggle checkbox
    $(document).on("click", ".service-item", function (e) {
      // Prevent clicking on checkbox from triggering this handler
      if ($(e.target).is(".service-checkbox")) {
        return;
      }

      const checkbox = $(this).find(".service-checkbox");
      checkbox.prop("checked", !checkbox.prop("checked")).trigger("change");
    });

    // Validate services selection when clicking Next
    $(document).on("click", '.booking-step[data-step="services"] .next-btn', function (e) {
      if (!validateServicesSelection()) {
        e.preventDefault();
        showValidationAlert();
        return false;
      }
    });
  }

  function updateNextButtonState() {
    const hasSelectedServices = $(".service-checkbox:checked").length > 0;
    $('.booking-step[data-step="services"] .next-btn').prop("disabled", !hasSelectedServices);
  }

  function validateServicesSelection() {
    return $(".service-checkbox:checked").length > 0;
  }

  /**
   * Show validation alert when no services selected
   */
  function showValidationAlert() {
    try {
      // Remove any existing alerts first
      $(".validation-alert-overlay").remove();

      // Create custom alert as shown in the mockup
      const alertHtml = `
      <div class="validation-alert-overlay">
        <div class="validation-alert">
          <div class="validation-alert-title">Повідомлення з localhost</div>
          <div class="validation-alert-message">Please select at least one service</div>
          <button class="validation-alert-button">OK</button>
        </div>
      </div>
    `;

      $("body").append(alertHtml);

      // Bind click event to the OK button
      $(document).on("click", ".validation-alert-button", function () {
        $(".validation-alert-overlay").remove();
      });

      console.log("Validation alert shown: No services selected");
      return true;
    } catch (error) {
      console.error("Error showing validation alert:", error);
      alert("Please select at least one service");
      return false;
    }
  }
})(jQuery);
