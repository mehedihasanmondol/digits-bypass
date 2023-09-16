<?php

namespace DigitsFormHandler;


use DigitsUserFormHandler\UserSettingsHandler;
use WP_User;


if (!defined('ABSPATH')) {
    exit;
}

UserFlow::instance();

final class UserFlow
{
    protected static $_instance = null;

    public function __construct()
    {
        add_action('digits_user_login_flow', [$this, 'login_flow'], 10, 2);
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

    public function login_flow($user_flow, $user_id)
    {
        $user_based_flow_enable = get_option('digits_user_based_flow_enable', false);
        if (!empty($user_based_flow_enable)) {
            $user_based_flows = $this->get_flow();

            if (!is_array($user_based_flows)) {
                $user_based_flows = stripslashes($user_based_flows);
                $user_based_flows = json_decode($user_based_flows, true);
            }

            if (!empty($user_based_flows)) {
                $user_meta = get_userdata($user_id);

                $user_values = array();
                foreach ($user_meta->roles as $user_role) {
                    $user_values[] = 'ug_' . $user_role;
                }
                $user_values[] = 'user_id_' . $user_id;

                $user_based_flows = array_reverse($user_based_flows);
                foreach ($user_based_flows as $user_based_flow) {
                    $flow_users = $user_based_flow['users'];

                    if (is_array($flow_users)) {
                        if ($this->is_user_in_flow($user_values, $flow_users)) {
                            return $user_based_flow;
                        }
                    } else if ($flow_users == 'all') {
                        return $user_based_flow;
                    }

                }
            }
        }

        return $user_flow;
    }

    public function get_flow($return_empty = true)
    {
        $default = false;
        if ($return_empty) {
            $default = $this->default_flow();
        }
        return get_option('digits_auth_user_based_flow', $default);
    }

    public function default_flow()
    {
        $flow = digits_admin_default_auth_flow();
        $flow['users'] = 'all';
        return [$flow];
    }

    public function is_user_in_flow($user_values, $flow)
    {
        $check = array_intersect($user_values, array_keys($flow));
        return sizeof($check) > 0;
    }

    public function get_flow_string()
    {
        $auth_flow = $this->get_flow(false);
        if (empty($auth_flow)) {
            $auth_flow = json_encode($this->default_flow());
        } else {
            $auth_flow = stripslashes($auth_flow);
        }
        return $auth_flow;
    }

}

