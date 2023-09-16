<?php

namespace DigitsSettingsHandler;

use OTPHP\TOTP;

if (!defined('ABSPATH')) {
    exit;
}

UserAccountInfo::instance();

final class UserAccountInfo
{
    const TOTP_KEY = 'digits_totp';
    const TEMP_TOTP_KEY = 'temp_digits_totp';
    const TOTP_DURATION = 60;
    protected static $_instance = null;

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


    /**
     * @returns TOTP
     */
    public function get_user_totp($user_id, $temp = false)
    {
        $user = get_user_by('ID', $user_id);

        if (empty($user)) {
            return null;
        }
        if (!empty($user->user_email)) {
            $user_label = $user->user_email;
        } else {
            $user_label = $user->display_name;
        }
        $site_name = get_bloginfo('name');

        if ($temp) {
            $totp_code = \DigitsSessions::get(self::TEMP_TOTP_KEY);
        } else {
            $totp_code = get_user_meta($user_id, self::TOTP_KEY, true);
        }
        if (empty($totp_code)) {
            if (!$temp) {
                return false;
            }
            $totp_code = null;
            $totp = TOTP::create(null);
            \DigitsSessions::set(self::TEMP_TOTP_KEY, $totp->getSecret(), 3600);
        } else {
            $totp = TOTP::create($totp_code);
        }

        $totp->setLabel($user_label);
        if (!empty($site_name)) {
            $totp->setIssuer($site_name);
        }
        return $totp;
    }


}