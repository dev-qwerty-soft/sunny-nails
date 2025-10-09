/**
 * First Visit Popup Handler
 * Manages first visit popup display and user journey selection
 */

class FirstVisitPopup {
  constructor() {
    this.popup = document.getElementById('first-visit-popup');
    this.closeButton = document.getElementById('firstVisitPopupClose');
    this.overlay = null;
    this.cookieName = 'first_visit_popup_shown';
    this.cookieExpiry = 30; // days

    this.init();
  }

  init() {
    console.log('FirstVisitPopup: Initializing...');
    console.log('FirstVisitPopup: Popup element found:', !!this.popup);

    if (!this.popup) {
      console.log('FirstVisitPopup: No popup element found');
      return;
    }

    this.overlay = this.popup.querySelector('.first-visit-popup__overlay');
    this.bindEvents();
    this.checkAndShowPopup();
  }

  checkAndShowPopup() {
    const cookieValue = this.getCookie(this.cookieName);
    console.log('FirstVisitPopup: Cookie value:', cookieValue);

    // Check if this is the first visit
    if (!cookieValue) {
      console.log('FirstVisitPopup: First visit detected, showing popup...');
      // Show popup after a small delay to ensure page is loaded
      setTimeout(() => {
        this.showPopup();
      }, 1000);
    } else {
      console.log('FirstVisitPopup: Not first visit, popup hidden');
      // Make sure popup is hidden for returning visitors
      this.hidePopup();
    }
  }

  bindEvents() {
    // Close button
    if (this.closeButton) {
      this.closeButton.addEventListener('click', () => this.closePopup());
    }

    // Overlay click to close
    if (this.overlay) {
      this.overlay.addEventListener('click', (e) => {
        if (e.target === this.overlay) {
          this.closePopup();
        }
      });
    }

    // Journey option buttons
    const optionButtons = this.popup.querySelectorAll('.option-button');
    optionButtons.forEach((button) => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const option = button.dataset.option;
        const link = button.getAttribute('href');

        this.handleJourneySelection(option, link);
      });
    });

    // Escape key to close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isPopupVisible()) {
        this.closePopup();
      }
    });
  }

  showPopup() {
    if (!this.popup) return;

    console.log('FirstVisitPopup: Showing popup...');

    // Show the popup element
    this.popup.style.display = 'block';

    // Add visible class for animations after a small delay
    setTimeout(() => {
      this.popup.classList.add('popup-visible');
    }, 50);

    // Prevent body scroll
    document.body.style.overflow = 'hidden';
  }

  closePopup() {
    if (!this.popup) return;

    this.popup.classList.remove('popup-visible');

    // Hide after animation
    setTimeout(() => {
      this.popup.style.display = 'none';
      document.body.style.overflow = '';
    }, 300);

    // Set cookie to prevent showing again (expires in 30 days)
    this.setCookie(this.cookieName, 'true', this.cookieExpiry);
  }

  hidePopup() {
    if (!this.popup) return;

    this.popup.style.display = 'none';
    this.popup.classList.remove('popup-visible');
    document.body.style.overflow = '';
  }

  handleJourneySelection(option, link) {
    console.log(`User selected journey: ${option}`);

    this.setCookie('user_journey_preference', option, this.cookieExpiry);

    this.closePopup();

    if (link) {
      setTimeout(() => {
        window.open(link, '_blank');
      }, 100);
    }
  }

  isPopupVisible() {
    return this.popup && this.popup.style.display === 'block';
  }

  // Cookie utilities
  setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
  }

  getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) === ' ') c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }

  // Static method to reset for testing
  static resetFirstVisit() {
    document.cookie = 'first_visit_popup_shown=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    document.cookie = 'user_journey_preference=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    console.log('First visit cookies cleared');
    location.reload();
  }

  // Static method to force show popup for testing
  static forceShowPopup() {
    // Clear the cookie first
    document.cookie = 'first_visit_popup_shown=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

    // Get or create instance and show popup
    if (firstVisitPopupInstance) {
      firstVisitPopupInstance.showPopup();
    } else {
      // Create new instance if doesn't exist
      const newInstance = new FirstVisitPopup();
      newInstance.showPopup();
    }
  }
}

// Smooth scroll utility for CTA buttons
function scrollToPartnersSection() {
  const targetSection = document.getElementById('partners-benefits');
  if (targetSection) {
    targetSection.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
    });
  }
}

// Expose for debugging and testing
window.FirstVisitPopup = FirstVisitPopup;

// Add global helper functions for testing
window.showFirstVisitPopup = () => FirstVisitPopup.forceShowPopup();
window.resetFirstVisit = () => FirstVisitPopup.resetFirstVisit();

// Initialize when DOM is ready - only once
let firstVisitPopupInstance = null;

document.addEventListener('DOMContentLoaded', () => {
  if (!firstVisitPopupInstance) {
    firstVisitPopupInstance = new FirstVisitPopup();
    window.firstVisitPopupInstance = firstVisitPopupInstance; // For debugging
  }
});

// Optional: Show console help message in development
if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
  console.log(
    '%c First Visit Popup Development Helper Functions:',
    'color: #ffd700; font-weight: bold;',
  );
  console.log('%c - showFirstVisitPopup(): Force show the popup', 'color: #90ee90;');
  console.log('%c - resetFirstVisit(): Clear cookies and reload', 'color: #90ee90;');
}

// Optional: Auto-reset for testing during development (comment out for production)
/*
if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
  console.log('Development mode - resetting first visit popup for testing');
  document.cookie = 'first_visit_popup_shown=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
}
*/
