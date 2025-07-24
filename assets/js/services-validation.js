(function ($) {
  'use strict';

  $(document).ready(function () {
    initServicesStep();
  });

  function initServicesStep() {
    // Category tabs functionality
    $(document).on('click', '.category-tab', function () {
      const categoryId = $(this).data('category-id');

      // Update active tab
      $('.category-tab').removeClass('active');
      $(this).addClass('active');

      // Show only services from selected category
      $('.category-services').hide();
      $('.category-services[data-category-id="' + categoryId + '"]').show();
    });

    // Service checkbox selection
    $(document).on('change', '.service-checkbox', function () {
      const isChecked = $(this).is(':checked');
      const serviceItem = $(this).closest('.service-item');

      if (isChecked) {
        serviceItem.addClass('selected');
      } else {
        serviceItem.removeClass('selected');
      }

      // Update next button state
      updateNextButtonState();
    });

    // Make the entire service item clickable to toggle checkbox
    $(document).on('click', '.service-item', function (e) {
      // Prevent clicking on checkbox from triggering this handler
      if ($(e.target).is('.service-checkbox')) {
        return;
      }

      const checkbox = $(this).find('.service-checkbox');
      checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Validate services selection when clicking Next
    $(document).on('click', '.booking-step[data-step="services"] .next-btn', function (e) {
      if (!validateServicesSelection()) {
        e.preventDefault();
        showValidationAlert();
        return false;
      }
    });
  }

  function updateNextButtonState() {
    const hasSelectedServices = $('.service-checkbox:checked').length > 0;
    $('.booking-step[data-step="services"] .next-btn').prop('disabled', !hasSelectedServices);
  }

  function validateServicesSelection() {
    return $('.service-checkbox:checked').length > 0;
  }

  /**
   * Show validation alert when no services selected
   */
  function showValidationAlert(message = 'Please select at least one service.') {
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
})(jQuery);
