document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('partner-apply-form');
  if (!form) return;

  const fileInput = document.getElementById('partner_photo');
  const fileUploadArea = document.querySelector('.file-upload-area');
  const submitButton = form.querySelector('.btn-continue');
  const spinner = submitButton ? submitButton.querySelector('.spinner') : null;

  initFloatingLabels();

  initFileUpload();

  initCustomSelects();

  if (form) {
    form.addEventListener('submit', handleFormSubmit);
  }

  function initFloatingLabels() {
    if (!form) return;

    const inputs = form.querySelectorAll('.form-input, .form-textarea');

    inputs.forEach((input) => {
      const label = input.nextElementSibling;

      if (label && label.classList.contains('form-label')) {
        checkFloatState(input, label);

        input.addEventListener('focus', () => {
          label.classList.add('float-active');
        });

        input.addEventListener('blur', () => {
          checkFloatState(input, label);

          validateField(input);
        });

        input.addEventListener('input', () => {
          checkFloatState(input, label);

          clearFieldError(input.id);
        });
      }
    });
  }

  function checkFloatState(input, label) {
    if (input.value.trim() !== '') {
      label.classList.add('float-active');
    } else {
      label.classList.remove('float-active');
    }
  }

  function initFileUpload() {
    if (!fileUploadArea || !fileInput) return;

    fileInput.addEventListener('change', function (e) {
      const file = e.target.files[0];
      if (file) {
        displaySelectedFile(file);
        clearFieldError('partner_photo');
      }
    });

    fileUploadArea.addEventListener('click', (e) => {
      const filePreview = fileUploadArea.querySelector('.file-preview');

      if (
        e.target.closest('.file-action-btn') ||
        e.target.closest('.file-preview-actions') ||
        (filePreview && filePreview.classList.contains('show'))
      ) {
        return;
      }

      fileInput.click();
    });

    fileUploadArea.addEventListener('dragover', handleDragOver);
    fileUploadArea.addEventListener('dragleave', handleDragLeave);
    fileUploadArea.addEventListener('drop', handleFileDrop);
  }

  function handleDragOver(e) {
    e.preventDefault();
    fileUploadArea.classList.add('dragover');
  }

  function handleDragLeave(e) {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');
  }

  function handleFileDrop(e) {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      const file = files[0];

      if (file.type.startsWith('image/')) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        displaySelectedFile(file);
        clearFieldError('partner_photo');
      } else {
        showFieldError('partner_photo', 'Please select an image file');
      }
    }
  }

  function displaySelectedFile(file) {
    const filePreview = fileUploadArea.querySelector('.file-preview');
    const previewImage = fileUploadArea.querySelector('.file-preview-image');
    const previewName = fileUploadArea.querySelector('.file-preview-name');
    const uploadIcon = fileUploadArea.querySelector('.upload-icon');
    const fileUploadText = fileUploadArea.querySelector('.file-upload-text');

    if (previewImage && previewName && filePreview) {
      const imageUrl = URL.createObjectURL(file);

      previewImage.src = imageUrl;
      previewName.textContent = file.name;

      filePreview.classList.add('show');
      if (uploadIcon) uploadIcon.style.display = 'none';
      if (fileUploadText) fileUploadText.style.display = 'none';

      const deleteBtn = fileUploadArea.querySelector('.delete-btn');
      const changeBtn = fileUploadArea.querySelector('.change-btn');

      if (deleteBtn) {
        deleteBtn.replaceWith(deleteBtn.cloneNode(true));
        const newDeleteBtn = fileUploadArea.querySelector('.delete-btn');
        newDeleteBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          resetFileUpload();
        });
      }

      if (changeBtn) {
        changeBtn.replaceWith(changeBtn.cloneNode(true));
        const newChangeBtn = fileUploadArea.querySelector('.change-btn');
        newChangeBtn.addEventListener('click', (e) => {
          e.stopPropagation();

          const currentFileInput = document.getElementById('partner_photo');
          if (currentFileInput) {
            currentFileInput.value = '';

            setTimeout(() => {
              currentFileInput.click();
            }, 10);
          }
        });
      }
    }
  }

  let isSubmitting = false;
  let hasSubmittedSuccessfully = false;
  let lastSubmissionTime = 0;

  async function handleFormSubmit(e) {
    e.preventDefault();

    // Prevent rapid consecutive submissions
    const now = Date.now();
    if (now - lastSubmissionTime < 3000) {
      showErrorMessage('Please wait before submitting again.');
      return;
    }

    if (isSubmitting || hasSubmittedSuccessfully) {
      return;
    }

    lastSubmissionTime = now;
    isSubmitting = true;

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Submitting...';
      submitButton.style.opacity = '0.6';
      submitButton.style.cursor = 'not-allowed';
    }

    clearAllErrors();
    clearMessages();

    if (!validateForm()) {
      isSubmitting = false;

      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Submit Application';
        submitButton.style.opacity = '1';
        submitButton.style.cursor = 'pointer';
      }
      return;
    }

    setLoadingState(true);

    try {
      const formData = new FormData(form);
      formData.append('action', 'submit_partner_application');
      formData.append('nonce', apply_ajax.nonce);

      const response = await fetch(apply_ajax.ajax_url, {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        hasSubmittedSuccessfully = true;
        showSuccessMessage(data.data.message || 'Application submitted successfully!');
        form.reset();
        resetFileUpload();
        resetFloatingLabels();

        if (submitButton) {
          submitButton.disabled = true;
          submitButton.textContent = 'Application Submitted';
          submitButton.style.opacity = '0.6';
          submitButton.style.cursor = 'not-allowed';
        }
      } else {
        showErrorMessage(data.data || 'An error occurred. Please try again.');
      }
    } catch (error) {
      showErrorMessage('Network error. Please check your connection and try again.');
    } finally {
      setLoadingState(false);

      if (!hasSubmittedSuccessfully) {
        isSubmitting = false;

        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = 'Submit Application';
          submitButton.style.opacity = '1';
          submitButton.style.cursor = 'pointer';
        }
      }
    }
  }

  function validateForm() {
    let isValid = true;

    const requiredFields = [
      { id: 'partner_title', name: 'Partner Title' },
      { id: 'partner_description', name: 'Partner description' },
      { id: 'benefit_title', name: 'Benefit title' },
      { id: 'benefit_description', name: 'Benefit description' },
      { id: 'benefit_icon_type', name: 'Benefit icon type' },
    ];

    requiredFields.forEach((fieldData) => {
      const field = document.getElementById(fieldData.id);

      if (!field) return;

      if (fieldData.id === 'partner_photo') {
        if (!field.files || field.files.length === 0) {
          showFieldError(fieldData.id, 'Please select a photo');
          isValid = false;
        }
      } else {
        if (!field.value.trim()) {
          showFieldError(fieldData.id, `${fieldData.name} is required`);
          isValid = false;
        }
      }
    });

    if (fileInput && (!fileInput.files || fileInput.files.length === 0)) {
      showFieldError('partner_photo', 'Please select a photo');
      isValid = false;
    }

    const linkField = document.getElementById('link_card');
    if (linkField && linkField.value.trim()) {
      if (!isValidUrl(linkField.value.trim())) {
        showFieldError('link_card', 'Please enter a valid URL');
        isValid = false;
      }
    }

    return isValid;
  }

  function validateField(field) {
    if (!field) return true;

    const fieldId = field.id;
    const fieldValue = field.value.trim();
    let isValid = true;

    const requiredFields = [
      'partner_title',
      'partner_description',
      'benefit_title',
      'benefit_description',
      'benefit_icon_type',
    ];

    if (requiredFields.includes(fieldId)) {
      if (!fieldValue) {
        const fieldNames = {
          partner_title: 'Partner Title',
          partner_description: 'Partner description',
          benefit_title: 'Benefit title',
          benefit_description: 'Benefit description',
          benefit_icon_type: 'Benefit icon type',
        };
        showFieldError(fieldId, `${fieldNames[fieldId]} is required`);
        isValid = false;
      }
    }

    if (fieldId === 'link_card' && fieldValue) {
      if (!isValidUrl(fieldValue)) {
        showFieldError(fieldId, 'Please enter a valid URL');
        isValid = false;
      }
    }

    return isValid;
  }

  function isValidUrl(string) {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  }

  function showFieldError(fieldName, message) {
    const field = document.getElementById(fieldName);
    if (field) {
      field.classList.add('error');

      const errorElement =
        document.getElementById(fieldName + '_error') ||
        field.parentNode.querySelector('.field-error');

      if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
      }
    }
  }

  function clearFieldError(fieldName) {
    const field = document.getElementById(fieldName);
    if (field) {
      field.classList.remove('error');

      const errorElement =
        document.getElementById(fieldName + '_error') ||
        field.parentNode.querySelector('.field-error');

      if (errorElement) {
        errorElement.textContent = '';
        errorElement.classList.remove('show');
      }
    }
  }

  function clearAllErrors() {
    if (!form) return;

    const errorElements = form.querySelectorAll('.field-error');
    errorElements.forEach((element) => {
      element.textContent = '';
      element.classList.remove('show');
    });

    const fields = form.querySelectorAll('.form-input, .form-textarea');
    fields.forEach((field) => {
      field.classList.remove('error');
    });

    const messageContainer = form.querySelector('.apply-messages');
    if (messageContainer) {
      messageContainer.innerHTML = '';
    }
  }

  function showSuccessMessage(message) {
    const messageContainer = document.getElementById('form-messages');
    if (messageContainer) {
      messageContainer.innerHTML = `
        <div class="message success-message">
          <div class="message-icon">
            <svg xmlns="http:
              <path d="M8 1.33331C11.682 1.33331 14.6667 4.31798 14.6667 7.99998C14.6667 11.682 11.682 14.6666 8 14.6666C4.318 14.6666 1.33334 11.682 1.33334 7.99998C1.33334 4.31798 4.318 1.33331 8 1.33331ZM11.0587 5.724L7.33334 9.44998L4.94134 7.058L4.058 7.94131L7.33334 11.216L11.942 6.608L11.0587 5.724Z" fill="#00C853" />
            </svg>
          </div>
          <div class="message-text">${message}</div>
        </div>
      `;
      messageContainer.style.display = 'block';

      setTimeout(() => {
        messageContainer.style.transition = 'opacity 0.5s ease-out';
        messageContainer.style.opacity = '0';
        setTimeout(() => {
          messageContainer.style.display = 'none';
          messageContainer.style.opacity = '1';
          messageContainer.style.transition = '';
        }, 500);
      }, 5000);

      messageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function showErrorMessage(message) {
    const messageContainer = document.getElementById('form-messages');
    if (messageContainer) {
      messageContainer.innerHTML = `
        <div class="message error-message">
          <div class="message-icon">
            <svg xmlns="http:
              <path d="M7.99998 1.33331C11.682 1.33331 14.6666 4.31865 14.6666 7.99998C14.6666 11.6813 11.682 14.6666 7.99998 14.6666C4.31798 14.6666 1.33331 11.6813 1.33331 7.99998C1.33331 4.31865 4.31798 1.33331 7.99998 1.33331ZM7.99998 2.44465C4.93665 2.44465 2.44465 4.93665 2.44465 7.99998C2.44465 11.0633 4.93665 13.5553 7.99998 13.5553C11.0633 13.5553 13.5553 11.0633 13.5553 7.99998C13.5553 4.93665 11.0633 2.44465 7.99998 2.44465ZM7.99931 9.66798C8.17595 9.66798 8.34535 9.73815 8.47025 9.86305C8.59515 9.98795 8.66531 10.1573 8.66531 10.334C8.66531 10.5106 8.59515 10.68 8.47025 10.8049C8.34535 10.9298 8.17595 11 7.99931 11C7.82268 11 7.65328 10.9298 7.52838 10.8049C7.40348 10.68 7.33331 10.5106 7.33331 10.334C7.33331 10.1573 7.40348 9.98795 7.52838 9.86305C7.65328 9.73815 7.82268 9.66798 7.99931 9.66798ZM7.99598 4.66665C8.11692 4.66649 8.23382 4.71017 8.32502 4.78961C8.41621 4.86904 8.47553 4.97883 8.49198 5.09865L8.49665 5.16598L8.49931 8.16731C8.49944 8.29405 8.45144 8.41611 8.36501 8.50881C8.27857 8.60151 8.16017 8.65792 8.03373 8.66664C7.90729 8.67537 7.78225 8.63575 7.68391 8.5558C7.58557 8.47585 7.52125 8.36154 7.50398 8.23598L7.49931 8.16798L7.49665 5.16731C7.49656 5.1016 7.50943 5.03651 7.53451 4.97577C7.5596 4.91503 7.59642 4.85983 7.64286 4.81333C7.6893 4.76683 7.74444 4.72994 7.80515 4.70477C7.86586 4.6796 7.93026 4.66665 7.99598 4.66665Z" fill="#dc3232" />
            </svg>
          </div>
          <div class="message-text">${message}</div>
        </div>
      `;
      messageContainer.style.display = 'block';

      setTimeout(() => {
        messageContainer.style.transition = 'opacity 0.5s ease-out';
        messageContainer.style.opacity = '0';
        setTimeout(() => {
          messageContainer.style.display = 'none';
          messageContainer.style.opacity = '1';
          messageContainer.style.transition = '';
        }, 500);
      }, 5000);

      messageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function clearMessages() {
    const messageContainer = document.getElementById('form-messages');
    if (messageContainer) {
      messageContainer.style.display = 'none';
      messageContainer.innerHTML = '';
    }
  }

  function setLoadingState(loading) {
    if (!submitButton) return;

    if (loading) {
      submitButton.disabled = true;
      const btnSpinner = submitButton.querySelector('.btn-spinner');
      if (btnSpinner) btnSpinner.style.display = 'inline-block';
      const btnText = submitButton.querySelector('.btn-text');
      if (btnText) btnText.textContent = 'Submitting...';
    } else {
      submitButton.disabled = false;
      const btnSpinner = submitButton.querySelector('.btn-spinner');
      if (btnSpinner) btnSpinner.style.display = 'none';
      const btnText = submitButton.querySelector('.btn-text');
      if (btnText) btnText.textContent = 'Become a Partner';
    }
  }

  function resetFileUpload() {
    if (fileUploadArea) {
      const filePreview = fileUploadArea.querySelector('.file-preview');
      const uploadIcon = fileUploadArea.querySelector('.upload-icon');
      const fileUploadText = fileUploadArea.querySelector('.file-upload-text');
      const previewImage = fileUploadArea.querySelector('.file-preview-image');

      const currentFileInput = document.getElementById('partner_photo');

      if (currentFileInput) {
        currentFileInput.value = '';
      }
      if (fileInput) {
        fileInput.value = '';
      }

      if (filePreview) filePreview.classList.remove('show');
      if (uploadIcon) uploadIcon.style.display = 'block';
      if (fileUploadText) fileUploadText.style.display = 'block';

      if (previewImage && previewImage.src) {
        if (previewImage.src.startsWith('blob:')) {
          URL.revokeObjectURL(previewImage.src);
        }
        previewImage.src = '';
        previewImage.alt = '';
      }

      clearFieldError('partner_photo');
    }
  }

  function resetFloatingLabels() {
    const labels = form.querySelectorAll('.form-label');
    labels.forEach((label) => {
      label.classList.remove('float-active');
    });
  }

  function initCustomSelects() {
    const customSelects = document.querySelectorAll('.custom-select');

    customSelects.forEach((select) => {
      const trigger = select.querySelector('.custom-select-trigger');
      const options = select.querySelectorAll('.custom-select-option');
      const hiddenInput = select.parentElement.querySelector('input[type="hidden"]');
      const textSpan = trigger.querySelector('.custom-select-text');

      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        closeAllSelects();
        select.classList.toggle('open');
      });

      options.forEach((option) => {
        option.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          options.forEach((opt) => opt.classList.remove('selected'));

          option.classList.add('selected');

          const content = option.querySelector('.custom-select-option-content');
          if (content && textSpan) {
            const contentClone = content.cloneNode(true);

            textSpan.innerHTML = '';
            textSpan.appendChild(contentClone);
            textSpan.classList.remove('placeholder');

            trigger.classList.add('selected');
          }

          if (hiddenInput) {
            hiddenInput.value = option.dataset.value;

            clearFieldError('benefit_icon_type');
          }

          select.classList.remove('open');
          select.classList.add('selected');
        });
      });
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.custom-select')) {
        closeAllSelects();
      }
    });

    function closeAllSelects() {
      customSelects.forEach((select) => {
        select.classList.remove('open');
      });
    }
  }
});
