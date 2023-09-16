<?php

namespace DigitsSettingsHandler;


use DigitsUserFormHandler\UserSettingsHandler;

if (!defined('ABSPATH')) {
    exit;
}

SecureAccountHandler::instance();

final class SecureAccountHandler
{

    protected static $_instance = null;

    public function __construct()
    {
        $this->init_hooks();
    }

    public function init_hooks()
    {
        add_action('wp_ajax_digits_enable_account_2fa', [$this, 'enable_account_2fa']);
        add_action('wp_ajax_digits_enable_account_3fa', [$this, 'enable_account_3fa']);

    }

    public function enable_account_2fa()
    {
        $this->validate_request('digits_enable_account_2fa');
        $this->process_enable_user_fa(2);
    }

    private function validate_request($action)
    {
        check_ajax_referer($action);
        if (!is_user_logged_in()) {
            wp_send_json_error(array("message" => __("Please login to continue!")));
        }
    }

    public function process_enable_user_fa($step_no)
    {
        try {
            $this->enable_user_fa($step_no);
        } catch (\Exception $e) {
            $data = array();
            $data['message'] = $e->getMessage();
            wp_send_json_error($data);
        }
    }

    /**
     * @throws \Exception
     */
    public function enable_user_fa($step_no)
    {

        $user = wp_get_current_user();
        $user_id = $user->ID;


        $available_methods = $this->getUserAvailableSetupMethods($user_id, $step_no, true);

        if (empty($_REQUEST['auth_methods'])) {
            throw new \Exception(__('Please select a method to continue!', 'digits'));
        }
        $methods = $_REQUEST['auth_methods'];

        $user_email = $user->user_email;
        $user_phone = digits_get_mobile($user_id);

        $totp = UserAccountInfo::instance()->get_user_totp($user_id, false);

        foreach ($methods as $method) {
            if (!in_array($method, $available_methods, true)) {
                throw new \Exception(__('Authentication method not found, please try using another method!', 'digits'));
            }

            if (empty($user_email) && $method == 'email_otp') {
                $this->setup_user_method('email');
            }

            if (empty($user_phone) && ($method == 'sms_otp' || $method == 'whatsapp_otp')) {
                $this->setup_user_method('phone');
            }

            if (in_array($method, array('cross-platform', 'platform'), true)) {
                $devices = \DigitsDeviceAuth::instance()->getUserSecurityDevicesType($user_id, $method);
                if (empty($devices)) {
                    $this->setup_user_method($method);
                }
            }

            if (empty($totp) && $method == '2fa_app') {
                $this->setup_user_method('2fa_app');
            }

        }

        $data = array();
        UserSettingsHandler::updateUserFaPreferredMethods($user_id, $step_no, $methods);
        $message = __('%d-Factor Authentication is now successfully enabled!', 'digits');
        $message = sprintf($message, $step_no);
        $data['message'] = $message;
        wp_send_json_success($data);

    }

    /**
     * @throws \Exception
     */
    public function getUserAvailableSetupMethods($user_id, $step_no, $validate)
    {
        if ($validate) {
            if (UserSettingsHandler::isUserFaEnabled($user_id, $step_no)) {
                $error = __('%d-Factor Authentication is already enabled!', 'digits');
                $error = sprintf($error, $step_no);
                throw new \Exception($error);
            }
        }

        $available_methods = UserSettingsHandler::instance()->get_all_available_methods($user_id, $step_no);
        if (empty($available_methods)) {
            $error = __('%d-Factor Authentication is not available', 'digits');
            $error = sprintf($error, $step_no);
            throw new \Exception($error);
        }

        if ($validate) {
            if ($step_no == 3) {
                $is_2fa_enabled = UserSettingsHandler::isUser2FaEnabled($user_id);
                if (!$is_2fa_enabled) {
                    $error = __('Please enable 2FA before enabling 3FA', 'digits');
                    throw new \Exception($error);
                }
            }
        }

        return $available_methods;
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

    private function setup_user_method($method)
    {
        $setupAccountAuths = SetupAccountAuths::instance();
        $data = array();

        switch ($method) {
            case 'email':
                $data['html'] = $setupAccountAuths->render_email_setup();
                break;
            case 'platform':
                $data['html'] = $setupAccountAuths->render_device_setup('platform');
                break;
            case 'cross-platform':
                $data['html'] = $setupAccountAuths->render_device_setup('cross-platform');
                break;
            case 'phone':
                $error = __('Please add phone number to your account before using it as a %s-Factor Authenticator', 'digits');
                $error = sprintf($error, $method);
                throw new \Exception($error);
                break;
            case '2fa_app':
                $data['html'] = $setupAccountAuths->render_auth_app_setup();
                break;
        }
        wp_send_json_success($data);
    }

    public function enable_account_3fa()
    {
        $this->validate_request('digits_enable_account_3fa');
        $this->process_enable_user_fa(3);
    }

}