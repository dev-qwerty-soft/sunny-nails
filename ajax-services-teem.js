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
    const serviceDuration = $serviceCard.find(".service-duration").text().replace("<strong>Duration:</strong>", "").trim();
    const serviceWearTime = $serviceCard.find(".service-wear-time").text().replace("<strong>Wear time:</strong>", "").trim();

    const priceMatch = servicePriceText.match(/(\d+(?:\.\d+)?)/);
    const servicePrice = priceMatch ? priceMatch[0] : "0";
    const currency = servicePriceText.replace(/[\d.,]/g, "").trim() || "SGD";

    sessionStorage.setItem("selected_service_id", serviceId);

    $(".loading-overlay").show();
    $(".booking-popup-overlay").addClass("active");
    $(".booking-popup-overlay .booking-popup").css("display", "none");

    function showPopup() {
      $(".booking-popup-overlay .booking-popup").css("display", "block");
      $(".loading-overlay").hide();
    }

    if (window.bookingData && window.bookingData.staffId) {
      showPopup();
    } else {
      setTimeout(function () {
        showPopup();
      }, 1500);
    }

    setTimeout(function () {
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
        window.goToStep("datetime");
        setTimeout(function () {
          if (typeof window.generateCalendar === "function") {
            window.generateCalendar();
            debug("🗓️ generateCalendar() called after goToStep");
          }
        }, 300);
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

(function ($) {
  "use strict";

  function ensureBookingData() {
    if (typeof window.bookingData === "undefined") {
      window.bookingData = {
        services: [],
        coreServices: [],
        addons: [],
        staffId: null,
        staffName: "",
        staffAvatar: "",
        staffLevel: 1,
        date: null,
        time: null,
        contact: {},
        flowHistory: ["initial"],
        initialOption: "services",
      };
    }
  }
  function showLoaderPopup() {
    $(".loading-overlay").show();
    $(".booking-popup-overlay").addClass("active");
    $(".booking-popup-overlay .booking-popup").css("display", "none");
  }

  function showPopupWithDelay(delay = 1500) {
    setTimeout(() => {
      $(".booking-popup-overlay .booking-popup").css("display", "block");
      $(".loading-overlay").hide();
    }, delay);
  }

  $(document).on("click", ".service-card .book-btn, button.book-btn[data-popup-open='true']", function (e) {
    e.preventDefault();
    e.stopPropagation();

    if (typeof window.bookingData !== "object") {
      window.bookingData = {};
    }

    if (!window.bookingData.staffId && sessionStorage.getItem("selected_master_id")) {
      window.bookingData.staffId = sessionStorage.getItem("selected_master_id");
      window.bookingData.staffName = sessionStorage.getItem("selected_master_name") || "Selected Master";
    }

    const $button = $(this);
    const $serviceCard = $button.closest(".service-card");
    const serviceId = $serviceCard.data("service-id") || $button.data("service-id");
    if (!serviceId) return;

    const serviceTitle = $serviceCard.find(".service-title").clone().children().remove().end().text().trim();
    const servicePriceText = $serviceCard.find(".service-price").text().trim();
    const serviceDuration = $serviceCard.find(".service-duration").text().replace("<strong>Duration:</strong>", "").trim();
    const serviceWearTime = $serviceCard.find(".service-wear-time").text().replace("<strong>Wear time:</strong>", "").trim();
    const priceMatch = servicePriceText.match(/(\d+(?:\.\d+)?)/);
    const servicePrice = priceMatch ? priceMatch[0] : "0";
    const currency = servicePriceText.replace(/[\d.,]/g, "").trim() || "SGD";

    sessionStorage.setItem("selected_service_id", serviceId);
    sessionStorage.removeItem("selected_master_id");

    setTimeout(function () {
      let $checkbox = $(`.service-checkbox[data-service-id="${serviceId}"]`);
      let serviceFound = false;

      if ($checkbox.length) {
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
        $(".category-tab").each(function () {
          const tabId = $(this).data("category-id");
          $(".category-tab").removeClass("active");
          $(this).addClass("active");
          $(".category-services").hide();
          $(`.category-services[data-category-id="${tabId}"]`).show();

          $checkbox = $(`.category-services[data-category-id="${tabId}"] .service-checkbox[data-service-id="${serviceId}"]`);
          if ($checkbox.length) {
            $checkbox.prop("checked", true);
            $checkbox.closest(".service-item").addClass("selected");
            $checkbox.trigger("change");
            serviceFound = true;
            return false;
          }
        });
      }

      if (!serviceFound && typeof window.addService === "function") {
        window.addService(serviceId, serviceTitle, servicePrice, currency, serviceDuration, serviceWearTime, false, serviceId, "");
      }

      $(".booking-step[data-step='services'] .next-btn, .choose-a-master-btn").prop("disabled", false);

      setTimeout(function () {
        const $chooseBtn = $("button:contains('Choose a master')");
        if ($chooseBtn.length) {
          $chooseBtn.click();
        } else {
          $(".booking-step[data-step='services'] .next-btn").click();
        }
      }, 800);
    }, 500);
  });

  $(document).on("click", ".team-card .btn.yellow, .team-card__buttons .btn.yellow, .book-tem", function (e) {
    e.preventDefault();
    e.stopPropagation();

    ensureBookingData();

    const $button = $(this);
    const $masterCard = $button.closest(".team-card");
    const masterId = $button.data("staff-id") || $masterCard.data("staff-id") || $button.data("master-id") || $masterCard.data("master-id") || $button.attr("data-staff-id") || $masterCard.attr("data-staff-id");
    if (!masterId) return;

    const masterName = $masterCard.find(".team-card__name").text().trim();
    sessionStorage.setItem("selected_master_id", masterId);
    sessionStorage.setItem("selected_master_name", masterName);

    $(".booking-popup-overlay").addClass("active");

    $(".booking-popup-overlay .booking-popup").css("display", "none");

    setTimeout(function () {
      showLoaderPopup();

      let $staffItem = $(`.staff-item[data-staff-id="${masterId}"]`);

      if ($staffItem.length) {
        $(".staff-item").removeClass("selected");
        $staffItem.addClass("selected");

        const $radio = $staffItem.find('input[type="radio"]');
        if ($radio.length) {
          $radio.prop("checked", true);
          $radio.trigger("change");
          $radio.trigger("click");
        }

        try {
          $staffItem.trigger("click");
        } catch (e) {}
      } else {
        const $anyMaster = $(".staff-item.any-master");
        if ($anyMaster.length) {
          $(".staff-item").removeClass("selected");
          $anyMaster.addClass("selected");

          const $radio = $anyMaster.find('input[type="radio"]');
          if ($radio.length) {
            $radio.prop("checked", true);
            $radio.trigger("change");
            $radio.trigger("click");
          }
        }
      }

      window.bookingData.initialOption = "master";
      window.bookingData.staffId = masterId;
      window.bookingData.staffName = masterName;
      window.bookingData.flowHistory = ["initial", "master"];

      if (typeof window.goToStep === "function") {
        window.goToStep("services");
      } else {
        $(".booking-step").removeClass("active");
        $(".booking-step[data-step='services']").addClass("active");
      }

      $('.booking-step[data-step="services"] .next-btn').text("Select date and time");

      window.bookingData.flowHistory.push("services");

      showPopupWithDelay(0);
    }, 1000);
  });

  $(document).on("click", '.booking-step[data-step="services"] .next-btn', function (e) {
    ensureBookingData();

    if (!window.bookingData.staffId && sessionStorage.getItem("selected_master_id")) {
      window.bookingData.staffId = sessionStorage.getItem("selected_master_id");
      window.bookingData.staffName = sessionStorage.getItem("selected_master_name") || "Selected Master";
      window.bookingData.initialOption = "master";
    }

    if (typeof window.goToStep === "function") {
      window.goToStep("datetime");
    } else {
      $(".booking-step").removeClass("active");
      $(".booking-step[data-step='datetime']").addClass("active");
    }

    setTimeout(() => {
      const staffId = window.bookingData?.staffId;
      const services = window.bookingData?.services;
      const isReady = staffId && Array.isArray(services) && services.length > 0 && $(".booking-step[data-step='datetime']").hasClass("active");

      if (isReady && typeof window.generateCalendar === "function") {
        window.generateCalendar();
      }
    }, 400);
  });
  let allowAutoAdvanceFromMaster = false;

  $(document).on("bookingStepChanged", function (e, step) {
    if (step === "master" && window.bookingData?.staffId && allowAutoAdvanceFromMaster) {
      setTimeout(() => {
        const $nextBtn = $('.booking-step[data-step="master"] .next-btn');
        if ($nextBtn.length && !$nextBtn.prop("disabled")) {
          $nextBtn.trigger("click");
        }
      }, 300);
    }
  });

  $(document).on("click", ".booking-popup-close, .close-popup-btn", function () {
    sessionStorage.removeItem("selected_master_id");
    sessionStorage.removeItem("selected_service_id");
  });
})(jQuery);
