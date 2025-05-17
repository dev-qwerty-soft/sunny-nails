/**
 * Simple Direct Booking Fix
 * Selects service in popup and activates "Choose a master" button
 */
(function ($) {
  "use strict";

  function debug(message, ...args) {
    console.log(`[BookingFix] ${message}`, ...args);
  }

  $(document).on("click", ".service-card .book-btn, button.book-btn[data-popup-open='true']", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $button = $(this);
    const $serviceCard = $button.closest(".service-card");
    const serviceId = $serviceCard.data("service-id") || $button.data("service-id");

    if (!serviceId) {
      debug("No service ID found");
      return;
    }

    debug("Service selected:", serviceId);

    const serviceTitle = $serviceCard.find(".service-title").clone().children().remove().end().text().trim();

    const servicePriceText = $serviceCard.find(".service-price").text().trim();
    const serviceDuration = $serviceCard.find(".service-duration").text().replace("Duration:", "").trim();
    const serviceWearTime = $serviceCard.find(".service-wear-time").text().replace("Wear time:", "").trim();

    const priceMatch = servicePriceText.match(/(\d+(?:\.\d+)?)/);
    const servicePrice = priceMatch ? priceMatch[0] : "0";
    const currency = servicePriceText.replace(/[\d.,]/g, "").trim() || "SGD";

    sessionStorage.setItem("selected_service_id", serviceId);

    $(".booking-popup-overlay").addClass("active");

    setTimeout(function () {
      $(".booking-step").removeClass("active");
      $(".booking-step[data-step='services']").addClass("active");

      let $checkbox = $(`.service-checkbox[data-service-id="${serviceId}"]`);
      let serviceFound = false;

      if ($checkbox.length) {
        debug("Service checkbox found immediately");

        const $categoryServices = $checkbox.closest(".category-services");
        const categoryId = $categoryServices.data("category-id");

        $(".category-tab").removeClass("active");
        $(`.category-tab[data-category-id="${categoryId}"]`).addClass("active");

        $(".category-services").hide();
        $categoryServices.show();

        $checkbox.prop("checked", true);
        $checkbox.closest(".service-item").addClass("selected");

        $checkbox.trigger("change");
        serviceFound = true;
      } else {
        debug("Service checkbox not found initially, searching in all categories");

        $(".category-tab").each(function () {
          const tabId = $(this).data("category-id");
          debug("Checking category", tabId);

          $(".category-tab").removeClass("active");
          $(this).addClass("active");

          $(".category-services").hide();
          $(`.category-services[data-category-id="${tabId}"]`).show();

          $checkbox = $(`.category-services[data-category-id="${tabId}"] .service-checkbox[data-service-id="${serviceId}"]`);

          if ($checkbox.length) {
            debug("Service checkbox found in category", tabId);

            $checkbox.prop("checked", true);
            $checkbox.closest(".service-item").addClass("selected");

            $checkbox.trigger("change");
            serviceFound = true;
            return false;
          }
        });
      }

      if (!serviceFound) {
        debug("Service not found in any category, trying to add manually");

        if (typeof window.addService === "function") {
          window.addService(serviceId, serviceTitle, servicePrice, currency, serviceDuration, serviceWearTime, false, serviceId, "");
        }
      }

      $(".booking-step[data-step='services'] .next-btn, .choose-a-master-btn").prop("disabled", false);

      setTimeout(function () {
        const $chooseBtn = $("button:contains('Choose a master')");
        if ($chooseBtn.length) {
          debug("Found 'Choose a master' button, clicking it");
          $chooseBtn.click();
        } else {
          debug("Clicking next button");
          $(".booking-step[data-step='services'] .next-btn").click();
        }
      }, 800);
    }, 500);
  });

  $(document).on("click", ".booking-step[data-step='services'] .next-btn", function () {
    debug("Services next button clicked");

    setTimeout(function () {
      if (typeof window.loadStaffForServices === "function") {
        debug("Calling loadStaffForServices manually after delay");
        window.loadStaffForServices();
      }
    }, 500);
  });

  $(document).on("click", "button:contains('Choose a master')", function () {
    debug("'Choose a master' button clicked");

    if (window.bookingData && (!window.bookingData.services || window.bookingData.services.length === 0)) {
      debug("No services in bookingData when trying to choose master");

      const serviceId = sessionStorage.getItem("selected_service_id");
      if (serviceId) {
        debug("Found service ID in sessionStorage:", serviceId);

        const $checkbox = $(`.service-checkbox[data-service-id="${serviceId}"]`);
        if ($checkbox.length) {
          $checkbox.prop("checked", true).trigger("change");
        }
      }
    }

    setTimeout(function () {
      if (typeof window.goToStep === "function") {
        window.goToStep("master");
      } else {
        $(".booking-step").removeClass("active");
        $(".booking-step[data-step='master']").addClass("active");
      }

      if (typeof window.loadStaffForServices === "function") {
        debug("Calling loadStaffForServices");
        window.loadStaffForServices();
      }
    }, 300);
  });

  $(document).on("click", ".staff-item.any-master", function () {
    debug("Any master selected");

    if (window.bookingData) {
      window.bookingData.staffId = "any";
      window.bookingData.staffName = "Any master";
      window.bookingData.staffLevel = 1;
    }

    $(".staff-item").removeClass("selected");
    $(this).addClass("selected");
  });
})(jQuery);

/**
 * Team Page Master Booking Enhancement
 * Adds functionality to book a specific master when clicking "Book an Appointment" button on team page
 */
(function ($) {
  "use strict";

  function debug(message, ...args) {
    console.log(`[TeamBookingFix] ${message}`, ...args);
  }

  $(document).on("click", ".team-card__buttons .btn.yellow, .team-card .btn.yellow, button.book-tem", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $button = $(this);
    const $teamCard = $button.closest(".team-card");
    const staffId = $button.data("staff-id") || $teamCard.data("staff-id");
    const staffName = $teamCard.find(".team-card__name").text().trim();

    const starCount = $teamCard.find(".star").length;
    const staffLevel = starCount > 0 ? starCount : 1;

    const staffAvatar = $teamCard.find(".team-card__image").attr("src") || "";

    sessionStorage.setItem("selected_master_id", staffId || "any");
    sessionStorage.setItem("selected_master_name", staffName);
    sessionStorage.setItem("selected_master_level", staffLevel);
    sessionStorage.setItem("selected_master_avatar", staffAvatar);

    debug("Master selected from team page:", {
      id: staffId,
      name: staffName,
      level: staffLevel,
      avatar: staffAvatar,
    });

    $(".booking-popup-overlay").addClass("active");

    setTimeout(function () {
      if (window.bookingData) {
        window.bookingData.initialOption = "master";
        window.bookingData.flowHistory = ["initial", "master"];

        window.bookingData.staffId = staffId || "any";
        window.bookingData.staffName = staffName;
        window.bookingData.staffLevel = staffLevel;
        window.bookingData.staffAvatar = staffAvatar;
      }

      $(".booking-step").removeClass("active");
      $('.booking-step[data-step="master"]').addClass("active");

      setTimeout(function () {
        selectMasterInUI(staffId);

        setTimeout(function () {
          const $nextBtn = $('.booking-step[data-step="master"] .next-btn');
          debug("Clicking next button to services step");
          $nextBtn.prop("disabled", false).click();
        }, 500);
      }, 300);
    }, 300);
  });

  function selectMasterInUI(staffId) {
    if (!staffId) staffId = "any";

    debug("Selecting master in UI:", staffId);

    const $staffItem = $(`.staff-item[data-staff-id="${staffId}"]`);

    if ($staffItem.length) {
      debug("Found master item in popup");

      $(".staff-item").removeClass("selected");

      $staffItem.addClass("selected");

      const $radio = $staffItem.find('input[type="radio"]');
      if ($radio.length) {
        $radio.prop("checked", true);
      }
    } else {
      debug("Master not found in popup, trying with 'any'");

      $(".staff-item").removeClass("selected");
      $(".staff-item.any-master").addClass("selected");

      const $anyRadio = $(".staff-item.any-master").find('input[type="radio"]');
      if ($anyRadio.length) {
        $anyRadio.prop("checked", true);
      }

      if (window.bookingData) {
        window.bookingData.staffId = "any";
        window.bookingData.staffName = "Any master";
        window.bookingData.staffLevel = 1;
      }
    }

    $('.booking-step[data-step="master"] .next-btn').prop("disabled", false);
  }

  function loadServicesForSelectedMaster(staffId) {
    debug("Loading services for master:", staffId);

    if (typeof window.loadServicesForMaster === "function") {
      window.loadServicesForMaster(staffId);
    } else {
      debug("loadServicesForMaster function not found");

      $.ajax({
        url: window.booking_params ? window.booking_params.ajax_url : ajaxurl,
        type: "POST",
        data: {
          action: "get_services_for_master",
          staff_id: staffId,
          nonce: window.booking_params ? window.booking_params.nonce : "",
        },
        success: function (response) {
          debug("Services loaded for master", response);

          if (response.success && Array.isArray(response.data)) {
            debug("Successfully loaded", response.data.length, "services");
          }
        },
        error: function (xhr, status, error) {
          debug("Error loading services for master:", error);
        },
      });
    }
  }

  $(document).on("click", ".booking-step[data-step='master'] .next-btn", function () {
    debug("Master next button clicked");

    if (window.bookingData && window.bookingData.staffId) {
      debug("Master selected:", window.bookingData.staffId);

      setTimeout(function () {
        loadServicesForSelectedMaster(window.bookingData.staffId);
      }, 300);
    } else {
      debug("No master selected when trying to proceed");

      const staffId = sessionStorage.getItem("selected_master_id");
      if (staffId) {
        debug("Recovered master ID from sessionStorage:", staffId);

        if (window.bookingData) {
          window.bookingData.staffId = staffId;
          window.bookingData.staffName = sessionStorage.getItem("selected_master_name") || "Selected master";
          window.bookingData.staffLevel = parseInt(sessionStorage.getItem("selected_master_level") || "1");
          window.bookingData.staffAvatar = sessionStorage.getItem("selected_master_avatar") || "";
        }

        loadServicesForSelectedMaster(staffId);
      }
    }
  });
})(jQuery);
