<?php

namespace DigitsFormHandler;


use DigitsSignupFields;
use Exception;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}


class UserRegistration
{

    const USER_VERIFY_EMAIL_KEY = 'digits_email_verification_key';
    const USER_VERIFY_EMAIL_KEY_GEN_TIME = 'digits_email_verification_key_time';
    const USER_VERIFY_LINK_VALIDITY_EXPIRE = false;
    const USER_VERIFY_LINK_VALIDITY_SEC = 604800;
    const USER_VERIFIED_EMAIL = 'digits_email_verified_email';
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @throws Exception
     */
    public function process()
    {
        $request = $this->data;

        $user_can_register = get_option('dig_enable_registration', 1);
        if ($user_can_register == 0) {
            throw new Exception(__('Registrations are not enabled!', 'digits'));
        }

        $skip_otp_verification = get_option('dig_reg_skip_otp_verification', 0);

        $user_id = null;
        $dig_reg_details = apply_filters('digits_registration_default_fields', digit_get_reg_fields());

        $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
        $reg_custom_fields = json_decode($reg_custom_fields, true);
        $reg_builder_fields = apply_filters('digits_registration_all_fields', []);
        $is_builder = false;
        if (!empty($reg_builder_fields)) {
            $is_builder = true;
            $reg_custom_fields = $reg_builder_fields;
        }

        $nameaccep = $dig_reg_details['dig_reg_name'];
        $usernameaccep = $dig_reg_details['dig_reg_uname'];
        $emailaccep = $dig_reg_details['dig_reg_email'];
        $passaccep = $dig_reg_details['dig_reg_password'];
        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

        if ($nameaccep > 0) {
            if (!empty($request['digits_reg_firstname'])) {
                $name = sanitize_text_field($request['digits_reg_firstname']);
            } else if (!empty($request['digits_reg_name'])) {
                $name = sanitize_text_field($request['digits_reg_name']);
            } else {
                $name = '';
            }
        }

        $email = '';
        $mobile = '';
        $countrycode = '';

        $email_key = 'email';
        $countrycode_key = 'digt_countrycode';
        $phone_key = 'phone';

        $show_optional_step = false;

        $action_type = $this->get('action_type', false);
        if (!empty($action_type)) {
            if ($mobileaccp == 1 || $emailaccep == 1) {
                $optional_key = 'optional_';
                if ($action_type == 'email') {
                    $countrycode_key = $optional_key . $countrycode_key;
                    $phone_key = $optional_key . $phone_key;
                } else {
                    $email_key = $optional_key . $email_key;
                }
                $show_optional_step = true;
            }
        }

        $validation_error = new WP_Error();

        if ($emailaccep > 0) {
            $email = sanitize_email($this->get($email_key, false));
        }

        if ($mobileaccp > 0) {
            $countrycode = $this->get($countrycode_key, false);
            $raw_mobile = $this->get($phone_key, false);
            $mobile = sanitize_mobile_field_dig($raw_mobile);
        }
        $phone = $countrycode . $mobile;


        $username = '';
        if ($usernameaccep > 0) {
            $username = sanitize_text_field($this->get('digits_reg_username', false));
        }

        if (empty($this->get('digits_process_register', false))) {
            $this->validate_basic_info($phone, $email, $username);
            $this->show_next_step($dig_reg_details, $reg_custom_fields, $is_builder);
        }


        if ($passaccep > 0) {
            $password = sanitize_text_field($this->get('digits_reg_password', false));
        }
        if ($passaccep == 0) {
            $password = wp_generate_password();
        } else if ($passaccep == 2 && empty($password)) {
            $validation_error->add("invalidpassword", __("Invalid Password!", "digits"));
        } else if (empty($password)) {
            $password = wp_generate_password();
        } else {
            if (strlen($password) < 6) {
                $validation_error->add("weakpassword", __("Please use a strong password!", "digits"));
                $this->check_errors($validation_error);
            }
        }

        Handler::instance()->addUserRequestData($phone);
        Handler::instance()->addUserRequestData($email);

        if ($emailaccep == 2) {
            if (empty($email) || !isValidEmail($email)) {
                $validation_error->add("Mail", __("Please enter a valid Email!", "digits"));
            }
        } else if ($emailaccep == 1 && !empty($email) && !isValidEmail($email)) {
            $validation_error->add("Mail", __("Please enter a valid Email!", "digits"));
        }

        if (!empty($email) && email_exists($email)) {
            $validation_error->add("MailinUse", __("Email is already in use!", "digits"));
        }

        $validation_error = apply_filters('digits_validate_email', $validation_error, $email);
        $this->check_errors($validation_error);

        if ($mobileaccp == 2) {

            if (!$this->isValidMobile($mobile)) {
                $validation_error->add("Mobile", __("Please enter a valid Mobile Number!", "digits"));
            }
        } else if ($mobileaccp == 1 && !empty($mobile)) {
            if (!$this->isValidMobile($mobile)) {
                $validation_error->add("Mobile", __("Please enter a valid Mobile Number!", "digits"));
            }
        }


        if ($mobileaccp == 1 && $emailaccep == 1) {
            if (!is_numeric($mobile) && !isValidEmail($email)) {
                $validation_error->add("Mobile", __("Please enter a valid Email\Mobile Number!", "digits"));
            }

            if (!empty($email) && !isValidEmail($email)) {
                $validation_error->add("Mail", __("Invalid Email!", "digits"));
            }
            if (!empty($email) && email_exists($email)) {
                $validation_error->add("MailinUse", __("Email is already in use!", "digits"));
            }

        }

        if (!empty($mobile)) {
            $mobuser = getUserFromPhone($phone);
            if ($mobuser != null) {
                $validation_error->add("MobinUse", __("Mobile Number is already in use!", "digits"));
            } else if (username_exists($phone)) {
                $validation_error->add("MobinUse", __("Mobile Number is already in use!", "digits"));
            }

            if (!checkwhitelistcode($countrycode)) {
                $error = __('At the moment, we do not allow users from your country', ' digits');
                throw new Exception($error);
            }
            $is_phone_allowed = dig_is_phone_no_allowed($phone);
            if (!$is_phone_allowed) {
                $error = __('This phone number is not allowed!', ' digits');
                throw new Exception($error);
            }
        }


        $this->check_errors($validation_error);

        $validation_error = validate_digp_reg_fields($reg_custom_fields, $validation_error);

        $this->check_errors($validation_error);
        if ($show_optional_step && ($mobileaccp == 1 || $emailaccep == 1)) {
            $optional_data = $this->get('is_digits_optional_data', false);
            if (empty($optional_data)) {
                if (empty($email)) {
                    $optional_field = 'email_section';
                } else {
                    $optional_field = 'phone_section';
                }
                wp_send_json_success($this->show_html_step($optional_field, null));
            }
        }

        $useMobAsUname = get_option('dig_mobilein_uname', 0);

        if ($useMobAsUname == 3 && empty($username)) {
            if (!empty($email)) {
                $username = strstr($email, '@', true);;
            }
        }


        if (empty($username)) {
            $auto = 0;

            if (in_array($useMobAsUname, array(1, 4, 5, 6)) && !empty($mobile)) {

                $tname = $mobile;

                if ($useMobAsUname == 1 || $useMobAsUname == 4) {
                    $tname = '';
                    if (!empty($countrycode)) {
                        $tname = $countrycode;
                    }

                    $tname .= $mobile;

                    if ($useMobAsUname == 1) {
                        $tname = str_replace("+", "", $tname);
                    }

                } else if ($useMobAsUname == 5) {
                    $tname = $mobile;
                } else if ($useMobAsUname == 6) {
                    $tname = '0' . $mobile;
                }

            } else if ((!empty($name) || !empty($email)) && $useMobAsUname == 0) {
                $auto = 1;

                if (!empty($name)) {
                    $tname = digits_filter_username($name, 'name');
                } else if (!empty($email)) {
                    $tname = strstr($email, '@', true);
                }
            } else {
                $tname = apply_filters('digits_username', '');
            }

            if (empty($tname) || $auto == 1) {
                if (empty($tname)) {
                    if (!empty($email)) {
                        $tname = strstr($email, '@', true);
                    } else if (!empty($mobile)) {
                        $tname = $mobile;
                    }
                }

                if (empty($tname)) {
                    $validation_error->add("username", __("Error while generating username!", "digits"));
                } else {

                    $check = username_exists($tname);
                    if ($tname == $mobile && $check) {
                        $validation_error->add("MobinUse", __("Mobile Number is already in use!", "digits"));
                    }

                    if (!empty($check)) {
                        $suffix = 2;
                        while (!empty($check)) {
                            $alt_ulogin = $tname . $suffix;
                            $check = username_exists($alt_ulogin);
                            $suffix++;
                        }
                        $ulogin = $alt_ulogin;
                    } else {
                        $ulogin = $tname;
                    }

                }

            } else {

                $check = username_exists($tname);
                if (!empty($check)) {
                    $suffix = 2;
                    while (!empty($check)) {
                        $alt_ulogin = $tname . $suffix;
                        $check = username_exists($alt_ulogin);
                        $suffix++;
                    }
                    $ulogin = $alt_ulogin;
                } else {
                    $ulogin = $tname;
                }
            }

        } else {
            if (username_exists($username)) {
                $validation_error->add("UsernameinUse", __("Username is already in use!", "digits"));
            } else {
                $ulogin = $username;
            }
        }
        $validation_error = apply_filters('digits_registration_errors', $validation_error, $ulogin, $email);

        $this->check_errors($validation_error);

        if (empty($ulogin)) {
            $validation_error->add("username", __("Error while generating username!", "digits"));
        }
        $this->check_errors($validation_error);

        $sub_action = $this->get('sub_action', false);

        $phone_verified = false;

        if (!empty($mobile)) {
            if ($skip_otp_verification == 0) {

                if ($this->is_otp_action($sub_action)) {
                    $data = array();
                    $request = $this->send_otp($sub_action, $countrycode, $mobile);
                    $data = array_merge($data, $request);
                    wp_send_json_success($data);
                }

                $otp_type = 'sms_otp';
                $otp = $this->get($otp_type, false);
                if (empty($otp)) {
                    $otp_type = 'whatsapp_otp';
                    $otp = $this->get($otp_type, false);;
                }
                if (!empty($this->data['digits_otp_field']) && empty($otp)) {
                    $validation_error->add("invalidOTP", __("Please enter a valid OTP!", "digits"));
                }
                $this->check_errors($validation_error);

                if (empty($otp)) {
                    wp_send_json_success($this->show_html_step('otp_section', null));
                }

                try {
                    if (!$this->isValidOTP($otp_type, $countrycode, $mobile, $otp, true)) {
                        $validation_error->add("invalidOTP", __("Please enter a valid OTP!", "digits"));
                    }
                    $phone_verified = true;
                } catch (\DigitsFireBaseException $e) {
                    wp_send_json_success(['verify_firebase' => true]);
                }

            }
        }
        $this->check_errors($validation_error);

        $ulogin = sanitize_user($ulogin, false);
        $user_id = wp_create_user($ulogin, $password, $email);
        $userd = get_user_by('ID', $user_id);

        if (!is_wp_error($user_id)) {
            if (!empty($mobile)) {
                digits_update_mobile($user_id, $countrycode, $mobile);

                if (!$phone_verified) {
                    update_user_meta($user_id, 'digits_phone_verification_skipped', 'pending');
                }
            }
        } else {
            $this->check_errors($user_id);
        }

        $defaultuserrole = get_option('defaultuserrole', "customer");
        $user_role = apply_filters('digits_register_user_role', $defaultuserrole);

        $user_data = array(
            'ID' => $user_id,
            'role' => $user_role,
        );

        if (!empty($name)) {
            $user_data['first_name'] = $name;
            $user_data['display_name'] = $name;
        }
        wp_update_user($user_data);

        update_digp_reg_fields($reg_custom_fields, $user_id);

        if (class_exists('WooCommerce')) {
            // code that requires WooCommerce
            $userdata = array(
                'user_login' => $ulogin,
                'user_pass' => $password,
                'user_email' => $email,
                'role' => $user_role,
            );
            do_action('woocommerce_created_customer', $user_id, $userdata, $password);
        } else {
            do_action('register_new_user', $user_id);
        }

        self::send_verify_email($userd);

        $redirect_url = -1;

        do_action('digits_user_created', $user_id);

        $allow_login_without_email_verify = get_option('dig_allow_login_without_email_verify', 1);


        $data = array(
            'code' => 1,
            'process' => 1
        );


        if ($allow_login_without_email_verify == 1 || empty($email)) {
            wp_set_current_user($userd->ID, $userd->user_login);
            wp_set_auth_cookie($userd->ID);


            $redirect_url = $this->get('digits_redirect_page', false);
            if (empty($redirect_url) || $redirect_url == -1 || $redirect_url == -2) {
                $redirect_url = UserRedirection::get_redirect_uri('register', $userd, false);
            }

            $message = __('Registration Successful, Redirecting..', 'digits');
            $redirect_url = apply_filters('digits_register_redirect', $redirect_url);
        } else {
            $data['show_message'] = true;
            $data['delay'] = 3800;
            $message = __('Please check your email for the verification link to verify the account.', 'digits');
        }

        $data['message'] = $message;
        $data['redirect'] = $redirect_url;

        $data = array(
            'success' => true,
            'data' => $data
        );
        $data = apply_filters('digits_user_created_response', $data, $user_id);

        wp_send_json($data);


    }

    /**
     * @throws Exception
     */
    private function get($key, $is_req = true)
    {
        $value = '';
        if (!isset($this->data[$key])) {
            if ($is_req) {
                throw new Exception(__("Please enter all the details!", "digits"));
            }
        } else {
            $value = $this->data[$key];
        }
        return $value;
    }

    public function validate_basic_info($phone, $email, $username)
    {
        $validation_error = new WP_Error();
        if (!empty($phone)) {
            if (!$this->isValidMobile($phone)) {
                $validation_error->add("Mail", __("Please enter a valid Mobile Number!", "digits"));
            }
            $mobuser = getUserFromPhone($phone);
            if ($mobuser != null) {
                $validation_error->add("MobinUse", __("Mobile Number is already in use!", "digits"));
            } else if (username_exists($phone)) {
                $validation_error->add("MobinUse", __("Mobile Number is already in use!", "digits"));
            }
            $this->check_errors($validation_error);

        }

        if (!empty($email)) {
            if (!isValidEmail($email)) {
                $validation_error->add("Mail", __("Please enter a valid Email!", "digits"));
            }
            $validation_error = apply_filters('digits_validate_email', $validation_error, $email);
            $this->check_errors($validation_error);
            if (email_exists($email)) {
                $validation_error->add("MailinUse", __("Email is already in use!", "digits"));
            }
        }
        if (!empty($username)) {
            if (username_exists($username)) {
                $validation_error->add("UsernameinUse", __("Username is already in use!", "digits"));
            }
        }
        $this->check_errors($validation_error);
    }

    public function isValidMobile($mobile)
    {
        return !(empty($mobile) || !is_numeric($mobile));
    }

    public function check_errors($validation_error)
    {
        if (!empty($validation_error->get_error_codes())) {
            $errors = array_unique($validation_error->get_error_messages());
            throw new Exception(implode(PHP_EOL, $errors));
        }
    }

    private function show_next_step($dig_reg_details, $reg_extra_fields, $is_builder)
    {
        ob_start();

        $renderer = new DigitsSignupFields();

        if ($is_builder) {
            $renderer->initFields($reg_extra_fields);
        } else {
            $renderer->initNativeFields();
        }
        $renderer->setRegDetails($dig_reg_details);
        $renderer->setFormData($this->data);
        $renderer->render();

        $html = ob_get_clean();
        $data['html'] = $html;

        $check_empty_fields = apply_filters('digits_signup_check_empty_field', false);
        if ($check_empty_fields) {

            if (strpos($html, 'digits-form_input_row') === false) {
                return;
            }
        }

        wp_send_json_success($data);
        die();
    }

    public function show_html_step($action, $data)
    {
        $data = array();
        $data['html'] = $this->render_step_html($action, $data);
        return $data;
    }

    private function render_step_html($action, $data)
    {
        ob_start();
        $this->render_section($action, $data);
        return ob_get_clean();
    }

    public function render_section($action, $data = null)
    {
        $actions = [
            'otp_section' => [
                'tabs' => [
                    'otp' => array(
                        'label' => __('OTP', 'digits'),
                        'render' => 'render_tab_otp_field'),
                ],
                'change' => 'dig_otp',
                'hide_tabs' => true,
            ],
            'email_section' => [
                'tabs' => [
                    'email' => array(
                        'label' => __('Email', 'digits'),
                        'render' => 'render_tab_optional_field'),
                ],
                'hide_tabs' => true,
                'change' => 'optional_data',
            ],
            'phone_section' => [
                'tabs' => [
                    'phone' => array(
                        'label' => __('Phone', 'digits'),
                        'render' => 'render_tab_optional_field'),
                ],
                'hide_tabs' => true,
                'change' => 'optional_data',
            ],
        ];
        $details = $actions[$action];
        $change = $details['change'];
        $tabs = $details['tabs'];
        $hide_tabs = !empty($details['hide_tabs']);
        $body_class = '';
        if ($hide_tabs) {
            $body_class = 'digits-form_body-no_tabs';
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
                        if (!empty($step_tab['hide']) || $hide_tabs) {
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

            <div class="digits-form_body <?php echo $body_class; ?>">
                <div class="digits-form_body_wrapper">
                    <?php
                    foreach ($tabs as $key => $step_tab) {
                        $class = array('digits-form_tab_body');
                        $render = $step_tab['render'];
                        ?>
                        <div data-change="<?php echo $change; ?>"
                             class="<?php echo implode(" ", $class); ?>">
                            <?php
                            $this->$render($action, $data);
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

    public function is_otp_action($sub_action)
    {
        $all_actions = $this->get_all_otp_actions();
        return isset($all_actions[$sub_action]);
    }

    public function get_all_otp_actions()
    {
        $all = Processor::_all_otp_actions();
        unset($all['2fa_app']);
        unset($all['email_otp']);
        return $all;
    }

    public function send_otp($action, $country_code, $mobile)
    {
        $phone = array('country_code' => $country_code, 'phone' => $mobile);;
        $details = array();
        $details['phone'] = $phone;

        return Handler::instance()->process_otp_request(0, $details, $action, 1, 'register');
    }

    public function isValidOTP($otp_type, $country_code, $phone, $otp, $delete_otp)
    {
        $verify_func = 'verify_phone_otp';
        if ($otp_type == 'whatsapp_otp') {
            $verify_func = 'verify_whatsapp_otp';
        }
        return Handler::instance()->$verify_func($country_code, $phone, $otp, $delete_otp);
    }

    public static function send_verify_email($user)
    {
        if (empty($user->user_email)) {
            return false;
        }

        $dig_reg_verify_email = get_option('dig_reg_verify_email', 1);
        if ($dig_reg_verify_email == 0) {
            return false;
        }
        $verification_link = self::create_verification_link($user);

        $placeholders = array();
        $placeholders['{{verify-link}}'] = $verification_link;


        $email_type = 'register';
        $email_handler = new EmailHandler($email_type);
        $email_handler->setUser($user);
        $email_handler->parse_placeholders($placeholders);
        $send = $email_handler->send();

        return true;
    }

    public static function create_verification_link($user)
    {
        $user_id = $user->ID;
        $email = $user->user_email;
        $token = Handler::generate_token(64);

        $args = array(
            'auth_key' => md5($email),
            'method' => 'verify_email',
            'auth_token' => $token,
            'type' => 'verification',
        );
        $url = add_query_arg($args, home_url());

        update_user_meta($user_id, self::USER_VERIFY_EMAIL_KEY, $token);
        update_user_meta($user_id, self::USER_VERIFY_EMAIL_KEY_GEN_TIME, time());
        return $url;
    }

    public function render_tab_optional_field($action, $data)
    {
        if ($action == 'email_section') {
            $type = 'email';
            $title = __('Add Email (Optional)', 'digits');
        } else if ($action == 'phone_section') {
            $type = 'phone';
            $title = __('Add Phone (Optional)', 'digits');
        } else {
            return;
        }

        echo '<span class="main-section-title digits_display_none">' . $title . '</span>';

        $parent_class = 'digits-' . $type . '_row';
        ?>
        <div data-field-type="<?php echo $type; ?>"
             class="<?php echo $parent_class; ?>">
            <?php
            if ($type == 'phone') {
                $country = $this->get_country();
                $userCountry = $country['country'];
                $userCountryCode = $country['code'];

                digits_ui_reg_phone_field('optional_', $userCountryCode, $userCountry, -1);
            } else if ($type == 'email') {
                digits_ui_reg_email_field('optional_email', -1);
            }
            ?>
            <input type="hidden" name="is_digits_optional_data" value="1"/>
        </div>
        <div class="digits-form_footer_content">
            <div class="digits-form_link digits_skip_now">
                <?php esc_attr_e('Skip for now', 'digits'); ?>
            </div>
        </div>
        <?php
    }

    public function get_country()
    {
        return getUserCountryCode(true);
    }

    public function render_tab_otp_field($action, $data)
    {
        $method = 'sms_otp';
        Processor::instance()->render_otp_box(0, $method, 1, 'register');
        ?>
        <input type="hidden"
               class="digits-form_otp_selector auto-click dig_process_data"
               data-disable_update="1"
               data-type="<?php esc_attr_e($method); ?>"/>
        <input type="hidden" name="digits_otp_field" value="1"/>
        <?php
        echo '<span class="main-section-title digits_display_none">' . __('Verify Phone', 'digits') . '</span>';

    }
}