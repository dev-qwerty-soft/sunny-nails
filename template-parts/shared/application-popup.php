<?php
// Get user data for auto-fill if logged in
$user_name = '';
$user_email = '';
$user_phone = '';
$user_phone_country = '';

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    $user_email = $current_user->user_email;
    $user_phone = get_user_meta($current_user->ID, 'phone', true);
    $user_phone_country = get_user_meta($current_user->ID, 'phone_country', true);
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
                                required />
                            <label for="applicant-name">Name</label>
                        </div>

                        <div class="form-group">
                            <input type="email" id="applicant-email" name="applicant[email]" placeholder=" "
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
                                    required />
                            </div>
                            <label class="phone-label" for="applicant-phone">Phone Number</label>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn yellow submit-application-btn">
                            Submit application

                        </button>
                    </div>



                    <div class="form-errors global-form-error" style="display:none;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="24" height="24" rx="6" fill="white" />
                            <path d="M12.0002 5.33325C15.6822 5.33325 18.6668 8.31859 18.6668 11.9999C18.6668 15.6813 15.6822 18.6666 12.0002 18.6666C8.31816 18.6666 5.3335 15.6813 5.3335 11.9999C5.3335 8.31859 8.31816 5.33325 12.0002 5.33325ZM12.0002 6.44459C8.93683 6.44459 6.44483 8.93659 6.44483 11.9999C6.44483 15.0633 8.93683 17.5553 12.0002 17.5553C15.0635 17.5553 17.5555 15.0633 17.5555 11.9999C17.5555 8.93659 15.0635 6.44459 12.0002 6.44459ZM11.9995 13.6679C12.1761 13.6679 12.3455 13.7381 12.4704 13.863C12.5953 13.9879 12.6655 14.1573 12.6655 14.3339C12.6655 14.5106 12.5953 14.68 12.4704 14.8049C12.3455 14.9298 12.1761 14.9999 11.9995 14.9999C11.8229 14.9999 11.6535 14.9298 11.5286 14.8049C11.4037 14.68 11.3335 14.5106 11.3335 14.3339C11.3335 14.1573 11.4037 13.9879 11.5286 13.863C11.6535 13.7381 11.8229 13.6679 11.9995 13.6679ZM11.9962 8.66659C12.1171 8.66643 12.234 8.71011 12.3252 8.78954C12.4164 8.86898 12.4757 8.97877 12.4922 9.09859L12.4968 9.16592L12.4995 12.1673C12.4996 12.294 12.4516 12.4161 12.3652 12.5087C12.2788 12.6014 12.1603 12.6579 12.0339 12.6666C11.9075 12.6753 11.7824 12.6357 11.6841 12.5557C11.5857 12.4758 11.5214 12.3615 11.5042 12.2359L11.4995 12.1679L11.4968 9.16725C11.4967 9.10154 11.5096 9.03645 11.5347 8.97571C11.5598 8.91497 11.5966 8.85977 11.643 8.81327C11.6895 8.76677 11.7446 8.72988 11.8053 8.70471C11.866 8.67954 11.9304 8.66659 11.9962 8.66659Z" fill="#DC3232" />
                        </svg>
                        <span>One or more fields have an error. Please check and try again.</span>
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
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="40" cy="40" r="40" fill="#A7E8BD" />
                        <path d="M28 40L36 48L52 32" stroke="#1B5E20" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h2>Thank you, your application has been submitted successfully</h2>
                <p>We will contact you shortly.</p>
                <button type="button" class="btn yellow thank-you-btn" id="thankYouCloseBtn">Ok</button>
            </div>
        </div>
    </div>
</div>