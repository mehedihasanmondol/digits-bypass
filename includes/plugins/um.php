<?php

defined('ABSPATH') || exit;

add_action('um_submit_form_errors_hook__registration', 'um_dig_validate_mobileno', 999, 1);
function um_dig_validate_mobileno($args)
{
    global $ultimatemember;

    if (isset(UM()->form()->errors)) {
        if (is_array(UM()->form()->errors)) {
            if (count(UM()->form()->errors) > 0) {
                return;
            }
        }
    }
    if (empty($args['mobile_number'])) {
        return;
    }


    $mobile = sanitize_mobile_field_dig($args['mobile_number']);
    $countrycode = sanitize_text_field($args['digt_countrycode']);


    $code = sanitize_text_field($args['code']);
    $csrf = sanitize_text_field($args['csrf']);
    $otp = sanitize_text_field($args['digit_ac_otp']);

    $nounce = sanitize_text_field($args['dig_nounce']);

    $mobile = sanitize_mobile_field_dig($mobile);
    $countrycode = sanitize_text_field($countrycode);

    $mobuser = getUserFromPhone($countrycode . $mobile);
    if ($mobuser != null) {
        UM()->form()->add_error("MobinUse", __("Mobile number already in use!", "digits"));
    } else if (username_exists($countrycode . $mobile)) {
        UM()->form()->add_error("MobinUse", __("Mobile number already in use!", "digits"));
    }


    if (empty($otp)) {
        UM()->form()->add_error('user_mobileno', __('Unable to verify Mobile number', 'digits'));

        return;
    } else {

        if (verifyOTP($countrycode, $mobile, $otp, true)) {
            $mob = $countrycode . $mobile;
            $phone = $mobile;
        } else {
            UM()->form()->add_error('user_mobileno', __('Unable to verify Mobile number', 'digits'));

            return;
        }

    }


}


add_action('um_registration_complete', 'um_update_digits_data', 10, 2);
function um_update_digits_data($user_id, $args)
{

    if (empty($args['mobile_number'])) {
        return;
    }

    $phone = sanitize_mobile_field_dig($args['mobile_number']);
    $countrycode = sanitize_text_field($args['digt_countrycode']);


    if (!empty($phone) && !empty($countrycode)) {
        update_user_meta($user_id, 'digt_countrycode', $countrycode);
        update_user_meta($user_id, 'digits_phone_no', $phone);
        update_user_meta($user_id, 'digits_phone', $countrycode . $phone);
    }
}


add_action('um_after_form_fields', 'um_add_dig_otp_fields');
function um_add_dig_otp_fields($args)
{
    global $ultimatemember;
    ?>
    <input type="hidden" name="code" id="digits_um_code" value=""/>
    <input type="hidden" name="csrf" id="digits_um_csrf" value=""/>
    <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
    <?php

    if (!empty($otp) || !empty($code)) {
        echo '<input type="hidden" value="1" id="um_sub" />';
    }

    ?>

    <div class="um-field dig_otp_um_reg" style="<?php echo 'display: none;'; ?>">
        <div class="um-field-label"><label for="digit_ac_otp"><?php _e("OTP", "digits"); ?></label>
            <div class="um-clear"></div>
        </div>
        <div class="um-field-area">
            <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp" data-key="user_otp" value=""/>
        </div>
    </div><br/>
    <?php
    echo "<div  class=\"dig_resendotp dig_um_regis_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>";


}

add_action('init', 'dig_ump_login_update', 1);
function dig_ump_login_update()
{
    if (isset($_POST['ump_login_form']) && isset($_POST['form_id'])) {
        if (isset($_POST['mobile/email']) && isset($_POST['digt_countrycode'])) {
            $countrycode = sanitize_text_field($_POST['digt_countrycode']);
            $mob = sanitize_mobile_field_dig($_POST['mobile/email']);
            $form_id = sanitize_text_field($_POST['form_id']);
            if (is_numeric($mob)) {
                $user = getUserFromPhone($countrycode . $mob);
                $_POST['username-' . $form_id] = $user->user_login;
            } else {
                $_POST['username-' . $form_id] = $mob;
            }
            unset($_POST['mobile/email']);
        }
    }
}

add_action('um_after_login_fields', 'dig_ump_otp', 1001);

function dig_ump_otp()
{
    ?>
    <div class="ump_digits_otp_container">
        <input name="ump_login_form" value="1" type="hidden"/>
        <?php
        $dig_login_details = digit_get_login_fields();
        $passaccep = $dig_login_details['dig_login_password'];
        $otpaccep = $dig_login_details['dig_login_otp'];
        if ($otpaccep != 1) {
            return;
        }
        ?>
        <br/>
        <div class="digor"><?php _e("OR", "digits"); ?><br/></div>
        <br/>
        <input class="um-button dig_um_loginviaotp" value="<?php _e('Login With OTP', 'digits'); ?>" type="submit"/>

        <?php

        echo "<div  class=\"dig_resendotp dig_um_login_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>";

        ?>
    </div>
    <?php
}


function dig_otp_bx_um()
{
    ?>
    <div class="um-field dig_otp_um_login" style="<?php echo 'display: none;'; ?>">
        <div class="um-field-label"><label for="digit_ac_otp"><?php _e("OTP", "digits"); ?></label>
            <div class="um-clear"></div>
        </div>
        <div class="um-field-area">
            <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp" data-key="user_otp" value=""/>
        </div>
    </div>

    <?php
}

add_action('um_after_login_fields', 'dig_otp_bx_um', 10);

function dig_um_login($args)
{
    if (!empty($args['digt_countrycode']) && !empty($args['username']) && is_numeric($args['username'])) {

        $_POST['countrycode'] = $args['digt_countrycode'];
        $_POST['mobmail'] = $args['username'];
        $_POST['password'] = $args['user_password'];
        _digits_process_form(false);

        if (is_user_logged_in()) {
            um_redirect_home();
        }

    }
}

add_action('um_submit_form_errors_hook_login', 'dig_um_login');

function digits_um_submit_form_error($error, $key)
{
    if ($key == 'invalid_username') {
        if (!empty($_POST['countrycode']) && !empty($_POST['mobmail'])
            && is_numeric($_POST['mobmail'])) {
            $mob = sanitize_text_field($_POST['countrycode']) . sanitize_mobile_field_dig($_POST['mobmail']);
            if (getUserFromPhone($mob)) {
                return '';
            } else {
                return esc_html__('Invalid Email/Mobile Number', 'digits');
            }
        }
    }
    return $error;
}

add_filter('um_submit_form_error', 'digits_um_submit_form_error', 10, 2);


function digits_um_after_password_reset_fields($args)
{
    wc_dig_lost_pass();
}

add_action('um_after_password_reset_fields', 'digits_um_after_password_reset_fields');
