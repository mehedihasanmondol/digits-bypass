<?php

if (!defined('ABSPATH')) {
    exit;
}


add_action('register_form', 'dig_wp_bp_reg_fields');
function dig_wp_bp_reg_fields()
{

    $wp_login_inte = get_option("dig_wp_login_inte", 0);
    if (is_account_page() || is_dig_page() || $wp_login_inte == 0) {
        return;
    }
    $dig_reg_details = digit_get_reg_fields();
    $reg_mobile_accept = $dig_reg_details['dig_reg_mobilenumber'];
    echo '<div class="dig_wp_bp_fields">';
    if ($reg_mobile_accept > 0) {
        ?>
        <div class="bbp-mobile">
            <label for="user_login"><?php _e('Mobile Number', 'digits'); ?> </label>
            <input type="text" name="" value="" id="username" data-dig-mob="1" only-mob="1">
        </div>
        <?php
    }
    wooc_extra_register_fields_custom();
    wooc_add_extra_otp_reg_field();
    echo '<input type="hidden" name="username" id="dig_reg_wp_elem" value="" />';
    ?>
    <input type="hidden" name="code" class="register_code"/>
    <input type="hidden" name="csrf" class="register_csrf"/>
    <?php

    echo '</div>';
}


add_action('wp_footer', 'add_ihc_login_secure_support');
function add_ihc_login_secure_support()
{
    if (is_user_logged_in()) {
        return;
    }

    if (defined('IHC_PATH')) {
        if (digits_make_third_party_secure()) {
            ?>
            <div class="digits_display_none" id="digits_ihc_form_secure_login">
                <?php
                digits_new_form_create_style();
                ?>
                <div class="wp_form_last_section form_last_section">
                    <input class="digits_secure_login-tp digits-form_submit-btn"
                           type="submit" value="<?php esc_attr_e('Continue', 'digits'); ?>" name="Submit">
                    <input type="hidden" name="ihc_form" value="1">
                    <?php
                    digits_render_tp_secure_form('ihc');
                    ?>
                </div>
            </div>
            <?php
        }
    }
}

add_action('login_form', 'dig_wp_bp_otp_box');
function dig_wp_bp_otp_box()
{
    $wp_login_inte = get_option("dig_wp_login_inte", 0);

    if (is_account_page() || is_dig_page() || $wp_login_inte == 0) {
        return;
    }

    if (digits_make_third_party_secure()) {


        $reauth = empty($_REQUEST['reauth']) ? false : true;
        if ($reauth) {
            wp_clear_auth_cookie();
        }

        digits_render_tp_secure_form('wp');

        digits_new_form_create_style();
        ?>
        <div class="wp_form_last_section form_last_section">
            <button onclick="return false"
                    class="button button-primary button-large digits_secure_login-tp digits-form_submit-btn"
            ><?php _e('Continue', 'digits'); ?></button>
            <input type="hidden" name="wp_form" value="1">
            <?php
            ?>
        </div>
        <?php
        return;
    }

    addNewUserNameInLogin(1, 'dig_bb_wp_otp_field');
    echo '<input type="hidden" name="user_name" id="dig_login_wp_elem" value="" />';
}


if (!function_exists('is_account_page')) {
    function is_account_page()
    {
        if (!function_exists('wc_get_page_id')) {
            return false;
        }
        $page_id = wc_get_page_id('myaccount');

        return ($page_id && is_page($page_id)) || wc_post_content_has_shortcode('woocommerce_my_account') || apply_filters('woocommerce_is_account_page', false);
    }
}


function dig_add_loader()
{

    $wp_login_inte = get_option("dig_wp_login_inte", 0);
    if ($wp_login_inte == 0) {
        return;
    }

    if (is_account_page() || is_dig_page()) {
        return;
    }

    ?>
    <style>
        .login #loginform .dig_wc_mobileLogin {
            margin-bottom: 20px;
        }

        .digor {
            display: inline-block;
        }

        #wp_bb_log_submit {
            width: 100% !important
        }

        .login #loginform .countrycode {
            max-width: 60px;
        }

        .login #loginform #dig_wc_log_otp {
            margin-bottom: 0px;
        }

        #wp_bb_log_submit {
            margin-bottom: 8px !important;
            font-size: 12px !important;
        }

        .login #loginform .dig_resendotp {
            font-size: 12px;
        }

        .wp_login {
            display: flex;
            flex-flow: row wrap;
        }

        .forgetmenot {
            width: 100%;
            padding-bottom: 16px;
        }

        .digcon {
            margin-bottom: 16px;
        }

        .digcon input {
            margin-bottom: 0 !important;
        }

        div.digor {
            display: block;
            height: 30px;
            font-size: 12px;
            line-height: 1;
            margin: 9px 7px 0px 7px;
            font-weight: 600;
        }

        .login-action-login .wp_login .dig_otp_block {
            top: 0;
            width: 100%;
            display: inline-block;
        }

        .submit {
            width: 100%;
            display: block;
        }

        .dig_wc_mobileLogin {
            width: 100%;
        }

        .login form {
            padding-bottom: 28px;
        }

        .wp_login p {
            width: 100%;
        }

        #dig_wc_log_otp_container {
            padding-bottom: 20px;
        }

        .login #loginform .dig_resendotp {
            margin-bottom: 0px !important;
        }

        .dig_otp_blk, .dig_otp_blk #wp_bb_log_submit {
            display: block;
            width: 100% !important;
        }

        <?php
if(is_rtl()){?>
        .dig_otp_block {
            float: left;
        }

        #wp-submit {
            float: right;

        <?php
}else{
            ?>
        .dig_otp_block {
            float: right;
        }

        #wp-submit {
            float: left;
        }

        <?php
        }

 ?>
    </style>

    <div class="dig_load_overlay">
        <div class="dig_load_content">
            <div class="dig_spinner">
                <div class="dig_double-bounce1"></div>
                <div class="dig_double-bounce2"></div>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function () {
            jQuery('#username').attr('removeStyle', 1);
        })
    </script>
    <?php
    digCountry();
}

add_action('login_header', 'dig_add_loader');


function dig_validate_new_user_custom_fields($errors, $user_login, $user_email)
{


    $wp_login_inte = get_option("dig_wp_login_inte", 0);
    if ($wp_login_inte == 0) {
        return $errors;
    }

    if (is_numeric($user_login)) {

        $user = getUserFromPhone($user_login);
        if ($user != null) {
            $errors->add("UsernameinUse", __("Username is already in use!", "digits"));
        }
    }


    $dig_reg_details = digit_get_reg_fields();
    $reg_mobile_accept = $dig_reg_details['dig_reg_mobilenumber'];
    if ($reg_mobile_accept > 0) {
        $phone = sanitize_mobile_field_dig($_POST['mobile/email']);
        if (empty($phone) && $reg_mobile_accept == 2) {
            $errors->add('phone', __('Please enter a valid Mobile Number', 'digits'));

            return $errors;
        } else if (!empty($phone)) {
            $code = sanitize_text_field($_POST['code']);
            $csrf = sanitize_text_field($_POST['csrf']);

            $otp = sanitize_text_field($_POST['reg_billing_otp']);

            $countrycode = sanitize_text_field($_POST['digt_countrycode']);


            if (!is_numeric($phone)) {

                $errors->add('phone', __('Please enter a valid Mobile Number', 'digits'));

                return $errors;
            }


            $mobVerificationFailed = __('Mobile Number verification failed', 'digits');
            if (dig_gatewayToUse($countrycode) == 1) {
                if (empty($code) || !wp_verify_nonce($csrf, 'crsf-otp')) {
                    $errors->add('phone', $mobVerificationFailed);

                    return $errors;
                }
                $json = getUserPhoneFromAccountkit($code);
                $phoneJson = json_decode($json, true);
                if ($json == null) {
                    $errors->add('phone', $mobVerificationFailed);

                    return $errors;

                }

                $mob = $countrycode . $phone;

                if ($phoneJson['phone'] != $mob) {
                    $errors->add('phone', $mobVerificationFailed);

                    return $errors;

                }

                $mob = $phoneJson['phone'];
                $phone = $phoneJson['nationalNumber'];
                $countrycode = $phoneJson['countrycode'];


            } else {
                if (empty($otp)) {
                    $errors->add('phone', __('Please enter a valid OTP', 'digits'));

                    return $errors;
                }
                if (verifyOTP($countrycode, $phone, $otp, true)) {

                    $mob = $countrycode . $phone;
                } else {
                    $errors->add('phone', $mobVerificationFailed);

                    return $errors;
                }
            }


            $user = getUserFromPhone($mob);
            if ($user == null) {
                global $dig_save_details;
                $dig_save_details = 1;
            } else {
                $errors->add("phone", __("Mobile Number is already in use!", "digits"));
            }

        }


    }


    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);
    $errors = validate_digp_reg_fields($reg_custom_fields, $errors);

    return $errors;

}

add_action('registration_errors', 'dig_validate_new_user_custom_fields', 10, 3);


function dig_update_new_user_fields_custom_fields($user_id)
{
    global $dig_save_details;


    $wp_login_inte = get_option("dig_wp_login_inte", 0);
    if ($wp_login_inte == 0) {
        return;
    }

    if ($dig_save_details == 1) {
        $phone = sanitize_mobile_field_dig($_POST['mobile/email']);
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);

        if (!empty($phone) && !empty($countrycode)) {
            update_user_meta($user_id, 'digt_countrycode', $countrycode);
            update_user_meta($user_id, 'digits_phone_no', $phone);
            update_user_meta($user_id, 'digits_phone', $countrycode . $phone);
        }
    }


    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);
    update_digp_reg_fields($reg_custom_fields, $user_id);

}

add_action('register_new_user', 'dig_update_new_user_fields_custom_fields');


add_action('authenticate', 'dig_authenticate', 100);
function dig_authenticate($user)
{
    if (!empty($_POST['mobile/email']) && !is_wp_error($user)) {

        $dig_login_details = digit_get_login_fields();
        $mobile = sanitize_mobile_field_dig($_POST['mobile/email']);

        if (($dig_login_details['dig_login_email'] == 0 && isValidEmail($mobile))) {
            return new WP_Error('error', __('Please use mobile number to login!', 'digits'));
        }
    }
    return $user;
}

add_action('wp_authenticate', 'dig_update_username');
function dig_update_username(&$username)
{


    if (isset($_POST['digt_countrycode']) && isset($_POST['log']) && !isset($_POST['mobile/email'])) {
        $_POST['mobile/email'] = $_POST['log'];
    }


    if (!empty($_POST['mobile/email'])) {

        $dig_login_details = digit_get_login_fields();


        $mobile = sanitize_mobile_field_dig($_POST['mobile/email']);

        if (($dig_login_details['dig_login_email'] == 0 && isValidEmail($mobile))) {
            return new WP_Error('error', __('Invalid Details!', 'digits'));
        }

        if (isset($_POST['digt_countrycode']) && isset($_POST['mobile/email'])) {
            $countrycode = sanitize_text_field($_POST['digt_countrycode']);


            if (!empty($countrycode) && !empty($mobile)) {

                $user = getUserFromPhone($countrycode . $mobile);

                if (!empty($user) && $dig_login_details['dig_login_mobilenumber'] > 0) {
                    $username = $user->user_login;
                }

            }
        }
    }

}


function is_dig_page()
{
    global $dig_logingpage;
    if ($dig_logingpage == 1) {
        return true;
    } else {
        return false;
    }
}


function login_function()
{

    add_filter('gettext', 'dig_update_login_label', 20, 3);
    if (!function_exists('dig_update_login_label')) {
        function dig_update_login_label($translated_text, $text, $domain)
        {

            $wp_login_inte = get_option("dig_wp_login_inte", 0);
            if ($wp_login_inte == 0) {
                return $translated_text;
            }

            if ($text === 'Username or Email Address') {
                $dig_login_details = digit_get_login_fields();
                if ($dig_login_details['dig_login_mobilenumber'] > 0) {
                    $translated_text = __('Email/Mobile Number', 'digits');
                }
            }

            return $translated_text;
        }
    }
}

add_action('login_head', 'login_function');


function dig_url_language()
{
    if (isset($_GET['lang'])) {
        return '&lang=' . esc_attr($_GET['lang']);
    } else {
        return '';
    }
}


function dig_redirect_wc_myaccount()
{
    if (is_user_logged_in()) {
        return;
    }
    $dig_redirect_wc_to_dig = get_option('dig_redirect_wc_to_dig', 0);

    if ($dig_redirect_wc_to_dig == 1) {
        if (function_exists('is_account_page')) {
            if (is_account_page()) {
                global $wp;
                $page = 1;
                if (isset($wp->query_vars['lost-password'])) {
                    $page = 3;
                    if (isset($_GET['key']) || isset($_GET['show-reset-form'])) {
                        return;
                    }
                }
                if (isset($_GET['action'])) {
                    if ($_GET['action'] == 'register') {
                        $page = 2;
                    }
                }
                $redirect = '';
                if (!empty($_POST['redirect'])) {
                    $redirect = wp_unslash($_POST['redirect']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                } elseif (wc_get_raw_referer()) {
                    $redirect = wc_get_raw_referer();
                }

                $args = array(
                    'login' => 'true',
                    'back' => 'home',
                    'page' => $page
                );

                $url = get_home_url();
                if (!empty($redirect)) {
                    $args['redirect_to'] = urlencode($redirect);
                    $url = $redirect;
                    unset($args['back']);
                }

                $url = add_query_arg($args, $url);

                wp_safe_redirect($url);
                die();
            }
        }
    }
}

add_action('template_redirect', 'dig_redirect_wc_myaccount');


add_filter('logout_url', 'digits_wp_wc_redirect_logout_uri', 10, 2);
function digits_wp_wc_redirect_logout_uri($logout_url, $redirect)
{
    $custom_logout_url = get_option("digits_logoutred");

    if (!empty($custom_logout_url)) {

        $new_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $query = parse_url($new_url, PHP_URL_QUERY);

// Returns a string if the URL has parameters or NULL if not
        if ($query) {
            $new_url .= '&logout=true&lnounce=' . wp_create_nonce("lnounce");
        } else {
            $new_url .= '?logout=true&lnounce=' . wp_create_nonce("lnounce");
        }

        $wc_url = '';
        if (function_exists('wc_get_page_permalink')) {
            $wc_url = apply_filters('woocommerce_logout_default_redirect_url', wc_get_page_permalink('myaccount'));
        }
        if ($wc_url == $redirect) {
            return $new_url;
        }
    }
    return $logout_url;
}


add_action('init', 'digits_redirect_wp_login');
function digits_redirect_wp_login()
{
    global $pagenow;
    if ('wp-login.php' == $pagenow) {
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'logout') {
            return;
        }
        $wp_login_hide = get_option("dig_wp_login_hide", 0);

        if ($wp_login_hide == 1) {
            $url = digits_get_login_uri('');
            wp_redirect($url);
            exit();
        }
    }
}

add_action('register_url', 'digits_register_url');
function digits_register_url($url)
{
    $url = digits_get_login_uri('register');

    return $url;
}

function digits_get_login_uri($type)
{
    $url = home_url();
    $query = array(
        'login' => 'true',
    );

    if (!empty($type)) {
        $query['type'] = $type;
    }
    if (isset($_REQUEST['redirect_to'])) {
        $query['redirect_to'] = urlencode($_REQUEST['redirect_to']);
    }
    $url = add_query_arg($query, $url);

    return $url;
}
