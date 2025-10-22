class ApplicationPopup {
  constructor() {
    this.popup = document.getElementById('application-popup');
    this.thankYouPopup = document.getElementById('thank-you-popup');
    this.form = document.getElementById('application-form');
    this.currentCourse = null;

    this.init();
  }

  init() {
    this.bindEvents();
    this.initCountrySelect();
    this.initFormInputs();
  }

  initFormInputs() {
    // Handle input value changes for floating labels
    const inputs = this.form?.querySelectorAll(
      'input[type="text"], input[type="email"], input[type="tel"]',
    );

    inputs?.forEach((input) => {
      // Check initial values
      this.updateInputState(input); // Handle input changes
      input.addEventListener('input', () => {
        this.updateInputState(input);
        this.updateSubmitButtonState();
      });

      input.addEventListener('focus', () => {
        this.updateInputState(input);
      });

      input.addEventListener('blur', () => {
        this.updateInputState(input);
        this.updateSubmitButtonState();
      });
    });
  }

  updateInputState(input) {
    const formGroup = input.closest('.form-group');
    if (!formGroup) return;

    if (input.value.trim() !== '' || document.activeElement === input) {
      formGroup.classList.add('has-value');
    } else {
      formGroup.classList.remove('has-value');
    }
  }

  updateSubmitButtonState() {
    const submitBtn = this.form?.querySelector('.submit-application-btn');
    if (!submitBtn) return;

    const name = document.getElementById('applicant-name')?.value.trim() || '';
    const email = document.getElementById('applicant-email')?.value.trim() || '';
    const phone = document.getElementById('applicant-phone')?.value.trim() || '';

    const allFieldsFilled = name && email && phone;

    if (allFieldsFilled) {
      submitBtn.disabled = false;
      submitBtn.classList.remove('disabled');
    } else {
      submitBtn.disabled = true;
      submitBtn.classList.add('disabled');
    }
  }

  bindEvents() {
    // Submit application buttons
    document.addEventListener('click', (e) => {
      if (
        e.target.matches('.course-card--button .btn.yellow') ||
        e.target.matches('.popup-details__text .btn.yellow')
      ) {
        e.preventDefault();
        this.openPopup(e.target);
      }
    });

    // Close buttons
    document.getElementById('applicationPopupClose')?.addEventListener('click', () => {
      this.closePopup();
    });

    document.getElementById('thankYouPopupClose')?.addEventListener('click', () => {
      this.closeThankYouPopup();
    });

    document.getElementById('thankYouCloseBtn')?.addEventListener('click', () => {
      this.closeThankYouPopup();
    });

    // Overlay clicks
    this.popup?.addEventListener('click', (e) => {
      if (e.target === this.popup || e.target.classList.contains('application-popup__overlay')) {
        this.closePopup();
      }
    });

    this.thankYouPopup?.addEventListener('click', (e) => {
      if (
        e.target === this.thankYouPopup ||
        e.target.classList.contains('thank-you-popup__overlay')
      ) {
        this.closeThankYouPopup();
      }
    });

    // Form submission
    this.form?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleSubmit();
    });

    // Submit button click (backup for disabled button issue)
    const submitBtn = this.form?.querySelector('.submit-application-btn');
    submitBtn?.addEventListener('click', (e) => {
      if (!submitBtn.disabled) {
        e.preventDefault();
        this.handleSubmit();
      }
    });

    // Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closePopup();
        this.closeThankYouPopup();
      }
    });
  }

  initCountrySelect() {
    const countryButton = document.getElementById('applicationCountrySelectButton');
    const countryDropdown = document.getElementById('applicationCountryDropdown');
    const countryOptions = countryDropdown?.querySelectorAll('.country-option');

    if (!countryButton || !countryDropdown) return;

    countryButton.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      const isActive = countryDropdown.classList.contains('active');
      if (isActive) {
        this.closeCountryDropdown();
      } else {
        this.openCountryDropdown();
      }
    });

    countryOptions?.forEach((option) => {
      option.addEventListener('click', () => {
        this.selectCountry(option);
      });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!countryButton.contains(e.target) && !countryDropdown.contains(e.target)) {
        this.closeCountryDropdown();
      }
    });
  }

  openCountryDropdown() {
    const countryButton = document.getElementById('applicationCountrySelectButton');
    const countryDropdown = document.getElementById('applicationCountryDropdown');

    countryDropdown?.classList.add('active');
    countryButton?.classList.add('active');
  }

  closeCountryDropdown() {
    const countryButton = document.getElementById('applicationCountrySelectButton');
    const countryDropdown = document.getElementById('applicationCountryDropdown');

    countryDropdown?.classList.remove('active');
    countryButton?.classList.remove('active');
  }

  selectCountry(option) {
    const countryButton = document.getElementById('applicationCountrySelectButton');
    const selectedSpan = countryButton?.querySelector('.selected-country');
    const allOptions = document.querySelectorAll('#applicationCountryDropdown .country-option');

    // Update selected option
    allOptions.forEach((opt) => opt.classList.remove('selected'));
    option.classList.add('selected');

    // Update button text
    if (selectedSpan) {
      selectedSpan.textContent = option.textContent;
    }

    this.closeCountryDropdown();
  }

  openPopup(button) {
    // Get course information from the course card
    const courseCard = button.closest('.course-card') || button.closest('.popup-details');
    if (!courseCard) return;

    const courseId = courseCard.dataset.id || courseCard.getAttribute('data-id');
    const courseTitle =
      courseCard.dataset.courseTitle ||
      courseCard.getAttribute('data-course-title') ||
      courseCard.querySelector('.course-card--title')?.textContent?.trim();
    const coursePrice =
      courseCard.dataset.coursePrice ||
      courseCard.getAttribute('data-course-price') ||
      courseCard.querySelector('.course-card--price')?.textContent?.replace('$', '').trim();

    // Store course information
    this.currentCourse = {
      id: courseId,
      title: courseTitle,
      price: coursePrice,
    };

    // Fill hidden fields
    document.getElementById('course-id').value = courseId || '';
    document.getElementById('course-title').value = courseTitle || '';
    document.getElementById('course-price').value = coursePrice || '';

    // Show popup
    this.popup.style.display = 'flex';
    setTimeout(() => {
      this.popup.classList.add('active');
      document.body.style.overflow = 'hidden';

      // Initialize input states for floating labels
      const inputs = this.form?.querySelectorAll(
        'input[type="text"], input[type="email"], input[type="tel"]',
      );
      inputs?.forEach((input) => {
        this.updateInputState(input);
      });

      // Initialize submit button state
      this.updateSubmitButtonState();
    }, 10);

    // Focus first input
    const firstInput = this.form.querySelector('input[type="text"]');
    if (firstInput && !firstInput.value) {
      setTimeout(() => firstInput.focus(), 100);
    }
  }

  closePopup() {
    this.popup.classList.remove('active');
    setTimeout(() => {
      this.popup.style.display = 'none';
      document.body.style.overflow = '';
    }, 300);
    this.clearForm();
  }

  openThankYouPopup() {
    this.thankYouPopup.style.display = 'flex';
    setTimeout(() => {
      this.thankYouPopup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }, 10);
  }

  closeThankYouPopup() {
    this.thankYouPopup.classList.remove('active');
    setTimeout(() => {
      this.thankYouPopup.style.display = 'none';
      document.body.style.overflow = '';
    }, 300);
  }

  clearForm() {
    // Only clear non-auto-filled fields or if user is not logged in
    const isLoggedIn = document.body.classList.contains('logged-in');

    if (!isLoggedIn) {
      this.form.reset();
    }

    // Clear errors
    this.clearErrors();

    // Reset country dropdown
    this.closeCountryDropdown();
  }

  validateForm() {
    const name = document.getElementById('applicant-name').value.trim();
    const email = document.getElementById('applicant-email').value.trim();
    const phone = document.getElementById('applicant-phone').value.trim();

    let isValid = true;
    this.clearErrors();

    if (!name) {
      this.showFieldError('applicant-name', 'Name is required');
      isValid = false;
    }

    if (!email) {
      this.showFieldError('applicant-email', 'Email is required');
      isValid = false;
    } else if (!this.isValidEmail(email)) {
      this.showFieldError('applicant-email', 'Please enter a valid email');
      isValid = false;
    }

    if (!phone) {
      this.showFieldError('applicant-phone', 'Phone number is required');
      isValid = false;
    }

    if (!isValid) {
      this.showGlobalError('Please fill in all required fields correctly.');
    }

    return isValid;
  }

  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
      field.classList.add('error');

      // Remove existing error message
      const formGroup = field.closest('.form-group');
      const existingError = formGroup?.querySelector('.field-error');
      if (existingError) {
        existingError.remove();
      }

      // Add new error message
      const errorDiv = document.createElement('div');
      errorDiv.className = 'field-error';
      errorDiv.textContent = message;

      if (formGroup) {
        formGroup.appendChild(errorDiv);
      }
    }
  }

  showGlobalError(message) {
    const errorDiv = this.form.querySelector('.global-form-error');
    const errorSpan = errorDiv?.querySelector('span');

    if (errorDiv && errorSpan) {
      errorSpan.textContent = message;
      errorDiv.style.display = 'flex';
    }
  }

  clearErrors() {
    // Clear field errors
    const errorFields = this.form.querySelectorAll('.error');
    errorFields.forEach((field) => field.classList.remove('error'));

    // Clear individual field error messages
    const fieldErrors = this.form.querySelectorAll('.field-error');
    fieldErrors.forEach((error) => error.remove());

    // Clear global error
    const errorDiv = this.form.querySelector('.global-form-error');
    if (errorDiv) {
      errorDiv.style.display = 'none';
    }
  }

  async handleSubmit() {
    if (!this.validateForm()) {
      return;
    }

    const submitBtn = this.form.querySelector('.submit-application-btn');
    const originalText = submitBtn.textContent;

    // Show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
      const formData = new FormData(this.form);

      // Get country code from selected option
      const selectedCountryOption = document.querySelector(
        '#applicationCountryDropdown .country-option.selected',
      );
      const countryCode = selectedCountryOption?.getAttribute('data-value') || '+65';

      formData.append('action', 'submit_course_application');
      formData.append('nonce', window.application_popup_params?.nonce || '');
      formData.append('country_code', countryCode);

      const response = await fetch(
        window.application_popup_params?.ajax_url || '/wp-admin/admin-ajax.php',
        {
          method: 'POST',
          body: formData,
        },
      );

      const result = await response.json();

      if (result.success) {
        this.closePopup();
        setTimeout(() => {
          this.openThankYouPopup();
        }, 400);
      } else {
        this.showGlobalError(result.data?.message || 'Something went wrong. Please try again.');
      }
    } catch (error) {
      this.showGlobalError('Network error. Please check your connection and try again.');
    } finally {
      // Reset button state
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const popup = new ApplicationPopup();
});
