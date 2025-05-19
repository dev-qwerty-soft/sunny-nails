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

  // Star SVG template for consistent usage
  const starSvg = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M20.8965 18.008L18.6085 15.7L19.2965 15.012L21.6045 17.3L20.8965 18.008ZM17.7005 6.373L17.0125 5.685L19.3005 3.396L20.0085 4.085L17.7005 6.373ZM6.30048 6.393L4.01148 4.084L4.70048 3.395L7.00848 5.684L6.30048 6.393ZM3.08548 18.007L2.39648 17.299L4.68548 15.01L5.39248 15.699L3.08548 18.007ZM6.44048 20L7.91048 13.725L3.00048 9.481L9.47048 8.933L12.0005 3L14.5505 8.933L21.0205 9.481L16.1085 13.725L17.5785 20L12.0005 16.66L6.44048 20Z" fill="#FDC41F"/>
  </svg>`;

  /**
   * Generate stars HTML based on level
   * @param {number} level - Star level (1-5)
   * @returns {string} - HTML with star SVGs
   */
  function generateStarsHtml(level) {
    if (!level || level <= 0) return "";

    // Ensure level is a reasonable number (1-5)
    const starCount = Math.min(Math.max(parseInt(level) || 1, 1), 5);
    let stars = "";

    for (let i = 0; i < starCount; i++) {
      stars += `<span class="star">${starSvg}</span>`;
    }

    return `<div class="staff-stars">${stars}</div>`;
  }

  /**
   * Log debug information when debug mode is enabled
   * @param {string} message - Debug message
   * @param {*} data - Optional data to log
   */
  function debug(message, data) {
    if (config.debug) {
      if (data !== undefined) {
        console.log(`[Booking] ${message}:`, data);
      } else {
        console.log(`[Booking] ${message}`);
      }
    }
  }

  /**
   * Initialize the booking system when document is ready
   */
  $(document).ready(function () {
    initServiceHandling();
    initMasterHandling();
    initDateTimeHandling();
    initContactHandling();
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
      $(".loading-overlay").hide();
      // Trigger custom event
      $(document).trigger("bookingPopupOpened");
    });

    // Close popup
    $(document).on("click", ".booking-popup-close, .close-popup-btn", function () {
      $(".booking-popup-overlay").removeClass("active");
      // Add a confirmation if there's unsaved data
      clearBookingSession();
    });

    // Close when clicking outside popup
    $(document).on("click", ".booking-popup-overlay", function (e) {
      if ($(e.target).is(".booking-popup-overlay")) {
        $(".booking-popup-overlay").removeClass("active");
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
      if (nextStep === "services" && bookingData.preSelectedServiceId) {
        setTimeout(function () {
          $(`input[data-service-id="${bookingData.preSelectedServiceId}"]`).prop("checked", true).trigger("change");
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
  function initServiceStep() {
    $(document).on("click", '.booking-step[data-step="services"] .next-btn', function () {
      if (bookingData.coreServices.length === 0) return;

      const serviceIds = bookingData.coreServices.map((s) => s.id);

      $.ajax({
        url: config.apiEndpoint,
        type: "POST",
        data: {
          action: "get_staff_for_service",
          service_ids: serviceIds,
          nonce: config.nonce,
        },
        success: function (response) {
          if (response.success && response.data && response.data.data) {
            renderStaff(response.data.data);
          } else {
            $(".staff-list").html('<p class="no-items-message">No specialists available for the selected services.</p>');
            debug("Failed to load staff from API", response);
          }
        },
        error: function (xhr) {
          console.error("Error loading staff for selected services", xhr);
        },
      });

      const nextStep = bookingData.initialOption === "services" ? "master" : "datetime";
      bookingData.flowHistory.push(nextStep);
      if (nextStep === "master") loadStaffForServices();
      if (nextStep === "datetime") generateCalendar();
      goToStep(nextStep);
    });
  }

  function initMasterStep() {
    $(document).on("click", ".staff-item", function () {
      const staffId = $(this).data("staff-id");
      bookingData.staffId = staffId;

      // AJAX call to filter available services for selected master
      $.ajax({
        url: config.apiEndpoint,
        type: "POST",
        data: {
          action: "get_services_for_master",
          staff_id: staffId,
          nonce: config.nonce,
        },
        success: function (response) {
          if (response.success && Array.isArray(response.data)) {
            console.log("Filtered services:", response.data);
            // handle rendering filtered services if necessary
          } else {
            console.warn("Service filtering failed or empty response", response);
          }
        },
        error: function (xhr) {
          console.error("Error loading services for selected master", xhr);
        },
      });
    });

    $(document).on("click", '.booking-step[data-step="master"] .next-btn', function () {
      if (!bookingData.staffId) return;
      let nextStep = "datetime";
      if (bookingData.initialOption === "master" && !bookingData.coreServices.length) nextStep = "services";
      bookingData.flowHistory.push(nextStep);
      if (nextStep === "datetime") generateCalendar();
      goToStep(nextStep);
    });
  }
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
      const isAddon = $(this).data("is-addon") === true || $(this).data("is-addon") === "true";
      const altegioId = $(this).data("altegio-id") || serviceId;

      // Update UI classes
      if ($(this).is(":checked")) {
        $(this).closest(".service-item").addClass("selected");

        // Add service to bookingData
        const serviceWearTime = $(this).data("service-wear-time") || "";
        const desc = $(this).closest(".service-item").find(".service-description").text().trim();
        addService(serviceId, serviceTitle, servicePrice, serviceCurrency, serviceDuration, serviceWearTime, isAddon, altegioId, desc);

        debug("Service added", {
          id: serviceId,
          title: serviceTitle,
          isAddon: isAddon,
        });
      } else {
        $(this).closest(".service-item").removeClass("selected");
        removeService(serviceId);

        debug("Service removed", serviceId);
      }

      // Update addon availability and enable/disable the Next button
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
        console.error("Error in services next button:", error);
        showValidationAlert("An error occurred. Please try again.");
        return false;
      }
    });
  }

  /**
   * Initialize master selection handling
   */
  function initMasterHandling() {
    // Staff selection
    $(document).on("click", ".staff-item", function () {
      const staffId = $(this).data("staff-id");
      const staffName = $(this).find(".staff-name").text();
      let staffAvatar = "";

      // Get avatar if exists
      const avatarImg = $(this).find(".staff-avatar img");
      if (avatarImg.length) {
        staffAvatar = avatarImg.attr("src") || "";
      }
      const specialization = $(this).data("staff-specialization");
      const staffLevel = $(this).data("staff-level") || 1;
      bookingData.staffLevel = staffLevel;
      bookingData.staffSpecialization = specialization;
      // Update bookingData
      selectStaff(staffId, staffName, staffAvatar, staffLevel, specialization);

      // Update UI
      $(".staff-item").removeClass("selected");
      $(this).addClass("selected");

      // Enable next button
      updateMasterNextButtonState();
    });

    // Next button in master step
    $(document).on("click", '.booking-step[data-step="master"] .next-btn', function () {
      if (!bookingData.staffId) {
        showValidationAlert("Please select a specialist");
        return;
      }

      // Determine next step based on initial option and flow history
      let nextStep;
      if (bookingData.initialOption === "master") {
        nextStep = "services";

        // Check if we're coming back to master from datetime
        if (bookingData.flowHistory.includes("datetime")) {
          nextStep = "datetime";
        }
      } else {
        nextStep = "datetime";
      }

      // Add to flow history
      bookingData.flowHistory.push(nextStep);

      debug("Master selected, proceeding to", nextStep);

      if (nextStep === "datetime") {
        generateCalendar();
      }

      goToStep(nextStep);

      // Trigger custom event
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
    const levelTitles = {
      1: "Sunny Ray",
      2: "Sunny Shine",
      3: "Sunny Inferno",
    };

    const level = parseInt(bookingData.staffLevel || 1);

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
    // Previous/Next month buttons
    $(document).on("click", ".prev-month", function () {
      navigateCalendar(-1);
    });

    $(document).on("click", ".next-month", function () {
      navigateCalendar(1);
    });

    // Date selection
    $(document).on("click", ".calendar-day:not(.disabled, .empty)", function () {
      const date = $(this).data("date");
      selectDate(date);
      $(".calendar-day").removeClass("selected");
      $(this).addClass("selected");

      // This must be triggered:
      loadTimeSlots(date);
    });

    // Time slot selection
    $(document).on("click", ".time-slot:not(.disabled)", function () {
      const time = $(this).data("time");
      selectTime(time);

      // Update UI
      $(".time-slot").removeClass("selected");
      $(this).addClass("selected");

      // Enable next button when time is selected
      updateDateTimeNextButtonState();
    });

    // Next button in datetime step
    $(document).on("click", '.booking-step[data-step="datetime"] .next-btn', function () {
      if (validateDateTimeStep()) {
        // Add to flow history
        bookingData.flowHistory.push("contact");

        renderContactStepSummary();
        goToStep("contact");

        updateSummary();

        // Trigger custom event
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
      let phone = input.val().replace(/\D/g, "");

      // Simple phone formatting
      if (phone.length > 0) {
        if (phone.length <= 3) {
          phone = phone;
        } else if (phone.length <= 6) {
          phone = phone.slice(0, 3) + "-" + phone.slice(3);
        } else if (phone.length <= 10) {
          phone = phone.slice(0, 3) + "-" + phone.slice(3, 6) + "-" + phone.slice(6);
        } else {
          phone = phone.slice(0, 3) + "-" + phone.slice(3, 6) + "-" + phone.slice(6, 10);
        }
        input.val(phone);
      }
    });

    // Confirm booking button
    $(document).on("click", ".confirm-booking-btn", function () {
      const form = $("#booking-form")[0];
      const $form = $("#booking-form");
      let valid = true;

      // Очистити попередні помилки
      $form.find(".input-error").text("");
      $(".global-form-error").hide();

      // Перевірити поля вручну
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

      // Proceed to confirmation step
      bookingData.flowHistory.push("confirm");
      goToStep("confirm");
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
    const fieldName = field.prev("label").text() || fieldId;

    // Remove any existing error
    field.removeClass("error");
    field.next(".field-error").remove();

    if (field.prop("required") && !value) {
      field.addClass("error");
      field.after(`<div class="field-error">${fieldName} is required</div>`);
      return false;
    }

    // Email validation
    if (fieldId === "client-email" && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        field.addClass("error");
        field.after('<div class="field-error">Please enter a valid email address</div>');
        return false;
      }
    }

    // Phone validation
    if (fieldId === "client-phone" && value) {
      // Simple check for min length
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
  function showValidationAlert(message) {}

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
      contact: {},
      flowHistory: ["initial"],
      initialOption: "services",
    };

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

    // Reset staff selection
    $(".staff-item").removeClass("selected");

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
          desc, // <- важливо!
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
    bookingData.staffLevel = parseInt(level) || 1;

    debug("Staff selected", { id, name, level, specialization });
  }

  /**
   * Load staff for selected services
   * This calls the Altegio API to get available staff for the selected services
   */
  function loadStaffForServices() {
    if (bookingData.services.length === 0) return;

    const serviceIds = bookingData.services.map((service) => service.altegioId || service.id).join(",");

    debug("Loading staff for services", serviceIds);

    $(".staff-list").html('<p class="loading-message">Loading specialists...</p>');

    $.ajax({
      url: config.apiEndpoint,
      type: "POST",
      data: {
        action: "get_filtered_staff",
        service_id: serviceIds,
        nonce: config.nonce,
      },
      success: function (response) {
        if (response.success && response.data && Array.isArray(response.data.data)) {
          renderStaff(response.data.data);
        } else {
          $(".staff-list").html('<p class="no-items-message">No specialists available for the selected services.</p>');
          debug("Failed to load staff from API", response);
        }
      },
      error: function (xhr, status, error) {
        debug("AJAX error loading staff", { status, error });
        $(".staff-list").html('<p class="no-items-message">Error loading specialists.</p>');
      },
    });
  }
  function loadServicesForMaster(masterId) {
    debug("Loading services for master", masterId);

    $(".booking-popup .services-list").html('<p class="loading-message">Loading services...</p>');

    $.ajax({
      url: config.apiEndpoint,
      type: "POST",
      data: {
        action: "get_services_for_master",
        staff_id: masterId,
        nonce: config.nonce,
      },
      success: function (response) {
        if (response.success && Array.isArray(response.data)) {
          renderServices(response.data);
        } else {
          console.warn("No services available for the selected master");
          $(".booking-popup .services-list").html('<p class="no-items-message">No services available for this master.</p>');
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading services for master:", error);
        $(".booking-popup .services-list").html('<p class="no-items-message">Error loading services.</p>');
      },
    });

    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: {
        action: "get_filtered_services",
        staff_id: masterId,
        nonce: booking_params.nonce,
      },
      success: function (response) {
        if (response.success && response.data && response.data.html) {
          $(".booking-popup .services-list").html(response.data.html);
          debug("Filtered services HTML loaded");

          updateAddonAvailability();
          updateNextButtonState();
        } else {
          $(".booking-popup .services-list").html('<p class="no-items-message">No services available for this master.</p>');
        }
      },
      error: function () {
        $(".booking-popup .services-list").html('<p class="no-items-message">Error loading services.</p>');
      },
    });
  }

  // Додай цю частину одразу після оголошення функції
  $(document).on("click", ".staff-item", function () {
    const masterId = $(this).data("staff-id");
    if (masterId && masterId !== "any") {
      loadServicesForMaster(masterId);
    } else {
      $(".service-item").show();
    }
  });
  function filterServicesByAllowedIds(allowedIds) {
    // Пройтись по кожній категорії
    $(".category-services").each(function () {
      let hasVisible = false;

      $(this)
        .find(".service-item")
        .each(function () {
          const $item = $(this);
          const serviceId = String($item.data("service-id"));

          if (allowedIds.includes(serviceId)) {
            $item.show();
            hasVisible = true;
          } else {
            $item.hide();
            $item.removeClass("selected");
            $item.find(".service-checkbox").prop("checked", false);
          }
        });

      // Показуємо/ховаємо категорію цілком, якщо в ній нічого не лишилось
      if (hasVisible) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });

    // Активуємо першу доступну вкладку
    $(".category-tab").each(function () {
      const categoryId = $(this).data("category-id");
      const $categoryBlock = $(`.category-services[data-category-id="${categoryId}"]`);
      if ($categoryBlock.is(":visible")) {
        $(".category-tab").removeClass("active");
        $(this).addClass("active");
        return false; // break .each
      }
    });

    updateAddonAvailability();
    updateNextButtonState();
    debug("Filtered visible services by master:", allowedIds);
  }

  /**
   * Load a specific staff member by ID
   * @param {string|number} staffId - Staff ID to load
   */
  function loadStaffById(staffId) {
    debug("Loading staff by ID", staffId);

    // Call AJAX to get specific staff details
    $.ajax({
      url: config.apiEndpoint,
      type: "POST",
      data: {
        action: "get_staff_details",
        staff_id: staffId,
        nonce: config.nonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          const staff = response.data;
          selectStaff(staff.id, staff.name, staff.avatar, staff.level, staff.specialization);

          // Update UI - mark staff as selected
          $(".staff-item").removeClass("selected");
          $(`.staff-item[data-staff-id="${staff.id}"]`).addClass("selected");

          debug("Staff data loaded", staff);
        } else {
          debug("Failed to load staff details", response);
          showValidationAlert("Failed to load master details. Please select another master.");
        }
      },
      error: function (xhr, status, error) {
        debug("AJAX error loading staff details", { status, error });
        showValidationAlert("Failed to load master details. Please select another master.");
      },
    });
  }

  /**
   * Render staff list with fallback if API fails
   * Use a blend of existing staff items and default values
   */

  /**
   * Render staff list
   * @param {Array} staffList - List of staff members
   */
  function renderStaff(staffList) {
    if (!staffList || staffList.length === 0) {
      // If no staff available, show a message
      $(".staff-list").html('<p class="no-items-message">No specialists available for the selected services.</p>');
      return;
    }
    let html = "";
    const levelTitles = {
      1: "Sunny Ray",
      2: "Sunny Shine",
      3: "Sunny Inferno",
    };
    const isSelected = bookingData.staffId == "any" ? " selected" : "";
    // Start with "Any master" option
    html = `
        <label class="staff-item any-master first${isSelected}"   data-staff-id="any" data-staff-level="1">
        <input type="radio" name="staff">
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

    // Then add each staff member
    staffList.forEach(function (staff) {
      // Check if already selected
      const isSelected = bookingData.staffId == staff.id ? " selected" : "";

      // Get staff level (defaulting to 1)
      const staffLevel = staff.level || 1;
      const levelTitle = levelTitles[staffLevel] || "";
      let priceModifier = "";

      if (staffLevel > 1) {
        const priceIncrease = (staffLevel - 1) * config.priceAdjustmentPerLevel;
        priceModifier = `<div class="staff-price-modifier">+${priceIncrease}% to price</div>`;
      }

      html += `
        <label class="staff-item${isSelected}" data-staff-id="${staff.id}" data-staff-level="${staffLevel}">
          <input type="radio" name="staff">
          <div class="staff-radio-content">
            <div class="staff-avatar">
              ${staff.avatar ? `<img src="${staff.avatar}" alt="${staff.name}">` : ""}
            </div>
            <div class="staff-info">
              <h4 class="staff-name">${staff.name}</h4>
              <div class="staff-specialization">
                ${generateStarsHtml(staffLevel)}
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

    // If a staff member was previously selected, reselect it
    if (bookingData.staffId) {
      $(`.staff-item[data-staff-id="${bookingData.staffId}"]`).addClass("selected");
    }
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
  function renderCalendar(month, year) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();

    // Adjust day of week for Monday as first day
    // (0 = Monday, 6 = Sunday)
    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;

    // Update month and year in header
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    $(".month-header span").text(monthNames[month] + " " + year);

    let html = "";

    // Add empty cells for days before first day of month
    for (let i = 0; i < startDay; i++) {
      html += '<div class="calendar-day empty"></div>';
    }

    // Get current date for comparison
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Add days of month
    for (let i = 1; i <= daysInMonth; i++) {
      const date = new Date(year, month, i);
      const dateStr = formatDate(date);

      // Check if date is today or in the past
      const isToday = date.getTime() === today.getTime();
      const isPast = date < today;

      let classes = "calendar-day";
      if (isToday) classes += " today";
      if (isPast) classes += " disabled";

      // Check if this date was previously selected
      if (bookingData.date === dateStr) {
        classes += " selected";
      }

      html += `<div class="${classes}" data-date="${dateStr}">${i}</div>`;
    }

    $(".calendar-grid").html(html);

    // If a date was previously selected, reload time slots
    if (bookingData.date) {
      const currentMonth = new Date(bookingData.date).getMonth();
      const currentYear = new Date(bookingData.date).getFullYear();

      // Only reload if we're looking at the same month
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

  function loadTimeSlots(date) {
    if (!bookingData.staffId || bookingData.services.length === 0) {
      console.warn("Staff or service not selected");
      $(".time-slots").html('<p class="error-message">Please select a staff and service first.</p>');
      return;
    }

    if (!date) {
      $(".time-slots").html('<p class="error-message">Please select a date.</p>');
      return;
    }

    const serviceIds = bookingData.coreServices.map((s) => s.altegioId || s.id).join(",");
    if (!serviceIds) {
      $(".time-slots").html('<p class="error-message">Please select at least one core service.</p>');
      return;
    }

    $(".time-slots").html('<p class="loading-message">Loading available time slots...</p>');
    console.log("Sending service IDs:", serviceIds);

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
        if (response.success) {
          const slots = response.data?.slots ?? [];
          if (slots.length > 0) {
            renderTimeSlots(slots);
          } else {
            $(".time-slots").html('<p class="error-message">No available time slots for this day.</p>');
          }
        } else {
          $(".time-slots").html('<p class="error-message">Error loading time slots. Please try again later.</p>');
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading time slots:", error);
        $(".time-slots").html('<p class="error-message">Error loading time slots. Please try again later.</p>');
      },
    });
  }

  function renderTimeSlots(slots) {
    if (!slots || slots.length === 0) {
      $(".time-sections").html('<div class="no-slots-message">No available time slots for this date.</div>');
      return;
    }

    const getTime = (slot) => {
      if (typeof slot === "object" && slot.time) return slot.time.slice(0, 5);
      if (typeof slot === "string") return slot.split(" ")[1]?.slice(0, 5) || slot.slice(0, 5);
      return "";
    };

    const times = slots.map(getTime).filter(Boolean);

    const grouped = {
      Morning: times.filter((t) => parseInt(t.split(":")[0]) < 12),
      Day: times.filter((t) => {
        const h = parseInt(t.split(":")[0]);
        return h >= 12 && h < 17;
      }),
      Evening: times.filter((t) => parseInt(t.split(":")[0]) >= 17),
    };

    let html = "";
    for (const [label, group] of Object.entries(grouped)) {
      if (!group.length) continue;
      html += `<div class="time-group">
      <div class="time-group-title">${label}</div>
      <div class="time-slots">${group.map((t) => `<div class="time-slot" data-time="${t}">${t}</div>`).join("")}</div>
    </div>`;
    }

    $(".time-sections").html(html);

    if (bookingData.time) {
      $(`.time-slot[data-time="${bookingData.time}"]`).addClass("selected");
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const calendarGrid = document.querySelector(".calendar-grid");
    const timeSlotsContainer = document.querySelector(".time-slots");
    const currentMonthElem = document.querySelector(".current-month");
    const prevMonthBtn = document.querySelector(".prev-month");
    const nextMonthBtn = document.querySelector(".next-month");
    const nextBtn = document.querySelector(".next-btn");

    let staffId = window.bookingData?.staffId || null;
    let serviceIds = window.bookingData?.services || [];
    let selectedDate = null;
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth();

    function renderCalendar(year, month, availableDates = []) {
      currentMonthElem.textContent = new Date(year, month).toLocaleString("en-US", { month: "long", year: "numeric" });
      calendarGrid.innerHTML = "";

      const firstDayOfMonth = new Date(year, month, 1);
      const startDay = firstDayOfMonth.getDay() || 7;
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      for (let i = 1; i < startDay; i++) {
        const emptyCell = document.createElement("div");
        emptyCell.classList.add("empty-day");
        calendarGrid.appendChild(emptyCell);
      }

      for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement("div");
        dayCell.textContent = day;
        dayCell.classList.add("calendar-day");

        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
        if (availableDates.includes(dateStr)) {
          dayCell.classList.add("available");
          dayCell.addEventListener("click", () => {
            selectedDate = dateStr;
            highlightSelectedDate(dayCell);
            loadTimeSlots();
          });
        } else {
          dayCell.classList.add("unavailable");
        }
        calendarGrid.appendChild(dayCell);
      }
    }

    function highlightSelectedDate(selectedElem) {
      document.querySelectorAll(".calendar-day.available").forEach((el) => el.classList.remove("selected"));
      selectedElem.classList.add("selected");
    }

    async function loadAvailableDates() {
      if (!staffId || serviceIds.length === 0) {
        console.warn("Staff or service not selected");
        return;
      }

      try {
        const response = await fetch(ajaxurl, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            action: "get_booking_dates",
            nonce: booking_nonce,
            staff_id: staffId,
            service_ids: serviceIds.join(","),
            year: currentYear,
            month: currentMonth + 1,
          }),
        });
        const data = await response.json();

        if (data.success) {
          renderCalendar(currentYear, currentMonth, data.data.available_dates);
        } else {
          renderCalendar(currentYear, currentMonth, []);
          console.error("Error fetching dates:", data.data?.message);
        }
      } catch (error) {
        console.error("Ajax error:", error);
      }
    }

    async function loadTimeSlots() {
      if (!staffId || !selectedDate || serviceIds.length === 0) {
        timeSlotsContainer.innerHTML = "<p>Please select a date to see available time slots</p>";
        return;
      }

      timeSlotsContainer.innerHTML = "<p>Loading...</p>";

      try {
        const response = await fetch(ajaxurl, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            action: "get_time_slots",
            nonce: booking_nonce,
            staff_id: staffId,
            date: selectedDate,
            service_id: serviceIds.join(","),
          }),
        });
        const data = await response.json();

        if (data.success && data.slots.length) {
          timeSlotsContainer.innerHTML = "";
          data.slots.forEach((slot) => {
            const slotBtn = document.createElement("button");
            slotBtn.type = "button";
            slotBtn.classList.add("time-slot");
            slotBtn.textContent = `${slot.time} (${Math.floor(slot.seance_length / 60)} min)`;
            slotBtn.addEventListener("click", () => {
              window.bookingData.time = slot.time;
              updateSelectedTimeUI(slotBtn);
            });
            timeSlotsContainer.appendChild(slotBtn);
          });
        } else {
          timeSlotsContainer.innerHTML = "<p>No available time slots for this day.</p>";
        }
      } catch (error) {
        timeSlotsContainer.innerHTML = "<p>Error loading time slots.</p>";
        console.error("Ajax error:", error);
      }
    }

    function updateSelectedTimeUI(selectedBtn) {
      document.querySelectorAll(".time-slot").forEach((btn) => btn.classList.remove("selected"));
      selectedBtn.classList.add("selected");
    }

    prevMonthBtn.addEventListener("click", () => {
      currentMonth--;
      if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
      }
      loadAvailableDates();
    });

    nextMonthBtn.addEventListener("click", () => {
      currentMonth++;
      if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
      }
      loadAvailableDates();
    });

    loadAvailableDates();
  });

  /**
   * Format time for display (24h to 12h)
   * @param {string} timeStr - Time in 24-hour format (HH:MM)
   * @returns {string} - Formatted time in 12-hour format with AM/PM
   */
  function formatTimeDisplay(timeStr) {
    if (!timeStr || !timeStr.includes(":")) return timeStr;

    // Convert 24-hour time to 12-hour format with AM/PM
    const [hours, minutes] = timeStr.split(":");
    const hour = parseInt(hours, 10);
    const period = hour >= 12 ? "PM" : "AM";
    const hour12 = hour % 12 || 12; // Convert 0 to 12
    return `${hour12}:${minutes} ${period}`;
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

  /**
   * Update booking summary with selected services and master
   * This function is called when navigating to the contact step
   * and populates all data dynamically from previous selections
   */
  function updateSummary() {
    // Select all the elements we need to update
    const masterBox = $(".summary-master-date .master-info");
    const dateTimeBox = $(".booking-date-time");
    const serviceList = $(".summary-services-list");
    const addonsList = $(".summary-addons");
    const masterBonusEl = $(".master-bonus");
    const masterPercent = $(".summary-total-group .percent");
    const totalAmountEl = $(".summary-total-amount");

    // Update master info
    if (bookingData.staffAvatar) {
      masterBox.find(".avatar").attr("src", bookingData.staffAvatar);
    } else {
      // If no avatar, use placeholder
      masterBox.find(".avatar").attr("src", "https://be.cdn.alteg.io/images/no-master-sm.png");
    }

    // Set master name and stars
    masterBox.find(".name").text(bookingData.staffName || "Any Master");
    masterBox.find(".stars").html(generateStarsHtml(bookingData.staffLevel));
    const levelTitles = {
      1: "Sunny Ray",
      2: "Sunny Shine",
      3: "Sunny Inferno",
    };
    const title = levelTitles[bookingData.staffLevel];
    masterBox
      .find(".stars-name")
      .text(title ? `(${title})` : "")
      .toggle(!!title);

    // Update date/time
    const dateStr = formatDateDisplay(bookingData.date);
    const timeStr = formatTimeRange(bookingData.time);
    dateTimeBox.find(".calendar-date").text(dateStr);
    dateTimeBox.find(".calendar-time").text(timeStr);

    // Build service items and calculate totals
    let serviceHTML = "";
    let addonHTML = "";
    let total = 0;
    let bonus = 0;

    // Process each service
    bookingData.services.forEach((service) => {
      // Convert price to number
      let price = parseFloat(service.price);
      let adjusted = price;

      // Apply master level price adjustment
      if (bookingData.staffLevel > 1) {
        const percent = (bookingData.staffLevel - 1) * config.priceAdjustmentPerLevel;
        const extra = price * (percent / 100);
        adjusted += extra;
        bonus += extra;
      }

      // Create service item HTML
      const itemHTML = `
      <div class="summary-service-item">
        <div class="service-info">
          <strong>${service.title}</strong>
          ${service.duration ? `<div class="meta"><strong>Duration:</strong> ${service.duration} min</div>` : ""}
          ${service.wearTime ? `<div class="meta"><strong>Wear time:</strong> ${service.wearTime}</div>` : ""}
         ${service.desc ? `<div class="meta service-description">${service.desc}</div>` : ""}

        </div>
        <div class="service-price"><strong>${adjusted.toFixed(2)} ${service.currency || "SGD"}</strong></div>
      </div>
    `;

      // Add to appropriate section (core services or add-ons)
      if (service.isAddon) {
        addonHTML += itemHTML;
      } else {
        serviceHTML += itemHTML;
      }

      total += adjusted;
    });

    // Update service sections
    serviceList.html(serviceHTML || '<p class="no-services">No services selected</p>');

    // Update add-ons section with title if there are add-ons
    if (addonHTML) {
      addonsList.html(`
        <h3 class="section-subtitle">Add-ons</h3>
        ${addonHTML}
      `);
    } else {
      addonsList.empty();
    }

    // Update master bonus and total
    const bonusPercent = bookingData.staffLevel > 1 ? (bookingData.staffLevel - 1) * config.priceAdjustmentPerLevel : 0;

    // Only show master bonus if there is one
    if (bonus > 0) {
      masterBonusEl.text(`${bonus.toFixed(2)} SGD`);
      masterPercent.text(bonusPercent);
      $(".master-bonus-row").show();
    } else {
      $(".master-bonus-row").hide();
    }

    totalAmountEl.text(`${total.toFixed(2)} SGD`);

    // Restore any previously entered contact info
    if (bookingData.contact) {
      $("#client-name").val(bookingData.contact.name || "");
      $("#client-phone").val(bookingData.contact.phone || "");
      $("#client-email").val(bookingData.contact.email || "");
      $("#client-comment").val(bookingData.contact.comment || "");
    }
  }

  /**
   * Validate the contact form
   * @returns {boolean} Whether the form is valid
   */
  function validateContactStep() {
    let isValid = true;

    // Clear previous errors
    $(".field-error").remove();
    $(".contact-form input, .contact-form textarea").removeClass("error");

    // Required fields validation
    const name = $("#client-name").val().trim();
    if (!name) {
      $("#client-name").addClass("error").after('<div class="field-error">Name is required</div>');
      isValid = false;
    }

    const phone = $("#client-phone").val().trim();
    if (!phone) {
      $("#client-phone").addClass("error").after('<div class="field-error">Phone is required</div>');
      isValid = false;
    }

    // Email validation (optional)
    const email = $("#client-email").val().trim();
    if (email && !isValidEmail(email)) {
      $("#client-email").addClass("error").after('<div class="field-error">Please enter a valid email address</div>');
      isValid = false;
    }

    // Privacy policy checkbox
    if ($("#privacy-policy").length && !$("#privacy-policy").prop("checked")) {
      $("#privacy-policy").addClass("error");
      $(".form-group.checkbox").append('<div class="field-error">You must accept the Privacy Policy</div>');
      isValid = false;
    }

    // If form is valid, save contact info to bookingData
    if (isValid) {
      bookingData.contact = {
        name: name,
        phone: phone,
        email: email,
        comment: $("#client-comment").val().trim(),
      };
    }

    return isValid;
  }

  /**
   * Check if email is valid
   * @param {string} email - Email to validate
   * @returns {boolean} - Whether email is valid
   */
  function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  /**
   * Submit the booking
   * This function is called when clicking the confirmation button
   */
  function submitBooking() {
    // Show loading state
    $(".confirm-booking-btn").prop("disabled", true).text("Processing...");

    // Prepare data for submission
    const bookingRequest = {
      action: "submit_booking",
      booking_nonce: booking_params.nonce,
      service_id: bookingData.services.map((s) => s.id).join(","),
      staff_id: bookingData.staffId,
      date: bookingData.date,
      time: bookingData.time,
      client_name: bookingData.contact.name,
      client_phone: bookingData.contact.phone,
      client_email: bookingData.contact.email || "",
      client_comment: bookingData.contact.comment || "",
      // Include additional data
      core_services: JSON.stringify(bookingData.coreServices.map((s) => s.id)),
      addon_services: JSON.stringify(bookingData.addons.map((s) => s.id)),
      staff_level: bookingData.staffLevel,
      total_price: $(".summary-total-amount").text(),
    };

    // Submit booking via AJAX
    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: bookingRequest,
      success: function (response) {
        $(".confirm-booking-btn").prop("disabled", false).text("Book an appointment");

        if (response.success) {
          // Handle successful booking
          handleSuccessfulBooking(response.data);
        } else {
          // Show error message
          showValidationAlert(response.data?.message || "Booking failed. Please try again.");
        }
      },
      error: function (xhr, status, error) {
        // Show error message
        $(".confirm-booking-btn").prop("disabled", false).text("Book an appointment");
        showValidationAlert("Error submitting booking. Please try again.");

        console.error("Booking submission error:", error);

        // If maximum retries reached, show fallback confirmation
        window.bookingRetryCount = (window.bookingRetryCount || 0) + 1;
        if (window.bookingRetryCount >= config.maxRetries) {
          handleFallbackBookingConfirmation();
        }
      },
    });
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
      },
    ]);
  }

  /**
   * Handle fallback booking confirmation when API fails
   */
  function handleFallbackBookingConfirmation() {
    debug("Using fallback booking confirmation");

    // Clear booking session
    clearBookingSession();

    // Generate reference number
    const reference = generateBookingReference();

    // Set values in confirmation page
    $(".booking-reference").text(reference);
    $(".booking-date").text(formatDateDisplay(bookingData.date));
    $(".booking-time").text(formatTimeRange(bookingData.time));

    // Build summary
    buildBookingConfirmationSummary();

    // Navigate to confirmation step
    goToStep("confirm");

    // Show notification about using fallback
    $(".confirmation-message").after('<p class="fallback-notice">(Your booking will be processed as soon as our system is back online)</p>');
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
            ${service.title}${service.isAddon ? ' <span class="addon-label">(add-on)</span>' : ""}
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

  /**
   * Add CSS styles for validation, alerts, and star ratings
   */
  $(document).on("click", ".new-booking-btn", function () {
    resetBookingForm();
  });
  $(document).on("click", ".edit-booking-btn", function () {
    goToStep("contact");
    updateSummary();
  });

  // Initialize styles when document is ready
})(jQuery);
