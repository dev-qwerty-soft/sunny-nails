import "./scss/main.min.scss";
import "./js/index.js";
import "./js/services-page-booking.js";
import "./js/services-validation.js";
import "./js/services.js";
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/scrollbar";
import Swiper from "swiper";
import "./js/map.js";
import { Navigation, Pagination, Scrollbar, FreeMode } from "swiper/modules";
import { has, g, add, remove, toggle, respond, updateDisplay } from "./js/function.js";

let gallerySwiper;
let filterFn;
const modal = g(".gallery-modal");
const filterSection = g(".gallery-section");

if (g(".counter-section")) {
  const timeContainer = g(".counter-section .time");
  let timeLeftMs = parseInt(timeContainer.dataset.timeMs);
  if (timeLeftMs) {
    function tick() {
      timeLeftMs -= 1000;
      if (timeLeftMs < 0) {
        timeLeftMs = 0;
        clearInterval(timer);
      }
      updateDisplay(timeLeftMs);
    }

    updateDisplay(timeLeftMs);
    const timer = setInterval(tick, 1000);
  }
}

if (g(".hero-swiper")) {
  new Swiper(".hero-swiper", {
    modules: [Navigation, Pagination],
    slidesPerView: 1,
    loop: true,
    pagination: {
      el: ".hero-swiper .swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".hero-swiper .swiper-button-next",
      prevEl: ".hero-swiper .swiper-button-prev",
    },
  });
}

if (g(".gallery-swiper")) {
  gallerySwiper = new Swiper(".gallery-swiper", {
    modules: [Navigation],
    slidesPerView: 1,
    navigation: {
      nextEl: ".gallery-swiper .swiper-button-next",
      prevEl: ".gallery-swiper .swiper-button-prev",
    },
  });
}

if (g(".reviews-swiper")) {
  new Swiper(".reviews-swiper", {
    modules: [Navigation],
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: {
      nextEl: ".reviews-section .swiper-button-next",
      prevEl: ".reviews-section .swiper-button-prev",
    },
    breakpoints: {
      768: {
        slidesPerView: 2,
      },
      1024: {
        slidesPerView: 3,
      },
    },
  });
}

if (g(".team-swiper")) {
  new Swiper(".team-swiper", {
    modules: [Navigation],
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: {
      nextEl: ".team-section__wrapper .swiper-button-next",
      prevEl: ".team-section__wrapper .swiper-button-prev",
    },
    breakpoints: {
      768: {
        slidesPerView: 2,
      },
      1024: {
        slidesPerView: 3,
      },
    },
  });
}

if (g(".winners-swiper")) {
  new Swiper(".winners-swiper", {
    modules: [Navigation],
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: {
      nextEl: ".winners-section__wrapper .swiper-button-next",
      prevEl: ".winners-section__wrapper .swiper-button-prev",
    },
    breakpoints: {
      768: {
        slidesPerView: 2,
      },
      1024: {
        slidesPerView: 3,
      },
    },
  });
}

if (g(".mini-swiper")) {
  g(".mini-swiper", document, true).forEach((swiper) => {
    new Swiper(swiper, {
      modules: [FreeMode, Scrollbar],
      slidesPerView: 4,
      spaceBetween: 6,
      freeMode: true,
      scrollbar: {
        el: swiper.querySelector(".swiper-scrollbar"),
        draggable: true,
        dragSize: 32,
      },
      breakpoints: {
        1024: {
          slidesPerView: 6,
        },
      },
    });
  });
}

if (filterSection) {
  const filters = g(".gallery-section__filters .filter", document, true);
  const images = g(".gallery-section__images .image", document, true);
  const isFull = has(filterSection, ".full");

  filterFn = (filter) => {
    if (!filters) return;
    const slug = filter.getAttribute("data-slug");
    remove(filters);
    add(filter);

    const filteredImages = images.filter((image) => {
      return slug === "all" || image.getAttribute("data-slug") === slug;
    });

    remove(images);
    add(isFull ? filteredImages : filteredImages.slice(0, respond("md") ? 6 : 5));
  };

  filterFn(filters[0]);
}

document.onclick = (e) => {
  if (has(e.target, ".gallery-section__filters .filter")) {
    filterFn?.(e.target);
  } else if (has(e.target, "#burger")) {
    const btn = g("#burger");
    const menu = g(".burger-menu");
    window.scrollTo(0, 0);
    toggle([btn, menu]);
  } else if (has(e.target, ".gallery-section__images .image")) {
    const image = e.target.closest(".image");
    const index = Number(image.getAttribute("data-index"));
    gallerySwiper?.slideTo(index);
    add(modal);
  } else if (has(e.target, ".gallery-modal .cross")) {
    remove(modal);
    gallerySwiper?.slideTo(0);
  }
};
