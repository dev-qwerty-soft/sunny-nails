/**
 * Booking Popup Script
 */
(function ($) {
  "use strict";

  // Booking data object
  let bookingData = {
    selectedOption: "services", // Initial selection
    staffId: null,
    staffName: "",
    staffAvatar: "",
    services: [],
    date: null,
    time: null,
    contact: {},
  };

  // Initialize when document is ready
  $(document).ready(function () {
    initializeBookingPopup();
  });

  /**
   * Initialize the booking popup
   */
  function initializeBookingPopup() {
    // Open popup
    $(document).on("click", ".open-popup", function (e) {
      e.preventDefault();
      resetBookingForm();

      $(".booking-popup-overlay").addClass("active");

      // Pre-select service if specified
      const service = $(this).data("service");
      if (service) {
        bookingData.selectedOption = "services";
        updateInitialScreen();
      }
    });

    // Close popup
    $(document).on("click", ".booking-popup-close, .close-popup-btn", function () {
      $(".booking-popup-overlay").removeClass("active");
    });

    // Close on clicking outside the popup
    $(document).on("click", ".booking-popup-overlay", function (e) {
      if ($(e.target).is(".booking-popup-overlay")) {
        $(".booking-popup-overlay").removeClass("active");
      }
    });

    // Close on overlay click (outside popup)
    $(document).on("click", ".booking-popup-overlay", function (e) {
      if ($(e.target).is(".booking-popup-overlay")) {
        $(".booking-popup-overlay").fadeOut(300);
      }
    });

    // Option selection in initial screen
    $(document).on("click", ".booking-option-item", function () {
      const option = $(this).data("option");
      bookingData.selectedOption = option;

      // Update UI
      $(".booking-option-item").removeClass("active");
      $(this).addClass("active");

      // Update status indicators
      $(".status-indicator").removeClass("active");
      $(this).find(".status-indicator").addClass("active");
    });

    // Next button in initial screen
    $(document).on("click", '.booking-step[data-step="initial"] .next-btn', function () {
      goToStep(bookingData.selectedOption);
    });

    // Staff selection
    $(document).on("click", ".staff-item", function () {
      const staffId = $(this).data("staff-id");
      const staffName = $(this).find(".staff-name").text();
      const staffAvatar = $(this).find(".staff-avatar img").attr("src") || "";

      selectStaff(staffId, staffName, staffAvatar);

      // Update UI
      $(".staff-item").removeClass("selected");
      $(this).addClass("selected");
    });

    // Service selection
    $(document).on("click", ".select-service-btn", function () {
      const serviceItem = $(this).closest(".service-item");
      const serviceId = serviceItem.data("service-id");
      const serviceName = serviceItem.find(".service-name").text();
      const servicePrice = serviceItem.find(".service-price").text();

      addService(serviceId, serviceName, servicePrice);
    });

    // Remove service
    $(document).on("click", ".remove-service", function () {
      const serviceItem = $(this).closest(".selected-service-item");
      const serviceId = serviceItem.data("service-id");

      removeService(serviceId);
    });

    // Next button in services step
    $(document).on("click", '.booking-step[data-step="services"] .next-btn', function () {
      if (validateServicesStep()) {
        goToStep("master");
      }
    });

    // Next button in master step
    $(document).on("click", '.booking-step[data-step="master"] .next-btn', function () {
      if (validateMasterStep()) {
        goToStep("datetime");
        generateCalendar();
      }
    });

    // Calendar day selection
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
   * Reset the booking form to initial state
   */
  function resetBookingForm() {
    // Reset booking data
    bookingData = {
      selectedOption: "services",
      staffId: null,
      staffName: "",
      staffAvatar: "",
      services: [],
      date: null,
      time: null,
      contact: {},
    };

    // Reset UI - hide all steps and show initial step
    $(".booking-step").removeClass("active");
    $('.booking-step[data-step="initial"]').addClass("active");

    // Reset selections
    $(".staff-item").removeClass("selected");
    $(".selected-services-list").empty();
    $(".total-price-amount").text("$0.00");
    $(".calendar-day").removeClass("selected");
    $(".time-slot").removeClass("selected");

    // Reset form fields
    $("#client-name, #client-phone, #client-email, #client-comment").val("");

    // Reset summary
    $(".selected-master-info").empty();
    $(".summary-services-list").empty();
    $(".summary-total-amount").text("$0.00");

    // Update initial screen options
    updateInitialScreen();
  }

  /**
   * Update the initial screen based on selected option
   */
  function updateInitialScreen() {
    $(".booking-option-item").removeClass("active");
    $(".status-indicator").removeClass("active");

    $(`.booking-option-item[data-option="${bookingData.selectedOption}"]`).addClass("active");
    $(`.booking-option-item[data-option="${bookingData.selectedOption}"] .status-indicator`).addClass("active");
  }

  /**
   * Go to a specific step
   */
  function goToStep(step) {
    // Hide all steps
    $(".booking-step").removeClass("active");

    // Show the requested step
    $(`.booking-step[data-step="${step}"]`).addClass("active");
  }

  /**
   * Select a staff member
   */
  function selectStaff(id, name, avatar) {
    bookingData.staffId = id;
    bookingData.staffName = name;
    bookingData.staffAvatar = avatar;
  }

  /**
   * Add a service to the booking
   */
  function addService(id, name, price) {
    // Check if service already in the list
    const exists = bookingData.services.some((service) => service.id === id);

    if (!exists) {
      // Add to booking data
      bookingData.services.push({
        id: id,
        name: name,
        price: price,
      });

      // Update UI
      renderSelectedServices();
    }
  }

  /**
   * Remove a service from the booking
   */
  function removeService(id) {
    // Remove from booking data
    bookingData.services = bookingData.services.filter((service) => service.id !== id);

    // Update UI
    renderSelectedServices();
  }

  /**
   * Render the selected services list
   */
  function renderSelectedServices() {
    let html = "";
    let totalPrice = 0;

    // Generate HTML for each service
    bookingData.services.forEach((service) => {
      html += `<div class="selected-service-item" data-service-id="${service.id}">
              <span class="selected-service-name">${service.name}</span>
              <span class="selected-service-price">${service.price}</span>
              <button type="button" class="remove-service">&times;</button>
          </div>`;

      // Add to total price (remove non-numeric characters and parse)
      const price = parseFloat(service.price.replace(/[^\d.]/g, ""));
      if (!isNaN(price)) {
        totalPrice += price;
      }
    });

    // Update the UI
    $(".selected-services-list").html(html);
    $(".total-price-amount").text("$" + totalPrice.toFixed(2));
  }

  /**
   * Select a date for the booking
   */
  function selectDate(date) {
    bookingData.date = date;
  }

  /**
   * Select a time for the booking
   */
  function selectTime(time) {
    bookingData.time = time;
  }

  /**
   * Generate calendar for date selection
   */
  function generateCalendar() {
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();

    renderCalendar(currentMonth, currentYear);
  }

  /**
   * Render a calendar for the given month and year
   */
  function renderCalendar(month, year) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay() || 7; // Make Monday 1, Sunday 7

    // Update month name in header
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    $(".month-header span").text(monthNames[month] + " " + year);

    let html = "";

    // Add empty cells for days before first day of month (adjust for Monday as first day)
    for (let i = 1; i < startingDay; i++) {
      html += '<div class="calendar-day empty"></div>';
    }

    // Add days of the month
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let i = 1; i <= daysInMonth; i++) {
      const date = new Date(year, month, i);
      date.setHours(0, 0, 0, 0);

      const dateStr = formatDate(date);
      const isToday = date.getTime() === today.getTime();
      const isPast = date < today;

      let classes = "calendar-day";
      if (isToday) classes += " today";
      if (isPast) classes += " disabled";

      html += `<div class="${classes}" data-date="${dateStr}">${i}</div>`;
    }

    // Update calendar grid
    $(".calendar-grid").html(html);
  }

  /**
   * Format a date as YYYY-MM-DD
   */
  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  /**
   * Load time slots for selected date
   */
  function loadTimeSlots(date) {
    // In a real implementation, you would fetch from server
    // For demo, generate sample time slots
    const slots = generateSampleTimeSlots();

    let html = "";
    slots.forEach((slot) => {
      const classes = slot.available ? "time-slot" : "time-slot disabled";
      html += `<div class="${classes}" data-time="${slot.time}">${slot.display}</div>`;
    });

    $(".time-slots").html(html);
  }

  /**
   * Generate sample time slots for demo
   */
  function generateSampleTimeSlots() {
    const slots = [];
    const startHour = 9; // 9 AM
    const endHour = 19; // 7 PM

    for (let hour = startHour; hour < endHour; hour++) {
      for (let minute of [0, 30]) {
        const hourDisplay = hour > 12 ? hour - 12 : hour;
        const ampm = hour >= 12 ? "PM" : "AM";
        const timeStr = `${hour.toString().padStart(2, "0")}:${minute.toString().padStart(2, "0")}`;
        const displayStr = `${hourDisplay}:${minute.toString().padStart(2, "0")} ${ampm}`;

        // Randomly determine if slot is available
        const available = Math.random() > 0.3;

        slots.push({
          time: timeStr,
          display: displayStr,
          available: available,
        });
      }
    }

    return slots;
  }

  /**
   * Update the booking summary
   */
  function updateSummary() {
    // Update selected master info
    let masterHtml = "";
    if (bookingData.staffId) {
      masterHtml = `
              <div class="selected-master-avatar">
                  ${bookingData.staffAvatar ? `<img src="${bookingData.staffAvatar}" alt="${bookingData.staffName}">` : ""}
              </div>
              <div class="selected-master-name">${bookingData.staffName}</div>
          `;
    }
    $(".selected-master-info").html(masterHtml);

    // Update selected services
    let servicesHtml = "";
    let totalPrice = 0;

    bookingData.services.forEach((service) => {
      servicesHtml += `<div class="summary-service-item">
              <span class="summary-service-name">${service.name}</span>
              <span class="summary-service-price">${service.price}</span>
          </div>`;

      // Add to total price
      const price = parseFloat(service.price.replace(/[^\d.]/g, ""));
      if (!isNaN(price)) {
        totalPrice += price;
      }
    });

    $(".summary-services-list").html(servicesHtml);
    $(".summary-total-amount").text("$" + totalPrice.toFixed(2));
  }

  /**
   * Validate the services step
   */
  function validateServicesStep() {
    if (bookingData.services.length === 0) {
      alert("Please select at least one service");
      return false;
    }
    return true;
  }

  /**
   * Validate the master step
   */
  function validateMasterStep() {
    if (!bookingData.staffId) {
      alert("Please select a specialist");
      return false;
    }
    return true;
  }

  /**
   * Validate the date and time step
   */
  function validateDateTimeStep() {
    if (!bookingData.date) {
      alert("Please select a date");
      return false;
    }

    if (!bookingData.time) {
      alert("Please select a time");
      return false;
    }

    return true;
  }

  /**
   * Validate the contact step
   */
  function validateContactStep() {
    const name = $("#client-name").val().trim();
    const phone = $("#client-phone").val().trim();

    if (!name) {
      alert("Please enter your name");
      $("#client-name").focus();
      return false;
    }

    if (!phone) {
      alert("Please enter your phone number");
      $("#client-phone").focus();
      return false;
    }

    // Save contact information
    bookingData.contact = {
      name: name,
      phone: phone,
      email: $("#client-email").val().trim(),
      comment: $("#client-comment").val().trim(),
    };

    return true;
  }

  /**
   * Submit the booking
   */
  function submitBooking() {
    // In a real implementation, you would send data to server
    console.log("Submitting booking:", bookingData);

    // For demo, just show confirmation
    // Generate random booking reference
    const bookingReference =
      "BK" +
      Math.floor(Math.random() * 10000)
        .toString()
        .padStart(4, "0");

    // Update confirmation screen
    $(".booking-reference").text(bookingReference);

    // Show confirmation screen
    goToStep("confirm");
  }
})(jQuery);
