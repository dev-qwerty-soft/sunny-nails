/**
 * Service Page Booking Integration
 *
 * This script enhances the booking functionality on the Services page,
 * allowing direct booking from service cards by integrating with the
 * main booking system.
 */
(function ($) {
  "use strict";

  // Variable to store the selected service data
  let selectedServiceData = null;

  console.log("Services page booking enhancement script loaded");

  $(document).ready(function () {
    // Only initialize if we're on the services page
    if (!$(".services-section").length) return;

    console.log("Services page booking enhancement initialized");
    initServiceCardButtons();
    customizeBackButton();
  });

  /**
   * Initialize booking buttons on service cards
   */
  function initServiceCardButtons() {
    $(document).on("click", ".service-card .book-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Get service data from the service card
      const $serviceCard = $(this).closest(".service-card");
      const serviceId = $serviceCard.data("service-id");

      // Extract service title (remove price element first)
      const serviceTitleElement = $serviceCard.find(".service-title").clone();
      serviceTitleElement.find(".service-price").remove();
      const serviceTitle = serviceTitleElement.text().trim();

      // Extract other service details
      const servicePriceText = $serviceCard.find(".service-price").text().trim();
      const serviceDuration = $serviceCard.find(".service-duration").length ? $serviceCard.find(".service-duration").text().replace("Duration:", "").trim() : "";
      const serviceWearTime = $serviceCard.find(".service-wear-time").length ? $serviceCard.find(".service-wear-time").text().replace("Wear time:", "").trim() : "";
      const serviceDesc = $serviceCard.find(".service-description").length ? $serviceCard.find(".service-description").text().trim() : "";

      // Extract price and currency
      const priceMatch = servicePriceText.match(/(\d+(?:\.\d+)?)/);
      const servicePrice = priceMatch ? priceMatch[0] : "0";
      const currency = servicePriceText.replace(/[\d.,]/g, "").trim() || "SGD";

      console.log("Service selected:", {
        id: serviceId,
        title: serviceTitle,
        price: servicePrice,
        duration: serviceDuration,
        wearTime: serviceWearTime,
      });

      // Create service data object
      selectedServiceData = {
        id: serviceId,
        altegioId: serviceId,
        title: serviceTitle,
        price: servicePrice,
        currency: currency,
        isAddon: false,
        duration: serviceDuration,
        wearTime: serviceWearTime,
        desc: serviceDesc,
      };

      // Save selected service to localStorage for persistence
      try {
        localStorage.setItem("selectedServiceData", JSON.stringify(selectedServiceData));
      } catch (e) {
        console.error("Failed to save to localStorage:", e);
      }

      // Call the main code function to handle the rest of the booking flow
      handleBookingFlow(selectedServiceData);

      // Open booking popup
      openBookingPopup();

      // Load masters for this service
      loadMastersForService(serviceId);
    });
  }

  /**
   * Function to call main booking flow code to reset and add the service
   * @param {Object} selectedServiceData - The selected service data
   */
  function handleBookingFlow(selectedServiceData) {
    // Call the main function for resetting booking data
    if (typeof window.resetBookingData === "function") {
      window.resetBookingData(); // Reset booking data
    }

    // Add selected service to the global booking data
    if (typeof window.bookingData === "undefined") {
      window.bookingData = {};
    }

    // Add service to core services and general services
    window.bookingData.services = window.bookingData.services || [];
    window.bookingData.coreServices = window.bookingData.coreServices || [];
    window.bookingData.addons = window.bookingData.addons || [];

    window.bookingData.services.push(selectedServiceData);
    window.bookingData.coreServices.push(selectedServiceData);

    // Initialize any additional properties like flow steps
    window.bookingData.initialOption = "master";
    window.bookingData.flowHistory = ["initial", "master"];
    window.bookingData.staffId = null;
    window.bookingData.staffName = "";
    window.bookingData.staffAvatar = "";
    window.bookingData.staffLevel = 1;
    window.bookingData.date = null;
    window.bookingData.time = null;
    window.bookingData.contact = {};

    console.log("Booking data updated with selected service:", selectedServiceData);
  }

  /**
   * Open booking popup and go to master selection step
   */
  function openBookingPopup() {
    $(".booking-popup-overlay").addClass("active");

    // Use goToStep if it exists, otherwise manually change steps
    if (typeof window.goToStep === "function") {
      window.goToStep("master");
    } else {
      $(".booking-step").removeClass("active");
      $('.booking-step[data-step="master"]').addClass("active");
    }
  }

  /**
   * Load masters for the selected service
   */
  function loadMastersForService(serviceId) {
    console.log("Loading masters for service", serviceId);
    $(".staff-list").html('<p class="loading-message">Loading specialists...</p>');

    $.ajax({
      url: booking_params.ajax_url,
      type: "POST",
      data: {
        action: "get_filtered_staff",
        service_id: serviceId,
        nonce: booking_params.nonce,
      },
      success: function (response) {
        console.log("Master response:", response);
        if (response.success && response.data && Array.isArray(response.data.data)) {
          if (typeof window.renderStaff === "function") {
            window.renderStaff(response.data.data);
          } else {
            renderMastersList(response.data.data);
          }
        } else {
          $(".staff-list").html('<p class="no-items-message">No specialists available for this service.</p>');
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading masters:", error);
        $(".staff-list").html('<p class="error-message">Error loading specialists.</p>');
      },
    });
  }

  /**
   * Render masters list if main renderStaff function is not available
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

    staffList.forEach(function (staff) {
      const staffLevel = parseInt(staff.level) || 1;
      const levelTitle = levelTitles[staffLevel] || "";

      let priceModifier = "";
      if (staffLevel > 1) {
        const priceIncrease = (staffLevel - 1) * 10;
        priceModifier = `<div class="staff-price-modifier">+${priceIncrease}% to price</div>`;
      }

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

    // Add click handler to staff items if not already handled
    if (typeof window.selectStaff !== "function") {
      $(".staff-list .staff-item").on("click", function () {
        const staffId = $(this).data("staff-id");
        const staffName = $(this).find(".staff-name").text();
        const staffLevel = parseInt($(this).data("staff-level")) || 1;
        let staffAvatar = "";

        const avatarImg = $(this).find(".staff-avatar img");
        if (avatarImg.length) {
          staffAvatar = avatarImg.attr("src") || "";
        }

        // Update bookingData
        window.bookingData.staffId = staffId;
        window.bookingData.staffName = staffName;
        window.bookingData.staffAvatar = staffAvatar;
        window.bookingData.staffLevel = staffLevel;

        // Update UI
        $(".staff-item").removeClass("selected");
        $(this).addClass("selected");

        console.log("Master selected:", {
          id: staffId,
          name: staffName,
          level: staffLevel,
        });
      });
    }
  }

  /**
   * Customize back button behavior for the service page flow
   */
  function customizeBackButton() {
    $(document).on("click", ".booking-back-btn", function () {
      const currentStep = $(this).closest(".booking-step").data("step");

      // If we're on the master step and came from a service card,
      // close the popup instead of going back to initial step
      if (currentStep === "master" && window.bookingData && window.bookingData.initialOption === "master") {
        $(".booking-popup-overlay").removeClass("active");
        return false;
      }
    });
  }
})(jQuery);
