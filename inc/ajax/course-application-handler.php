<?php

// Handle course application submission
add_action('wp_ajax_submit_course_application', 'handle_course_application_submission');
add_action('wp_ajax_nopriv_submit_course_application', 'handle_course_application_submission');

function handle_course_application_submission()
{
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Sanitize and validate input data
    $applicant_data = [
        'name' => sanitize_text_field($_POST['applicant']['name'] ?? ''),
        'email' => sanitize_email($_POST['applicant']['email'] ?? ''),
        'phone' => sanitize_text_field($_POST['applicant']['phone'] ?? ''),
    ];

    $course_id = intval($_POST['course_id'] ?? 0);
    $course_title = sanitize_text_field($_POST['course_title'] ?? '');
    $course_price = sanitize_text_field($_POST['course_price'] ?? '');

    // Validate required fields
    $errors = [];

    if (empty($applicant_data['name'])) {
        $errors[] = 'Name is required';
    }

    if (empty($applicant_data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!is_email($applicant_data['email'])) {
        $errors[] = 'Invalid email format';
    }

    if (empty($applicant_data['phone'])) {
        $errors[] = 'Phone number is required';
    }

    if (!empty($errors)) {
        wp_send_json_error(['message' => implode(', ', $errors)]);
        return;
    }

    // Get country code from form
    $country_button = $_POST['country_code'] ?? '+65';

    // Prepare data for database
    $submission_data = [
        'submission_date' => current_time('mysql'),
        'course_id' => $course_id,
        'course_title' => $course_title,
        'course_price' => $course_price,
        'applicant_name' => $applicant_data['name'],
        'applicant_email' => $applicant_data['email'],
        'applicant_phone' => $applicant_data['phone'],
        'phone_country' => $country_button,
        'status' => 'new',
        'user_id' => is_user_logged_in() ? get_current_user_id() : null,
    ];

    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'course_applications';

    $result = $wpdb->insert(
        $table_name,
        $submission_data,
        [
            '%s', // submission_date
            '%d', // course_id
            '%s', // course_title
            '%s', // course_price
            '%s', // applicant_name
            '%s', // applicant_email
            '%s', // applicant_phone
            '%s', // phone_country
            '%s', // status
            '%d'  // user_id
        ]
    );

    if ($result === false) {
        error_log('Failed to save course application: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to save application. Please try again.']);
        return;
    }

    $application_id = $wpdb->insert_id;

    // Send email notification to admin
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');

    $subject = "New Course Application - {$course_title}";
    $message = "
    New course application received on {$site_name}
    
    Application Details:
    - Application ID: {$application_id}
    - Course: {$course_title}
    - Course ID: {$course_id}
    - Price at submission: {$course_price}
    - Submission Date: {$submission_data['submission_date']}
    
    Applicant Information:
    - Name: {$applicant_data['name']}
    - Email: {$applicant_data['email']}
    - Phone: {$country_button} {$applicant_data['phone']}
    
    You can review this application in the WordPress admin panel.
    ";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>',
        'Reply-To: ' . $applicant_data['email'],
    ];

    wp_mail($admin_email, $subject, $message, $headers);

    // Log successful submission
    error_log("Course application submitted successfully. ID: {$application_id}, Course: {$course_title}, Applicant: {$applicant_data['email']}");

    // Send success response
    wp_send_json_success([
        'message' => 'Application submitted successfully',
        'application_id' => $application_id
    ]);
}

// Create database table for course applications
function create_course_applications_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'course_applications';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        submission_date datetime DEFAULT CURRENT_TIMESTAMP,
        course_id int(11) NOT NULL,
        course_title varchar(255) NOT NULL,
        course_price varchar(50) NOT NULL,
        applicant_name varchar(255) NOT NULL,
        applicant_email varchar(255) NOT NULL,
        applicant_phone varchar(50) NOT NULL,
        phone_country varchar(10) NOT NULL,
        status varchar(20) DEFAULT 'new',
        user_id int(11) NULL,
        notes text NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_course_id (course_id),
        INDEX idx_applicant_email (applicant_email),
        INDEX idx_status (status),
        INDEX idx_submission_date (submission_date)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook to create table on theme activation
add_action('after_switch_theme', 'create_course_applications_table');

// Also create table if it doesn't exist (for existing installations)
add_action('init', function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'course_applications';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        create_course_applications_table();
    }
});
