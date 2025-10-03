<?php

// Add admin menu for course applications
add_action('admin_menu', 'add_course_applications_admin_menu');

function add_course_applications_admin_menu()
{
    add_menu_page(
        'Course Applications',
        'Course Applications',
        'manage_options',
        'course-applications',
        'display_course_applications_page',
        'dashicons-welcome-learn-more',
        30
    );
}

function display_course_applications_page()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'course_applications';

    // Handle status updates
    if (isset($_POST['update_status']) && isset($_POST['application_id']) && isset($_POST['new_status'])) {
        $application_id = intval($_POST['application_id']);
        $new_status = sanitize_text_field($_POST['new_status']);

        $wpdb->update(
            $table_name,
            ['status' => $new_status],
            ['id' => $application_id],
            ['%s'],
            ['%d']
        );

        echo '<div class="notice notice-success"><p>Status updated successfully!</p></div>';
    }

    // Handle delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $application_id = intval($_GET['id']);
        $wpdb->delete($table_name, ['id' => $application_id], ['%d']);
        echo '<div class="notice notice-success"><p>Application deleted successfully!</p></div>';
    }

    // Get filter parameters
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $course_filter = isset($_GET['course']) ? sanitize_text_field($_GET['course']) : '';
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

    // Build query
    $query = "SELECT * FROM $table_name WHERE 1=1";
    $query_params = [];

    if ($status_filter) {
        $query .= " AND status = %s";
        $query_params[] = $status_filter;
    }

    if ($course_filter) {
        $query .= " AND course_title LIKE %s";
        $query_params[] = '%' . $course_filter . '%';
    }

    if ($date_from) {
        $query .= " AND DATE(submission_date) >= %s";
        $query_params[] = $date_from;
    }

    if ($date_to) {
        $query .= " AND DATE(submission_date) <= %s";
        $query_params[] = $date_to;
    }

    $query .= " ORDER BY submission_date DESC";

    if (!empty($query_params)) {
        $applications = $wpdb->get_results($wpdb->prepare($query, $query_params));
    } else {
        $applications = $wpdb->get_results($query);
    }

    // Get unique courses for filter
    $courses = $wpdb->get_results("SELECT DISTINCT course_title FROM $table_name ORDER BY course_title");

?>
    <div class="wrap">
        <h1>Course Applications</h1>

        <!-- Filters -->
        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="course-applications">

            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="">All Statuses</option>
                <option value="new" <?php selected($status_filter, 'new'); ?>>New</option>
                <option value="contacted" <?php selected($status_filter, 'contacted'); ?>>Contacted</option>
                <option value="enrolled" <?php selected($status_filter, 'enrolled'); ?>>Enrolled</option>
                <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejected</option>
            </select>

            <label for="course">Course:</label>
            <select name="course" id="course">
                <option value="">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo esc_attr($course->course_title); ?>"
                        <?php selected($course_filter, $course->course_title); ?>>
                        <?php echo esc_html($course->course_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="date_from">From:</label>
            <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">

            <label for="date_to">To:</label>
            <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">

            <input type="submit" class="button" value="Filter">
            <a href="?page=course-applications" class="button">Clear Filters</a>
        </form>

        <div class="tablenav top">
            <div class="alignleft">
                <strong>Total: <?php echo count($applications); ?> applications</strong>
            </div>
        </div>

        <div class="table-responsive">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Course</th>
                        <th>Price</th>
                        <th>Applicant</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No applications found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo $app->id; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($app->submission_date)); ?></td>
                                <td><strong><?php echo esc_html($app->course_title); ?></strong></td>
                                <td>$<?php echo esc_html($app->course_price); ?></td>
                                <td><?php echo esc_html($app->applicant_name); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($app->applicant_email); ?>"><?php echo esc_html($app->applicant_email); ?></a></td>
                                <td><a href="tel:<?php echo esc_attr($app->phone_country . $app->applicant_phone); ?>"><?php echo esc_html($app->phone_country . ' ' . $app->applicant_phone); ?></a></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="application_id" value="<?php echo $app->id; ?>">
                                        <select name="new_status" onchange="this.form.submit()">
                                            <option value="new" <?php selected($app->status, 'new'); ?>>New</option>
                                            <option value="contacted" <?php selected($app->status, 'contacted'); ?>>Contacted</option>
                                            <option value="enrolled" <?php selected($app->status, 'enrolled'); ?>>Enrolled</option>
                                            <option value="rejected" <?php selected($app->status, 'rejected'); ?>>Rejected</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <a href="?page=course-applications&action=delete&id=<?php echo $app->id; ?>"
                                        onclick="return confirm('Are you sure you want to delete this application?')"
                                        class="button button-small">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div> <!-- .table-responsive -->
    </div>

    <style>
        .wrap form {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .wrap form label {
            margin-right: 5px;
            margin-left: 15px;
        }

        .wrap form label:first-child {
            margin-left: 0;
        }

        .wrap form select,
        .wrap form input[type="date"] {
            margin-right: 10px;
        }

        /* Responsive styles for admin panel */
        @media screen and (max-width: 1440px) {
            .wp-list-table {
                font-size: 13px;
            }

            .wp-list-table th,
            .wp-list-table td {
                padding: 8px 6px;
            }
        }

        @media screen and (max-width: 1200px) {
            .wrap form {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                align-items: center;
            }

            .wrap form label {
                margin: 0;
                white-space: nowrap;
            }

            .wrap form select,
            .wrap form input[type="date"] {
                margin: 0;
                min-width: 120px;
            }

            .wp-list-table {
                font-size: 12px;
            }

            .wp-list-table th,
            .wp-list-table td {
                padding: 6px 4px;
            }

            /* Hide less important columns on smaller screens */
            .wp-list-table th:nth-child(1),
            .wp-list-table td:nth-child(1),
            .wp-list-table th:nth-child(4),
            .wp-list-table td:nth-child(4) {
                display: none;
            }
        }

        @media screen and (max-width: 900px) {
            .wrap form {
                flex-direction: column;
                align-items: stretch;
            }

            .wrap form>* {
                margin: 5px 0;
            }

            .wp-list-table {
                font-size: 11px;
            }

            .wp-list-table th,
            .wp-list-table td {
                padding: 4px 2px;
                word-break: break-word;
            }

            /* Hide more columns on very small screens */
            .wp-list-table th:nth-child(2),
            .wp-list-table td:nth-child(2),
            .wp-list-table th:nth-child(7),
            .wp-list-table td:nth-child(7) {
                display: none;
            }

            /* Make action buttons smaller */
            .button-small {
                font-size: 10px;
                padding: 2px 6px;
            }

            /* Make status select smaller */
            select[name="new_status"] {
                font-size: 10px;
                padding: 2px;
            }
        }

        /* Table overflow handling */
        .table-responsive {
            overflow-x: auto;
            margin: 20px 0;
        }

        @media screen and (max-width: 1440px) {
            .wp-list-table {
                min-width: 800px;
            }
        }
        }

        .wrap form select,
        .wrap form input[type="date"] {
            margin-right: 10px;
        }

        .wp-list-table th {
            white-space: nowrap;
        }

        .wp-list-table td {
            vertical-align: middle;
        }
    </style>
<?php
}
