import './scss/main.min.scss';
import './js/gsap.js';
import './js/services-validation.js';
import './js/services.js';
import './js/parnters.js';
import './js/apply-form.js';
import './js/application-popup.js';
import './js/first-visit-popup.js';
import './js/clear-inline-styles.js';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/scrollbar';
import Swiper from 'swiper';
import './js/map.js';
import { Navigation, Pagination, Scrollbar, FreeMode, Thumbs } from 'swiper/modules';
import { has, g, add, remove, toggle, updateDisplay, initMasterPopup } from './js/function.js';
import 'swiper/css/thumbs';

const header = g('.site-header');
const btn = g('#burger');
const menu = g('.burger-menu');

let gallerySwiper;
let filterFn;
let tabFn;
const modal = g('.gallery-modal');
const filterSection = g('.gallery-section');
const buttonsTabsWrapper = g('.sunny-friends-table-section__buttons');

setTimeout(() => {
  let lastScrollTop = 0;
  let scrollingDown = false;
  window.onscroll = () => {
    let currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
    scrollingDown = currentScrollTop > lastScrollTop;
    lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
    scrollingDown ? add(header, 'hidden') : remove(header, 'hidden');
    if (!scrollingDown) {
      header.style.setProperty('--border-color', '#D5CCB5');
    }
  };
}, 500);

if (g('.counter-section')) {
  const timeContainer = g('.counter-section .time');
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

if (g('.single-swiper-thumbs')) {
  const swiperThumbs = new Swiper('.single-swiper-thumbs', {
    modules: [Navigation],
    navigation: {
      nextEl: '.single-swiper-thumbs--arrows .next',
      prevEl: '.single-swiper-thumbs--arrows .prev',
    },
    spaceBetween: 6,
    slidesPerView: 'auto',
    watchOverflow: true,
    watchSlidesVisibility: true,
    watchSlidesProgress: true,
    direction: 'horizontal',
    breakpoints: {
      [768]: {
        direction: 'vertical',
        slidesPerView: 'auto',
        spaceBetween: 10,
      },
    },
  });

  new Swiper('.single-swiper', {
    modules: [Thumbs, Pagination],
    spaceBetween: 0,
    watchOverflow: true,
    watchSlidesVisibility: true,
    watchSlidesProgress: true,
    preventInteractionOnTransition: true,
    thumbs: {
      swiper: swiperThumbs,
    },
  });
}

if (g('.hero-swiper')) {
  new Swiper('.hero-swiper', {
    modules: [Navigation, Pagination],
    slidesPerView: 1,
    loop: true,
    pagination: {
      el: '.hero-swiper .swiper-pagination',
      clickable: true,
    },
    navigation: {
      nextEl: '.hero-swiper .swiper-button-next',
      prevEl: '.hero-swiper .swiper-button-prev',
    },
  });
}

if (g('.gallery-swiper')) {
  gallerySwiper = new Swiper('.gallery-swiper', {
    modules: [Navigation],
    slidesPerView: 1,
    navigation: {
      nextEl: '.gallery-swiper .swiper-button-next',
      prevEl: '.gallery-swiper .swiper-button-prev',
    },
  });
}

if (g('.reviews-swiper')) {
  new Swiper('.reviews-swiper', {
    modules: [Navigation],
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: {
      nextEl: '.reviews-section .swiper-button-next',
      prevEl: '.reviews-section .swiper-button-prev',
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

if (g('.team-swiper')) {
  const teamSwiper = g('.team-swiper');
  const slides = teamSwiper.querySelectorAll('.swiper-slide');
  const slidesCount = slides.length;

  // Determine if navigation should be enabled based on screen size and slide count
  const shouldShowNavigation = () => {
    const width = window.innerWidth;
    if (width >= 1024) {
      // Desktop: slidesPerView = 3, show navigation if 4 or more slides
      return slidesCount >= 4;
    } else if (width >= 768) {
      // Tablet: slidesPerView = 2, show navigation if 3 or more slides
      return slidesCount >= 3;
    } else {
      // Mobile: slidesPerView = 1, show navigation if 2 or more slides
      return slidesCount >= 2;
    }
  };

  const navigationEnabled = shouldShowNavigation();

  // Get navigation buttons
  const nextBtn = g('.team-section__wrapper .swiper-button-next');
  const prevBtn = g('.team-section__wrapper .swiper-button-prev');

  new Swiper('.team-swiper', {
    modules: navigationEnabled ? [Navigation] : [],
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: navigationEnabled
      ? {
          nextEl: '.team-section__wrapper .swiper-button-next',
          prevEl: '.team-section__wrapper .swiper-button-prev',
        }
      : false,
    nested: true,
    simulateTouch: true,
    allowTouchMove: true,
    breakpoints: {
      768: {
        slidesPerView: 2,
      },
      1024: {
        slidesPerView: 3,
      },
    },
  });

  // Hide or show navigation buttons based on condition
  if (nextBtn) {
    nextBtn.style.display = navigationEnabled ? 'block' : 'none';
  }
  if (prevBtn) {
    prevBtn.style.display = navigationEnabled ? 'block' : 'none';
  }
}

if (g('.mini-swiper')) {
  g('.mini-swiper', document, true).forEach((swiper) => {
    new Swiper(swiper, {
      modules: [FreeMode, Scrollbar],
      slidesPerView: 4.5,
      spaceBetween: 6,
      freeMode: true,
      scrollbar: {
        el: swiper.querySelector('.swiper-scrollbar'),
        draggable: true,
        dragSize: 32,
      },
      nested: true,
      touchStartPreventDefault: false,
      allowTouchMove: true,
      simulateTouch: true,
      grabCursor: true,
      cssMode: false,
      breakpoints: {
        1024: {
          slidesPerView: 6,
        },
      },
    });
  });
}

document.querySelectorAll('.mini-swiper').forEach((swiper) => {
  swiper.addEventListener(
    'touchstart',
    (e) => {
      e.stopPropagation();
    },
    { passive: true },
  );
  swiper.addEventListener('pointerdown', (e) => {
    e.stopPropagation();
  });
});
if (g('.winners-swiper')) {
  new Swiper('.winners-swiper', {
    modules: [Navigation],
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: {
      nextEl: '.winners-section__wrapper .swiper-button-next',
      prevEl: '.winners-section__wrapper .swiper-button-prev',
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

if (filterSection) {
  const filters = g('.gallery-section__filters .filter', document, true);
  const images = g('.gallery-section__images .image', document, true);
  const isFull = has(filterSection, '.full');

  filterFn = (filter) => {
    if (!filters) return;
    const slug = filter.getAttribute('data-slug');
    remove(filters);
    add(filter);

    const filteredImages = images?.filter((image) => {
      if (slug === 'all') return true;
      const slugs = image.getAttribute('data-slug')?.split(' ') || [];
      return slugs.includes(slug);
    });

    if (images) {
      remove(images);

      add(isFull ? filteredImages : filteredImages.slice(0, 8));
    }
  };

  filterFn(filters[0]);
}

if (buttonsTabsWrapper) {
  const buttonsTabs = g('.sunny-friends-table-section__button', document, true);
  const tabsWrapper = g('.sunny-friends-table-section__tabs');
  const tabs = g('.sunny-friends-table-section__tab', document, true);
  tabsWrapper.style.height = `${tabsWrapper.offsetHeight}px`;

  tabFn = (target) => {
    const btn = target.closest('.sunny-friends-table-section__button');
    const index = Number(btn.getAttribute('data-index'));
    remove([...buttonsTabs, ...tabs]);
    add([btn, tabs[index]]);
    const offsetLeft = btn.offsetLeft;
    buttonsTabsWrapper.style.setProperty('--offset-left', `${offsetLeft}px`);
  };

  tabFn(buttonsTabs[0]);
}

document.onclick = (e) => {
  if (has(e.target, '.gallery-section__filters .filter')) {
    filterFn?.(e.target);
  } else if (has(e.target, '#burger')) {
    window.scrollTo(0, 0);
    toggle([btn, menu, header]);
  } else if (has(e.target, '.open-popup-details')) {
    const btn = e.target.closest('.open-popup-details');
    const card = btn.closest('.course-card');
    const id = card.getAttribute('data-id');
    const popup = g(`.popup-details[data-id="${id}"]`);
    add(popup);
  } else if (has(e.target, '.popup-details__close')) {
    const popup = e.target.closest('.popup-details');
    remove(popup);
  } else if (has(e.target, '.gallery-section__images .image')) {
    const image = e.target.closest('.image');
    const index = Number(image.getAttribute('data-index'));
    gallerySwiper?.slideTo(index);
    add(modal);
  } else if (has(e.target, '.gallery-modal .cross')) {
    remove(modal);
    gallerySwiper?.slideTo(0);
  } else if (has(e.target, '[data-popup]')) {
    e.preventDefault();
    const popup = e.target.closest('[data-popup]');
    const id = popup.getAttribute('data-popup');
    add(g(id));
  } else if (has(e.target, '.popup-join .cross')) {
    remove(g('.popup-join'));
  } else if (has(e.target, '.sunny-friends-table-section__button')) {
    tabFn?.(e.target);
  } else {
    remove(g('.popup-details'));
  }
};

initMasterPopup();

document.addEventListener('DOMContentLoaded', () => {
  initMasterPopup();
});
