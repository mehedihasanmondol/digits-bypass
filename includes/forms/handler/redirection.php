<?php

namespace DigitsFormHandler;

if (!defined('ABSPATH')) {
    exit;
}


final class UserRedirection
{

    public function __construct()
    {

    }

    public static function get_redirect_uri($type, $user, $return_empty = false)
    {
        $uri = '';
        switch ($type) {
            case 'login':
                $uri = get_option("digits_loginred");
                break;
            case 'forgot':
                $uri = get_option("digits_forgotred");
                break;
            case 'register':
                $uri = get_option("digits_regred");
                break;
            case 'logout':
                $uri = get_option("digits_logoutred");
                break;
            case 'my-account':
                $uri = get_option('digits_myaccount_redirect');
                break;
        }

        if (empty($uri)) {
            if ($return_empty) {
                return '';
            }
            return home_url();
        }
        if (!empty($user)) {

            $placeholders = array(
                '{{user-id}}' => $user->ID,
                '{{user-email}}' => $user->user_email,
                '{{username}}' => $user->user_login,
                '{{display-name}}' => $user->display_name,
                '{{first-name}}' => $user->first_name,
                '{{last-name}}' => $user->last_name,
            );
            $uri = strtr($uri, $placeholders);
        }
        return $uri;
    }
}