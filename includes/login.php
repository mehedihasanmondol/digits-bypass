<?php


if (!defined('ABSPATH')) {
    exit;
}

add_action("wp_ajax_nopriv_digits_resendotp", "digits_resendotp");

add_action("wp_ajax_digits_resendotp", "digits_resendotp");

function digits_resendotp()
{

    $countrycode = sanitize_text_field($_REQUEST['countrycode']);
    $mobileno = sanitize_mobile_field_dig($_REQUEST['mobileNo']);
    $csrf = $_REQUEST['csrf'];
    $login = $_REQUEST['login'];

    if (dig_gatewayToUse($countrycode) == 1) {
        die();
    }
    if (!checkwhitelistcode($countrycode)) {
        echo "-99";
        die();
    }

    if (!wp_verify_nonce($csrf, 'dig_form')) {
        echo '0';
        die();
    }

    $users_can_register = get_option('dig_enable_registration', 1);
    $digforgotpass = get_option('digforgotpass', 1);
    if ($users_can_register == 0 && $login == 2) {
        echo "0";
        die();
    }
    if ($digforgotpass == 1 && $login == 3) {
        echo "0";
        die();
    }

    if (OTPexists($countrycode, $mobileno, true)) {
        digits_check_mob();
    }
    echo "0";
    die();

}


add_action("wp_ajax_nopriv_digits_verifyotp_login", "digits_verifyotp_login", 10);

add_action("wp_ajax_digits_verifyotp_login", "digits_verifyotp_login", 10);

function dig_checkblacklist($code)
{
    $blacklistcountrycodes = get_option("dig_blacklistcountrycodes");

    if (!empty($blacklistcountrycodes)) {
        if (is_array($blacklistcountrycodes) && sizeof($blacklistcountrycodes) > 0) {
            $countryarray = getCountryList();
            $code = str_replace("+", "", $code);
            foreach ($countryarray as $key => $value) {
                if ($value == $code) {
                    if (in_array($key, $blacklistcountrycodes)) {
                        return true;
                    }
                }

            }
        }
    }

    return false;
}

function checkwhitelistcode($code)
{

    if (empty($code) || $code == '+' || !is_numeric($code)) {
        return false;
    }


    $whiteListCountryCodes = get_option("whitelistcountrycodes");

    if (!empty($whiteListCountryCodes)) {
        $size = sizeof($whiteListCountryCodes);
        if ($size > 0 && is_array($whiteListCountryCodes)) {

            $countryarray = getCountryList();
            $code = str_replace("+", "", $code);

            foreach ($countryarray as $key => $value) {
                if ($value == $code) {
                    if (in_array($key, $whiteListCountryCodes)) {
                        return true;
                    }
                }

            }

            return false;
        }
    }

    $check_blacklist = dig_checkblacklist($code);
    if ($check_blacklist) {
        return false;
    }

    if (empty($whiteListCountryCodes)) {
        return true;
    }

    return true;

}

function digits_verifyotp_login()
{

    $countrycode = sanitize_text_field($_REQUEST['countrycode']);

    if (dig_gatewayToUse($countrycode) == 1) {
        die();
    }


    if (!checkwhitelistcode($countrycode)) {
        echo "-99";
        die();
    }


    $mobileno = sanitize_mobile_field_dig($_REQUEST['mobileNo']);
    $csrf = $_REQUEST['csrf'];
    $otp = sanitize_text_field($_REQUEST['otp']);
    $del = false;


    $users_can_register = get_option('dig_enable_registration', 1);
    $digforgotpass = get_option('digforgotpass', 1);
    if (($users_can_register == 0 && $_REQUEST['dtype'] == 2) || ($digforgotpass == 0 && $_REQUEST['dtype'] == 3)
        || !wp_verify_nonce($csrf, 'dig_form')
    ) {
        wp_send_json(array(
            'success' => false,
            'data' => array('msg' => __('Error', 'digits'), 'level' => 2)
        ));
        die();
    }


    if ($_REQUEST['dtype'] == 1) {
        $del = true;
    }

    $rememberMe = false;
    if (isset($_REQUEST['rememberMe']) && $_REQUEST['rememberMe'] == 'true') {
        $rememberMe = true;
    }

    if (verifyOTP($countrycode, $mobileno, $otp, $del)) {

        $user1 = getUserFromPhone($countrycode . $mobileno);
        if ($user1) {

            if ($_REQUEST['dtype'] == 1) {
                wp_set_current_user($user1->ID, $user1->user_login);
                wp_set_auth_cookie($user1->ID, $rememberMe);
                do_action('wp_login', $user1->user_login, $user1);

                $uri = get_option("digits_loginred",'');

                $redirect_url = apply_filters('digits_login_redirect', $uri);
                if (!empty($redirect_url)) {
                    wp_send_json(array(
                        'success' => true,
                        'data' => array(
                            'code' => 1,
                            'msg' => __('Login Successful, Redirecting..', 'digits'),
                            'redirect' => $redirect_url
                        )
                    ));
                }

                wp_send_json(array(
                    'success' => true,
                    'data' => array(
                        'code' => 11
                    )
                ));

                die();

            } else {
                wp_send_json(array(
                    'success' => true,
                    'data' => array(
                        'code' => 1
                    )
                ));

                die();
            }

        } else {
            wp_send_json(array(
                'success' => true,
                'data' => array(
                    'code' => -1
                )
            ));

            die();
        }


    } else {
        wp_send_json(array(
            'success' => false,
            'data' => array(
                'code' => 0
            )
        ));

        die();
    }

}

add_action("wp_ajax_nopriv_digits_check_mob", "digits_check_mob", 10);
add_action("wp_ajax_digits_check_mob", "digits_check_mob", 10);


function sanitize_mobile_field_dig($mobile)
{
    $pl = '';
    if (substr($mobile, 0, 1) == '+') {
        $pl = '+';
    }
    $mobile = apply_filters('digits_filter_mobile', $mobile);
    $mobile = $pl . preg_replace('/[\s+()-]+/', '', $mobile);

    return ltrim(sanitize_text_field($mobile), '0');
}

add_filter('digits_filter_mobile', 'digits_arabic_persian_filter');
function digits_arabic_persian_filter($mobile)
{
    $fromchar = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹',
        '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');

    $num = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

    return str_replace($fromchar, $num, $mobile);

}

function dig_is_phone_no_allowed($phone)
{
    $deny_list = get_option('dig_phonenumberdenylist');
    if (!empty($deny_list)) {
        $phone = dig_sanitize_phone_number($phone);
        if (in_array($phone, $deny_list)) {
            return false;
        }
    }
    return true;
}

function digits_check_mob()
{

    if (session_id() == '') {
        session_start();
    }

    $data = array();

    $dig_login_details = digit_get_login_fields();
    $mobileaccp = $dig_login_details['dig_login_mobilenumber'];
    $otpaccp = $dig_login_details['dig_login_otp'];
    $countrycode = sanitize_text_field($_REQUEST['countrycode']);


    if (!empty($countrycode) &&
        (!is_numeric($countrycode) ||
            strpos($countrycode, '+') !== 0)) {
        wp_send_json_error(array('message' => __('Please enter a valid country code!', 'digits')));
        die();
    }

    $digit_gateway = dig_gatewayToUse($countrycode);
    if (dig_isWhatsAppEnabled()) {
        if (isset($_POST['whatsapp'])) {
            if ($_POST['whatsapp'] == 1) {
                $digit_gateway = -1;
            }
        }
    }
    if ($digit_gateway == 1) {
        $data['accountkit'] = 1;
    } else {
        $data['accountkit'] = 0;
    }

    if ($digit_gateway == 13) {
        $data['firebase'] = 1;
    } else {
        $data['firebase'] = 0;
    }

    $mobileno = sanitize_mobile_field_dig($_REQUEST['mobileNo']);
    $csrf = $_REQUEST['csrf'];
    $login = $_REQUEST['login'];


    if (!wp_verify_nonce($csrf, 'dig_form')) {
        $data['code'] = 0;
        digit_send_json_status($data);
        die();
    }


    if (isset($_POST['captcha']) && isset($_POST['captcha_ses'])) {
        $ses = filter_var($_POST['captcha_ses'], FILTER_SANITIZE_NUMBER_FLOAT);
        if (isset($_SESSION['dig_captcha' . $ses])) {
            if ($_POST['captcha'] != $_SESSION['dig_captcha' . $ses]) {
                wp_send_json_error(array('message' => __('Please enter a valid captcha!', 'digits')));
                die();
            }
        }
    }

    $users_can_register = get_option('dig_enable_registration', 1);
    $digforgotpass = get_option('digforgotpass', 1);
    if ($users_can_register == 0 && $login == 2) {
        $data['code'] = 0;
        wp_send_json_error(array('message' => __('Registration is disabled!', 'digits')));
        die();
    }

    if ($digforgotpass == 0 && $login == 3) {
        $data['code'] = 0;
        wp_send_json_error(array('message' => __('Forgot Password is disabled!', 'digits')));
        die();
    }

    if ($login == 2 || $login == 11) {
        $result = false;
        if (isset($_POST['username']) && !empty($_POST['username'])) {
            $username = sanitize_text_field($_POST['username']);
            if (username_exists($username)) {
                wp_send_json_error(array('message' => __('Username is already in use!', 'digits')));
                die();
            }
            $result = true;
        }
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $email = sanitize_text_field($_POST['email']);

            $validation_error = new WP_Error();
            $validation_error = apply_filters('digits_validate_email', $validation_error, $email);

            if ($validation_error->get_error_code()) {
                wp_send_json_error(array('message' => $validation_error->get_error_message()));
                die();
            }


            if (email_exists($email)) {
                if ($login == 11) {
                    $user = get_user_by('email', $email);
                    if ($user->ID != get_current_user_id()) {
                        wp_send_json_error(array('message' => __('Email is already in use!', 'digits')));
                        die();
                    }

                } else {
                    wp_send_json_error(array('message' => __('Email is already in use!', 'digits')));
                    die();
                }
            }
            $result = true;

        }

        if (empty($mobileno) && $result = true) {
            $data['code'] = 1;
            digit_send_json_status($data);
            die();
        }


    }


    if (($otpaccp == 0 && $login == 1) || ($mobileaccp == 0 && $login == 1)) {
        $data['code'] = -99;
        $data['message'] = __('Error', ' digits');
        digit_send_json_status($data);
        die();
    }

    if (!checkwhitelistcode($countrycode) || empty($countrycode)) {
        $data['code'] = -99;
        $data['message'] = __('At the moment, we do not allow users from your country', ' digits');
        digit_send_json_status($data);
        die();
    }

    $is_phone_allowed = dig_is_phone_no_allowed($countrycode . $mobileno);
    if (!$is_phone_allowed) {
        $data['code'] = -1;
        $data['message'] = __('Mobile Number not allowed!', ' digits');
        digit_send_json_status($data);
        die();
    }

    $user1 = getUserFromPhone($countrycode . $mobileno);
    if (($user1 != null && $login == 11) || ($user1 != null && $login == 2)) {

        $data['code'] = -1;
        $data['message'] = __('Mobile Number already in use!', ' digits');
        digit_send_json_status($data);
        die();
    }

    if ($user1 != null) {
        $validate_user = new WP_Error();

        if ($login == 1) {
            $validate_user = apply_filters('digits_check_user_login', $validate_user, $user1);
        } else if ($login == 3) {
            $validate_user = apply_filters('digits_check_user_forgotpass', $validate_user, $user1);
        }
        if (!empty($validate_user->get_error_code())) {
            $data['code'] = -1;
            $data['message'] = $validate_user->get_error_message();
            if ($validate_user->get_error_code() == 'notice') {
                $data['notice'] = 1;
            }
            wp_send_json_error($data);
            die();
        }
    }

    $request_type = '';
    switch ($request_type) {
        case 1:
            $request_type = 'login';
            break;
        case 2:
            $request_type = 'register';
            break;
        case 3:
            $request_type = 'forgot';
            break;
        case 11:
            $request_type = 'Account Update';
            break;
        case 101:
            $request_type = 'Phone Verification';
            break;
    }

    if ($user1 != null || $login == 2 || $login == 11 || $login == 101) {

        if (!digits_validate_phone($countrycode . $mobileno)) {
            wp_send_json_error(array('message' => __('Please enter a valid mobile number', 'digits')));
        }

        if ($login == 101) {
            $allow = apply_filters('digits_allow_only_mobile_verfication', true, $login);
            if (is_wp_error($allow) || !$allow) {
                $data['code'] = 0;
                if (is_wp_error($allow)) {
                    $data['message'] = $allow->get_error_message();
                    if ($allow->get_error_code() == 'notice') {
                        $data['notice'] = 1;
                    }
                } else {
                    $data['message'] = __('Error', ' digits');
                }
                wp_send_json_error($data);
            }
        }

        if(empty($email)){
            $email = '';
        }

        $check_request = digits_check_request($countrycode . $mobileno, $email);

        if ($check_request instanceof WP_Error) {
            wp_send_json_error(array('message' => $check_request->get_error_message()));
        }

        if ($digit_gateway == 1 || $digit_gateway == 13) {
            $result = 1;
        } else {
            $result = digit_create_otp($countrycode, $mobileno, $request_type);
        }
        $data['code'] = $result;
        digit_send_json_status($data);


        die();
    } else {
        digit_send_json_status(array(
            'code' => -11,
            'message' => __('Please signup before logging in.', 'digits')
        ));
        die();
    }

    digit_send_json_status(array('code' => 0));
    die();

}


function digit_send_json_status($data)
{
    if (isset($_REQUEST['json'])) {
        wp_send_json($data);
    } else {
        echo $data['code'];
    }
    die();
}

function digit_create_otp($countrycode, $mobileno, $request_type)
{
    $digit_gateway = dig_gatewayToUse($countrycode);

    $mode = 'sms';

    if (dig_isWhatsAppEnabled()) {
        if (isset($_POST['whatsapp'])) {
            if ($_POST['whatsapp'] == 1) {
                $digit_gateway = -1;
                $mode = 'whatsapp';

            }
        }
    }

    if ($digit_gateway != 13) {

        if (OTPexists($countrycode, $mobileno)) {
            return "1";
        }

        $code = dig_get_otp();


        if (!digit_send_otp($digit_gateway, $countrycode, $mobileno, $code, false, $request_type)) {
            return "0";
        }


        $mobileVerificationCode = md5($code);

        global $wpdb;
        $table_name = $wpdb->prefix . "digits_mobile_otp";

        $db = $wpdb->replace($table_name, array(
            'countrycode' => $countrycode,
            'mobileno' => $mobileno,
            'otp' => $mobileVerificationCode,
            'time' => date("Y-m-d H:i:s", strtotime("now"))
        ), array(
                '%d',
                '%s',
                '%s',
                '%s'
            )
        );

        if (!$db) {
            return "0";

        }

    }

    return "1";

}


function digits_default_otp_template()
{
    return 'Your OTP is {OTP} ' . PHP_EOL . '@{DOMAIN} #{OTP}';
}

add_action("wp_loaded", "digits_load_gateways");
function digits_load_gateways()
{
    require_once(dirname(__FILE__) . '/gateways.php');
}

if (!function_exists('digit_send_otp')) {

    function digit_send_otp($digit_gateway, $countrycode, $mobile, $otp, $testCall, $request_type)
    {
        if (empty($countrycode) || $countrycode == '+') {
            return false;
        }

        $dig_messagetemplate = get_option("dig_messagetemplate", digits_default_otp_template());

        $whatsapp = false;
        if (dig_isWhatsAppEnabled() || $testCall) {
            if (isset($_POST['whatsapp'])) {
                if ($_POST['whatsapp'] == 1) {
                    $digit_gateway = -1;
                    $whatsapp = true;
                }
            }
        }
        if ($whatsapp) {
            $dig_messagetemplate = get_option("dig_whatsapp_messagetemplate", $dig_messagetemplate);
        }

        if ($whatsapp) {
            $route = 'whatsapp';
        } else {
            $route = 'sms';
        }


        $blog_name = get_option('blogname');

        $domain = $_SERVER['SERVER_NAME'];
        $placeholders = array('%NAME%', '{NAME}', '%OTP%', '{OTP}', '{DOMAIN}');
        $values = array($blog_name, $blog_name, $otp, $otp, $domain);

        $dig_messagetemplate = str_replace($placeholders, $values, $dig_messagetemplate);


        $dig_messagetemplate = apply_filters('dig_messagetemplate', $dig_messagetemplate, $digit_gateway, $countrycode, $mobile, $otp);


        $result = digit_send_message($digit_gateway, $countrycode, $mobile, $otp, $dig_messagetemplate, $testCall, $whatsapp);

        digits_add_request_log($countrycode . $mobile, $route, $request_type, $dig_messagetemplate, $digit_gateway);

        return $result;
    }

}

add_action("wp_ajax_nopriv_digits_login_user", "digits_login_user", 10);


function digits_login_user()
{


    $code = sanitize_text_field($_REQUEST['code']);
    $csrf = sanitize_text_field($_REQUEST['csrf']);


    $dig_login_details = digit_get_login_fields();
    $mobileaccp = $dig_login_details['dig_login_mobilenumber'];
    $otpaccp = $dig_login_details['dig_login_otp'];


    if (!wp_verify_nonce($csrf, 'crsf-otp') || $mobileaccp == 0 || $otpaccp == 0) {
        echo '0';
        die();
    }


    $json = getUserPhoneFromAccountkit($code);

    $phoneJson = json_decode($json, true);


    $phone = $phoneJson['phone'];


    $rememberMe = false;

    if (isset($_REQUEST['rememberMe']) && $_REQUEST['rememberMe'] == 'true') {
        $rememberMe = true;
    }


    if ($json != null) {
        $user1 = getUserFromPhone($phone);
        if ($user1) {
            wp_set_current_user($user1->ID, $user1->user_login);
            wp_set_auth_cookie($user1->ID, $rememberMe);

            do_action('wp_login', $user1->user_login, $user1);
            $redirect_url = apply_filters('digits_login_redirect', '');
            if (!empty($redirect_url)) {
                wp_send_json(array(
                    'success' => true,
                    'data' => array(
                        'code' => 1,
                        'msg' => __('Login Successful, Redirecting..', 'digits'),
                        'redirect' => $redirect_url
                    )
                ));
            }

            echo '1';
            die();
        } else {
            echo '-1';
            die();
        }
    } else {
        echo '-9';
        die();
    }


    echo '0';
    die();
}

if (!function_exists('dig_get_otp')) {
    function dig_get_otp($isPlaceHolder = false)
    {
        $dig_otp_size = get_option("dig_otp_size", 6);


        $code = "";
        for ($i = 0; $i < $dig_otp_size; $i++) {
            if (!$isPlaceHolder) {
                $code .= rand(0, 9);
            } else {
                $code .= '-';
            }

        }

        $code = apply_filters('digits_otp', $code, $isPlaceHolder);

        return $code;
    }
}

function digits_test_api()
{

    if (!current_user_can('manage_options')) {
        echo '0';
        die();
    }

    $gateway = sanitize_text_field($_POST['gateway']);

    if (!empty($_REQUEST['email'])) {
        $email = $_REQUEST['email'];
        if (!isValidEmail($email)) {
            _e('Invalid Email Address', 'digits');
            die();
        }
        $result = digit_send_test_email($gateway, $email);
    } else {

        $mobile = sanitize_text_field($_POST['digt_mobile']);
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        if (empty($mobile) || !is_numeric($mobile) || empty($countrycode) || !is_numeric($countrycode)) {
            _e('Invalid Mobile Number', 'digits');
            die();
        }

        $code = dig_get_otp();

        $result = digit_send_otp($gateway, $countrycode, $mobile, $code, true, 'test call');
        if (!$result) {
            _e('Error', 'digits');
            die();
        }
    }
    print_r($result);
    die();

}

add_action('wp_ajax_digits_test_api', 'digits_test_api');


function dig_validate_login_captcha($del = true)
{
    if (session_id() == '') {
        session_start();
    }

    $ses = filter_var($_POST['dig_captcha_ses'], FILTER_SANITIZE_NUMBER_FLOAT);
    if ($_POST['digits_reg_logincaptcha'] != $_SESSION['dig_captcha' . $ses]) {
        return false;
    } else if (isset($_SESSION['dig_captcha' . $ses])) {
        if ($del) {
            unset($_SESSION['dig_captcha' . $ses]);
        }

        return true;
    }

}
