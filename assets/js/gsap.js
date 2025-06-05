import gsap from "gsap";
import ScrollTrigger from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);
import {g} from "./function.js";

window.onload = () => {
  window.scrollTo({ top: 0, behavior: 'auto' });
}

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

  tl.to(element.querySelector(".container"), {
    opacity: 1,
    y: 0,
  })
});
