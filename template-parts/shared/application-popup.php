<?php
// Get user data for auto-fill if logged in
$user_name = '';
$user_email = '';
$user_phone = '';
$user_phone_country = '+65'; // Default to Singapore

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_id = get_current_user_id();

    // Check if plugin is active and table exists
    $plugin_active = false;
    $user_data = null;

    // Try to get data from sunny_friends_customers table directly
    global $wpdb;
    $registrations_table = $wpdb->prefix . 'sunny_friends_customers';

    // Check if table exists to avoid SQL errors
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $registrations_table)) == $registrations_table;

    if ($table_exists) {
        $user_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $registrations_table WHERE user_id = %d",
            $user_id
        ));

        if ($user_data) {
            $plugin_active = true;
            // Try different name fields from Sunny Friends plugin
            if (!empty($user_data->name)) {
                $user_name = $user_data->name;
            } elseif (!empty($user_data->first_name) && !empty($user_data->last_name)) {
                $user_name = trim($user_data->first_name . ' ' . $user_data->last_name);
            } elseif (!empty($user_data->first_name)) {
                $user_name = $user_data->first_name;
            } elseif (!empty($user_data->full_name)) {
                $user_name = $user_data->full_name;
            } else {
                $user_name = '';
            }
            $user_email = $user_data->email;
            $phone = $user_data->phone;

            // Parse country code and phone number
            $user_phone_country = '+65'; // Default to Singapore
            $user_phone = '';

            if ($phone) {
                // Parse country code from phone number
                $phone_cleaned = preg_replace('/[^\d+]/', '', $phone);

                // List of common country codes (longest first)
                $country_codes = ['+65', '+1', '+7', '+44', '+49', '+33', '+86', '+81', '+82', '+91'];

                foreach ($country_codes as $code) {
                    if (strpos($phone_cleaned, $code) === 0) {
                        $user_phone_country = $code;
                        $user_phone = substr($phone_cleaned, strlen($code));
                        break;
                    }
                }

                // If no country code found, assume it's Singapore and the whole number is phone
                if (empty($user_phone)) {
                    $user_phone = $phone_cleaned;
                }
            }
        }
    }

    // Fallback to WordPress user data if plugin is not active or no custom data
    if (!$plugin_active || !$user_data) {
        $user_name = trim($current_user->first_name . ' ' . $current_user->last_name);
        if (empty($user_name)) {
            $user_name = $current_user->display_name;
        }
        // If still empty, try user_login as last resort
        if (empty($user_name)) {
            $user_name = $current_user->user_login;
        }
        $user_email = $current_user->user_email;

        // Try to get phone from user meta as fallback
        $user_phone_meta = get_user_meta($user_id, 'phone', true);
        if ($user_phone_meta) {
            $phone = $user_phone_meta;

            // Use the same parsing logic as above
            $user_phone_country = '+65'; // Default to Singapore
            $user_phone = '';

            if ($phone) {
                // Parse country code from phone number
                $phone_cleaned = preg_replace('/[^\d+]/', '', $phone);

                // List of country codes to check (longest first)
                $country_codes = ['+65', '+1', '+7', '+44', '+49', '+33', '+86', '+81', '+82', '+91'];

                foreach ($country_codes as $code) {
                    if (strpos($phone_cleaned, $code) === 0) {
                        $user_phone_country = $code;
                        $user_phone = substr($phone_cleaned, strlen($code));
                        break;
                    }
                }

                // If no country code found, assume it's Singapore and the whole number is phone
                if (empty($user_phone)) {
                    $user_phone = $phone_cleaned;
                }
            }
        }
    }
}
?>

<!-- Application Popup -->
<div id="application-popup" class="application-popup" style="display: none;">
    <div class="application-popup__overlay">
        <div class="application-popup__container">
            <div class="application-popup__header">
                <h2>Submit Your Application</h2>
                <button type="button" class="application-popup__close" id="applicationPopupClose">
                    <svg class="close-icon" viewBox="0 0 24 24">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <div class="application-popup__content">
                <form id="application-form" class="application-form" novalidate>
                    <input type="hidden" id="course-id" name="course_id" value="">
                    <input type="hidden" id="course-title" name="course_title" value="">
                    <input type="hidden" id="course-price" name="course_price" value="">

                    <div class="form-fields">
                        <div class="form-group">
                            <input type="text" id="applicant-name" name="applicant[name]" placeholder=" "
                                value="<?php echo is_user_logged_in() ? esc_attr($user_name) : ''; ?>"
                                required />
                            <label for="applicant-name">Name</label>
                        </div>

                        <div class="form-group">
                            <input type="email" id="applicant-email" name="applicant[email]" placeholder=" "
                                value="<?php echo is_user_logged_in() ? esc_attr($user_email) : ''; ?>"
                                required />
                            <label for="applicant-email">Email</label>
                        </div>

                        <div class="form-group phone-group">
                            <div class="phone-input-wrapper">
                                <div class="custom-country-select">
                                    <button type="button" class="country-select-button" id="applicationCountrySelectButton">
                                        <span class="selected-country">
                                            <?php
                                            // Set default selected country based on user data
                                            $selected_country = 'Singapore +65'; // Default
                                            if (is_user_logged_in() && $user_phone_country) {
                                                $country_names = [
                                                    '+65' => 'Singapore',
                                                    '+93' => 'Afghanistan',
                                                    '+355' => 'Albania',
                                                    '+213' => 'Algeria',
                                                    '+1684' => 'American Samoa',
                                                    '+376' => 'Andorra',
                                                    '+244' => 'Angola',
                                                    '+1264' => 'Anguilla',
                                                    '+54' => 'Argentina',
                                                    '+374' => 'Armenia',
                                                    '+297' => 'Aruba',
                                                    '+61' => 'Australia',
                                                    '+43' => 'Austria',
                                                    '+994' => 'Azerbaijan',
                                                    '+1242' => 'Bahamas',
                                                    '+973' => 'Bahrain',
                                                    '+880' => 'Bangladesh',
                                                    '+1246' => 'Barbados',
                                                    '+375' => 'Belarus',
                                                    '+32' => 'Belgium',
                                                    '+501' => 'Belize',
                                                    '+229' => 'Benin',
                                                    '+1441' => 'Bermuda',
                                                    '+975' => 'Bhutan',
                                                    '+591' => 'Bolivia',
                                                    '+387' => 'Bosnia and Herzegovina',
                                                    '+267' => 'Botswana',
                                                    '+55' => 'Brazil',
                                                    '+673' => 'Brunei',
                                                    '+359' => 'Bulgaria',
                                                    '+226' => 'Burkina Faso',
                                                    '+257' => 'Burundi',
                                                    '+855' => 'Cambodia',
                                                    '+237' => 'Cameroon',
                                                    '+1' => 'Canada',
                                                    '+238' => 'Cape Verde',
                                                    '+1345' => 'Cayman Islands',
                                                    '+236' => 'Central African Republic',
                                                    '+235' => 'Chad',
                                                    '+56' => 'Chile',
                                                    '+86' => 'China',
                                                    '+57' => 'Colombia',
                                                    '+269' => 'Comoros',
                                                    '+682' => 'Cook Islands',
                                                    '+506' => 'Costa Rica',
                                                    '+385' => 'Croatia',
                                                    '+53' => 'Cuba',
                                                    '+599' => 'Curacao',
                                                    '+357' => 'Cyprus',
                                                    '+420' => 'Czech Republic',
                                                    '+243' => 'Congo (DRC)',
                                                    '+45' => 'Denmark',
                                                    '+253' => 'Djibouti',
                                                    '+1767' => 'Dominica',
                                                    '+1809' => 'Dominican Republic',
                                                    '+670' => 'East Timor',
                                                    '+593' => 'Ecuador',
                                                    '+20' => 'Egypt',
                                                    '+503' => 'El Salvador',
                                                    '+240' => 'Equatorial Guinea',
                                                    '+291' => 'Eritrea',
                                                    '+372' => 'Estonia',
                                                    '+268' => 'Eswatini',
                                                    '+251' => 'Ethiopia',
                                                    '+298' => 'Faroe Islands',
                                                    '+679' => 'Fiji',
                                                    '+358' => 'Finland',
                                                    '+33' => 'France',
                                                    '+594' => 'French Guiana',
                                                    '+689' => 'French Polynesia',
                                                    '+241' => 'Gabon',
                                                    '+220' => 'Gambia',
                                                    '+995' => 'Georgia',
                                                    '+49' => 'Germany',
                                                    '+233' => 'Ghana',
                                                    '+350' => 'Gibraltar',
                                                    '+30' => 'Greece',
                                                    '+299' => 'Greenland',
                                                    '+1473' => 'Grenada',
                                                    '+590' => 'Guadeloupe',
                                                    '+1671' => 'Guam',
                                                    '+502' => 'Guatemala',
                                                    '+44' => 'Guernsey',
                                                    '+224' => 'Guinea',
                                                    '+245' => 'Guinea-Bissau',
                                                    '+592' => 'Guyana',
                                                    '+509' => 'Haiti',
                                                    '+504' => 'Honduras',
                                                    '+852' => 'Hong Kong',
                                                    '+36' => 'Hungary',
                                                    '+354' => 'Iceland',
                                                    '+91' => 'India',
                                                    '+62' => 'Indonesia',
                                                    '+98' => 'Iran',
                                                    '+964' => 'Iraq',
                                                    '+353' => 'Ireland',
                                                    '+44' => 'Isle of Man',
                                                    '+972' => 'Israel',
                                                    '+39' => 'Italy',
                                                    '+225' => 'Ivory Coast',
                                                    '+1876' => 'Jamaica',
                                                    '+81' => 'Japan',
                                                    '+44' => 'Jersey',
                                                    '+962' => 'Jordan',
                                                    '+7' => 'Kazakhstan',
                                                    '+254' => 'Kenya',
                                                    '+686' => 'Kiribati',
                                                    '+383' => 'Kosovo',
                                                    '+965' => 'Kuwait',
                                                    '+996' => 'Kyrgyzstan',
                                                    '+856' => 'Laos',
                                                    '+371' => 'Latvia',
                                                    '+961' => 'Lebanon',
                                                    '+266' => 'Lesotho',
                                                    '+231' => 'Liberia',
                                                    '+218' => 'Libya',
                                                    '+423' => 'Liechtenstein',
                                                    '+370' => 'Lithuania',
                                                    '+352' => 'Luxembourg',
                                                    '+853' => 'Macau',
                                                    '+389' => 'Macedonia',
                                                    '+261' => 'Madagascar',
                                                    '+265' => 'Malawi',
                                                    '+60' => 'Malaysia',
                                                    '+960' => 'Maldives',
                                                    '+223' => 'Mali',
                                                    '+356' => 'Malta',
                                                    '+692' => 'Marshall Islands',
                                                    '+596' => 'Martinique',
                                                    '+222' => 'Mauritania',
                                                    '+230' => 'Mauritius',
                                                    '+262' => 'Mayotte',
                                                    '+52' => 'Mexico',
                                                    '+691' => 'Micronesia',
                                                    '+373' => 'Moldova',
                                                    '+377' => 'Monaco',
                                                    '+976' => 'Mongolia',
                                                    '+382' => 'Montenegro',
                                                    '+1664' => 'Montserrat',
                                                    '+212' => 'Morocco',
                                                    '+258' => 'Mozambique',
                                                    '+95' => 'Myanmar',
                                                    '+264' => 'Namibia',
                                                    '+674' => 'Nauru',
                                                    '+977' => 'Nepal',
                                                    '+31' => 'Netherlands',
                                                    '+687' => 'New Caledonia',
                                                    '+64' => 'New Zealand',
                                                    '+505' => 'Nicaragua',
                                                    '+227' => 'Niger',
                                                    '+234' => 'Nigeria',
                                                    '+683' => 'Niue',
                                                    '+850' => 'North Korea',
                                                    '+1670' => 'Northern Mariana Islands',
                                                    '+47' => 'Norway',
                                                    '+968' => 'Oman',
                                                    '+92' => 'Pakistan',
                                                    '+680' => 'Palau',
                                                    '+970' => 'Palestine',
                                                    '+507' => 'Panama',
                                                    '+675' => 'Papua New Guinea',
                                                    '+595' => 'Paraguay',
                                                    '+51' => 'Peru',
                                                    '+63' => 'Philippines',
                                                    '+48' => 'Poland',
                                                    '+351' => 'Portugal',
                                                    '+1787' => 'Puerto Rico',
                                                    '+974' => 'Qatar',
                                                    '+242' => 'Republic of the Congo',
                                                    '+262' => 'Reunion',
                                                    '+40' => 'Romania',
                                                    '+7' => 'Russia',
                                                    '+250' => 'Rwanda',
                                                    '+590' => 'Saint Barthelemy',
                                                    '+1869' => 'Saint Kitts and Nevis',
                                                    '+1758' => 'Saint Lucia',
                                                    '+590' => 'Saint Martin',
                                                    '+1784' => 'St. Vincent & Grenadines',
                                                    '+685' => 'Samoa',
                                                    '+378' => 'San Marino',
                                                    '+239' => 'Sao Tome and Principe',
                                                    '+966' => 'Saudi Arabia',
                                                    '+221' => 'Senegal',
                                                    '+381' => 'Serbia',
                                                    '+248' => 'Seychelles',
                                                    '+232' => 'Sierra Leone',
                                                    '+1721' => 'Sint Maarten',
                                                    '+421' => 'Slovakia',
                                                    '+386' => 'Slovenia',
                                                    '+677' => 'Solomon Islands',
                                                    '+252' => 'Somalia',
                                                    '+27' => 'South Africa',
                                                    '+82' => 'South Korea',
                                                    '+211' => 'South Sudan',
                                                    '+34' => 'Spain',
                                                    '+94' => 'Sri Lanka',
                                                    '+249' => 'Sudan',
                                                    '+597' => 'Suriname',
                                                    '+47' => 'Svalbard and Jan Mayen',
                                                    '+46' => 'Sweden',
                                                    '+41' => 'Switzerland',
                                                    '+963' => 'Syria',
                                                    '+886' => 'Taiwan',
                                                    '+992' => 'Tajikistan',
                                                    '+255' => 'Tanzania',
                                                    '+66' => 'Thailand',
                                                    '+228' => 'Togo',
                                                    '+676' => 'Tonga',
                                                    '+1868' => 'Trinidad and Tobago',
                                                    '+216' => 'Tunisia',
                                                    '+90' => 'Turkey',
                                                    '+993' => 'Turkmenistan',
                                                    '+1649' => 'Turks and Caicos Islands',
                                                    '+688' => 'Tuvalu',
                                                    '+1340' => 'U.S. Virgin Islands',
                                                    '+256' => 'Uganda',
                                                    '+380' => 'Ukraine',
                                                    '+971' => 'United Arab Emirates',
                                                    '+44' => 'United Kingdom',
                                                    '+1' => 'United States',
                                                    '+598' => 'Uruguay',
                                                    '+998' => 'Uzbekistan',
                                                    '+678' => 'Vanuatu',
                                                    '+39' => 'Vatican',
                                                    '+58' => 'Venezuela',
                                                    '+84' => 'Vietnam',
                                                    '+212' => 'Western Sahara',
                                                    '+967' => 'Yemen',
                                                    '+260' => 'Zambia',
                                                    '+263' => 'Zimbabwe',
                                                ];
                                                $selected_country = isset($country_names[$user_phone_country])
                                                    ? $country_names[$user_phone_country] . ' ' . $user_phone_country
                                                    : 'Singapore +65';
                                            }
                                            echo esc_html($selected_country);
                                            ?>
                                        </span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.47342 5.33181C1.14743 5.02036 1.14743 4.4998 1.47342 4.18836C1.77904 3.89637 2.26025 3.89637 2.56586 4.18835L6.70833 8.14601L10.8508 4.18836C11.1564 3.89637 11.6376 3.89637 11.9432 4.18836C12.2692 4.4998 12.2692 5.02036 11.9432 5.33181L6.70833 10.3332L1.47342 5.33181Z" fill="#9A9A9A" />
                                        </svg>
                                    </button>
                                    <div class="country-dropdown" id="applicationCountryDropdown">
                                        <?php
                                        $countries = [
                                            '+65' => 'Singapore',
                                            '+93' => 'Afghanistan',
                                            '+355' => 'Albania',
                                            '+213' => 'Algeria',
                                            '+1684' => 'American Samoa',
                                            '+376' => 'Andorra',
                                            '+244' => 'Angola',
                                            '+1264' => 'Anguilla',
                                            '+54' => 'Argentina',
                                            '+374' => 'Armenia',
                                            '+297' => 'Aruba',
                                            '+61' => 'Australia',
                                            '+43' => 'Austria',
                                            '+994' => 'Azerbaijan',
                                            '+1242' => 'Bahamas',
                                            '+973' => 'Bahrain',
                                            '+880' => 'Bangladesh',
                                            '+1246' => 'Barbados',
                                            '+375' => 'Belarus',
                                            '+32' => 'Belgium',
                                            '+501' => 'Belize',
                                            '+229' => 'Benin',
                                            '+1441' => 'Bermuda',
                                            '+975' => 'Bhutan',
                                            '+591' => 'Bolivia',
                                            '+387' => 'Bosnia and Herzegovina',
                                            '+267' => 'Botswana',
                                            '+55' => 'Brazil',
                                            '+673' => 'Brunei',
                                            '+359' => 'Bulgaria',
                                            '+226' => 'Burkina Faso',
                                            '+257' => 'Burundi',
                                            '+855' => 'Cambodia',
                                            '+237' => 'Cameroon',
                                            '+1' => 'Canada',
                                            '+238' => 'Cape Verde',
                                            '+1345' => 'Cayman Islands',
                                            '+236' => 'Central African Republic',
                                            '+235' => 'Chad',
                                            '+56' => 'Chile',
                                            '+86' => 'China',
                                            '+57' => 'Colombia',
                                            '+269' => 'Comoros',
                                            '+682' => 'Cook Islands',
                                            '+506' => 'Costa Rica',
                                            '+385' => 'Croatia',
                                            '+53' => 'Cuba',
                                            '+599' => 'Curacao',
                                            '+357' => 'Cyprus',
                                            '+420' => 'Czech Republic',
                                            '+243' => 'Congo (DRC)',
                                            '+45' => 'Denmark',
                                            '+253' => 'Djibouti',
                                            '+1767' => 'Dominica',
                                            '+1809' => 'Dominican Republic',
                                            '+670' => 'East Timor',
                                            '+593' => 'Ecuador',
                                            '+20' => 'Egypt',
                                            '+503' => 'El Salvador',
                                            '+240' => 'Equatorial Guinea',
                                            '+291' => 'Eritrea',
                                            '+372' => 'Estonia',
                                            '+268' => 'Eswatini',
                                            '+251' => 'Ethiopia',
                                            '+298' => 'Faroe Islands',
                                            '+679' => 'Fiji',
                                            '+358' => 'Finland',
                                            '+33' => 'France',
                                            '+594' => 'French Guiana',
                                            '+689' => 'French Polynesia',
                                            '+241' => 'Gabon',
                                            '+220' => 'Gambia',
                                            '+995' => 'Georgia',
                                            '+49' => 'Germany',
                                            '+233' => 'Ghana',
                                            '+350' => 'Gibraltar',
                                            '+30' => 'Greece',
                                            '+299' => 'Greenland',
                                            '+1473' => 'Grenada',
                                            '+590' => 'Guadeloupe',
                                            '+1671' => 'Guam',
                                            '+502' => 'Guatemala',
                                            '+44' => 'Guernsey',
                                            '+224' => 'Guinea',
                                            '+245' => 'Guinea-Bissau',
                                            '+592' => 'Guyana',
                                            '+509' => 'Haiti',
                                            '+504' => 'Honduras',
                                            '+852' => 'Hong Kong',
                                            '+36' => 'Hungary',
                                            '+354' => 'Iceland',
                                            '+91' => 'India',
                                            '+62' => 'Indonesia',
                                            '+98' => 'Iran',
                                            '+964' => 'Iraq',
                                            '+353' => 'Ireland',
                                            '+44' => 'Isle of Man',
                                            '+972' => 'Israel',
                                            '+39' => 'Italy',
                                            '+225' => 'Ivory Coast',
                                            '+1876' => 'Jamaica',
                                            '+81' => 'Japan',
                                            '+44' => 'Jersey',
                                            '+962' => 'Jordan',
                                            '+7' => 'Kazakhstan',
                                            '+254' => 'Kenya',
                                            '+686' => 'Kiribati',
                                            '+383' => 'Kosovo',
                                            '+965' => 'Kuwait',
                                            '+996' => 'Kyrgyzstan',
                                            '+856' => 'Laos',
                                            '+371' => 'Latvia',
                                            '+961' => 'Lebanon',
                                            '+266' => 'Lesotho',
                                            '+231' => 'Liberia',
                                            '+218' => 'Libya',
                                            '+423' => 'Liechtenstein',
                                            '+370' => 'Lithuania',
                                            '+352' => 'Luxembourg',
                                            '+853' => 'Macau',
                                            '+389' => 'Macedonia',
                                            '+261' => 'Madagascar',
                                            '+265' => 'Malawi',
                                            '+60' => 'Malaysia',
                                            '+960' => 'Maldives',
                                            '+223' => 'Mali',
                                            '+356' => 'Malta',
                                            '+692' => 'Marshall Islands',
                                            '+596' => 'Martinique',
                                            '+222' => 'Mauritania',
                                            '+230' => 'Mauritius',
                                            '+262' => 'Mayotte',
                                            '+52' => 'Mexico',
                                            '+691' => 'Micronesia',
                                            '+373' => 'Moldova',
                                            '+377' => 'Monaco',
                                            '+976' => 'Mongolia',
                                            '+382' => 'Montenegro',
                                            '+1664' => 'Montserrat',
                                            '+212' => 'Morocco',
                                            '+258' => 'Mozambique',
                                            '+95' => 'Myanmar',
                                            '+264' => 'Namibia',
                                            '+674' => 'Nauru',
                                            '+977' => 'Nepal',
                                            '+31' => 'Netherlands',
                                            '+687' => 'New Caledonia',
                                            '+64' => 'New Zealand',
                                            '+505' => 'Nicaragua',
                                            '+227' => 'Niger',
                                            '+234' => 'Nigeria',
                                            '+683' => 'Niue',
                                            '+850' => 'North Korea',
                                            '+1670' => 'Northern Mariana Islands',
                                            '+47' => 'Norway',
                                            '+968' => 'Oman',
                                            '+92' => 'Pakistan',
                                            '+680' => 'Palau',
                                            '+970' => 'Palestine',
                                            '+507' => 'Panama',
                                            '+675' => 'Papua New Guinea',
                                            '+595' => 'Paraguay',
                                            '+51' => 'Peru',
                                            '+63' => 'Philippines',
                                            '+48' => 'Poland',
                                            '+351' => 'Portugal',
                                            '+1787' => 'Puerto Rico',
                                            '+974' => 'Qatar',
                                            '+242' => 'Republic of the Congo',
                                            '+262' => 'Reunion',
                                            '+40' => 'Romania',
                                            '+7' => 'Russia',
                                            '+250' => 'Rwanda',
                                            '+590' => 'Saint Barthelemy',
                                            '+1869' => 'Saint Kitts and Nevis',
                                            '+1758' => 'Saint Lucia',
                                            '+590' => 'Saint Martin',
                                            '+1784' => 'St. Vincent & Grenadines',
                                            '+685' => 'Samoa',
                                            '+378' => 'San Marino',
                                            '+239' => 'Sao Tome and Principe',
                                            '+966' => 'Saudi Arabia',
                                            '+221' => 'Senegal',
                                            '+381' => 'Serbia',
                                            '+248' => 'Seychelles',
                                            '+232' => 'Sierra Leone',
                                            '+1721' => 'Sint Maarten',
                                            '+421' => 'Slovakia',
                                            '+386' => 'Slovenia',
                                            '+677' => 'Solomon Islands',
                                            '+252' => 'Somalia',
                                            '+27' => 'South Africa',
                                            '+82' => 'South Korea',
                                            '+211' => 'South Sudan',
                                            '+34' => 'Spain',
                                            '+94' => 'Sri Lanka',
                                            '+249' => 'Sudan',
                                            '+597' => 'Suriname',
                                            '+47' => 'Svalbard and Jan Mayen',
                                            '+46' => 'Sweden',
                                            '+41' => 'Switzerland',
                                            '+963' => 'Syria',
                                            '+886' => 'Taiwan',
                                            '+992' => 'Tajikistan',
                                            '+255' => 'Tanzania',
                                            '+66' => 'Thailand',
                                            '+228' => 'Togo',
                                            '+676' => 'Tonga',
                                            '+1868' => 'Trinidad and Tobago',
                                            '+216' => 'Tunisia',
                                            '+90' => 'Turkey',
                                            '+993' => 'Turkmenistan',
                                            '+1649' => 'Turks and Caicos Islands',
                                            '+688' => 'Tuvalu',
                                            '+1340' => 'U.S. Virgin Islands',
                                            '+256' => 'Uganda',
                                            '+380' => 'Ukraine',
                                            '+971' => 'United Arab Emirates',
                                            '+44' => 'United Kingdom',
                                            '+1' => 'United States',
                                            '+598' => 'Uruguay',
                                            '+998' => 'Uzbekistan',
                                            '+678' => 'Vanuatu',
                                            '+39' => 'Vatican',
                                            '+58' => 'Venezuela',
                                            '+84' => 'Vietnam',
                                            '+212' => 'Western Sahara',
                                            '+967' => 'Yemen',
                                            '+260' => 'Zambia',
                                            '+263' => 'Zimbabwe',
                                        ];
                                        $singapore_code = '+65';
                                        $user_code = is_user_logged_in() && $user_phone_country ? $user_phone_country : $singapore_code;

                                        echo '<div class="country-option' . ($user_code === $singapore_code ? ' selected' : '') .
                                            '" data-value="' . $singapore_code . '" data-country="Singapore">Singapore +65</div>';

                                        $other_countries = $countries;
                                        unset($other_countries[$singapore_code]);
                                        asort($other_countries);
                                        foreach ($other_countries as $code => $name) {
                                            $selected = $user_code === $code ? ' selected' : '';
                                            echo '<div class="country-option' . $selected .
                                                '" data-value="' . $code . '" data-country="' . $name . '">' .
                                                $name . ' ' . $code . '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <input type="tel" id="applicant-phone" name="applicant[phone]" placeholder=" Enter Phone Number"
                                    value="<?php echo is_user_logged_in() ? esc_attr($user_phone) : ''; ?>"
                                    required />
                            </div>
                            <label class="phone-label" for="applicant-phone">Phone Number</label>
                        </div>

                    </div>

                    <div class="form-errors global-form-error" style="display:none;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="24" height="24" rx="6" fill="white" />
                            <path d="M12.0002 5.33325C15.6822 5.33325 18.6668 8.31859 18.6668 11.9999C18.6668 15.6813 15.6822 18.6666 12.0002 18.6666C8.31816 18.6666 5.3335 15.6813 5.3335 11.9999C5.3335 8.31859 8.31816 5.33325 12.0002 5.33325ZM12.0002 6.44459C8.93683 6.44459 6.44483 8.93659 6.44483 11.9999C6.44483 15.0633 8.93683 17.5553 12.0002 17.5553C15.0635 17.5553 17.5555 15.0633 17.5555 11.9999C17.5555 8.93659 15.0635 6.44459 12.0002 6.44459ZM11.9995 13.6679C12.1761 13.6679 12.3455 13.7381 12.4704 13.863C12.5953 13.9879 12.6655 14.1573 12.6655 14.3339C12.6655 14.5106 12.5953 14.68 12.4704 14.8049C12.3455 14.9298 12.1761 14.9999 11.9995 14.9999C11.8229 14.9999 11.6535 14.9298 11.5286 14.8049C11.4037 14.68 11.3335 14.5106 11.3335 14.3339C11.3335 14.1573 11.4037 13.9879 11.5286 13.863C11.6535 13.7381 11.8229 13.6679 11.9995 13.6679ZM11.9962 8.66659C12.1171 8.66643 12.234 8.71011 12.3252 8.78954C12.4164 8.86898 12.4757 8.97877 12.4922 9.09859L12.4968 9.16592L12.4995 12.1673C12.4996 12.294 12.4516 12.4161 12.3652 12.5087C12.2788 12.6014 12.1603 12.6579 12.0339 12.6666C11.9075 12.6753 11.7824 12.6357 11.6841 12.5557C11.5857 12.4758 11.5214 12.3615 11.5042 12.2359L11.4995 12.1679L11.4968 9.16725C11.4967 9.10154 11.5096 9.03645 11.5347 8.97571C11.5598 8.91497 11.5966 8.85977 11.643 8.81327C11.6895 8.76677 11.7446 8.72988 11.8053 8.70471C11.866 8.67954 11.9304 8.66659 11.9962 8.66659Z" fill="#DC3232" />
                        </svg>
                        <span>One or more fields have an error. Please check and try again.</span>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn yellow submit-application-btn">
                            Submit application

                        </button>
                    </div>



                </form>
            </div>
            </button>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Thank You Popup -->
<div id="thank-you-popup" class="thank-you-popup" style="display: none;">
    <div class="thank-you-popup__overlay">
        <div class="thank-you-popup__container">
            <div class="thank-you-popup__content">
                <div class="success-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="4" width="40" height="40" rx="20" fill="#B4E6CD" />
                        <rect x="4" y="4" width="40" height="40" rx="20" stroke="#E9F8F0" stroke-width="8" />
                        <path d="M24.001 16.1667C28.3272 16.1667 31.8348 19.6743 31.835 23.9997C31.835 28.3252 28.3273 31.8336 24.001 31.8336C19.6748 31.8335 16.168 28.3251 16.168 23.9997C16.1681 19.6744 19.6749 16.1668 24.001 16.1667ZM24.001 16.5563C19.8959 16.5565 16.5578 19.8946 16.5576 23.9997C16.5576 28.1049 19.8958 31.4438 24.001 31.444C28.1063 31.444 31.4453 28.105 31.4453 23.9997C31.4451 19.8945 28.1062 16.5563 24.001 16.5563Z" fill="#08AE66" stroke="#08AE66" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M27.9823 20.872C28.0995 21.0034 28.1654 21.1816 28.1654 21.3674C28.1654 21.5532 28.0995 21.7314 27.9823 21.8628L23.2951 27.1144C23.2332 27.1838 23.1597 27.2388 23.0787 27.2764C22.9978 27.314 22.911 27.3333 22.8234 27.3333C22.7358 27.3333 22.6491 27.314 22.5681 27.2764C22.4872 27.2388 22.4136 27.1838 22.3517 27.1144L20.0229 24.5056C19.9632 24.441 19.9156 24.3637 19.8828 24.2782C19.85 24.1927 19.8328 24.1008 19.8321 24.0077C19.8313 23.9147 19.8472 23.8224 19.8786 23.7363C19.91 23.6502 19.9565 23.572 20.0152 23.5062C20.0739 23.4404 20.1437 23.3884 20.2206 23.3532C20.2974 23.3179 20.3798 23.3002 20.4628 23.301C20.5458 23.3018 20.6279 23.3211 20.7042 23.3579C20.7805 23.3946 20.8495 23.448 20.9072 23.5149L22.8232 25.6617L27.0976 20.872C27.1557 20.8069 27.2247 20.7553 27.3005 20.72C27.3764 20.6848 27.4578 20.6667 27.5399 20.6667C27.6221 20.6667 27.7035 20.6848 27.7793 20.72C27.8552 20.7553 27.9242 20.8069 27.9823 20.872Z" fill="#08AE66" />
                    </svg>

                </div>
                <div class="thank-you-message">
                    <h2>Thank you, your application has been submitted successfully</h2>
                    <p>We will contact you shortly.</p>
                </div>

            </div>
            <button type="button" class=" thank-you-btn" id="thankYouCloseBtn">Ok</button>
        </div>
    </div>
</div>