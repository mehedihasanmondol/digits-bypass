<?php

namespace DigitsFormHandler;


use DigitsDeviceAuth;
use DigitsNoticeException;
use DigitsSettingsHandler\UserAccountInfo;
use DigitsUserFormHandler\UserSettingsHandler;
use donatj\UserAgent\UserAgentParser;
use Exception;
use WP_Error;
use WP_User;


if (!defined('ABSPATH')) {
    exit;
}

UserActionHandler::instance();

final class UserActionHandler
{
    const available_methods = ['direct_email_login', 'verify_email', 'setup_auth_device', 'remote_device_auth'];
    protected static $_instance = null;

    public function __construct()
    {
        $this->init_hooks();
    }

    public function init_hooks()
    {
        add_action('wp_ajax_nopriv_digits_resend_email_verification', [$this, 'resend_verification_email']);

        add_action('wp_ajax_digits_user_remote_action', [$this, 'user_action']);
        add_action('wp_ajax_nopriv_digits_user_remote_action', [$this, 'user_action']);
    }

    public function resend_verification_email()
    {
        $user_login = $_REQUEST['user'];
        $nonce = $_REQUEST['nonce'];

        if (empty($user_login) || empty($nonce)) {
            wp_send_json_error(['message' => __('Error', 'digits')]);
        }

        if (!wp_verify_nonce($nonce, $user_login . '_resend_verify_email')) {
            wp_send_json_error(['message' => __('Error, Please try again after sometime', 'digits')]);
        }

        $user = get_user_by('login', $user_login);
        if (empty($user)) {
            wp_send_json_error(['message' => __('Unknown error occurred', 'digits')]);
        }
        $result = UserRegistration::send_verify_email($user);
        if (!$result) {
            wp_send_json_error(['message' => __('Error, while sending verification email! Please try again later', 'digits')]);
        }
        wp_send_json_success(['message' => __('Please check your email for the verification link to verify the account.', 'digits')]);
    }

    /**
     * @param $request_token
     * @return WP_User
     */
    public static function get_user_from_email_token($request_token)
    {
        $token_info = \DigitsSessions::get(Handler::EMAIL_VERIFY_PROCESS_KEY);
        $token_info = json_decode($token_info, true);

        $validate = self::instance()->validate_token($token_info, $request_token);

        if ($validate instanceof WP_Error) {
            wp_send_json_error(['message' => $validate->get_error_message()]);
        }

        $user_email = $token_info['email'];
        return get_user_by('email', $user_email);
    }

    public function validate_token($token_info, $request_token)
    {

        if (empty($token_info)) {
            return new WP_Error('error', __('This link has expired, Please try again!', 'digits'));
        }

        $token = $token_info['token'];

        $generation_time = $token_info['time'];
        if ($token != $request_token || time() - $generation_time > 600) {
            return new WP_Error('error', __('Email approval link has expired, Please try again!', 'digits'));
        }

        return true;
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

    public function user_action()
    {
        $method = $this->get_var('method', true);
        $auth_key = $this->get_var('auth_key', true);
        $auth_token = $this->get_var('auth_token', true);
        if (!in_array($method, self::available_methods, true)) {
            wp_send_json_error(['message' => __('Request not found!')]);
        }

        if ($method == 'direct_email_login') {
            $this->process_email_login($auth_key, $auth_token);
        } else if ($method == 'verify_email') {
            $this->verify_user_email($auth_key, $auth_token);
        } else if ($method == 'remote_device_auth') {
            $this->process_remote_auth_login($auth_key, $auth_token);
        }

    }

    public function check_remote_auth_token($token_info)
    {
        if (empty($token_info)) {
            wp_send_json_error(array("message" => __('Session expired, please try logging in again!', 'digits')));
        }

        $token_info = json_decode($token_info, true);

        if (empty($token_info)) {
            wp_send_json_error(array("message" => __('Error please try again!', 'digits')));
        }

        if ($token_info['status'] != Handler::REMOTE_DEVICE_AUTH_PENDING_STATUS) {
            wp_send_json_error(array("message" => __('You have already logged in via this QR Code!', 'digits')));
        }
        return $token_info;
    }

    public function process_remote_auth_login($auth_key, $auth_token)
    {
        try {
            $data = array();

            $token_info = \DigitsSessions::get_from_identifier($auth_token);
            $token_info = $this->check_remote_auth_token($token_info);

            $user_id = $token_info['user_id'];
            $step_no = $token_info['step_no'];

            $user = get_user_by('ID', $user_id);


            Handler::instance()->check_remote_auth_available($user_id, true);


            if (!empty($_REQUEST['cred'])) {
                $auth_cred = $_REQUEST['cred'];
                $validate = DigitsDeviceAuth::authenticate_user_device($user, $step_no, $auth_cred);
                if ($validate instanceof WP_Error) {
                    throw new Exception($validate->get_error_message());
                }
                $token_info['status'] = 'completed';
                \DigitsSessions::update_identifier_value($auth_token, $token_info);

                wp_send_json_success(['message' => __('Device authentication successful!', 'digits')]);

            }

            $data['token'] = Handler::instance()->generate_platform_token($user, $step_no, 'platform');

            $data['process_remote_auth_login'] = true;
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function get_var($name, $required)
    {
        if (!empty($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }
        if ($required) {
            wp_send_json_error(['message' => __('Request not found!')]);
        }
        return null;
    }

    public function process_email_login($request_key, $request_token)
    {
        if (is_user_logged_in()) {
            wp_send_json_error(['message' => __('You are already logged in!', 'digits'), 'notice' => true]);
        }


        $token_info = \DigitsSessions::get(Handler::EMAIL_VERIFY_KEY);

        if (empty($token_info)) {
            $this->process_email_login_using_identifier($request_key, $request_token);
            die();
        }

        $token_info = json_decode($token_info, true);

        $validate = $this->validate_token($token_info, $request_token);
        if ($validate instanceof WP_Error) {
            wp_send_json_error(['message' => $validate->get_error_message()]);
        }

        $data = array();

        if (!empty($token_info['form_id'])) {
            $data['form_id'] = esc_attr($token_info['form_id']);
        } else {
            $data['form_id'] = esc_attr('digits_protected');
        }


        $user_email = $token_info['email'];
        $user = get_user_by('email', $user_email);

        if (empty($user)) {
            wp_send_json_error(['message' => __('Please signup before logging in.', 'digits'), 'notice' => true]);
        }

        Handler::instance()->delete_email_otp($user_email);

        $new_token = Handler::generate_token(30);
        $updated_token_info = $token_info;
        $updated_token_info['time'] = time();
        $updated_token_info['token'] = $new_token;


        \DigitsSessions::delete(Handler::EMAIL_VERIFY_KEY);
        \DigitsSessions::update(Handler::EMAIL_VERIFY_PROCESS_KEY, $updated_token_info, 3600);


        $data['email_verify'] = $new_token;
        $data['process_login'] = true;
        wp_send_json_success($data);
    }

    public function process_email_login_using_identifier($request_key, $request_token)
    {
        $token_info = \DigitsSessions::get_from_identifier($request_key, true);

        if (empty($token_info)) {
            wp_send_json_error(['message' => __('This link has expired, Please try again!', 'digits')]);
        }

        if ($token_info->data_key != Handler::EMAIL_VERIFY_KEY) {
            wp_send_json_error(['message' => __('This link is not valid, Please try again!', 'digits')]);
        }

        $token_details = json_decode($token_info->data_value, true);

        if (isset($_REQUEST['action_type'])) {
            $action_type = $_REQUEST['action_type'];

            if ($token_details['status'] == 'pending' || $token_details['status'] == 'deny') {
                $response = array();
                if ($token_details['status'] == 'pending' && (
                        $action_type == 'approve' || $action_type == 'deny')) {
                    if ($action_type == 'approve') {
                        $token_details['status'] = 'approved';
                        $response['message'] = __('Login Approved Successfully!', 'digits');
                    } else {
                        $token_details['status'] = 'denied';
                    }

                } else {
                    $token_details['status'] = 'blocked';
                    $response['message'] = __('Device Blocked Successfully!', 'digits');

                    $user_id = $token_details['user_id'];

                    $block_key = md5($token_details['user_ip'] . $token_details['device']);

                    $block_key = $block_key . '_blocked_' . $user_id;

                    \DigitsSessions::delete_identifier($block_key);
                    \DigitsSessions::set_session_value($block_key, 'blocked', 'blocked', 86400, $block_key);
                }
                $response['redirect_to'] = home_url();
                \DigitsSessions::update_identifier_value($request_key, $token_details);
                wp_send_json_success($response);
            } else {
                $response['redirect_to'] = home_url();
                wp_send_json_error(['message' => __('This link is no longer valid, please try again!', 'digits')]);
            }

        } else {
            $user_ip = $token_details['user_ip'];
            $user_agent = $token_details['device'];

            $region = digits_getRegionFromIP($user_ip);

            $parser = new UserAgentParser();
            $parser = $parser->parse($user_agent);
            $browser = $parser->browser();

            $data = array();
            $data['body_html'] = $this->render_email_access($region, $browser, $user_ip);
            wp_send_json_success($data);
        }
    }

    public function render_email_access($region, $browser, $ip)
    {
        ob_start();

        $details = array('approval_form' => true);
        $details['region'] = $region;
        $details['browser'] = $browser;
        $details['user_ip'] = $ip;
        ?>
        <div class="digits_ui" id="digits_protected_login_approval">
            <div class="digits_popup_wrapper dig-box" style="display: block;">
                <?php
                digits_new_form_page(true, $details);
                ?>
            </div>
        </div>
        <?php


        return ob_get_clean();
    }

    public function verify_user_email($request_key, $request_token)
    {
        global $wpdb;


        $request_token = filter_var($request_token, FILTER_SANITIZE_STRING);

        $token_meta_row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->usermeta . '
        WHERE meta_value = %s AND meta_key= %s LIMIT 1',
                $request_token, UserRegistration::USER_VERIFY_EMAIL_KEY
            )
        );

        if ($token_meta_row) {
            $user_id = $token_meta_row->user_id;
            $user = get_user_by('id', $user_id);
            $gen_time = get_user_meta($user_id, UserRegistration::USER_VERIFY_EMAIL_KEY_GEN_TIME, true);
            $diff_time = time() - $gen_time;

            if (!UserRegistration::USER_VERIFY_LINK_VALIDITY_EXPIRE
                || $diff_time < UserRegistration::USER_VERIFY_LINK_VALIDITY_SEC) {

                $email = $user->user_email;

                if (md5($email) == $request_key) {

                    self::user_email_verified($user_id, $email);
                    
                    $data = array();
                    $data['message'] = __('Thank you for verifiying your email!', 'digits');
                    $data['redirect'] = home_url();
                    wp_send_json_success($data);
                }
            }
        }

        wp_send_json_error(['message' => __('This link has expired, Please try again!', 'digits')]);

    }

    public static function user_email_verified($user_id,$email){
        update_user_meta($user_id, UserRegistration::USER_VERIFIED_EMAIL, $email);

        delete_user_meta($user_id, UserRegistration::USER_VERIFY_EMAIL_KEY);
        delete_user_meta($user_id, UserRegistration::USER_VERIFY_EMAIL_KEY_GEN_TIME);

    }
}