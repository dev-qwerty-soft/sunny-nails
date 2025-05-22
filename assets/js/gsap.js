import gsap from "gsap";
import ScrollTrigger from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);
import {g} from "./function.js";

g("section", document, true)?.forEach((element) => {
  const tl = gsap.timeline({
    defaults: { 
      ease: "power4.inOut",
      duration: 1.5 
    },
    scrollTrigger: {
      trigger: element,
      start: "top center",
      end: "bottom bottom",
      toggleActions: "play none none none",
      // markers: !false
    },
  });

  tl.from(element, {
    opacity: 0,
    y: 75,
  }, 0);
  
});
