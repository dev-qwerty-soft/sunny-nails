
import "./scss/main.min.scss";
import "./js/index.js";
import "swiper/css"
import "swiper/css/pagination"
import Swiper from 'swiper';
import { Navigation, Pagination } from "swiper/modules";

const heroSwiper = new Swiper('.hero-swiper', {
  modules: [Navigation, Pagination],
  pagination: {
    el: '.swiper-pagination',
    clickable: true,
  },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },
})
