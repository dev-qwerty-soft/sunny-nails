/**
 * Services Page Booking Enhancement
 *
 * This script adds specialized booking functionality to the Services page:
 * - Opens booking popup directly to master selection when clicking "Book this"
 * - Pre-selects the service and loads compatible masters
 * - Works with the existing booking system without modification
 */
jQuery(function ($) {
  "use strict";

  // Only initialize on services page
  if (!$("body").hasClass("page-template-services") && !window.location.pathname.includes("/services/")) {
    return;
  }

  console.log("Services page booking enhancement initialized");

  // Override the Book This button on service cards
  $(document).on("click", ".service-card .book-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    // Get service data from the card
    const $serviceCard = $(this).closest(".service-card");
    const serviceId = $serviceCard.data("service-id");
    const serviceTitle = $serviceCard.find(".service-title").text().trim();
    const servicePriceText = $serviceCard.find(".service-price").text().trim();
    const serviceDuration = $serviceCard.find(".service-duration").text().replace("Duration:", "").trim();
    const serviceWearTime = $serviceCard.find(".service-wear-time").text().replace("Wear time:", "").trim();
    const serviceDesc = $serviceCard.find(".service-description").text().trim();

    // Parse price (remove currency symbol)
    const priceMatch = servicePriceText.match(/(\d+(?:\.\d+)?)/);
    const servicePrice = priceMatch ? priceMatch[0] : "0";
    const currency = servicePriceText.replace(/[\d.,]/g, "").trim() || "SGD";

    console.log("Service selected:", {
      id: serviceId,
      title: serviceTitle,
      price: servicePrice,
      currency: currency,
      duration: serviceDuration,
      wearTime: serviceWearTime,
    });

    // Reset the booking data
    if (typeof window.resetBookingForm === "function") {
      window.resetBookingForm();
    } else {
      // Initialize empty booking data if reset function not available
      window.bookingData = window.bookingData || {};
      window.bookingData.services = [];
      window.bookingData.coreServices = [];
      window.bookingData.addons = [];
      window.bookingData.staffId = null;
      window.bookingData.staffName = "";
      window.bookingData.staffAvatar = "";
      window.bookingData.staffLevel = 1;
      window.bookingData.date = null;
      window.bookingData.time = null;
      window.bookingData.contact = {};
      window.bookingData.flowHistory = ["initial", "master"];
      window.bookingData.initialOption = "master";
    }

    // Add selected service to booking data
    window.bookingData.preSelectedServiceId = serviceId;

    // Create service object
    const serviceObject = {
      id: serviceId,
      title: serviceTitle,
      price: servicePrice,
      currency: currency,
      duration: serviceDuration,
      wearTime: serviceWearTime,
      description: serviceDesc,
      isAddon: false,
    };

    // Add to bookingData
    window.bookingData.services = [serviceObject];
    window.bookingData.coreServices = [serviceObject];
    window.bookingData.initialOption = "master";
    window.bookingData.flowHistory = ["initial", "master"];

    // Open booking popup
    $(".booking-popup-overlay").addClass("active");

    // Load masters for this service via AJAX
    loadMastersForService(serviceId);
  });

  /**
   * Load masters that can perform this service
   */
  function loadMastersForService(serviceId) {
    console.log("Loading masters for service", serviceId);

    // Show loading indicator
    $(".staff-list").html('<p class="loading-message">Loading specialists...</p>');

    // Call AJAX to get masters for this service
    $.ajax({
      url: booking_params.ajax_url || ajaxurl || "/wp-admin/admin-ajax.php",
      type: "POST",
      data: {
        action: "get_filtered_staff",
        service_id: serviceId,
        nonce: booking_params.nonce || $('input[name="booking_nonce"]').val(),
      },
      success: function (response) {
        console.log("Master response:", response);

        if (response.success && response.data && Array.isArray(response.data.data)) {
          // Render masters using existing function if available
          if (typeof window.renderStaff === "function") {
            window.renderStaff(response.data.data);
          } else {
            // Fallback rendering
            renderMastersList(response.data.data);
          }

          // Go to master step
          goToBookingStep("master");
        } else {
          // Show error or fallback
          $(".staff-list").html('<p class="no-items-message">No specialists available for this service.</p>');
          goToBookingStep("master");
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading masters:", error);
        $(".staff-list").html('<p class="no-items-message">Error loading specialists.</p>');
        goToBookingStep("master");
      },
    });
  }

  /**
   * Navigate to a booking step
   */
  function goToBookingStep(step) {
    // Use the main function if available
    if (typeof window.goToStep === "function") {
      window.goToStep(step);
    } else {
      // Simple fallback
      $(".booking-step").removeClass("active");
      $(`.booking-step[data-step="${step}"]`).addClass("active");

      // Update next button text
      if (step === "master") {
        $(`.booking-step[data-step="master"] .next-btn`).text("Select date and time");
      }
    }

    console.log("Booking step changed to:", step);
  }

  /**
   * Render masters list without depending on main function
   */
  function renderMastersList(staffList) {
    if (!staffList || staffList.length === 0) {
      $(".staff-list").html('<p class="no-items-message">No specialists available for this service.</p>');
      return;
    }

    const levelTitles = {
      1: "Sunny Ray",
      2: "Sunny Shine",
      3: "Sunny Inferno",
    };

    // Generate staff items
    let html = `
      <label class="staff-item any-master first" data-staff-id="any" data-staff-level="1">
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

    // Add each staff member
    staffList.forEach(function (staff) {
      const staffLevel = staff.level || 1;
      const levelTitle = levelTitles[staffLevel] || "";

      // Calculate price modifier if level > 1
      let priceModifier = "";
      if (staffLevel > 1) {
        const priceIncrease = (staffLevel - 1) * 10; // 10% per level
        priceModifier = `<div class="staff-price-modifier">+${priceIncrease}% to price</div>`;
      }

      // Generate stars based on level
      let starsHtml = "";
      if (staffLevel > 0) {
        starsHtml = '<div class="staff-stars">';
        for (let i = 0; i < staffLevel; i++) {
          starsHtml += '<span class="star">★</span>';
        }
        starsHtml += "</div>";
      }

      html += `
        <label class="staff-item" data-staff-id="${staff.id}" data-staff-level="${staffLevel}">
          <input type="radio" name="staff">
          <div class="staff-radio-content">
            <div class="staff-avatar">
              ${staff.avatar ? `<img src="${staff.avatar}" alt="${staff.name}">` : ""}
            </div>
            <div class="staff-info">
              <h4 class="staff-name">${staff.name}</h4>
              <div class="staff-specialization">
                ${starsHtml}
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

    // Handle staff selection
    $(".staff-item").on("click", function () {
      const staffId = $(this).data("staff-id");
      const staffName = $(this).find(".staff-name").text();
      const staffLevel = $(this).data("staff-level") || 1;
      let staffAvatar = "";

      const avatarImg = $(this).find(".staff-avatar img");
      if (avatarImg.length) {
        staffAvatar = avatarImg.attr("src") || "";
      }

      // Update booking data
      window.bookingData.staffId = staffId;
      window.bookingData.staffName = staffName;
      window.bookingData.staffAvatar = staffAvatar;
      window.bookingData.staffLevel = parseInt(staffLevel) || 1;

      // Update UI
      $(".staff-item").removeClass("selected");
      $(this).addClass("selected");

      // Enable next button
      $(`.booking-step[data-step="master"] .next-btn`).prop("disabled", false);

      console.log("Master selected:", {
        id: staffId,
        name: staffName,
        level: staffLevel,
      });
    });
  }

  // Handle navigation to datetime after master selection
  $(document).on("click", '.booking-step[data-step="master"] .next-btn', function () {
    // Only handle on services page
    if (window.bookingData && window.bookingData.initialOption === "master") {
      // Ensure we go to datetime after master selection
      window.bookingData.flowHistory = ["initial", "master", "datetime"];

      // If there's a master selected, generate calendar
      if (window.bookingData.staffId) {
        if (typeof window.generateCalendar === "function") {
          window.generateCalendar();
        }
        goToBookingStep("datetime");
      }
    }
  });
});
