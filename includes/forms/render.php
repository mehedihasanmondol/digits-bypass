<?php


if (!defined('ABSPATH')) {
    exit;
}


function digits_use_new_form_style()
{
    $dig_enable_new_design = get_option('dig_new_forms', 1);
    return $dig_enable_new_design == 'on' || $dig_enable_new_design == 1;
}


function digits_new_form_page($modal = false, $details = array())
{
    $dig_login_details = digit_get_login_fields();

    $details['login_details'] = $dig_login_details;
    $details['users_can_register'] = get_option('dig_enable_registration', 1);

    $theme = get_option('dig_form_theme', 'automatic');
    $theme_class = 'digits-auto-theme';
    if ($theme == 'dark') {
        $theme_class = 'digits-dark-theme';
    }
    $back_title = __('Cancel');
    $back_url = '';
    if (!$modal) {
        if (isset($_REQUEST['back'])) {
            $back_url = "//" . $_SERVER['HTTP_HOST'];
        } else {
            $back_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $back_url = remove_query_arg(array('login', 'type'), $back_url);
        }
        $back_title = __('Go Back');
    }

    $style = digits_new_form_create_style();
    $details['style'] = $style;


    $dig_cust_forms = apply_filters('dig_hide_forms', 0);

    if ($dig_cust_forms === 1) {
        $details['use_custom_form'] = true;
    }


    ?>
    <div class="digits-form_page <?php echo $theme_class; ?>">
        <?php
        digits_render_new_form($details);
        /*<div class="digits-hide_modal dig_login_cancel"></div>*/
        ?>
        <div class="digits-cancel dig_login_cancel"
             title="<?php echo esc_attr($back_title); ?>"
            <?php if (!empty($back_url)) {
                echo 'data-back="' . esc_attr($back_url) . '"';
            } ?>
        ></div>
    </div>
    <?php
}

function digits_new_form_create_style($styles = array(), $theme = '', $class = '')
{
    if (empty($styles)) {
        $theme = get_option('dig_form_theme', 'automatic');
        $styles = digits_theme_values();
    }

    $data = array();
    $data['light'] = digits_new_form_get_style($styles['light']);
    $data['dark'] = digits_new_form_get_style($styles['dark']);
    /*add tp style*/
    ?>
    <style>
        .digits-main_style, .digits-tp_style, .digits-form_page, .digits-form_container {
        <?php echo $data['light'];?>
        }

        .digits-main_style.digits-dark-theme, .digits-form_page.digits-dark-theme, .digits-form_container.digits-dark-theme,
        .digits-dark-theme .digits-form_container {
        <?php echo $data['dark'];?>
        }

        <?php
        if($theme != 'light'){
            ?>

        @media (prefers-color-scheme: dark) {
            .digits-main_style, .digits-form_page, .digits-form_container {
            <?php echo $data['dark'];?>
            }
        }

        <?php
            }
        ?>
    </style>
    <?php
    $light_logo = !empty($styles['light']['logo']) ? $styles['light']['logo'] : '';
    $dark_logo = !empty($styles['dark']['logo']) ? $styles['dark']['logo'] : '';
    if ($theme != 'automatic') {
        $theme_logo = !empty($styles[$theme]['logo']) ? $styles[$theme]['logo'] : '';
        $light_logo = $theme_logo;
        $dark_logo = $theme_logo;
    }

    $data['theme'] = $theme;
    $data['light_logo'] = $light_logo;
    $data['dark_logo'] = $dark_logo;

    return $data;
}

function digits_new_form_get_style($styles)
{
    ob_start();
    digits_new_form_css_style($styles);
    return ob_get_clean();
}

function digits_new_form_css_style($style)
{
    ?>
    --dprimary: <?php echo $style['primary_color']; ?>;
    --dtitle: <?php echo $style['title_color']; ?>;
    --dfield_bg: <?php echo $style['field_bg_color']; ?>;
    --daccent: <?php echo $style['accent_color']; ?>;
    --dform_bg: <?php echo $style['form_bg_color']; ?>;
    --dbutton_text: <?php echo $style['button_text_color']; ?>;
    <?php
}

function digits_render_new_form($details)
{
    $details['userCountryCode'] = getUserCountryCode(true);

    if (empty($details['button_texts'])) {
        $details['button_texts'] = digits_form_button();
    }

    if (isset($details['type'])) {
        $_REQUEST['type'] = $details['type'];
    }

    if (!isset($details['login_details'])) {
        $dig_login_details = digit_get_login_fields();
        $details['login_details'] = $dig_login_details;
    }
    if (!isset($details['users_can_register'])) {
        $details['users_can_register'] = get_option('dig_enable_registration', 1);
    }

    $type = 'login-register';

    $pages = array('login', 'login-register', 'forgot-password', 'register');

    if (empty($details['page_type'])) {
        if (!empty($_REQUEST['type']) && in_array($_REQUEST['type'], $pages)) {
            $type = $_REQUEST['type'];
        }
        $details['page_type'] = $type;
    }
    ?>
    <div class="digits-form_container digits">
        <div class="digits-form_wrapper digits_modal_box digits2_box">
            <?php
            if (isset($details['approval_form'])) {
                digits_render_approval_form($details);
            } else {
                if (!empty($details['use_custom_form'])) {
                    do_action('digits_render_custom_form', $details);
                } else {
                    if (empty($details['disable_login'])) {
                        digits_render_login_form($details);
                    }
                    if (empty($details['disable_forgot'])) {
                        digits_render_forgot_form($details);
                    }
                    if (empty($details['disable_register'])) {
                        digits_render_register_form($details);
                    }
                }
            }
            ?>
        </div>

        <div class="dig_load_overlay">
            <div class="dig_load_content">
                <div class="dig_spinner">
                    <div class="dig_double-bounce1"></div>
                    <div class="dig_double-bounce2"></div>
                </div>
            </div>
        </div>
        <?php
        if (!empty($details['style'])) {
            do_action('digits_box_wrapper', $details['style']);
        }
        $custom_css = digits_get_custom_css();
        if (!empty($custom_css)) {
            ?>
            <style>
                <?php
                echo $custom_css;
               ?>
            </style>
            <?php
        }
        ?>
    </div>
    <?php

}


function digits_get_redirect_uri($details)
{
    if (!empty($details['redirect_to'])) {
        $redirect_to = $details['redirect_to'];
    } else {
        $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $redirect_to = remove_query_arg(array('login', 'type'), $current_url);
    }
    return $redirect_to;
}

function digits_render_forgot_form($details)
{
    if (!digits_is_forgot_password_enabled()) {
        return;
    }

    $userCountry = strtolower($details['userCountryCode']['country']);
    $userCountryCode = $details['userCountryCode']['code'];

    $redirect_to = digits_get_redirect_uri($details);

    $t = get_option("digits_forgotred");
    if (!empty($t)) {
        $redirect_to = -1;
    }

    if (isset($details['forgot_redirect'])) {
        $redirect_to = $details['forgot_redirect'];
    }

    $hide_form = $details['page_type'] != 'forgot-password';

    $login_details = $details['login_details'];
    $emailaccep = $login_details['dig_login_email'];
    $mobileaccp = $login_details['dig_login_mobilenumber'];
    $usernameaccep = $login_details['dig_login_username'];

    $emailActiveClass = '';
    $action_type = 'phone';

    if (isset($details['forgot_title'])) {
        $title = esc_attr($details['forgot_title']);
    } else {
        $title = esc_attr__('Reset Password', 'digits');
    }

    ?>
    <form class="digits_form_index_section forgot digits_original" method="post" enctype="multipart/form-data"
        <?php if ($hide_form) {
            echo 'style="display: none;"';
        } ?>
    >
        <div class="digits-form_forgot_password">
            <div class="digits-form_heading">
                <span class="digits_back_icon digits_hide_back digits_form_back"></span>
                <span class="digits-form_heading_text" data-text="<?php echo $title; ?>"><?php echo $title; ?></span>
            </div>
            <div class="digits-form_tab_wrapper">
                <div class="digits-form_tab_container">
                    <div class="digits-form_tabs">
                        <div class="digits-form_tab-bar">
                            <?php
                            if ($mobileaccp > 0) {
                                echo '<div data-change="action_type" data-value="phone" class="digits-form_tab-item digits_login_use_phone digits-tab_active">' . __('Use Phone Number', 'digits') . '</div>';
                            } else {
                                $emailActiveClass = 'digits-tab_active';
                                $action_type = 'email';
                            }

                            $show_username = $emailaccep == 0 && $usernameaccep == 1;

                            $email_label = __('Email Address', 'digits');
                            if ($show_username) {
                                $email_label = __('Username', 'digits');
                            }
                            $tab_label = __('Use %s', 'digits');
                            $tab_label = sprintf($tab_label, $email_label);

                            if ($emailaccep > 0 || $show_username) {
                                echo '<div data-change="action_type" data-value="email" class="digits-form_tab-item digits_login_use_email ' . $emailActiveClass . '">' . $tab_label . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="digits-form_body">
                        <div class="digits-form_body_wrapper">
                            <?php if ($mobileaccp > 0) { ?>
                                <div class="digits-form_tab_body digits-tab_active">
                                    <div class="digits-form_input_row digits-mobile_wrapper digits-form_border">
                                        <div class="digits-form_input digits-form_countrycode countrycodecontainer digits_countrycodecontainer">
                                            <span class="digits-field-country_flag untdovr_flag_container_flag"></span>
                                            <input type="text" name="login_digt_countrycode"
                                                   class="input-text countrycode digits_countrycode country_code_flag"
                                                   value="<?php if (isset($countrycode)) {
                                                       echo esc_attr($countrycode);
                                                   } else {
                                                       echo esc_attr($userCountryCode);
                                                   } ?>"
                                                   country="<?php echo esc_attr($userCountry); ?>"
                                                   maxlength="6" size="3"
                                                   placeholder="<?php echo esc_attr($userCountryCode); ?>"
                                                   autocomplete="tel-country-code"/>
                                        </div>
                                        <div class="digits-form_input">
                                            <input type="tel"
                                                   class="mobile_field mobile_format dig-mobmail dig-mobile_field mobile_placeholder"
                                                   name="digits_phone"
                                                   autocomplete="tel-national"
                                                   placeholder="<?php esc_attr_e('Phone Number', 'digits'); ?>"
                                                   data-placeholder="<?php esc_attr_e('Phone Number', 'digits'); ?>"
                                                   style="padding-left: 123px"
                                                   value="" data-type="2" required/>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($emailaccep > 0 || $show_username) { ?>
                                <div class="digits-form_tab_body <?php echo $emailActiveClass; ?>">
                                    <div class="digits-form_input_row">
                                        <div class="digits-form_input">
                                            <input
                                                    name="digits_email"
                                                    type="email"
                                                    autocomplete="email"
                                                    placeholder="<?php echo esc_attr($email_label); ?>"
                                                    required
                                            />
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <input type="hidden" name="action_type" value="<?php echo esc_attr($action_type); ?>"
                           autocomplete="off"/>
                </div>

            </div>
            <button class="digits-form_button digits-form_submit digits-form_submit-btn" type="submit">
                <span class="digits-form_button-text">
                    <?php esc_attr_e('Continue', 'digits'); ?>
                </span>
                <span class="digits-form_button_ic"></span>
            </button>
            <div class="digits-form_footer">

            </div>
            <input type="hidden" name="instance_id" value="<?php echo esc_attr(md5(uniqid())); ?>"
                   autocomplete="off"/>
            <input type="hidden" name="action" value="digits_forms_ajax" autocomplete="off"/>
            <input type="hidden" name="type" value="forgot" autocomplete="off"/>

            <input type="hidden" name="forgot_pass_method" autocomplete="off"/>
            <input type="hidden" name="forgot_password_value" autocomplete="off"/>
            <input type="hidden" name="digits" value="1"/>

            <input type="hidden" name="digits_redirect_page"
                   value="<?php echo esc_attr($redirect_to); ?>"/>

        </div>

        <?php
        wp_nonce_field('digits_login_form', 'digits_form');

        if (!empty($details['extra_data'])) {
            echo $details['extra_data'];
        }
        ?>
    </form>
    <?php
}

function digits_render_login_form($details)
{
    $userCountry = strtolower($details['userCountryCode']['country']);
    $userCountryCode = $details['userCountryCode']['code'];

    $redirect_to = digits_get_redirect_uri($details);

    $t = get_option("digits_loginred");
    if (!empty($t)) {
        $redirect_to = -1;
    }

    $login_redirect = $redirect_to;

    if (isset($details['login_redirect'])) {
        $login_redirect = $details['login_redirect'];
    }

    $hide_form = !in_array($details['page_type'], array('login', 'login-register'));

    $login_details = $details['login_details'];
    $users_can_register = $details['users_can_register'];

    $emailaccep = $login_details['dig_login_email'];
    $mobileaccp = $login_details['dig_login_mobilenumber'];
    $usernameaccep = $login_details['dig_login_username'];
    $captcha = $login_details['dig_login_captcha'];
    $emailActiveClass = '';
    $action_type = 'phone';

    $login_type = '';

    if (isset($details['login_title'])) {
        $title = esc_attr($details['login_title']);
    } else {
        $title = esc_attr__('Login', 'digits');
    }
    ?>
    <form class="digits_form_index_section digloginpage digits_original" method="post" enctype="multipart/form-data"
        <?php if ($hide_form) {
            echo 'style="display: none;"';
        } ?>>
        <div class="digits-form_login">
            <div class="digits-form_heading">
                <span class="digits_back_icon digits_hide_back digits_form_back"></span>
                <span class="digits-form_heading_text" data-text="<?php echo $title; ?>"><?php echo $title; ?></span>
            </div>
            <div class="digits-form_tab_wrapper">
                <div class="digits-form_tab_container">
                    <div class="digits-form_tabs">
                        <div class="digits-form_tab-bar">
                            <?php
                            if ($mobileaccp > 0) {
                                echo '<div data-change="action_type" data-value="phone" class="digits-form_tab-item digits_login_use_phone digits-tab_active">' . __('Use Phone Number', 'digits') . '</div>';
                            } else {
                                $emailActiveClass = 'digits-tab_active';
                                $action_type = 'email';
                            }

                            $show_username = $emailaccep == 0 && $usernameaccep == 1;

                            $email_label = __('Email Address', 'digits');
                            if ($show_username) {
                                $email_label = __('Username', 'digits');
                            }
                            $tab_label = __('Use %s', 'digits');
                            $tab_label = sprintf($tab_label, $email_label);

                            if ($emailaccep > 0 || $show_username) {
                                echo '<div data-change="action_type" data-value="email" class="digits-form_tab-item digits_login_use_email ' . $emailActiveClass . '">' . $tab_label . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="digits-form_body">
                        <div class="digits-form_body_wrapper">
                            <?php if ($mobileaccp > 0) { ?>
                                <div class="digits-form_tab_body digits-tab_active">
                                    <div class="digits-form_input_row digits-mobile_wrapper digits-form_border">
                                        <div class="digits-form_input digits-form_countrycode countrycodecontainer digits_countrycodecontainer">
                                            <span class="digits-field-country_flag untdovr_flag_container_flag"></span>
                                            <input type="text" name="login_digt_countrycode"
                                                   class="input-text countrycode digits_countrycode country_code_flag"
                                                   value="<?php if (isset($countrycode)) {
                                                       echo esc_attr($countrycode);
                                                   } else {
                                                       echo esc_attr($userCountryCode);
                                                   } ?>"
                                                   country="<?php echo esc_attr($userCountry); ?>"
                                                   maxlength="6" size="3"
                                                   placeholder="<?php echo esc_attr($userCountryCode); ?>"
                                                   autocomplete="tel-country-code"/>
                                        </div>
                                        <div class="digits-form_input">
                                            <input type="tel"
                                                   class="mobile_field mobile_format dig-mobmail dig-mobile_field mobile_placeholder"
                                                   name="digits_phone"
                                                   autocomplete="tel-national"
                                                   placeholder="<?php esc_attr_e('Phone Number', 'digits'); ?>"
                                                   data-placeholder="<?php esc_attr_e('Phone Number', 'digits'); ?>"
                                                   style="padding-left: 123px"
                                                   value="<?php if (isset($username)) {
                                                       echo $username;
                                                   } ?>" data-type="2" required/>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($emailaccep > 0 || $show_username) { ?>
                                <div class="digits-form_tab_body <?php echo $emailActiveClass; ?>">
                                    <div class="digits-form_input_row">
                                        <div class="digits-form_input">
                                            <input
                                                    name="digits_email"
                                                    type="email"
                                                    autocomplete="email"
                                                    placeholder="<?php echo esc_attr($email_label); ?>"
                                                    required
                                            />
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <?php /*if ($otpaccp == 0) {
                            $login_type = 'password';

                            <div class="digits_login_step digits_login_step1">
                                <div class="digits-form_input_row">
                                    <div class="digits-form_input">
                                        <input type="password"
                                               name="password"
                                               autocomplete="current-password"
                                               placeholder="<?php esc_attr_e('Password', 'digits'); ?>"/>
                                    </div>
                                </div>
                            </div>

                        }*/ ?>
                        <input type="hidden" name="action_type" value="<?php echo esc_attr($action_type); ?>"
                               autocomplete="off"/>
                    </div>
                    <?php
                    if ($captcha > 0) {
                        ?>
                        <div class="digits_captcha_row">
                            <?php
                            dig_show_login_captcha(11, null, 0, 'digits-form_input_row', true, $captcha);
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="digits_form-init_step_data">
                <?php dig_rememberMe(); ?>
            </div>
            <button class="digits-form_button digits-form_submit digits-form_submit-btn" type="submit">
                <span class="digits-form_button-text">
                    <?php esc_attr_e('Continue', 'digits'); ?>
                </span>
                <span class="digits-form_button_ic"></span>
            </button>
            <div class="digits-form_footer">

            </div>
            <input type="hidden" name="digits" value="1"/>
            <?php
            digits_login_hidden_fields($login_redirect, true);
            ?>
            <?php
            if ($users_can_register == 1 && $details['page_type'] != 'login') {
                ?>
                <div class="dig_login_signup_bar digits-title_color digits_show_on_index">
                    <span><?php echo __('Not a member yet?', 'digits'); ?></span>
                    <a href="#" class="digits-form_toggle_login_register show_register">
                        <?php echo __('Register Now', 'digits'); ?>
                    </a>
                </div>
                <?php
            }
            if (digits_is_forgot_password_enabled()) {
                ?>
                <div class="digits-hide">
                    <div class="digits-form_show_forgot_password digits_reset_pass"></div>
                    <div class="digits-form_toggle_login_register show_login"></div>
                </div>
                <?php
            }

            global $dig_logingpage;
            $dig_logingpage = 1;
            do_action('login_form');
            $dig_logingpage = 0;
            ?>
        </div>
        <?php
        if (!empty($details['extra_data'])) {
            echo $details['extra_data'];
        }
        ?>
    </form>
    <?php
}

function digits_login_hidden_fields($login_redirect, $referrer)
{
    ?>
    <input type="hidden" name="instance_id" value="<?php echo esc_attr(md5(uniqid())); ?>"
           autocomplete="off"/>
    <input type="hidden" name="action" value="digits_forms_ajax" class="digits_action_type" autocomplete="off"/>
    <input type="hidden" name="type" value="login" class="digits_action_type" autocomplete="off"/>

    <input type="hidden" name="digits_step_1_type" value=""
           autocomplete="off"/>
    <input type="hidden" name="digits_step_1_value" value=""
           autocomplete="off"/>
    <input type="hidden" name="digits_step_2_type" value=""
           autocomplete="off"/>
    <input type="hidden" name="digits_step_2_value" value=""
           autocomplete="off"/>

    <input type="hidden" name="digits_step_3_type" value=""
           autocomplete="off"/>
    <input type="hidden" name="digits_step_3_value" value=""
           autocomplete="off"/>

    <input type="hidden" name="digits_login_email_token" value="" class="reset_on_back"/>

    <input type="hidden" name="digits_redirect_page"
           value="<?php echo esc_attr($login_redirect); ?>"/>
    <?php
    wp_nonce_field('digits_login_form', 'digits_form', $referrer);
}

function digits_render_register_form($details)
{
    $userCountry = $details['userCountryCode']['country'];
    $userCountryCode = $details['userCountryCode']['code'];

    $redirect_to = digits_get_redirect_uri($details);

    $t = get_option("digits_regred");
    if (!empty($t)) {
        $redirect_to = -1;
    }

    if (isset($details['register_redirect'])) {
        $redirect_to = $details['register_redirect'];
    }

    $form_class = array();

    if (isset($details['show_label'])) {
        $show_labels = $details['show_label'];
    } else {
        $show_labels = get_option('dig_show_labels', 0);
    }
    if (empty($show_labels)) {
        $form_class[] = 'digits_hide_label';
    }

    $hide_form = $details['page_type'] != 'register';

    $users_can_register = $details['users_can_register'];


    if ($users_can_register != 1) {
        return;
    }
    if (isset($details['reg_fields'])) {
        $dig_reg_details = $details['reg_fields'];
    } else {
        $dig_reg_details = digit_get_reg_fields();
    }

    if (isset($details['register_title'])) {
        $title = esc_attr($details['register_title']);
    } else {
        $title = esc_attr__('Register', 'digits');
    }

    ?>
    <form class="digits_form_index_section register digits_register digits_original <?php echo implode(' ', $form_class); ?>"
          method="post"
          enctype="multipart/form-data"
        <?php if ($hide_form) {
            echo 'style="display: none;"';
        } ?>
    >
        <div class="digits-form_register">
            <div class="digits-form_heading">
                <span class="digits_back_icon digits_hide_back digits_form_back"></span>
                <span class="digits-form_heading_text" data-text="<?php echo $title; ?>"><?php echo $title; ?></span>
            </div>

            <div class="digits-form_tab_wrapper">
                <?php
                $renderer = new DigitsSignupFields();
                if (isset($details['fields_data'])) {
                    $renderer->initFields($details['fields_data']);
                } else {
                    $renderer->initNativeFields();
                }
                $renderer->setRegDetails($dig_reg_details);
                $renderer->render();

                ?>
            </div>

            <button class="digits-form_button digits-form_submit digits-form_submit-btn"
                    data-subaction="signup"
                    type="submit">
                <span class="digits-form_button-text">
                    <?php esc_attr_e('Continue', 'digits'); ?>
                </span>
                <span class="digits-form_button_ic"></span>
            </button>
            <div class="digits-form_footer">

            </div>
            <input type="hidden" name="instance_id" value="<?php echo esc_attr(md5(uniqid())); ?>"
                   autocomplete="off"/>
            <input type="hidden" name="optional_data" value="optional_data" autocomplete="off"/>
            <input type="hidden" name="action" value="digits_forms_ajax" autocomplete="off"/>
            <input type="hidden" name="type" value="register" autocomplete="off"/>
            <input type="hidden" name="dig_otp" value=""/>
            <input type="hidden" name="digits" value="1"/>
            <input type="hidden" name="digits_redirect_page"
                   value="<?php echo esc_attr($redirect_to); ?>"/>


            <?php
            if (empty($details['disable_login'])) {

                ?>
                <div class="dig_login_signup_bar digits-title_color digits_show_on_index">
                    <span><?php echo __('Already a member?', 'digits'); ?></span>
                    <a href="#" class="digits-form_toggle_login_register show_login">
                        <?php echo __('Login Now', 'digits'); ?>
                    </a>
                </div>
                <?php
            } ?>
            <div>
                <?php
                global $dig_logingpage;
                $dig_logingpage = 1;
                do_action('register_form');
                $dig_logingpage = 0;
                ?>
            </div>
        </div>
        <?php
        wp_nonce_field('digits_login_form', 'digits_form');

        if (!empty($details['extra_data'])) {
            echo $details['extra_data'];
        }
        ?>
    </form>
    <?php
}

function digits_ui_reg_email_field($name, $show_label = false)
{
    if ($show_label == -1) {
        $show_label = get_option('dig_show_labels', 0) == 1;
    }

    $name = esc_attr($name);
    ?>
    <div id="dig_cs_email" class="digits-form_input_row">
        <div class="digits-form_input">
            <?php
            if ($show_label) {
                ?>
                <label class="field_label main_field_label"><?php esc_attr_e('Email Address', 'digits'); ?></label>
                <?php
            }
            ?>
            <input
                    name="<?php echo esc_attr($name); ?>"
                    type="email"
                    autocomplete="email"
                    placeholder="<?php esc_attr_e('Email Address', 'digits'); ?>"/>
        </div>
    </div>
    <?php
}

function digits_ui_reg_phone_field($name, $userCountryCode, $userCountry, $show_label = false)
{
    if ($show_label == -1) {
        $show_label = get_option('dig_show_labels', 0) == 1;
    }


    $userCountryCode = esc_attr($userCountryCode);
    $userCountry = strtolower(esc_attr($userCountry));

    $phone_name = $name . 'phone';
    $country_code_name = $name . 'digt_countrycode';

    ?>
    <div class="digits-form_input_row">
        <?php
        if ($show_label) {
            ?>
            <div class="digits-form_input">
                <label class="field_label main_field_label"><?php esc_attr_e('Phone Number', 'digits'); ?></label>
            </div>
            <?php
        }
        ?>

        <div
                id="dig_cs_mobilenumber"
                class="digits-mobile_wrapper digits-form_border">

            <div class="digits-form_input digits-form_countrycode countrycodecontainer digits_countrycodecontainer">
                <span class="digits-field-country_flag untdovr_flag_container_flag"></span>
                <input type="text" name="<?php echo esc_attr($country_code_name); ?>"
                       class="input-text countrycode digits_countrycode country_code_flag"
                       value="<?php echo esc_attr($userCountryCode); ?>"
                       country="<?php echo esc_attr($userCountry); ?>"
                       maxlength="6" size="3" placeholder="<?php echo esc_attr($userCountryCode); ?>"
                       autocomplete="tel-country-code"/>
            </div>
            <div class="digits-form_input">
                <input type="tel"
                       class="mobile_field mobile_format dig-mobmail dig-mobile_field mobile_placeholder"
                       name="<?php echo esc_attr($phone_name); ?>"
                       autocomplete="tel-national"
                       placeholder="<?php esc_attr_e('Phone Number', 'digits'); ?>"
                       data-placeholder="<?php esc_attr_e('Phone Number', 'digits'); ?>"
                       style="padding-left: 123px"
                       value="" data-type="2"/>
            </div>
        </div>
    </div>
    <?php
}
