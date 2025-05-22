import gsap from "gsap";
import ScrollTrigger from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);
import {g} from "./function.js";

g("section", document, true)?.forEach((element) => {
  const tl = gsap.timeline({
    defaults: { 
      ease: "power4.inOut",
      duration: .5 
    },
    scrollTrigger: {
      trigger: element,
      start: "top center",
      end: "bottom center",
    },
  });

  tl.from(element, {
    opacity: 0,
    yPercent: 10,
  }, 0);
});
