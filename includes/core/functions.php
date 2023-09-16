<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once('translate.php');
require_once('functionUnicode.php');
require_once('dig_geo.php');
require_once('phandler.php');
require_once('qrcode.php');
require_once('digits_session.php');
require_once 'enqueue/enqueue_scripts.php';
require_once 'enqueue/gateway_scripts.php';


function digits_get_mobile($user_id)
{
    return get_user_meta($user_id, 'digits_phone', true);
}

function digits_get_mobile_country_code($user_id)
{
    $cc = get_user_meta($user_id, 'digt_countrycode', true);
    $phone = get_user_meta($user_id, 'digits_phone_no', true);
    if (empty($phone) || empty($cc)) {
        return null;
    }
    return array('country_code' => $cc, 'phone' => $phone);
}

function digits_update_mobile($user_id, $countrycode, $phone)
{
    update_user_meta($user_id, 'digt_countrycode', $countrycode);
    update_user_meta($user_id, 'digits_phone_no', $phone);
    update_user_meta($user_id, 'digits_phone', $countrycode . $phone);
}

function digits_delete_mobile($user_id)
{
    delete_user_meta($user_id, 'digt_countrycode');
    delete_user_meta($user_id, 'digits_phone_no');
    delete_user_meta($user_id, 'digits_phone');
}

function getCountryList()
{
    return array(
        "Afghanistan" => "93",
        "Albania" => "355",
        "Algeria" => "213",
        "American Samoa" => "1",
        "Andorra" => "376",
        "Angola" => "244",
        "Anguilla" => "1",
        "Antigua" => "1",
        "Argentina" => "54",
        "Armenia" => "374",
        "Aruba" => "297",
        "Australia" => "61",
        "Austria" => "43",
        "Azerbaijan" => "994",
        "Bahrain" => "973",
        "Bangladesh" => "880",
        "Barbados" => "1",
        "Belarus" => "375",
        "Belgium" => "32",
        "Belize" => "501",
        "Benin" => "229",
        "Bermuda" => "1",
        "Bhutan" => "975",
        "Bolivia" => "591",
        "Bonaire, Sint Eustatius and Saba" => "599",
        "Bosnia and Herzegovina" => "387",
        "Botswana" => "267",
        "Brazil" => "55",
        "British Indian Ocean Territory" => "246",
        "British Virgin Islands" => "1",
        "Brunei" => "673",
        "Bulgaria" => "359",
        "Burkina Faso" => "226",
        "Burundi" => "257",
        "Cambodia" => "855",
        "Cameroon" => "237",
        "Canada" => "1",
        "Cape Verde" => "238",
        "Cayman Islands" => "1",
        "Central African Republic" => "236",
        "Chad" => "235",
        "Chile" => "56",
        "China" => "86",
        "Colombia" => "57",
        "Comoros" => "269",
        "Cook Islands" => "682",
        "Ivory Coast" => "225",
        "Costa Rica" => "506",
        "Croatia" => "385",
        "Cuba" => "53",
        "Curaçao" => "599",
        "Cyprus" => "357",
        "Czech Republic" => "420",
        "Democratic Republic of the Congo" => "243",
        "Denmark" => "45",
        "Djibouti" => "253",
        "Dominica" => "1",
        "Dominican Republic" => "1",
        "Ecuador" => "593",
        "Egypt" => "20",
        "El Salvador" => "503",
        "Equatorial Guinea" => "240",
        "Eritrea" => "291",
        "Estonia" => "372",
        "Ethiopia" => "251",
        "Falkland Islands" => "500",
        "Faroe Islands" => "298",
        "Federated States of Micronesia" => "691",
        "Fiji" => "679",
        "Finland" => "358",
        "France" => "33",
        "French Guiana" => "594",
        "French Polynesia" => "689",
        "Gabon" => "241",
        "Georgia" => "995",
        "Germany" => "49",
        "Ghana" => "233",
        "Gibraltar" => "350",
        "Greece" => "30",
        "Greenland" => "299",
        "Grenada" => "1",
        "Guadeloupe" => "590",
        "Guam" => "1",
        "Guatemala" => "502",
        "Guernsey" => "44",
        "Guinea" => "224",
        "Guinea-Bissau" => "245",
        "Guyana" => "592",
        "Haiti" => "509",
        "Honduras" => "504",
        "Hong Kong" => "852",
        "Hungary" => "36",
        "Iceland" => "354",
        "India" => "91",
        "Indonesia" => "62",
        "Iran" => "98",
        "Iraq" => "964",
        "Ireland" => "353",
        "Isle Of Man" => "44",
        "Israel" => "972",
        "Italy" => "39",
        "Jamaica" => "1",
        "Japan" => "81",
        "Jersey" => "44",
        "Jordan" => "962",
        "Kazakhstan" => "7",
        "Kenya" => "254",
        "Kiribati" => "686",
        "Kuwait" => "965",
        "Kyrgyzstan" => "996",
        "Laos" => "856",
        "Latvia" => "371",
        "Lebanon" => "961",
        "Lesotho" => "266",
        "Liberia" => "231",
        "Libya" => "218",
        "Liechtenstein" => "423",
        "Lithuania" => "370",
        "Luxembourg" => "352",
        "Macau" => "853",
        "Macedonia" => "389",
        "Madagascar" => "261",
        "Malawi" => "265",
        "Malaysia" => "60",
        "Maldives" => "960",
        "Mali" => "223",
        "Malta" => "356",
        "Marshall Islands" => "692",
        "Martinique" => "596",
        "Mauritania" => "222",
        "Mauritius" => "230",
        "Mayotte" => "262",
        "Mexico" => "52",
        "Moldova" => "373",
        "Monaco" => "377",
        "Mongolia" => "976",
        "Montenegro" => "382",
        "Montserrat" => "1",
        "Morocco" => "212",
        "Mozambique" => "258",
        "Myanmar" => "95",
        "Namibia" => "264",
        "Nauru" => "674",
        "Nepal" => "977",
        "Netherlands" => "31",
        "New Caledonia" => "687",
        "New Zealand" => "64",
        "Nicaragua" => "505",
        "Niger" => "227",
        "Nigeria" => "234",
        "Niue" => "683",
        "Norfolk Island" => "672",
        "North Korea" => "850",
        "Northern Mariana Islands" => "1",
        "Norway" => "47",
        "Oman" => "968",
        "Pakistan" => "92",
        "Palau" => "680",
        "Palestine" => "970",
        "Panama" => "507",
        "Papua New Guinea" => "675",
        "Paraguay" => "595",
        "Peru" => "51",
        "Philippines" => "63",
        "Poland" => "48",
        "Portugal" => "351",
        "Puerto Rico" => "1",
        "Qatar" => "974",
        "Republic of the Congo" => "242",
        "Romania" => "40",
        "Reunion" => "262",
        "Russia" => "7",
        "Rwanda" => "250",
        "Saint Helena" => "290",
        "Saint Kitts and Nevis" => "1",
        "Saint Pierre and Miquelon" => "508",
        "Saint Vincent and the Grenadines" => "1",
        "Samoa" => "685",
        "San Marino" => "378",
        "Sao Tome and Principe" => "239",
        "Saudi Arabia" => "966",
        "Senegal" => "221",
        "Serbia" => "381",
        "Seychelles" => "248",
        "Sierra Leone" => "232",
        "Singapore" => "65",
        "Sint Maarten" => "1",
        "Slovakia" => "421",
        "Slovenia" => "386",
        "Solomon Islands" => "677",
        "Somalia" => "252",
        "South Africa" => "27",
        "South Korea" => "82",
        "South Sudan" => "211",
        "Spain" => "34",
        "Sri Lanka" => "94",
        "St. Lucia" => "1",
        "Sudan" => "249",
        "Suriname" => "597",
        "Swaziland" => "268",
        "Sweden" => "46",
        "Switzerland" => "41",
        "Syria" => "963",
        "Taiwan" => "886",
        "Tajikistan" => "992",
        "Tanzania" => "255",
        "Thailand" => "66",
        "The Bahamas" => "1",
        "The Gambia" => "220",
        "Timor-Leste" => "670",
        "Togo" => "228",
        "Tokelau" => "690",
        "Tonga" => "676",
        "Trinidad and Tobago" => "1",
        "Tunisia" => "216",
        "Turkey" => "90",
        "Turkmenistan" => "993",
        "Turks and Caicos Islands" => "1",
        "Tuvalu" => "688",
        "U.S. Virgin Islands" => "1",
        "Uganda" => "256",
        "Ukraine" => "380",
        "United Arab Emirates" => "971",
        "United Kingdom" => "44",
        "United States" => "1",
        "Uruguay" => "598",
        "Uzbekistan" => "998",
        "Vanuatu" => "678",
        "Venezuela" => "58",
        "Vietnam" => "84",
        "Wallis and Futuna" => "681",
        "Western Sahara" => "212",
        "Yemen" => "967",
        "Zambia" => "260",
        "Zimbabwe" => "263"
    );

}

function getTranslatedCountryName($countryName)
{
    $data = array(
        "Afghanistan" => "Afghanistan",
        "Albania" => "Albania",
        "Algeria" => "Algeria",
        "American Samoa" => "American Samoa",
        "Andorra" => "Andorra",
        "Angola" => "Angola",
        "Anguilla" => "Anguilla",
        "Antigua" => "Antigua",
        "Argentina" => "Argentina",
        "Armenia" => "Armenia",
        "Aruba" => "Aruba",
        "Australia" => "Australia",
        "Austria" => "Austria",
        "Azerbaijan" => "Azerbaijan",
        "Bahrain" => "Bahrain",
        "Bangladesh" => "Bangladesh",
        "Barbados" => "Barbados",
        "Belarus" => "Belarus",
        "Belgium" => "Belgium",
        "Belize" => "Belize",
        "Benin" => "Benin",
        "Bermuda" => "Bermuda",
        "Bhutan" => "Bhutan",
        "Bolivia" => "Bolivia",
        "Bonaire, Sint Eustatius and Saba" => "Bonaire, Sint Eustatius and Saba",
        "Bosnia and Herzegovina" => "Bosnia and Herzegovina",
        "Botswana" => "Botswana",
        "Brazil" => "Brazil",
        "British Indian Ocean Territory" => "British Indian Ocean Territory",
        "British Virgin Islands" => "British Virgin Islands",
        "Brunei" => "Brunei",
        "Bulgaria" => "Bulgaria",
        "Burkina Faso" => "Burkina Faso",
        "Burundi" => "Burundi",
        "Cambodia" => "Cambodia",
        "Cameroon" => "Cameroon",
        "Canada" => "Canada",
        "Cape Verde" => "Cape Verde",
        "Cayman Islands" => "Cayman Islands",
        "Central African Republic" => "Central African Republic",
        "Chad" => "Chad",
        "Chile" => "Chile",
        "China" => "China",
        "Colombia" => "Colombia",
        "Comoros" => "Comoros",
        "Cook Islands" => "Cook Islands",
        "Costa Rica" => "Costa Rica",
        "Croatia" => "Croatia",
        "Cuba" => "Cuba",
        "Curaçao" => "Curaçao",
        "Cyprus" => "Cyprus",
        "Czech Republic" => "Czech Republic",
        "Democratic Republic of the Congo" => "Democratic Republic of the Congo",
        "Denmark" => "Denmark",
        "Djibouti" => "Djibouti",
        "Dominica" => "Dominica",
        "Dominican Republic" => "Dominican Republic",
        "Ecuador" => "Ecuador",
        "Egypt" => "Egypt",
        "El Salvador" => "El Salvador",
        "Equatorial Guinea" => "Equatorial Guinea",
        "Eritrea" => "Eritrea",
        "Estonia" => "Estonia",
        "Ethiopia" => "Ethiopia",
        "Falkland Islands" => "Falkland Islands",
        "Faroe Islands" => "Faroe Islands",
        "Federated States of Micronesia" => "Federated States of Micronesia",
        "Fiji" => "Fiji",
        "Finland" => "Finland",
        "France" => "France",
        "French Guiana" => "French Guiana",
        "French Polynesia" => "French Polynesia",
        "Gabon" => "Gabon",
        "Georgia" => "Georgia",
        "Germany" => "Germany",
        "Ghana" => "Ghana",
        "Gibraltar" => "Gibraltar",
        "Greece" => "Greece",
        "Greenland" => "Greenland",
        "Grenada" => "Grenada",
        "Guadeloupe" => "Guadeloupe",
        "Guam" => "Guam",
        "Guatemala" => "Guatemala",
        "Guernsey" => "Guernsey",
        "Guinea" => "Guinea",
        "Guinea-Bissau" => "Guinea-Bissau",
        "Guyana" => "Guyana",
        "Haiti" => "Haiti",
        "Honduras" => "Honduras",
        "Hong Kong" => "Hong Kong",
        "Hungary" => "Hungary",
        "Iceland" => "Iceland",
        "India" => "India",
        "Indonesia" => "Indonesia",
        "Iran" => "Iran",
        "Iraq" => "Iraq",
        "Ireland" => "Ireland",
        "Isle Of Man" => "Isle Of Man",
        "Israel" => "Israel",
        "Italy" => "Italy",
        "Ivory Coast" => "Côte d'Ivoire",
        "Jamaica" => "Jamaica",
        "Japan" => "Japan",
        "Jersey" => "Jersey",
        "Jordan" => "Jordan",
        "Kazakhstan" => "Kazakhstan",
        "Kenya" => "Kenya",
        "Kiribati" => "Kiribati",
        "Kuwait" => "Kuwait",
        "Kyrgyzstan" => "Kyrgyzstan",
        "Laos" => "Laos",
        "Latvia" => "Latvia",
        "Lebanon" => "Lebanon",
        "Lesotho" => "Lesotho",
        "Liberia" => "Liberia",
        "Libya" => "Libya",
        "Liechtenstein" => "Liechtenstein",
        "Lithuania" => "Lithuania",
        "Luxembourg" => "Luxembourg",
        "Macau" => "Macau",
        "Macedonia" => "Macedonia",
        "Madagascar" => "Madagascar",
        "Malawi" => "Malawi",
        "Malaysia" => "Malaysia",
        "Maldives" => "Maldives",
        "Mali" => "Mali",
        "Malta" => "Malta",
        "Marshall Islands" => "Marshall Islands",
        "Martinique" => "Martinique",
        "Mauritania" => "Mauritania",
        "Mauritius" => "Mauritius",
        "Mayotte" => "Mayotte",
        "Mexico" => "Mexico",
        "Moldova" => "Moldova",
        "Monaco" => "Monaco",
        "Mongolia" => "Mongolia",
        "Montenegro" => "Montenegro",
        "Montserrat" => "Montserrat",
        "Morocco" => "Morocco",
        "Mozambique" => "Mozambique",
        "Myanmar" => "Myanmar",
        "Namibia" => "Namibia",
        "Nauru" => "Nauru",
        "Nepal" => "Nepal",
        "Netherlands" => "Netherlands",
        "New Caledonia" => "New Caledonia",
        "New Zealand" => "New Zealand",
        "Nicaragua" => "Nicaragua",
        "Niger" => "Niger",
        "Nigeria" => "Nigeria",
        "Niue" => "Niue",
        "Norfolk Island" => "Norfolk Island",
        "North Korea" => "North Korea",
        "Northern Mariana Islands" => "Northern Mariana Islands",
        "Norway" => "Norway",
        "Oman" => "Oman",
        "Pakistan" => "Pakistan",
        "Palau" => "Palau",
        "Palestine" => "Palestine",
        "Panama" => "Panama",
        "Papua New Guinea" => "Papua New Guinea",
        "Paraguay" => "Paraguay",
        "Peru" => "Peru",
        "Philippines" => "Philippines",
        "Poland" => "Poland",
        "Portugal" => "Portugal",
        "Puerto Rico" => "Puerto Rico",
        "Qatar" => "Qatar",
        "Republic of the Congo" => "Republic of the Congo",
        "Romania" => "Romania",
        "Reunion" => "Reunion",
        "Russia" => "Russia",
        "Rwanda" => "Rwanda",
        "Saint Helena" => "Saint Helena",
        "Saint Kitts and Nevis" => "Saint Kitts and Nevis",
        "Saint Pierre and Miquelon" => "Saint Pierre and Miquelon",
        "Saint Vincent and the Grenadines" => "Saint Vincent and the Grenadines",
        "Samoa" => "Samoa",
        "San Marino" => "San Marino",
        "Sao Tome and Principe" => "Sao Tome and Principe",
        "Saudi Arabia" => "Saudi Arabia",
        "Senegal" => "Senegal",
        "Serbia" => "Serbia",
        "Seychelles" => "Seychelles",
        "Sierra Leone" => "Sierra Leone",
        "Singapore" => "Singapore",
        "Sint Maarten" => "Sint Maarten",
        "Slovakia" => "Slovakia",
        "Slovenia" => "Slovenia",
        "Solomon Islands" => "Solomon Islands",
        "Somalia" => "Somalia",
        "South Africa" => "South Africa",
        "South Korea" => "South Korea",
        "South Sudan" => "South Sudan",
        "Spain" => "Spain",
        "Sri Lanka" => "Sri Lanka",
        "St. Lucia" => "St. Lucia",
        "Sudan" => "Sudan",
        "Suriname" => "Suriname",
        "Swaziland" => "Swaziland",
        "Sweden" => "Sweden",
        "Switzerland" => "Switzerland",
        "Syria" => "Syria",
        "Taiwan" => "Taiwan",
        "Tajikistan" => "Tajikistan",
        "Tanzania" => "Tanzania",
        "Thailand" => "Thailand",
        "The Bahamas" => "The Bahamas",
        "The Gambia" => "The Gambia",
        "Timor-Leste" => "Timor-Leste",
        "Togo" => "Togo",
        "Tokelau" => "Tokelau",
        "Tonga" => "Tonga",
        "Trinidad and Tobago" => "Trinidad and Tobago",
        "Tunisia" => "Tunisia",
        "Turkey" => "Turkey",
        "Turkmenistan" => "Turkmenistan",
        "Turks and Caicos Islands" => "Turks and Caicos Islands",
        "Tuvalu" => "Tuvalu",
        "U.S. Virgin Islands" => "U.S. Virgin Islands",
        "Uganda" => "Uganda",
        "Ukraine" => "Ukraine",
        "United Arab Emirates" => "United Arab Emirates",
        "United Kingdom" => "United Kingdom",
        "United States" => "United States",
        "Uruguay" => "Uruguay",
        "Uzbekistan" => "Uzbekistan",
        "Vanuatu" => "Vanuatu",
        "Venezuela" => "Venezuela",
        "Vietnam" => "Vietnam",
        "Wallis and Futuna" => "Wallis and Futuna",
        "Western Sahara" => "Western Sahara",
        "Yemen" => "Yemen",
        "Zambia" => "Zambia",
        "Zimbabwe" => "Zimbabwe",
    );

    return $data[$countryName];

}

function digits_get_ip()
{
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = '-';
    return $ipaddress;
}


function getCountryCode($country)
{
    if ($country == "") {
        return '';
    }
    $countryarray = getCountryList();

    if(isset($countryarray[$country])){
         return $countryarray[$country];
     }

    return '';

    $whiteListCountryCodes = get_option("whitelistcountrycodes");

    if (is_array($whiteListCountryCodes)) {
        $size = sizeof($whiteListCountryCodes);

        if ($size > 0) {
            if (!in_array($country, $whiteListCountryCodes)) {
                $defaultccode = get_option("dig_default_ccode");
                if (!in_array($defaultccode, $whiteListCountryCodes)) {
                    return $whiteListCountryCodes[0];
                } else {
                    return $defaultccode;
                }
            }
        }

    }
    return $country;
}

function digCountry()
{

    if (did_action('digits_country_code_list')) {
        return;
    }

    $countryList = getCountryList();
    $valCon = "";
    $currentCountry = getUserCountryCode();
    $whiteListCountryCodes = get_option("whitelistcountrycodes");
    $blacklistcountrycodes = get_option("dig_blacklistcountrycodes");

    $size = 0;
    if (is_array($whiteListCountryCodes)) {
        $size = sizeof($whiteListCountryCodes);
    }

    $is_mobile = wp_is_mobile();


    if (!$is_mobile) {
        $valCon .= '<li class="dig-cc-search-container search_field">';
        $valCon .= '<div class="dig-cc-search"><input autocomplete="off" placeholder="' . __('Search', 'digits') . '" type="text" class="countrycode_search regular-text" style="border-color: transparent !important"></div>';
        $valCon .= '</li>';
    }

    $country_codes_list = array_flip(dig_country_list());

    $flag_size = 48;
    $position = $flag_size;
    foreach ($countryList as $key => $value) {
        $ac = "";

        $position -= $flag_size;

        if (is_array($whiteListCountryCodes) && !empty($whiteListCountryCodes)) {
            if ($size > 0) {
                if (!in_array($key, $whiteListCountryCodes)) {
                    continue;
                }
            }
        }
        if (!empty($blacklistcountrycodes)) {
            if (in_array($key, $blacklistcountrycodes)) {
                continue;
            }
        }

        if ($currentCountry == '+' . $value) {
            $ac = "selected";
        }

        $style = 'style="background-position:' . $position . 'px 0"';
        $data_position = 'data-position="' . $position . 'px 0"';
        $data_country_code = 'data-country-code="' . $country_codes_list[$key] . '"';
        $valCon .= '<li class="dig-cc-visible ' . $ac . '" value="' . $value . '" data-country="' . strtolower($key) . '" ' . $data_position . ' ' . $data_country_code . '>';
        $valCon .= '<span class="untdovr_flag_container"><span class="untdovr_flag_container_flag" ' . $style . '></span></span>';
        $valCon .= getTranslatedCountryName($key) . ' (+' . $value . ')';
        $valCon .= '</li>';

    }

    $class = '';
    $stype = 'list';
    if ($is_mobile) {
        $stype = 'mobile';
        $class = 'digits-mobile-list';
        $valCon .= '<li class="spacer" disabled=""></li>';
    }


    $list = '<ul class="digit_cs-list digits_scrollbar ' . $class . '" style="display: none;" data-type="' . $stype . '">' . $valCon . '</ul>';

    if ($is_mobile) {
        $search = '<div class="digits-countrycode-search"><div class="digits-hide-countrycode"></div><input type="text" class="countrycode_search regular-text" placeholder="' . __('Search', 'digits') . '"></div>';
        $list = '<div class="digits-fullscreen">' . $list . $search . '</div>';
    }
    ?>
    <script type="text/template" id="digits_country_list_template">
        <?php echo $list; ?>
    </script>
    <div id="digits_country_list_wrapper"></div>
    <?php

    do_action('digits_country_code_list');
    digits_flags_sprite();
}

function digits_flags_sprite()
{
    $url = get_digits_asset_uri('/assets/images/flags-sprite.png');
    ?>
    <style>
        .untdovr_flag_container_flag {
            background-image: url("<?php echo $url;?>");
        }
    </style>
    <script>
        var country_list_template = document.getElementById('digits_country_list_template').innerHTML;
        document.getElementById('digits_country_list_wrapper').innerHTML = country_list_template;
    </script>
    <?php
}


function dig_sanitize($input)
{

    // Initialize the new array that will hold the sanitize values
    $new_input = array();

    // Loop through the input and sanitize each of the values
    foreach ($input as $key => $val) {
        $new_input[$key] = sanitize_text_field($val);
    }

    return $new_input;

}

function dig_array_sanitize_phone($input)
{

    if (empty($input) || !is_array($input)) {
        return $input;
    }
    $new_input = array();

    foreach ($input as $key => $val) {
        $value = dig_sanitize_phone_number($val);
        if (!empty($value)) {
            $new_input[$key] = $value;
        }
    }

    return $new_input;

}

function dig_sanitize_phone_number($phone)
{
    return preg_replace('/[^\d]/', '', $phone);
}


function dig_isWhatsAppEnabled()
{
    $whatsapp_gateway = get_option('digit_whatsapp_gateway', -1);

    return $whatsapp_gateway == -1 ? false : true;
}

function dig_securityKeysEnabled()
{
    $keys = get_option('digits_enable_security_devices', 1);

    return $keys == 1;
}

if (!function_exists('wpn_parse_message_template')) {
    function wpn_parse_message_template($message, $template_ids)
    {
        $value_separator = ':';
        $use_arrow = strpos($message, '=>') !== false;
        if ($use_arrow) {
            $value_separator = '=>';
        }

        $message_values = explode("\n", $message);
        $params = array();
        $template = array();
        $last_id = false;
        foreach ($message_values as $attr) {
            if (empty($attr)) continue;
            $obj = explode($value_separator, $attr, 2);
            if (sizeof($obj) !== 2) {
                /*continuation of previous variable*/
                if (!$last_id) {
                    continue;
                }
                if (in_array($last_id, $template_ids)) {
                    $template[$last_id] = $template[$last_id] . PHP_EOL . $attr;
                } else {
                    $params[$last_id] = $params[$last_id] . PHP_EOL . $attr;
                }
            } else {
                $id = trim($obj[0]);
                $value = trim($obj[1]);
                $last_id = $id;
                if (in_array($id, $template_ids)) {
                    $template[$id] = $value;
                } else {
                    $params[$id] = $value;
                }
            }
        }

        if (empty($template) && empty($params)) {
            return $message;
        }
        return array(
            'template' => $template,
            'params' => $params
        );
    }
}

if (!function_exists('digits_get_wa_gateway_templates')) {
    function digits_get_wa_gateway_templates($message, $otp)
    {
        $params = array();
        $blog_name = get_option('blogname');
        $domain = $_SERVER['SERVER_NAME'];
        $words = explode(" ", $message);
        $values = array($blog_name, $domain, $otp);
        $i = 1;
        foreach ($words as $word) {
            if (in_array($word, $values, true)) {
                $params[$i] = $word;
                $i++;
            }
        }
        if (empty($params)) {
            $params[$i] = $otp;
        }
        return $params;
    }
}