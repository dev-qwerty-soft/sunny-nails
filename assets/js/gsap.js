import gsap from "gsap";
import ScrollTrigger from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);
import {g} from "./function.js";

let isEntered = false;

gsap.timeline({
  defaults: {
    ease: "sine.inOut",
    duration: 1
  },
  scrollTrigger: {
    trigger: "body",
    start: "top+=300 top",
    onEnter: () => {
      if (isEntered) return;
      isEntered = true;
      gsap.fromTo(".site-header", {
        yPercent: -100,
        duration: .5,
      }, {
        yPercent: 0,
        duration: .5,
        position: "fixed",
      });
    }
  },
});

g("section", document, true)?.forEach((element) => {
  const tl = gsap.timeline({
    defaults: { 
      ease: "sine.inOut",
      duration: 1 
    },
    scrollTrigger: {
      trigger: element,
      start: "top center+=25%",
      end: "bottom bottom",
      toggleActions: "play none none none",
    },
  });

  tl.to(element, {
    opacity: 1,
    y: 0,
  })
});
