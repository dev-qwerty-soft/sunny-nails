(function ($) {
  "use strict";

  // Booking data object
  let bookingData = {
    services: [],
    coreServices: [], // Track core services separately
    addons: [], // Track add-ons separately
    staffId: null,
    staffName: "",
    staffAvatar: "",
    staffLevel: "", // Store staff level for price adjustment
    date: null,
    time: null,
    contact: {},
    initialOption: "services", // Default first step after initial
  };

  // Debug flag for development
  const DEBUG = true;

  $(document).ready(function () {
    initBookingPopup();
  });

  function initBookingPopup() {
    // Open popup when book button is clicked
    $(document).on("click", ".book-btn, .open-popup", function (e) {
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
    });

    // Close popup
    $(document).on("click", ".booking-popup-close, .close-popup-btn", function () {
      $(".booking-popup-overlay").removeClass("active");
    });

    // Close when clicking outside popup
    $(document).on("click", ".booking-popup-overlay", function (e) {
      if ($(e.target).is(".booking-popup-overlay")) {
        $(".booking-popup-overlay").removeClass("active");
      }
    });

    // Option selection in initial step
    $(document).on("click", ".booking-option-item", function () {
      $(".booking-option-item").removeClass("active");
      $(".status-indicator").removeClass("active");

      $(this).addClass("active");
      $(this).find(".status-indicator").addClass("active");

      bookingData.initialOption = $(this).data("option");

      if (DEBUG) console.log("Initial option selected:", bookingData.initialOption);
    });

    // Next button in initial step
    $(document).on("click", '.booking-step[data-step="initial"] .next-btn', function () {
      const nextStep = $(".booking-option-item.active").data("option") || "services";
      bookingData.initialOption = nextStep;

      if (DEBUG) console.log("Going to step:", nextStep);
      goToStep(nextStep);

      // If pre-selected service, select it
      if (nextStep === "services" && bookingData.preSelectedServiceId) {
        setTimeout(function () {
          $('input[data-service-id="' + bookingData.preSelectedServiceId + '"]')
            .prop("checked", true)
            .trigger("change")
            .closest(".service-item")
            .addClass("selected");
        }, 100);
      }

      // If pre-selected master, load that master's data
      if (nextStep === "master" && bookingData.preSelectedMasterId) {
        // Pre-load staff data for the selected master
        loadStaffById(bookingData.preSelectedMasterId);
      }
    });

    // Back buttons with dynamic path based on initial choice
    $(document).on("click", ".booking-back-btn", function () {
      const currentStep = $(this).closest(".booking-step").data("step");

      if (DEBUG) console.log("Going back from:", currentStep);

      if (currentStep === "services" || currentStep === "master") {
        // If we're in the first actual step, go back to initial
        goToStep("initial");
      } else if (currentStep === "datetime") {
        // Go to the previous step based on the order
        const previousStep = bookingData.initialOption === "services" ? "master" : "services";
        goToStep(previousStep);
      } else if (currentStep === "contact") {
        goToStep("datetime");
      }
    });

    // Category filter tabs
    $(document).on("click", ".category-tab", function () {
      const categoryId = $(this).data("category-id");

      // Update active tab
      $(".category-tab").removeClass("active");
      $(this).addClass("active");

      // Show only services from selected category
      $(".category-services").hide();
      $('.category-services[data-category-id="' + categoryId + '"]').show();
    });

    // Make the entire service item clickable
    $(document).on("click", ".service-item", function (e) {
      // Prevent multiple triggering if clicking on the checkbox directly
      if ($(e.target).is(".service-checkbox")) {
        return;
      }

      // Don't allow clicking if disabled
      if ($(this).hasClass("disabled")) {
        if (DEBUG) console.log("Service item is disabled");
        return;
      }

      const checkbox = $(this).find(".service-checkbox");

      // If checkbox is disabled, don't allow toggling
      if (checkbox.prop("disabled")) {
        return;
      }

      checkbox.prop("checked", !checkbox.prop("checked"));

      // Manually trigger change event
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

      if (DEBUG) console.log("Service checkbox changed:", serviceId, serviceTitle, servicePrice, "Is addon:", isAddon);

      // Update visual selection
      if ($(this).is(":checked")) {
        $(this).closest(".service-item").addClass("selected");

        // Add service to bookingData
        addService(serviceId, serviceTitle, servicePrice, serviceCurrency, serviceDuration, serviceWearTime, isAddon, altegioId);

        if (DEBUG) console.log("bookingData after adding:", JSON.parse(JSON.stringify(bookingData)));
      } else {
        $(this).closest(".service-item").removeClass("selected");
        removeService(serviceId);

        if (DEBUG) console.log("bookingData after removing:", JSON.parse(JSON.stringify(bookingData)));
      }

      // Update addon availability based on core service selection
      updateAddonAvailability();
    });

    // Next button in services step
    $(document).on("click", '.booking-step[data-step="services"] .next-btn', function () {
      try {
        // Check if at least one core service is selected
        if (bookingData.coreServices.length === 0) {
          showValidationAlert("Please select at least one service");
          return false;
        }

        if (DEBUG) console.log("Services selected, proceeding to next step:", bookingData.services);

        // Determine next step based on initial option
        const nextStep = bookingData.initialOption === "services" ? "master" : "datetime";

        if (nextStep === "master") {
          loadStaffForServices();
        } else if (nextStep === "datetime") {
          // If we're skipping master step, we need to check if a master is selected
          if (!bookingData.staffId) {
            showValidationAlert("Please go back and select a master first");
            return false;
          }
          generateCalendar();
        }

        // Navigate to the next step
        goToStep(nextStep);

        return true;
      } catch (error) {
        console.error("Error in services next button:", error);
        showValidationAlert("An error occurred. Please try again.");
        return false;
      }
    });

    // Staff selection
    $(document).on("click", ".staff-item", function () {
      const staffId = $(this).data("staff-id");
      const staffName = $(this).find(".staff-name").text();
      const staffAvatar = $(this).find(".staff-avatar img").attr("src") || "";
      const staffLevel = $(this).data("staff-level") || "1";

      selectStaff(staffId, staffName, staffAvatar, staffLevel);

      // Update UI
      $(".staff-item").removeClass("selected");
      $(this).addClass("selected");
    });

    // Next button in master step - dynamic next step based on initial choice
    $(document).on("click", '.booking-step[data-step="master"] .next-btn', function () {
      if (!bookingData.staffId) {
        showValidationAlert("Please select a specialist");
        return;
      }

      // Determine next step based on initial option
      const nextStep = bookingData.initialOption === "master" ? "services" : "datetime";

      if (DEBUG) console.log("Master selected, proceeding to:", nextStep);

      if (nextStep === "datetime") {
        generateCalendar();
      }

      goToStep(nextStep);
    });

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

      // Update UI
      $(".calendar-day").removeClass("selected");
      $(this).addClass("selected");

      // Load time slots
      loadTimeSlots(date);
    });

    // Time slot selection
    $(document).on("click", ".time-slot:not(.disabled)", function () {
      const time = $(this).data("time");
      selectTime(time);

      // Update UI
      $(".time-slot").removeClass("selected");
      $(this).addClass("selected");
    });

    // Next button in datetime step
    $(document).on("click", '.booking-step[data-step="datetime"] .next-btn', function () {
      if (validateDateTimeStep()) {
        goToStep("contact");
        updateSummary();
      }
    });

    // Confirm booking button
    $(document).on("click", ".confirm-booking-btn", function () {
      if (validateContactStep()) {
        submitBooking();
      }
    });
  }

  /**
   * Update addon availability based on core service selection
   */
  function updateAddonAvailability() {
    if (bookingData.coreServices.length > 0) {
      // Enable all addon checkboxes
      $(".service-checkbox[data-is-addon='true']").prop("disabled", false);
      $(".service-item.addon-item").removeClass("disabled");

      if (DEBUG) console.log("Addons enabled");
    } else {
      // Disable and uncheck all addon checkboxes
      $(".service-checkbox[data-is-addon='true']").prop("disabled", true).prop("checked", false);
      $(".service-item.addon-item").addClass("disabled").removeClass("selected");

      // Remove all addons from bookingData
      bookingData.addons = [];
      bookingData.services = bookingData.services.filter((service) => !service.isAddon);

      if (DEBUG) console.log("Addons disabled and removed from selection");
    }

    // Update addon section visibility
    if (bookingData.coreServices.length > 0) {
      $(".addon-title").show();
      $(".addon-services-container").show();
    } else {
      $(".addon-title").hide();
      $(".addon-services-container").hide();
    }
  }

  /**
   * Show validation alert when validation fails
   */
  function showValidationAlert(message) {
    // Create custom alert
    if ($(".validation-alert-overlay").length === 0) {
      const alertHtml = `
        <div class="validation-alert-overlay">
          <div class="validation-alert">
            <div class="validation-alert-title">Message</div>
            <div class="validation-alert-message">${message}</div>
            <button class="validation-alert-button">OK</button>
          </div>
        </div>
      `;

      $("body").append(alertHtml);

      $(document).on("click", ".validation-alert-button", function () {
        $(".validation-alert-overlay").remove();
      });
    }
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
      staffLevel: "",
      date: null,
      time: null,
      contact: {},
      initialOption: "services", // Default first step after initial
    };

    // Reset UI - hide all steps and show initial step
    $(".booking-step").removeClass("active");
    $('.booking-step[data-step="initial"]').addClass("active");

    // Reset selections
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

    // Reset summary
    $(".selected-master-info").empty();
    $(".summary-services-list").empty();
    $(".summary-total-amount").text("0.00");

    // Hide addons initially
    $(".addon-title").hide();
    $(".addon-services-container").hide();
    $(".service-item.addon-item").addClass("disabled");
    $(".service-checkbox[data-is-addon='true']").prop("disabled", true);

    if (DEBUG) console.log("Booking form reset");
  }

  /**
   * Go to a specific step
   */
  function goToStep(step) {
    $(".booking-step").removeClass("active");
    $('.booking-step[data-step="' + step + '"]').addClass("active");

    // Update next buttons based on initial flow
    if (step === "services") {
      const nextButtonText = bookingData.initialOption === "services" ? "Choose a master" : "Select date and time";
      $('.booking-step[data-step="services"] .next-btn').text(nextButtonText);
    }

    if (step === "master") {
      const nextButtonText = bookingData.initialOption === "master" ? "Select services" : "Select date and time";
      $('.booking-step[data-step="master"] .next-btn').text(nextButtonText);
    }

    if (DEBUG) console.log("Navigated to step:", step);
  }

  /**
   * Add a service to the booking
   */
  function addService(id, title, price, currency, duration, wearTime, isAddon, altegioId) {
    try {
      // Check if any of the required parameters are undefined
      if (!id) {
        console.error("Missing service ID", { id, title, price });
        return false;
      }

      if (!title) {
        console.error("Missing service title", { id, title, price });
        return false;
      }

      if (!price) {
        console.error("Missing service price", { id, title, price });
        return false;
      }

      // Check if service already exists
      const existingIndex = bookingData.services.findIndex((service) => service.id == id);

      if (existingIndex === -1) {
        // Add new service
        const newService = {
          id: id,
          altegioId: altegioId || id, // Store Altegio ID if available
          title: title,
          price: price,
          currency: currency || "SGD",
          isAddon: isAddon || false,
        };

        // Add optional fields if they exist
        if (duration) newService.duration = duration;
        if (wearTime) newService.wearTime = wearTime;

        // Add to bookingData
        bookingData.services.push(newService);

        // Also add to core or addon arrays
        if (isAddon) {
          bookingData.addons.push(newService);
        } else {
          bookingData.coreServices.push(newService);
        }

        if (DEBUG) console.log("Service added:", newService);
        return true;
      } else {
        if (DEBUG) console.log("Service already exists:", id, title);
        return false;
      }
    } catch (error) {
      console.error("Error in addService:", error);
      return false;
    }
  }

  /**
   * Remove a service from the booking
   */
  function removeService(id) {
    const initialLength = bookingData.services.length;

    // Get service to check if it's an addon
    const service = bookingData.services.find((s) => s.id == id);

    // Remove from main services array
    bookingData.services = bookingData.services.filter((service) => service.id != id);

    // Also remove from core or addon arrays
    if (service && service.isAddon) {
      bookingData.addons = bookingData.addons.filter((addon) => addon.id != id);
    } else {
      bookingData.coreServices = bookingData.coreServices.filter((core) => core.id != id);
    }

    // For debugging
    if (initialLength !== bookingData.services.length) {
      if (DEBUG) console.log("Service removed:", id);
    }
  }

  /**
   * Select a staff member
   */
  function selectStaff(id, name, avatar, level) {
    bookingData.staffId = id;
    bookingData.staffName = name;
    bookingData.staffAvatar = avatar;
    bookingData.staffLevel = level || "1";

    if (DEBUG) console.log("Staff selected:", id, name, "Level:", level);
  }

  /**
   * Load staff for selected services
   */
  function loadStaffForServices() {
    // If no services selected, return early
    if (bookingData.services.length === 0) {
      return;
    }

    const serviceIds = bookingData.services.map((service) => service.altegioId || service.id).join(",");

    if (DEBUG) console.log("Loading staff for services:", serviceIds);

    // Call AJAX to get staff for selected services
    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: {
        action: "get_staff",
        service_id: serviceIds,
        nonce: booking_params.nonce,
      },
      beforeSend: function () {
        $(".staff-list").html('<p class="loading-message">Loading specialists...</p>');
      },
      success: function (response) {
        if (response.success && response.data && response.data.data) {
          renderStaff(response.data.data);
        } else {
          $(".staff-list").html('<p class="error-message">Failed to load specialists. Please try again.</p>');
          console.error("Failed to load staff:", response);
        }
      },
      error: function (xhr, status, error) {
        $(".staff-list").html('<p class="error-message">Failed to load specialists. Please try again.</p>');
        console.error("AJAX error:", status, error);
      },
    });
  }

  /**
   * Load staff by ID (for pre-selected master)
   */
  function loadStaffById(staffId) {
    if (DEBUG) console.log("Loading staff by ID:", staffId);

    // Call AJAX to get staff details
    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: {
        action: "get_staff_details",
        staff_id: staffId,
        nonce: booking_params.nonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          const staff = response.data;
          selectStaff(staff.id, staff.name, staff.avatar, staff.level);

          // Update UI - mark this staff as selected
          $(".staff-item").removeClass("selected");
          $(".staff-item[data-staff-id='" + staff.id + "']").addClass("selected");

          if (DEBUG) console.log("Staff data loaded:", staff);
        } else {
          console.error("Failed to load staff details:", response);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", status, error);
      },
    });
  }

  /**
   * Render staff list
   */
  function renderStaff(staffList) {
    if (!staffList || staffList.length === 0) {
      $(".staff-list").html('<p class="no-items-message">No specialists available for the selected services.</p>');
      return;
    }

    let html = "";

    staffList.forEach(function (staff) {
      // Check if this staff is already selected
      const isSelected = bookingData.staffId == staff.id ? " selected" : "";
      const staffLevel = staff.level || "1";

      // Generate stars display
      let starsHtml = "";
      if (staffLevel > 1) {
        starsHtml = '<div class="staff-level">';
        for (let i = 0; i < staffLevel; i++) {
          starsHtml += '<span class="star">★</span>';
        }
        starsHtml += "</div>";
      }

      html += '<div class="staff-item' + isSelected + '" data-staff-id="' + staff.id + '" data-staff-level="' + staffLevel + '">';

      if (staff.avatar) {
        html += '<div class="staff-avatar"><img src="' + staff.avatar + '" alt="' + staff.name + '"></div>';
      }

      html += '<div class="staff-info">' + '<h4 class="staff-name">' + staff.name + "</h4>";

      if (staff.specialization) {
        html += '<p class="staff-specialization">' + staff.specialization + "</p>";
      }

      if (staffLevel > 1) {
        html += starsHtml;
      }

      html += "</div></div>";
    });

    $(".staff-list").html(html);
  }

  /**
   * Select a date
   */
  function selectDate(date) {
    bookingData.date = date;
    if (DEBUG) console.log("Date selected:", date);
  }

  /**
   * Select a time
   */
  function selectTime(time) {
    bookingData.time = time;
    if (DEBUG) console.log("Time selected:", time);
  }

  /**
   * Generate calendar
   */
  function generateCalendar() {
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();

    renderCalendar(currentMonth, currentYear);

    if (DEBUG) console.log("Calendar generated for:", currentMonth, currentYear);
  }

  /**
   * Navigate calendar
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

    if (DEBUG) console.log("Calendar navigated to:", newMonth, newYear);
  }

  /**
   * Get month index from name
   */
  function getMonthIndex(monthName) {
    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    return months.indexOf(monthName);
  }

  /**
   * Render calendar
   */
  function renderCalendar(month, year) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();

    // Adjust day of week (0 = Sunday, 1 = Monday, etc.)
    // Convert to Monday as first day (0 = Monday, 6 = Sunday)
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

      html += '<div class="' + classes + '" data-date="' + dateStr + '">' + i + "</div>";
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
   */
  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return year + "-" + month + "-" + day;
  }

  /**
   * Load time slots for selected date
   */
  function loadTimeSlots(date) {
    if (!bookingData.staffId) {
      $(".time-slots").html('<p class="error-message">Please select a specialist first.</p>');
      return;
    }

    // Show loading indicator
    $(".time-slots").html('<p class="loading-message">Loading available times...</p>');

    if (DEBUG) console.log("Loading time slots for date:", date, "staff:", bookingData.staffId);

    // Call AJAX to get time slots
    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: {
        action: "get_time_slots",
        staff_id: bookingData.staffId,
        date: date,
        nonce: booking_params.nonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          // Get time slots from response
          const slots = response.data.slots || [];
          if (DEBUG) console.log("Time slots loaded:", slots.length);
          renderTimeSlots(slots);
        } else {
          $(".time-slots").html('<p class="error-message">Failed to load time slots. Please try again.</p>');
          console.error("Failed to load time slots:", response);
        }
      },
      error: function (xhr, status, error) {
        $(".time-slots").html('<p class="error-message">Failed to load time slots. Please try again.</p>');
        console.error("AJAX error:", status, error);
      },
    });
  }

  /**
   * Render time slots
   */
  function renderTimeSlots(slots) {
    if (!slots || slots.length === 0) {
      $(".time-slots").html('<p class="no-slots-message">No available time slots for this date.</p>');
      return;
    }

    let html = "";

    slots.forEach(function (slot) {
      // Handle different slot formats
      let timeValue = "";
      let displayTime = "";

      if (typeof slot === "string") {
        // If slot is a string, extract time part if it's a datetime string
        if (slot.includes(" ")) {
          // Extract time from format "YYYY-MM-DD HH:MM:SS"
          timeValue = slot.split(" ")[1].substring(0, 5); // Get "HH:MM"
          displayTime = formatTimeDisplay(timeValue);
        } else {
          // Just a time string
          timeValue = slot;
          displayTime = formatTimeDisplay(slot);
        }
      } else if (typeof slot === "object" && slot.time) {
        // If slot is an object with time property
        timeValue = slot.time;
        displayTime = slot.display || formatTimeDisplay(slot.time);
      }

      // Check if this time slot was previously selected
      const isSelected = bookingData.time === timeValue ? " selected" : "";

      html += '<div class="time-slot' + isSelected + '" data-time="' + timeValue + '">' + displayTime + "</div>";
    });

    $(".time-slots").html(html);
  }

  /**
   * Format time for display (24h to 12h)
   */
  function formatTimeDisplay(timeStr) {
    // Convert 24-hour time to 12-hour format with AM/PM
    const [hours, minutes] = timeStr.split(":");
    const hour = parseInt(hours, 10);
    const period = hour >= 12 ? "PM" : "AM";
    const hour12 = hour % 12 || 12;
    return hour12 + ":" + minutes + " " + period;
  }

  /**
   * Calculate price with adjustment based on staff level
   */
  function calculateAdjustedPrice(basePrice, staffLevel) {
    let adjustmentPercent = 0;
    const level = parseInt(staffLevel) || 1;

    // 10% per level above 1
    if (level > 1) {
      adjustmentPercent = (level - 1) * 10;
    }

    // Convert price to number, removing currency symbols
    const numericPrice = parseFloat(basePrice.toString().replace(/[^\d.]/g, ""));

    if (isNaN(numericPrice)) {
      return basePrice; // Return original if conversion failed
    }

    // Calculate adjusted price
    const adjustment = numericPrice * (adjustmentPercent / 100);
    const adjustedPrice = numericPrice + adjustment;

    if (DEBUG) console.log(`Price adjustment: ${basePrice} + ${adjustmentPercent}% = ${adjustedPrice.toFixed(2)}`);

    return adjustedPrice.toFixed(2);
  }

  /**
   * Update booking summary
   */
  function updateSummary() {
    // Update selected master
    let masterHtml = "";
    if (bookingData.staffId) {
      masterHtml = '<div class="selected-master-item">';
      if (bookingData.staffAvatar) {
        masterHtml += '<div class="selected-master-avatar"><img src="' + bookingData.staffAvatar + '" alt="' + bookingData.staffName + '"></div>';
      }

      // Add stars for staff level if available
      let stars = "";
      if (bookingData.staffLevel && parseInt(bookingData.staffLevel) > 1) {
        const level = parseInt(bookingData.staffLevel);
        for (let i = 0; i < level; i++) {
          stars += "★";
        }
      }

      masterHtml += '<div class="selected-master-name">' + bookingData.staffName + (stars ? ' <span class="master-stars">' + stars + "</span>" : "") + "</div></div>";
    }
    $(".selected-master-info").html(masterHtml);

    // Update selected services
    let servicesHtml = "";
    let totalPrice = 0;

    bookingData.services.forEach(function (service) {
      // Calculate adjusted price based on staff level
      let displayPrice = service.price;
      let adjustedPrice = parseFloat(service.price.toString().replace(/[^\d.]/g, ""));

      // Apply staff level price adjustment
      if (bookingData.staffLevel && parseInt(bookingData.staffLevel) > 1) {
        const level = parseInt(bookingData.staffLevel);
        const adjustmentPercent = (level - 1) * 10; // 10% per level above 1
        const adjustment = adjustedPrice * (adjustmentPercent / 100);
        adjustedPrice += adjustment;

        // Format the adjusted price for display
        displayPrice = adjustedPrice.toFixed(2);
      }

      // Add price to total
      totalPrice += adjustedPrice;

      // Create service item HTML
      servicesHtml += '<div class="summary-service-item">' + '<span class="summary-service-name">' + service.title + (service.isAddon ? ' <span class="addon-label">(add-on)</span>' : "") + "</span>" + '<span class="summary-service-price">' + displayPrice + " " + service.currency + "</span>" + "</div>";
    });

    // Add master level price adjustment note if applicable
    if (bookingData.staffLevel && parseInt(bookingData.staffLevel) > 1) {
      const level = parseInt(bookingData.staffLevel);
      const adjustmentPercent = (level - 1) * 10; // 10% per level above 1

      servicesHtml += '<div class="price-adjustment-note">' + "Note: Prices include " + adjustmentPercent + "% adjustment for selected master level." + "</div>";
    }

    $(".summary-services-list").html(servicesHtml);
    $(".summary-total-amount").text(totalPrice.toFixed(2));

    if (DEBUG) console.log("Summary updated with total price:", totalPrice.toFixed(2));
  }

  /**
   * Validate date and time step
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
   * Validate contact step
   */
  function validateContactStep() {
    const name = $("#client-name").val().trim();
    const phone = $("#client-phone").val().trim();
    const email = $("#client-email").val().trim();

    if (!name) {
      showValidationAlert("Please enter your name");
      $("#client-name").focus();
      return false;
    }

    if (!phone) {
      showValidationAlert("Please enter your phone number");
      $("#client-phone").focus();
      return false;
    }

    // Basic email validation if provided
    if (email && !isValidEmail(email)) {
      showValidationAlert("Please enter a valid email address");
      $("#client-email").focus();
      return false;
    }

    // Save contact info
    bookingData.contact = {
      name: name,
      phone: phone,
      email: email,
      comment: $("#client-comment").val().trim(),
    };

    if (DEBUG) console.log("Contact info validated and saved:", bookingData.contact);
    return true;
  }

  /**
   * Validate email format
   */
  function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  /**
   * Format date for display
   */
  function formatDateDisplay(dateStr) {
    const date = new Date(dateStr);
    const options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    };
    return date.toLocaleDateString("en-US", options);
  }

  /**
   * Submit booking
   */
  function submitBooking() {
    // Prepare data for submission
    const serviceIds = bookingData.services.map((service) => service.altegioId || service.id).join(",");

    // Disable button and show loading state
    $(".confirm-booking-btn").prop("disabled", true).text("Processing...");

    if (DEBUG)
      console.log("Submitting booking with data:", {
        serviceIds,
        staffId: bookingData.staffId,
        date: bookingData.date,
        time: bookingData.time,
        clientInfo: bookingData.contact,
        coreServices: bookingData.coreServices.map((s) => s.id),
        addons: bookingData.addons.map((s) => s.id),
        staffLevel: bookingData.staffLevel,
      });

    // Submit to server
    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: {
        action: "submit_booking",
        booking_nonce: booking_params.nonce,
        service_id: serviceIds,
        staff_id: bookingData.staffId,
        date: bookingData.date,
        time: bookingData.time,
        client_name: bookingData.contact.name,
        client_phone: bookingData.contact.phone,
        client_email: bookingData.contact.email,
        client_comment: bookingData.contact.comment,
        // Add new data about core services and addons
        core_services: JSON.stringify(bookingData.coreServices.map((s) => s.altegioId || s.id)),
        addon_services: JSON.stringify(bookingData.addons.map((s) => s.altegioId || s.id)),
        staff_level: bookingData.staffLevel,
      },
      success: function (response) {
        // Re-enable button
        $(".confirm-booking-btn").prop("disabled", false).text("Confirm booking");

        if (response.success) {
          if (DEBUG) console.log("Booking submitted successfully:", response.data);

          // Show confirmation
          const booking = response.data.booking || {};
          const reference =
            booking.reference ||
            "BK" +
              Math.floor(Math.random() * 10000)
                .toString()
                .padStart(4, "0");

          // Set booking reference
          $(".booking-reference").text(reference);

          // Set date and time
          const formattedDate = formatDateDisplay(bookingData.date);
          $(".booking-date").text(formattedDate);
          $(".booking-time").text(formatTimeDisplay(bookingData.time));

          // Build services summary with adjusted prices
          let servicesHtml = '<h4>Booked Services</h4><div class="booked-services-list">';

          bookingData.services.forEach(function (service) {
            // Calculate adjusted price based on staff level
            let displayPrice = service.price;
            let adjustedPrice = parseFloat(service.price.toString().replace(/[^\d.]/g, ""));

            // Apply staff level price adjustment
            if (bookingData.staffLevel && parseInt(bookingData.staffLevel) > 1) {
              const level = parseInt(bookingData.staffLevel);
              const adjustmentPercent = (level - 1) * 10; // 10% per level above 1
              const adjustment = adjustedPrice * (adjustmentPercent / 100);
              adjustedPrice += adjustment;

              // Format the adjusted price for display
              displayPrice = adjustedPrice.toFixed(2);
            }

            servicesHtml += '<div class="booked-service-item">' + '<span class="booked-service-name">' + service.title + (service.isAddon ? ' <span class="addon-label">(add-on)</span>' : "") + "</span>" + '<span class="booked-service-price">' + displayPrice + " " + service.currency + "</span>" + "</div>";
          });

          // Add master level info if available
          let masterInfo = bookingData.staffName;
          if (bookingData.staffLevel) {
            const level = parseInt(bookingData.staffLevel);
            let stars = "";
            for (let i = 0; i < level; i++) {
              stars += "★";
            }
            masterInfo += ' <span class="master-stars">' + stars + "</span>";
          }

          servicesHtml += '</div><div class="booked-master">Specialist: ' + masterInfo + "</div>";

          $(".booked-services-summary").html(servicesHtml);

          // Show confirmation step
          goToStep("confirm");
        } else {
          showValidationAlert(response.data?.message || "Booking failed. Please try again.");
          console.error("Booking submission failed:", response);
        }
      },
      error: function (xhr, status, error) {
        // Re-enable button
        $(".confirm-booking-btn").prop("disabled", false).text("Confirm booking");
        showValidationAlert("Error submitting booking. Please try again.");
        console.error("AJAX error:", status, error);
      },
    });
  }
})(jQuery);
