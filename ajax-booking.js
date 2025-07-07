/**
 * Enhanced Booking System for Altegio Integration
 *
 * Features:
 * - Core services and add-ons separation
 * - Master level price adjustment
 * - Consistent star rating system
 * - Smooth booking flow with proper validation
 */
(function ($) {
  "use strict";

  // Booking data object to store all selected information
  let bookingData = {
    services: [], // All selected services
    coreServices: [], // Only core services (not add-ons)
    addons: [], // Only add-on services
    staffId: null, // Selected master ID
    staffName: "", // Selected master name
    staffAvatar: "", // Selected master avatar URL
    staffLevel: 1, // Selected master level (stars) - default to 1
    date: null, // Selected date (YYYY-MM-DD)
    time: null, // Selected time (HH:MM)
    coupon: null,
    contact: {}, // Customer contact information
    flowHistory: ["initial"], // Track step navigation history for back button
    initialOption: "services", // Default first step after initial
  };

  // Configuration
  const config = {
    debug: true, // Enable debug logging
    priceAdjustmentPerLevel: 10, // Price increase percentage per master level above 1
    apiEndpoint: booking_params.ajax_url, // API endpoint from localized WP
    nonce: booking_params.nonce, // Security nonce from WP
    simulateTimeSlots: true, // Enable time slot simulation as fallback
    useLocalStorage: true, // Save form progress in local storage
    maxRetries: 3, // Maximum API call retries
  };

  /**
   * Generate stars HTML based on level
   * @param {number} level - Star level (1-5)
   * @returns {string} - HTML with star SVGs
   */
  const levelTitles = {
    [-1]: "Intern",
    1: "Sunny Ray",
    2: "Sunny Shine",
    3: "Sunny Inferno",
    4: "Trainer",
    5: "Sunny Inferno, Supervisor",
  };

  const percentMap = {
    [-1]: -50,
    1: 0,
    2: 10,
    3: 20,
    4: 30,
    5: 30,
  };

  const starsMap = {
    [-1]: 0,
    1: 1,
    2: 2,
    3: 3,
    4: 3,
    5: 4,
  };

  function generateStarsHtml(level) {
    if (typeof level === "undefined" || level === null) return "";

    const starsCount = starsMap[level];

    if (typeof starsCount === "undefined" || starsCount === 0) return "";

    const starSvg = `<div class='star'><svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'>
    <path d='M20.8965 18.008L18.6085 15.7L19.2965 15.012L21.6045 17.3L20.8965 18.008ZM17.7005 6.373L17.0125 5.685L19.3005 3.396L20.0085 4.085L17.7005 6.373ZM6.30048 6.393L4.01148 4.084L4.70048 3.395L7.00848 5.684L6.30048 6.393ZM3.08548 18.007L2.39648 17.299L4.68548 15.01L5.39248 15.699L3.08548 18.007ZM6.44048 20L7.91048 13.725L3.00048 9.481L9.47048 8.933L12.0005 3L14.5505 8.933L21.0205 9.481L16.1085 13.725L17.5785 20L12.0005 16.66L6.44048 20Z' fill='#FDC41F'/>
  </svg></div>`;

    return starSvg.repeat(starsCount);
  }

  /**
   * Log debug information when debug mode is enabled
   * @param {string} message - Debug message
   * @param {*} data - Optional data to log
   */
  function debug(message, data) {
    // if (!config.debug) return;
    // if (data !== undefined) {
    //   console.log(message, data);
    // } else {
    //   console.log(message);
    // }
  }

  /**
   * Initialize the booking system when document is ready
   */
  $(document).ready(function () {
    initServiceHandling();
    initMasterHandling();
    initDateTimeHandling();
    initContactHandling();
    initCouponHandling();
    initLocalStorageSupport();
    setupEditButtons();
    initBookingPopup();
    // Check for restored session
    if (config.useLocalStorage) {
      restoreBookingSession();
    }

    debug("Booking system initialized");
  });

  /**
   * Initialize local storage support for saving booking progress
   */
  function initLocalStorageSupport() {
    if (!config.useLocalStorage) return;

    // Save booking data on each step change
    $(document).on("bookingStepChanged", function (e, step) {
      saveBookingSession();
    });

    // Save on form field changes with debounce
    let debounceTimer;
    $(document).on("change keyup", ".contact-form input, .contact-form textarea", function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        // Save contact info before saving session
        let name = $("#client-name").val();
        let phone = $("#client-phone").val();
        let email = $("#client-email").val();
        let comment = $("#client-comment").val();

        bookingData.contact = {
          name: name || "",
          phone: phone || "",
          email: email || "",
          comment: comment || "",
        };

        saveBookingSession();
      }, 500);
    });
  }

  /**
   * Save current booking session to local storage
   */
  function saveBookingSession() {
    if (!config.useLocalStorage) return;
    try {
      localStorage.setItem("altegio_booking_data", JSON.stringify(bookingData));
      debug("Booking session saved to local storage");
    } catch (e) {
      debug("Error saving booking session", e);
    }
  }
  /**
   * Initialize coupon handling
   */
  function initCouponHandling() {
    const couponInput = $("#coupon-code");
    const applyBtn = $(".apply-coupon-btn");

    applyBtn.prop("disabled", true);

    couponInput.on("input", function () {
      const inputValue = $(this).val().trim();

      if (inputValue.length > 0) {
        applyBtn.prop("disabled", false);
      } else {
        applyBtn.prop("disabled", true);
      }
    });

    couponInput.on("focus", function () {
      const inputValue = $(this).val().trim();
      applyBtn.prop("disabled", inputValue.length === 0);
    });

    $(document).on("click", ".apply-coupon-btn", function () {
      const couponCode = $("#coupon-code").val().trim();

      if (!couponCode) {
        showCouponFeedback("Please enter a coupon code", "error");
        return;
      }

      $(this).prop("disabled", true).text("Checking...");

      $.ajax({
        url: booking_params.ajax_url,
        type: "POST",
        data: {
          action: "check_promo_code",
          nonce: booking_params.nonce,
          promo_code: couponCode,
        },
        success: function (response) {
          $(".apply-coupon-btn").prop("disabled", false).text("Apply");

          if (response.success) {
            bookingData.coupon = {
              code: response.data.promo_code,
              value: parseFloat(response.data.discount_value),
            };

            showCouponFeedback(response.data.message, "success");

            updateSummary();

            $(".booking-step[data-step='confirm'] .coupon-feedback").show();
          } else {
            showCouponFeedback(response.data.message || "Invalid coupon", "error");
          }
        },
        error: function () {
          $(".apply-coupon-btn").prop("disabled", false).text("Apply");
          showCouponFeedback("Error checking coupon. Please try again.", "error");
        },
      });
    });

    $(document).on("keypress", "#coupon-code", function (e) {
      if (e.which === 13) {
        e.preventDefault();
        $(".apply-coupon-btn").click();
      }
    });
  }

  /**
   * Show coupon feedback message
   */
  function showCouponFeedback(message, type) {
    const $feedback = $(".coupon-feedback");

    $feedback.removeClass("success error").addClass(type).text(message).fadeIn();

    // Hide after 5 seconds for success
    if (type === "success") {
      setTimeout(() => {
        $feedback.fadeOut();
      }, 5000);
    }
  }
  /**
   * Restore booking session from local storage
   */
  function restoreBookingSession() {
    if (!config.useLocalStorage) return;
    try {
      const savedData = localStorage.getItem("altegio_booking_data");
      if (savedData) {
        const parsedData = JSON.parse(savedData);

        // Validate the data before restoring
        if (parsedData && parsedData.services) {
          bookingData = parsedData;
          debug("Restored booking session from local storage", bookingData);

          // Apply restored data to UI
          applyRestoredSession();
          return true;
        }
      }
    } catch (e) {
      debug("Error restoring booking session", e);
    }
    return false;
  }

  /**
   * Apply restored session data to UI elements
   */
  function applyRestoredSession() {
    // Restore service selections
    bookingData.services.forEach((service) => {
      const checkbox = $(`.service-checkbox[data-service-id="${service.id}"]`);
      checkbox.prop("checked", true);
      checkbox.closest(".service-item").addClass("selected");
    });

    // Restore staff selection
    if (bookingData.staffId) {
      $(`.staff-item[data-staff-id="${bookingData.staffId}"]`).addClass("selected");
    }

    // Restore contact form data
    if (bookingData.contact) {
      $("#client-name").val(bookingData.contact.name || "");
      $("#client-phone").val(bookingData.contact.phone || "");
      $("#client-email").val(bookingData.contact.email || "");
      $("#client-comment").val(bookingData.contact.comment || "");
    }

    // Update add-on availability
    updateAddonAvailability();
  }

  /**
   * Clear booking session from local storage
   */
  function clearBookingSession() {
    if (!config.useLocalStorage) return;
    try {
      localStorage.removeItem("altegio_booking_data");
      debug("Booking session cleared from local storage");
    } catch (e) {
      debug("Error clearing booking session", e);
    }
  }

  /**
   * Initialize booking popup and general navigation
   */
  function initBookingPopup() {
    // Open popup when book button is clicked
    $(document).on("click", " .open-popup", function (e) {
      e.preventDefault();

      // Reset booking data
      resetBookingForm();

      // If booking from service card, pre-select that service
      if ($(this).closest(".service-card").length) {
        const serviceId = $(this).closest(".service-card").data("service-id");
        bookingData.preSelectedServiceId = serviceId;
      }

      // If booking from master card, pre-select that master
      if ($(this).closest(".master-card").length) {
        const masterId = $(this).closest(".master-card").data("master-id");
        bookingData.preSelectedMasterId = masterId;
        // Set initial option to master if coming from master card
        bookingData.initialOption = "master";
      }

      // Show popup
      $(".booking-popup-overlay").addClass("active");
      $("body").addClass("popup-open");
      $(".loading-overlay").hide();
      // Trigger custom event
      $(document).trigger("bookingPopupOpened");
    });

    // Close popup
    $(document).on("click", ".booking-popup-close, .close-popup-btn", function () {
      $(".booking-popup-overlay").removeClass("active");
      $("body").removeClass("popup-open");
      // Add a confirmation if there's unsaved data
      clearBookingSession();
    });

    // Close when clicking outside popup
    $(document).on("click", ".booking-popup-overlay", function (e) {
      if ($(e.target).is(".booking-popup-overlay")) {
        $(".booking-popup-overlay").removeClass("active");
        $("body").removeClass("popup-open");
        clearBookingSession();
      }
    });

    // Initial step selection (Services or Master)
    $(document).on("click", ".booking-option-item", function () {
      $(".booking-option-item").removeClass("active");
      $(".status-indicator").removeClass("active");

      $(this).addClass("active");
      $(this).find(".status-indicator").addClass("active");

      bookingData.initialOption = $(this).data("option");
      debug("Initial option selected", bookingData.initialOption);

      // Update flow history
      bookingData.flowHistory = ["initial"];
    });

    // Next button in initial step
    $(document).on("click", '.booking-step[data-step="initial"] .next-btn', function () {
      // Get selected option (services or master)
      const nextStep = $(".booking-option-item.active").data("option") || "services";
      bookingData.initialOption = nextStep;

      // Initialize flow history
      bookingData.flowHistory = ["initial", nextStep];

      debug("Going to step", nextStep);
      goToStep(nextStep);

      // If pre-selected service, select it
      if (nextStep === "services" && bookingData.preSelectedMasterId) {
        setTimeout(function () {
          const masterId = bookingData.preSelectedMasterId;
          loadServicesForMaster(masterId);
        }, 100);
      }

      // If pre-selected master, load that master's data
      if (nextStep === "master" && bookingData.preSelectedMasterId) {
        loadStaffById(bookingData.preSelectedMasterId);
      }
    });

    // Universal back button behavior - fixed to go to previous step, not always initial
    $(document).on("click", ".booking-back-btn", function () {
      const currentStep = $(this).closest(".booking-step").data("step");
      debug("Going back from", currentStep);

      // Remove current step from history
      if (bookingData.flowHistory.length > 1) {
        bookingData.flowHistory.pop();
        // Get the previous step
        const previousStep = bookingData.flowHistory[bookingData.flowHistory.length - 1];
        goToStep(previousStep);
      } else {
        // Fallback to initial if history is broken
        goToStep("initial");
      }
    });
  }

  $(document).on("click", ".want-this-btn", function (e) {
    e.preventDefault();

    const masterId = parseInt($(this).data("master-id"));
    const serviceIds = $(this)
      .data("service-ids")
      .toString()
      .split(",")
      .map((id) => parseInt(id.trim()));

    const galleryTitle = $(this).data("gallery-title") || "";
    bookingData.galleryTitle = galleryTitle;
    if (!masterId || !serviceIds.length) return;
    resetBookingForm();

    $(".booking-popup").hide();
    $(".loading-overlay").show();

    bookingData.staffId = masterId;
    bookingData.initialOption = "master";
    bookingData.flowHistory = ["initial", "master"];
    bookingData.galleryTitle = galleryTitle;
    $(".booking-popup-overlay").addClass("active");
    $("body").addClass("popup-open");

    const $staffItem = $(`.staff-item[data-staff-id="${masterId}"]`);
    if ($staffItem.length) {
      $(".staff-item").removeClass("selected");
      $staffItem.addClass("selected");

      const $radio = $staffItem.find('input[type="radio"]');
      if ($radio.length) {
        $radio.prop("checked", true).trigger("change").trigger("click");
      }

      const name = $staffItem.find(".staff-name").text().trim();
      const level = $staffItem.find(".star").length || 0;
      const specialization = $staffItem.find(".stars span").text().trim().replace(/[()]/g, "");

      bookingData.staffName = name || "Selected Master";
      bookingData.staffLevel = level;
      bookingData.staffSpecialization = specialization;

      $staffItem.trigger("click");
    } else {
      bookingData.staffName = "Selected Master";
      bookingData.staffLevel = 1;
    }

    bookingData.services = [];
    bookingData.coreServices = [];
    bookingData.addons = [];

    loadServicesForMaster(masterId);

    function waitForServiceCheckboxes(serviceIds, callback, maxTries = 30, delay = 100) {
      let tries = 0;
      const interval = setInterval(() => {
        const allFound = serviceIds.every((id) => $(`.service-checkbox[data-service-id="${id}"]`).length > 0);
        if (allFound || tries >= maxTries) {
          clearInterval(interval);
          callback();
        }
        tries++;
      }, delay);
    }

    waitForServiceCheckboxes(serviceIds, () => {
      serviceIds.forEach((id) => {
        const $checkbox = $(`.service-checkbox[data-service-id="${id}"]`);
        if ($checkbox.length) {
          $checkbox.prop("checked", true).trigger("change");
          $checkbox.closest(".service-item").addClass("selected");

          if (typeof window.addService === "function") {
            const title = $checkbox.data("service-title") || "Service";
            const price = parseFloat($checkbox.data("service-price")) || 0;
            const currency = $checkbox.data("service-currency") || "SGD";
            const duration = $checkbox.data("service-duration") || "";
            const wearTime = $checkbox.data("service-wear-time") || "";
            const isAddon = $checkbox.data("is-addon") === true || $checkbox.data("is-addon") === "true";

            addService(id, title, price, currency, duration, wearTime, isAddon, isAddon ? id : null, "");
          }
        }
      });
      if (!bookingData.flowHistory.includes("services")) {
        bookingData.flowHistory.push("services");
      }
      bookingData.flowHistory = ["initial", "services", "master", "datetime"];

      goToStep("datetime");
      generateCalendar();
      updateSummary();

      $(".booking-popup").fadeIn(200);
      $(".loading-overlay").hide();
    });
  });

  /**
   * Initialize service selection handling
   */
  function initServiceHandling() {
    // Category filter tabs
    $(document).on("click", ".category-tab", function () {
      const categoryId = $(this).data("category-id");

      // Update active tab
      $(".category-tab").removeClass("active");
      $(this).addClass("active");

      // Show only services from selected category
      $(".category-services").hide();
      $(`.category-services[data-category-id="${categoryId}"]`).show();
    });

    // Make the entire service item clickable
    $(document).on("click", ".service-item", function (e) {
      // Prevent triggering if clicking on checkbox directly
      if ($(e.target).is(".service-checkbox")) {
        return;
      }

      // Don't allow clicking if disabled
      if ($(this).hasClass("disabled")) {
        debug("Service item is disabled");
        return;
      }

      const checkbox = $(this).find(".service-checkbox");

      // If checkbox is disabled, don't allow toggling
      if (checkbox.prop("disabled")) {
        return;
      }

      checkbox.prop("checked", !checkbox.prop("checked"));
      checkbox.trigger("change");
    });

    // Service checkbox selection
    $(document).on("change", ".service-checkbox", function () {
      const serviceId = $(this).data("service-id");
      const serviceTitle = $(this).data("service-title");
      const servicePrice = $(this).data("service-price");
      const serviceCurrency = $(this).data("service-currency");
      const serviceDuration = $(this).data("service-duration") || "";
      const serviceWearTime = $(this).data("service-wear-time") || "";

      const isAddon = $(this).data("is-addon") === true || $(this).data("is-addon") === "true" || $(this).closest(".service-item").hasClass("addon-item") || $(this).closest(".core-related-addons").length > 0;

      const altegioId = $(this).data("altegio-id") || serviceId;

      const $serviceItem = $(this).closest(".service-item");
      const desc = $serviceItem.find(".service-description").text().trim();

      if ($(this).is(":checked")) {
        $serviceItem.addClass("selected");

        addService(serviceId, serviceTitle, servicePrice, serviceCurrency, serviceDuration, serviceWearTime, isAddon, altegioId, desc);
        debug("Service added", { id: serviceId, title: serviceTitle, isAddon });

        if (!isAddon) {
          const coreId = $(this).data("service-id");
          const $container = $(`.core-related-addons[data-core-id="${coreId}"]`);
          $container.addClass("open");
          $container.find(".service-checkbox").prop("disabled", false);
          $container.find(".addon-item").removeClass("disabled");
        }
      } else {
        $serviceItem.removeClass("selected");
        removeService(serviceId);
        debug("Service removed", serviceId);

        if (!isAddon) {
          const coreId = $(this).data("service-id");
          const $container = $(`.core-related-addons[data-core-id="${coreId}"]`);
          $container.removeClass("open");
          $container.find("input[type=checkbox]").prop("checked", false).prop("disabled", true);
          $container.find(".addon-item").removeClass("selected").addClass("disabled");

          $container.find(".service-checkbox").each(function () {
            const addonId = $(this).data("service-id");
            removeService(addonId);
          });
        }
      }

      updateAddonAvailability();
      updateNextButtonState();
    });

    // Next button in services step
    $(document).on("click", '.booking-step[data-step="services"] .next-btn', function () {
      try {
        // Validate that at least one core service is selected

        // Determine next step based on initial option
        let nextStep;
        if (bookingData.initialOption === "services") {
          nextStep = "master";
        } else {
          // If we started with master, then we go to datetime
          nextStep = "datetime";
        }

        // Add to flow history
        bookingData.flowHistory.push(nextStep);

        debug("Services selected, proceeding to", nextStep);

        if (nextStep === "master") {
          loadStaffForServices();
        } else if (nextStep === "datetime") {
          // If skipping master step, ensure master is selected
          if (!bookingData.staffId) {
            showValidationAlert("Please go back and select a master first");
            return false;
          }
          generateCalendar();
        }

        // Navigate to the next step
        goToStep(nextStep);

        // Trigger custom event
        $(document).trigger("bookingServicesCompleted", [bookingData.services]);

        return true;
      } catch (error) {
        showValidationAlert("An error occurred. Please try again.");
        return false;
      }
    });
  }

  /**
   * Initialize master selection handling
   */
  function initMasterHandling() {
    $(document).on("click", ".staff-item", function () {
      const staffId = $(this).data("staff-id");
      const staffName = $(this).find(".staff-name").text();
      let staffAvatar = "";

      const avatarImg = $(this).find(".staff-avatar img");
      if (avatarImg.length) {
        staffAvatar = avatarImg.attr("src") || "";
      }
      const specialization = $(this).data("staff-specialization");
      const staffLevel = typeof $(this).data("staff-level") !== "undefined" ? parseInt($(this).data("staff-level")) : 1;

      bookingData.staffLevel = staffLevel;
      bookingData.staffSpecialization = specialization;
      selectStaff(staffId, staffName, staffAvatar, staffLevel, specialization);

      $(".staff-item").removeClass("selected");
      $(this).addClass("selected");

      updateMasterNextButtonState();
    });

    $(document).on("click", '.booking-step[data-step="master"] .next-btn', function () {
      if (!bookingData.staffId) {
        showValidationAlert("Please select a specialist");
        return;
      }

      let nextStep;
      if (bookingData.initialOption === "master") {
        nextStep = "services";

        if (bookingData.flowHistory.includes("datetime")) {
          nextStep = "datetime";
        }
      } else {
        nextStep = "datetime";
      }

      bookingData.flowHistory.push(nextStep);

      debug("Master selected, proceeding to", nextStep);

      if (nextStep === "services") {
        loadServicesForMaster(bookingData.staffId);
      } else if (nextStep === "datetime") {
        generateCalendar();
      }

      goToStep(nextStep);

      $(document).trigger("bookingMasterSelected", [
        {
          id: bookingData.staffId,
          name: bookingData.staffName,
          level: bookingData.staffLevel,
        },
      ]);
    });
  }

  function renderContactStepSummary() {
    const level = typeof bookingData.staffLevel !== "undefined" ? parseInt(bookingData.staffLevel) : 1;

    $(".summary-master .name").text(bookingData.staffName || "N/A");

    const stars = generateStarsHtml(level);
    $(".summary-master .stars").html(stars);

    const levelTitle = levelTitles[level];
    $(".summary-master .stars-name")
      .text(levelTitle ? `(${levelTitle})` : "")
      .toggle(!!levelTitle);

    if (bookingData.staffAvatar) {
      $(".summary-master .avatar").attr("src", bookingData.staffAvatar);
    }
  }

  /**
   * Initialize date and time selection handling
   */
  function initDateTimeHandling() {
    $(document).on("click", ".prev-month", function () {
      navigateCalendar(-1);
    });

    $(document).on("click", ".next-month", function () {
      navigateCalendar(1);
    });

    $(document).on("click", ".calendar-day:not(.disabled, .empty)", function () {
      const $day = $(this);
      const date = $day.data("date");

      // Check if day is marked as unavailable
      if ($day.hasClass("unavailable")) {
        showUnavailableDateDialog(date);
        return;
      }

      // Proceed with normal date selection for available dates
      selectDate(date);
      $(".calendar-day").removeClass("selected");
      $day.addClass("selected");

      // Load time slots for selected date
      loadTimeSlots(date);
    });

    $(document).on("click", ".time-slot:not(.disabled)", function () {
      const time = $(this).data("time");
      selectTime(time);

      $(".time-slot").removeClass("selected");
      $(this).addClass("selected");

      updateDateTimeNextButtonState();
    });

    $(document).on("click", '.booking-step[data-step="datetime"] .next-btn', function () {
      if (validateDateTimeStep()) {
        bookingData.flowHistory.push("contact");

        renderContactStepSummary();
        goToStep("contact");

        updateSummary();

        $(document).trigger("bookingDateTimeSelected", [
          {
            date: bookingData.date,
            time: bookingData.time,
          },
        ]);
      }
    });
  }

  /**
   * Check day availability - simplified version without AJAX
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  /**
   * Check day availability based on actual time slots availability
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  /**
   * Check day availability based on actual time slots availability
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  /**
   * Check day availability using Promise.all for simultaneous requests
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  /**
   * Check day availability with batch processing
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  /**
   * Check day availability using single optimized API call
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  /**
   * Check day availability - all requests simultaneously for maximum speed
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  function checkDayAvailability(month, year) {
    if (!bookingData.staffId || bookingData.services.length === 0) {
      console.log("Skipping availability check - no staff or services selected");
      return;
    }

    const serviceIds = bookingData.services.map((s) => s.altegioId || s.id);
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Collect all dates to check for the month
    const datesToCheck = [];
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      if (date >= today) {
        datesToCheck.push(formatDate(date));
      }
    }

    if (datesToCheck.length === 0) return;

    console.log(`Checking availability for ${datesToCheck.length} dates simultaneously`);

    // Send ALL requests at once - no delays, no batches
    const allPromises = datesToCheck.map((dateToCheck) => {
      return new Promise((resolve) => {
        $.ajax({
          url: booking_params.ajax_url,
          method: "POST",
          data: {
            action: "get_time_slots",
            nonce: booking_params.nonce,
            staff_id: bookingData.staffId,
            date: dateToCheck,
            service_ids: serviceIds,
          },
          success: function (response) {
            let hasSlots = false;

            if (response.success && response.data) {
              if (Array.isArray(response.data) && response.data.length > 0) {
                hasSlots = true;
              } else if (response.data.slots && Array.isArray(response.data.slots) && response.data.slots.length > 0) {
                hasSlots = true;
              } else if (response.data.data && Array.isArray(response.data.data) && response.data.data.length > 0) {
                hasSlots = true;
              }
            }

            resolve({ date: dateToCheck, hasSlots });
          },
          error: function (xhr, status, error) {
            console.warn(`Error checking availability for ${dateToCheck}:`, error);
            resolve({ date: dateToCheck, hasSlots: false, error: true });
          },
        });
      });
    });

    // Process ALL results when they all complete
    Promise.all(allPromises).then((results) => {
      console.log(`Availability check completed for all ${results.length} dates`);

      // Process all results immediately
      results.forEach((result) => {
        const $dayElement = $(`.calendar-day[data-date="${result.date}"]`);

        if (!result.hasSlots) {
          // Mark as unavailable if no time slots
          if ($dayElement.length && !$dayElement.hasClass("disabled")) {
            $dayElement.addClass("unavailable");

            // If this was the selected date, clear selection
            if ($dayElement.hasClass("selected")) {
              $dayElement.removeClass("selected");
              bookingData.date = null;
              bookingData.time = null;
              $(".time-sections").html('<p class="error-message">Please select an available date.</p>');
              updateDateTimeNextButtonState();
            }
          }
        } else {
          // Mark as available
          if ($dayElement.length && !$dayElement.hasClass("disabled")) {
            $dayElement.removeClass("unavailable").addClass("available");
          }
        }
      });

      // Show summary statistics
      const availableCount = results.filter((r) => r.hasSlots && !r.error).length;
      const unavailableCount = results.filter((r) => !r.hasSlots && !r.error).length;
      const errorCount = results.filter((r) => r.error).length;

      console.log(`Summary: ${availableCount} available, ${unavailableCount} unavailable, ${errorCount} errors`);
    });
  }
  function clearAvailabilityCache() {
    $.ajax({
      url: booking_params.ajax_url,
      method: "POST",
      data: {
        action: "clear_availability_cache",
        nonce: booking_params.nonce,
      },
      success: function (response) {
        if (response.success) {
          debug("Availability cache cleared");
          // Refresh current month's availability
          const monthHeader = $(".month-header span").text();
          if (monthHeader) {
            const [monthName, year] = monthHeader.split(" ");
            const monthIndex = getMonthIndex(monthName);
            checkDayAvailability(monthIndex, parseInt(year));
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("Failed to clear availability cache:", error);
      },
    });
  }
  function markUnavailableDatesFromTimeSlots() {
    $(".calendar-day:not(.disabled, .empty)").each(function () {
      const dateStr = $(this).data("date");
      const $dayElement = $(this);

      if (dateStr && !$dayElement.hasClass("unavailable")) {
        $.ajax({
          url: booking_params.ajax_url,
          method: "POST",
          data: {
            action: "get_time_slots",
            nonce: booking_params.nonce,
            staff_id: bookingData.staffId,
            date: dateStr,
            service_ids: bookingData.services.map((s) => s.altegioId || s.id),
          },
          success: function (response) {
            if (!response.success || !response.data || (Array.isArray(response.data) && response.data.length === 0) || (response.data.slots && Array.isArray(response.data.slots) && response.data.slots.length === 0)) {
              $dayElement.addClass("unavailable");

              if ($dayElement.hasClass("selected")) {
                $dayElement.removeClass("selected");
                bookingData.date = null;
                bookingData.time = null;
                $(".time-sections").html('<p class="error-message">Please select an available date.</p>');
                updateDateTimeNextButtonState();
              }
            }
          },
        });
      }
    });
  }
  /**
   * Initialize contact form handling
   */
  function initContactHandling() {
    // Form field validation on blur
    $(document).on("blur", ".contact-form input[required]", function () {
      validateField($(this));
    });

    // Phone number formatting
    $(document).on("input", "#client-phone", function () {
      const input = $(this);
      let value = input.val();

      let cleaned = value.replace(/\D/g, "");

      let formatted = cleaned;
      if (cleaned.length > 4) {
        formatted = cleaned.substring(0, 4) + " " + cleaned.substring(4);
      }

      input.val(formatted);

      if (typeof bookingData !== "undefined") {
        bookingData.contact = bookingData.contact || {};
        bookingData.contact.phone = cleaned;
        const currentCountryCode = window.getSelectedCountryCode ? window.getSelectedCountryCode() : null;
        bookingData.contact.countryCode = currentCountryCode;
        bookingData.contact.fullPhone = currentCountryCode ? currentCountryCode + cleaned : cleaned;
      }
    });

    $(document).on("click", ".confirm-booking-btn", function () {
      const form = $("#booking-form")[0];
      const $form = $("#booking-form");
      let valid = true;

      $form.find(".input-error").text("");
      $(".global-form-error").hide();

      const fields = [
        { id: "client-name", label: "Name" },
        { id: "client-email", label: "Email" },
        { id: "client-phone", label: "Phone" },
        { id: "privacy-policy", label: "Privacy policy", type: "checkbox" },
      ];

      fields.forEach((field) => {
        const input = document.getElementById(field.id);
        const errorBlock = $(`.input-error[data-for="${field.id}"]`);

        if (field.type === "checkbox") {
          if (!input.checked) {
            errorBlock.text("You must accept the terms");
            valid = false;
          }
        } else if (!input.value.trim()) {
          errorBlock.text(`${field.label} is required`);
          valid = false;
        }
      });

      if (!valid) {
        $(".global-form-error").fadeIn();
        return;
      }

      bookingData.contact = {
        name: $("#client-name").val().trim(),
        phone: bookingData.contact.fullPhone || $("#client-phone").val().trim().replace(/\D/g, ""),
        email: $("#client-email").val().trim(),
        comment: $("#client-comment").val().trim(),
      };

      updateSummary();
      submitBooking();
    });
  }

  /**
   * Set up the edit buttons for master and date/time
   * This adds click handlers to navigate back to the appropriate steps
   */
  function setupEditButtons() {
    // Edit master button
    $(document).on("click", ".edit-master-btn", function () {
      // Add the current step to history so we can return
      bookingData.returnToContactStep = true;
      goToStep("master");
    });

    // Edit date/time button
    $(document).on("click", ".edit-datetime-btn", function () {
      bookingData.returnToContactStep = false;
      generateCalendar();
      goToStep("datetime");
    });

    // When returning from edit, check if we should go back to contact step
    $(document).on("bookingStepChanged", function (e, step) {
      if (bookingData.returnToContactStep && (step === "services" || step === "datetime") && bookingData.flowHistory.includes("contact")) {
        // Make sure we have the necessary data before returning
        if ((step === "master" && bookingData.staffId) || (step === "datetime" && bookingData.date && bookingData.time)) {
          // Return to contact step
          setTimeout(function () {
            goToStep("contact");
            updateSummary();
            bookingData.returnToContactStep = false;
          }, 100);
        }
      }
    });
  }

  /**
   * Validate a specific form field
   * @param {jQuery} field - The field to validate
   * @returns {boolean} - Whether the field is valid
   */
  function validateField(field) {
    const value = field.val().trim();
    const fieldId = field.attr("id");

    const fieldLabels = {
      "client-name": "Name",
      "client-email": "Email",
      "client-phone": "Phone",
      "client-comment": "Comment",
    };
    const fieldName = fieldLabels[fieldId] || fieldId;

    field.removeClass("error");
    field.next(".field-error").remove();

    // Required
    if (field.prop("required") && !value) {
      field.addClass("error");
      field.after(`<div class="field-error">${fieldName} is required</div>`);
      return false;
    }

    // Email
    if (fieldId === "client-email" && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        field.addClass("error");
        field.after('<div class="field-error">Please enter a valid email address</div>');
        return false;
      }
    }

    // Phone
    if (fieldId === "client-phone" && value) {
      if (value.replace(/\D/g, "").length < 7) {
        field.addClass("error");
        field.after('<div class="field-error">Please enter a valid phone number</div>');
        return false;
      }
    }

    return true;
  }

  /**
   * Update next button state based on service selection
   */
  function updateNextButtonState() {
    const hasServicesSelected = bookingData.services.length > 0;
    $('.booking-step[data-step="services"] .next-btn').prop("disabled", !hasServicesSelected);
  }

  /**
   * Update next button state based on master selection
   */
  function updateMasterNextButtonState() {
    const hasMasterSelected = bookingData.staffId !== null;
    $('.booking-step[data-step="master"] .next-btn').prop("disabled", !hasMasterSelected);
  }

  /**
   * Update next button state based on date/time selection
   */
  function updateDateTimeNextButtonState() {
    const hasDateTimeSelected = bookingData.date !== null && bookingData.time !== null;
    $('.booking-step[data-step="datetime"] .next-btn').prop("disabled", !hasDateTimeSelected);
  }

  /**
   * Update addon availability based on core service selection
   */
  function updateAddonAvailability() {
    if (bookingData.coreServices.length > 0) {
      // Enable all addon checkboxes
      $(".service-checkbox[data-is-addon='true']").prop("disabled", false);
      $(".service-item.addon-item").removeClass("disabled");
      debug("Addons enabled");

      // Show addon section if it exists
      $(".addon-services-container").show();
      $(".addon-title").show();
    } else {
      // Disable and uncheck all addon checkboxes
      $(".service-checkbox[data-is-addon='true']").prop("disabled", true).prop("checked", false);

      $(".service-item.addon-item").addClass("disabled").removeClass("selected");

      // Remove all addons from bookingData
      bookingData.addons = [];
      bookingData.services = bookingData.services.filter((service) => !service.isAddon);

      debug("Addons disabled and removed from selection");

      // Hide addon section
      $(".addon-services-container").hide();
      $(".addon-title").hide();
    }
  }

  /**
   * Show validation alert when validation fails
   * @param {string} message - Alert message to show
   */
  function showValidationAlert(message) {
    // Remove any existing alerts
    $(".validation-alert-overlay").remove();
    const dateMatch = message.match(/\((.*?)\)/);
    const cleanMessage = dateMatch ? `This service is not available at the selected time ${dateMatch[0]}` : message;

    let alertMessage = "Please choose a different time.";

    if (message.includes("phone") || message.includes("Phone")) {
      alertMessage = "Please check your phone number and try again.";
    } else if (message.includes("email") || message.includes("Email")) {
      alertMessage = "Please check your email address and try again.";
    } else if (message.includes("name") || message.includes("Name")) {
      alertMessage = "Please enter your name and try again.";
    } else if (message.includes("specialist") || message.includes("master")) {
      alertMessage = "Please select a specialist to continue.";
    } else if (message.includes("service")) {
      alertMessage = "Please select at least one service.";
    } else if (message.includes("date")) {
      alertMessage = "Please select a date to continue.";
    } else if (message.includes("time")) {
      alertMessage = "Please choose a different time.";
    } else if (message.includes("network") || message.includes("Network")) {
      alertMessage = "Please check your internet connection and try again.";
    } else if (message.includes("error") || message.includes("Error")) {
      alertMessage = "Something went wrong. Please try again.";
    }
    // Create custom alert
    const alertHtml = `
    <div class="validation-alert-overlay">
      <div class="validation-alert">
          <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="4" y="4" width="40" height="40" rx="20" fill="#FEE4E2"/>
      <rect x="4" y="4" width="40" height="40" rx="20" stroke="#FEF3F2" stroke-width="8"/>
      <path d="M23.9997 16.1667C28.3259 16.1667 31.8335 19.6743 31.8337 23.9998C31.8337 28.3253 28.326 31.8337 23.9997 31.8337C19.6735 31.8336 16.1667 28.3252 16.1667 23.9998C16.1669 19.6745 19.6736 16.1669 23.9997 16.1667ZM23.9997 16.5564C19.8946 16.5566 16.5565 19.8947 16.5563 23.9998C16.5563 28.105 19.8945 31.4439 23.9997 31.4441C28.105 31.4441 31.444 28.1051 31.444 23.9998C31.4439 19.8946 28.1049 16.5564 23.9997 16.5564ZM23.9987 26.5847C24.0868 26.5847 24.1717 26.6201 24.2341 26.6824C24.2964 26.7447 24.3317 26.8295 24.3317 26.9177C24.3317 27.0059 24.2964 27.0908 24.2341 27.1531C24.1717 27.2152 24.0868 27.2498 23.9987 27.2498C23.9328 27.2497 23.8692 27.2301 23.8151 27.1941L23.7643 27.1531C23.702 27.0908 23.6667 27.0059 23.6667 26.9177C23.6667 26.8516 23.6862 26.7874 23.7224 26.7332L23.7643 26.6824C23.8266 26.6202 23.9108 26.5848 23.9987 26.5847ZM23.9948 20.3337H23.9958C24.0109 20.3337 24.0258 20.3363 24.0397 20.3416L24.0778 20.364C24.0987 20.3822 24.1115 20.4073 24.1169 20.4343L24.1208 20.4773L24.1237 24.2097L24.1159 24.2556C24.1102 24.2701 24.1013 24.2831 24.0905 24.2947C24.0689 24.3179 24.0391 24.3325 24.0075 24.3347C23.9918 24.3358 23.9763 24.3335 23.9616 24.3289L23.9206 24.3074V24.3064C23.8967 24.2869 23.8804 24.2597 23.8757 24.2292L23.8727 24.1892L23.8708 20.4587L23.8806 20.4109L23.9069 20.3699C23.9185 20.3582 23.9328 20.3488 23.9479 20.3425L23.9948 20.3337Z" fill="#302F34" stroke="#DC3232"/>
      </svg>
       <div class="validation-alert-content">
            <div class="validation-alert-title">${cleanMessage}</div>
            <div class="validation-alert-message">${alertMessage}</div>
            <button class="validation-alert-button">OK</button>
        </div>
     
      </div>
    </div>
  `;

    $("body").append(alertHtml);

    // Bind click event to the button
    $(document).on("click", ".validation-alert-button", function () {
      $(".validation-alert-overlay").remove();
    });
  }
  /**
   * Reset the booking form to initial state
   */
  function resetBookingForm() {
    bookingData = {
      services: [],
      coreServices: [],
      addons: [],
      staffId: null,
      staffName: "",
      staffAvatar: "",
      staffLevel: 1,
      date: null,
      time: null,
      coupon: null,
      contact: {},
      flowHistory: ["initial"],
      initialOption: "services",
    };

    $("#coupon-code").val("");
    $(".coupon-feedback").hide();
    // Reset UI - hide all steps and show initial step
    $(".booking-step").removeClass("active");
    $('.booking-step[data-step="initial"]').addClass("active");

    // Reset option selection
    $(".booking-option-item").removeClass("active");
    $('.booking-option-item[data-option="services"]').addClass("active");
    $(".status-indicator").removeClass("active");
    $('.booking-option-item[data-option="services"] .status-indicator').addClass("active");

    // Reset service checkboxes
    $(".service-checkbox").prop("checked", false);
    $(".service-item").removeClass("selected");

    // Reset calendar
    $(".calendar-day").removeClass("selected");
    $(".time-slot").removeClass("selected");

    // Reset form fields
    $("#client-name, #client-phone, #client-email, #client-comment").val("");
    $(".field-error").remove();
    $(".contact-form input").removeClass("error");

    // Reset summary
    $(".selected-master-info").empty();
    $(".summary-services-list").empty();
    $(".summary-total-amount").text("0.00");

    // Hide addons initially
    $(".addon-title").hide();
    $(".addon-services-container").hide();
    $(".service-item.addon-item").addClass("disabled");
    $(".service-checkbox[data-is-addon='true']").prop("disabled", true);

    debug("Booking form reset");
  }

  /**
   * Go to a specific step in the booking flow
   * @param {string} step - Step name to navigate to
   */
  function goToStep(step) {
    $(".booking-step").removeClass("active");
    $(`.booking-step[data-step="${step}"]`).addClass("active");

    // Update next buttons based on initial flow
    if (step === "services") {
      const nextButtonText = bookingData.initialOption === "services" ? "Choose a master" : "Select date and time";
      $(`.booking-step[data-step="services"] .next-btn`).text(nextButtonText);

      // Update button state
      updateNextButtonState();
    }

    if (step === "master") {
      const nextButtonText = bookingData.initialOption === "master" ? "Select services" : "Select date and time";
      $(`.booking-step[data-step="master"] .next-btn`).text(nextButtonText);

      // Update button state
      updateMasterNextButtonState();
    }

    if (step === "datetime") {
      // Update button state
      updateDateTimeNextButtonState();
    }

    debug("Navigated to step", step);

    // Trigger custom event
    $(document).trigger("bookingStepChanged", [step]);
  }

  /**
   * Add a service to the booking
   * @param {string|number} id - Service ID
   * @param {string} title - Service title
   * @param {string|number} price - Service price
   * @param {string} currency - Currency code
   * @param {string} duration - Service duration
   * @param {string} wearTime - Service wear time
   * @param {boolean} isAddon - Whether this is an addon service
   * @param {string|number} altegioId - Altegio API ID
   * @returns {boolean} - Whether the service was added
   */
  /**
   * Updated addService function to properly handle add-ons
   */
  function addService(id, title, price, currency, duration, wearTime, isAddon, altegioId, desc = "") {
    try {
      if (!id || !title || !price) return false;

      const existingIndex = bookingData.services.findIndex((s) => s.id == id);
      if (existingIndex === -1) {
        const newService = {
          id,
          altegioId: altegioId || id,
          title,
          price,
          currency: currency || "SGD",
          isAddon: isAddon || false,
          duration,
          wearTime,
          desc,
        };

        bookingData.services.push(newService);

        if (isAddon) {
          bookingData.addons.push(newService);
        } else {
          bookingData.coreServices.push(newService);
        }

        return true;
      }

      return false;
    } catch (error) {
      console.error("Error in addService:", error);
      return false;
    }
  }

  /**
   * Updated removeService function to properly handle add-ons
   */
  function removeService(id) {
    // Find the service to check if it's an addon
    const service = bookingData.services.find((s) => s.id == id);

    // Remove from main services array
    bookingData.services = bookingData.services.filter((service) => service.id != id);

    // Also remove from core or addon arrays
    if (service && service.isAddon) {
      bookingData.addons = bookingData.addons.filter((addon) => addon.id != id);
    } else {
      bookingData.coreServices = bookingData.coreServices.filter((core) => core.id != id);
    }

    debug("Service removed", id);
  }

  /**
   * Remove a service from the booking
   * @param {string|number} id - Service ID to remove
   */
  function removeService(id) {
    // Find the service to check if it's an addon
    const service = bookingData.services.find((s) => s.id == id);

    // Remove from main services array
    bookingData.services = bookingData.services.filter((service) => service.id != id);

    // Also remove from core or addon arrays
    if (service && service.isAddon) {
      bookingData.addons = bookingData.addons.filter((addon) => addon.id != id);
    } else {
      bookingData.coreServices = bookingData.coreServices.filter((core) => core.id != id);
    }

    debug("Service removed", id);
  }

  /**
   * Select a staff member (master)
   * @param {string|number} id - Staff ID
   * @param {string} name - Staff name
   * @param {string} avatar - Staff avatar URL
   * @param {string|number} level - Staff level/stars
   */
  function selectStaff(id, name, avatar, level, specialization) {
    bookingData.staffId = id;
    bookingData.staffName = name;
    bookingData.staffAvatar = avatar;
    bookingData.staffSpecialization = specialization || "";
    if (typeof level === "number") {
      bookingData.staffLevel = level;
    } else {
      const parsedLevel = parseInt(level);
      bookingData.staffLevel = isNaN(parsedLevel) ? 1 : parsedLevel;
    }

    debug("Staff selected", { id, name, level, specialization });

    updateSummary();
  }

  // Add retry counters for all loading functions
  let staffLoadRetryCount = 0;
  let servicesLoadRetryCount = 0;
  const MAX_STAFF_RETRIES = 2;
  const MAX_SERVICES_RETRIES = 2;
  const STAFF_RETRY_DELAY = 1000; // 1 second
  const SERVICES_RETRY_DELAY = 1000; // 1 second
  // Add retry counter for time slots
  let timeSlotsRetryCount = 0;
  const MAX_TIMESLOTS_RETRIES = 2;
  const TIMESLOTS_RETRY_DELAY = 1000; // 1 second

  /**
   * Load staff for selected services with loading overlay and retry mechanism
   * This calls the Altegio API to get available staff for the selected services
   */
  function loadStaffForServices() {
    if (bookingData.services.length === 0) return;

    const serviceIds = bookingData.services.map((service) => service.altegioId || service.id).join(",");

    debug("Loading staff for services", serviceIds);

    // Show loading overlay
    $(".loading-overlay").show();
    $(".staff-list").html('<p class="loading-message">Loading specialists...</p>');

    // Reset retry counter for new request
    staffLoadRetryCount = 0;

    performStaffLoadRequest(serviceIds);
  }

  /**
   * Perform the actual staff loading request with retry logic
   * @param {string} serviceIds - Comma separated service IDs
   */
  function performStaffLoadRequest(serviceIds) {
    $.ajax({
      url: config.apiEndpoint,
      type: "POST",
      data: {
        action: "get_filtered_staff",
        service_id: serviceIds,
        nonce: config.nonce,
      },
      success: function (response) {
        // Hide loading overlay on success
        $(".loading-overlay").hide();

        if (response.success && response.data && Array.isArray(response.data.data)) {
          renderStaff(response.data.data);
        } else {
          $(".staff-list").html('<p class="no-items-message">No specialists available for the selected services.</p>');
          debug("Failed to load staff from API", response);
        }
      },
      error: function (xhr, status, error) {
        $(".loading-overlay").hide();
        debug("AJAX error loading staff", { status, error });

        // Retry logic
        staffLoadRetryCount++;
        if (staffLoadRetryCount <= MAX_STAFF_RETRIES) {
          setTimeout(() => {
            debug("Retrying staff load request", { attempt: staffLoadRetryCount });
            performStaffLoadRequest(serviceIds);
          }, STAFF_RETRY_DELAY);
        } else {
          $(".staff-list").html('<p class="no-items-message">Error loading specialists.</p>');
        }
      },
    });
  }

  /**
   * Load services for a specific master with loading overlay and retry mechanism
   * @param {string|number} masterId - Master ID to load services for
   */
  function loadServicesForMaster(masterId) {
    debug("Loading services for master", masterId);

    // Show loading overlay
    $(".loading-overlay").show();
    $(".booking-popup .services-list").html('<p class="loading-message">Loading services...</p>');

    // Reset retry counter for new request
    servicesLoadRetryCount = 0;

    performServicesLoadRequest(masterId);
  }

  /**
   * Perform the actual services loading request with retry logic
   * @param {string|number} masterId - Master ID
   */
  function performServicesLoadRequest(masterId) {
    $.ajax({
      url: booking_params.ajax_url,
      method: "POST",
      data: {
        action: "get_filtered_services",
        staff_id: masterId,
        nonce: booking_params.nonce,
      },
      success: function (response) {
        // Hide loading overlay on success
        $(".loading-overlay").hide();

        if (response.success && response.data && response.data.html) {
          $(".booking-popup .services-list").html(response.data.html);
          updateAddonAvailability();
          updateNextButtonState();
          // Reset retry counter on successful load
          servicesLoadRetryCount = 0;
        } else {
          // Try to retry if data is empty but request was "successful"
          if (servicesLoadRetryCount < MAX_SERVICES_RETRIES) {
            servicesLoadRetryCount++;
            debug(`Retrying services load attempt ${servicesLoadRetryCount}/${MAX_SERVICES_RETRIES} - empty data`);

            setTimeout(() => {
              performServicesLoadRequest(masterId);
            }, SERVICES_RETRY_DELAY);
          } else {
            console.error("Services response details:", response);
            $(".booking-popup .services-list").html('<p class="no-items-message">No services available for this master.</p>');
          }
        }
      },
      error: function (xhr, status, error) {
        debug("AJAX error loading services", { status, error });

        // Retry on error
        if (servicesLoadRetryCount < MAX_SERVICES_RETRIES) {
          servicesLoadRetryCount++;
          debug(`Retrying services load attempt ${servicesLoadRetryCount}/${MAX_SERVICES_RETRIES} after error`);

          // Keep loading overlay visible during retry
          setTimeout(() => {
            performServicesLoadRequest(masterId);
          }, SERVICES_RETRY_DELAY);
        } else {
          // Hide loading overlay after all retries failed
          $(".loading-overlay").hide();
          console.error("AJAX Error:", {
            status: status,
            error: error,
            responseText: xhr.responseText,
          });
          $(".booking-popup .services-list").html('<p class="no-items-message">Error loading services. Please try again.</p>');
        }
      },
    });
  }

  /**
   * Load time slots for selected date and staff with loading overlay and retry mechanism
   * @param {string} date - Date in YYYY-MM-DD format
   */
  function loadTimeSlots(date) {
    if (!bookingData.staffId || bookingData.services.length === 0) {
      console.warn("Staff or service not selected");
      $(".time-sections").html('<p class="error-message">Please select a staff and service first.</p>');
      return;
    }

    if (!date) {
      $(".time-sections").html('<p class="error-message">Please select a date.</p>');
      return;
    }

    // Include ALL services (core services + addons) for time slot calculation
    const serviceIds = bookingData.services.map((s) => s.altegioId || s.id);

    if (!serviceIds.length) {
      $(".time-sections").html('<p class="error-message">Please select at least one service.</p>');
      return;
    }

    // Show loading overlay
    $(".loading-overlay").show();
    $(".time-sections").html('<p class="loading-message">Loading available time slots...</p>');

    // Reset retry counter for new request
    timeSlotsRetryCount = 0;

    performLoadTimeSlotsRequest(date, serviceIds);
  }

  /**
   * Perform the actual time slots loading request with retry logic
   * @param {string} date - Date in YYYY-MM-DD format
   * @param {Array} serviceIds - Array of service IDs
   */
  function performLoadTimeSlotsRequest(date, serviceIds) {
    $.ajax({
      url: booking_params.ajax_url,
      method: "POST",
      data: {
        action: "get_time_slots",
        nonce: booking_params.nonce,
        staff_id: bookingData.staffId,
        date: date,
        service_ids: serviceIds,
      },
      success: function (response) {
        // Hide loading overlay on success
        $(".loading-overlay").hide();

        if (response.success) {
          let slots = [];

          if (response.data && Array.isArray(response.data)) {
            slots = response.data;
          } else if (response.data && Array.isArray(response.data.slots)) {
            slots = response.data.slots;
          } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
            slots = response.data.data;
          }

          if (slots.length > 0) {
            renderTimeSlots(slots);
            // Reset retry counter on successful load
            timeSlotsRetryCount = 0;
          } else {
            $(".time-sections").html('<p class="error-message">No available time slots for this day.</p>');
          }
        } else {
          // Try to retry if request was not successful
          if (timeSlotsRetryCount < MAX_TIMESLOTS_RETRIES) {
            timeSlotsRetryCount++;
            debug(`Retrying time slots load attempt ${timeSlotsRetryCount}/${MAX_TIMESLOTS_RETRIES} - unsuccessful response`);

            setTimeout(() => {
              performLoadTimeSlotsRequest(date, serviceIds);
            }, TIMESLOTS_RETRY_DELAY);
          } else {
            $(".time-sections").html('<p class="error-message">Error loading time slots. Please try again later.</p>');
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading time slots:", error, xhr.responseText);

        // Retry on error
        if (timeSlotsRetryCount < MAX_TIMESLOTS_RETRIES) {
          timeSlotsRetryCount++;
          debug(`Retrying time slots load attempt ${timeSlotsRetryCount}/${MAX_TIMESLOTS_RETRIES} after error`);

          // Keep loading overlay visible during retry
          setTimeout(() => {
            performLoadTimeSlotsRequest(date, serviceIds);
          }, TIMESLOTS_RETRY_DELAY);
        } else {
          // Hide loading overlay after all retries failed
          $(".loading-overlay").hide();
          $(".time-sections").html('<p class="error-message">Error loading time slots. Please try again later.</p>');
        }
      },
    });
  }

  /**
   * Render staff list
   * @param {Array} staffList - Array of staff objects from API
   */
  function renderStaff(staffList) {
    if (!staffList || staffList.length === 0) {
      $(".staff-list").html('<p class="no-items-message">No specialists available for the selected services.</p>');
      return;
    }

    let html = "";

    // Show "Any master" only if no specific master is selected
    const shouldShowAnyMaster = !bookingData.staffId || bookingData.staffId === "any";

    if (shouldShowAnyMaster) {
      const isAnyMasterSelected = bookingData.staffId === "any" || !bookingData.staffId;

      html = `
      <label class="staff-item any-master first${isAnyMasterSelected ? " selected" : ""}" data-staff-id="any" data-staff-level="1">
        <input type="radio" name="staff"${isAnyMasterSelected ? " checked" : ""}>
        <div class="staff-radio-content">
          <div class="staff-avatar circle yellow-bg">
            <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M16.4891 6.89062C16.3689 8.55873 15.1315 9.84375 13.7821 9.84375C12.4327 9.84375 11.1932 8.55914 11.0751 6.89062C10.952 5.15525 12.1566 3.9375 13.7821 3.9375C15.4075 3.9375 16.6122 5.18684 16.4891 6.89062Z" stroke="#302F34" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M13.7811 12.4688C11.1081 12.4688 8.53765 13.7964 7.8937 16.3821C7.80839 16.7241 8.0229 17.0625 8.37441 17.0625H19.1882C19.5397 17.0625 19.753 16.7241 19.6689 16.3821C19.0249 13.755 16.4545 12.4688 13.7811 12.4688Z" stroke="#302F34" stroke-miterlimit="10" />
              <path d="M8.20211 7.62645C8.10614 8.95863 7.10618 10.0078 6.02828 10.0078C4.95039 10.0078 3.94879 8.95904 3.85446 7.62645C3.75643 6.24053 4.72973 5.25 6.02828 5.25C7.32684 5.25 8.30014 6.26596 8.20211 7.62645Z" stroke="#302F34" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M8.44962 12.5507C7.70929 12.2115 6.8939 12.0811 6.0297 12.0811C3.89689 12.0811 1.842 13.1413 1.32726 15.2065C1.25958 15.4796 1.43103 15.7499 1.71157 15.7499H6.31681" stroke="#302F34" stroke-miterlimit="10" stroke-linecap="round" />
            </svg>
          </div>
          <div class="staff-info">
            <h4 class="staff-name">Any master</h4>
          </div>
          <span class="radio-indicator"></span>
        </div>
      </label>
    `;
    }

    // Render all available masters
    staffList.forEach(function (staff) {
      const isSelected = bookingData.staffId == staff.id ? " selected" : "";
      const staffLevel = Number.isInteger(staff.level) ? staff.level : 1;
      const levelTitle = levelTitles[staffLevel] || "";

      let priceModifier = "";
      const modifier = percentMap[staffLevel];

      if (typeof modifier === "number") {
        const sign = modifier > 0 ? "+" : "";
        priceModifier = `<div class="staff-price-modifier">${sign}${modifier}% to price</div>`;
      }

      html += `
        <label class="staff-item${isSelected}" data-staff-id="${staff.id}" data-staff-level="${staffLevel}">
          <input type="radio" name="staff"${isSelected ? " checked" : ""}>
          <div class="staff-radio-content">
            <div class="staff-avatar">
              ${staff.avatar ? `<img src="${staff.avatar}" alt="${staff.name}">` : ""}
            </div>
            <div class="staff-info">
              <h4 class="staff-name">${staff.name}</h4>
              <div class="staff-specialization">
               <div class="staff-stars">
                      ${generateStarsHtml(staffLevel)}
              </div>
                ${levelTitle ? `<span class="studio-name">(${levelTitle})</span>` : ""}
              </div>
            </div>
            ${priceModifier}
            <span class="radio-indicator"></span>
          </div>
        </label>
      `;
    });

    $(".staff-list").html(html);

    // Update next button state
    updateMasterNextButtonState();
  }

  /**
   * Select a date
   * @param {string} date - Date in YYYY-MM-DD format
   */
  function selectDate(date) {
    bookingData.date = date;
    debug("Date selected", date);
  }

  /**
   * Select a time
   * @param {string} time - Time in HH:MM format
   */
  function selectTime(time) {
    bookingData.time = time;
    debug("Time selected", time);
  }

  /**
   * Generate calendar for date selection
   * This creates a month view calendar starting from current month
   */
  function generateCalendar() {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();

    const todayFormatted = formatDate(today);

    bookingData.date = todayFormatted;

    renderCalendar(currentMonth, currentYear);

    setTimeout(() => {
      $(`.calendar-day[data-date="${todayFormatted}"]`).addClass("selected");

      loadTimeSlots(todayFormatted);
    }, 50);

    debug("Calendar generated + today selected + time slots loaded", todayFormatted);
  }

  /**
   * Navigate calendar to previous or next month
   * @param {number} direction - Direction to navigate (-1 for prev, 1 for next)
   */
  function navigateCalendar(direction) {
    const monthText = $(".month-header span").text();
    const [month, year] = monthText.split(" ");

    const monthIndex = getMonthIndex(month);
    let newMonth = monthIndex + direction;
    let newYear = parseInt(year, 10);

    if (newMonth < 0) {
      newMonth = 11;
      newYear--;
    } else if (newMonth > 11) {
      newMonth = 0;
      newYear++;
    }

    renderCalendar(newMonth, newYear);
    debug("Calendar navigated to", { month: newMonth, year: newYear });
  }

  /**
   * Get month index from name
   * @param {string} monthName - Month name
   * @returns {number} - Month index (0-11)
   */
  function getMonthIndex(monthName) {
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    return months.indexOf(monthName);
  }

  /**
   * Render calendar for specified month and year
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  /**
   * Render calendar for specified month and year
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  function renderCalendar(month, year) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();

    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    $(".month-header span").text(monthNames[month] + " " + year);

    let html = "";

    // Empty days at the beginning
    for (let i = 0; i < startDay; i++) {
      html += '<div class="calendar-day empty"></div>';
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Render all days of the month
    for (let i = 1; i <= daysInMonth; i++) {
      const date = new Date(year, month, i);
      const dateStr = formatDate(date);

      const isToday = date.getTime() === today.getTime();
      const isPast = date < today;

      let classes = "calendar-day";
      if (isToday) classes += " today";
      if (isPast) classes += " disabled";

      if (bookingData.date === dateStr && !isPast) {
        classes += " selected";
      }

      html += `<div class="${classes}" data-date="${dateStr}">${i}</div>`;
    }

    $(".calendar-grid").html(html);

    // Check availability for all days in this month (if we have staff and services selected)
    if (bookingData.staffId && bookingData.services.length > 0) {
      // Small delay to ensure DOM is updated
      setTimeout(() => {
        checkDayAvailability(month, year);
      }, 100);
    }

    // Load time slots for currently selected date if it's in this month
    if (bookingData.date) {
      const currentMonth = new Date(bookingData.date).getMonth();
      const currentYear = new Date(bookingData.date).getFullYear();

      if (currentMonth === month && currentYear === year) {
        loadTimeSlots(bookingData.date);
      }
    }
  }

  /**
   * Format date as YYYY-MM-DD
   * @param {Date} date - Date object
   * @returns {string} - Formatted date
   */
  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return year + "-" + month + "-" + day;
  }

  /**
   * Load time slots for selected date and staff
   * @param {string} date - Date in YYYY-MM-DD format
   */

  /**
   * Load time slots for selected date and staff
   * @param {string} date - Date in YYYY-MM-DD format
   */

  function loadTimeSlots(date) {
    if (!bookingData.staffId || bookingData.services.length === 0) {
      console.warn("Staff or service not selected");
      $(".time-sections").html('<p class="error-message">Please select a staff and service first.</p>');
      return;
    }

    if (!date) {
      $(".time-sections").html('<p class="error-message">Please select a date.</p>');
      return;
    }

    // Include ALL services (core services + addons) for time slot calculation
    const serviceIds = bookingData.services.map((s) => s.altegioId || s.id);

    if (!serviceIds.length) {
      $(".time-sections").html('<p class="error-message">Please select at least one service.</p>');
      return;
    }

    // Show loading overlay
    $(".loading-overlay").show();
    $(".time-sections").html('<p class="loading-message">Loading available time slots...</p>');

    // Reset retry counter for new request
    timeSlotsRetryCount = 0;

    performLoadTimeSlotsRequest(date, serviceIds);
  }

  /**
   * Perform the actual time slots loading request with retry logic
   * @param {string} date - Date in YYYY-MM-DD format
   * @param {Array} serviceIds - Array of service IDs
   */
  function performLoadTimeSlotsRequest(date, serviceIds) {
    $.ajax({
      url: booking_params.ajax_url,
      method: "POST",
      data: {
        action: "get_time_slots",
        nonce: booking_params.nonce,
        staff_id: bookingData.staffId,
        date: date,
        service_ids: serviceIds,
      },
      success: function (response) {
        // Hide loading overlay on success
        $(".loading-overlay").hide();

        if (response.success) {
          let slots = [];

          if (response.data && Array.isArray(response.data)) {
            slots = response.data;
          } else if (response.data && Array.isArray(response.data.slots)) {
            slots = response.data.slots;
          } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
            slots = response.data.data;
          }

          if (slots.length > 0) {
            renderTimeSlots(slots);
            // Reset retry counter on successful load
            timeSlotsRetryCount = 0;
          } else {
            $(".time-sections").html('<p class="error-message">No available time slots for this day.</p>');
          }
        } else {
          // Try to retry if request was not successful
          if (timeSlotsRetryCount < MAX_TIMESLOTS_RETRIES) {
            timeSlotsRetryCount++;
            debug(`Retrying time slots load attempt ${timeSlotsRetryCount}/${MAX_TIMESLOTS_RETRIES} - unsuccessful response`);

            setTimeout(() => {
              performLoadTimeSlotsRequest(date, serviceIds);
            }, TIMESLOTS_RETRY_DELAY);
          } else {
            $(".time-sections").html('<p class="error-message">Error loading time slots. Please try again later.</p>');
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading time slots:", error, xhr.responseText);

        // Retry on error
        if (timeSlotsRetryCount < MAX_TIMESLOTS_RETRIES) {
          timeSlotsRetryCount++;
          debug(`Retrying time slots load attempt ${timeSlotsRetryCount}/${MAX_TIMESLOTS_RETRIES} after error`);

          // Keep loading overlay visible during retry
          setTimeout(() => {
            performLoadTimeSlotsRequest(date, serviceIds);
          }, TIMESLOTS_RETRY_DELAY);
        } else {
          // Hide loading overlay after all retries failed
          $(".loading-overlay").hide();
          $(".time-sections").html('<p class="error-message">Error loading time slots. Please try again later.</p>');
        }
      },
    });
  }

  /**
   * Render time slots
   * @param {Array} slots - Array of time slot objects from API
   */
  function renderTimeSlots(slots) {
    const $target = $(".time-sections");
    if (!slots || slots.length === 0) {
      $target.html('<div class="no-slots-message">This servise is not available at the selected time.</div>');
      return;
    }

    // Process the time slots from API format to simple time strings
    const times = [];
    slots.forEach((slot) => {
      // Handle different data formats from API
      if (typeof slot === "object" && slot.time) {
        times.push(slot.time);
      } else if (typeof slot === "string") {
        // If it's a string like "2025-05-23 10:00:00", extract just the time part
        const timePart = slot.split(" ")[1];
        if (timePart) {
          times.push(timePart.slice(0, 5)); // Get just HH:MM
        }
      }
    });

    // Group times by period of day
    const grouped = {
      Morning: times.filter((t) => parseInt(t.split(":")[0]) < 12),
      Afternoon: times.filter((t) => {
        const h = parseInt(t.split(":")[0]);
        return h >= 12 && h < 17;
      }),
      Evening: times.filter((t) => parseInt(t.split(":")[0]) >= 17),
    };

    // Build HTML for each time group
    let html = "";
    for (const [label, group] of Object.entries(grouped)) {
      if (!group.length) continue;
      html += `<div class="time-group">
      <div class="time-group-title">${label}</div>
      <div class="time-slot-list">${group.map((t) => `<div class="time-slot" data-time="${t}">${t}</div>`).join("")}</div>
    </div>`;
    }

    $target.html(html);

    // If a time was previously selected, mark it as selected
    if (bookingData.time) {
      $(`.time-slot[data-time="${bookingData.time}"]`).addClass("selected");
    }
  }

  /**
   * Calculate price with adjustment based on staff level
   * @param {string|number} basePrice - Original price
   * @param {string|number} staffLevel - Staff level
   * @returns {string} - Adjusted price with two decimal places
   */
  function calculateAdjustedPrice(basePrice, staffLevel) {
    // Get numeric price and level
    const numericPrice = parseFloat(basePrice.toString().replace(/[^\d.]/g, ""));
    const level = parseInt(staffLevel) || 1;

    if (isNaN(numericPrice)) {
      return basePrice; // Return original if not a valid number
    }

    // Calculate adjustment percentage based on level
    let adjustmentPercent = 0;
    if (level > 1) {
      adjustmentPercent = (level - 1) * config.priceAdjustmentPerLevel;
    }

    // Calculate adjusted price
    const adjustment = numericPrice * (adjustmentPercent / 100);
    const adjustedPrice = numericPrice + adjustment;

    debug("Price adjustment", {
      basePrice,
      level,
      adjustmentPercent,
      adjustment,
      result: adjustedPrice.toFixed(2),
    });

    return adjustedPrice.toFixed(2);
  }

  /**
   * Validate date and time step
   * @returns {boolean} - Whether all required fields are filled
   */
  function validateDateTimeStep() {
    if (!bookingData.date) {
      showValidationAlert("Please select a date");
      return false;
    }

    if (!bookingData.time) {
      showValidationAlert("Please select a time");
      return false;
    }

    return true;
  }

  /**
   * Format date for display in a user-friendly way
   * @param {string} dateStr - Date in YYYY-MM-DD format
   * @returns {string} Formatted date (e.g., "5 May, Monday")
   */
  function formatDateDisplay(dateStr) {
    if (!dateStr) return "";

    const date = new Date(dateStr);
    const day = date.getDate();
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const month = monthNames[date.getMonth()];
    const dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    const dayOfWeek = dayNames[date.getDay()];

    return `${day} ${month}, ${dayOfWeek}`;
  }

  /**
   * Format time range for display
   * @param {string} timeStr - Time in HH:MM format
   * @returns {string} Formatted time range (e.g., "12:00-13:30")
   */
  function formatTimeRange(timeStr) {
    if (!timeStr) return "";

    // Calculate end time based on service duration if available
    let endTime = "";
    let duration = 0;

    // Get average duration from core services
    if (bookingData.coreServices && bookingData.coreServices.length > 0) {
      bookingData.coreServices.forEach((service) => {
        if (service.duration) {
          // Parse duration from string or number
          let serviceDuration = 0;
          if (typeof service.duration === "string") {
            const match = service.duration.match(/(\d+)/);
            if (match) serviceDuration = parseInt(match[1]);
          } else if (typeof service.duration === "number") {
            serviceDuration = service.duration;
          }
          duration += serviceDuration;
        }
      });

      // Use average duration if multiple services
      if (bookingData.coreServices.length > 1) {
        duration = Math.ceil(duration / bookingData.coreServices.length);
      }
    }

    // If we have duration info, calculate end time
    if (duration > 0 && timeStr) {
      const [hours, minutes] = timeStr.split(":").map(Number);
      const startDate = new Date();
      startDate.setHours(hours, minutes, 0);

      const endDate = new Date(startDate.getTime() + duration * 60000);
      endTime = `${String(endDate.getHours()).padStart(2, "0")}:${String(endDate.getMinutes()).padStart(2, "0")}`;

      // Return time range
      return `${timeStr}-${endTime}`;
    }

    // Return just the time if we can't calculate a range
    return timeStr;
  }
  function selectRandomMaster() {
    const $availableStaff = $(".staff-item").not(".any-master");

    if ($availableStaff.length === 0) return;

    const randomIndex = Math.floor(Math.random() * $availableStaff.length);
    const $randomStaff = $availableStaff.eq(randomIndex);

    $randomStaff.find('input[type="radio"]').prop("checked", true).trigger("change").trigger("click");
    $randomStaff.addClass("selected");

    const staffId = $randomStaff.data("staff-id");
    const staffName = $randomStaff.find(".staff-name").text().trim();
    const staffLevel = typeof bookingData.staffLevel === "number" ? bookingData.staffLevel : 1;

    const specialization = $randomStaff.find(".stars span").text().trim().replace(/[()]/g, "");

    bookingData.staffId = staffId;
    bookingData.staffName = staffName;
    bookingData.staffLevel = staffLevel;
    bookingData.staffSpecialization = specialization;

    return staffId;
  }

  $(document).on("click", ".booking-step[data-step='master'] .next-btn", function () {
    if (bookingData.staffId === "any") {
      const selectedId = selectRandomMaster();
      if (!selectedId) {
        showValidationAlert("No available masters.");
        return;
      }
    }

    goToStep("datetime");
    generateCalendar();
    updateSummary();
  });

  /**
   * Updated updateSummary function with proper add-on handling
   */
  function updateSummary() {
    const masterBox = $(".summary-master .master-info");
    const dateTimeBox = $(".booking-date-time");
    const serviceList = $(".summary-services-list").not(".summary-addons");
    const addonsList = $(".summary-addons");
    const masterBonusEl = $(".master-bonus");
    const masterPercent = $(".summary-total-group .percent");
    const totalAmountEl = $(".summary-total-amount");

    if (bookingData.staffAvatar) {
      masterBox.find(".avatar").attr("src", bookingData.staffAvatar);
    } else {
      masterBox.find(".avatar").attr("src", "https://be.cdn.alteg.io/images/no-master-sm.png");
    }

    masterBox.find(".name").text(bookingData.staffName || "Any Master");

    const stars = generateStarsHtml(bookingData.staffLevel);
    masterBox.find(".stars").html(stars);

    const title = levelTitles[bookingData.staffLevel];
    masterBox.find(".stars-name").text(title ? `(${title})` : "");

    const dateStr = formatDateDisplay(bookingData.date);
    const timeStr = formatTimeRange(bookingData.time);
    dateTimeBox.find(".calendar-date").text(dateStr);
    dateTimeBox.find(".calendar-time").text(timeStr);

    let serviceHTML = "";
    let addonHTML = "";
    let basePrice = 0;
    let masterMarkupAmount = 0; // Змінено: тільки для розрахунку націнки майстра

    let percent = percentMap[bookingData.staffLevel];
    if (typeof percent === "undefined") {
      percent = 0;
    }
    if (bookingData.staffLevel === -1) {
      percent = -50;
    }

    // Core services - показуємо базову ціну, розраховуємо націнку окремо
    bookingData.coreServices.forEach((service) => {
      let price = parseFloat(service.price) || 0;
      basePrice += price;

      // Розраховуємо націнку тільки для відображення в Master category
      let serviceMarkup = price * (percent / 100);
      masterMarkupAmount += serviceMarkup;

      const itemHTML = `
            <div class="summary-service-item">
                <div class="service-info">
                    <div class="service-title">
                        <strong>${service.title}</strong>
                           <strong class="service-price">
                              ${price.toFixed(2)} ${service.currency || "SGD"}
                        </strong>
                    </div>
                    ${service.duration ? `<div class="meta"><strong>Duration:</strong> ${service.duration} min</div>` : ""}
                    ${service.wearTime ? `<div class="meta"><strong>Wear time:</strong> ${service.wearTime}</div>` : ""}
                    ${service.desc ? `<div class="meta service-description">${service.desc}</div>` : ""}
                </div>
             
            </div>
        `;

      serviceHTML += itemHTML;
    });

    // Add-ons - показуємо базову ціну, БЕЗ націнки
    if (bookingData.addons && bookingData.addons.length > 0) {
      bookingData.addons.forEach((addon) => {
        let price = parseFloat(addon.price) || 0;
        basePrice += price;

        // БЕЗ націнки для add-on'ів

        const addonItemHTML = `
                <div class="summary-service-item addon-service">
                    <div class="service-info">
                        <strong>Add-on: ${addon.title}</strong>
                        ${addon.duration ? `<div class="meta"><strong>Duration:</strong> ${addon.duration} min</div>` : ""}
                        ${addon.wearTime ? `<div class="meta"><strong>Wear time:</strong> ${addon.wearTime}</div>` : ""}
                        ${addon.desc ? `<div class="meta service-description">${addon.desc}</div>` : ""}
                    </div>
                    <div class="service-price">
                        <strong>${price.toFixed(2)} ${addon.currency || "SGD"}</strong>
                    </div>
                </div>
            `;

        addonHTML += addonItemHTML;
      });
    }

    serviceList.html(serviceHTML || '<p class="no-services">No services selected</p>');

    if (addonHTML) {
      addonsList.html(addonHTML).show();
    } else {
      addonsList.empty().hide();
    }

    // Показуємо націнку майстра окремо
    masterPercent.text(`${percent > 0 ? "+" : ""}${percent}`);
    masterBonusEl.text(`${masterMarkupAmount.toFixed(2)} SGD`);

    // Розраховуємо загальну суму: базова ціна + націнка майстра
    let adjustedTotal = basePrice + masterMarkupAmount;

    // Застосовуємо знижку купону якщо є
    let discountAmount = 0;
    if (bookingData.coupon && bookingData.coupon.value > 0) {
      const discountPercent = bookingData.coupon.value;
      discountAmount = adjustedTotal * (discountPercent / 100);
      adjustedTotal = Math.max(0, adjustedTotal - discountAmount);

      $(".summary-coupon").show();
      $(".summary-coupon-group").show();
      $(".coupon-discount-amount").text(`- ${discountAmount.toFixed(2)} SGD`);
      $(".coupon-desc").text(`Applied coupon: ${bookingData.coupon.code} (-${discountPercent}% discount)`);
      $(".coupon-discount").text(`- ${discountAmount.toFixed(2)} SGD`);
    } else {
      $(".summary-coupon").hide();
      $(".summary-coupon-group").show();
      $(".coupon-desc").text(`Do you have a coupon? Enter it here and get a discount on services.`);
    }

    totalAmountEl.text(`${adjustedTotal.toFixed(2)} SGD`);

    bookingData.totalWithTax = adjustedTotal;
    bookingData.basePrice = basePrice;
    bookingData.adjustedPrice = adjustedTotal;
    bookingData.priceAdjustment = masterMarkupAmount;
    bookingData.adjustmentPercent = percent;

    if (bookingData.contact) {
      const cleaned = (bookingData.contact.comment || "").replace(/Price information:[\s\S]*/i, "").trim();
      $("#client-name").val(bookingData.contact.name || "");
      $("#client-phone").val(bookingData.contact.phone || "");
      $("#client-email").val(bookingData.contact.email || "");
      $("#client-comment").val(cleaned);
    }
  }

  function submitBooking() {
    $(".confirm-booking-btn").prop("disabled", true).text("Processing...");
    $(".loading-overlay").show();

    if (!bookingData.staffId || !bookingData.date || !bookingData.time || bookingData.services.length === 0) {
      showValidationAlert("Missing booking information. Please complete all steps.");
      $(".confirm-booking-btn").prop("disabled", false).text("Book an appointment");
      $(".loading-overlay").hide();
      return;
    }

    const currentCountryCode = window.getSelectedCountryCode ? window.getSelectedCountryCode() : null;
    const phoneNumber = $("#client-phone").val().trim().replace(/\D/g, "");
    const fullPhoneNumber = currentCountryCode ? currentCountryCode + phoneNumber : phoneNumber;

    if (!currentCountryCode) {
      showValidationAlert("Please select a country for your phone number.");
      $(".confirm-booking-btn").prop("disabled", false).text("Book an appointment");
      $(".loading-overlay").hide();
      return;
    }

    const basePrice = calculateBasePrice();
    const staffLevel = bookingData.staffLevel != null ? parseInt(bookingData.staffLevel) : 1;
    const adjustmentPercent = percentMap[staffLevel] || 0;

    let masterMarkupAmount = 0;
    bookingData.coreServices.forEach((service) => {
      const servicePrice = parseFloat(service.price);
      masterMarkupAmount += servicePrice * (adjustmentPercent / 100);
    });

    const adjustedPriceBeforeDiscount = basePrice + masterMarkupAmount;

    const formattedServices = [...bookingData.coreServices, ...bookingData.addons].map((service) => ({
      id: parseInt(service.id),
      altegio_id: service.altegioId || service.id,
      title: service.title,
      price: parseFloat(service.price),
      currency: service.currency || "SGD",
      duration: service.duration || "",
      is_addon: service.isAddon || false,
    }));

    const cleanComment = $("#client-comment")
      .val()
      .trim()
      .replace(/Price information:[\s\S]*$/i, "")
      .trim();

    let galleryInfo = "";
    if (bookingData.initialOption === "master" && bookingData.galleryTitle) {
      galleryInfo = `Gallery selection: ${bookingData.galleryTitle}\n\n`;
    }

    // Build service descriptions - separate core services and add-ons
    const coreServiceDescriptions = bookingData.coreServices
      .map((service) => {
        const servicePrice = parseFloat(service.price);
        return `- ${service.title}: ${servicePrice.toFixed(2)} SGD`;
      })
      .join("\n");

    const addonServiceDescriptions = bookingData.addons
      .map((addon) => {
        const servicePrice = parseFloat(addon.price);
        return `- ${addon.title}: ${servicePrice.toFixed(2)} SGD`;
      })
      .join("\n");

    // Combine all service descriptions
    let serviceDescriptions = coreServiceDescriptions;
    if (addonServiceDescriptions) {
      serviceDescriptions += "\n" + addonServiceDescriptions;
    }

    let discountAmount = 0;
    let finalAdjustedPrice = adjustedPriceBeforeDiscount;

    let couponInfo = "";
    if (bookingData.coupon && bookingData.coupon.value > 0) {
      discountAmount = (adjustedPriceBeforeDiscount * bookingData.coupon.value) / 100;
      finalAdjustedPrice = adjustedPriceBeforeDiscount - discountAmount;
      couponInfo = `Coupon discount (${bookingData.coupon.code}): -${discountAmount.toFixed(2)} SGD\n`;
    }

    const fullComment =
      `${cleanComment ? "Comment from client: " + cleanComment + "\n\n" : ""}` +
      galleryInfo +
      `WEB-SITE BOOKING 
      Price information:
${serviceDescriptions}
Base price: ${basePrice.toFixed(2)} SGD
Master category: ${adjustmentPercent >= 0 ? "+" : ""}${adjustmentPercent}% (${masterMarkupAmount.toFixed(2)} SGD)
Final price before discount: ${adjustedPriceBeforeDiscount.toFixed(2)} SGD
${couponInfo}Note: Master markup applied only to core services, not to Add-on services.`;

    const bookingRequest = {
      action: "submit_booking",
      booking_nonce: booking_params.nonce,
      staff_id: bookingData.staffId,
      date: bookingData.date,
      time: bookingData.time,
      core_services: JSON.stringify(formattedServices.filter((s) => bookingData.coreServices.find((cs) => parseInt(cs.id) === s.id))),
      addon_services: JSON.stringify(formattedServices.filter((s) => bookingData.addons.find((a) => parseInt(a.id) === s.id))),
      client_name: bookingData.contact.name,
      client_phone: fullPhoneNumber,
      client_email: bookingData.contact.email || "",
      client_comment: fullComment,
      staff_level: staffLevel,
      base_price: basePrice.toFixed(2),
      adjusted_price: finalAdjustedPrice.toFixed(2),
      price_adjustment: masterMarkupAmount.toFixed(2),
      adjustment_percent: adjustmentPercent,
      total_price: finalAdjustedPrice.toFixed(2),
      coupon_code: bookingData.coupon ? bookingData.coupon.code : "",
    };

    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: bookingRequest,
      success: function (response) {
        $(".loading-overlay").hide();
        $(".confirm-booking-btn").prop("disabled", false).text("Book an appointment");

        if (response.success) {
          handleSuccessfulBooking(response.data);
        } else {
          showValidationAlert(response.data?.message || "Booking failed. Please try again.");
        }
      },
      error: function (xhr, status, error) {
        $(".loading-overlay").hide();
        $(".confirm-booking-btn").prop("disabled", false).text("Book an appointment");
        console.error("Booking submission error:", { xhr, status, error });
        showValidationAlert("Network error. Please check your connection and try again.");
      },
    });
  }
  /**
   * Calculate base price (before adjustment)
   * @returns {number} Base price
   */
  function calculateBasePrice() {
    let total = 0;

    [...bookingData.coreServices, ...bookingData.addons].forEach(function (service) {
      const rawPrice = parseFloat(service.price);
      if (!isNaN(rawPrice)) {
        total += rawPrice;
      }
    });

    return parseFloat(total.toFixed(2));
  }

  /**
   * Calculate price adjustment based on master level
   * @param {number} basePrice - Base price
   * @param {number} staffLevel - Staff level
   * @returns {number} Price adjustment amount
   */
  function calculatePriceAdjustment(basePrice, staffLevel) {
    if (staffLevel <= 1) {
      return 0;
    }

    const adjustmentPercent = (staffLevel - 1) * config.priceAdjustmentPerLevel;
    const adjustment = basePrice * (adjustmentPercent / 100);
    return parseFloat(adjustment.toFixed(2));
  }
  /**
   * Handle successful booking response
   * @param {Object} data - Response data
   */
  function handleSuccessfulBooking(data) {
    // Reset retry counter
    window.bookingRetryCount = 0;

    // Get booking reference
    const reference = data.booking?.reference || generateBookingReference();

    // Update confirmation screen values
    $(".booking-reference").text(reference);
    $(".booking-date").text(formatDateDisplay(bookingData.date));
    $(".booking-time").text(formatTimeRange(bookingData.time));

    // Build services summary for confirmation screen
    buildBookingConfirmationSummary();

    // Navigate to confirmation step
    goToStep("confirm");
    updateSummary();

    // Clear session data
    if (config.useLocalStorage) {
      clearBookingSession();
    }

    // Trigger event that booking was confirmed
    $(document).trigger("bookingConfirmed", [
      {
        reference: reference,
        date: bookingData.date,
        time: bookingData.time,
        services: bookingData.services,
        staffName: bookingData.staffName,
        staffLevel: bookingData.staffLevel,
        adjustedPrice: data.booking?.adjusted_price || calculateTotalPrice(),
      },
    ]);
  }

  /**
   * Generate a booking reference number
   * @returns {string} - Reference number
   */
  function generateBookingReference() {
    return "BK" + Math.floor(10000 + Math.random() * 90000);
  }

  /**
   * Build booking confirmation summary
   */
  function buildBookingConfirmationSummary() {
    let servicesHtml = '<h4>Booked Services</h4><div class="booked-services-list">';

    bookingData.services.forEach(function (service) {
      // Calculate adjusted price based on staff level
      let displayPrice = parseFloat(service.price);

      // Apply staff level price adjustment
      if (bookingData.staffLevel > 1) {
        const adjustmentPercent = (bookingData.staffLevel - 1) * config.priceAdjustmentPerLevel;
        const adjustment = displayPrice * (adjustmentPercent / 100);
        displayPrice += adjustment;
      }

      servicesHtml += `
        <div class="booked-service-item">
          <span class="booked-service-name">
            ${service.title}${service.isAddon ? ' <span class="addon-label"></span>' : ""}
          </span>
          <span class="booked-service-price">
            ${displayPrice.toFixed(2)} ${service.currency || "SGD"}
          </span>
        </div>`;
    });

    // Add master info with stars
    const masterWithStars = bookingData.staffName + " " + generateStarsHtml(bookingData.staffLevel);
    servicesHtml += `</div><div class="booked-master">Specialist: ${masterWithStars}</div>`;

    // Show total price
    const totalPrice = calculateTotalPrice();
    servicesHtml += `<div class="booked-total">Total: ${totalPrice} ${bookingData.services[0]?.currency || "SGD"}</div>`;

    $(".booked-services-summary").html(servicesHtml);
  }

  /**
   * Calculate total price with master level adjustments
   * @returns {string} - Formatted total price
   */
  function calculateTotalPrice() {
    let totalPrice = 0;

    bookingData.services.forEach(function (service) {
      let price = parseFloat(service.price.toString().replace(/[^\d.]/g, ""));

      // Apply staff level price adjustment
      if (bookingData.staffLevel > 1) {
        price = parseFloat(calculateAdjustedPrice(price, bookingData.staffLevel));
      }

      totalPrice += price;
    });

    return totalPrice.toFixed(2);
  }

  $(document).on("click", ".new-booking-btn", function () {
    resetBookingForm();
  });
  $(document).on("click", ".edit-booking-btn", function () {
    goToStep("contact");
    updateSummary();
  });
  $(document).on("click", ".cancel-booking-btn", function (e) {
    e.preventDefault();

    $(".booking-popup-overlay").removeClass("active");
    $("body").removeClass("popup-open");
    $(".booking-popup").hide();
    $(".loading-overlay").hide();

    if (typeof resetBookingForm === "function") {
      resetBookingForm();
    }

    if (window.localStorage) {
      localStorage.removeItem("bookingData");
    }
  });

  // Initialize styles when document is ready
})(jQuery);
