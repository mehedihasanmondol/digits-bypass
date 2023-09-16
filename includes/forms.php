<?php


if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'digits_forgot_key');

function digits_forgot_key()
{
    if (is_user_logged_in()) {
        return;
    }

    if (isset($_GET['action']) && $_GET['action'] == 'rp' && !empty($_GET['action_type'])) {
        list($rp_path) = explode('?', wp_unslash($_SERVER['REQUEST_URI']));
        $rp_cookie = 'wp-resetpass-' . COOKIEHASH;

        if (isset($_GET['key'])) {
            $value = sprintf('%s:%s', wp_unslash($_GET['ulogin']), wp_unslash($_GET['key']));
            setcookie($rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true);

            wp_safe_redirect(remove_query_arg(array('key', 'ulogin')));
            exit;
        }

        if (isset($_COOKIE[$rp_cookie]) && 0 < strpos($_COOKIE[$rp_cookie], ':')) {
            list($rp_login, $rp_key) = explode(':', wp_unslash($_COOKIE[$rp_cookie]), 2);

            $user = check_password_reset_key($rp_key, $rp_login);

        } else {
            $user = false;
        }

        if (!$user || is_wp_error($user)) {
            setcookie($rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true);

        }
    }
}

function digits_wp_wc_fields_list()
{
    $list = array(
        'first_name', 'last_name', 'display_name', 'user_role',
        'billing_first_name',
        'billing_last_name',
        'billing_company',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_postcode',
    );
    return $list;
}

function digits_form_button()
{
    return array(
        'dig_login_via_pass' => esc_attr__('Login', 'digits'),
        'dig_login_via_mob' => esc_attr__('Login With OTP', 'digits'),
        'dig_login_remember_me' => esc_attr__('Remember Me', 'digits'),
        'dig_login_forgot_pass' => esc_attr__('Forgot your Password?', 'digits'),
        'dig_login_otp_text' => esc_attr__('OTP', 'digits'),
        'dig_login_submit_otp' => esc_attr__('Submit OTP', 'digits'),
        'dig_login_resend_otp' => esc_attr__('Resend OTP', 'digits'),
        'dig_signup_desc_text' => esc_attr__('Don\'t have an account?', 'digits'),
        'dig_signup_button_text' => esc_attr__('Signup', 'digits'),
        'dig_signup_via_password' => esc_attr__('Signup With Password', 'digits'),
        'dig_signup_via_otp' => esc_attr__('Signup With OTP', 'digits'),
        'dig_signup_otp_text' => esc_attr__('OTP', 'digits'),
        'dig_signup_submit_otp' => esc_attr__('Submit OTP', 'digits'),
        'dig_signup_resend_otp' => esc_attr__('Resend OTP', 'digits'),
    );
}

function digits_forms($values = null, $form_data = '')
{
    $is_elem = -1;
    $users_can_register = get_option('dig_enable_registration', 1);
    $digforgotpass = get_option('digforgotpass', 1);
    $button_texts = digits_form_button();

    $url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $query = parse_url($url, PHP_URL_QUERY);
    if ($query) {
        $url .= '&login=true';
    } else {
        $url .= '?login=true';
    }

    $is_native = true;
    if ($values != null && is_array($values)) {
        if (isset($values['render_form'])) {
            $is_elem = $values['render_form'];
            $is_native = true;
        } else if (isset($values['is_elem'])) {
            $is_elem = $values['is_elem'];
            $is_native = false;
            if (isset($values['url'])) {
                $button_texts = $values['button_texts'];
                $url = $values['url'];
                $digforgotpass = $values['forgot_password'];
            }
        }
    }


    if ($is_elem == -1 || $is_elem == 1) {


        $page = !empty($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

        if ($page == 'register') {
            $page = 2;
        } else if ($page == 'forgot-password') {
            $page = 3;
        } else if ($page == 'login') {
            $page = 4;
        } else if (empty($page)) {
            $page = 1;
        }

        if ($page == 4) {
            $users_can_register = 0;
        }

        if (empty($page) || ($users_can_register == 0 && $page == 2) || ($digforgotpass == 0 && $page == 3)) {
            $page = 1;
        }
        if ($page > 1 && $page > 4) {
            $page = 1;
        }

    } else {
        $page = $is_elem;
    }


    $rp_login = '';
    $rp_key = '';
    $show_pass_change_fields = false;
    if (isset($_GET['action']) && $_GET['action'] == 'rp') {
        list($rp_path) = explode('?', wp_unslash($_SERVER['REQUEST_URI']));
        $rp_cookie = 'wp-resetpass-' . COOKIEHASH;

        if (isset($_COOKIE[$rp_cookie]) && 0 < strpos($_COOKIE[$rp_cookie], ':')) {
            list($rp_login, $rp_key) = explode(':', wp_unslash($_COOKIE[$rp_cookie]), 2);

            $user = check_password_reset_key($rp_key, $rp_login);
            $show_pass_change_fields = true;

        } else {
            $user = false;
        }

        if (!$user || is_wp_error($user)) {
            $rp_login = '';
            $rp_key = '';
            $show_pass_change_fields = false;
        }
    }


    $userCountryCode = getUserCountryCode();


    $dig_login_details = apply_filters('digits_login_fields', digit_get_login_fields(), $values);


    if (!isset($values['redirect_to'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';
    } else {
        $redirect_to = '';
    }

    $login_redirect = $redirect_to;

    if (isset($values['login_redirect'])) {
        $login_redirect = $values['login_redirect'];
    }
    $usernameaccep = $dig_login_details['dig_login_username'];
    $emailaccep = $dig_login_details['dig_login_email'];
    $passaccep = $dig_login_details['dig_login_password'];
    $mobileaccp = $dig_login_details['dig_login_mobilenumber'];
    $otpaccp = $dig_login_details['dig_login_otp'];
    $captcha = $dig_login_details['dig_login_captcha'];
    if ($emailaccep == 1 && $mobileaccp == 1) {
        $emailaccep = 2;
    }

    if ($emailaccep == 2) {
        $emailmob = __("Email/Mobile Number", "digits");
    } else if ($mobileaccp == 1) {
        $emailmob = __("Mobile Number", "digits");
    } else if ($emailaccep > 0) {
        $emailmob = __("Email", "digits");
    } else {
        $emailmob = __("Username", "digits");
    }
    $data_accept = 1;

    if ($mobileaccp == 0) {
        $data_accept = 3;
    } else if ($emailaccep == 0 && $usernameaccep == 0 && $mobileaccp != 0) {
        $data_accept = 2;
    }

    $theme = 'dark';
    $bgtype = 'bgdark';
    $themee = 'lighte';
    $bgtransbordertype = "bgtransborderdark";


    if ($is_elem != 3) {
        ?>
        <div
                class="digloginpage" <?php if ($page == 2 || $page == 3) {
            echo 'style="display:none"';
        } ?>>
            <form accept-charset="utf-8" method="post" class="digits_login" action="<?php echo $url; ?>">
                <div class="digits_fields_wrapper digits_login_fields">
                    <div class="minput digits_login_field_row">
                        <div class="minput_inner">
                            <div class="countrycodecontainer logincountrycodecontainer">
                                <input type="text" name="countrycode"
                                       class="input-text countrycode logincountrycode <?php echo $theme; ?>"
                                       value="<?php if (isset($countrycode)) {
                                           echo $countrycode;
                                       } else {
                                           echo $userCountryCode;
                                       } ?>"
                                       maxlength="6" size="3" placeholder="<?php echo $userCountryCode; ?>"
                                       autocomplete="tel-country-code"/>
                            </div>
                            <div class="digits-input-wrapper">
                                <input type="text" class="mobile_field mobile_format dig-mobmail" name="mobmail"
                                       value="<?php if (isset($username)) {
                                           echo $username;
                                       } ?>" data-type="<?php echo $data_accept; ?>" required/>
                            </div>

                            <label><?php echo $emailmob; ?></label>
                            <span class="<?php echo $bgtype; ?>"></span>
                        </div>
                    </div>


                    <?php


                    if ($passaccep == 1) {
                        ?>
                        <div class="minput password_row" style="display: none">
                            <div class="minput_inner">
                                <div class="digits-input-wrapper">
                                    <input type="password" name="password" autocomplete="current-password" disabled/>
                                </div>
                                <label><?php _e('Password', 'digits'); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        </div>
                        <?php
                    }

                    if ($captcha == 1) {
                        dig_show_login_captcha(1, $bgtype);
                    }

                    ?>
                    <input type="hidden" name="login_page" value="1"/>
                    <div class="minput dig_login_otp" style="display: none;">
                        <div class="minput_inner">
                            <div class="digits-input-wrapper">
                                <input type="text" name="dig_otp" class="dig-login-otp" autocomplete="one-time-code"/>
                            </div>
                            <label><?php echo $button_texts['dig_signup_otp_text']; ?></label>
                            <span class="<?php echo $bgtype; ?>"></span>
                        </div>
                    </div>


                    <input type="hidden" class="dig_login_captcha"
                           value="<?php echo $captcha; ?>">


                    <input type="hidden" name="dig_nounce" class="dig_nounce"
                           value="<?php echo wp_create_nonce('dig_form') ?>">

                    <?php

                    global $dig_logingpage;
                    $dig_logingpage = 1;
                    do_action('login_form');
                    $dig_logingpage = 0;
                    ?>

                    <div class="digits_login_field_row">
                        <?php
                        dig_rememberMe($dig_login_details, $button_texts['dig_login_remember_me']);
                        ?>
                    </div>
                </div>
                <div class="dig_spacer"></div>
                <?php
                if ($passaccep == 1) { ?>
                    <div class="logforb">
                        <button type="submit" class="<?php echo $themee; ?> <?php echo $bgtype; ?> button">
                            <?php echo $button_texts['dig_login_via_pass'] ?>
                        </button>
                        <?php

                        if ($digforgotpass == 1) {
                            ?>
                            <div class="forgotpasswordaContainer"><a
                                        class="forgotpassworda"><?php echo $button_texts['dig_login_forgot_pass']; ?></a>
                            </div>
                        <?php } ?>
                    </div>
                    <?php
                }
                if ($mobileaccp == 1 && $otpaccp == 1) {
                    ?>

                    <div id="dig_login_va_otp"
                         class=" <?php echo $themee; ?> <?php echo $bgtype; ?> button digits_login_via_otp loginviasms loginviasmsotp"><?php echo $button_texts['dig_login_via_mob']; ?></div>

                    <?php if (dig_isWhatsAppEnabled()) { ?>
                        <div id="dig_login_va_whatsapp"
                             class=" <?php echo $themee; ?> <?php echo $bgtype; ?> button loginviasms loginviawhatsapp dig_use_whatsapp"><?php _e('Login With WhatsApp', 'digits'); ?></div>
                        <?php
                    }
                    ?>
                    <?php echo "<div  class=\"dig_resendotp dig_logof_log_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'> " . $button_texts['dig_login_resend_otp'] . "<span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>

                    <input type="hidden" class="dig_submit_otp_text"
                           value="<?php echo $button_texts['dig_login_submit_otp']; ?>"/>

                    <?php
                }


                if ($users_can_register == 1 && $is_elem != 4) {

                    $need_account_text = $button_texts['dig_signup_desc_text'];
                    ?>
                    <div class="signdesc"><?php echo $need_account_text; ?></div>
                    <div class="signupbutton transupbutton bgtransborderdark"><?php echo $button_texts['dig_signup_button_text']; ?></div>
                <?php }

                echo $form_data;
                ?>
                <input type="hidden" name="digits_redirect_page"
                       value="<?php echo esc_attr($login_redirect); ?>"/>

            </form>
        </div>

        <?php
    }


    if ($digforgotpass == 1 && $dig_login_details['dig_login_password'] == 1) {
        $data_type = 1;
        if ($emailaccep == 2) {
            $emailmob = __("Email/Mobile Number", "digits");
        } else if ($mobileaccp == 1) {
            $data_type = 2;
            $emailmob = __("Mobile Number", "digits");
        } else {
            $data_type = 3;
            $emailmob = __("Email", "digits");
        }
        ?>
        <div class="forgot" <?php if ($page != 3) {
            echo 'style="display:none"';
        } else echo 'style="display:block"'; ?>>
            <form accept-charset="utf-8" method="post" action="<?php echo $url; ?>" class="digits_forgot_pass">

                <div class="digits_fields_wrapper digits_forgot_pass_fields">
                    <div class="minput forgotpasscontainer" <?php if ($show_pass_change_fields) echo 'style="display:none;"'; ?>>
                        <div class="minput_inner">
                            <div class="countrycodecontainer forgotcountrycodecontainer">
                                <input type="text" name="countrycode"
                                       class="input-text countrycode forgotcountrycode  <?php echo $theme; ?>"
                                       value="<?php echo $userCountryCode; ?>"
                                       maxlength="6" size="3" placeholder="<?php echo $userCountryCode; ?>"
                                       autocomplete="tel-country-code"/>
                            </div>

                            <div class="digits-input-wrapper">
                                <input class="mobile_field mobile_format forgotpass" type="text" name="forgotmail"
                                       data-type="<?php echo $data_type; ?>"
                                       required/>
                            </div>
                            <label><?php echo $emailmob; ?></label>
                            <span class="<?php echo $bgtype; ?>"></span>
                        </div>
                    </div>


                    <div class="minput dig_forgot_otp" style="display: none;">
                        <div class="minput_inner">
                            <div class="digits-input-wrapper">
                                <input type="text" name="dig_otp" class="dig-forgot-otp" autocomplete="one-time-code"/>
                            </div>
                            <label><?php _e('OTP', 'digits'); ?></label>
                            <span class="<?php echo $bgtype; ?>"></span>
                        </div>
                    </div>

                    <input type="hidden" name="rp_key" value="<?php echo esc_attr($rp_key); ?>"/>

                    <input type="hidden" name="code" class="digits_code"/>
                    <input type="hidden" name="csrf" class="digits_csrf"/>
                    <input type="hidden" name="dig_nounce" class="dig_nounce"
                           value="<?php echo wp_create_nonce('dig_form') ?>">
                    <div class="changepassword" <?php if ($show_pass_change_fields) echo 'style="display:block;"'; ?>>
                        <div class="minput">
                            <div class="minput_inner">
                                <div class="digits-input-wrapper">
                                    <input type="password" class="digits_password" name="digits_password"
                                           autocomplete="new-password" required/>
                                </div>
                                <label><?php _e('Password', 'digits'); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        </div>

                        <div class="minput">
                            <div class="minput_inner">
                                <div class="digits-input-wrapper">
                                    <input type="password" class="digits_cpassword" name="digits_cpassword"
                                           autocomplete="new-password" required/>
                                </div>
                                <label><?php _e('Confirm Password', 'digits'); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" class="dig_submit_otp_text"
                           value="<?php echo $button_texts['dig_login_submit_otp']; ?>"/>
                </div>
                <div class="dig_spacer"></div>
                <button type="submit"
                        class="<?php echo $themee; ?> <?php echo $bgtype; ?> button forgotpassword"
                        value="<?php _e('Reset Password', 'digits'); ?>"><?php _e("Reset Password", "digits"); ?></button>
                <?php echo "<div  class=\"dig_resendotp dig_logof_forg_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'>" . $button_texts['dig_login_resend_otp'] . "<span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>
                <?php if ($is_elem != 3) { ?>
                    <div class="backtoLoginContainer"><a
                                class="backtoLogin"><?php _e("Back to login", "digits"); ?></a>
                    </div>
                    <?php
                }
                echo $form_data;

                ?>
                <input type="hidden" name="digits_redirect_page"
                       value="<?php echo esc_attr($redirect_to); ?>"/>

            </form>
        </div>

        <?php
    }


    if ($users_can_register == 1 && ($is_elem == -1 || $is_native)) {

        $dig_reg_details = digit_get_reg_fields();


        $nameaccep = $dig_reg_details['dig_reg_name'];
        $usernameaccep = $dig_reg_details['dig_reg_uname'];
        $emailaccep = $dig_reg_details['dig_reg_email'];
        $passaccep = $dig_reg_details['dig_reg_password'];
        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

        $data_accept = 3;

        if ($emailaccep == 1 && $mobileaccp == 1) {
            $data_accept = 1;
            $emailmob = __("Email/Mobile Number", "digits");
        } else if ($mobileaccp > 0) {
            $data_accept = 2;
            $emailmob = __("Mobile Number", "digits");
        } else if ($emailaccep > 0) {
            $emailmob = __("Email", "digits");
        } else if ($usernameaccep == 0) {
            $usernameaccep = 1;
            $emailmob = __("Username", "digits");
        }


        if ($emailaccep == 0) {
            echo "<input type=\"hidden\" value=\"1\" class=\"disable_email_digit\" />";
        }
        if ($passaccep == 0) {
            echo "<input type=\"hidden\" value=\"1\" class=\"disable_password_digit\" />";
        }
        ?>
        <div class="register" <?php if ($page == 2) {
            echo 'style="display:block"';
        } ?>>

            <form accept-charset="utf-8" method="post" class="digits_register digits_native_registration_form"
                  action="<?php echo $url; ?>">

                <div class="dig_reg_inputs">

                    <div class="digits_fields_wrapper digits_register_fields">
                        <?php
                        if ($nameaccep > 0) {
                            ?>

                            <div id="dig_cs_name" class="minput">
                                <div class="minput_inner">
                                    <div class="digits-input-wrapper">
                                        <input type="text" name="digits_reg_name" class="digits_reg_name"
                                               value="<?php if (isset($name)) {
                                                   echo $name;
                                               } ?>" <?php if ($nameaccep == 2) {
                                            echo "required";
                                        } ?> autocomplete="name"/>
                                    </div>
                                    <label><?php _e("First Name", "digits"); ?></label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>
                            </div>
                        <?php }

                        if ($usernameaccep > 0) {
                            ?>

                            <div id="dig_cs_username" class="minput">
                                <div class="minput_inner">
                                    <div class="digits-input-wrapper">
                                        <input type="text" name="digits_reg_username" id="digits_reg_username"
                                               value="<?php if (isset($username)) {
                                                   echo $username;
                                               } ?>" <?php if ($usernameaccep == 2) {
                                            echo "required";
                                        } ?> autocomplete="username"/>
                                    </div>
                                    <label><?php _e("Username", "digits"); ?></label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>
                            </div>
                        <?php }


                        $reqoropt = "";


                        if ($emailaccep > 0 || $mobileaccp > 0) {

                            ?>
                            <div id="dig_cs_mobilenumber" class="minput">
                                <div class="minput_inner">
                                    <div class="countrycodecontainer registercountrycodecontainer">
                                        <input type="text" name="digregcode"
                                               class="input-text countrycode registercountrycode  <?php echo $theme; ?>"
                                               value="<?php echo $userCountryCode; ?>" maxlength="6" size="3"
                                               placeholder="<?php echo $userCountryCode; ?>" <?php if ($emailaccep == 2 || $mobileaccp == 2) {
                                            echo 'required';
                                        } ?> autocomplete="tel-country-code"/>
                                    </div>

                                    <div class="digits-input-wrapper">
                                        <input type="text" class="mobile_field mobile_format digits_reg_email"
                                               name="digits_reg_mail"
                                               data-type="<?php echo $data_accept; ?>"
                                               value="<?php if (isset($mob) || $emailaccep == 2 || $mobileaccp == 2) {
                                                   if ($mobileaccp == 1) {
                                                       $reqoropt = "(" . __("Optional", 'digits') . ")";
                                                   }

                                               } else if (isset($mail)) {
                                                   echo $mail;
                                               } ?>" <?php if (empty($reqoropt))
                                            echo 'required' ?>/>
                                    </div>
                                    <label><?php if ($emailaccep == 2 && $mobileaccp == 2) {
                                            echo __('Mobile Number', 'digits');
                                        } else {
                                            echo $emailmob;
                                        } ?><span class="optional"><?php echo $reqoropt; ?></span></label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>
                            </div>

                            <?php
                        }
                        if ($emailaccep > 0 && $mobileaccp > 0) {
                            $emailmob = __('Email/Mobile Number', 'digits');

                            $reqoropt = "";
                            if ($emailaccep == 1) {
                                $reqoropt = "(" . __("Optional", 'digits') . ")";
                            }
                            if ($emailaccep == 2 || $mobileaccp == 2) {
                                $emailmob = __('Email', 'digits');

                            }

                            ?>
                            <div id="dig_cs_email"
                                 class="minput dig-mailsecond" <?php if ($emailaccep != 2 && $mobileaccp != 2) {
                                echo 'style="display: none;"';
                            } ?>>
                                <div class="minput_inner">
                                    <div class="countrycodecontainer secondregistercountrycodecontainer">
                                        <input type="text" name="digregscode2"
                                               class="input-text countrycode registersecondcountrycode  <?php echo $theme; ?>"
                                               value="<?php echo $userCountryCode; ?>" maxlength="6" size="3"
                                               placeholder="<?php echo $userCountryCode; ?>"
                                               autocomplete="tel-country-code"/>
                                    </div>

                                    <div class="digits-input-wrapper">
                                        <input type="text" class="mobile_field mobile_format dig-secondmailormobile"
                                               name="mobmail2"
                                               data-mobile="<?php echo $mobileaccp; ?>"
                                               data-mail="<?php echo $emailaccep; ?>"
                                            <?php if ($emailaccep == 2) {
                                                echo "required";
                                            } ?>/>
                                    </div>

                                    <label>
                                        <span class="dig_secHolder"><?php echo $emailmob; ?></span>
                                        <span class="optional"><?php echo $reqoropt; ?></span>
                                    </label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>
                            </div>
                            <?php
                        }

                        if ($passaccep > 0) {
                            ?>


                            <div id="dig_cs_password" class="minput" <?php if ($passaccep == 1) {
                                echo 'style="display: none;"';
                            } ?>>
                                <div class="minput_inner">
                                    <div class="digits-input-wrapper">
                                        <input type="password" name="digits_reg_password"
                                               class="digits_reg_password" <?php if ($passaccep == 2) {
                                            echo "required";
                                        } ?> autocomplete="new-password"/>
                                    </div>
                                    <label><?php _e("Password", "digits"); ?></label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>
                            </div>
                        <?php }

                        show_digp_reg_fields(1, $bgtype);

                        echo '</div>';

                        ?>
                        <div>
                            <?php
                            do_action('register_form');
                            ?>
                        </div>

                        <div class="minput dig_register_otp" style="display: none;">
                            <div class="minput_inner">
                                <div class="digits-input-wrapper">
                                    <input type="text" name="dig_otp" class="dig-register-otp"
                                           value="<?php if (isset($_POST['dig_otp'])) {
                                               echo dig_filter_string($_POST['dig_otp']);
                                           } ?>" autocomplete="one-time-code"/>
                                </div>
                                <label><?php echo $button_texts['dig_signup_otp_text']; ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        </div>


                        <input type="hidden" name="code" class="register_code"/>
                        <input type="hidden" name="csrf" class="register_csrf"/>
                        <input type="hidden" name="dig_reg_mail" class="dig_reg_mail">
                        <input type="hidden" name="dig_nounce" class="dig_nounce"
                               value="<?php echo wp_create_nonce('dig_form') ?>">

                        <?php
                        echo '<input type="hidden" class="digits_form_reg_fields" value="' . esc_html__(json_encode($dig_reg_details)) . '" />';
                        ?>
                    </div>
                    <div class="dig_spacer"></div>
                    <?php
                    if ($mobileaccp > 0 || $passaccep == 0 || $passaccep == 2) {
                        if (($passaccep == 0 && $mobileaccp == 0) || $passaccep == 2 || ($passaccep == 0 && $mobileaccp > 0)) {
                            $subVal = $button_texts['dig_signup_button_text'];
                        } else {
                            $subVal = $button_texts['dig_signup_via_otp'];
                        }
                        ?>

                        <button class="<?php echo $themee . ' ' . $bgtype; ?> button dig-signup-otp registerbutton"
                                value="<?php echo $subVal; ?>" type="submit"><?php echo $subVal; ?></button>
                        <?php if (dig_isWhatsAppEnabled()) { ?>
                            <button class="<?php echo $themee . ' ' . $bgtype; ?> button dig-signup-otp registerbutton dig_use_whatsapp"
                                    value="<?php echo $subVal; ?>" type="submit">
                                <?php _e('Signup With WhatsApp', 'digits'); ?>
                            </button>
                            <?php
                        }
                        ?>
                        <?php echo "<div  class=\"dig_resendotp dig_logof_reg_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'>" . $button_texts['dig_signup_resend_otp'] . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>

                        <input type="hidden" class="dig_submit_otp_text"
                               value="<?php echo $button_texts['dig_signup_submit_otp']; ?>"/>
                    <?php } ?>

                    <?php if ($passaccep == 1) {

                        $signup_pass_text = $button_texts['dig_signup_via_password'];
                        ?>
                        <button class="dig_reg_btn_password <?php echo $themee . ' ' . $bgtype; ?> button registerbutton"
                                attr-dis="1"
                                value="<?php echo $signup_pass_text; ?>" type="submit">
                            <?php echo $signup_pass_text; ?>
                        </button>


                    <?php } ?>

                    <div class="backtoLoginContainer"><a
                                class="backtoLogin"><?php _e("Back to login", "digits"); ?></a>
                    </div>


            </form>
        </div>


        <?php
    }


    ?>

    <?php

}

function digits_custom_css()
{
    $custom_css = digits_get_custom_css();
    echo $custom_css;
}

function digits_get_custom_css()
{
    $custom_css = get_option('digit_custom_css');
    $custom_css = stripslashes($custom_css);
    return $custom_css;
}

add_action('digits_custom_css', 'digits_custom_css');

function digits_form_inline_css($color, $is_page, $bg_image_url)
{
    $bgcolor = "#4cc2fc";
    $fontcolor = 0;

    $backcolor = '';
    $loginboxcolor = "rgba(255,255,255,1)";
    $sx = 0;
    $sy = 2;
    $sspread = 0;
    $sblur = 4;
    $scolor = "rgba(0, 0, 0, 0.5)";

    $fontcolor2 = "rgba(255,255,255,1)";
    $fontcolor1 = "rgba(20,20,20,1)";

    $left_color = 'rgba(255,255,255,1)';
    $page_type = 1;
    $sradius = 4;
    $left_bg_position = 'Center Center';
    $left_bg_size = 'auto';
    if ($color !== false) {
        $bgcolor = $color['bgcolor'];


        if (isset($color['fontcolor'])) {
            $fontcolor = $color['fontcolor'];
            $loginboxcolor = $bgcolor;
            $scolor = "rgba(0,0,0,0)";
            if ($fontcolor == 1) {
                $fontcolor1 = "rgba(20,20,20,1)";
                $fontcolor2 = "rgba(255,255,255,1)";
            }
        }
        if (isset($color['sx'])) {
            $sx = $color['sx'];
            $sy = $color['sy'];
            $sspread = $color['sspread'];
            $sblur = $color['sblur'];
            $scolor = $color['scolor'];
            $fontcolor1 = $color['fontcolor1'];
            $fontcolor2 = $color['fontcolor2'];
            $loginboxcolor = $color['loginboxcolor'];
            $sradius = $color['sradius'];

            if (isset($color['backcolor'])) {
                $backcolor = $color['backcolor'];
            }

        }
        if (isset($color['type'])) {
            $page_type = $color['type'];
            if ($page_type == 2) {
                $left_color = $color['left_color'];
            }
        }

    }

    if ($page_type == 2) {
        $input_bg_color = $color['input_bg_color'];
        $input_border_color = $color['input_border_color'];
        $input_text_color = $color['input_text_color'];
        $button_bg_color = $color['button_bg_color'];
        $signup_button_color = $color['signup_button_color'];
        $signup_button_border_color = $color['signup_button_border_color'];
        $button_text_color = $color['button_text_color'];
        $signup_button_text_color = $color['signup_button_text_color'];
        $left_bg_position = $color['left_bg_position'];
        $left_bg_size = $color['left_bg_size'];
    }

    ?>
    <style>
        <?php
        do_action('digits_custom_css');
        ?>
        .digits_login_form .dig-container {
            background-color: <?php echo $loginboxcolor; ?>;
            border-radius: <?php echo $sradius; ?>px;
            box-shadow: <?php echo $sx."px ".$sy."px ".$sblur."px ".$sspread."px ".$scolor; ?>
        }

        <?php
        if($is_page){
            ?>

        body {
            background: linear-gradient(<?php echo $bgcolor; ?>,<?php echo $bgcolor; ?>)<?php echo $bg_image_url; ?>;
            background-size: cover;
            background-attachment: fixed;
        }

        <?php
        }else{
            ?>
        .digits_login_form .dig-modal-con {
            border-radius: <?php echo $sradius; ?>px;
            box-shadow: <?php echo $sx."px ".$sy."px ".$sblur."px ".$sspread."px ".$scolor; ?>;
            background: linear-gradient(<?php echo $loginboxcolor; ?>,<?php echo $loginboxcolor; ?>)<?php echo $bg_image_url; ?>;
            background-size: cover;
        }

        <?php
        }
         ?>

        .digits_login_form .dig_ma-box .bglight {
            background-color: <?php echo $fontcolor1; ?>;
        }


        <?php if (!empty($backcolor)){?>

        .dig_page_cancel_color {
            color: <?php echo $backcolor;?>
        }

        <?php }?>

        .digits_login_form .dig_login_rembe .dig_input_wrapper:before,
        .digits_login_form .dig-custom-field-type-radio .dig_opt_mult_con .selected:before,
        .digits_login_form .dig-custom-field-type-radio .dig_opt_mult_con .dig_input_wrapper:before,
        .digits_login_form .dig-custom-field-type-tac .dig_opt_mult_con .selected:before,
        .digits_login_form .dig-custom-field-type-checkbox .dig_opt_mult_con .selected:before,
        .digits_login_form .dig-custom-field-type-tac .dig_opt_mult_con .dig_input_wrapper:before,
        .digits_login_form .dig-custom-field-type-checkbox .dig_opt_mult_con .dig_input_wrapper:before {
            background-color: <?php echo $fontcolor1;?>;
        }


        <?php if($page_type==2){ ?>
        .digits_login_form .dig_ul_left_side {
            background: <?php echo $left_color;?>;
        }

        .digits_login_form .dig_ul_left_side {
            background-repeat: no-repeat;
            background-size: <?php echo $left_bg_size;?>;
            background-position: <?php echo $left_bg_position;?>;
        }

        .digits_login_form .dig_ma-box .bgtransborderdark {
            color: <?php echo $signup_button_text_color; ?>;
        }

        .digits_login_form .dig_ma-box .dark input[type="submit"], .digits_login_form .dig_ma-box .lighte {
            color: <?php echo $button_text_color; ?> !important;
        }

        .digits_login_form .dig_ma-box .dark a, .digits_login_form .dig_ma-box .dark .dig-cont-close, .digits_login_form .dig_ma-box .dark,
        .digits_login_form .dig_ma-box .dark .minput label, .digits_login_form .dig_ma-box .dark .minput input, .digits_login_form .dig_ma-box .darke,
        .digits_login_form .dig_pgmdl_2 .minput label {
            color: <?php echo $fontcolor1;?>;
        }

        .digits_login_form .dig_pgmdl_2 .digits-form-select .untselect-selection__rendered {
            color: <?php echo $input_text_color;?>;
        }

        .digits_login_form .dig_sbtncolor {
            color: <?php echo $button_text_color; ?>;
            background-color: <?php echo $button_bg_color; ?>;
        }

        .digits_login_form .dig_pgmdl_2 .digits-form-select .untselect-selection--single {
            background: <?php echo $input_bg_color;?>;
            padding-left: 1em;
            border: 1px solid<?php echo $input_border_color; ?>;
        }

        .digits_login_form .dig_pgmdl_2 .digits-form-select .untselect-selection .untselect-selection__arrow b::after {
            border-bottom: 1.5px solid <?php echo $input_border_color; ?> !important;
            border-right: 1.5px solid <?php echo $input_border_color; ?> !important;
        }

        .digits_login_form .dig_ma-box .bgdark {
            background-color: <?php echo $button_bg_color; ?>;
        }

        .digits_login_form .dig_ma-box .bgtransborderdark {
            border: 1px solid;
            border-color: <?php echo $signup_button_border_color; ?>;
            background: <?php echo $signup_button_color;?>;
        }

        .digits_login_form .dig_pgmdl_2 .minput .countrycodecontainer input,
        .digits_login_form .dig_pgmdl_2 .minput input[type='number'],
        .digits_login_form .dig_pgmdl_2 .minput input[type='password'],
        .digits_login_form .dig_pgmdl_2 .minput textarea,
        .digits_login_form .dig_pgmdl_2 .minput input[type='text'] {
            color: <?php echo $input_text_color;?> !important;
            background: <?php echo $input_bg_color;?>;
        }

        .digits_login_form .dig_pgmdl_2 .minput .countrycodecontainer input,
        .digits_login_form .dig_pgmdl_2 .minput input[type='number'],
        .digits_login_form .dig_pgmdl_2 .minput textarea,
        .digits_login_form .dig_pgmdl_2 .minput input[type='password'],
        .digits_login_form .dig_pgmdl_2 .minput input[type='text'],
        .digits_login_form .dig_pgmdl_2 input:focus:invalid:focus,
        .digits_login_form .dig_pgmdl_2 textarea:focus:invalid:focus,
        .digits_login_form .dig_pg_border_box,
        .digits_login_form .dig_pgmdl_2 select:focus:invalid:focus {
            border: 1px solid <?php echo $input_border_color;?> !important;
        }

        .digits_login_form .dig_ma-box .countrycodecontainer .dark {
            border-right: 1px solid <?php echo $input_border_color; ?> !important;
        }


        .digits_login_form .dig-bgleft-arrow-right {
            border-left-color: <?php echo $left_color;?>;
        }

        .digits_login_form .dig_pgmdl_2 .minput .countrycodecontainer .dig_input_error,
        .digits_login_form .dig_pgmdl_2 .minput .dig_input_error,
        .digits_login_form .dig_pgmdl_2 .minput .dig_input_error[type='number'],
        .digits_login_form .dig_pgmdl_2 .minput .dig_input_error[type='password'],
        .digits_login_form .dig_pgmdl_2 .minput .dig_input_error[type='text'],
        .digits_login_form .dig_pgmdl_2 .dig_input_error:focus:invalid:focus,
        .digits_login_form .dig_pgmdl_2 .dig_input_error:focus:invalid:focus,
        .digits_login_form .dig_pgmdl_2 .dig_input_error:focus:invalid:focus {
            border: 1px solid #E00000 !important;
        }


        <?php
            $footer_text_color = get_option('login_page_footer_text_color');
            if(!empty($footer_text_color)){
                echo '.dig_lp_footer,.dig_lp_footer *{color: '.$footer_text_color.';}';
            }
        ?>


        <?php }else{
            ?>
        .digits_login_form .dig_sbtncolor {
            color: <?php echo $fontcolor2; ?>;
            background-color: <?php echo $fontcolor1; ?>;
        }

        .digits_login_form .dig_ma-box .dark input[type="submit"], .digits_login_form .dig_ma-box .lighte {
            color: <?php echo $fontcolor2; ?>;
        }

        .digits_login_form .dig_ma-box .bgdark {
            background-color: <?php echo $fontcolor1; ?>;
        }

        .digits_login_form .dig_sml_box_msg_head,
        .digits_login_form .dig_ma-box .digits-form-select .untselect-selection__rendered,
        .digits_login_form .dig_ma-box .dark a, .digits_login_form .dig_ma-box .dark .dig-cont-close, .digits_login_form .dig_ma-box .dark,
        .digits_login_form .dig_ma-box .dark .minput label, .digits_login_form .dig_ma-box .dark .minput input, .digits_login_form .dig_ma-box .darke {
            color: <?php echo $fontcolor1; ?>;

        }

        .digits_login_form .dig_ma-box .countrycodecontainer .dark {
            border-right: 1px solid <?php echo $fontcolor1; ?> !important;
        }

        .digits_login_form .dig_ma-box .bgtransborderdark {
            border: 1px solid;
            border-color: <?php echo $fontcolor1; ?>;
            background: transparent;
        }

        .digits_login_form .dig_ma-box .digits-form-select .untselect-selection--single {
            border-bottom: 1px solid;
            border-color: <?php echo $fontcolor1; ?>;
        }

        .digits_login_form .digits-select .untselect-selection .untselect-selection__arrow b::after {
            border-bottom: 1.5px solid<?php echo $fontcolor1; ?>;
            border-right: 1.5px solid<?php echo $fontcolor1; ?>;
        }

        <?php
        }
        if(is_rtl()){
           ?>

        .digits_login_form .minput label {
            right: 0 !important;
            left: auto !important;
        }

        <?php
     }?>
    </style>
    <?php
}


add_action('dokan_vendor_reg_form_start', 'dig_remove_fields');

function dig_remove_fields()
{
    global $dig_logingpage;
    $dig_logingpage = 1;
}