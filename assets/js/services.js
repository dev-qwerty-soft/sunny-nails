document.addEventListener("DOMContentLoaded", function () {
  const countrySelectButton = document.getElementById("countrySelectButton");
  const countryDropdown = document.getElementById("countryDropdown");
  const selectedCountrySpan = countrySelectButton?.querySelector(".selected-country");
  const phoneInput = document.getElementById("client-phone");
  const countryOptions = countryDropdown?.querySelectorAll(".country-option");

  if (!countrySelectButton || !countryDropdown || !phoneInput) return;

  let isOpen = false;
  let selectedCountryCode = null;

  if (selectedCountrySpan) {
    selectedCountrySpan.textContent = "Choose country";
  }

  countryOptions.forEach((opt) => opt.classList.remove("selected"));

  countrySelectButton.addEventListener("click", function () {
    toggleDropdown();
  });

  document.addEventListener("click", function (e) {
    if (!countrySelectButton.contains(e.target) && !countryDropdown.contains(e.target)) {
      closeDropdown();
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && isOpen) {
      closeDropdown();
    }
  });

  countryOptions.forEach((option) => {
    option.addEventListener("click", function () {
      selectCountry(this);
    });
  });

  function toggleDropdown() {
    if (isOpen) {
      closeDropdown();
    } else {
      openDropdown();
    }
  }

  function openDropdown() {
    isOpen = true;
    countrySelectButton.classList.add("active");
    countryDropdown.classList.add("active");

    const selectedOption = countryDropdown.querySelector(".country-option.selected");
    if (selectedOption) {
      selectedOption.scrollIntoView({ block: "nearest" });
    }
  }

  function closeDropdown() {
    isOpen = false;
    countrySelectButton.classList.remove("active");
    countryDropdown.classList.remove("active");
  }

  function selectCountry(option) {
    countryOptions.forEach((opt) => opt.classList.remove("selected"));
    option.classList.add("selected");

    const countryText = option.textContent;
    selectedCountrySpan.textContent = countryText;

    selectedCountryCode = option.dataset.value;

    if (typeof bookingData !== "undefined") {
      bookingData.contact = bookingData.contact || {};
      bookingData.contact.countryCode = selectedCountryCode;

      const phoneNumber = phoneInput.value.trim();
      if (phoneNumber) {
        bookingData.contact.fullPhone = selectedCountryCode + phoneNumber;
      }
    }

    closeDropdown();
    phoneInput.focus();
  }

  window.getSelectedCountryCode = function () {
    return selectedCountryCode;
  };

  window.getFullPhoneNumber = function () {
    const phoneNumber = phoneInput.value.trim();
    return phoneNumber && selectedCountryCode ? selectedCountryCode + phoneNumber : "";
  };
});

jQuery(document).ready(function ($) {
  $(document).on("click", ".review__expand-btn", function () {
    const $btn = $(this);
    const $container = $btn.closest(".review__text-container");
    const $shortText = $container.find(".review__text--short");
    const $fullText = $container.find(".review__text--full");

    if ($btn.hasClass("expanded")) {
      $fullText.slideUp(300);
      $shortText.slideDown(300);
      $btn.removeClass("expanded").text("Read more");
    } else {
      $shortText.slideUp(300);
      $fullText.slideDown(300);
      $btn.addClass("expanded").text("Read less");
    }
  });
});
