<?php

namespace DigitsFormHandler;


use DigitsUserFormHandler\UserSettingsHandler;
use WP_User;


if (!defined('ABSPATH')) {
    exit;
}

Processor::instance();

final class Processor
{
    protected static $_instance = null;
    public $button_class = '';
    public $request_user_login = [];
    private $immediate_methods = false;
    private $remote_auth = false;

    public function __construct()
    {

    }

    public static function get_otp_actions($user_id, $step_no)
    {
        $allowed_methods = array_keys(self::_all_otp_actions());
        $methods = UserSettingsHandler::get_user_methods($user_id, $step_no);

        if (empty($methods)) {
            return $methods;
        }
        return array_intersect($allowed_methods, $methods);
    }

    public static function _all_otp_actions()
    {
        return array(
            '2fa_app' => array(
                'label' => __('2FA App', 'digits'),
                'placeholder' => __('2FA Code', 'digits')
            ),
            'sms_otp' => array(
                'label' => __('SMS', 'digits'),
                'placeholder' => __('SMS Passcode', 'digits')
            ),
            'whatsapp_otp' => array(
                'label' => __('WhatsApp', 'digits'),
                'placeholder' => __('WhatsApp Passcode', 'digits')
            ),
            'email_otp' => array(
                'label' => __('Email', 'digits'),
                'placeholder' => __('Email Passcode', 'digits')
            ),
        );
    }

    /**
     * @param WP_User $user
     * @param $step_no
     * @return void
     */
    public function step_html($user, $step_no, $request_type)
    {
        $user_id = $user->ID;

        $user_steps = UserSettingsHandler::get_user_methods($user, $step_no);

        $steps_details = $this->get_all_steps();

        $steps_tab = array(
            'password' => array('label' => __('Password', 'digits'), 'render' => 'password_tab'),
            'otp' => array('label' => __('OTP', 'digits'), 'render' => 'otp_tab'),
            'auth_device' => array('label' => __('Key', 'digits'), 'render' => 'auth_device_tab'),
        );

        $tabs = array();
        foreach ($user_steps as $step_key) {
            $tab_info = $steps_details[$step_key];
            $tab_key = $tab_info['tab'];
            if (!isset($tabs[$tab_key])) {
                $data = array('methods' => array());
                $data = array_merge($data, $steps_tab[$tab_key]);
                $tabs[$tab_key] = $data;
            }
            $tabs[$tab_key]['methods'][] = $step_key;
        }

        $login_step_type = 'digits_step_' . $step_no . '_type';
        ?>
        <div class="digits-form_tab_container">
            <?php
            if ($step_no == 1) {
                if (!empty($_REQUEST['show_force_title'])) {
                    $title = __('Login', 'digits');
                }
            } else {
                $title = $this->get_step_title($step_no);
            }

            if (!empty($title)) {
                echo '<span class="main-section-title digits_display_none">' . $title . '</span>';
            }
            ?>
            <div class="digits-form_tabs">
                <div class="digits-form_tab-bar">
                    <?php
                    foreach ($tabs as $key => $step_tab) {

                        $class = array('digits-form_tab-item');
                        $label = $step_tab['label'];
                        ?>
                        <div data-change="<?php echo $login_step_type; ?>" data-value="<?php echo $key; ?>"
                             class="<?php echo implode(" ", $class); ?>">
                            <?php _e('Use', 'digits'); ?><?php echo ' ' . $label; ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <div class="digits-form_body">
                <div class="digits-form_body_wrapper">
                    <?php
                    foreach ($tabs as $key => $step_tab) {
                        $class = array('digits-form_tab_body');
                        ?>
                        <div data-change="<?php echo $login_step_type; ?>"
                             class="<?php echo implode(" ", $class); ?>">
                            <?php
                            $render = $step_tab['render'];
                            $this->$render($user_id, $step_tab['methods'], $step_no, $request_type);
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * @return \string[][]
     */
    private function get_all_steps()
    {
        return array(
            'password' => array('tab' => 'password'),
            'email_otp' => array('tab' => 'otp'),
            'sms_otp' => array('tab' => 'otp'),
            'whatsapp_otp' => array('tab' => 'otp'),
            '2fa_app' => array('tab' => 'otp'),
            'cross-platform' => array('tab' => 'auth_device'),
            'platform' => array('tab' => 'auth_device'),
            'platform-all' => array('tab' => 'auth_device'),
        );
    }

    public function get_step_title($step_no)
    {
        $title = __('%s-Factor Authentication', 'digits');
        $titles = array(
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
        );
        $title = sprintf($title, $step_no);
        return $title;
    }

    public function password_tab($user_id, $methods, $step_no, $request_type)
    {

        ?>
        <div class="digits-form_input_row digits_password_inp_row">
            <div class="digits-form_input">
                <input type="password"
                       name="password"
                       autocomplete="current-password"
                       placeholder="<?php esc_attr_e('Password', 'digits'); ?>"/>
            </div>
            <div class="digits_password_eye-cont digits_password_eye">
                <svg class="digits_password_eye-open digit-eye" xmlns="http://www.w3.org/2000/svg" width="24"
                     height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <div class="digits_password_eye-open digits_password_eye-line digits_password_eye-default-line"></div>
            </div>
        </div>
        <?php
        if (digits_is_forgot_password_enabled()) {
            ?>
            <div class="digits-form_footer_content">
                <div class="digits-form_link digits-form_show_forgot_password">
                    <?php esc_attr_e('Forgot Password', 'digits'); ?>?
                </div>
            </div>
            <?php
        }
    }

    public function otp_tab($user_id, $methods, $step_no, $request_type)
    {

        $labels = self::_all_otp_actions();

        $hide_submit_button = true;


        $immediately_methods = [];
        $is_immediately = false;

        if ($step_no == 1) {
            $immediately_methods = $this->get_immediate_methods();

            $diff = array_diff($methods, $immediately_methods);
            $immediately_methods = array_intersect($methods, $immediately_methods);
            $is_immediately = sizeof($immediately_methods) > 0;
            if ($is_immediately) {
                $methods = $diff;
            }
            $immediately_methods = array_values($immediately_methods);
        }


        if ($is_immediately) {
            $this->render_otp_field($user_id, $immediately_methods[0], $step_no, $request_type);
            $hide_submit_button = false;
        } else if (sizeof($methods) == 1) {
            $this->render_otp_field($user_id, $methods[0], $step_no, $request_type);
            unset($methods[0]);
            $hide_submit_button = false;
        }

        foreach ($methods as $method) {

            if (!isset($labels[$method])) {
                continue;
            }

            if ($method == '2fa_app') {
                $text = __('<span>Use</span> %s', 'digits');
            } else {
                $text = __("<span>Send Passcode on</span> %s", 'digits');
            }
            $values = $labels[$method];
            $text = sprintf($text, $values['label']);

            $btn_class = '';
            if (!empty($this->button_class)) {
                $btn_class = $this->button_class;
            }
            ?>
            <div class="digits-form_input_row">
                <div class="digits-form_field_button digits-form_otp_selector <?php echo esc_attr($btn_class); ?>"
                     data-type="<?php esc_attr_e($method); ?>">
                    <?php echo $text; ?>
                </div>
            </div>
            <?php
        }
        if ($hide_submit_button) {
            ?>
            <input type="hidden" class="hide_submit">
            <?php
        }
    }

    public function get_immediate_methods()
    {
        if (empty($this->immediate_methods)) {
            $this->immediate_methods = get_digits_otp_immediately_methods();
        }
        return $this->immediate_methods;
    }

    public function render_otp_field($user_id, $method, $step_no, $request_type)
    {
        $this->render_otp_box($user_id, $method, $step_no, $request_type);
        ?>
        <input type="hidden"
               class="digits-form_otp_selector auto-click dig_process_data"
               data-disable_update="1"
               data-type="<?php esc_attr_e($method); ?>"/>
        <?php
    }

    public function render_otp_box($user_id, $action, $step_no, $request_type)
    {
        $extra_inp_class = '';
        $inp_wrapper_class = '';
        $auto_check = false;

        if ($request_type == 'login' && $this->is_immediate_method($user_id, $action, $step_no)) {
            $immediate_methods = $this->immediate_methods;
            $user_methods = UserSettingsHandler::get_user_methods($user_id, $step_no);
            $methods = array_intersect($user_methods, $immediate_methods);

            $placeholder = $this->get_otp_field_placeholder($methods);

            if (in_array('email_otp', $methods, true)) {
                $auto_check = true;
            }
        } else {
            $placeholder = $this->get_otp_field_placeholder($action);
        }

        if ($request_type == 'login' && $action == 'email_otp') {
            $auto_check = true;
        }


        if ($request_type == 'register') {
            $extra_inp_class = ' disable_auto_read';
        }

        if ($auto_check) {
            $inp_wrapper_class = 'digits_auto_check';
        }
        ?>
        <div class="digits_secure_login_auth_wrapper" data-change="otp_token_verify_status">
            <div class="digits-form_input_row ">
                <div class="digits-form_input digits-form_input_info <?php echo $inp_wrapper_class; ?>">
                    <input type="text"
                           class="otp_input digits_otp_input-field <?php echo $extra_inp_class; ?>"
                           name="<?php esc_attr_e($action); ?>"
                           autocomplete="one-time-code"
                           placeholder="<?php esc_attr_e($placeholder); ?>"/>
                    <div class="digits-form_footer_content">
                        <div class="digits-form_link digits-form_resend_otp digits_resend_disabled"
                             data-disable_update="true"
                             data-id="<?php echo esc_attr(uniqid('digits_timer_')); ?>"
                             style="display: none;">
                            <?php echo esc_attr(__('Resend OTP', 'digits')); ?>&nbsp;<span>00:00</span>
                        </div>
                    </div>
                    <?php
                    if ($auto_check) {
                        ?>
                        <input type="hidden"
                               class="otp_token_verify_status"
                               name="otp_token_verify_status"/>
                        <input type="hidden"
                               class="otp_token_key"
                               name="otp_token_key"/>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function is_immediate_method($user_id, $method, $step_no)
    {
        if ($step_no == 1) {
            if (in_array($method, $this->get_immediate_methods(), true)) {
                return true;
            }
        }
        return false;
    }

    public function get_otp_field_placeholder($action)
    {
        $all_actions = self::_all_otp_actions();
        if (is_array($action)) {
            $total_action = sizeof($action);

            if ($total_action == 3) {
                $placeholder = __('%s, %s or %s Passcode', 'digits');
            } else if ($total_action == 2) {
                $placeholder = __('%s or %s Passcode', 'digits');
            } else {
                $placeholder = __('%s Passcode', 'digits');
            }
            $labels = [];
            foreach ($action as $method) {
                $labels[] = $all_actions[$method]['label'];
            }

            return vsprintf($placeholder, $labels);
        }
        if ($action == '2fa_app') {
            return __('2FA Code', 'digits');
        }
        $placeholder = __('%s Passcode', 'digits');
        $placeholder = sprintf($placeholder, $all_actions[$action]['label']);
        return $placeholder;
    }

    public function addButtonClass($class)
    {
        $this->button_class = $class;
    }

    public function startRemoteLogin($url, $device_token)
    {
        $this->remote_auth = ['url' => $url, 'token' => $device_token];
    }

    public function auth_device_tab($user_id, $methods, $step_no, $request_type)
    {
        $action_name = reset($methods);
        $action_name = esc_attr($action_name);

        $auth_url = '';
        $auth_token = '';
        $hint_text = __("Please use your device's authentication or security key", "digits");
        if ($action_name == 'platform') {
            $hint_text = __("Please use your device's authentication", "digits");
        } else if ($action_name == 'platform-cross') {
            $hint_text = __("Please use your security key", "digits");
        }
        $allow_remote_login = false;
        $remote_login = false;
        $force_remote_login = false;

        if (in_array($action_name, ['platform-all', 'platform'], true)) {

            $devices = \DigitsDeviceAuth::instance()->getUserSecurityDevicesCategoryWise($user_id, $action_name);

            if (!empty($devices['mobile_devices'])) {
                $allow_remote_login = true;

                $remote_login = $this->remote_auth;

                $platform_count = $devices['platform'];
                $cross_platform_count = $devices['cross-platform'];
                $mobile_devices = $devices['mobile_devices'];

                $total_devices = $cross_platform_count + $platform_count;


                $is_mobile = wp_is_mobile();
                if ($mobile_devices == $total_devices && !$is_mobile) {
                    if (empty($remote_login)) {
                        $force_remote_login = true;
                        Handler::instance()->generate_remote_auth_token($user_id, $step_no);
                        $remote_login = $this->remote_auth;
                    }
                }

                if (!empty($remote_login)) {
                    $auth_url = $this->remote_auth['url'];
                    $auth_token = $this->remote_auth['token'];
                    $hint_text = __("Login with your Phone's Fingerprint or Face ID", "digits");
                }


            }

        }

        ?>
        <div class="digits_secure_login_auth_wrapper" data-change="platform_value">
            <div class="digits-form_input_row digits_secure_login_auth">
                <?php
                if (!empty($remote_login)) {
                    ?>
                    <div class="digits_secure_fingerprint_container">
                        <div class="digits_secure_phone_qr_wrap">
                            <div class="digits_secure_phone_qr_container">
                                <div class="digits_secure_qr_code digits_phone_scanner digits_auto_check">
                                    <?php
                                    echo digits_create_qr($auth_url);
                                    ?>
                                </div>
                                <div class="digits_secure_qr_code_hint">
                                    <?php echo esc_attr(__('Scan the QR code with your phone', 'digits')); ?>
                                    <input type="hidden" name="remote_device_auth" value="1"/>
                                    <input type="hidden" name="remote_device_auth_token"
                                           value="<?php echo esc_attr($auth_token); ?>"/>
                                </div>
                                <?php
                                if (!$force_remote_login) {
                                    ?>
                                    <div class="digits_secure_close-sic digits_remote_device_auth"
                                         data-remove="1"></div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="digits_secure_fingerprint_container">
                        <div class="digits_secure_fingerprint_icon digits_start_device_auth"></div>
                        <?php
                        if ($allow_remote_login) {
                            ?>
                            <div class="digits_secure_phone_wrapper digits_remote_device_auth">
                                <div class="digits_secure_phone_icon">
                                </div>
                                <?php
                                if (wp_is_mobile()) {
                                    esc_attr_e('Use Other Phone', 'digits');
                                } else {
                                    esc_attr_e('Use Phone', 'digits');
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="digits-form_hint">
                <?php echo $hint_text ?>
            </div>
            <input type="hidden" class="hide_submit">
            <input type="hidden" class="platform_authenticate dig_process_data">
            <input type="hidden" class="step_action_name" value="<?php echo $action_name; ?>">
            <input type="hidden" name="<?php echo $action_name; ?>" class="platform_value" value="">
        </div>
        <?php
    }

    /**
     *  Constructor.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function forgot_password($user, $html, $action)
    {
        $new_password = false;
        if ($action == 'new_password') {
            $tabs = array(
                'password' => array('label' => __('Password', 'digits'), 'hide' => false),
            );
            $new_password = true;
            $change = 'forgot_password_value';
        } else {
            $tabs = array(
                'otp' => array('label' => __('OTP', 'digits')),
            );
            $change = 'forgot_pass_method';
        }
        ?>
        <div class="digits-form_tab_container">
            <div class="digits-form_tabs">
                <div class="digits-form_tab-bar">
                    <?php
                    foreach ($tabs as $key => $step_tab) {

                        $class = array('digits-form_tab-item');
                        $label = $step_tab['label'];
                        $style = '';
                        if (!empty($step_tab['hide'])) {
                            $style = 'style="display:none;"';
                        }
                        ?>
                        <div data-change="<?php echo $change; ?>" data-value="<?php echo $key; ?>"
                             class="<?php echo implode(" ", $class); ?>" <?php echo $style; ?>>
                            <?php echo $label; ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <div class="digits-form_body">
                <div class="digits-form_body_wrapper">
                    <?php
                    foreach ($tabs as $key => $step_tab) {
                        $class = array('digits-form_tab_body');
                        ?>
                        <div data-change="<?php echo $change; ?>"
                             class="<?php echo implode(" ", $class); ?>">
                            <?php
                            if ($new_password) {
                                $this->forgot_pass_field();
                            } else {
                                echo $html;
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function forgot_pass_field()
    {
        ?>
        <div class="digits-form_input_row digits_password_inp_row">
            <div class="digits-form_input">
                <input type="password"
                       name="password"
                       class="new_password"
                       autocomplete="new-password"
                       placeholder="<?php esc_attr_e('New Password', 'digits'); ?>"/>
            </div>
        </div>
        <?php
    }

    public function mask_text($text)
    {
        if (in_array($text, $this->request_user_login, true)) {
            return $text;
        }

        $mail_parts = explode("@", $text, 2);
        $mail_parts[0] = $this->mask($mail_parts[0], 2, 1);
        if (sizeof($mail_parts) > 1) {
            return implode("@", $mail_parts);
        }

        return implode("", $mail_parts);
    }

    public function mask($str, $first, $last)
    {
        $len = strlen($str);
        $toShow = $first + $last;
        return substr($str, 0, $len <= $toShow ? 0 : $first) . str_repeat("*", $len - ($len <= $toShow ? 0 : $toShow)) . substr($str, $len - $last, $len <= $toShow ? 0 : $last);
    }

    public function mask_phone($phone)
    {
        $phone = $this->mask($phone, 2, 1);
        return $phone;
    }

    public function code_hint_box($targets, $request_user_text)
    {
        ob_start();
        $this->code_hint_box_html($targets, $request_user_text);
        return ob_get_clean();
    }

    private function code_hint_box_html($targets, $request_user_text)
    {
        $this->request_user_login = $request_user_text;
        ?>
        <div class="digits_otp_info">
            <div class="digits_otp_info_ic"></div>
            <div class="digits_otp_info_desc">
                <div class="digits_otp_info_desc_text">
                    <?php
                    if (in_array('2fa_app', $targets)) {
                        esc_attr_e('Please type the verification code shown in your authentication app', 'digits');
                    } else {
                        $targets_obj = array_map([$this, 'mask_text'], $targets);
                        $targets_str = implode(" & ", $targets_obj);
                        esc_attr_e('Please type the verification code sent to', 'digits');
                        ?>
                        <span class="digits_otp_dest"><?php echo $targets_str; ?></span>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}

