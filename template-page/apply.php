<?php
/*
Template Name: Apply Page
*/

if (!defined('ABSPATH')) {
    exit;
}

// Initialize variables for messages
$success_message = '';
$errors = array();

// Check for success/error messages from session or query params
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Your partner application has been submitted successfully! We will review it and get back to you soon.';
}

if (isset($_GET['error'])) {
    $errors[] = sanitize_text_field($_GET['error']);
}

$apply_image = get_field('apply_image') ?: '';
$apply_form_title = get_field('apply_form_title') ?: 'Become Our Partner';
$apply_form_description = get_field('apply_form_description') ?: 'Expand your opportunities by collaborating with our salon.';

get_header(); ?>

<main class="sunny-apply-page">
    <div class="sunny-apply-container">
        <div class="apply-section">
            <form id="partner-apply-form" class="partner-apply-form" enctype="multipart/form-data">
                <?php wp_nonce_field('sunny_partner_apply', 'apply_nonce'); ?>

                <!-- Hidden field to track submission status -->
                <input type="hidden" id="form-submitted" value="false">

                <h2 class="form-title"><?php echo esc_html($apply_form_title); ?></h2>

                <p class="form-description"><?php echo esc_html($apply_form_description); ?></p>

                <div class="form-group">
                    <input type="text" id="partner_title" name="partner_title" required class="form-input" placeholder=" " value="<?php echo esc_attr($_POST['partner_title'] ?? ''); ?>">
                    <label for="partner_title" class="form-label">Partner Title*</label>
                    <div class="field-error" id="partner_title_error"></div>
                </div>

                <div class="form-group">
                    <textarea id="partner_description" name="partner_description" required class="form-input" placeholder=" " rows="4"><?php echo esc_textarea($_POST['partner_description'] ?? ''); ?></textarea>
                    <label for="partner_description" class="form-label">Partner description*</label>
                    <div class="field-error" id="partner_description_error"></div>
                </div>

                <div class="form-group benefit-fields">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="custom-select-wrapper">
                                <div class="custom-select" data-name="benefit_icon_type" data-required="true">
                                    <div class="custom-select-trigger">
                                        <span class="custom-select-text placeholder">Benefit Icon*</span>
                                        <svg class="custom-select-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6,9 12,15 18,9"></polyline>
                                        </svg>
                                    </div>
                                    <div class="custom-select-options">
                                        <?php
                                        $benefit_types = [
                                            'discount' => 'Discount for clients',
                                            'complimentary' => 'Complimentary service',
                                            'gift' => 'Gift with service'
                                        ];

                                        foreach ($benefit_types as $value => $label):
                                        ?>
                                            <div class="custom-select-option" data-value="<?php echo esc_attr($value); ?>">
                                                <div class="custom-select-option-content">
                                                    <?php if ($value === 'discount'): ?>
                                                        <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.97266 1.59265L5.86774 2.69659C5.77782 2.78479 5.65733 2.83488 5.53138 2.83642H3.79285C2.9891 2.83642 2.33788 3.48763 2.33788 4.29138V6.02991C2.33659 6.1562 2.28649 6.27709 2.19806 6.36726L1.09314 7.47119C0.820863 7.74429 0.667969 8.1142 0.667969 8.49984C0.667969 8.88548 0.820863 9.25539 1.09314 9.52849L2.19806 10.6334C2.28625 10.7233 2.33634 10.8438 2.33788 10.9698V12.7083C2.33788 13.512 2.9891 14.1633 3.79285 14.1633H5.53138C5.65766 14.1646 5.77855 14.2147 5.86872 14.3031L6.97266 15.408C7.24576 15.6803 7.61566 15.8332 8.0013 15.8332C8.38694 15.8332 8.75685 15.6803 9.02995 15.408L10.1349 14.3031C10.2248 14.2149 10.3453 14.1648 10.4712 14.1633H12.2098C13.0135 14.1633 13.6647 13.512 13.6647 12.7083V10.9698C13.666 10.8435 13.7161 10.7226 13.8046 10.6324L14.9095 9.52849C15.1817 9.25539 15.3346 8.88548 15.3346 8.49984C15.3346 8.1142 15.1817 7.74429 14.9095 7.47119L13.8046 6.36628C13.7164 6.27635 13.6663 6.15586 13.6647 6.02991V4.29138C13.6647 3.48763 13.0135 2.83642 12.2098 2.83642H10.4712C10.3449 2.83513 10.2241 2.78502 10.1339 2.69659L9.02995 1.59168C8.75685 1.3194 8.38694 1.1665 8.0013 1.1665C7.61566 1.1665 7.24576 1.32038 6.97266 1.59265ZM5.5568 10.9434C5.36563 10.7522 5.36563 10.4423 5.5568 10.2511L9.75352 6.05436C9.94469 5.86319 10.2546 5.86319 10.4458 6.05436C10.637 6.24553 10.637 6.55547 10.4458 6.74664L6.24908 10.9434C6.05792 11.1345 5.74797 11.1345 5.5568 10.9434ZM6.0457 6.05534C5.77569 6.05534 5.5568 6.27423 5.5568 6.54424C5.5568 6.81425 5.77569 7.03314 6.0457 7.03314C6.31571 7.03314 6.5346 6.81425 6.5346 6.54424C6.5346 6.27423 6.31571 6.05534 6.0457 6.05534ZM9.46801 10.4554C9.46801 10.7255 9.68689 10.9443 9.95691 10.9443C10.2269 10.9443 10.4458 10.7255 10.4458 10.4554C10.4458 10.1854 10.2269 9.96654 9.95691 9.96654C9.68689 9.96654 9.46801 10.1854 9.46801 10.4554Z" fill="#302F34" />
                                                        </svg>
                                                    <?php elseif ($value === 'complimentary'): ?>
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <g clip-path="url(#clip0_4307_14672)">
                                                                <path d="M15.5023 13.2269L16.7864 10.6608C16.8834 10.4607 17.0348 10.2923 17.2229 10.1753C17.411 10.0583 17.6282 9.99734 17.8493 9.99957C18.0676 9.99957 18.2816 10.0643 18.4628 10.1865C18.6419 10.3058 18.7853 10.4725 18.8766 10.668L20.1322 13.2341L20.2106 13.2916L23.0071 13.7013C23.2226 13.73 23.4252 13.8199 23.5921 13.96C23.7591 14.1074 23.8846 14.2957 23.9559 14.5063C24.0164 14.7201 24.0164 14.9466 23.9559 15.1604C23.9063 15.3714 23.7973 15.5636 23.6421 15.7138L21.616 17.7048C21.6091 17.7169 21.6055 17.7305 21.6055 17.7444C21.6055 17.7582 21.6091 17.7719 21.616 17.7839L22.1011 20.6087C22.1347 20.8222 22.1125 21.0399 22.0369 21.2412C21.954 21.4464 21.8173 21.625 21.6413 21.758C21.4654 21.8909 21.2569 21.9732 21.0382 21.9959C20.8124 22.0139 20.5864 21.9639 20.389 21.8522L17.9777 20.5799L17.8565 20.5296H17.7566L15.2454 21.8522C15.0787 21.9422 14.8924 21.9892 14.7033 21.9887H14.582C14.3654 21.9676 14.1584 21.8882 13.9827 21.7587C13.8045 21.6317 13.6679 21.454 13.5904 21.2484C13.5141 21.015 13.5141 20.7631 13.5904 20.5296L14.0612 17.7911C14.0641 17.7648 14.0641 17.7383 14.0612 17.712H14.0255L12.0138 15.7426C11.8539 15.5851 11.7403 15.3861 11.6856 15.1676C11.6307 14.9541 11.6371 14.7293 11.704 14.5193C11.771 14.3094 11.8957 14.1229 12.0637 13.9816C12.2323 13.8397 12.4379 13.7499 12.6558 13.7228L15.4167 13.3131L15.5023 13.2269Z" fill="#302F34" transform="translate(-8 -8)" />
                                                            </g>
                                                        </svg>
                                                    <?php elseif ($value === 'gift'): ?>
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M15.4085 16.1541V12.6049H14.4108C13.3056 12.6049 12.7231 11.8884 12.7231 11.1921C12.7231 10.4689 13.2588 10.0404 13.9754 10.0404C14.7991 10.0404 15.4419 10.6764 15.4419 11.7412V12.6049H16.5602V11.7412C16.5602 10.6764 17.2031 10.0404 18.0268 10.0404C18.7434 10.0404 19.2856 10.4689 19.2856 11.1918C19.2856 11.8884 18.6828 12.6049 17.5914 12.6049H16.5936V16.1544H21.3148C22.0445 16.1544 22.4396 15.8732 22.4396 15.1566V13.6029C22.4396 12.8932 22.0445 12.6052 21.3148 12.6052H19.7545C20.1696 12.2304 20.4174 11.7212 20.4174 11.1452C20.4174 9.85293 19.3928 8.92236 18.0936 8.92236C17.1294 8.92236 16.3459 9.45779 16.0045 10.4089C15.6628 9.45808 14.8728 8.92236 13.9085 8.92236C12.6159 8.92236 11.5848 9.85293 11.5848 11.1452C11.5848 11.7212 11.8259 12.2304 12.2476 12.6052H10.6876C9.99764 12.6052 9.5625 12.8932 9.5625 13.6029V15.1566C9.5625 15.8732 9.96421 16.1544 10.6874 16.1544L15.4085 16.1541ZM15.4085 23.0781V16.8706H10.5199V21.3772C10.5199 22.4955 11.1762 23.0781 12.2945 23.0781H15.4085ZM16.5936 16.8706V23.0781H19.7076C20.8259 23.0781 21.4819 22.4955 21.4819 21.3772V16.8706H16.5936Z" fill="#302F34" transform="translate(-8 -8)" />
                                                        </svg>
                                                    <?php endif; ?>
                                                    <span><?php echo esc_html($label); ?></span>
                                                </div>
                                                <svg class="custom-select-option-check" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                    <path d="M7.9987 1.3335C11.6807 1.3335 14.6654 4.31883 14.6654 8.00016C14.6654 11.6815 11.6807 14.6668 7.9987 14.6668C4.3167 14.6668 1.33203 11.6815 1.33203 8.00016C1.33203 4.31883 4.3167 1.3335 7.9987 1.3335ZM7.9987 2.44483C4.93536 2.44483 2.44336 4.93683 2.44336 8.00016C2.44336 11.0635 4.93536 13.5555 7.9987 13.5555C11.062 13.5555 13.554 11.0635 13.554 8.00016C13.554 4.93683 11.062 2.44483 7.9987 2.44483Z" fill="#85754F" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.1882 5.4978C11.2819 5.60291 11.3346 5.74545 11.3346 5.89408C11.3346 6.04271 11.2819 6.18526 11.1882 6.29037L7.43845 10.4917C7.3889 10.5472 7.33007 10.5912 7.26532 10.6213C7.20057 10.6514 7.13117 10.6668 7.06108 10.6668C6.991 10.6668 6.9216 10.6514 6.85685 10.6213C6.79209 10.5912 6.73326 10.5472 6.68371 10.4917L4.8207 8.40466C4.77291 8.35295 4.7348 8.2911 4.70858 8.22271C4.68237 8.15433 4.66856 8.08078 4.66799 8.00635C4.66741 7.93193 4.68007 7.85812 4.70522 7.78923C4.73038 7.72035 4.76752 7.65776 4.8145 7.60513C4.86147 7.5525 4.91732 7.51088 4.97881 7.4827C5.04029 7.45452 5.10616 7.44033 5.17259 7.44098C5.23901 7.44163 5.30466 7.45709 5.36569 7.48647C5.42673 7.51584 5.48193 7.55855 5.52808 7.61208L7.06092 9.32952L10.4804 5.4978C10.5269 5.44571 10.5821 5.40439 10.6428 5.3762C10.7035 5.34801 10.7686 5.3335 10.8343 5.3335C10.9 5.3335 10.9651 5.34801 11.0258 5.3762C11.0865 5.40439 11.1417 5.44571 11.1882 5.4978Z" fill="#85754F" />
                                                </svg>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <input type="hidden" id="benefit_icon_type" name="benefit_icon_type" required>
                            </div>
                            <div class="field-error" id="benefit_icon_type_error"></div>
                        </div>
                        <div class="form-col">
                            <input type="text" id="benefit_title" name="benefit_title" required class="form-input" placeholder=" " value="<?php echo esc_attr($_POST['benefit_title'] ?? ''); ?>">
                            <label for="benefit_title" class="form-label">Benefit title*</label>

                        </div>
                    </div>
                    <div class="field-error" id="benefit_title_error"></div>
                </div>

                <div class="form-group">
                    <textarea id="benefit_description" name="benefit_description" required class="form-input" placeholder=" " rows="4"><?php echo esc_textarea($_POST['benefit_description'] ?? ''); ?></textarea>
                    <label for="benefit_description" class="form-label">Benefit description*</label>
                    <div class="field-error" id="benefit_description_error"></div>
                </div>

                <div class="form-group">
                    <input type="url" id="link_card" name="link_card" class="form-input" placeholder=" " value="<?php echo esc_attr($_POST['link_card'] ?? ''); ?>">
                    <label for="link_card" class="form-label">Link to read about us more</label>
                    <div class="field-error" id="link_card_error"></div>
                </div>

                <div class="form-group">
                    <label class="field-label">Choose a photo*</label>
                    <div class="file-upload-area">
                        <svg class="upload-icon" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g opacity="0.8">
                                <path d="M16.1952 7.9925C11.2377 7.3115 6.96275 10.9925 7.25 15.875M5.375 6.5C5.375 6.89782 5.53304 7.27936 5.81434 7.56066C6.09564 7.84196 6.47718 8 6.875 8C7.27282 8 7.65436 7.84196 7.93566 7.56066C8.21696 7.27936 8.375 6.89782 8.375 6.5C8.375 6.10218 8.21696 5.72064 7.93566 5.43934C7.65436 5.15804 7.27282 5 6.875 5C6.47718 5 6.09564 5.15804 5.81434 5.43934C5.53304 5.72064 5.375 6.10218 5.375 6.5Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2.75 10.2993C4.835 10.0105 6.70625 11.0178 7.718 12.6243" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2.75 7.55C2.75 5.87 2.75 5.03 3.077 4.388C3.36462 3.82354 3.82354 3.36462 4.388 3.077C5.03 2.75 5.87 2.75 7.55 2.75H11.45C13.13 2.75 13.97 2.75 14.612 3.077C15.1765 3.36462 15.6354 3.82354 15.923 4.388C16.25 5.03 16.25 5.87 16.25 7.55V11.45C16.25 13.13 16.25 13.97 15.923 14.612C15.6354 15.1765 15.1765 15.6354 14.612 15.923C13.97 16.25 13.13 16.25 11.45 16.25H7.55C5.87 16.25 5.03 16.25 4.388 15.923C3.82354 15.6354 3.36462 15.1765 3.077 14.612C2.75 13.97 2.75 13.13 2.75 11.45V7.55Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </g>
                        </svg>
                        <span class="file-upload-text"><span class="upload-link">Click to upload</span> or drag and drop</span>

                        <!-- File Preview (hidden initially) -->
                        <div class="file-preview">

                            <div class="file-preview-info">
                                <img class="file-preview-image" src="" alt="Preview" />
                                <p class="file-preview-name"></p>
                            </div>
                            <div class="file-preview-actions">
                                <button type="button" class="file-action-btn change-btn">Change</button>
                                <button type="button" class="file-action-btn delete delete-btn">Delete</button>
                            </div>
                        </div>

                        <input type="file" id="partner_photo" name="partner_photo" accept="image/*" class="file-input">
                    </div>
                    <div class="field-error" id="partner_photo_error"></div>
                </div>

                <!-- Messages container for AJAX responses -->
                <div class="apply-messages" id="form-messages" style="display: none;">
                    <!-- Messages will be dynamically inserted here -->
                </div>

                <!-- Static PHP messages (for non-AJAX fallback) -->
                <?php if (!empty($success_message)): ?>
                    <div class="apply-messages auto-hide-message">
                        <div class="message success-message">
                            <div class="message-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M8.00004 1.3335C11.682 1.3335 14.6667 4.31883 14.6667 8.00016C14.6667 11.6815 11.682 14.6668 8.00004 14.6668C4.31804 14.6668 1.33337 11.6815 1.33337 8.00016C1.33337 4.31883 4.31804 1.3335 8.00004 1.3335ZM8.00004 2.44483C4.93671 2.44483 2.44471 4.93683 2.44471 8.00016C2.44471 11.0635 4.93671 13.5555 8.00004 13.5555C11.0634 13.5555 13.5554 11.0635 13.5554 8.00016C13.5554 4.93683 11.0634 2.44483 8.00004 2.44483Z" fill="#00c853" />
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.1868 5.4978C11.2806 5.60291 11.3333 5.74545 11.3333 5.89408C11.3333 6.04271 11.2806 6.18526 11.1868 6.29037L7.43711 10.4917C7.38756 10.5472 7.32873 10.5912 7.26398 10.6213C7.19923 10.6514 7.12983 10.6668 7.05974 10.6668C6.98965 10.6668 6.92025 10.6514 6.8555 10.6213C6.79075 10.5912 6.73192 10.5472 6.68237 10.4917L4.81935 8.40466C4.77157 8.35295 4.73346 8.2911 4.70724 8.22271C4.68102 8.15433 4.66722 8.08078 4.66664 8.00635C4.66607 7.93193 4.67873 7.85812 4.70388 7.78923C4.72903 7.72035 4.76618 7.65776 4.81315 7.60513C4.86012 7.5525 4.91598 7.51088 4.97746 7.4827C5.03894 7.45452 5.10482 7.44033 5.17125 7.44098C5.23767 7.44163 5.30332 7.45709 5.36435 7.48647C5.42539 7.51584 5.48059 7.55855 5.52674 7.61208L7.05957 9.32952L10.4791 5.4978C10.5256 5.44571 10.5807 5.40439 10.6414 5.3762C10.7022 5.34801 10.7672 5.3335 10.833 5.3335C10.8987 5.3335 10.9638 5.34801 11.0245 5.3762C11.0852 5.40439 11.1404 5.44571 11.1868 5.4978Z" fill="#00c853" />
                                </svg>
                            </div>
                            <div class="message-text"><?php echo esc_html($success_message); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="apply-messages auto-hide-message">
                        <div class="message error-message">
                            <div class="message-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M7.99998 1.33331C11.682 1.33331 14.6666 4.31865 14.6666 7.99998C14.6666 11.6813 11.682 14.6666 7.99998 14.6666C4.31798 14.6666 1.33331 11.6813 1.33331 7.99998C1.33331 4.31865 4.31798 1.33331 7.99998 1.33331ZM7.99998 2.44465C4.93665 2.44465 2.44465 4.93665 2.44465 7.99998C2.44465 11.0633 4.93665 13.5553 7.99998 13.5553C11.0633 13.5553 13.5553 11.0633 13.5553 7.99998C13.5553 4.93665 11.0633 2.44465 7.99998 2.44465ZM7.99931 9.66798C8.17595 9.66798 8.34535 9.73815 8.47025 9.86305C8.59515 9.98795 8.66531 10.1573 8.66531 10.334C8.66531 10.5106 8.59515 10.68 8.47025 10.8049C8.34535 10.9298 8.17595 11 7.99931 11C7.82268 11 7.65328 10.9298 7.52838 10.8049C7.40348 10.68 7.33331 10.5106 7.33331 10.334C7.33331 10.1573 7.40348 9.98795 7.52838 9.86305C7.65328 9.73815 7.82268 9.66798 7.99931 9.66798ZM7.99598 4.66665C8.11692 4.66649 8.23382 4.71017 8.32502 4.78961C8.41621 4.86904 8.47553 4.97883 8.49198 5.09865L8.49665 5.16598L8.49931 8.16731C8.49944 8.29405 8.45144 8.41611 8.36501 8.50881C8.27857 8.60151 8.16017 8.65792 8.03373 8.66664C7.90729 8.67537 7.78225 8.63575 7.68391 8.5558C7.58557 8.47585 7.52125 8.36154 7.50398 8.23598L7.49931 8.16798L7.49665 5.16731C7.49656 5.1016 7.50943 5.03651 7.53451 4.97577C7.5596 4.91503 7.59642 4.85983 7.64286 4.81333C7.6893 4.76683 7.74444 4.72994 7.80515 4.70477C7.86586 4.6796 7.93026 4.66665 7.99598 4.66665Z" fill="#dc3232" />
                                </svg>
                            </div>
                            <div class="message-text"><?php echo esc_html(implode(', ', $errors)); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="form-actions">
                    <button type="submit" class="btn yellow btn-continue" id="apply-btn">
                        <span class="btn-text">Become a Partner</span>
                        <span class="btn-spinner" style="display:none;">
                            <div class="spinner"></div>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div class="hero-section" style="background-image: url('<?php echo esc_url(get_field('apply_image') ? get_field('apply_image')['url'] : get_template_directory_uri() . '/assets/svg/illustration2.png'); ?>');">
        </div>
    </div>
</main>

<script>
    // Auto-hide messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('.auto-hide-message');
        messages.forEach(function(message) {
            setTimeout(function() {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
</script>

<?php get_footer(); ?>