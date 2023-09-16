<?php

namespace DigitsFormHandler;


use DigitsDeviceAuth;
use DigitsNoticeException;
use DigitsSettingsHandler\UserAccountInfo;
use DigitsSignUpException;
use DigitsUserFormHandler\UserSettingsHandler;
use Exception;
use WP_Error;
use WP_User;


if (!defined('ABSPATH')) {
    exit;
}

Handler::instance();

final class Handler
{
    const OTP_TABLE = 'digits_otp';
    const EMAIL_VERIFY_KEY = 'email_auto_login';
    const EMAIL_VERIFY_PROCESS_KEY = 'email_auto_login_process';
    const REMOTE_DEVICE_AUTH_LOGIN = 'remote_device_auth_login';
    const REMOTE_DEVICE_AUTH_PENDING_STATUS = 'auth_pending';
    protected static $_instance = null;
    public $request_user_login = [];
    private $type;
    private $data;
    private $login_details;
    private $valid_otps = array();
    private $login_steps = [];
    private $login_methods = [];
    private $request_source = 'unknown';
    private $request_type = 'login';

    public function __construct()
    {
        add_action('digits_check_user_login', array($this, 'check_user_login'), 10, 2);
        add_action('digits_check_user_forgotpass', array($this, 'check_user_login'), 10, 2);
        add_action('wp_login', array($this, 'user_login'), 10, 2);
        add_action('authenticate', array($this, 'authenticate'), 100, 3);
    }

    public function check_user_login($validation_error, $user)
    {
        if ($validation_error->has_errors()) {
            return $validation_error;
        }
        $check = $this->is_tp_auth_allowed($user);
        if ($check instanceof WP_Error) {
            return $check;
        }
        return $validation_error;
    }

    public function is_tp_auth_allowed($user)
    {
        $is_email_verified = $this->is_user_email_verified($user);
        if ($is_email_verified instanceof WP_Error) {
            return $is_email_verified;
        }

        $only_digits_allowed = get_option('dig_only_allow_secure_logins', 0);
        if ($only_digits_allowed == 1) {
            if (empty($this->type)) {
                $message = __('Login not allowed', 'digits');
                return new WP_Error('not_allowed', $message);
            }
        }
        return false;
    }

    public function is_user_email_verified($user)
    {
        $allow_login_without_email_verify = get_option('dig_allow_login_without_email_verify', 1);
        if ($allow_login_without_email_verify == 0) {
            $user_id = $user->ID;
            $verify_key = get_user_meta($user_id, UserRegistration::USER_VERIFY_EMAIL_KEY, true);
            if (!empty($verify_key)) {
                $message = __('You need to verify your email before you can access your account.', 'digits');
                $nonce = wp_create_nonce($user->user_login . '_resend_verify_email');
                $attrs = 'data-user="' . esc_attr($user->user_login) . '" data-nonce="' . esc_attr($nonce) . '"';
                $message .= '&nbsp;<a class="digits_resend_email_verification" href="#" ' . $attrs . '>' . __('Resend Email', 'digits') . '</a>';
                return new WP_Error('verify_email', $message);
            }
        }
        return true;
    }

    public function authenticate($user, $uname, $pass)
    {
        if (empty($user) || is_wp_error($user)) {
            return $user;
        }
        $check = $this->is_tp_auth_allowed($user);
        if ($check instanceof WP_Error) {
            return $check;
        }
        return $user;
    }

    public function user_login($user_login, $user)
    {
        if (!$user instanceof WP_User) {
            $user = get_user_by('user_login', $user_login);
        }
        $data = array();
        $token = wp_get_session_token();

        $login_steps = 1;
        $login_methods = 'default';
        $password_less = 0;

        if (!empty($this->login_steps)) {
            $login_steps = implode(",", $this->login_steps);
        }

        if (!empty($this->login_methods)) {
            if (!in_array('password', $this->login_methods)) {
                $password_less = 1;
            }
            $login_methods = implode(",", $this->login_methods);
        }
        $data['password_less'] = $password_less;

        $data['login_steps'] = $login_steps;
        $data['login_methods'] = $login_methods;

        $data['request_source'] = $this->request_source;
        $data['request_type'] = $this->request_type;

        $data['user_id'] = $user->ID;
        $data['user_token'] = $token;

        $data['user_agent'] = wp_unslash($_SERVER['HTTP_USER_AGENT']);
        $data['ip'] = digits_get_ip();


        global $wpdb;
        $table = $wpdb->prefix . 'digits_login_logs';
        $wpdb->insert($table, $data);
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function process()
    {
        try {
            $this->_process();
        } catch (DigitsNoticeException $e) {
            wp_send_json_error(
                array(
                    'notice' => true,
                    'message' => $e->getMessage())
            );
            die();
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
            die();
        }
    }

    /**
     * @throws Exception
     */
    public function _process()
    {
        $this->request_source = 'digits';
        $this->request_type = $this->type;

        switch ($this->type) {
            case 'login':
                $this->login();
                return;
            case 'register':
                $this->register();
                return;
            case 'forgot':
                $this->forgotpass();
                return;
            default:
                throw new Exception(__("Error! Not found", "digits"));
        }
    }

    /**
     * @throws Exception
     */
    public function login()
    {
        $data = array();

        $this->login_details = digit_get_login_fields();

        $email_accep = $this->login_details['dig_login_email'];
        $pass_accep = $this->login_details['dig_login_password'];
        $mobile_accp = $this->login_details['dig_login_mobilenumber'];
        $username_accep = $this->login_details['dig_login_username'];

        $login_type = $this->get('action_type', true);

        $captcha = $this->login_details['dig_login_captcha'];
        if (empty($this->data['check_status'])) {
            if ($captcha > 0) {
                $verify = $this->verify_captcha($captcha);
                if (!$verify) {
                    throw new Exception(__('Please verify captcha!', 'digits'));
                }
            }
        }

        $username = false;
        $user = false;

        $email_token = $this->get('digits_login_email_token', false);

        $bypass_authentication_till = false;
        if (!empty($email_token)) {
            $user = UserActionHandler::get_user_from_email_token($email_token);
            if ($user instanceof WP_Error) {
                throw new Exception($user->get_error_message());
            }
            $bypass_authentication_till = 'email_otp';
            $email = $user->user_email;

        } else if ($mobile_accp == 1 && $login_type == 'phone') {


            $countryCode = $this->get('login_digt_countrycode', false);
            if (empty($countryCode)) {
                $countryCode = $this->get('digt_countrycode', false);
                if (empty($countryCode)) {
                    $countryCode = $this->get('dig_countrycodec', false);
                }
            }

            $raw_mobile = $this->get('digits_phone', true);

            $mobile = sanitize_mobile_field_dig($raw_mobile);
            $phone = $countryCode . $mobile;

            $this->request_user_login[] = $phone;

            if (empty($phone) || !is_numeric($phone)) {
                throw new Exception(__("Please enter a valid phone number.", "digits"));
            }

            if (empty($countryCode) || $countryCode == '+') {
                throw new Exception(__("Please enter a valid country code.", "digits"));
            }

            if (!checkwhitelistcode($countryCode)) {
                $error = __('At the moment, we do not allow users from your country', ' digits');
                throw new Exception($error);
            }

            $is_phone_allowed = dig_is_phone_no_allowed($phone);
            if (!$is_phone_allowed) {
                $error = __('This phone number is not allowed!', ' digits');
                throw new Exception($error);
            }

            if (checkIfUsernameIsMobile_validate($countryCode, $mobile) == 1) {
                $userfromPhone = getUserFromPhone($phone);

                if ($userfromPhone != null) {
                    $user = $userfromPhone;
                } else {
                    $userfromMobile = getUserFromPhone($mobile);
                    if ($userfromMobile != null) {
                        $user = $userfromMobile;
                    }
                }
            }
            if (!$user) {
                $user = getUserFromPhone($countryCode . $phone);
            }
        } else {
            $email = $this->get('digits_email', true);
            $this->request_user_login[] = $email;
            if (empty($email)) {
                throw new Exception(__('Please enter a valid email!', 'digits'));
            }
            if ($username_accep == 0 && !isValidEmail($email)) {
                throw new Exception(__('Please enter a valid email!', 'digits'));
            }

            if ($email_accep == 1 && isValidEmail($email)) {
                $user = get_user_by('email', $email);
            }
            if ($username_accep == 1 && !$user) {
                $user = get_user_by('login', $email);
            }
        }

        if (!$user) {
            do_action('digits_login_user_not_found', $login_type);
            throw new DigitsNoticeException(__("Please signup before logging in.", "digits"));
        }

        $validate_user = new WP_Error();
        $validate_user = apply_filters('digits_check_user_login', $validate_user, $user);

        if ($validate_user->has_errors()) {
            $message = $validate_user->get_error_message();
            if ($validate_user->get_error_code() == 'notice') {
                throw new DigitsNoticeException($message);
            }
            throw new Exception($message);
        }

        $this->checkUserStatus($user);


        $user_authenticated = false;

        $sub_action = $this->get('sub_action', false);
        $available_steps = [1, 2, 3];
        foreach ($available_steps as $step_no) {
            $user_methods = UserSettingsHandler::get_user_methods($user, $step_no);

            if (empty($user_methods)) {
                continue;
            }

            $this->login_steps[] = $step_no;

            if (!empty($bypass_authentication_till)) {
                if (in_array('email_otp', $user_methods, true)) {
                    $user_authenticated = true;
                    $bypass_authentication_till = false;
                    $this->login_methods[] = 'auto_email_login';
                }
                continue;
            }

            $userStepEnabled = UserSettingsHandler::isUserFaEnabled($user->ID, $step_no);
            if ($userStepEnabled) {

                $step_key = "digits_step_{$step_no}_type";
                $method_type = $this->get($step_key, true);
                $method_value = $this->get_step_value($method_type, $step_no);

                if ($method_type == 'password' && $pass_accep == 0) {
                    throw new Exception(__("Passwords are not allowed!", 'digits'));
                }

                /*check for remote approval*/
                if (!empty($method_type) && !empty($this->data['check_status'])) {
                    $this->check_remote_approve_status($method_type);
                }

                if (empty($method_type) || empty($method_value)) {
                    if (!empty($sub_action) && $sub_action == 'generate_device_key') {
                        $data['token'] = $this->generate_platform_token($user, $step_no);
                        wp_send_json_success($data);
                    } else if ($sub_action == 'start_remote_device_auth' || $sub_action == 'remove_remote_device_auth') {
                        $data['html'] = $this->change_platform_auth_device($user, $step_no, $sub_action);
                        $data['check_remote_status'] = true;
                        wp_send_json_success($data);
                    } else if ($this->is_otp_action($user, $sub_action, $step_no)) {
                        $request = $this->process_user_otp_request($user, $sub_action, $step_no, 'login');
                        $data = array_merge($data, $request);
                        wp_send_json_success($data);
                    } else if (!empty($sub_action)) {
                        throw new Exception(__("Auth Method not found!", 'digits'));
                    } else if (!empty($method_type)) {
                        throw new Exception(__("Please fill the required field!", 'digits'));
                    }
                    $data['html'] = $this->show_step_html($user, $step_no, 'login');
                    wp_send_json_success($data);
                }

                try {
                    $user_authenticated = $this->processLoginStep($user, $step_no, $method_type, $method_value);
                } catch (\DigitsFireBaseException $e) {
                    wp_send_json_success(['verify_firebase' => true]);
                }

                $this->login_methods[] = $method_type;

                if ($user_authenticated instanceof WP_Error) {
                    throw new Exception($user_authenticated->get_error_message());
                }
            }
        }

        $result = array();
        $user2FaEnabled = false;
        if ($user_authenticated) {

            $result['success'] = true;
            $result['data'] = $this->processLogin($user, 'login');

            if (!$user2FaEnabled) {
                $result['data']['showAdd2Fa'] = true;
            }
            wp_send_json($result);

        } else {
            throw new Exception(__("Unknown error occurred, please try again!", "digits"));
        }
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

    public function verify_captcha($captcha)
    {
        $instance_id = $this->get('instance_id', false);
        if (empty($instance_id)) {
            return false;
        }
        $key = 'captcha_key_' . $instance_id;
        $check = \DigitsSessions::get($key);
        $value = 1;

        if (!empty($check) && $check <= 7) {
            $value = $check + 1;
            $verify = true;
        } else {
            if ($captcha == 1) {
                $verify = dig_validate_login_captcha(true);
            } else {
                $verify = digits_verify_recaptcha();
            }
        }

        if ($verify) {
            \DigitsSessions::update($key, $value, 240);
            return true;
        } else {
            return false;
        }
    }

    public function checkUserStatus($user)
    {

        $user_id = $user->ID;
        $key = digits_get_ip() . wp_unslash($_SERVER['HTTP_USER_AGENT']);
        $block_key = md5($key);
        $block_key = $block_key . '_blocked_' . $user_id;

        $block = \DigitsSessions::get_from_identifier($block_key);

        if (!empty($block)) {
            $data = ['message' => __('This device is blocked for this user for a invalid attempt, please try again after some time!', 'digits')];
            $data['redirect_to'] = home_url();
            wp_send_json_error($data);
        }
    }

    private function get_step_value($step_type, $step_no)
    {
        $value = $this->get($step_type, false);
        if (!empty($value)) {
            return $value;
        }

        $step_value = $this->get('digits_step_' . $step_no . '_value', true);
        return $step_value;
    }

    /**
     * @throws Exception
     */
    public function check_remote_approve_status($method)
    {
        $immediate_methods = Processor::instance()->get_immediate_methods();
        $remote_approve_methods = ['email_otp', 'platform-all', 'platform'];

        if (in_array('email_otp', $immediate_methods)) {
            $remote_approve_methods = array_merge($remote_approve_methods, $immediate_methods);
        }

        if (!in_array($method, $remote_approve_methods)) {
            throw new Exception(__('Error, auth not found!', 'digits'));
        }
        if ($method == 'email_otp' || in_array($method, $immediate_methods)) {
            $identifier_id = $this->get('otp_token_key', true);
            $check_email = \DigitsSessions::get_from_key_identifier(self::EMAIL_VERIFY_KEY, $identifier_id);
            if (empty($check_email)) {
                wp_send_json_error(['error' => __('Error, please try again!', 'digits')]);
            }
            $check_email = json_decode($check_email, true);

            if ($check_email['status'] == 'approved') {
                $response['verification_code'] = $check_email['otp'];
                $response['status'] = 'completed';
                wp_send_json_success($response);
            } else if ($check_email['status'] == 'denied' || $check_email['status'] == 'blocked') {
                $response = [];

                if ($check_email['status'] == 'denied') {
                    $response['message'] = __('Denied permission to login from email', 'digits');
                } else {
                    $response['message'] = __('User is blocked from logging in via this device for some time!', 'digits');
                }
                $response['redirect_to'] = home_url();

                \DigitsSessions::instance()->destroy_session();
                wp_send_json_error($response);
            } else {
                wp_send_json_success(['status' => 'pending']);
            }

        } else {
            $status = $this->validate_remote_device_approval();
            wp_send_json_success(['status' => $status]);
        }
        die();
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

    /**
     * @return string
     * @throws Exception
     */
    public function validate_remote_device_approval()
    {
        if (empty($this->data['remote_device_auth_token'])) {
            throw new Exception(__('Error, token not found!', 'digits'));
        }
        $remote_token = $this->data['remote_device_auth_token'];
        $token_info = \DigitsSessions::get_from_key_identifier(self::REMOTE_DEVICE_AUTH_LOGIN, $remote_token);

        if (!empty($token_info)) {
            $token_info = json_decode($token_info, true);

            if ($token_info['status'] == 'completed') {
                return 'completed';
            } else if ($token_info['status'] == self::REMOTE_DEVICE_AUTH_PENDING_STATUS) {
                return 'pending';
            }
        }

        return 'failed';
    }

    /**
     * @param WP_User $user
     * @param $action
     * @param $step_no
     * @return bool
     * @throws Exception
     */
    public function generate_platform_token($user, $step_no, $device_type = false)
    {
        $method = UserSettingsHandler::get_user_platform_method($user, $step_no);
        if (empty($method)) {
            throw new Exception(__("No devices found!", 'digits'));
        }
        $platform_type = reset($method);

        if (!empty($device_type)) {
            if ($platform_type != 'platform-all' && !in_array($device_type, $method, true)) {
                throw new Exception(__("No devices found!", 'digits'));
            }
        } else {
            $device_type = $platform_type;
        }

        $user_id = $user->ID;

        return DigitsDeviceAuth::generate_auth_public_key($user_id, $device_type, $step_no);
    }

    /**
     * @param WP_User $user
     * @param $action
     * @param $step_no
     * @return bool
     * @throws Exception
     */
    private function change_platform_auth_device($user, $step_no, $auth_type)
    {
        $user_id = $user->ID;
        $method = UserSettingsHandler::get_user_platform_method($user, $step_no);

        if (empty($method)) {
            throw new Exception(__("No devices found!", 'digits'));
        }

        if ($auth_type == 'start_remote_device_auth') {
            $remote_auth_methods = ['platform', 'platform-all'];
            $available_remote_auth = array_intersect($method, $remote_auth_methods);
            if (empty($available_remote_auth)) {
                throw new Exception(__("No devices found!", 'digits'));
            }
            $this->generate_remote_auth_token($user_id, $step_no);
        } else {
            \DigitsSessions::delete(self::REMOTE_DEVICE_AUTH_LOGIN);
        }

        ob_start();
        Processor::instance()->auth_device_tab($user_id, $method, $step_no, 'login');
        return ob_get_clean();
    }

    public function generate_remote_auth_token($user_id, $step_no)
    {
        $this->check_remote_auth_available($user_id);

        $auth_token = self::generate_token(32);

        $args = array(
            'auth_key' => base64_encode(time()),
            'method' => 'remote_device_auth',
            'auth_token' => $auth_token,
            'type' => 'device_login',
            'wait' => false,
        );
        $url = add_query_arg($args, home_url());

        $token_data = [
            'status' => self::REMOTE_DEVICE_AUTH_PENDING_STATUS,
            'user_id' => $user_id,
            'step_no' => $step_no
        ];
        \DigitsSessions::update(self::REMOTE_DEVICE_AUTH_LOGIN, $token_data, 3600, $auth_token);
        Processor::instance()->startRemoteLogin($url, $auth_token);
    }

    public function check_remote_auth_available($user_id, $is_remote_request = false)
    {
        $devices = \DigitsDeviceAuth::instance()->getUserSecurityDevicesCategoryWise($user_id, 'platform');
        if (empty($devices['mobile_devices'])) {
            $message = __('No device found!', 'digits');
            if ($is_remote_request) {
                $message = __('No device is not linked to your account!', 'digits');
            }
            throw new Exception($message);
        }
    }

    public static function generate_token($size = 64)
    {
        return bin2hex(random_bytes($size));
    }

    /**
     * @param WP_User $user
     * @param $action
     * @param $step_no
     * @return bool
     * @throws Exception
     */
    private function is_otp_action($user, $action, $step_no)
    {
        if (empty($action)) {
            return false;
        }
        $methods = Processor::get_otp_actions($user->ID, $step_no);

        if (in_array($action, $methods)) {
            return true;
        }
        throw new Exception(__("Auth Method not allowed!", 'digits'));

    }

    private function process_user_otp_request($user, $action, $step_no, $request_type)
    {
        $user_id = $user->ID;
        $details = array();
        $details['user'] = $user;
        $details['phone'] = digits_get_mobile_country_code($user_id);
        $details['email'] = $user->user_email;

        return $this->process_otp_request($user_id, $details, $action, $step_no, $request_type);
    }

    public function process_otp_request($user_id, $details, $action, $step_no, $request_type)
    {
        $send_otp = $this->send_otp($action, $details, $step_no, $request_type);
        ob_start();
        Processor::instance()->render_otp_box($user_id, $action, $step_no, $request_type);
        $html = ob_get_clean();


        $response = array('html' => $html);

        if (is_array($send_otp)) {
            $response = array_merge($response, $send_otp);
            $response['auto_fill'] = true;
        }
        if ($action == '2fa_app') {
            $response['input_info_html'] = Processor::instance()->code_hint_box(['2fa_app'], $this->request_user_login);
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    public function send_otp($action, $details, $step_no, $request_type)
    {
        $allowed_methods = array('sms_otp', 'whatsapp_otp', 'email_otp');

        if (!in_array($action, $allowed_methods)) {
            return -1;
        }

        if (empty($details['phone']) && empty($details['email'])) {
            throw new Exception(__('Phone number/Email not found, please try using different method!', 'digits'));
        }

        $response = [];
        $otp = dig_get_otp(false);
        $data = array();
        $data['action_type'] = $action;
        $data['otp'] = $otp;
        $data['ip'] = digits_get_ip();
        $is_immediate_otp = false;

        if (!empty($details['user'])) {
            $user = $details['user'];
            $user_id = $user->ID;
            $data['ref_id'] = $user_id;
            if ($request_type == 'login') {
                $is_immediate_otp = Processor::instance()->is_immediate_method($user_id, $action, $step_no);
                if ($is_immediate_otp) {
                    $data['user_id'] = $user_id;
                    $immediate_methods = Processor::instance()->get_immediate_methods();
                    $user_methods = UserSettingsHandler::get_user_methods($user_id, $step_no);
                    $action = array_intersect($user_methods, $immediate_methods);

                    $data['action_type'] = 'user';
                }
            }
        }


        if ($is_immediate_otp || is_array($action)) {
            $this->delete_user_otp($user_id);
        } else {
            if ($action == 'email_otp') {
                $this->delete_email_otp($details['email']);
            } else {
                $this->delete_phone_otp($details['phone'], $action);
            }
        }


        if (!is_array($action)) {
            $action = array($action);
        }

        $user_email = '';
        $user_phone = '';
        if (in_array('email_otp', $action) && !empty($details['email'])) {
            $user_email = $details['email'];
        }
        if (!empty($details['phone'])) {
            if (in_array('sms_otp', $action) || in_array('whatsapp_otp', $action)) {
                $phone_obj = $details['phone'];
                $user_country_code = $phone_obj['country_code'];
                $user_phone = $phone_obj['phone'];
                $user_phone = $user_country_code . $user_phone;

                if (!checkwhitelistcode($user_country_code)) {
                    $error = __('At the moment, we do not allow users from your country', ' digits');
                    throw new Exception($error);
                }
            }
        }


        $check_request = digits_check_request($user_phone, $user_email);

        if ($check_request instanceof WP_Error) {
            throw new \DigitsRateLimitException($check_request->get_error_message());
        }

        $additional_data = array('otp_target' => []);

        foreach ($action as $method) {
            try {
                $this->process_otp($method, $details, $step_no, $otp, $data, $response, $additional_data);
            } catch (\DigitsRateLimitException $e) {
                throw $e;
            } catch (Exception $e) {
                if (sizeof($action) == 1) {
                    throw $e;
                }
            }
        }
        $otp_target = $additional_data['otp_target'];

        if (!empty($otp_target)) {
            $response['input_info_html'] = Processor::instance()->code_hint_box($otp_target, $this->request_user_login);
        }

        $this->insert_otp($data);

        $method_name = array_values($action)[0];

        $response['resend_timer'] = $this->get_resend_time($method_name);
        return $response;
    }

    public function delete_user_otp($user_id)
    {
        return $this->_delete_otp_data(['user_id' => $user_id]);
    }

    public function _delete_otp_data($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::OTP_TABLE;
        return $wpdb->delete($table_name, $data);
    }

    public function delete_email_otp($email)
    {
        return $this->_delete_otp_data(['email' => $email]);
    }

    public function delete_phone_otp($user_phone, $type)
    {
        $country_code = $user_phone['country_code'];
        $phone = $user_phone['phone'];
        $data = array();
        $data['countrycode'] = str_replace("+", "", $country_code);
        $data['phone'] = $phone;
        $data['action_type'] = $type;
        return $this->_delete_otp_data($data);
    }

    public function process_otp($action, $details, $step_no, $otp, &$data, &$response, &$additional_data)
    {
        switch ($action) {
            case 'sms_otp':
            case 'whatsapp_otp':
                $phone = $details['phone'];

                if (empty($phone['phone'])) {
                    throw new Exception(__('Phone number not found, please try different method!', 'digits'));
                }

                $country_code_str = $phone['country_code'];
                $phone_str = $phone['phone'];

                $data['countrycode'] = str_replace("+", "", $country_code_str);
                $data['phone'] = $phone_str;

                $send = $this->process_mobile_otp($details['phone'], $action, $otp);
                if ($send === 'firebase') {
                    $response['firebase'] = true;
                }
                $additional_data['otp_target'][] = $country_code_str . $phone_str;
                break;
            case 'email_otp':
                $email = $details['email'];
                if (empty($email)) {
                    throw new Exception(__('Email not found, please try different method!', 'digits'));
                }
                $data['email'] = $email;

                $user = $details['user'];

                $placeholders = array('{{otp}}' => $otp);

                $link_data = $this->create_auto_login_link($user, $data['email'], $otp, $step_no);
                $placeholders['{{verify-link}}'] = $link_data['url'];

                $response['check_remote_status'] = true;
                $response['otp_token_key'] = $link_data['token'];

                digits_add_request_log($email, 'email', $this->type, '', digits_get_email_gateway());
                $this->process_email_otp($details['email'], $placeholders, $user);

                $additional_data['otp_target'][] = $email;
                break;
            case '2fa_app':
                $additional_data['otp_target'][] = '2fa_app';
                break;
            default:
                return -1;
        }
    }

    public function process_mobile_otp($phone_obj, $gateway, $otp)
    {
        $countrycode = $phone_obj['country_code'];
        $phone = $phone_obj['phone'];
        $whatsapp = $gateway == 'whatsapp_otp';
        $digit_gateway = -1;

        if ($gateway == 'whatsapp_otp') {
            $_POST['whatsapp'] = 1;
        } else {
            $_POST['whatsapp'] = 0;
            $digit_gateway = dig_gatewayToUse($countrycode);
            if ($digit_gateway == 13) {
                digits_add_request_log($countrycode . $phone, 'sms', $this->type, '', $digit_gateway);
                return 'firebase';
            }
        }

        if ($whatsapp || $digit_gateway != 13) {
            if (!digit_send_otp($digit_gateway, $countrycode, $phone, $otp, false, $this->type)) {
                return -1;
            }
        }

        return true;
    }

    public function create_auto_login_link($user, $email, $otp, $step_no)
    {
        $link = wp_get_referer();
        if (filter_var($link, FILTER_VALIDATE_URL) === FALSE) {
            $home_url = rtrim(home_url(), '/');
            $url = $home_url . $link;
        } else {
            $url = $link;
        }
        $token = self::generate_token(64);
        $identifier = self::generate_token(32);

        $args = array(
            'auth_key' => $identifier,
            'method' => 'direct_email_login',
            'auth_token' => $token,
            'type' => 'auto_login',
            'wait' => empty($this->data['digits']),
        );
        $token_data = array('token' => $token);
        $token_data['email'] = $email;
        $token_data['otp'] = $otp;
        $token_data['digits'] = !empty($this->data['digits']);
        $token_data['time'] = time();
        $token_data['device'] = wp_unslash($_SERVER['HTTP_USER_AGENT']);
        $token_data['step_no'] = $step_no;
        $token_data['status'] = 'pending';
        $token_data['user_ip'] = digits_get_ip();
        $token_data['user_id'] = $user->ID;

        if (!empty($this->data['container'])) {
            $container = $this->data['container'];
            $token_data['form_id'] = $container;
        } else {
            $args['login'] = 'true';
            $token_data['form_id'] = false;
        }

        \DigitsSessions::update(self::EMAIL_VERIFY_KEY, $token_data, 3600, $identifier);

        $url = add_query_arg($args, $url);

        return ['url' => $url, 'token' => $identifier];
    }

    public function process_email_otp($email, $placeholders, $user)
    {
        $email_type = $this->type;
        $email_handler = new EmailHandler($email_type);
        $email_handler->setUser($user);
        $email_handler->parse_placeholders($placeholders);
        $send = $email_handler->send();

        return true;
    }

    private function insert_otp($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::OTP_TABLE;

        $data['time'] = date('Y-m-d H:i:s', strtotime("now"));

        return $wpdb->insert($table_name, $data);
    }

    public function get_resend_time($method)
    {

        $key = 'otp_trigger_' . $method;
        $count = 1;
        $last_count = \DigitsSessions::get($key);

        if (!empty($last_count)) {
            $count = max($last_count + 1, 1);
        }

        \DigitsSessions::update($key, $count, 3600);


        $data = [
            'sms_otp' => 'dig_mob_otp_resend_time',
            'whatsapp_otp' => 'dig_whatsapp_otp_resend_time',
            'email_otp' => 'dig_email_otp_resend_time',
        ];

        $db_key = $data[$method];
        if ($count > 1) {
            $db_key = $db_key . '_' . min($count, 3);
            $time = get_option($db_key, 30 * $count);
        } else {
            $time = get_option($db_key, 30);
        }

        return max(20, $time);
    }

    private function show_step_html($user, $step_no, $request_type)
    {
        ob_start();
        $processor = Processor::instance();
        if (!empty($this->data['wp_form'])) {
            $processor->addButtonClass('digits_wp_button button');
        }
        $processor->step_html($user, $step_no, $request_type);
        return ob_get_clean();
    }

    private function processLoginStep($user, $step_no, $type, $value)
    {
        $user_id = $user->ID;
        $username = $user->user_login;

        $user_methods = UserSettingsHandler::get_user_methods($user, $step_no);

        if (!in_array($type, $user_methods, true)) {
            return new WP_Error('method_failed', __('Auth Method not allowed!' . $type, 'digits'));
        }


        if ($type == 'password') {
            $authenticate = wp_authenticate($username, $value);
            if ($authenticate instanceof WP_Error) {
                return $authenticate;
            }
            return true;
        }

        if ($type == '2fa_app') {
            return $this->authenticate_2fa_auth_app_code($user, $value, $step_no);
        }

        if (in_array($type, array('email_otp', 'immediate_otp', 'sms_otp', 'whatsapp_otp'))) {
            $delete_otp = false;
            $verified = false;

            $is_immediate_otp = Processor::instance()->is_immediate_method($user_id, $type, $step_no);

            if ($is_immediate_otp) {
                $verified = $this->verify_user_otp($user_id, $value, $delete_otp);
            } else if ($type == 'sms_otp' || $type == 'whatsapp_otp') {
                $phone_obj = digits_get_mobile_country_code($user_id);
                if (empty($phone_obj)) {
                    return new WP_Error('method_not_found', __('There is no phone number linked to the account!', 'digits'));
                }

                $phone = $phone_obj['phone'];
                $country_code = $phone_obj['country_code'];

                if ($type == 'whatsapp_otp') {
                    $verified = $this->verify_whatsapp_otp($country_code, $phone, $value, $delete_otp);
                } else {
                    $gatewayToUse = dig_gatewayToUse($country_code);
                    if ($gatewayToUse == 13) {
                        if (empty($this->data['firebase_token'])) {
                            throw new \DigitsFireBaseException('verify_firebase');
                        }
                    }
                    $verified = $this->verify_phone_otp($country_code, $phone, $value, $delete_otp);
                }
            } else if ($type == 'email_otp') {
                $email = $user->user_email;
                if (empty($email)) {
                    return new WP_Error('method_not_found', __('There is no email address linked to the account!', 'digits'));
                }
                $verified = $this->verify_email_otp($email, $value, $delete_otp);
            }

            if (!$verified && $is_immediate_otp) {
                $is_sms_immediate_otp = Processor::instance()->is_immediate_method($user_id, 'sms_otp', $step_no);
                if ($is_sms_immediate_otp) {
                    $phone_obj = digits_get_mobile_country_code($user_id);
                    if (!empty($phone_obj)) {
                        $country_code = $phone_obj['country_code'];
                        $phone = $phone_obj['phone'];
                        $gatewayToUse = dig_gatewayToUse($country_code);
                        if ($gatewayToUse == 13) {
                            if (empty($this->data['firebase_token'])) {
                                throw new \DigitsFireBaseException('verify_firebase');
                            } else {
                                $verified = $this->verify_phone_otp($country_code, $phone, $value, $delete_otp);
                            }
                        }
                    }
                }
            }

            if ($verified) {
                if ($is_immediate_otp || $type == 'email_otp') {
                    $this->delete_email_link();
                }
            }

            if (!$verified) {
                return new WP_Error('invalid_otp', __('Please enter a valid otp!', 'digits'));
            }
            return true;
        }


        $platform_methods = UserSettingsHandler::get_user_platform_method($user, $step_no);
        if (!empty($platform_methods)) {
            $platform_method = reset($platform_methods);

            if (!empty($platform_method)) {
                if ($value == 'remote') {
                    $status = $this->validate_remote_device_approval();
                    if ($status == 'completed') {
                        return true;
                    } else if ($status == self::REMOTE_DEVICE_AUTH_PENDING_STATUS) {
                        return new WP_Error('invalid_otp', __('Please Login with your phone via using the QR Code!', 'digits'));
                    } else {
                        return new WP_Error('error', __('Failed, please try logging in again!', 'digits'));
                    }
                }
                return DigitsDeviceAuth::authenticate_user_device($user, $step_no, $value);
            }

        }

        return false;

    }

    public function authenticate_2fa_auth_app_code($user, $otp, $step_no)
    {
        if (empty($otp)) {
            return new WP_Error('invalid_otp', __('Please enter a valid otp!', 'digits'));
        }

        $otp = trim($otp);
        $otp_validity = 600;

        $user_id = $user->ID;
        $key = 'login_auth_app_verify_info_' . $user_id;
        $prev_time = \DigitsSessions::get($key);

        $totp = UserAccountInfo::instance()->get_user_totp($user_id, false);
        if (!empty($prev_time) && (time() - $prev_time) < $otp_validity) {
            if ($totp->verify($otp, $prev_time)) {
                return true;
            }
        }

        $time = time();
        if ($totp->verify($otp)) {
            \DigitsSessions::set($key, $time, 3600);
            return true;
        }


        return new WP_Error('invalid_otp', __('Please enter a valid otp!', 'digits'));
    }

    public function verify_user_otp($user_id, $otp, $delete_otp, $validity = 1200)
    {
        return $this->verify_otp($user_id, false, false, $otp, $delete_otp, 'user', $validity);
    }

    private function verify_otp($email, $country_code, $phone, $otp, $delete_otp, $route, $validity = 1200)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::OTP_TABLE;
        if (empty($otp)) {
            return false;
        }
        $otp = trim($otp);

        if ($email) {
            $column = 'email';
            if (is_numeric($email)) {
                $column = 'user_id';
            } else {
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            }


            $query = $wpdb->prepare(
                'SELECT * FROM ' . $table_name . '
        WHERE ' . $column . ' = %s AND otp=%s AND action_type=%s ORDER BY time DESC LIMIT 1',
                $email, $otp, $route
            );

        } else {
            $is_phone_allowed = dig_is_phone_no_allowed($country_code . $phone);
            if (!$is_phone_allowed) {
                return false;
            }

            if (dig_gatewayToUse($country_code) == 13 && $route == 'sms_otp') {
                if (!empty($this->data['firebase_token'])) {
                    $token = $this->data['firebase_token'];
                    if ($token != -1) {
                        return dig_verify_firebase($token, $country_code . $phone);
                    }
                } else {
                    throw new \DigitsFireBaseException('verify_firebase');
                }
            }

            $country_code = str_replace("+", "", $country_code);
            $country_code = filter_var($country_code, FILTER_SANITIZE_NUMBER_INT);
            $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
            $query = $wpdb->prepare(
                'SELECT * FROM ' . $table_name . '
        WHERE countrycode = %s AND phone = %s AND otp=%s AND action_type=%s ORDER BY time DESC LIMIT 1',
                $country_code, $phone, $otp, $route
            );

        }

        $verify_row = $wpdb->get_row($query);

        if ($verify_row) {
            $time = strtotime($verify_row->time);
            $current = strtotime("now");

            if (($current - $time) > $validity) {
                $this->delete_otp_row($verify_row->id);
                return false;
            }

            $this->valid_otps[] = $verify_row->id;
            if ($delete_otp) {
                $this->delete_otp_row($verify_row->id);
            }

            return true;
        } else {
            return false;
        }

    }

    private function delete_otp_row($row_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::OTP_TABLE;

        return $wpdb->delete($table_name, array(
            'id' => $row_id,
        ));
    }

    public function verify_whatsapp_otp($country_code, $phone, $otp, $delete_otp, $validity = 1200)
    {
        return $this->verify_otp(false, $country_code, $phone, $otp, $delete_otp, 'whatsapp_otp', $validity);
    }

    public function verify_phone_otp($country_code, $phone, $otp, $delete_otp, $validity = 1200)
    {
        return $this->verify_otp(false, $country_code, $phone, $otp, $delete_otp, 'sms_otp', $validity);
    }

    public function verify_email_otp($email, $otp, $delete_otp, $validity = 1200)
    {
        return $this->verify_otp($email, false, false, $otp, $delete_otp, 'email_otp', $validity);
    }

    public function delete_email_link()
    {
        \DigitsSessions::delete(self::EMAIL_VERIFY_KEY);
    }

    /**
     * @throws Exception
     */
    private function processLogin($user, $type = 'login')
    {
        $user_id = $user->ID;

        $this->delete_all_user_otps($user_id);

        $rememberMe = false;


        if (!empty($this->get('rememberme', false))) {
            $rememberMe = true;
        }

        $redirect_url = $this->get('digits_redirect_page', false);

        $redirect_url = apply_filters('digits_login_redirect', $redirect_url);

        $redirect_url = apply_filters('digits_login_user_redirect', $redirect_url, $user_id);

        if (strpos($redirect_url, '/wp-login.php') !== false) {
            $redirect_url = home_url();
        }

        if (empty($redirect_url) || $redirect_url == -1 || $redirect_url == -2) {
            $redirect_url = UserRedirection::get_redirect_uri($type, $user, false);
        }


        $ssl = is_ssl();
        if (false !== strpos($redirect_url, 'wp-admin')) {
            $ssl = true;
        }


        wp_clear_auth_cookie();
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, $rememberMe, $ssl);
        do_action('wp_login', $user->user_login, $user);


        if (!empty($this->login_methods) && is_array($this->login_methods)) {
            $is_phone_otp = in_array('sms_otp', $this->login_methods) || in_array('whatsapp_otp', $this->login_methods);
            if ($is_phone_otp) {
                delete_user_meta($user_id, 'digits_phone_verification_skipped');
            }

            if (in_array('email_otp', $this->login_methods) || in_array('auto_email_login', $this->login_methods)) {
                $email = $user->user_email;
                UserActionHandler::user_email_verified($user_id, $email);
            }
        }

        if ($ssl) {
            $redirect_url = preg_replace('|^http://|', 'https://', $redirect_url);

        }
        $message = __('Login Successful, Redirecting..', 'digits');

        if ($type == 'forgot') {
            $message = __('Password changed successfully, Redirecting..', 'digits');
        }

        $response = array(
            'message' => $message,
            'process_type' => 'login',
            'process' => true,
            'login_reg_success_msg' => get_option('login_reg_success_msg', 1),
        );
        if (!empty($redirect_url)) {
            $response['code'] = 1;
        } else {
            $redirect_url = -1;
            $response['code'] = 11;
        }
        $response['redirect'] = $redirect_url;

        return $response;
    }

    private function delete_all_user_otps($user_id)
    {
        $this->_delete_otp_data(['ref_id' => $user_id]);
        \DigitsSessions::instance()->destroy_session();
    }

    /**
     * @throws Exception
     */
    public function register()
    {
        $register = new UserRegistration($this->data);
        $register->process();
    }

    /**
     * @throws Exception
     */
    public function forgotpass()
    {
        if (!digits_is_forgot_password_enabled()) {
            throw new Exception(__('Forgot Password is not enabled!', 'digits'));
        }
        $this->login_details = digit_get_login_fields();

        $email_accep = $this->login_details['dig_login_email'];
        $mobile_accp = $this->login_details['dig_login_mobilenumber'];


        $captcha = $this->login_details['dig_login_captcha'];
        if ($captcha > 0) {
            $verify = $this->verify_captcha($captcha);
            if (!$verify) {
                throw new Exception(__('Please verify captcha!', 'digits'));
            }
        }

        $login_type = $this->get('action_type', true);

        $user = false;

        if ($mobile_accp == 1 && $login_type == 'phone') {
            $countryCode = $this->get('login_digt_countrycode', true);
            $raw_mobile = $this->get('digits_phone', true);

            $mobile = sanitize_mobile_field_dig($raw_mobile);
            $phone = $countryCode . $mobile;
            $this->request_user_login[] = $phone;

            if (empty($phone) || !is_numeric($phone)) {
                throw new Exception(__("Please enter a valid phone number.", "digits"));
            }

            if (!checkwhitelistcode($countryCode)) {
                $error = __('At the moment, we do not allow users from your country', ' digits');
                throw new Exception($error);
            }
            $is_phone_allowed = dig_is_phone_no_allowed($phone);
            if (!$is_phone_allowed) {
                $error = __('This phone number is not allowed!', ' digits');
                throw new Exception($error);
            }

            if (checkIfUsernameIsMobile_validate($countryCode, $mobile) == 1) {
                $userfromPhone = getUserFromPhone($phone);

                if ($userfromPhone != null) {
                    $user = $userfromPhone;
                } else {
                    $userfromMobile = getUserFromPhone($mobile);
                    if ($userfromMobile != null) {
                        $user = $userfromMobile;
                    }
                }
            }
            if (!$user) {
                $user = getUserFromPhone($countryCode . $phone);
            }
            $action = 'sms_otp';
            $otp_key = 'sms_otp';
        } else {
            $email = $this->get('digits_email', true);
            $this->request_user_login[] = $email;
            if (!isValidEmail($email)) {
                throw new Exception(__('Please enter a valid email!', 'digits'));
            }

            if ($email_accep == 1 && isValidEmail($email)) {
                $user = get_user_by('email', $email);
            }

            $action = 'email_otp';
            $otp_key = 'email_otp';
        }

        if (empty($user)) {
            throw new Exception(__('There is no account with that phone or email address.', 'digits'));
        }


        $validate_user = new WP_Error();
        $validate_user = apply_filters('digits_check_user_forgotpass', $validate_user, $user);
        if ($validate_user->has_errors()) {
            $message = $validate_user->get_error_message();
            if ($validate_user->get_error_code() == 'notice') {
                throw new DigitsNoticeException($message);
            }
            throw new Exception($message);
        }


        $this->checkUserStatus($user);

        $user_id = $user->ID;

        $data = array();

        $otp = $this->get($otp_key, false);
        if (empty($otp)) {
            $result = $this->process_user_otp_request($user, $action, 1, 'forgot');
            $data = array_merge($data, $result);
            $data['html'] = $this->forgot_html($user, $data['html'], 'otp');
            wp_send_json_success($data);
        }


        $verify = false;
        if ($otp_key == 'email_otp') {
            $verify = $this->verify_email_otp($email, $otp, false);
        } else {
            $phone_obj = digits_get_mobile_country_code($user_id);
            if (empty($phone_obj)) {
                throw new Exception(__('There is no phone number linked to the account!', 'digits'));
            }

            $phone = $phone_obj['phone'];
            $country_code = $phone_obj['country_code'];

            try {
                $verify = $this->verify_phone_otp($country_code, $phone, $otp, false);
            } catch (\DigitsFireBaseException $e) {
                wp_send_json_success(['verify_firebase' => true]);
            }
        }
        if (!$verify) {
            throw new Exception(__('Please enter a valid OTP!', 'digits'));
        }

        $password = $this->get('password', false);
        if (empty($password)) {
            $data['html'] = $this->forgot_html($user, false, 'new_password');
            wp_send_json_success($data);
        }
        if (strlen($password) < 6) {
            throw new Exception(__('Please use a stronger password!', 'digits'));
        }

        $errors = new WP_Error();
        do_action('validate_password_reset', $errors, $user);

        if ($errors->has_errors()) {
            throw new Exception($errors->get_error_message());
        }

        do_action('password_reset', $user, $password);
        wp_set_password($password, $user_id);
        wp_password_change_notification($user);

        $result = array();
        $result['success'] = true;
        $result['data'] = $this->processLogin($user, 'forgot');
        wp_send_json($result);

    }

    private function forgot_html($user, $html, $action)
    {
        ob_start();
        Processor::instance()->forgot_password($user, $html, $action);
        return ob_get_clean();
    }

    public function addUserRequestData($data)
    {
        if (!empty($data)) {
            $this->request_user_login[] = $data;
        }
    }

    public function otp_log()
    {

    }

    public function get_user_request()
    {
        return $this->request_user_login;
    }

    private function delete_all_otps()
    {
        global $wpdb;

        $ids = $this->valid_otps;
        if (empty($ids)) {
            return;
        }
        $table_name = $wpdb->prefix . self::OTP_TABLE;
        $ids = implode(',', array_map('absint', $ids));
        $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
        $this->valid_otps = array();

        \DigitsSessions::instance()->destroy_session();
    }
}