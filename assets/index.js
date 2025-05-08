import "./scss/main.min.scss";
import "./js/index.js";
import "./js/booking.js";
import "swiper/css";
import "swiper/css/pagination";
import Swiper from "swiper";
import "./js/map.js";
import { Navigation, Pagination } from "swiper/modules";

import { has, g, add, remove, toggle, respond } from "./js/function.js";

if (g(".hero-swiper")) {
  new Swiper(".hero-swiper", {
    modules: [Navigation, Pagination],
    slidesPerView: 1,
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

let gallerySwiper;

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

const filters = g(".gallery-section__filters .filter", document, true);
const images = g(".gallery-section__images .image", document, true);
const filterSection = g(".gallery-section");
const isFull = has(filterSection, ".full");
const modal = g(".gallery-modal");

const filterFn = (filter) => {
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

document.onclick = (e) => {
  if (has(e.target, ".gallery-section__filters .filter")) {
    filterFn(e.target);
  } else if (has(e.target, "#burger")) {
    const btn = g("#burger");
    const menu = g(".burger-menu");
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
