import "./scss/main.min.scss";
import "./js/index.js";
import "./js/gsap.js";
import "./js/services-validation.js";
import "./js/services.js";
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/scrollbar";
import Swiper from "swiper";
import "./js/map.js";
import { Navigation, Pagination, Scrollbar, FreeMode } from "swiper/modules";
import { has, g, add, remove, toggle, respond, updateDisplay } from "./js/function.js";

const header = g(".site-header");
const footer = g(".footer");
const height = header.offsetHeight + footer.offsetHeight;
function resize() {
  document.body.style.setProperty("--vh-min", `${window.innerHeight - height}px`);
};
resize();
window.addEventListener("resize", resize);

let gallerySwiper;
let filterFn;
let tabFn;
const modal = g(".gallery-modal");
const filterSection = g(".gallery-section");
const buttonsTabsWrapper = g(".sunny-friends-table-section__buttons");

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
  const isMobile = respond("md");

  filterFn = (filter) => {
    if (!filters) return;
    const slug = filter.getAttribute("data-slug");
    remove(filters);
    add(filter);

    const filteredImages = images?.filter((image) => {
      if (slug === "all") return true;

      const slugs = image.getAttribute("data-slug")?.split(" ") || [];
      return slugs.includes(slug);
    });

    remove(images);
    remove(images, "big");

    const final = isFull ? filteredImages : filteredImages.slice(0, isMobile ? 6 : 5);

    if (!isMobile) {
      final.forEach((image, i) => {
        const nth = i + 1;
        if (nth % 10 === 3 || nth % 10 === 6) {
          add(image, "big");
        }
      });
    }

    add(final);
  };

  filterFn(filters[0]);
}

if (buttonsTabsWrapper) {
  const buttonsTabs = g(".sunny-friends-table-section__button", document, true);
  const tabsWrapper = g(".sunny-friends-table-section__tabs");
  const tabs = g(".sunny-friends-table-section__tab", document, true);
  tabsWrapper.style.height = `${tabsWrapper.offsetHeight}px`;

  tabFn = (target) => {
    const btn = target.closest(".sunny-friends-table-section__button");
    const index = Number(btn.getAttribute("data-index"));
    remove([...buttonsTabs, ...tabs]);
    add([btn, tabs[index]]);
    const offsetLeft = btn.offsetLeft;
    buttonsTabsWrapper.style.setProperty("--offset-left", `${offsetLeft}px`);
  };

  tabFn(buttonsTabs[0]);
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
  } else if (has(e.target, "[data-popup]")) {
    e.preventDefault();
    const popup = e.target.closest("[data-popup]");
    const id = popup.getAttribute("data-popup");
    add(g(id));
  } else if (has(e.target, ".popup-join .cross")) {
    remove(g(".popup-join"));
  } else if (has(e.target, ".sunny-friends-table-section__button")) {
    tabFn?.(e.target);
  }
};

