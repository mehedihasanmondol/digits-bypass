<?php

namespace DigitsUserFormHandler;


use DigitsFormHandler\Handler;
use DigitsSettingsHandler\UserAccountInfo;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

UserSettingsHandler::instance();

final class UserSettingsHandler
{
    const user_fa_key = "digits_enable_%sfa";

    protected static $_instance = null;

    private static $user_methods = [];
    private $user_default_methods = [];

    public function __construct()
    {

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

    public function get_default_config($user_id)
    {
        if (!empty($this->user_default_methods[$user_id])) {
            return $this->user_default_methods[$user_id];
        }

        $user_flow = digits_admin_get_user_flow();
        $user_flow = apply_filters('digits_user_login_flow', $user_flow, $user_id);

        $this->user_default_methods[$user_id] = $user_flow;

        return $user_flow;
    }

    public function get_all_available_methods($user_id, $step_no)
    {
        $config = $this->get_default_config($user_id);

        $method_config = $config[$step_no . 'fa'];

        $methods = $method_config['methods'];

        $methods = apply_filters('digits_user_login_methods', $methods, $user_id, $step_no);
        if (!empty($methods)) {
            $digits_enable_security_devices = dig_securityKeysEnabled();
            foreach ($methods as $method_index => $method) {
                if ($method == 'whatsapp_otp') {
                    if (!dig_isWhatsAppEnabled()) {
                        unset($methods[$method_index]);
                    }
                }
                if (in_array($method, self::platform_methods())) {
                    if ($digits_enable_security_devices == 0) {
                        unset($methods[$method_index]);
                    }
                }

            }

        }

        if (empty($methods)) {
            if ($step_no == 1) {
                return ['password', 'sms_otp'];
            }
            return [];
        }


        return $methods;
    }

    public static function get_fa_meta_key($step_no)
    {
        return sprintf(self::user_fa_key, $step_no);
    }

    public static function isUser2FaEnabled($user_id)
    {
        return self::isUserFaEnabled($user_id, 2);
    }

    public static function isUser3FaEnabled($user_id)
    {
        return self::isUserFaEnabled($user_id, 3);
    }

    public static function isUserFaEnabled($user_id, $step_no)
    {
        if ($step_no == 1) {
            return true;
        }
        $auth_config = self::getUserFaConfig($user_id, $step_no);
        return !empty($auth_config);
    }

    public static function updateUserFaPreferredMethods($user_id, $step_no, $methods)
    {
        $update = update_user_meta($user_id, self::get_fa_meta_key($step_no), $methods);
        return $update;
    }

    public static function getUserFaPreferredMethods($user_id, $step_no)
    {
        $available_methods = self::instance()->get_all_available_methods($user_id, $step_no);
        if ($step_no == 1) {
            return $available_methods;
        }
        $auth_config = self::getUserFaConfig($user_id, $step_no);

        if (empty($auth_config)) {
            return [];
        }

        $methods = array_intersect($available_methods, $auth_config);
        return $methods;
    }

    public static function removeUserFaMethod($user_id, $step_no)
    {
        $delete = delete_user_meta($user_id, self::get_fa_meta_key($step_no));
        return $delete;
    }

    public static function getUserFaConfig($user_id, $step_no)
    {
        return get_user_meta($user_id, self::get_fa_meta_key($step_no), true);
    }

    public static function platform_methods()
    {
        return array('platform', 'cross-platform', 'platform-all');
    }

    public static function get_user_platform_method($user, $step_no)
    {
        $user_methods = self::get_user_methods($user, $step_no);
        $method = array_intersect($user_methods, self::platform_methods());
        return $method;
    }

    /**
     * @param WP_User $user
     * @param $step_no
     * @return string[]
     */
    public static function get_user_methods($user, $step_no, $remove_firebase = true)
    {
        if ($user instanceof WP_User) {
            $user_id = $user->ID;
        } else {
            $user_id = $user;
        }

        if (!empty(self::$user_methods[$user_id][$step_no])) {
            return self::$user_methods[$user_id][$step_no];
        }

        $user_email = $user->user_email;

        $methods = UserSettingsHandler::getUserFaPreferredMethods($user_id, $step_no);

        $platform_methods = array('platform', 'cross-platform');
        if (in_array('platform', $methods) && in_array('cross-platform', $methods)) {
            $methods = array_diff($methods, $platform_methods);
            $methods[] = 'platform-all';
        }

        $user_phone = digits_get_mobile_country_code($user_id);

        foreach ($methods as $method_index => $method) {

            if ($method == '2fa_app') {
                $totp = UserAccountInfo::instance()->get_user_totp($user_id, false);
                if (empty($totp)) {
                    unset($methods[$method_index]);
                }
            }

            if ($method == 'email_otp' && empty($user_email)) {
                unset($methods[$method_index]);
            }

            if (in_array($method, ['sms_otp', 'whatsapp_otp'])) {
                if (empty($user_phone)) {
                    unset($methods[$method_index]);
                } else if ($method == 'sms_otp') {
                    $country_code = $user_phone['country_code'];
                    $digit_gateway = dig_gatewayToUse($country_code);
                    if ($digit_gateway == 13) {
                        $phone = $country_code . $user_phone['phone'];
                        $user_request_data = Handler::instance()->get_user_request();
                        if (!in_array($phone, $user_request_data)) {
                            unset($methods[$method_index]);
                        }
                    }
                }
            }

            if (in_array($method, array('cross-platform', 'platform', 'platform-all'), true)) {
                $devices = \DigitsDeviceAuth::instance()->getUserSecurityDevicesType($user_id, $method);
                if (empty($devices)) {
                    unset($methods[$method_index]);
                }
            }

        }

        $methods = self::filter_methods($methods);

        self::$user_methods[$user_id][$step_no] = $methods;
        return $methods;
    }

    public static function filter_methods($methods)
    {
        $all_methods = [
            'password',
            'cross-platform', 'platform', 'platform-all',
            'sms_otp', 'whatsapp_otp', 'email_otp', '2fa_app',
        ];
        return array_intersect($all_methods, $methods);
    }
}
