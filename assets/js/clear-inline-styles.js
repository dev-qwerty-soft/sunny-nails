/**
 * Clear inline styles from GSAP animations for specific elements
 * And add smooth scroll functionality for anchor links
 */

document.addEventListener('DOMContentLoaded', function () {
  // Function to clear inline styles from more-below button
  function clearMoreBelowInlineStyles() {
    const moreButton = document.querySelector('.more-below');
    if (moreButton) {
      // Remove inline styles that GSAP might have added
      moreButton.style.removeProperty('transform');
      moreButton.style.removeProperty('opacity');
      moreButton.style.removeProperty('translate');
      moreButton.style.removeProperty('rotate');
      moreButton.style.removeProperty('scale');

      console.log('Cleared inline styles from .more-below button');
    }
  }

  // Add smooth scroll functionality to more-below button
  const moreButton = document.querySelector('.more-below');
  if (moreButton) {
    moreButton.addEventListener('click', function (e) {
      e.preventDefault();

      // Get the target element from href
      const targetId = this.getAttribute('href').substring(1);
      const targetElement = document.getElementById(targetId);

      if (targetElement) {
        // Smooth scroll to target
        targetElement.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        });
      }
    });
  }

  // Clear styles initially
  clearMoreBelowInlineStyles();

  // Clear styles after GSAP animations might have run
  setTimeout(clearMoreBelowInlineStyles, 100);
  setTimeout(clearMoreBelowInlineStyles, 500);
  setTimeout(clearMoreBelowInlineStyles, 1000);

  // Observe for style changes and clear them
  if (moreButton && window.MutationObserver) {
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
          // Small delay to avoid infinite loop
          setTimeout(clearMoreBelowInlineStyles, 10);
        }
      });
    });

    observer.observe(moreButton, {
      attributes: true,
      attributeFilter: ['style'],
    });
  }
});
