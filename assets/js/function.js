export const toggle = (el, cl = 'active') => {
  if (!el) return;
  if (Array.isArray(el)) {
    el.forEach((item) => toggle(item, cl));
  } else {
    el.classList.toggle(cl);
  }
};

export const add = (el, cl = 'active') => {
  if (!el) return;
  if (Array.isArray(el)) {
    el.forEach((item) => add(item, cl));
  } else {
    el.classList.add(cl);
  }
};

export const remove = (el, cl = 'active') => {
  if (!el) return;
  if (Array.isArray(el)) {
    el.forEach((item) => remove(item, cl));
  } else {
    el.classList.remove(cl);
  }
};

export const has = (el, cl = '.active') => {
  return Boolean(el.closest(cl) && el.closest(cl).matches(cl));
};

export const random = (min, max) => {
  return Math.round(Math.random() * (max - min + 1) + min);
};

export function g(
  element,
  cont = document,
  flag = false
) {
  const elements = Array.from(cont.querySelectorAll(element));
  if (!elements.length || !cont) return;

  if (elements.length === 1) {
    return flag ? elements : elements[0];
  } else {
    return elements;
  }
}

export const max = (arr) =>
  arr.reduce((acc, num) => (acc > num ? acc : num));

export const upper = (text) => text[0].toUpperCase() + text.slice(1);

export const child = (str) => {
  if (!str) return;
  if (typeof str === 'string') {
    return Array.from(g(str)?.children);
  } else {
    return Array.from(str.children);
  }
};

export const scroll = () => {
  const scrollHeight = document.body.scrollHeight - window.innerHeight;
  const height = window.pageYOffset;
  const progress = Math.round((height / scrollHeight) * 100);
  return progress;
};

export const isSize = (num) => window.innerWidth <= num;

export const round = (num, del) => Math.round(num / del) * del;

export const getUrl = (url) => {
  const regExp =
    /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\v=)([^#\\?]*).*/;
  const match = url.match(regExp);
  if (match && match[2].length === 11) {
    return match[2];
  }
};

export function duration(duration) {
  const match = duration.match(/PT(\d+H)?(\d+M)?(\d+S)?/);
  if (!match) return '';
  let hours = '',
    minutes = '',
    seconds = '';
  if (match[1]) {
    hours = match[1].replace('H', '').padStart(2, '0');
  }
  if (match[2]) {
    minutes = match[2].replace('M', '').padStart(2, '0');
  }
  if (match[3]) {
    seconds = match[3].replace('S', '').padStart(2, '0');
  }
  return `${hours ? hours + ':' : ''}${minutes !== '' ? minutes : '00'}:${
    seconds !== '' ? seconds : '00'
  }`;
}

export function splitArray(arr) {
  const mid = Math.ceil(arr.length / 2);
  const firstHalf = arr.slice(0, mid);
  const secondHalf = arr.slice(mid);
  return [firstHalf, secondHalf];
}

export function extractURL(cssURL) {
  const urlPattern = /url\(["']?([^"']+)["']?\)/;
  const match = cssURL.match(urlPattern);
  return match ? match[1] : '';
}

export function respond(breakpoint) {
  const breakpoints = {
    xs: 480,
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    '2xl': 1536,
  };

  const width = window.innerWidth;

  if (breakpoint === '2xl') {
    return width >= breakpoints['2xl'];
  }

  return width <= breakpoints[breakpoint];
}


export function formatNumber(number) {
  return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
