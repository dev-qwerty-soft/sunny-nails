document.addEventListener("DOMContentLoaded", function () {
  const countrySelectButton = document.getElementById("countrySelectButton");
  const countryDropdown = document.getElementById("countryDropdown");
  const selectedCountrySpan = countrySelectButton?.querySelector(".selected-country");
  const phoneInput = document.getElementById("client-phone");
  const countryOptions = countryDropdown?.querySelectorAll(".country-option");

  if (!countrySelectButton || !countryDropdown || !phoneInput) return;

  let isOpen = false;

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

    const countryCode = option.dataset.value;
    const currentPhoneValue = phoneInput.value;
    const phoneNumber = currentPhoneValue.replace(/^\+\d+\s*/, "").trim();
    phoneInput.value = countryCode + " " + phoneNumber;

    closeDropdown();
    phoneInput.focus();
  }

  phoneInput.addEventListener("focus", function () {
    const selectedOption = countryDropdown.querySelector(".country-option.selected");
    if (selectedOption && (!this.value || this.value.trim() === "")) {
      this.value = selectedOption.dataset.value + " ";
    }
  });

  phoneInput.addEventListener("input", function () {
    const selectedOption = countryDropdown.querySelector(".country-option.selected");
    if (selectedOption) {
      const countryCode = selectedOption.dataset.value;
      const currentValue = this.value;

      if (!currentValue.startsWith(countryCode)) {
        const phoneNumber = currentValue.replace(/^\+\d+\s*/, "").trim();
        this.value = countryCode + " " + phoneNumber;
      }
    }
  });
});
