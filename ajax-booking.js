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
  'use strict';

  // Booking data object to store all selected information
  let bookingData = {
    services: [], // All selected services
    coreServices: [], // Only core services (not add-ons)
    addons: [], // Only add-on services
    staffId: null, // Selected master ID
    staffName: '', // Selected master name
    staffAvatar: '', // Selected master avatar URL
    staffLevel: 1, // Selected master level (stars) - default to 1
    date: null, // Selected date (YYYY-MM-DD)
    time: null, // Selected time (HH:MM)
    coupon: null,
    contact: {}, // Customer contact information
    flowHistory: ['initial'], // Track step navigation history for back button
    initialOption: 'services', // Default first step after initial
  };

  // Configuration
  const config = {
    debug: true,
    priceAdjustmentPerLevel: 10,
    apiEndpoint: booking_params.ajax_url,
    nonce: booking_params.nonce,
    simulateTimeSlots: true,
    useLocalStorage: true,
    maxRetries: 3,
  };

  /**
   * Generate stars HTML based on level
   * @param {number} level - Star level (1-5)
   * @returns {string} - HTML with star SVGs
   */
  const levelTitles = {
    [-1]: 'Intern',
    1: 'Sunny Ray',
    2: 'Sunny Shine',
    3: 'Sunny Inferno',
    4: 'Trainer',
    5: 'Sunny Inferno, Supervisor',
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
    if (typeof level === 'undefined' || level === null) return '';

    const starsCount = starsMap[level];

    if (typeof starsCount === 'undefined' || starsCount === 0) return '';

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
    if (!config.debug) return;
    if (data !== undefined) {
      console.log(message, data);
    } else {
      console.log(message);
    }
  }

  function showBookingDetailsNotification() {
    const notification = $('.booking-details-notification');

    if (notification.length) {
      notification.addClass('show');

      setTimeout(() => {
        notification.removeClass('show');
      }, 5000);
    }
  }

  /**
   * Initialize floating notification behavior
   */
  function initFloatingNotification() {
    const notification = $('.booking-details-notification');
    const summaryBox = $('.booking-summary-box');

    if (!notification.length || !summaryBox.length) {
      return;
    }

    function toggleNotificationVisibility() {
      const summaryBoxElement = summaryBox[0];
      const scrollTop = summaryBoxElement.scrollTop;
      const scrollHeight = summaryBoxElement.scrollHeight;
      const clientHeight = summaryBoxElement.clientHeight;

      // Show notification when user has scrolled down significantly
      // Hide when at the top or near the top
      const scrollPercentage = scrollTop / (scrollHeight - clientHeight);

      if (scrollPercentage > 0.3) {
        // Show when scrolled more than 30%
        notification.addClass('visible');
      } else {
        notification.removeClass('visible');
      }
    }

    // Listen to scroll events on the summary box
    summaryBox.on('scroll', toggleNotificationVisibility);

    // Initial check
    toggleNotificationVisibility();
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
    initCountrySelector();
    initFloatingNotification(); // Initialize floating notification

    $(document).on('click', '.booking-details-notification', function () {
      try {
        const summaryBox = $('.booking-summary-box');
        if (summaryBox.length) {
          // Scroll to top of the summary box
          summaryBox.animate({ scrollTop: 0 }, 'smooth');
        } else {
          // Fallback to window scroll
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        $(this).removeClass('visible show');
      } catch (e) {
        debug('Error scrolling to top:', e);
      }
    });

    if (config.useLocalStorage) {
      restoreBookingSession();
    }

    debug('Booking system initialized');
  });

  /**
   * Initialize local storage support for saving booking progress
   */
  function initLocalStorageSupport() {
    if (!config.useLocalStorage) return;

    // Save booking data on each step change
    $(document).on('bookingStepChanged', function (e, step) {
      saveBookingSession();
    });

    // Save on form field changes with debounce
    let debounceTimer;
    $(document).on('change keyup', '.contact-form input, .contact-form textarea', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        // Save contact info before saving session
        let name = $('#client-name').val();
        let phone = $('#client-phone').val();
        let email = $('#client-email').val();
        let comment = $('#client-comment').val();

        bookingData.contact = {
          name: name || '',
          phone: phone || '',
          email: email || '',
          comment: comment || '',
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
      localStorage.setItem('altegio_booking_data', JSON.stringify(bookingData));
      debug('Booking session saved to local storage');
    } catch (e) {
      debug('Error saving booking session', e);
    }
  }
  /**
   * Initialize coupon handling
   */
  function initCouponHandling() {
    const couponInput = $('#coupon-code');
    const applyBtn = $('.apply-coupon-btn');

    applyBtn.prop('disabled', true);

    couponInput.on('input', function () {
      const inputValue = $(this).val().trim();

      if (inputValue.length > 0) {
        applyBtn.prop('disabled', false);
      } else {
        applyBtn.prop('disabled', true);
      }
    });

    couponInput.on('focus', function () {
      const inputValue = $(this).val().trim();
      applyBtn.prop('disabled', inputValue.length === 0);
    });

    $(document).on('click', '.apply-coupon-btn', function () {
      const couponCode = $('#coupon-code').val().trim();

      if (!couponCode) {
        showCouponFeedback('Please enter a coupon code', 'error');
        return;
      }

      $(this).prop('disabled', true).text('Checking...');

      $.ajax({
        url: booking_params.ajax_url,
        type: 'POST',
        data: {
          action: 'check_promo_code',
          nonce: booking_params.nonce,
          promo_code: couponCode,
        },
        success: function (response) {
          $('.apply-coupon-btn').prop('disabled', false).text('Apply');

          if (response.success) {
            bookingData.coupon = {
              code: response.data.promo_code,
              value: parseFloat(response.data.discount_value),
            };

            showCouponFeedback(response.data.message, 'success');

            updateSummary();

            $(".booking-step[data-step='confirm'] .coupon-feedback").show();
          } else {
            showCouponFeedback(response.data.message || 'Invalid coupon', 'error');
          }
        },
        error: function () {
          $('.apply-coupon-btn').prop('disabled', false).text('Apply');
          showCouponFeedback('Error checking coupon. Please try again.', 'error');
        },
      });
    });

    $(document).on('keypress', '#coupon-code', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        $('.apply-coupon-btn').click();
      }
    });
  }

  /**
   * Show coupon feedback message
   */
  function showCouponFeedback(message, type) {
    const $feedback = $('.coupon-feedback');

    $feedback.removeClass('success error').addClass(type).text(message).fadeIn();

    // Hide after 5 seconds for success
    if (type === 'success') {
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
      const savedData = localStorage.getItem('altegio_booking_data');
      if (savedData) {
        const parsedData = JSON.parse(savedData);

        // Validate the data before restoring
        if (parsedData && parsedData.services) {
          bookingData = parsedData;
          debug('Restored booking session from local storage', bookingData);

          // Apply restored data to UI
          applyRestoredSession();
          return true;
        }
      }
    } catch (e) {
      debug('Error restoring booking session', e);
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
      checkbox.prop('checked', true);
      checkbox.closest('.service-item').addClass('selected');
    });

    // Restore staff selection
    if (bookingData.staffId) {
      $(`.staff-item[data-staff-id="${bookingData.staffId}"]`).addClass('selected');
    }

    // Restore contact form data
    if (bookingData.contact) {
      $('#client-name').val(bookingData.contact.name || '');
      $('#client-phone').val(bookingData.contact.phone || '');
      $('#client-email').val(bookingData.contact.email || '');
      $('#client-comment').val(bookingData.contact.comment || '');
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
      localStorage.removeItem('altegio_booking_data');
      debug('Booking session cleared from local storage');
    } catch (e) {
      debug('Error clearing booking session', e);
    }
  }

  /**
   * Pre-fill booking form with user data from WordPress/cabinet
   */
  function prefillBookingForm() {
    debug('prefillBookingForm called');
    debug('window.bookingUserData:', window.bookingUserData);

    if (!window.bookingUserData || typeof window.bookingUserData !== 'object') {
      debug('No user data available for prefilling');
      return;
    }

    const userName = (window.bookingUserData.name || '').trim();
    const userEmail = (window.bookingUserData.email || '').trim();
    let userPhone = (window.bookingUserData.phone || '').trim();
    const userPhoneCountry = (window.bookingUserData.phoneCountry || '').trim();

    // DEBUG: If phone is empty, check raw phone from DB
    if (!userPhone && window.bookingUserData.debug_user_phone_raw) {
      debug(
        'Phone is empty, checking raw phone from DB:',
        window.bookingUserData.debug_user_phone_raw,
      );

      // Try to parse the raw phone the same way as personal-info.php
      let rawPhone = window.bookingUserData.debug_user_phone_raw;
      if (rawPhone && rawPhone !== 'NO_USER_DATA' && rawPhone !== 'NULL_PHONE') {
        // Simple parsing - if it starts with +380, extract the number
        if (rawPhone.includes('+380')) {
          userPhone = rawPhone.replace('+380', '').replace(/[^\d]/g, '');
          debug('Extracted phone from +380:', userPhone);
        } else {
          // Use as is, removing non-digits
          userPhone = rawPhone.replace(/[^\d]/g, '');
          debug('Extracted phone digits only:', userPhone);
        }
      }
    }

    // If still no phone, try WordPress user meta
    if (!userPhone && window.bookingUserData.debug_wp_user_meta_phone) {
      debug(
        'Still no phone, checking WordPress user meta:',
        window.bookingUserData.debug_wp_user_meta_phone,
      );

      let wpPhone = window.bookingUserData.debug_wp_user_meta_phone;
      if (wpPhone && wpPhone !== 'NO_WP_PHONE') {
        // Parse the same way
        if (wpPhone.includes('+380')) {
          userPhone = wpPhone.replace('+380', '').replace(/[^\d]/g, '');
          debug('Extracted phone from WP meta +380:', userPhone);
        } else if (wpPhone.includes('+65')) {
          userPhone = wpPhone.replace('+65', '').replace(/[^\d]/g, '');
          debug('Extracted phone from WP meta +65:', userPhone);
        } else {
          // Use as is, removing non-digits
          userPhone = wpPhone.replace(/[^\d]/g, '');
          debug('Extracted phone from WP meta digits only:', userPhone);
        }
      }
    }

    // If STILL no phone, try hardcoded debug value
    if (!userPhone && window.bookingUserData.debug_hardcoded_phone) {
      debug('Using hardcoded debug phone:', window.bookingUserData.debug_hardcoded_phone);
      userPhone = window.bookingUserData.debug_hardcoded_phone;
    }

    debug('Prefilling form with user data:', { userName, userEmail, userPhone, userPhoneCountry });

    // Add delay to ensure DOM is ready
    setTimeout(function () {
      debug('=== DELAYED PREFILL START ===');

      if (userName && userName.length > 0) {
        try {
          const nameField = $('#client-name');
          debug('Name field found:', nameField.length > 0);
          debug('Name field current value before fill:', nameField.val());
          if (nameField.length) {
            nameField.val(userName);
            debug('Filled name field with:', userName);
            debug('Name field value after fill:', nameField.val());

            // Force trigger change event
            nameField.trigger('change');
            nameField.trigger('input');

            // Check again after short delay
            setTimeout(function () {
              debug('Name field value after 500ms:', nameField.val());
            }, 500);
          }
        } catch (e) {
          debug('Error filling name field:', e);
        }
      }

      if (userEmail && userEmail.length > 0 && userEmail.includes('@')) {
        try {
          const emailField = $('#client-email');
          debug('Email field found:', emailField.length > 0);
          debug('Email field current value before fill:', emailField.val());
          if (emailField.length) {
            emailField.val(userEmail);
            debug('Filled email field with:', userEmail);
            debug('Email field value after fill:', emailField.val());

            // Force trigger change event
            emailField.trigger('change');
            emailField.trigger('input');

            // Check again after short delay
            setTimeout(function () {
              debug('Email field value after 500ms:', emailField.val());
            }, 500);
          }
        } catch (e) {
          debug('Error filling email field:', e);
        }
      }

      if (userPhone && userPhone.length > 0) {
        try {
          const phoneField = $('#client-phone');
          debug('Phone field found:', phoneField.length > 0);
          debug('Phone field current value before fill:', phoneField.val());
          if (phoneField.length) {
            phoneField.val(userPhone);
            debug('Filled phone field with:', userPhone);
            debug('Phone field value after fill:', phoneField.val());

            // Force trigger change event
            phoneField.trigger('change');
            phoneField.trigger('input');

            // Check again after short delay
            setTimeout(function () {
              debug('Phone field value after 500ms:', phoneField.val());
            }, 500);
          }
        } catch (e) {
          debug('Error filling phone field:', e);
        }
      }

      if (userPhoneCountry && userPhoneCountry.length > 0 && userPhoneCountry.startsWith('+')) {
        try {
          setCountrySelector(userPhoneCountry);
          debug('Set country selector:', userPhoneCountry);
        } catch (e) {
          debug('Error setting country selector:', e);
        }
      }

      debug('=== DELAYED PREFILL END ===');
    }, 100);

    if (
      window.bookingUserData.discountPercentage &&
      window.bookingUserData.discountPercentage > 0
    ) {
      try {
        bookingData.personalDiscountPercent = window.bookingUserData.discountPercentage;
        debug('Set personal discount:', window.bookingUserData.discountPercentage + '%');
        console.log(
          '🎯 Personal discount set in prefillBookingForm:',
          bookingData.personalDiscountPercent,
        );

        const personalDiscountBlock = $('.summary-item.personal-discount');
        if (personalDiscountBlock.length) {
          personalDiscountBlock.show();
          personalDiscountBlock
            .find('span')
            .first()
            .text(`Your personal discount (${window.bookingUserData.discountValue})`);
        }

        if (typeof updateSummary === 'function') {
          updateSummary();
        }
      } catch (e) {
        debug('Error setting personal discount:', e);
      }
    }
  }

  /**
   * Set country selector to specific country code
   */
  function setCountrySelector(countryCode) {
    if (!countryCode || typeof countryCode !== 'string' || !countryCode.trim()) {
      debug('Invalid country code provided:', countryCode);
      return false;
    }

    try {
      const countryButton = $('#countrySelectButton');
      const countryDropdown = $('#countryDropdown');

      if (!countryButton.length) {
        debug('Country selector button not found');
        return false;
      }

      const cleanCode = countryCode.trim();

      if (countryDropdown.length) {
        const countryOption = countryDropdown.find(`[data-value="${cleanCode}"]`);

        if (countryOption.length) {
          countryDropdown.find('.country-option').removeClass('selected');
          countryOption.addClass('selected');

          const selectedCountrySpan = countryButton.find('.selected-country');
          if (selectedCountrySpan.length) {
            selectedCountrySpan.text(countryOption.text());
            debug('Country selector updated with:', countryOption.text());
          }

          countryButton.data('selected-value', cleanCode);
          debug('Country selector set to:', cleanCode);
          return true;
        }
      }

      countryButton.data('selected-value', cleanCode);
      debug('Country code stored:', cleanCode);
      return true;
    } catch (e) {
      debug('Error in setCountrySelector:', e);
      return false;
    }
  }

  /**
   * Initialize country selector functionality
   */
  function initCountrySelector() {
    try {
      // Toggle dropdown on button click
      $(document).on('click', '#countrySelectButton', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const dropdown = $('#countryDropdown');
        if (dropdown.length) {
          dropdown.toggleClass('show');
        }
      });

      // Handle country option selection
      $(document).on('click', '.country-option', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const selectedValue = $(this).data('value');
        const selectedText = $(this).text();

        if (!selectedValue || !selectedText) {
          debug('Invalid country option data');
          return;
        }

        // Update UI
        $('.country-option').removeClass('selected');
        $(this).addClass('selected');

        const countryButton = $('#countrySelectButton');
        const selectedCountrySpan = countryButton.find('.selected-country');

        if (selectedCountrySpan.length) {
          selectedCountrySpan.text(selectedText);
        }

        if (countryButton.length) {
          countryButton.data('selected-value', selectedValue);
        }

        // Hide dropdown
        $('#countryDropdown').removeClass('show');
      });

      // Close dropdown when clicking outside
      $(document).on('click', function (e) {
        if (!$(e.target).closest('.custom-country-select').length) {
          const dropdown = $('#countryDropdown');
          if (dropdown.length) {
            dropdown.removeClass('show');
          }
        }
      });

      // Global function to get selected country code (used by other scripts)
      window.getSelectedCountryCode = function () {
        const button = $('#countrySelectButton');
        if (button.length) {
          return button.data('selected-value') || '+65'; // Default to Singapore
        }
        return '+65';
      };
    } catch (e) {
      debug('Error initializing country selector:', e);
    }
  }

  /**
   * Initialize booking popup and general navigation
   */
  function initBookingPopup() {
    // Open popup when book button is clicked
    $(document).on('click', ' .open-popup', function (e) {
      e.preventDefault();

      // Reset booking data
      resetBookingForm();

      // If booking from service card, pre-select that service
      if ($(this).closest('.service-card').length) {
        const serviceId = $(this).closest('.service-card').data('service-id');
        bookingData.preSelectedServiceId = serviceId;
      }

      // If booking from master card, pre-select that master
      if ($(this).closest('.master-card').length) {
        const masterId = $(this).closest('.master-card').data('master-id');
        bookingData.preSelectedMasterId = masterId;
        // Set initial option to master if coming from master card
        bookingData.initialOption = 'master';
      }

      // Show popup
      $('.booking-popup-overlay').addClass('active');
      $('body').addClass('popup-open');
      $('.loading-overlay').hide();

      // Pre-fill form with user data after a small delay to ensure DOM is ready
      setTimeout(function () {
        try {
          prefillBookingForm();
        } catch (e) {
          debug('Error prefilling form:', e);
        }
      }, 100);

      // Trigger custom event
      $(document).trigger('bookingPopupOpened');
    });

    // Close popup
    $(document).on('click', '.booking-popup-close, .close-popup-btn', function () {
      $('.booking-popup-overlay').removeClass('active');
      $('body').removeClass('popup-open');
      // Add a confirmation if there's unsaved data
      clearBookingSession();
    });

    // Close when clicking outside popup
    $(document).on('click', '.booking-popup-overlay', function (e) {
      if ($(e.target).is('.booking-popup-overlay')) {
        $('.booking-popup-overlay').removeClass('active');
        $('body').removeClass('popup-open');
        clearBookingSession();
      }
    });

    // Initial step selection (Services or Master)
    $(document).on('click', '.booking-option-item', function () {
      $('.booking-option-item').removeClass('active');
      $('.status-indicator').removeClass('active');

      $(this).addClass('active');
      $(this).find('.status-indicator').addClass('active');

      bookingData.initialOption = $(this).data('option');
      debug('Initial option selected', bookingData.initialOption);
      // Update flow history
      bookingData.flowHistory = ['initial'];
    });

    // Next button in initial step
    $(document).on('click', '.booking-step[data-step="initial"] .next-btn', function () {
      // Get selected option (services or master)
      const nextStep = $('.booking-option-item.active').data('option') || 'services';
      bookingData.initialOption = nextStep;

      // Initialize flow history
      bookingData.flowHistory = ['initial', nextStep];
      debug('Going to step', nextStep);
      goToStep(nextStep);

      // If pre-selected service, select it
      if (nextStep === 'services' && bookingData.preSelectedMasterId) {
        setTimeout(function () {
          const masterId = bookingData.preSelectedMasterId;
          loadServicesForMaster(masterId);
        }, 100);
      }

      // If pre-selected master, load that master's data
      if (nextStep === 'master' && bookingData.preSelectedMasterId) {
        loadStaffById(bookingData.preSelectedMasterId);
      }
    });

    // Universal back button behavior - fixed to go to previous step, not always initial
    $(document).on('click', '.booking-back-btn', function () {
      const currentStep = $(this).closest('.booking-step').data('step');
      debug('Going back from', currentStep);

      // Clear selected slot when going back from certain steps
      if (currentStep === 'master' || currentStep === 'services') {
        if (bookingData.selectedPreviewSlot) {
          bookingData.selectedPreviewSlot = null;
          $('.nearest-seances .slot').removeClass('active');
          bookingData.time = null;
        }
      }

      if (bookingData.flowHistory.length > 1) {
        bookingData.flowHistory.pop();
        const previousStep = bookingData.flowHistory[bookingData.flowHistory.length - 1];
        goToStep(previousStep);
      } else {
        goToStep('initial');
      }
    });
  }

  $(document).on('click', '.want-this-btn', function (e) {
    e.preventDefault();

    const masterId = parseInt($(this).data('master-id'));
    const serviceIds = $(this)
      .data('service-ids')
      .toString()
      .split(',')
      .map((id) => parseInt(id.trim()));

    const galleryTitle = $(this).data('gallery-title') || '';
    bookingData.galleryTitle = galleryTitle;
    if (!masterId || !serviceIds.length) return;
    resetBookingForm();

    $('.booking-popup').hide();
    $('.loading-overlay').show();

    bookingData.staffId = masterId;
    bookingData.initialOption = 'master';
    bookingData.flowHistory = ['initial', 'master'];
    bookingData.galleryTitle = galleryTitle;
    $('.booking-popup-overlay').addClass('active');
    $('body').addClass('popup-open');

    const $staffItem = $(`.staff-item[data-staff-id="${masterId}"]`);
    if ($staffItem.length) {
      $('.staff-item').removeClass('selected');
      $staffItem.addClass('selected');

      const $radio = $staffItem.find('input[type="radio"]');
      if ($radio.length) {
        $radio.prop('checked', true).trigger('change').trigger('click');
      }

      const name = $staffItem.find('.staff-name').text().trim();
      const level = $staffItem.find('.star').length || 0;
      const specialization = $staffItem.find('.stars span').text().trim().replace(/[()]/g, '');

      bookingData.staffName = name || 'Selected Master';
      bookingData.staffLevel = level;
      bookingData.staffSpecialization = specialization;

      $staffItem.trigger('click');
    } else {
      bookingData.staffName = 'Selected Master';
      bookingData.staffLevel = 1;
    }

    bookingData.services = [];
    bookingData.coreServices = [];
    bookingData.addons = [];

    loadServicesForMaster(masterId);

    function waitForServiceCheckboxes(serviceIds, callback, maxTries = 30, delay = 100) {
      let tries = 0;
      const interval = setInterval(() => {
        const allFound = serviceIds.every(
          (id) => $(`.service-checkbox[data-service-id="${id}"]`).length > 0,
        );
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
          $checkbox.prop('checked', true).trigger('change');
          $checkbox.closest('.service-item').addClass('selected');

          if (typeof window.addService === 'function') {
            const title = $checkbox.data('service-title') || 'Service';
            const price = parseFloat($checkbox.data('service-price')) || 0;
            const currency = $checkbox.data('service-currency') || 'SGD';
            const duration = $checkbox.data('service-duration') || '';
            const wearTime = $checkbox.data('service-wear-time') || '';
            const isAddon =
              $checkbox.data('is-addon') === true || $checkbox.data('is-addon') === 'true';

            addService(
              id,
              title,
              price,
              currency,
              duration,
              wearTime,
              isAddon,
              isAddon ? id : null,
              '',
            );
          }
        }
      });
      if (!bookingData.flowHistory.includes('services')) {
        bookingData.flowHistory.push('services');
      }
      bookingData.flowHistory = ['initial', 'services', 'master', 'datetime'];

      goToStep('datetime');
      generateCalendar();
      updateSummary();

      $('.booking-popup').fadeIn(200);
      $('.loading-overlay').hide();
    });
  });

  /**
   * Initialize service selection handling
   */
  function initServiceHandling() {
    // Category filter tabs
    $(document).on('click', '.category-tab', function () {
      const categoryId = $(this).data('category-id');

      // Update active tab
      $('.category-tab').removeClass('active');
      $(this).addClass('active');

      // Show only services from selected category
      $('.category-services').hide();
      $(`.category-services[data-category-id="${categoryId}"]`).show();
    });

    // Make the entire service item clickable
    $(document).on('click', '.service-item', function (e) {
      // Prevent triggering if clicking on checkbox directly
      if ($(e.target).is('.service-checkbox')) {
        return;
      }

      // Don't allow clicking if disabled
      if ($(this).hasClass('disabled')) {
        debug('Service item is disabled');
        return;
      }

      const checkbox = $(this).find('.service-checkbox');

      // If checkbox is disabled, don't allow toggling
      if (checkbox.prop('disabled')) {
        return;
      }

      checkbox.prop('checked', !checkbox.prop('checked'));
      checkbox.trigger('change');
    });

    // Service checkbox selection
    $(document).on('change', '.service-checkbox', function () {
      const serviceId = $(this).data('service-id');
      const serviceTitle = $(this).data('service-title');
      const servicePrice = $(this).data('service-price');
      const serviceCurrency = $(this).data('service-currency');
      const serviceDuration = $(this).data('service-duration') || '';
      const serviceWearTime = $(this).data('service-wear-time') || '';

      const isAddon =
        $(this).data('is-addon') === true ||
        $(this).data('is-addon') === 'true' ||
        $(this).closest('.service-item').hasClass('addon-item') ||
        $(this).closest('.core-related-addons').length > 0;

      const altegioId = $(this).data('altegio-id') || serviceId;

      const $serviceItem = $(this).closest('.service-item');
      const desc = $serviceItem.find('.service-description').text().trim();

      if ($(this).is(':checked')) {
        $serviceItem.addClass('selected');

        addService(
          serviceId,
          serviceTitle,
          servicePrice,
          serviceCurrency,
          serviceDuration,
          serviceWearTime,
          isAddon,
          altegioId,
          desc,
        );
        debug('Service added', { id: serviceId, title: serviceTitle, isAddon });

        if (!isAddon) {
          const coreId = $(this).data('service-id');

          const $container = $(`.core-related-addons[data-core-id="${coreId}"]`);
          $container.addClass('open');
          $container.find('.service-checkbox').prop('disabled', false);
          $container.find('.addon-item').removeClass('disabled');
        }
      } else {
        $serviceItem.removeClass('selected');
        removeService(serviceId);
        debug('Service removed', serviceId);

        if (!isAddon) {
          const coreId = $(this).data('service-id');

          const $container = $(`.core-related-addons[data-core-id="${coreId}"]`);
          $container.removeClass('open');
          $container.find('input[type=checkbox]').prop('checked', false).prop('disabled', true);
          $container.find('.addon-item').removeClass('selected').addClass('disabled');

          $container.find('.service-checkbox').each(function () {
            const addonId = $(this).data('service-id');
            removeService(addonId);
          });
        }
      }

      updateAddonAvailability();
      updateNextButtonState();
    });

    // Next button in services step
    $(document).on('click', '.booking-step[data-step="services"] .next-btn', function () {
      try {
        let nextStep;
        if (bookingData.initialOption === 'services') {
          nextStep = 'master';
        } else {
          nextStep = 'datetime';
        }

        bookingData.flowHistory.push(nextStep);

        debug('Services selected, proceeding to', nextStep);

        if (nextStep === 'master') {
          goToStep(nextStep);
        } else if (nextStep === 'datetime') {
          if (!bookingData.staffId) {
            showValidationAlert('Please go back and select a master first');
            return false;
          }
          goToStep(nextStep);
          generateCalendar();
        }

        $(document).trigger('bookingServicesCompleted', [bookingData.services]);

        return true;
      } catch (error) {
        showValidationAlert('An error occurred. Please try again.');
        return false;
      }
    });
  }

  /**
   * Initialize master selection handling
   */
  function initMasterHandling() {
    $(document).on('click', '.staff-item', function (e) {
      if ($(e.target).closest('.slot').length) {
        return;
      }

      $('.staff-item').removeClass('selected');
      $(".staff-item input[type='radio']").prop('checked', false);

      $(this).addClass('selected');
      $(this).find("input[type='radio']").prop('checked', true);

      // Clear all selected slots when selecting a different master
      $('.nearest-seances .slot').removeClass('active');
      bookingData.selectedPreviewSlot = null;
      bookingData.time = null;

      const staffId = $(this).data('staff-id');
      const staffName = $(this).find('.staff-name').text();
      let staffAvatar = '';

      const avatarImg = $(this).find('.staff-avatar img');
      if (avatarImg.length) {
        staffAvatar = avatarImg.attr('src') || '';
      }
      const specialization = $(this).data('staff-specialization');
      const staffLevel =
        typeof $(this).data('staff-level') !== 'undefined'
          ? parseInt($(this).data('staff-level'))
          : 1;

      bookingData.staffLevel = staffLevel;
      bookingData.staffSpecialization = specialization;
      selectStaff(staffId, staffName, staffAvatar, staffLevel, specialization);

      bookingData.staffId = staffId;
      updateMasterNextButtonState();
      $.ajax({
        url: booking_params.ajax_url,
        method: 'POST',
        data: {
          action: 'get_next_seances',
          staff_id: staffId,
          nonce: booking_params.nonce,
        },
        success: function (response) {
          if (response.success && response.data && response.data.seance_date) {
            bookingData.date = response.data.seance_date;

            if ($('.booking-step[data-step="datetime"]').is('.active')) {
              generateCalendar();
            }
          }
        },
      });
    });

    $(document).on('click', '.booking-step[data-step="master"] .next-btn', function () {
      if (!bookingData.staffId) {
        showValidationAlert('Please select a specialist');
        return;
      }

      let nextStep;
      if (bookingData.initialOption === 'master') {
        nextStep = 'services';

        if (bookingData.flowHistory.includes('datetime')) {
          nextStep = 'datetime';
        }
      } else {
        nextStep = 'datetime';
      }

      bookingData.flowHistory.push(nextStep);

      debug('Master selected, proceeding to', nextStep);

      if (nextStep === 'services') {
        loadServicesForMaster(bookingData.staffId);
      } else if (nextStep === 'datetime') {
        generateCalendar();
      }

      goToStep(nextStep);

      $(document).trigger('bookingMasterSelected', [
        {
          id: bookingData.staffId,
          name: bookingData.staffName,
          level: bookingData.staffLevel,
        },
      ]);
    });
  }

  function renderContactStepSummary() {
    const level =
      typeof bookingData.staffLevel !== 'undefined' ? parseInt(bookingData.staffLevel) : 1;

    $('.summary-master .name').text(bookingData.staffName || 'N/A');

    const stars = generateStarsHtml(level);
    $('.summary-master .stars').html(stars);

    const levelTitle = levelTitles[level];
    $('.summary-master .stars-name')
      .text(levelTitle ? `(${levelTitle})` : '')
      .toggle(!!levelTitle);

    if (bookingData.staffAvatar) {
      $('.summary-master .avatar').attr('src', bookingData.staffAvatar);
    }
  }
  /**
   * Show dialog when date is unavailable
   * @param {string} date - Date in YYYY-MM-DD format
   */
  function showUnavailableDateDialog(date) {
    showValidationAlert(
      `This date (${date}) is unavailable for booking. Please choose another date.`,
    );
  }
  /**
   * Initialize date and time selection handling
   */
  function initDateTimeHandling() {
    $(document).on('click', '.prev-month', function () {
      navigateCalendar(-1);
    });

    $(document).on('click', '.next-month', function () {
      navigateCalendar(1);
    });

    $(document).on('click', '.calendar-day:not(.disabled, .empty)', function () {
      const $day = $(this);
      const date = $day.data('date');

      // Check if day is marked as unavailable
      if ($day.hasClass('unavailable')) {
        showUnavailableDateDialog(date);
        return;
      }

      $('.time-sections').html('<div class="loading">Loading available time slots...</div>');

      // Proceed with normal date selection for available dates
      selectDate(date);
      $('.calendar-day').removeClass('selected');
      $day.addClass('selected');

      // Load time slots for selected date
      loadTimeSlots(date);
    });

    $(document).on('click', '.time-slot:not(.disabled)', function () {
      const time = $(this).data('time');
      selectTime(time);

      $('.time-slot').removeClass('selected');
      $(this).addClass('selected');

      updateDateTimeNextButtonState();
    });

    $(document).on('click', '.booking-step[data-step="datetime"] .next-btn', function () {
      if (validateDateTimeStep()) {
        bookingData.flowHistory.push('contact');

        renderContactStepSummary();
        goToStep('contact');

        updateSummary();

        $(document).trigger('bookingDateTimeSelected', [
          {
            date: bookingData.date,
            time: bookingData.time,
          },
        ]);
      }
    });
  }

  /**
   * Check day availability - all requests simultaneously for maximum speed
   * @param {number} month - Month index (0-11)
   * @param {number} year - Year
   */
  function showDatePreloader(show = true) {
    $('.date-preloader').toggle(show);
    if (show) {
      // Show message in time section while checking dates
      $('.time-sections').html(
        '<p class="loading-message">Checking available dates, please wait...</p>',
      );
    } else {
      // If no date selected, show message to select a date
      if (!bookingData.date) {
        $('.time-sections').html('<p class="error-message">Please select an available date.</p>');
      } else {
        $('.time-sections').html('<p class="error-message">Please select an available date.</p>');
      }
    }
  }
  function getAvailabilityCacheKey(month, year, staffId, serviceIds) {
    return `availability_${year}_${month}_${staffId}_${serviceIds.join('_')}`;
  }

  function setAvailabilityCache(key, data) {
    const cacheData = {
      timestamp: Date.now(),
      data: data,
    };
    localStorage.setItem(key, JSON.stringify(cacheData));
  }

  function getAvailabilityCache(key, maxAgeMs = 60 * 60 * 1000) {
    const cache = localStorage.getItem(key);
    if (!cache) return null;
    try {
      const parsed = JSON.parse(cache);
      if (Date.now() - parsed.timestamp < maxAgeMs) {
        return parsed.data;
      }
    } catch (e) {}
    return null;
  }

  function checkDayAvailability(month, year) {
    if (!bookingData.staffId || bookingData.services.length === 0) {
      console.log('Skipping availability check - no staff or services selected');
      return;
    }
    showDatePreloader(true);
    const serviceIds = bookingData.services.map((s) => s.altegioId || s.id);
    const cacheKey = getAvailabilityCacheKey(month, year, bookingData.staffId, serviceIds);

    const cached = getAvailabilityCache(cacheKey);
    if (cached) {
      applyAvailabilityResults(cached);
      showDatePreloader(false);
      return;
    }

    $.ajax({
      url: booking_params.ajax_url,
      method: 'POST',
      data: {
        action: 'get_month_availability',
        nonce: booking_params.nonce,
        staff_id: bookingData.staffId,
        service_ids: serviceIds,
        month: month + 1,
        year: year,
      },
      success: function (response) {
        if (response.success && response.data && response.data.booking_days) {
          const daysArr = response.data.booking_days[String(month + 1)] || [];

          const results = [];
          daysArr.forEach((day) => {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            results.push({ date: dateStr, hasSlots: true });
          });

          const daysInMonth = new Date(year, month + 1, 0).getDate();
          for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            if (!daysArr.includes(String(d))) {
              results.push({ date: dateStr, hasSlots: false });
            }
          }

          setAvailabilityCache(cacheKey, results);
          applyAvailabilityResults(results);
        } else {
          const daysInMonth = new Date(year, month + 1, 0).getDate();
          const results = [];
          for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            results.push({ date: dateStr, hasSlots: false });
          }
          setAvailabilityCache(cacheKey, results);
          applyAvailabilityResults(results);
        }
        showDatePreloader(false);
      },
      error: function () {
        showDatePreloader(false);
        $('.time-sections').html(
          '<p class="error-message">Error loading available dates. Please try again later.</p>',
        );
      },
    });
  }

  function applyAvailabilityResults(bookingDays) {
    // bookingDays: [{date: "2025-07-21", hasSlots: true}, ...]

    const availableMap = {};
    bookingDays.forEach((obj) => {
      if (obj.hasSlots) availableMap[obj.date] = true;
    });

    $('.calendar-day').each(function () {
      const $day = $(this);
      if ($day.hasClass('empty') || $day.hasClass('disabled')) return;
      const dateStr = $day.data('date');
      if (availableMap[dateStr]) {
        $day.removeClass('unavailable').addClass('available');
      } else {
        $day.removeClass('available').addClass('unavailable');
      }
    });
  }
  /**
   * Clear availability cache
   */
  function clearAvailabilityCache() {
    $.ajax({
      url: booking_params.ajax_url,
      method: 'POST',
      data: {
        action: 'clear_availability_cache',
        nonce: booking_params.nonce,
      },
      success: function (response) {
        if (response.success) {
          debug('Availability cache cleared');
          // Refresh current month's availability
          const monthHeader = $('.month-header span').text();
          if (monthHeader) {
            const [monthName, year] = monthHeader.split(' ');
            const monthIndex = getMonthIndex(monthName);
            checkDayAvailability(monthIndex, parseInt(year));
          }
        }
      },
      error: function (xhr, status, error) {
        console.error('Failed to clear availability cache:', error);
      },
    });
  }
  function markUnavailableDatesFromTimeSlots() {
    $('.calendar-day:not(.disabled, .empty)').each(function () {
      const dateStr = $(this).data('date');
      const $dayElement = $(this);

      if (dateStr && !$dayElement.hasClass('unavailable')) {
        $.ajax({
          url: booking_params.ajax_url,
          method: 'POST',
          data: {
            action: 'get_time_slots',
            nonce: booking_params.nonce,
            staff_id: bookingData.staffId,
            date: dateStr,
            service_ids: bookingData.services.map((s) => s.altegioId || s.id),
          },
          success: function (response) {
            if (
              !response.success ||
              !response.data ||
              (Array.isArray(response.data) && response.data.length === 0) ||
              (response.data.slots &&
                Array.isArray(response.data.slots) &&
                response.data.slots.length === 0)
            ) {
              $dayElement.addClass('unavailable');

              if ($dayElement.hasClass('selected')) {
                $dayElement.removeClass('selected');
                bookingData.date = null;
                bookingData.time = null;
                $('.time-sections').html(
                  '<p class="error-message">Please select an available date.</p>',
                );
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
    // Автоматический AJAX-запрос на получение персональной скидки
    $(document).on('change keyup', '#client-email, #client-phone', function () {
      let name = $('#client-name').val();
      let phone = $('#client-phone').val();
      let email = $('#client-email').val();
      let comment = $('#client-comment').val();

      console.log('🎯 Contact change detected:', {
        email,
        phone,
        currentDiscount: bookingData.personalDiscountPercent,
      });

      bookingData.contact = {
        name: name || '',
        phone: phone || '',
        email: email || '',
        comment: comment || '',
      };

      // Получаем персональную скидку по email/телефону
      if (email || phone) {
        $.ajax({
          url: booking_params.ajax_url,
          type: 'POST',
          data: {
            action: 'get_personal_discount',
            nonce: booking_params.nonce,
            email: email,
            phone: phone,
          },
          success: function (response) {
            console.log('🎯 Personal discount AJAX response:', response);
            if (response.success && response.data) {
              // Персональная скидка
              if (response.data.discount_percent) {
                bookingData.personalDiscountPercent = parseFloat(response.data.discount_percent);
                $('#personal-discount').val(response.data.discount_percent + '%');
                $('.personal-discount-group').show();
                console.log('✅ Discount set to:', bookingData.personalDiscountPercent);
              } else {
                // НЕ скидаємо знижку, якщо вона вже встановлена
                if (
                  typeof bookingData.personalDiscountPercent === 'undefined' ||
                  bookingData.personalDiscountPercent === 0
                ) {
                  bookingData.personalDiscountPercent = 0;
                  $('#personal-discount').val('');
                  $('.personal-discount-group').hide();
                  console.log('❌ No discount found, cleared discount');
                } else {
                  console.log(
                    '🛡️ Existing discount preserved:',
                    bookingData.personalDiscountPercent,
                  );
                }
              }
              // Автозаполнение контактных данных, если backend их вернул
              if (response.data.name) {
                $('#client-name').val(response.data.name);
                bookingData.contact.name = response.data.name;
              }
              if (response.data.email) {
                $('#client-email').val(response.data.email);
                bookingData.contact.email = response.data.email;
              }
              if (response.data.phone) {
                $('#client-phone').val(response.data.phone);
                bookingData.contact.phone = response.data.phone;
              }
            } else {
              // НЕ скидаємо знижку, якщо вона вже встановлена
              if (
                typeof bookingData.personalDiscountPercent === 'undefined' ||
                bookingData.personalDiscountPercent === 0
              ) {
                bookingData.personalDiscountPercent = 0;
                $('#personal-discount').val('');
                $('.personal-discount-group').hide();
              }
            }
            updateSummary();
          },
          error: function () {
            // НЕ скидаємо знижку, якщо вона вже встановлена
            if (
              typeof bookingData.personalDiscountPercent === 'undefined' ||
              bookingData.personalDiscountPercent === 0
            ) {
              bookingData.personalDiscountPercent = 0;
              $('#personal-discount').val('');
              $('.personal-discount-group').hide();
            }
            updateSummary();
          },
        });
      } else {
        // НЕ скидаємо знижку, якщо вона вже встановлена
        if (
          typeof bookingData.personalDiscountPercent === 'undefined' ||
          bookingData.personalDiscountPercent === 0
        ) {
          bookingData.personalDiscountPercent = 0;
          $('#personal-discount').val('');
          $('.personal-discount-group').hide();
        }
        updateSummary();
      }
    });
    // Form field validation on blur
    $(document).on('blur', '.contact-form input[required]', function () {
      validateField($(this));
    });

    // Phone number formatting
    $(document).on('input', '#client-phone', function () {
      const input = $(this);
      let value = input.val();

      const countryCode = window.getSelectedCountryCode ? window.getSelectedCountryCode() : '';
      const codeDigits = countryCode.replace(/\D/g, '');

      const maxTotalDigits = 12;

      let cleaned = value.replace(/\D/g, '');

      cleaned = cleaned.slice(0, maxTotalDigits - codeDigits.length);

      let formatted = cleaned;
      if (cleaned.length > 4) {
        formatted = cleaned.substring(0, 4) + ' ' + cleaned.substring(4);
      }

      input.val(formatted);

      if (typeof bookingData !== 'undefined') {
        bookingData.contact = bookingData.contact || {};
        bookingData.contact.phone = cleaned;
        bookingData.contact.countryCode = countryCode;
        bookingData.contact.fullPhone = countryCode + cleaned;
      }
    });

    $(document).on('click', '.confirm-booking-btn', function () {
      const form = $('#booking-form')[0];
      const $form = $('#booking-form');
      let valid = true;

      $form.find('.input-error').text('');
      $('.global-form-error').hide();

      const fields = [
        { id: 'client-name', label: 'Name' },
        { id: 'client-email', label: 'Email' },
        { id: 'client-phone', label: 'Phone' },
        { id: 'privacy-policy', label: 'Privacy policy', type: 'checkbox' },
      ];

      fields.forEach((field) => {
        const input = document.getElementById(field.id);
        const errorBlock = $(`.input-error[data-for="${field.id}"]`);

        if (field.type === 'checkbox') {
          if (!input.checked) {
            errorBlock.text('You must accept the terms');
            valid = false;
          }
        } else if (!input.value.trim()) {
          errorBlock.text(`${field.label} is required`);
          valid = false;
        }
      });

      if (!valid) {
        $('.global-form-error').fadeIn();
        return;
      }

      bookingData.contact = {
        name: $('#client-name').val().trim(),
        phone: bookingData.contact.fullPhone || $('#client-phone').val().trim().replace(/\D/g, ''),
        email: $('#client-email').val().trim(),
        comment: $('#client-comment').val().trim(),
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
    $(document).on('click', '.edit-master-btn', function () {
      // Add the current step to history so we can return
      bookingData.returnToContactStep = true;
      goToStep('master');
    });

    // Edit date/time button
    $(document).on('click', '.edit-datetime-btn', function () {
      bookingData.returnToContactStep = false;
      generateCalendar();
      goToStep('datetime');
    });

    // When returning from edit, check if we should go back to contact step
    $(document).on('bookingStepChanged', function (e, step) {
      if (
        bookingData.returnToContactStep &&
        (step === 'services' || step === 'datetime') &&
        bookingData.flowHistory.includes('contact')
      ) {
        // Make sure we have the necessary data before returning
        if (
          (step === 'master' && bookingData.staffId) ||
          (step === 'datetime' && bookingData.date && bookingData.time)
        ) {
          // Return to contact step
          setTimeout(function () {
            goToStep('contact');
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
    const fieldId = field.attr('id');

    const fieldLabels = {
      'client-name': 'Name',
      'client-email': 'Email',
      'client-phone': 'Phone',
      'client-comment': 'Comment',
    };
    const fieldName = fieldLabels[fieldId] || fieldId;

    field.removeClass('error');
    field.next('.field-error').remove();

    // Required
    if (field.prop('required') && !value) {
      field.addClass('error');
      field.after(`<div class="field-error">${fieldName} is required</div>`);
      return false;
    }

    // Email
    if (fieldId === 'client-email' && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        field.addClass('error');
        field.after('<div class="field-error">Please enter a valid email address</div>');
        return false;
      }
    }

    // Phone
    if (fieldId === 'client-phone' && value) {
      if (value.replace(/\D/g, '').length < 7) {
        field.addClass('error');
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
    $('.booking-step[data-step="services"] .next-btn').prop('disabled', !hasServicesSelected);
  }

  /**
   * Update next button state based on master selection
   */
  function updateMasterNextButtonState() {
    const hasMasterSelected = !!bookingData.staffId;
    $('.booking-step[data-step="master"] .next-btn').prop('disabled', !hasMasterSelected);
  }

  /**
   * Update next button state based on date/time selection
   */
  function updateDateTimeNextButtonState() {
    const hasDateTimeSelected = bookingData.date !== null && bookingData.time !== null;
    $('.booking-step[data-step="datetime"] .next-btn').prop('disabled', !hasDateTimeSelected);
  }

  /**
   * Update addon availability based on core service selection
   */
  function updateAddonAvailability() {
    if (bookingData.coreServices.length > 0) {
      // Enable all addon checkboxes
      $(".service-checkbox[data-is-addon='true']").prop('disabled', false);
      $('.service-item.addon-item').removeClass('disabled');
      debug('Addons enabled');

      // Show addon section if it exists
      $('.addon-services-container').show();
      $('.addon-title').show();
    } else {
      // Disable and uncheck all addon checkboxes
      $(".service-checkbox[data-is-addon='true']").prop('disabled', true).prop('checked', false);

      $('.service-item.addon-item').addClass('disabled').removeClass('selected');

      // Remove all addons from bookingData
      bookingData.addons = [];
      bookingData.services = bookingData.services.filter((service) => !service.isAddon);

      debug('Addons disabled and removed from selection');

      // Hide addon section
      $('.addon-services-container').hide();
      $('.addon-title').hide();
    }
  }

  /**
   * Show validation alert when validation fails
   * @param {string} message - Alert message to show
   */
  function showValidationAlert(message) {
    // Remove any existing alerts
    $('.validation-alert-overlay').remove();
    const dateMatch = message.match(/\((.*?)\)/);
    const cleanMessage = dateMatch
      ? `This service is not available at the selected time ${dateMatch[0]}`
      : message;

    let alertMessage = 'Please choose a different time.';

    if (message.includes('phone') || message.includes('Phone')) {
      alertMessage = 'Please check your phone number and try again.';
    } else if (message.includes('email') || message.includes('Email')) {
      alertMessage = 'Please check your email address and try again.';
    } else if (message.includes('name') || message.includes('Name')) {
      alertMessage = 'Please enter your name and try again.';
    } else if (message.includes('specialist') || message.includes('master')) {
      alertMessage = 'Please select a specialist to continue.';
    } else if (message.includes('service')) {
      alertMessage = 'Please select at least one service.';
    } else if (message.includes('date')) {
      alertMessage = 'Please select a date to continue.';
    } else if (message.includes('time')) {
      alertMessage = 'Please choose a different time.';
    } else if (message.includes('network') || message.includes('Network')) {
      alertMessage = 'Please check your internet connection and try again.';
    } else if (message.includes('error') || message.includes('Error')) {
      alertMessage = 'Something went wrong. Please try again.';
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

    $('body').append(alertHtml);

    // Bind click event to the button
    $(document).on('click', '.validation-alert-button', function () {
      $('.validation-alert-overlay').remove();
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
      staffName: '',
      staffAvatar: '',
      staffLevel: 1,
      date: null,
      time: null,
      coupon: null,
      contact: {},
      flowHistory: ['initial'],
      initialOption: 'services',
    };

    $('#coupon-code').val('');
    $('.coupon-feedback').hide();

    $('.booking-step').removeClass('active');
    $('.booking-step[data-step="initial"]').addClass('active');

    $('.booking-option-item').removeClass('active');
    $('.booking-option-item[data-option="services"]').addClass('active');
    $('.status-indicator').removeClass('active');
    $('.booking-option-item[data-option="services"] .status-indicator').addClass('active');

    $('.service-checkbox').prop('checked', false);
    $('.service-item').removeClass('selected');

    $('.calendar-day').removeClass('selected');
    $('.time-slot').removeClass('selected');

    $('#client-name, #client-phone, #client-email, #client-comment').val('');
    $('.field-error').remove();
    $('.contact-form input').removeClass('error');

    $('.selected-master-info').empty();
    $('.summary-services-list').empty();
    $('.summary-total-amount').text('0.00');

    $('.addon-title').hide();
    $('.addon-services-container').hide();
    $('.service-item.addon-item').addClass('disabled');
    $(".service-checkbox[data-is-addon='true']").prop('disabled', true);

    $('.staff-item').removeClass('selected');
    $(".staff-item input[type='radio']").prop('checked', false);
    $('.staff-item.any-master').addClass('selected');
    $(".staff-item.any-master input[type='radio']").prop('checked', true);

    $('.time-sections').empty();

    staffLoadRetryCount = 0;
    servicesLoadRetryCount = 0;
    timeSlotsRetryCount = 0;

    debug('Booking form reset');
  }

  /**
   * Go to a specific step in the booking flow
   * @param {string} step - Step name to navigate to
   */
  function goToStep(step) {
    if (step === 'initial') {
      resetBookingForm();
      return;
    }

    // Clear selected preview slot when going back to initial or services step
    if (step === 'initial' || step === 'services') {
      if (bookingData.selectedPreviewSlot) {
        bookingData.selectedPreviewSlot = null;
        $('.nearest-seances .slot').removeClass('active');
        bookingData.time = null;
      }
    }

    $('.booking-step').removeClass('active');
    $(`.booking-step[data-step="${step}"]`).addClass('active');

    if (step === 'services') {
      const nextButtonText =
        bookingData.initialOption === 'services' ? 'Choose a master' : 'Select date and time';
      $(`.booking-step[data-step="services"] .next-btn`).text(nextButtonText);

      if (bookingData.services.length === 0) {
        const saved = localStorage.getItem('sunnyBookingData');
        if (saved) {
          const parsed = JSON.parse(saved);
          if (parsed.services?.length) {
            bookingData.services = parsed.services;
            bookingData.coreServices = parsed.coreServices || [];
            bookingData.addons = parsed.addons || [];
          }
        }
      }

      setTimeout(function () {
        $('.category-tab').removeClass('active');
        $('.category-tab').first().addClass('active');
        $('.category-services').hide();
        $('.category-services').first().show();
      }, 100);

      updateNextButtonState();
    }

    if (step === 'master') {
      const nextButtonText =
        bookingData.initialOption === 'master' ? 'Select services' : 'Select date and time';
      $(`.booking-step[data-step="master"] .next-btn`).text(nextButtonText);

      if (bookingData.services.length === 0) {
        const saved = localStorage.getItem('sunnyBookingData');
        if (saved) {
          const parsed = JSON.parse(saved);
          if (parsed.services?.length) {
            bookingData.services = parsed.services;
            bookingData.coreServices = parsed.coreServices || [];
            bookingData.addons = parsed.addons || [];
          }
        }
      }

      if (!bookingData.staffId || bookingData.staffId === 'any') {
        bookingData.staffId = 'any';
        $('.staff-item').removeClass('selected');
        $(".staff-item input[type='radio']").prop('checked', false);
        $('.staff-item.any-master').addClass('selected');
        $(".staff-item.any-master input[type='radio']").prop('checked', true);
      }

      const staffItemsCount = $('.staff-item').not('.any-master').length;

      if (bookingData.services.length > 0) {
        if (staffItemsCount === 0) {
          setTimeout(() => {
            loadStaffForServices();
          }, 100);
        }
      } else {
        if (staffItemsCount === 0) {
          setTimeout(() => {
            renderDefaultStaffList();
          }, 100);
        }
      }

      updateMasterNextButtonState();
    }

    if (step === 'datetime') {
      let targetMonth = null;
      let targetYear = null;
      if (bookingData.selectedPreviewSlot && bookingData.selectedPreviewSlot.dateText) {
        const match = bookingData.selectedPreviewSlot.dateText.match(/(\d{1,2}) ([A-Za-z]+),/);
        if (match) {
          const day = match[1];
          const monthName = match[2];
          const monthIndex = getMonthIndex(monthName);
          const year = new Date().getFullYear();
          targetMonth = monthIndex;
          targetYear = year;
        }
      } else if (bookingData.date) {
        const dateObj = new Date(bookingData.date);
        if (!isNaN(dateObj)) {
          targetMonth = dateObj.getMonth();
          targetYear = dateObj.getFullYear();
        }
      }
      if (typeof generateCalendar === 'function') {
        generateCalendar(targetMonth, targetYear);
      }
      updateDateTimeNextButtonState();
      if (bookingData.date && bookingData.staffId && bookingData.services.length > 0) {
        setTimeout(function () {
          loadTimeSlots(bookingData.date);
        }, 100);
      }
    }

    if (step === 'contact') {
      setTimeout(function () {
        try {
          prefillBookingForm();
        } catch (e) {
          debug('Error prefilling form on contact step:', e);
        }

        try {
          const personalInfoSection = $('.contact-form h3');
          if (personalInfoSection.length) {
            personalInfoSection[0].scrollIntoView({
              behavior: 'smooth',
              block: 'start',
            });
          }
        } catch (e) {
          debug('Error scrolling to personal info:', e);
        }

        try {
          showBookingDetailsNotification();
        } catch (e) {
          debug('Error showing notification:', e);
        }
      }, 200);
    }

    debug('Navigated to step', step);

    $(document).trigger('bookingStepChanged', [step]);

    // Initialize floating notification for confirm and contact steps
    if (step === 'confirm' || step === 'contact') {
      setTimeout(() => {
        initFloatingNotification();
      }, 300); // Small delay to ensure DOM is updated
    }
  }
  function renderDefaultStaffList() {
    let html = '';
    const isAnyMasterSelected = bookingData.staffId === 'any' || !bookingData.staffId;
    html += `
    <label class="staff-item any-master first${isAnyMasterSelected ? ' selected' : ''}" data-staff-id="any" data-staff-level="1">
      <input type="radio" name="staff"${isAnyMasterSelected ? ' checked' : ''}>
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
          <h4 class="staff-name">Random master</h4>
        </div>
        <span class="radio-indicator"></span>
      </div>
    </label>
  `;

    $.ajax({
      url: config.apiEndpoint,
      type: 'POST',
      data: {
        action: 'get_masters',
        nonce: config.nonce,
      },
      success: function (response) {
        if (response.success && response.data && Array.isArray(response.data.data)) {
          response.data.data.forEach(function (staff) {
            const isSelected = bookingData.staffId == staff.id ? ' selected' : '';
            const staffLevel = Number.isInteger(staff.level) ? staff.level : 1;
            const levelTitle = levelTitles[staffLevel] || '';

            let priceModifier = '';
            const modifier = percentMap[staffLevel];

            if (typeof modifier === 'number') {
              const sign = modifier > 0 ? '+' : '';
              priceModifier = `<div class="staff-price-modifier">${sign}${modifier}%    <span>to price</span></div>`;
            }

            html += `
              <label class="staff-item${isSelected}" data-staff-id="${staff.id}" data-staff-level="${staffLevel}">
                <input type="radio" name="staff"${isSelected ? ' checked' : ''}>
                <div class="staff-radio-content">
                  <div class="staff-avatar">
                    ${staff.avatar ? `<img src="${staff.avatar}" alt="${staff.name}">` : ''}
                  </div>
                  <div class="staff-info">
                    <h4 class="staff-name">${staff.name}</h4>
                    <div class="staff-specialization">
                     <div class="staff-stars">
                            ${generateStarsHtml(staffLevel)}
                    </div>
                      ${levelTitle ? `<span class="studio-name">(${levelTitle})</span>` : ''}
                    </div>
                  </div>
                  ${priceModifier}
                  <span class="radio-indicator"></span>
                </div>
              </label>
            `;
          });
        }

        $('.staff-list').html(html);
        updateMasterNextButtonState();
      },
      error: function () {
        $('.staff-list').html(html + '<p class="no-items-message">All specialists available.</p>');
        updateMasterNextButtonState();
      },
    });
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
  function addService(
    id,
    title,
    price,
    currency,
    duration,
    wearTime,
    isAddon,
    altegioId,
    desc = '',
  ) {
    try {
      if (!id || !title || !price) return false;

      const existingIndex = bookingData.services.findIndex((s) => s.id == id);
      if (existingIndex === -1) {
        const newService = {
          id,
          altegioId: altegioId || id,
          title,
          price,
          currency: currency || 'SGD',
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
      console.error('Error in addService:', error);
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

    debug('Service removed', id);
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
    bookingData.staffSpecialization = specialization || '';
    if (typeof level === 'number') {
      bookingData.staffLevel = level;
    } else {
      const parsedLevel = parseInt(level);
      bookingData.staffLevel = isNaN(parsedLevel) ? 1 : parsedLevel;
    }

    debug('Staff selected', { id, name, level, specialization });

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
    if (!bookingData.services || bookingData.services.length === 0) {
      console.warn('No services selected for staff loading');
      return;
    }

    const serviceIds = bookingData.services
      .map((service) => service.altegioId || service.id)
      .join(',');

    debug('Loading staff for services', serviceIds);

    $('.loading-overlay').show();
    $('.staff-list').html('<p class="loading-message">Loading specialists...</p>');

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
      type: 'POST',
      data: {
        action: 'get_filtered_staff',
        service_id: serviceIds,
        nonce: config.nonce,
      },
      success: function (response) {
        // Hide loading overlay on success
        $('.loading-overlay').hide();

        if (response.success && response.data && Array.isArray(response.data.data)) {
          renderStaff(response.data.data);
        } else {
          $('.staff-list').html(
            '<p class="no-items-message">No specialists available for the selected services.</p>',
          );
          debug('Failed to load staff from API', response);
        }
      },
      error: function (xhr, status, error) {
        $('.loading-overlay').hide();
        debug('AJAX error loading staff', { status, error });

        // Retry logic
        staffLoadRetryCount++;
        if (staffLoadRetryCount <= MAX_STAFF_RETRIES) {
          setTimeout(() => {
            debug('Retrying staff load request', { attempt: staffLoadRetryCount });
            performStaffLoadRequest(serviceIds);
          }, STAFF_RETRY_DELAY);
        } else {
          $('.staff-list').html('<p class="no-items-message">Error loading specialists.</p>');
        }
      },
    });
  }

  /**
   * Perform the actual services loading request with retry logic
   * @param {string|number} masterId - Master ID
   */
  function performServicesLoadRequest(masterId) {
    $.ajax({
      url: booking_params.ajax_url,
      method: 'POST',
      data: {
        action: 'get_filtered_services',
        staff_id: masterId,
        nonce: booking_params.nonce,
      },
      success: function (response) {
        // Hide loading overlay on success
        $('.loading-overlay').hide();

        if (response.success && response.data && response.data.html) {
          $('.booking-popup .services-list').html(response.data.html);
          updateAddonAvailability();
          updateNextButtonState();
          // Reset retry counter on successful load
          servicesLoadRetryCount = 0;
        } else {
          // Try to retry if data is empty but request was "successful"
          if (servicesLoadRetryCount < MAX_SERVICES_RETRIES) {
            servicesLoadRetryCount++;
            debug(
              `Retrying services load attempt ${servicesLoadRetryCount}/${MAX_SERVICES_RETRIES} - empty data`,
            );

            setTimeout(() => {
              performServicesLoadRequest(masterId);
            }, SERVICES_RETRY_DELAY);
          } else {
            console.error('Services response details:', response);
            $('.booking-popup .services-list').html(
              '<p class="no-items-message">No services available for this master.</p>',
            );
          }
        }
      },
      error: function (xhr, status, error) {
        debug('AJAX error loading services', { status, error });

        // Retry on error
        if (servicesLoadRetryCount < MAX_SERVICES_RETRIES) {
          servicesLoadRetryCount++;
          debug(
            `Retrying services load attempt ${servicesLoadRetryCount}/${MAX_SERVICES_RETRIES} after error`,
          );

          // Keep loading overlay visible during retry
          setTimeout(() => {
            performServicesLoadRequest(masterId);
          }, SERVICES_RETRY_DELAY);
        } else {
          // Hide loading overlay after all retries failed
          $('.loading-overlay').hide();
          console.error('AJAX Error:', {
            status: status,
            error: error,
            responseText: xhr.responseText,
          });
          $('.booking-popup .services-list').html(
            '<p class="no-items-message">Error loading services. Please try again.</p>',
          );
        }
      },
    });
  }

  /**
   * Load time slots for all masters for a given date (when "All masters" is selected)
   * @param {string} date - Date in YYYY-MM-DD format
   */
  // ...existing code...
  function loadTimeSlotsForAllMasters(date) {
    const serviceIds = bookingData.services.map((s) => s.id);

    $('.time-preloader').show();
    $('.time-sections').html('<div class="loading">Loading available time slots...</div>');

    $.ajax({
      url: booking_params.ajax_url,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'get_time_slots_for_all_masters',
        nonce: booking_params.nonce,
        date: date,
        service_ids: serviceIds,
      },
      success: function (response) {
        $('.time-preloader').hide();
        if (response.success && response.data) {
          renderAllMastersSlots(response.data);
        } else {
          $('.calendar-slots').html('<div class="error">No slots found</div>');
        }
      },
      error: function (xhr, status, error) {
        $('.time-preloader').hide();
        $('.calendar-slots').html('<div class="error">Error loading slots</div>');
        console.error('AJAX error:', error, xhr.responseText);
      },
    });
  }
  function renderAllMastersSlots(data) {
    const $target = $('.time-sections');
    if (!data || Object.keys(data).length === 0) {
      $target.html(
        '<div class="no-slots-message">No available slots for any master on this date.</div>',
      );
      return;
    }

    let html = '';
    Object.values(data).forEach((entry) => {
      if (!entry.slots || entry.slots.length === 0) return;

      const staff = entry.staff || {};
      const name = staff.name || 'Master';
      const avatar = staff.avatar ? `<img src="${staff.avatar}" alt="${name}">` : '';
      const level = staff.level || 1;

      // Build slots HTML
      let slotsHtml = '';
      entry.slots.forEach((slot) => {
        let time =
          typeof slot === 'object' && slot.time
            ? slot.time
            : typeof slot === 'string'
              ? slot.split(' ')[1]?.slice(0, 5)
              : '';
        if (!time) return;
        slotsHtml += `<div class="time-slot"
        data-time="${time}"
        data-staff-id="${staff.id}"
        data-staff-name="${name}"
        data-staff-level="${level}"
        data-staff-avatar="${staff.avatar || ''}"
      >${time}</div>`;
      });

      html += `
      <div class="master-slots-group" data-staff-id="${staff.id}" data-staff-name="${name}" data-staff-level="${level}">
        <div class="staff-radio-content">
          <div class="staff-avatar">${avatar}</div>
          <div class="staff-info">
            <h4 class="staff-name">${name}</h4>
            <div class="staff-specialization">
              <div class="staff-stars">${generateStarsHtml(level)}</div>
              ${levelTitles[level] ? `<span class="studio-name">(${levelTitles[level]})</span>` : ''}
              ${percentMap[level] > 0 ? `<div class="staff-price-modifier">+${percentMap[level]}%    <span>to price</span></div>` : ''}
            </div>
          </div>
        </div>
        <div class="master-slots-list">${slotsHtml}</div>
      </div>
    `;
    });

    if (!html) {
      $target.html(
        '<div class="no-slots-message">No available slots for any master on this date.</div>',
      );
    } else {
      $target.html(html);
    }
  }

  // Patch loadTimeSlots to support "All masters" logic
  const originalLoadTimeSlots = loadTimeSlots;
  loadTimeSlots = function (date) {
    if (bookingData.staffId === 'any') {
      loadTimeSlotsForAllMasters(date);
      return;
    }
    originalLoadTimeSlots(date);
  };

  // Handle slot click for "All masters"
  $(document).on('click', '.time-sections .time-slot', function () {
    if (bookingData.selectedPreviewSlot) {
      bookingData.selectedPreviewSlot = null;
    }
    if (bookingData.staffId === 'any') {
      const $slot = $(this);
      bookingData.staffId = $slot.data('staff-id');
      bookingData.staffName = $slot.data('staff-name');
      bookingData.staffLevel = parseInt($slot.data('staff-level')) || 1;
      bookingData.staffAvatar = $slot.data('staff-avatar') || '';
      bookingData.time = $slot.data('time');
      $('.time-slot').removeClass('selected');
      $slot.addClass('selected');
      updateDateTimeNextButtonState();
      updateSummary();
      return;
    }
    // Default logic for single master
    const time = $(this).data('time');
    selectTime(time);
    $('.time-slot').removeClass('selected');
    $(this).addClass('selected');
    updateDateTimeNextButtonState();
  });

  /**
   * Load time slots for selected date and staff with loading overlay and retry mechanism
   * @param {string} date - Date in YYYY-MM-DD format
   */
  function loadTimeSlots(date) {
    if (!bookingData.staffId || bookingData.services.length === 0) {
      $('.time-sections').html(
        '<p class="error-message">Please select a staff and service first.</p>',
      );
      return;
    }

    if (!date) {
      $('.time-sections').html('<p class="error-message">Please select a date.</p>');
      return;
    }

    const serviceIds = bookingData.services.map((s) => s.altegioId || s.id);
    const currentStaffId = bookingData.staffId;
    const currentServices = JSON.stringify(serviceIds);
    const currentDate = date;

    $('.time-preloader').show();
    $('.time-sections').html('<p class="loading-message">Loading available time slots...</p>');

    timeSlotsRetryCount = 0;

    $.ajax({
      url: booking_params.ajax_url,
      method: 'POST',
      data: {
        action: 'get_time_slots',
        nonce: booking_params.nonce,
        staff_id: currentStaffId,
        date: currentDate,
        service_ids: serviceIds,
      },
      success: function (response) {
        $('.time-preloader').hide();

        if (
          bookingData.staffId !== currentStaffId ||
          JSON.stringify(bookingData.services.map((s) => s.altegioId || s.id)) !==
            currentServices ||
          bookingData.date !== currentDate
        ) {
          return;
        }

        let slots = [];
        if (response.success && response.data) {
          if (Array.isArray(response.data)) {
            slots = response.data;
          } else if (Array.isArray(response.data.slots)) {
            slots = response.data.slots;
          } else if (response.data.data && Array.isArray(response.data.data)) {
            slots = response.data.data;
          }
        }

        if (slots.length > 0) {
          renderTimeSlots(slots);
          timeSlotsRetryCount = 0;
        } else {
          $('.time-sections').html(
            '<p class="error-message">No available time slots for this day.</p>',
          );
        }
      },
      error: function (xhr, status, error) {
        $('.time-preloader').hide();
        $('.time-sections').html(
          '<p class="error-message">Error loading time slots. Please try again later.</p>',
        );
      },
    });
  }

  /**
   * Perform the actual time slots loading request with retry logic
   * @param {string} date - Date in YYYY-MM-DD format
   * @param {Array} serviceIds - Array of service IDs
   */
  function performLoadTimeSlotsRequest(date, serviceIds) {
    $.ajax({
      url: booking_params.ajax_url,
      method: 'POST',
      data: {
        action: 'get_time_slots',
        nonce: booking_params.nonce,
        staff_id: bookingData.staffId,
        date: date,
        service_ids: serviceIds,
      },
      success: function (response) {
        // Hide loading overlay on success
        $('.time-preloader').hide();

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
            $('.time-sections').html(
              '<p class="error-message">No available time slots for this day.</p>',
            );
          }
        } else {
          // Try to retry if request was not successful
          if (timeSlotsRetryCount < MAX_TIMESLOTS_RETRIES) {
            timeSlotsRetryCount++;
            debug(
              `Retrying time slots load attempt ${timeSlotsRetryCount}/${MAX_TIMESLOTS_RETRIES} - unsuccessful response`,
            );

            setTimeout(() => {
              performLoadTimeSlotsRequest(date, serviceIds);
            }, TIMESLOTS_RETRY_DELAY);
          } else {
            $('.time-sections').html(
              '<p class="error-message">Error loading time slots. Please try again later.</p>',
            );
          }
        }
      },
      error: function (xhr, status, error) {
        console.error('Error loading time slots:', error, xhr.responseText);

        if (timeSlotsRetryCount < MAX_TIMESLOTS_RETRIES) {
          timeSlotsRetryCount++;
          debug(
            `Retrying time slots load attempt ${timeSlotsRetryCount}/${MAX_TIMESLOTS_RETRIES} after error`,
          );
          setTimeout(() => {
            performLoadTimeSlotsRequest(date, serviceIds);
          }, TIMESLOTS_RETRY_DELAY);
        } else {
          $('.time-preloader').hide();
          $('.time-sections').html(
            '<p class="error-message">Error loading time slots. Please try again later.</p>',
          );
        }
      },
    });
  }

  function fetchAndShowNextSeancesForMasters() {
    $('.staff-item').each(function () {
      const $staff = $(this);
      const staffId = $staff.data('staff-id');
      const $target = $staff.find('.nearest-seances');

      if (!staffId || staffId === 'any') return;

      $target.html('<div class="loading-slots">Loading...</div>');

      $.ajax({
        url: booking_params.ajax_url,
        method: 'POST',
        data: {
          action: 'get_next_seances',
          staff_id: staffId,
          nonce: booking_params.nonce,
        },
        success: function (response) {
          if (response.success && response.data && response.data.seances.length) {
            const date = response.data.seance_date;
            const formattedDate = formatDateDisplay(date);
            const slotsHtml = response.data.seances

              .map(
                (s) =>
                  `<div class="slot" data-time="${s.time}" data-duration="${s.duration || 30}">${s.time}</div>`,
              )
              .join('');

            $target.html(`
            <div class="seance-date">Nearest time slot for the appointment ${formattedDate}:</div>
            <div class="slots">${slotsHtml}</div>
          `);
          } else {
            $target.html('<div class="no-slots">No available slots</div>');
          }
        },
        error: function () {
          $target.html('<div class="no-slots">Error loading slots</div>');
        },
      });
    });
  }

  function loadServicesForMaster(masterId) {
    $('.loading-overlay').show();
    $('.services-list').html('<p class="loading-message">Loading services...</p>');

    if (masterId === 'any') {
      $.ajax({
        url: booking_params.ajax_url,
        method: 'POST',
        data: {
          action: 'get_services',
          nonce: booking_params.nonce,
        },
        success: function (response) {
          $('.loading-overlay').hide();
          if (response.success && response.data && response.data.html) {
            $('.services-list').html(response.data.html);
            if (typeof updateAddonAvailability === 'function') updateAddonAvailability();
            if (typeof updateNextButtonState === 'function') updateNextButtonState();
          } else {
            $('.services-list').html('<p class="no-items-message">No services available.</p>');
          }
        },
        error: function () {
          $('.loading-overlay').hide();
          $('.services-list').html(
            '<p class="no-items-message">Error loading services. Please try again.</p>',
          );
        },
      });
      return;
    }

    if (bookingData.date) {
      $.ajax({
        url: booking_params.ajax_url,
        method: 'POST',
        data: {
          action: 'get_time_slots',
          nonce: booking_params.nonce,
          staff_id: masterId,
          date: bookingData.date,
          service_ids: bookingData.services.map((s) => s.altegioId || s.id),
        },
        success: function (slotResp) {
          let slots = [];
          if (slotResp.success && slotResp.data) {
            if (Array.isArray(slotResp.data)) {
              slots = slotResp.data;
            } else if (Array.isArray(slotResp.data.slots)) {
              slots = slotResp.data.slots;
            }
          }
          let filterByDuration = false;
          let minSlotDuration = 0;
          if (slots.length === 1) {
            minSlotDuration = slots[0].seance_length ? Math.round(slots[0].seance_length / 60) : 30;
            if (minSlotDuration <= 30) {
              filterByDuration = true;
            }
          }
          $.ajax({
            url: booking_params.ajax_url,
            method: 'POST',
            data: {
              action: 'get_filtered_services',
              staff_id: masterId,
              nonce: booking_params.nonce,
            },
            success: function (response) {
              $('.loading-overlay').hide();
              if (response.success && response.data && response.data.html) {
                let $html = $('<div>' + response.data.html + '</div>');
                if (filterByDuration && minSlotDuration > 0 && slots.length === 1) {
                  $html.find('.service-item').each(function () {
                    const duration =
                      parseInt($(this).find('.service-checkbox').data('service-duration')) || 0;
                    if (duration > minSlotDuration) {
                      $(this).remove();
                    }
                  });
                  $('.services-list').html($html.html());
                  setTimeout(function () {
                    $('.category-tab').removeClass('active');
                    $('.category-tab[data-category-id="8"]').addClass('active');
                    $('.category-services').hide();
                    $('.category-services[data-category-id="8"]').show();
                  }, 50);
                } else {
                  $('.services-list').html($html.html());
                }

                if (typeof updateAddonAvailability === 'function') updateAddonAvailability();
                if (typeof updateNextButtonState === 'function') updateNextButtonState();
              } else {
                $('.services-list').html(
                  '<p class="no-items-message">No services available for this master.</p>',
                );
              }
            },
            error: function () {
              $('.loading-overlay').hide();
              $('.services-list').html(
                '<p class="no-items-message">Error loading services. Please try again.</p>',
              );
            },
          });
        },
        error: function () {
          $('.loading-overlay').hide();
          $('.services-list').html(
            '<p class="no-items-message">Error loading slots. Please try again.</p>',
          );
        },
      });
      return;
    }

    $.ajax({
      url: booking_params.ajax_url,
      method: 'POST',
      data: {
        action: 'get_filtered_services',
        staff_id: masterId,
        nonce: booking_params.nonce,
      },
      success: function (response) {
        $('.loading-overlay').hide();
        if (response.success && response.data && response.data.html) {
          $('.services-list').html(response.data.html);
          if (typeof updateAddonAvailability === 'function') updateAddonAvailability();
          if (typeof updateNextButtonState === 'function') updateNextButtonState();
        } else {
          $('.services-list').html(
            '<p class="no-items-message">No services available for this master.</p>',
          );
        }
      },
      error: function () {
        $('.loading-overlay').hide();
        $('.services-list').html(
          '<p class="no-items-message">Error loading services. Please try again.</p>',
        );
      },
    });
  }

  $(document).on('click', '.nearest-seances .slot', function (e) {
    e.stopPropagation();
    e.preventDefault();
    $('.nearest-seances .slot').removeClass('active');
    $(this).addClass('active');
    const $staff = $(this).closest('.staff-item');

    $('.staff-item').removeClass('selected');
    $(".staff-item input[type='radio']").prop('checked', false);
    $staff.addClass('selected');
    $staff.find("input[type='radio']").prop('checked', true);

    const staffId = $staff.data('staff-id');
    const staffName = $staff.find('.staff-name').text();
    const staffLevel = $staff.data('staff-level') || 1;
    const staffAvatar = $staff.find('.staff-avatar img').attr('src') || '';
    const time = $(this).data('time');
    const dateText = $staff.find('.seance-date').text();
    const minSlotDuration = $(this).data('duration') ? parseInt($(this).data('duration')) : 30;
    let onlyOneSlot = false;
    const $slots = $(this).parent().find('.slot');
    if ($slots.length === 1) {
      onlyOneSlot = true;
    }

    let date = '';
    const match = dateText.match(/appointment\s+(\d{1,2}) ([A-Za-z]+),/);
    if (match) {
      const day = match[1];
      const monthName = match[2];
      const monthIndex = getMonthIndex(monthName);
      const year = new Date().getFullYear();
      date = `${year}-${String(monthIndex + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    bookingData.selectedPreviewSlot = {
      staffId: staffId,
      time: time,
      dateText: dateText,
      minSlotDuration: minSlotDuration,
      onlyOneSlot: onlyOneSlot,
    };
    bookingData.staffId = staffId;
    bookingData.staffName = staffName;
    bookingData.staffLevel = staffLevel;
    bookingData.staffAvatar = staffAvatar;
    bookingData.date = date;
    bookingData.time = time;

    return false;
  });

  function renderStaff(staffList) {
    if (!staffList || staffList.length === 0) {
      $('.staff-list').html(
        '<p class="no-items-message">No specialists available for the selected services.</p>',
      );
      return;
    }

    let html = '';
    const isAnyMasterSelected = bookingData.staffId === 'any' || !bookingData.staffId;

    html += `
    <label class="staff-item any-master first${isAnyMasterSelected ? ' selected' : ''}" data-staff-id="any" data-staff-level="1">
      <input type="radio" name="staff"${isAnyMasterSelected ? ' checked' : ''}>
      <div class="staff-radio-content">
        <div class="staff-avatar circle yellow-bg">
          <img src="${themeUrl}/assets/svg/any-master.svg" alt="Random master">
        </div>
        <div class="staff-info">
          <h4 class="staff-name">Random master</h4>
        </div>
        <span class="radio-indicator"></span>
      </div>
    </label>
  `;

    staffList.forEach(function (staff) {
      const isSelected = bookingData.staffId == staff.id ? ' selected' : '';
      const staffLevel = Number.isInteger(staff.level) ? staff.level : 1;
      const levelTitle = levelTitles[staffLevel] || '';

      let priceModifier = '';
      const modifier = percentMap[staffLevel];

      if (typeof modifier === 'number') {
        const sign = modifier > 0 ? '+' : '';
        priceModifier = `<div class="staff-price-modifier">${sign}${modifier}%    <span>to price</span></div>`;
      }

      html += `
      <label class="staff-item${isSelected}" data-staff-id="${staff.id}" data-staff-level="${staffLevel}">
        <input type="radio" name="staff"${isSelected ? ' checked' : ''}>
        <div class="staff-radio-content">
          <div class="staff-avatar">
            ${staff.avatar ? `<img src="${staff.avatar}" alt="${staff.name}">` : ''}
          </div>
          <div class="staff-info">
            <h4 class="staff-name">${staff.name}</h4>
            <div class="staff-specialization">
             <div class="staff-stars">
                    ${generateStarsHtml(staffLevel)}
            </div>
              ${levelTitle ? `<span class="studio-name">(${levelTitle})</span>` : ''}
            </div>
            <div class="nearest-seances"></div>
          </div>
          ${priceModifier}
          <span class="radio-indicator"></span>
        </div>
      </label>
    `;
    });

    $('.staff-list').html(html);
    updateMasterNextButtonState();

    fetchAndShowNextSeancesForMasters();
  }

  jQuery(document).ready(fetchAndShowNextSeancesForMasters);

  /**
   * Select a date
   * @param {string} date - Date in YYYY-MM-DD format
   */
  function selectDate(date) {
    if (bookingData.selectedPreviewSlot) {
      bookingData.selectedPreviewSlot = null;
    }
    bookingData.date = date;
    debug('Date selected', date);
  }

  /**
   * Select a time
   * @param {string} time - Time in HH:MM format
   */
  function selectTime(time) {
    bookingData.time = time;
    debug('Time selected', time);
  }

  /**
   * Generate calendar for date selection
   * This creates a month view calendar starting from current month
   */
  function generateCalendar(targetMonth, targetYear) {
    const today = new Date();
    let month = typeof targetMonth === 'number' ? targetMonth : today.getMonth();
    let year = typeof targetYear === 'number' ? targetYear : today.getFullYear();

    let selectedDate = formatDate(today);
    let selectedTime = null;
    // If there is a saved slot selection, use it
    if (bookingData.selectedPreviewSlot && bookingData.selectedPreviewSlot.time) {
      const match =
        bookingData.selectedPreviewSlot.dateText &&
        bookingData.selectedPreviewSlot.dateText.match(/(\d{1,2}) ([A-Za-z]+),/);
      if (match) {
        const day = match[1];
        const monthName = match[2];
        const monthIndex = getMonthIndex(monthName);
        const slotYear = year; // Use passed year or current year
        selectedDate = `${slotYear}-${String(monthIndex + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        selectedTime = bookingData.selectedPreviewSlot.time;
        bookingData.date = selectedDate;
        bookingData.time = selectedTime;
        month = monthIndex;
        year = slotYear;
      }
    } else if (bookingData.date) {
      selectedDate = bookingData.date;
      const dateObj = new Date(selectedDate);
      if (!isNaN(dateObj)) {
        month = dateObj.getMonth();
        year = dateObj.getFullYear();
      }
    } else {
      bookingData.date = selectedDate;
    }

    renderCalendar(month, year);

    setTimeout(() => {
      $(`.calendar-day[data-date="${selectedDate}"]`).addClass('selected');
      loadTimeSlots(selectedDate);
      // After rendering slots, highlight the selected one
      if (selectedTime) {
        setTimeout(() => {
          $(`.time-slot[data-time="${selectedTime}"]`).addClass('selected');
        }, 100);
      }
    }, 50);

    debug('Calendar generated + selected slot loaded', selectedDate, selectedTime);
  }

  /**
   * Navigate calendar to previous or next month
   * @param {number} direction - Direction to navigate (-1 for prev, 1 for next)
   */
  function navigateCalendar(direction) {
    const monthText = $('.month-header span').text();
    const [month, year] = monthText.split(' ');

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
    debug('Calendar navigated to', { month: newMonth, year: newYear });
  }

  /**
   * Get month index from name
   * @param {string} monthName - Month name
   * @returns {number} - Month index (0-11)
   */
  function getMonthIndex(monthName) {
    const months = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
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

    const monthNames = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
    $('.month-header span').text(monthNames[month] + ' ' + year);

    let html = '';

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

      let classes = 'calendar-day';
      if (isToday) classes += ' today';
      if (isPast) classes += ' disabled';

      if (bookingData.date === dateStr && !isPast) {
        classes += ' selected';
      }

      html += `<div class="${classes}" data-date="${dateStr}">${i}</div>`;
    }

    $('.calendar-grid').html(html);

    // Check availability for all days in this month (if we have staff and services selected)
    if (bookingData.staffId && bookingData.services.length > 0) {
      // Small delay to ensure DOM is updated
      setTimeout(() => {
        checkDayAvailability(month, year);

        // After checking availability, ensure slots are loaded for selected date
        setTimeout(() => {
          if (bookingData.date) {
            const currentMonth = new Date(bookingData.date).getMonth();
            const currentYear = new Date(bookingData.date).getFullYear();

            if (currentMonth === month && currentYear === year) {
              loadTimeSlots(bookingData.date);
            }
          } else {
            // If no date selected, auto-select first available date and load its slots
            autoSelectAndLoadFirstAvailableDate();
          }
        }, 300);
      }, 100);
    } else {
      // Load time slots for currently selected date if it's in this month
      if (bookingData.date) {
        const currentMonth = new Date(bookingData.date).getMonth();
        const currentYear = new Date(bookingData.date).getFullYear();

        if (currentMonth === month && currentYear === year) {
          loadTimeSlots(bookingData.date);
        }
      }
    }
  }

  /**
   * Auto-select first available date and load its slots
   */
  function autoSelectAndLoadFirstAvailableDate() {
    // Wait a bit for availability classes to be applied
    setTimeout(() => {
      const $firstAvailable = $('.calendar-day.available:not(.disabled):not(.empty)').first();

      if ($firstAvailable.length) {
        const date = $firstAvailable.data('date');
        selectDate(date);
        $('.calendar-day').removeClass('selected');
        $firstAvailable.addClass('selected');
        loadTimeSlots(date);
      } else {
        // If no available dates found, try to select today if it's not disabled
        const today = formatDate(new Date());
        const $todayElement = $(`.calendar-day[data-date="${today}"]`);

        if ($todayElement.length && !$todayElement.hasClass('disabled')) {
          selectDate(today);
          $('.calendar-day').removeClass('selected');
          $todayElement.addClass('selected');
          loadTimeSlots(today);
        } else {
          // Try to select first non-disabled date
          const $firstNonDisabled = $('.calendar-day:not(.disabled):not(.empty)').first();
          if ($firstNonDisabled.length) {
            const date = $firstNonDisabled.data('date');
            selectDate(date);
            $('.calendar-day').removeClass('selected');
            $firstNonDisabled.addClass('selected');
            loadTimeSlots(date);
          }
        }
      }
    }, 200);
  }

  /**
   * Format date as YYYY-MM-DD
   * @param {Date} date - Date object
   * @returns {string} - Formatted date
   */
  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
  }

  /**
   * Render time slots
   * @param {Array} slots - Array of time slot objects from API
   */
  function renderTimeSlots(slots) {
    const $target = $('.time-sections');
    if (!slots || slots.length === 0) {
      $target.html(
        '<div class="no-slots-message">This servise is not available at the selected time.</div>',
      );
      return;
    }

    // Process the time slots from API format to simple time strings
    const times = [];
    slots.forEach((slot) => {
      // Handle different data formats from API
      if (typeof slot === 'object' && slot.time) {
        times.push(slot.time);
      } else if (typeof slot === 'string') {
        // If it's a string like "2025-05-23 10:00:00", extract just the time part
        const timePart = slot.split(' ')[1];
        if (timePart) {
          times.push(timePart.slice(0, 5)); // Get just HH:MM
        }
      }
    });

    // Group times by period of day
    const grouped = {
      Morning: times.filter((t) => parseInt(t.split(':')[0]) < 12),
      Afternoon: times.filter((t) => {
        const h = parseInt(t.split(':')[0]);
        return h >= 12 && h < 17;
      }),
      Evening: times.filter((t) => parseInt(t.split(':')[0]) >= 17),
    };

    // Build HTML for each time group
    let html = '';
    for (const [label, group] of Object.entries(grouped)) {
      if (!group.length) continue;
      html += `<div class="time-group">
      <div class="time-group-title">${label}</div>
      <div class="time-slot-list">${group.map((t) => `<div class="time-slot" data-time="${t}">${t}</div>`).join('')}</div>
    </div>`;
    }

    $target.html(html);

    // If a time was previously selected, mark it as selected
    if (bookingData.time) {
      $(`.time-slot[data-time="${bookingData.time}"]`).addClass('selected');
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
    const numericPrice = parseFloat(basePrice.toString().replace(/[^\d.]/g, ''));
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

    debug('Price adjustment', {
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
      showValidationAlert('Please select a date');
      return false;
    }

    if (!bookingData.time) {
      showValidationAlert('Please select a time');
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
    if (!dateStr) return '';

    const date = new Date(dateStr);
    const day = date.getDate();
    const monthNames = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
    const month = monthNames[date.getMonth()];
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayOfWeek = dayNames[date.getDay()];

    return `${day} ${month}, ${dayOfWeek}`;
  }

  /**
   * Format time range for display
   * @param {string} timeStr - Time in HH:MM format
   * @returns {string} Formatted time range (e.g., "12:00-13:30")
   */
  function formatTimeRange(timeStr) {
    if (!timeStr) return '';

    // Calculate end time based on service duration if available
    let endTime = '';
    let duration = 0;

    // Get average duration from core services
    if (bookingData.coreServices && bookingData.coreServices.length > 0) {
      bookingData.coreServices.forEach((service) => {
        if (service.duration) {
          // Parse duration from string or number
          let serviceDuration = 0;
          if (typeof service.duration === 'string') {
            const match = service.duration.match(/(\d+)/);
            if (match) serviceDuration = parseInt(match[1]);
          } else if (typeof service.duration === 'number') {
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
      const [hours, minutes] = timeStr.split(':').map(Number);
      const startDate = new Date();
      startDate.setHours(hours, minutes, 0);

      const endDate = new Date(startDate.getTime() + duration * 60000);
      endTime = `${String(endDate.getHours()).padStart(2, '0')}:${String(endDate.getMinutes()).padStart(2, '0')}`;

      // Return time range
      return `${timeStr}-${endTime}`;
    }

    // Return just the time if we can't calculate a range
    return timeStr;
  }

  $(document).on('click', ".booking-step[data-step='master'] .next-btn", function () {
    goToStep('datetime');
    generateCalendar();
    updateSummary();
  });

  /**
   * Updated updateSummary function with proper add-on handling
   */
  function updateSummary() {
    const masterBox = $('.summary-master .master-info');
    const dateTimeBox = $('.booking-date-time');
    const serviceList = $('.summary-services-list').not('.summary-addons');
    const addonsList = $('.summary-addons');
    const masterBonusEl = $('.master-bonus');
    const masterPercent = $('.summary-total-group .percent');
    const totalAmountEl = $('.summary-total-amount');

    if (bookingData.staffAvatar) {
      masterBox.find('.avatar').attr('src', bookingData.staffAvatar);
    } else {
      masterBox.find('.avatar').attr('src', 'https://be.cdn.alteg.io/images/no-master-sm.png');
    }

    masterBox.find('.name').text(bookingData.staffName || 'Random master');

    const stars = generateStarsHtml(bookingData.staffLevel);
    masterBox.find('.stars').html(stars);

    const title = levelTitles[bookingData.staffLevel];
    masterBox.find('.stars-name').text(title ? `(${title})` : '');

    const dateStr = formatDateDisplay(bookingData.date);
    const timeStr = formatTimeRange(bookingData.time);
    dateTimeBox.find('.calendar-date').text(dateStr);
    dateTimeBox.find('.calendar-time').text(timeStr);

    let serviceHTML = '';
    let addonHTML = '';
    let basePrice = 0;
    let masterMarkupAmount = 0;

    let percent = percentMap[bookingData.staffLevel];
    if (typeof percent === 'undefined') {
      percent = 0;
    }
    if (bookingData.staffLevel === -1) {
      percent = -50;
    }

    bookingData.coreServices.forEach((service) => {
      let price = parseFloat(service.price) || 0;
      basePrice += price;

      let serviceMarkup = price * (percent / 100);
      masterMarkupAmount += serviceMarkup;

      const itemHTML = `
            <div class="summary-service-item">
                <div class="service-info">
                    <div class="service-title">
                        <strong>${service.title}</strong>
                           <strong class="service-price">
                              ${price.toFixed(2)} ${service.currency || 'SGD'}
                        </strong>
                    </div>
                    ${service.duration ? `<div class="meta"><strong>Duration:</strong> ${service.duration} min</div>` : ''}
                    ${service.wearTime ? `<div class="meta"><strong>Wear time:</strong> ${service.wearTime}</div>` : ''}
                    ${service.desc ? `<div class="meta service-description">${service.desc}</div>` : ''}
                </div>
             
            </div>
        `;

      serviceHTML += itemHTML;
    });

    if (bookingData.addons && bookingData.addons.length > 0) {
      bookingData.addons.forEach((addon) => {
        let price = parseFloat(addon.price) || 0;
        basePrice += price;

        const addonItemHTML = `
                <div class="summary-service-item addon-service">
                    <div class="service-info">
                        <strong>Add-on: ${addon.title}</strong>
                        ${addon.duration ? `<div class="meta"><strong>Duration:</strong> ${addon.duration} min</div>` : ''}
                        ${addon.wearTime ? `<div class="meta"><strong>Wear time:</strong> ${addon.wearTime}</div>` : ''}
                        ${addon.desc ? `<div class="meta service-description">${addon.desc}</div>` : ''}
                    </div>
                    <div class="service-price">
                        <strong>${price.toFixed(2)} ${addon.currency || 'SGD'}</strong>
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

    masterPercent.text(`${percent > 0 ? '+' : ''}${percent}`);
    masterBonusEl.text(`${masterMarkupAmount.toFixed(2)} SGD`);

    let adjustedTotal = basePrice + masterMarkupAmount;

    let discountAmount = 0;
    if (bookingData.coupon && bookingData.coupon.value > 0) {
      const discountPercent = bookingData.coupon.value;
      discountAmount = adjustedTotal * (discountPercent / 100);
      adjustedTotal = Math.max(0, adjustedTotal - discountAmount);

      $('.summary-coupon').show();
      $('.summary-coupon-group').show();
      $('.coupon-discount-amount').text(`- ${discountAmount.toFixed(2)} SGD`);
      $('.coupon-desc').text(
        `Applied coupon: ${bookingData.coupon.code} (-${discountPercent}% discount)`,
      );
      $('.coupon-discount').text(`- ${discountAmount.toFixed(2)} SGD`);
    } else {
      $('.summary-coupon').hide();
      $('.summary-coupon-group').show();
      $('.coupon-desc').text(`Do you have a coupon? Enter it here and get a discount on services.`);
    }

    totalAmountEl.text(`${adjustedTotal.toFixed(2)} SGD`);

    // Персональная скидка: отображение в .summary-item.personal-discount
    const $personalDiscountBlock = $('.summary-total-group .summary-item.personal-discount');
    if (bookingData.personalDiscountPercent && bookingData.personalDiscountPercent > 0) {
      const personalDiscountAmount = (adjustedTotal * bookingData.personalDiscountPercent) / 100;
      const totalWithPersonalDiscount = Math.max(0, adjustedTotal - personalDiscountAmount);
      $personalDiscountBlock.show();
      $personalDiscountBlock
        .find('.summary-discount-amount')
        .text(`-${personalDiscountAmount.toFixed(2)} SGD`);

      let $totalBlock = $('.summary-total-group .summary-item.total');
      if ($totalBlock.length) {
        $totalBlock.html(
          `<span>Total</span> <span class="summary-total-amount"><s style="color:#aaa;font-weight:400;">${adjustedTotal.toFixed(2)} SGD</s> ${totalWithPersonalDiscount.toFixed(2)} SGD</span>`,
        );
      }
      bookingData.totalWithTax = totalWithPersonalDiscount;
      bookingData.adjustedPrice = totalWithPersonalDiscount;
    } else {
      $personalDiscountBlock.hide();
      let $totalBlock = $('.summary-total-group .summary-item.total');
      if ($totalBlock.length) {
        $totalBlock.html(
          `<span>Total</span> <span class="summary-total-amount">${adjustedTotal.toFixed(2)} SGD</span>`,
        );
      }
      bookingData.totalWithTax = adjustedTotal;
      bookingData.adjustedPrice = adjustedTotal;
    }

    if (bookingData.contact) {
      $('#client-name').val(bookingData.contact.name || '');
      $('#client-phone').val(bookingData.contact.phone || '');
      $('#client-email').val(bookingData.contact.email || '');
      $('#client-comment').val(bookingData.contact.comment || '');
    }

    bookingData.totalWithTax = adjustedTotal;
    bookingData.basePrice = basePrice;
    bookingData.adjustedPrice = adjustedTotal;
    bookingData.priceAdjustment = masterMarkupAmount;
    bookingData.adjustmentPercent = percent;

    if (bookingData.contact) {
      const cleaned = (bookingData.contact.comment || '')
        .replace(/Price information:[\s\S]*/i, '')
        .trim();
      $('#client-name').val(bookingData.contact.name || '');
      $('#client-phone').val(bookingData.contact.phone || '');
      $('#client-email').val(bookingData.contact.email || '');
      $('#client-comment').val(cleaned);
    }
  }

  function submitBooking() {
    $('.confirm-booking-btn').prop('disabled', true).text('Processing...');
    $('.loading-overlay').show();

    if (
      !bookingData.staffId ||
      !bookingData.date ||
      !bookingData.time ||
      bookingData.services.length === 0
    ) {
      showValidationAlert('Missing booking information. Please complete all steps.');
      $('.confirm-booking-btn').prop('disabled', false).text('Book an appointment');
      $('.loading-overlay').hide();
      return;
    }

    const currentCountryCode = window.getSelectedCountryCode
      ? window.getSelectedCountryCode()
      : null;
    const phoneNumber = $('#client-phone').val().trim().replace(/\D/g, '');
    const fullPhoneNumber = currentCountryCode ? currentCountryCode + phoneNumber : phoneNumber;

    if (!currentCountryCode) {
      showValidationAlert('Please select a country for your phone number.');
      $('.confirm-booking-btn').prop('disabled', false).text('Book an appointment');
      $('.loading-overlay').hide();
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

    const formattedServices = [...bookingData.coreServices, ...bookingData.addons].map(
      (service) => ({
        id: parseInt(service.id),
        altegio_id: service.altegioId || service.id,
        title: service.title,
        price: parseFloat(service.price),
        currency: service.currency || 'SGD',
        duration: service.duration || '',
        is_addon: service.isAddon || false,
      }),
    );

    const cleanComment = $('#client-comment')
      .val()
      .trim()
      .replace(/Price information:[\s\S]*$/i, '')
      .trim();

    let galleryInfo = '';
    if (bookingData.initialOption === 'master' && bookingData.galleryTitle) {
      galleryInfo = `Gallery selection: ${bookingData.galleryTitle}\n\n`;
    }

    // Build service descriptions - separate core services and add-ons
    const coreServiceDescriptions = bookingData.coreServices
      .map((service) => {
        const servicePrice = parseFloat(service.price);
        return `- ${service.title}: ${servicePrice.toFixed(2)} SGD`;
      })
      .join('\n');

    const addonServiceDescriptions = bookingData.addons
      .map((addon) => {
        const servicePrice = parseFloat(addon.price);
        return `- ${addon.title}: ${servicePrice.toFixed(2)} SGD`;
      })
      .join('\n');

    // Combine all service descriptions
    let serviceDescriptions = coreServiceDescriptions;
    if (addonServiceDescriptions) {
      serviceDescriptions += '\n' + addonServiceDescriptions;
    }

    let discountAmount = 0;
    let finalAdjustedPrice = adjustedPriceBeforeDiscount;

    let couponInfo = '';
    if (bookingData.coupon && bookingData.coupon.value > 0) {
      discountAmount = (adjustedPriceBeforeDiscount * bookingData.coupon.value) / 100;
      finalAdjustedPrice = adjustedPriceBeforeDiscount - discountAmount;
      couponInfo = `Coupon discount (${bookingData.coupon.code}): -${discountAmount.toFixed(2)} SGD\n`;
    }

    const fullComment =
      `${cleanComment ? 'Comment from client: ' + cleanComment + '\n\n' : ''}` +
      galleryInfo +
      `WEB-SITE BOOKING 
      Price information:
${serviceDescriptions}
Base price: ${basePrice.toFixed(2)} SGD
Master category: ${adjustmentPercent >= 0 ? '+' : ''}${adjustmentPercent}% (${masterMarkupAmount.toFixed(2)} SGD)
Final price before discount: ${adjustedPriceBeforeDiscount.toFixed(2)} SGD
${couponInfo}Note: Master markup applied only to core services, not to Add-on services.`;

    const bookingRequest = {
      action: 'submit_booking',
      booking_nonce: booking_params.nonce,
      staff_id: bookingData.staffId,
      date: bookingData.date,
      time: bookingData.time,
      core_services: JSON.stringify(
        formattedServices.filter((s) =>
          bookingData.coreServices.find((cs) => parseInt(cs.id) === s.id),
        ),
      ),
      addon_services: JSON.stringify(
        formattedServices.filter((s) => bookingData.addons.find((a) => parseInt(a.id) === s.id)),
      ),
      client_name: bookingData.contact.name,
      client_phone: fullPhoneNumber,
      client_email: bookingData.contact.email || '',
      client_comment: fullComment,
      staff_level: staffLevel,
      base_price: basePrice.toFixed(2),
      adjusted_price: finalAdjustedPrice.toFixed(2),
      price_adjustment: masterMarkupAmount.toFixed(2),
      adjustment_percent: adjustmentPercent,
      total_price: finalAdjustedPrice.toFixed(2),
      coupon_code: bookingData.coupon ? bookingData.coupon.code : '',
    };

    $.ajax({
      url: booking_params.ajax_url,
      type: 'POST',
      data: bookingRequest,
      success: function (response) {
        $('.loading-overlay').hide();
        $('.confirm-booking-btn').prop('disabled', false).text('Book an appointment');

        if (response.success) {
          handleSuccessfulBooking(response.data);
        } else {
          showValidationAlert(response.data?.message || 'Booking failed. Please try again.');
        }
      },
      error: function (xhr, status, error) {
        $('.loading-overlay').hide();
        $('.confirm-booking-btn').prop('disabled', false).text('Book an appointment');
        console.error('Booking submission error:', { xhr, status, error });
        showValidationAlert('Network error. Please check your connection and try again.');
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
   * Handle successful booking response
   * @param {Object} data - Response data
   */
  function handleSuccessfulBooking(data) {
    // Reset retry counter
    window.bookingRetryCount = 0;

    // Get booking reference
    const reference = data.booking?.reference || generateBookingReference();

    // Update confirmation screen values
    $('.booking-reference').text(reference);
    $('.booking-date').text(formatDateDisplay(bookingData.date));
    $('.booking-time').text(formatTimeRange(bookingData.time));

    // Build services summary for confirmation screen
    buildBookingConfirmationSummary();

    // Navigate to confirmation step
    goToStep('confirm');
    updateSummary();

    // Clear session data
    if (config.useLocalStorage) {
      clearBookingSession();
    }

    // Trigger event that booking was confirmed
    $(document).trigger('bookingConfirmed', [
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
    return 'BK' + Math.floor(10000 + Math.random() * 90000);
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
            ${service.title}${service.isAddon ? ' <span class="addon-label"></span>' : ''}
          </span>
          <span class="booked-service-price">
            ${displayPrice.toFixed(2)} ${service.currency || 'SGD'}
          </span>
        </div>`;
    });

    // Add master info with stars
    const masterWithStars = bookingData.staffName + ' ' + generateStarsHtml(bookingData.staffLevel);
    servicesHtml += `</div><div class="booked-master">Specialist: ${masterWithStars}</div>`;

    // Show total price
    const totalPrice = calculateTotalPrice();
    servicesHtml += `<div class="booked-total">Total: ${totalPrice} ${bookingData.services[0]?.currency || 'SGD'}</div>`;

    $('.booked-services-summary').html(servicesHtml);
  }

  /**
   * Calculate total price with master level adjustments
   * @returns {string} - Formatted total price
   */
  function calculateTotalPrice() {
    let totalPrice = 0;

    bookingData.services.forEach(function (service) {
      let price = parseFloat(service.price.toString().replace(/[^\d.]/g, ''));

      // Apply staff level price adjustment
      if (bookingData.staffLevel > 1) {
        price = parseFloat(calculateAdjustedPrice(price, bookingData.staffLevel));
      }

      totalPrice += price;
    });

    return totalPrice.toFixed(2);
  }

  $(document).on('click', '.new-booking-btn', function () {
    resetBookingForm();
  });

  $(document).on('click', '.cancel-booking-btn', function (e) {
    e.preventDefault();
    resetBookingForm();
    $('.booking-popup-overlay').removeClass('active');
    $('body').removeClass('popup-open');
    $('.booking-popup-overlay').hide();
    $('.booking-popup').hide();
    $('.loading-overlay').hide();

    if (typeof resetBookingForm === 'function') {
      resetBookingForm();
    }

    if (window.localStorage) {
      localStorage.removeItem('bookingData');
    }
  });
  $(document).on('mouseenter', '.calendar-day.unavailable', function (e) {
    $('.calendar-tooltip').remove();

    const $day = $(this);
    const offset = $day.offset();
    const tooltip = $(`
    <div class="calendar-tooltip" style="
      position: absolute;
      z-index: 9999;
      left: ${offset.left + $day.outerWidth() / 2}px;
      top: ${offset.top + $day.outerHeight() + 8}px;
      transform: translateX(-50%);
      background: #302f34;
      color: #fff;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      pointer-events: none;
      white-space: nowrap;
    ">
      No available time slots
    </div>
  `);

    $('body').append(tooltip);
  });

  $(document).on('mouseleave', '.calendar-day.unavailable', function () {
    $('.calendar-tooltip').remove();
  });

  // Initialize styles when document is ready
  $(document).ready(function () {
    // Add CSS for country dropdown if not present
    if (!$('#booking-country-dropdown-styles').length) {
      $('<style id="booking-country-dropdown-styles">')
        .text(
          `
          .custom-country-select {
            position: relative;
          }
          .country-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            z-index: 1000;
            display: none;
          }
          .country-dropdown.show {
            display: block;
          }
          .country-option {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
          }
          .country-option:hover,
          .country-option.selected {
            background-color: #f5f5f5;
          }
          .country-option:last-child {
            border-bottom: none;
          }
        `,
        )
        .appendTo('head');
    }
  });
})(jQuery);
