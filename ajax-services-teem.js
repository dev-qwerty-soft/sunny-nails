/**
 * Simple Direct Booking Fix
 * Selects service in popup and activates "Choose a master" button
 */
(function ($) {
  "use strict";

  // Debug function (включіть для відлагодження)
  function debug(message, ...args) {
    console.log(`[BookingFix] ${message}`, ...args);
  }

  // Обробник кнопки "Book this"
  $(document).on("click", ".service-card .book-btn, button.book-btn[data-popup-open='true']", function (e) {
    e.preventDefault();
    e.stopPropagation();

    // Отримуємо ID сервісу
    const $button = $(this);
    const $serviceCard = $button.closest(".service-card");
    const serviceId = $serviceCard.data("service-id") || $button.data("service-id");

    if (!serviceId) {
      debug("No service ID found");
      return;
    }

    debug("Service selected:", serviceId);

    // Отримуємо дані про сервіс
    const serviceTitle = $serviceCard.find(".service-title").clone().children().remove().end().text().trim();

    const servicePriceText = $serviceCard.find(".service-price").text().trim();
    const serviceDuration = $serviceCard.find(".service-duration").text().replace("Duration:", "").trim();
    const serviceWearTime = $serviceCard.find(".service-wear-time").text().replace("Wear time:", "").trim();

    // Парсимо ціну
    const priceMatch = servicePriceText.match(/(\d+(?:\.\d+)?)/);
    const servicePrice = priceMatch ? priceMatch[0] : "0";
    const currency = servicePriceText.replace(/[\d.,]/g, "").trim() || "SGD";

    // Зберігаємо ID в sessionStorage для безпеки
    sessionStorage.setItem("selected_service_id", serviceId);

    // Відкриваємо попап
    $(".booking-popup-overlay").addClass("active");

    // Чекаємо, щоб DOM повністю оновився
    setTimeout(function () {
      // Переходимо на крок сервісів
      $(".booking-step").removeClass("active");
      $(".booking-step[data-step='services']").addClass("active");

      // Знаходимо відповідний чекбокс і позначаємо його
      let $checkbox = $(`.service-checkbox[data-service-id="${serviceId}"]`);
      let serviceFound = false;

      if ($checkbox.length) {
        debug("Service checkbox found immediately");

        // Знаходимо категорію і активуємо її
        const $categoryServices = $checkbox.closest(".category-services");
        const categoryId = $categoryServices.data("category-id");

        // Активуємо відповідну вкладку категорії
        $(".category-tab").removeClass("active");
        $(`.category-tab[data-category-id="${categoryId}"]`).addClass("active");

        // Показуємо сервіси цієї категорії
        $(".category-services").hide();
        $categoryServices.show();

        // Встановлюємо чекбокс
        $checkbox.prop("checked", true);
        $checkbox.closest(".service-item").addClass("selected");

        // Тригеримо подію change
        $checkbox.trigger("change");
        serviceFound = true;
      } else {
        debug("Service checkbox not found initially, searching in all categories");

        // Пробуємо перебрати всі категорії, щоб знайти сервіс
        $(".category-tab").each(function () {
          const tabId = $(this).data("category-id");
          debug("Checking category", tabId);

          // Активуємо вкладку
          $(".category-tab").removeClass("active");
          $(this).addClass("active");

          // Показуємо сервіси цієї категорії
          $(".category-services").hide();
          $(`.category-services[data-category-id="${tabId}"]`).show();

          // Шукаємо наш сервіс
          $checkbox = $(`.category-services[data-category-id="${tabId}"] .service-checkbox[data-service-id="${serviceId}"]`);

          if ($checkbox.length) {
            debug("Service checkbox found in category", tabId);

            // Встановлюємо чекбокс
            $checkbox.prop("checked", true);
            $checkbox.closest(".service-item").addClass("selected");

            // Тригеримо подію change
            $checkbox.trigger("change");
            serviceFound = true;
            return false; // Виходимо з циклу
          }
        });
      }

      if (!serviceFound) {
        debug("Service not found in any category, trying to add manually");

        // Додаємо сервіс вручну, якщо його не знайдено в UI
        if (typeof window.addService === "function") {
          window.addService(serviceId, serviceTitle, servicePrice, currency, serviceDuration, serviceWearTime, false, serviceId, "");
        }
      }

      // Активуємо кнопку "Next" або "Choose a master"
      $(".booking-step[data-step='services'] .next-btn, .choose-a-master-btn").prop("disabled", false);

      // НОВЕ: Якщо є кнопка "Choose a master", імітуємо клік на ній
      // або на кнопці "Next" через визначений час
      setTimeout(function () {
        const $chooseBtn = $("button:contains('Choose a master')");
        if ($chooseBtn.length) {
          debug("Found 'Choose a master' button, clicking it");
          $chooseBtn.click();
        } else {
          // Якщо не знайшли специфічну кнопку, спробуємо стандартну Next
          debug("Clicking next button");
          $(".booking-step[data-step='services'] .next-btn").click();
        }
      }, 800); // Даємо трохи більше часу для впевненості
    }, 500);
  });

  // Розширюємо оригінальну функцію next button на кроці сервісів
  $(document).on("click", ".booking-step[data-step='services'] .next-btn", function () {
    debug("Services next button clicked");

    // Переконуємось, що функції для завантаження майстрів викликаються
    setTimeout(function () {
      if (typeof window.loadStaffForServices === "function") {
        debug("Calling loadStaffForServices manually after delay");
        window.loadStaffForServices();
      }
    }, 500);
  });

  // Додаткова підтримка для специфічної кнопки "Choose a master"
  $(document).on("click", "button:contains('Choose a master')", function () {
    debug("'Choose a master' button clicked");

    // Переконуємось, що сервіс вибраний
    if (window.bookingData && (!window.bookingData.services || window.bookingData.services.length === 0)) {
      debug("No services in bookingData when trying to choose master");

      // Спроба відновити з sessionStorage
      const serviceId = sessionStorage.getItem("selected_service_id");
      if (serviceId) {
        debug("Found service ID in sessionStorage:", serviceId);

        // Знайти чекбокс і перевірити його
        const $checkbox = $(`.service-checkbox[data-service-id="${serviceId}"]`);
        if ($checkbox.length) {
          $checkbox.prop("checked", true).trigger("change");
        }
      }
    }

    // Викликаємо стандартний перехід
    setTimeout(function () {
      if (typeof window.goToStep === "function") {
        window.goToStep("master");
      } else {
        // Альтернативний перехід
        $(".booking-step").removeClass("active");
        $(".booking-step[data-step='master']").addClass("active");
      }

      // Завантажуємо список майстрів
      if (typeof window.loadStaffForServices === "function") {
        debug("Calling loadStaffForServices");
        window.loadStaffForServices();
      }
    }, 300);
  });

  // Допоміжний код для підтримки вибору "Any master"
  $(document).on("click", ".staff-item.any-master", function () {
    debug("Any master selected");

    // Встановлюємо ID у bookingData
    if (window.bookingData) {
      window.bookingData.staffId = "any";
      window.bookingData.staffName = "Any master";
      window.bookingData.staffLevel = 1;
    }

    // Позначаємо у UI
    $(".staff-item").removeClass("selected");
    $(this).addClass("selected");
  });
})(jQuery);
