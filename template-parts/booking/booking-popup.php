<?php

/**
 * Booking Popup Template
 * 
 * This template is included in the footer and displays the booking popup.
 */

// Get necessary data
$staffList = isset($staff_list) && !empty($staff_list['data']) ? $staff_list['data'] : [];
$services = isset($services) && !empty($services['data']) ? $services['data'] : [];
?>

<!-- Booking Popup Overlay -->
<div class="booking-popup-overlay">
    <div class="booking-popup">
        <button class="booking-popup-close">
            <svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.2178 14.5L5.70132 6.98538C5.61706 6.90112 5.55022 6.80109 5.50462 6.691C5.45902 6.5809 5.43555 6.46291 5.43555 6.34375C5.43555 6.22459 5.45902 6.1066 5.50462 5.99651C5.55022 5.88642 5.61706 5.78638 5.70132 5.70213C5.78558 5.61787 5.88561 5.55103 5.9957 5.50543C6.10579 5.45983 6.22378 5.43636 6.34294 5.43636C6.4621 5.43636 6.5801 5.45983 6.69019 5.50543C6.80028 5.55103 6.90031 5.61787 6.98457 5.70213L14.4992 13.2186L22.0138 5.70213C22.184 5.53196 22.4148 5.43636 22.6554 5.43636C22.8961 5.43636 23.1269 5.53196 23.2971 5.70213C23.4672 5.87229 23.5628 6.10309 23.5628 6.34375C23.5628 6.58441 23.4672 6.81521 23.2971 6.98538L15.7806 14.5L23.2971 22.0146C23.4672 22.1848 23.5628 22.4156 23.5628 22.6562C23.5628 22.8969 23.4672 23.1277 23.2971 23.2979C23.1269 23.468 22.8961 23.5636 22.6554 23.5636C22.4148 23.5636 22.184 23.468 22.0138 23.2979L14.4992 15.7814L6.98457 23.2979C6.8144 23.468 6.5836 23.5636 6.34294 23.5636C6.10229 23.5636 5.87149 23.468 5.70132 23.2979C5.53115 23.1277 5.43555 22.8969 5.43555 22.6562C5.43555 22.4156 5.53115 22.1848 5.70132 22.0146L13.2178 14.5Z" fill="#302F34" />
            </svg>
        </button>

        <div class="booking-popup-content">
            <h2 class="booking-title">Book an appointment</h2>

            <!-- Booking Steps Content -->
            <div class="booking-steps-container">
                <!-- Step 1: Initial Options -->
                <div class="booking-step active" data-step="initial">
                    <div class="booking-option-item" data-option="services">
                        <div class="option-icon">
                            <svg width="44" height="45" viewBox="0 0 44 45" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.0325 17.2126V15.3793H36.6668V17.2126H14.0325ZM14.0325 23.4166V21.5833H36.6668V23.4166H14.0325ZM14.0325 29.6225V27.7891H36.6668V29.6225H14.0325ZM8.46283 17.4216C8.14261 17.4216 7.87433 17.3098 7.658 17.0861C7.44166 16.8649 7.3335 16.5893 7.3335 16.2593C7.3335 15.9501 7.44166 15.691 7.658 15.482C7.87433 15.2717 8.14261 15.1666 8.46283 15.1666C8.78183 15.1666 9.0495 15.2717 9.26583 15.482C9.48216 15.6897 9.59033 15.9488 9.59033 16.2593C9.59033 16.5893 9.48216 16.8655 9.26583 17.088C9.0495 17.3116 8.78122 17.4235 8.461 17.4235M8.461 23.5926C8.142 23.5926 7.87433 23.4881 7.658 23.2791C7.44166 23.0701 7.3335 22.8104 7.3335 22.5C7.3335 22.1455 7.44166 21.8632 7.658 21.653C7.87433 21.4427 8.14261 21.337 8.46283 21.3358C8.78305 21.3346 9.05072 21.4397 9.26583 21.6511C9.48094 21.8626 9.58911 22.1455 9.59033 22.5C9.59033 22.8092 9.48216 23.0689 9.26583 23.2791C9.0495 23.4893 8.78122 23.5938 8.461 23.5926ZM8.461 29.8333C8.142 29.8333 7.87433 29.7221 7.658 29.4996C7.44166 29.276 7.3335 28.9997 7.3335 28.671C7.3335 28.3605 7.44166 28.1008 7.658 27.8918C7.87433 27.6816 8.14261 27.5765 8.46283 27.5765C8.78183 27.5765 9.0495 27.6816 9.26583 27.8918C9.48216 28.102 9.59033 28.3617 9.59033 28.671C9.59033 28.9997 9.48216 29.276 9.26583 29.4996C9.0495 29.7221 8.78122 29.8333 8.461 29.8333Z" fill="#302F34" />
                            </svg>

                        </div>
                        <div class="option-text">Select services</div>
                        <div class="option-status">
                            <span class="status-indicator active"></span>
                        </div>
                    </div>

                    <div class="booking-option-item" data-option="master">
                        <div class="option-icon">
                            <svg width="35" height="34" viewBox="0 0 35 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12.8449 16.9655C15.5785 16.9655 17.7932 14.7508 17.7932 12.0172C17.7932 9.28368 15.5785 7.06897 12.8449 7.06897C10.1114 7.06897 7.89665 9.28368 7.89665 12.0172C7.89665 14.7508 10.1114 16.9655 12.8449 16.9655ZM16.3794 12.0172C16.3794 13.9704 14.7981 15.5517 12.8449 15.5517C10.8918 15.5517 9.31045 13.9704 9.31045 12.0172C9.31045 10.0641 10.8918 8.48276 12.8449 8.48276C14.7981 8.48276 16.3794 10.0641 16.3794 12.0172ZM3.65527 26.8621V22.9035C3.65527 19.8949 9.7777 18.3793 12.8449 18.3793C14.885 18.3793 18.2774 19.0502 20.3204 20.3848C21.7766 19.991 23.3734 19.7931 24.5087 19.7931C25.8221 19.7931 27.5844 20.0575 29.0399 20.5919C29.7652 20.8591 30.4601 21.2097 30.9853 21.6636C31.5148 22.1209 31.9311 22.7395 31.9311 23.5156V26.8621H3.65527ZM5.06907 22.9035C5.06907 22.6794 5.17227 22.3726 5.61055 21.9739C6.05801 21.5667 6.74653 21.1723 7.61814 20.8287C9.36488 20.1395 11.4856 19.7931 12.8449 19.7931C14.2043 19.7931 16.3257 20.1395 18.071 20.8287C18.9433 21.1723 19.6318 21.5667 20.0786 21.9739C20.5176 22.3726 20.6208 22.6794 20.6208 22.9035V25.4483H5.06907V22.9035ZM21.5772 21.5391C22.6468 21.3179 23.7191 21.2069 24.5087 21.2069C25.673 21.2069 27.2677 21.4472 28.5522 21.9195C29.1947 22.1556 29.7143 22.4341 30.0614 22.7338C30.4042 23.03 30.5173 23.2901 30.5173 23.5163V25.4483H22.0346V22.9035C22.0346 22.4086 21.8685 21.9527 21.5772 21.5391ZM28.3967 14.4914C28.3967 16.6396 26.657 18.3793 24.5087 18.3793C23.4776 18.3793 22.4887 17.9697 21.7595 17.2406C21.0304 16.5114 20.6208 15.5225 20.6208 14.4914C20.6208 12.3431 22.3605 10.6035 24.5087 10.6035C26.657 10.6035 28.3967 12.3431 28.3967 14.4914ZM24.5087 16.9655C25.8759 16.9655 26.9829 15.8585 26.9829 14.4914C26.9829 13.1242 25.8759 12.0172 24.5087 12.0172C23.8525 12.0172 23.2232 12.2779 22.7592 12.7419C22.2952 13.2059 22.0346 13.8352 22.0346 14.4914C22.0346 15.8585 23.1416 16.9655 24.5087 16.9655Z" fill="#302F34" />
                            </svg>

                        </div>
                        <div class="option-text">Choose a master</div>
                        <div class="option-status">
                            <span class="status-indicator"></span>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="book-btn next-btn">Next
                            <span class="book-bt__icon">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>

                    </div>
                </div>

                <!-- Step 2: Select Services -->
                <div class="booking-step" data-step="services">
                    <div class="step-header">
                        <h3>Select services</h3>
                    </div>
                    <div class="services-list">
                        <?php if (!empty($services)) : ?>
                            <?php foreach ($services as $service) : ?>
                                <div class="service-item" data-service-id="<?php echo esc_attr($service['id']); ?>" data-service-code="<?php echo esc_attr($service['code'] ?? ''); ?>">
                                    <div class="service-info">
                                        <h4 class="service-name"><?php echo esc_html($service['title']); ?></h4>
                                        <?php if (!empty($service['description'])) : ?>
                                            <p class="service-description"><?php echo esc_html($service['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="service-price-actions">
                                        <span class="service-price"><?php echo esc_html($service['price_min'] ?? '$0'); ?></span>
                                        <button type="button" class="btn yellow select-service-btn">Select</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="no-items-message">No services available at the moment.</p>
                        <?php endif; ?>
                    </div>
                    <div class="selected-services-container">
                        <h3>Selected Services</h3>
                        <div class="selected-services-list"></div>
                        <div class="total-price">
                            <span>Total:</span>
                            <span class="total-price-amount">$0.00</span>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="book-btn next-btn">Choose a master
                            <span class="book-bt__icon">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Choose a Master -->
                <div class="booking-step" data-step="master">
                    <div class="step-header">
                        <h3>Choose a master</h3>
                    </div>
                    <div class="staff-list">
                        <?php if (!empty($staffList)) : ?>
                            <?php foreach ($staffList as $staff) : ?>
                                <div class="staff-item" data-staff-id="<?php echo esc_attr($staff['id']); ?>">
                                    <?php if (!empty($staff['avatar'])) : ?>
                                        <div class="staff-avatar">
                                            <img src="<?php echo esc_url($staff['avatar']); ?>" alt="<?php echo esc_attr($staff['name']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="staff-info">
                                        <h4 class="staff-name"><?php echo esc_html($staff['name']); ?></h4>
                                        <?php if (!empty($staff['specialization'])) : ?>
                                            <p class="staff-specialization"><?php echo esc_html($staff['specialization']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="no-items-message">No specialists available at the moment.</p>
                        <?php endif; ?>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="book-btn next-btn">Select date and time
                            <span class="book-bt__icon">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Select Date and Time -->
                <div class="booking-step" data-step="datetime">
                    <div class="step-header">
                        <h3>Select date and time</h3>
                    </div>
                    <div class="datetime-container">
                        <div class="date-selector">
                            <div class="month-header">
                                <span>May 2025</span>
                            </div>
                            <div class="weekdays">
                                <div>Mo</div>
                                <div>Tu</div>
                                <div>We</div>
                                <div>Th</div>
                                <div>Fr</div>
                                <div>Sa</div>
                                <div>Su</div>
                            </div>
                            <div class="calendar-grid"></div>
                        </div>
                        <div class="time-selector">
                            <div class="time-header">
                                <span>Available</span>
                            </div>
                            <div class="time-slots"></div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="book-btn next-btn">Book an appointment
                            <span class="book-bt__icon">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.22581 0.773971L9.22581 8.01857M9.22581 0.773971L1.98122 0.773971M9.22581 0.773971L0.773784 9.226" stroke="#302F34" stroke-width="0.838404" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Step 5: Booking Details -->
                <div class="booking-step" data-step="contact">
                    <div class="step-header">
                        <h3>Booking details</h3>
                    </div>
                    <div class="booking-details-container">
                        <div class="contact-form">
                            <div class="form-group">
                                <label for="client-name">Name</label>
                                <input type="text" id="client-name" name="client_name" required>
                            </div>
                            <div class="form-group">
                                <label for="client-phone">Phone</label>
                                <input type="tel" id="client-phone" name="client_phone" required>
                            </div>
                            <div class="form-group">
                                <label for="client-email">Email</label>
                                <input type="email" id="client-email" name="client_email">
                            </div>
                            <div class="form-group">
                                <label for="client-comment">Comment</label>
                                <textarea id="client-comment" name="client_comment"></textarea>
                            </div>
                        </div>
                        <div class="booking-summary">
                            <h3>Selected master</h3>
                            <div class="selected-master-info"></div>

                            <h3>Selected services</h3>
                            <div class="summary-services-list"></div>

                            <div class="total-price">
                                <span>Total:</span>
                                <span class="summary-total-amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn yellow confirm-booking-btn">Confirm booking</button>
                    </div>
                </div>

                <!-- Step 6: Confirmation -->
                <div class="booking-step" data-step="confirm">
                    <div class="step-header">
                        <h3>Booking confirmed</h3>
                    </div>
                    <div class="confirmation-container">
                        <div class="confirmation-icon">✓</div>
                        <p class="confirmation-message">Your booking has been successfully confirmed!</p>
                        <div class="confirmation-details">
                            <p>Booking reference: <span class="booking-reference"></span></p>
                        </div>
                        <div class="booked-services-summary"></div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn yellow close-popup-btn">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>