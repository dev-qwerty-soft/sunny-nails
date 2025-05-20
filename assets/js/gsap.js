import gsap from "gsap";
import ScrollTrigger from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);
import {g} from "./function.js";

g("section", document, true)?.forEach((element) => {
  const firstLevelChildren = Array.from(element.children);
  const secondLevelChildren = firstLevelChildren.flatMap(child => Array.from(child.children));
  const thirdLevelChildren = secondLevelChildren.flatMap(child => Array.from(child.children));

  const tl = gsap.timeline({
    defaults: { 
      ease: "power1.inOut",
      duration: 1 
    },
    scrollTrigger: {
      trigger: element,
      start: "top center",
      end: "bottom center",
    },
  });

  tl.from([...firstLevelChildren, ...secondLevelChildren, ...thirdLevelChildren], {
    opacity: 0,
    y: 10,
    stagger: 0.1
  }, 0);
});
