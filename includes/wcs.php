<?php

if (!defined('ABSPATH')) {
    exit;
}

function dig_wc_search_usr($found_customers)
{

    if (!current_user_can('edit_shop_orders')) {
        wp_die(-1);
    }

    if (empty($_GET['term'])) {
        return $found_customers;
    }

    $term = wc_clean(stripslashes($_GET['term']));

    if (!is_numeric($term)) {
        return $found_customers;
    }


    $ids = getUserIDSfromPhone($term);

    if (empty($ids) || !is_array($ids)) {
        return $found_customers;
    }

    if (count($ids) == 0) {
        return $found_customers;
    }

    if (!empty($_GET['exclude'])) {
        $ids = array_diff($ids, (array)$_GET['exclude']);
    }
    foreach ($ids as $id) {
        $customer = new WC_Customer($id);
        /* translators: 1: user display name 2: user ID 3: user email */
        $found_customers[$id] = sprintf(
            esc_html__('%1$s (#%2$s &ndash; %3$s)', 'woocommerce'),
            $customer->get_first_name() . ' ' . $customer->get_last_name(),
            $customer->get_id(),
            $customer->get_email()
        );
    }

    return $found_customers;


}

add_action('woocommerce_json_search_found_customers', 'dig_wc_search_usr');


add_filter('wp_get_nav_menu_items', 'dig_upg_menu_accd', 1, 3);
function dig_upg_menu_accd($items, $menu, $args)
{
    if (function_exists('get_current_screen')) {
        $screeen = get_current_screen();

        if ($screeen != null && $screeen->base == 'nav-menus') {
            return $items;
        }
    }

    if (is_user_logged_in()) {
        $hide_items = array(
            "dm-login-page",
            "dm-login-modal",
            "[digits-registration]",
            "[digits-forgot-password]",
            "[digits-page-registration]",
            "[digits-page-forgot-password]"
        ,
            "[dm-registration-page]",
            "[dm-registration-modal]",
            "[dm-forgot-password-page]",
            "[dm-forgot-password-modal]",
            "dm-signup-modal",
            "dm-signup-page"
        );
    } else {
        $hide_items = array("[digits-logout]", "[dm-logout]");
    }
    foreach ($items as $i => $item) {
        $menu_item = $item->post_title;
        if (in_array($menu_item, $hide_items)) {
            unset($items[$i]);
        }
    }

    return $items;
}


function dig_verify_otp_box()
{


    $otp_placeholder = dig_get_otp(true);
    $otp_size = strlen($otp_placeholder);

    ?>
    <div class="dig_verify_mobile_otp_container" style="display: none;">
        <div class="dig_verify_mobile_otp">
            <div class="dig_verify_code_text dig_verify_code_head dig_sml_box_msg_head"><?php _e('Verification Code', 'digits'); ?></div>
            <div class="dig_verify_code_text dig_verify_code_msg dig_sml_box_msg"><?php echo sprintf(__('Please type the verification code sent to %s', 'digits'), '<span></span>'); ?></div>
            <div class="dig_verify_code_contents">

                <div class="minput">
                    <div class="minput_inner">
                        <div class="digits-input-wrapper">
                            <input type="text" class="empty dig_verify_otp_input" required="" name="dig_otp"
                                   maxlength="<?php echo $otp_size; ?>" placeholder="<?php echo $otp_placeholder; ?>"
                                   autocomplete="one-time-code"/>
                        </div>
                        <label></label>
                        <span class="bgdark"></span>
                    </div>
                </div>
                <div class="dig_verify_otp_submit_button dig_verify_otp lighte bgdark button"><?php _e('SUBMIT', 'digits'); ?></div>

            </div>
        </div>
    </div>
    <?php
}


function dig_wp_forgot_pass_mail($user_data)
{
    $user_login = $user_data->user_login;
    $user_email = $user_data->user_email;
    $key = get_password_reset_key($user_data);


    if (is_wp_error($key)) {
        return false;
    }

    $home_url = home_url();
    $base_url = $home_url;

    if (!empty($_SERVER['HTTP_REFERER'])) {
        $base_url = $_SERVER['HTTP_REFERER'];
    }

    $forgot_pass_url = digits_forgotpass_url($base_url, $home_url);

    $url = add_query_arg(array('action_type' => 'fp', 'type' => 'forgot-password', 'action' => 'rp', 'key' => $key, 'ulogin' => rawurlencode($user_login)), $forgot_pass_url);

    if (is_multisite()) {
        $site_name = get_network()->site_name;
    } else {
        /*
         * The blogname option is escaped with esc_html on the way into the database
         * in sanitize_option we want to reverse this for the plain text arena of emails.
         */
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    }

    $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
    /* translators: %s: Site name. */
    $message .= sprintf(__('Site Name: %s'), $site_name) . "\r\n\r\n";
    /* translators: %s: User login. */
    $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";


    $message .= $url . "\r\n";

    /* translators: Password reset notification email subject. %s: Site title. */
    $title = sprintf(__('[%s] Password Reset'), $site_name);
    $title = apply_filters('retrieve_password_title', $title, $user_login, $user_data);
    $message = apply_filters('retrieve_password_message', $message, $key, $user_login, $user_data);

    if ($message && !wp_mail($user_email, wp_specialchars_decode($title), $message)) {
        if (dig_is_doing_ajax()) {
            wp_send_json_error(array('msg' => __('The email could not be sent. Please contact site owner')));
        } else {
            wp_die(__('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.'));
        }
    } else {
        return true;
    }
}

add_filter('lostpassword_url', 'digits_forgotpass_url', 100, 2);
function digits_forgotpass_url($lostpassword_url, $redirect)
{

    $dig_overwrite_forgotpass_link = get_option('dig_overwrite_forgotpass_link', 1);

    if ($dig_overwrite_forgotpass_link == 0) {
        return $lostpassword_url;
    }

    if (class_exists('WooCommerce')) {
        if (is_account_page()) {
            return $lostpassword_url;
        }
    }


    $redirect = '';
    $args = array('login' => 'true', 'type' => 'forgot-password');
    if (!empty($redirect)) {
        $args['redirect_to'] = $redirect;
    }

    $url = add_query_arg($args, home_url());

    $url = apply_filters('digits_page_url', $url, $redirect, 'forgot');

    return $url;

}

function dig_curl($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $return = curl_exec($ch);

    curl_close($ch);

    return $return;
}

function digits_show_reg_check_disabled_show()
{
    digits_show_reg_check_disabled(true);
}

function digits_show_reg_check_disabled($show_notice = true)
{
    $request_link = admin_url('options-general.php?page=digits_settings&tab=dashboard');
    $digpc = dig_get_option('dig_purchasecode');


    if (empty($digpc)) {
        $dig_hid_update_notice = get_site_option('dig_hid_update_notice', 0);

        $current_time = time();
        if (isset($_POST['dig_hid_activate_notice'])) {
            update_site_option('dig_nt_time', $current_time);
            update_site_option('dig_hid_activate_notice', min($dig_hid_update_notice + 1, 3));

        }


        if ($dig_hid_update_notice < 1) {
            $time = get_site_option('dig_nt_time', -1);


            if (($current_time - $time) > 2592000) {
                ?>
                <div class="notice notice-error dig-new-activation-notice is-dismissible">
                    <p>
                        <?php _e('<b>Digits:</b> Please register digits with purchase code to enable automatic updates. Click <a href="' . $request_link . '">here</a>.<br />'); ?>
                    </p>

                    <form method="post">
                        <input type="hidden" name="dig_hid_activate_notice"/>
                        <button type="submit" class="notice-dismiss" style="z-index: 99">
                            <span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </form>

                </div>
                <?php


            }
        }
    }
    /*

        $passaccep = get_option("digpassaccep", 1);

        if (class_exists('WooCommerce')) {
            if ($passaccep == 0 && get_option('woocommerce_registration_generate_password') === 'no') {

                $class = 'notice notice-warning';

                $message = __('<b>Digits:</b> Please enable <b>Automatically generate customer password</b> option in your WooCommerce settings (WooCommerce --> Settings --> Accounts) for disabling password.', 'digits');
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
            }
        }*/
}

function dig_lgr_custom()
{
    return dig_manual_custom_redirect();
}

//dig_allow_external_redirect


function dig_manual_custom_redirect()
{
    if (isset($_POST['digits_redirect_page'])) {
        $current_url = $_POST['digits_redirect_page'];
        if (empty($current_url)) {
            return;
        }

        $current_url = preg_replace('/' . $_SERVER['SERVER_NAME'] . '/', '', $current_url, 1);

        return $current_url;
    }
}


function dig_register_meta_boxes()
{
    add_meta_box('digits_endpoints_nav_link', __('Digits Menu Items', 'digits'), 'dig_nav_menu_links', 'nav-menus', 'side', 'low');
}

add_action('admin_head-nav-menus.php', 'dig_register_meta_boxes');


function dig_nav_menu_links()
{
    // Get items from account menu.
    $endpoints = array();

    // Remove dashboard item.
    if (isset($endpoints['dashboard'])) {
        unset($endpoints['dashboard']);
    }


    $a = array(
        'dmpage' => '[dm-page]',
        'dmmodal' => '[dm-modal]',
        'loginpage' => '[dm-login-page]',
        'loginmodal' => '[dm-login-modal]',
        'registerpage' => '[dm-signup-page]',
        'registermodal' => '[dm-signup-modal]',
        'forgotpasspage' => '[dm-forgot-password-page]',
        'forgotpassmodal' => '[dm-forgot-password-modal]',
        'logout' => '[dm-logout]'
    );


    $endpoints['dmpage'] = __('Login/Signup Page', 'digits');
    $endpoints['dmmodal'] = __('Login/Signup Modal', 'digits');
    $endpoints['loginpage'] = __('Login Page', 'digits');
    $endpoints['loginmodal'] = __('Login Modal', 'digits');
    $endpoints['registerpage'] = __('Signup Page', 'digits');
    $endpoints['registermodal'] = __('Signup Modal', 'digits');
    $endpoints['forgotpasspage'] = __('Forgot Password Page', 'digits');
    $endpoints['forgotpassmodal'] = __('Forgot Password Modal', 'digits');
    $endpoints['logout'] = __('Logout', 'digits');

    ?>
    <div id="posttype-digits-endpoints" class="posttypediv">
        <div id="tabs-panel-digits-endpoints" class="tabs-panel tabs-panel-active">
            <ul id="digits-endpoints-checklist" class="categorychecklist form-no-clear">
                <?php
                $i = -1;
                foreach ($endpoints as $key => $value) :
                    ?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox"
                                   name="menu-item[<?php echo esc_attr($i); ?>][menu-item-object-id]"
                                   value="<?php echo esc_attr($i); ?>"/> <?php echo esc_html($value); ?>
                        </label>
                        <input type="hidden" class="menu-item-type"
                               name="menu-item[<?php echo esc_attr($i); ?>][menu-item-type]" value="custom"/>
                        <input type="hidden" class="menu-item-title"
                               name="menu-item[<?php echo esc_attr($i); ?>][menu-item-title]"
                               value="<?php echo esc_html($a[$key]); ?>"/>
                        <input type="hidden" class="menu-item-url"
                               name="menu-item[<?php echo esc_attr($i); ?>][menu-item-url]" value="#">
                        <input type="hidden" class="menu-item-classes"
                               name="menu-item[<?php echo esc_attr($i); ?>][menu-item-classes]"/>
                    </li>
                    <?php
                    $i--;
                endforeach;
                ?>
            </ul>
        </div>
        <p class="button-controls wp-clearfix" data-items-type="posttype-digits-endpoints">
			<span class="list-controls hide-if-no-js">
                <input type="checkbox" id="category-digits-menu" class="select-all">
                <label for="category-digits-menu"><?php esc_html_e('Select all', 'Digits'); ?></label>
			</span>

            <span class="add-to-menu">
				<button type="submit" class="button-secondary submit-add-to-menu right"
                        value="<?php esc_attr_e('Add to menu', 'digits'); ?>" name="add-post-type-menu-item"
                        id="submit-posttype-digits-endpoints"><?php esc_html_e('Add to menu', 'digits'); ?></button>
					<span class="spinner"></span>
			</span>
        </p>
    </div>
    <?php
}


function dig_custom_wpwc_fields_hide($hide, $type, $meta_key)
{
    $dig_fields = digits_wp_wc_fields_list();

    if (in_array($meta_key, $dig_fields)) {
        return true;
    }

    return $hide;
}

add_filter('dig_show_field_to_loggedin_user', 'dig_custom_wpwc_fields_hide', 10, 3);


add_action('admin_notices', 'digits_show_reg_check_disabled_show');

function dig_timeConvert($seconds)
{
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");

    return $dtF->diff($dtT)->format('%a');
}

/*
 * 2 - Registration
 * 4 - only verify
 * 11 - update mobile
 * */

function dig_validateMobileNumber($countrycode, $mobile, $otp, $csrf, $type, $code, $check_mob)
{

    if ($check_mob) {
        if (empty($mobile) || !is_numeric($mobile) || !is_numeric($countrycode) || empty($countrycode)) {
            $msg = __('Please enter a valid Mobile Number', 'digits');

            return array('success' => false, 'msg' => $msg);
        }

        if (!empty($countrycode) && strpos($countrycode, '+') !== 0) {
            $msg = __('Please enter a valid Country Code', 'digits');

            return array('success' => false, 'msg' => $msg);
        }
    }
    $user = getUserFromPhone($countrycode . $mobile);
    if ($user != null && ($type == 2 || $type == 11)) {
        $msg = __('Mobile Number is already in use', 'digits');

        return array('success' => false, 'msg' => $msg);
    }
    $msg = __('Please enter a valid OTP', 'digits');


    if (dig_gatewayToUse($countrycode) == 1) {

        if (empty($code)) {
            return array('success' => false, 'msg' => __('Unable to verify Mobile number', 'digits'));
        }
        $json = getUserPhoneFromAccountkit($code);
        $phoneJson = json_decode($json, true);
        if ($json == null) {
            return array('success' => false, 'msg' => $msg);
        }

        $mob = $countrycode . $mobile;

        if ($check_mob) {
            if ($phoneJson['phone'] != $mob) {
                return array('success' => false, 'msg' => $msg);

            }
        }


        $mob = $phoneJson['phone'];
        $phone = $phoneJson['nationalNumber'];
        $countrycode = $phoneJson['countrycode'];

        return array('success' => true, 'mobile' => $phone, 'countrycode' => $countrycode);

    } else {
        if (empty($otp)) {
            return array('success' => false, 'msg' => $msg);
        }
        if (verifyOTP($countrycode, $mobile, $otp, true)) {
            $mob = $countrycode . $mobile;

            return array('success' => true, 'mobile' => $mobile, 'countrycode' => $countrycode);
        } else {
            return array('success' => false, 'msg' => $msg);
        }
    }
}


function dig_is_gatewayEnabled($gatewayCode)
{


    $digit_tapp = get_option('digit_tapp', 0);

    $isEnabled = false;

    if ($gatewayCode == $digit_tapp) {
        $isEnabled = true;
    }

    $isEnabled = apply_filters('digit_gateway_check', $isEnabled, $gatewayCode);

    return $isEnabled;
}

function dig_gatewayToUse($countrycode)
{
    if (empty($countrycode) || !is_numeric($countrycode)) {
        return -1;
    }

    $gatewayToUse = get_option('digit_tapp', 1);

    $gatewayToUse = apply_filters('digit_gateway_to_use', $gatewayToUse, $countrycode);

    return $gatewayToUse;
}


function dig_rememberMe($dig_login_details = null, $text = null)
{


    if (is_array($dig_login_details) && isset($dig_login_details['dig_login_rememberme'])) {
        $rememberme = $dig_login_details['dig_login_rememberme'];
    } else {
        $rememberme = get_option('dig_login_rememberme', 1);
    }
    $class = '';
    $checked = '';
    if ($rememberme == 0) {
        return;
    } else {
        if ($rememberme == 2 || (isset($_REQUEST['rememberme']) && $_REQUEST['rememberme'] == 1)) {
            $class = 'selected';
            $checked = 'checked';
        }
    }
    $rand = esc_attr('digits_login_remember_me' . rand());
    if ($text == null)
        $text = __('Remember Me', 'digits');
    ?>
    <div class="digits-form_input_row digits-form_rememberme" <?php if ($rememberme == 2) {
        echo 'style="display:none;"';
    } ?>>
        <div class="digits-form_input digits-field-type_tac">
            <div class="dig_opt_mult_con">
                <div class="dig_opt_mult">
                    <label for="<?php echo $rand; ?>" class="<?php echo $checked; ?>">
                        <div class="dig_input_wrapper">
                            <input data-all="digits_login_remember_me" name="rememberme"
                                   class="not-empty digits_login_remember_me"
                                   id="<?php echo $rand; ?>"
                                   type="checkbox" value="1" <?php echo $checked; ?>>
                            <div><?php echo $text; ?></div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function digits_filter_username($username, $type = '')
{
    $username = preg_replace('/\s*/', '', $username);
    $username = digits_strtolower($username);

    if ($type == 'name' && strlen($username) > 49) {
        $username = dig_generate_username();
    }

    return $username;
}

function digits_update_username($username)
{

    $username = dig_generate_username();

    return $username;
}

add_filter('digits_username', 'digits_update_username');
function dig_generate_username()
{

    $tname = dig_generate_random_number();
    $check = username_exists($tname);
    if (!empty($check)) {
        while (!empty($check)) {
            $alt_ulogin = dig_generate_random_number();
            $check = username_exists($alt_ulogin);

        }
        $ulogin = $alt_ulogin;
    } else {
        $ulogin = $tname;
    }

    return $ulogin;
}

function dig_generate_random_number()
{
    $length = 12;
    $returnString = mt_rand(1, 9);
    while (strlen($returnString) < $length) {
        $returnString .= mt_rand(0, 9);
    }

    return $returnString;

}

function checkIfUsernameIsMobile_validate($countrycode, $mobile)
{
    $user_id = username_exists($mobile);
    if (!$user_id) {
        return 1;
    }
    if (substr($mobile, 0, 1) == '0') {
        return 1;
    }

    $mobile = sanitize_mobile_field_dig($mobile);
    $request = $countrycode . $mobile;


    $digits_phone_no = get_user_meta($user_id, 'digits_phone_no', true);

    if (empty($digits_phone_no)) {
        return 1;
    }


    $user = get_userdata($user_id);

    $user_phone = get_user_meta($user_id, 'digits_phone', true);
    if ($user_phone != $request && $digits_phone_no == $user->user_login) {

        return -1;

    } else {
        return 1;
    }


}

function dig_get_option($key, $default = null)
{
    if (!empty(get_site_option($key))) {
        return get_site_option($key);
    } else if (!empty(get_option($key))) {
        return get_option($key);
    } else {
        return $default;
    }
}

