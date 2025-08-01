import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);
import { g } from './function.js';

window.scrollTo({ top: 0, behavior: 'auto' });

g('section', document, true)?.forEach((element) => {
  window.scrollTo({ top: 0, behavior: 'auto' });

  const tl = gsap.timeline({
    defaults: {
      ease: 'sine.inOut',
      duration: 1,
    },
    scrollTrigger: {
      trigger: element,
      start: 'top center+=25%',
      end: 'bottom bottom',
      toggleActions: 'play none none none',
    },
  });

  tl.fromTo(
    [...element.children],
    {
      opacity: 0,
      y: 75,
    },
    {
      opacity: 1,
      y: 0,
    },
  );
});
