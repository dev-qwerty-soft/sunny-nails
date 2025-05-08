import "./scss/main.min.scss";
import "./js/index.js";
import "swiper/css";
import "swiper/css/pagination";
import Swiper from "swiper";
import "./js/map.js";
import { Navigation, Pagination } from "swiper/modules";

const heroSwiper = new Swiper(".hero-swiper", {
  modules: [Navigation, Pagination],
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
});

const btn = document.getElementById("burger");

btn?.addEventListener("click", () => {
  document.querySelector(".burger-menu")?.classList.toggle("active");
  btn.classList.toggle("active");
});

const filters = document.querySelectorAll(".gallery-section__filters .filter");
const filterSection = document.querySelector(".gallery-section");
const isFull = filterSection.classList.contains("full");

const filterFn = (filter) => {
  const slug = filter.getAttribute("data-slug");
  filters.forEach((f) => f.classList.remove("active"));
  filter.classList.add("active");
  
  const images = [...document.querySelectorAll(".gallery-section__images .image")];

  const filteredImages = images.filter((image) => {
    return slug === "all" || image.getAttribute("data-slug") === slug;
  });
  
  const array = isFull ? filteredImages : filteredImages.slice(0, 5);

  images.forEach((image) => image.classList.remove("active"));
  array.forEach((image) => image.classList.add("active"));
}

filterFn(filters[0]);

filters.forEach((filter) => {
  filter.addEventListener("click", () => {
    filterFn(filter);
  });
});