<?php


if (!defined('ABSPATH')) {
    exit;
}

DigitsDashboardData::instance();

final class DigitsDashboardData
{

    const LOGIN_TIME_SAVE_IN_S = 30;
    protected static $_instance = null;

    public function __construct()
    {
        add_action('wp_ajax_digits_admin_dashboard_stats', array($this, 'admin_dashboard'));
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

    public function admin_dashboard()
    {
        $this->check_permission();


        /* $duration = $_REQUEST['duration'];
         if (empty($duration)) {
             $duration = absint(12);
         }*/

        if ($_REQUEST['graph_type'] == 'users') {
            $data = $this->get_total_users(12);
        } else {
            $data = $this->get_total_logins(6);
        }
        wp_send_json_success($data);
    }

    public function check_permission()
    {
        if (!current_user_can('manage_options')) {
            die();
        }
        if (!wp_verify_nonce($_REQUEST['nonce'], 'digits_admin_dashboard')) {
            die();
        }

    }

    public function get_total_users($duration)
    {
        $total_otp = $this->get_total_otp_count();

        global $wpdb;

        $duration = absint($duration);

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}users WHERE user_registered > now()-interval %d month ";
        $sql = $wpdb->prepare($sql, $duration);
        $total_users = $wpdb->get_var($sql);

        $data = array();
        $sql = "SELECT COUNT(*) as total_users, DATE_FORMAT(user_registered,'%m-%Y') as duration FROM {$wpdb->prefix}users WHERE user_registered > now()-interval %d month GROUP BY DATE_FORMAT(user_registered,'%m-%Y') ";
        $sql = $wpdb->prepare($sql, $duration + 1);
        $records = $wpdb->get_results($sql);

        $dateTime = new DateTime();
        for ($i = 1; $i <= $duration; $i++) {
            $date_key = $dateTime->format('m-Y');
            $month = $dateTime->format('M');
            $data[$date_key] = ['x' => $month, 'y' => 0];
            $dateTime->modify('-1 month');
        }

        foreach ($records as $record) {
            if (isset($data[$record->duration])) {
                $data[$record->duration]['y'] = $record->total_users;
            }
        }


        $password_less_logins = $this->get_total_password_less_login_count();
        $result = array(
            'total_data' => $this->format_number($total_users),
            'graph' => array_reverse(array_values($data)),
            'total_otps' => $this->format_number($total_otp),
            'total_time_save' => $this->format_number($password_less_logins * self::LOGIN_TIME_SAVE_IN_S / 60),
            'type' => 'user'
        );
        return $result;
    }

    public function get_total_otp_count()
    {
        global $wpdb;
        /*$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}digits_request_logs WHERE mode = %s ";
        $sql = $wpdb->prepare($sql, $route);*/
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}digits_request_logs";
        $total = $wpdb->get_var($sql);
        return $total;
    }

    public function get_total_password_less_login_count()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}digits_login_logs WHERE password_less = 1";
        $total = $wpdb->get_var($sql);
        return $total;
    }

    public function format_number($number)
    {
        $suffix = '';
        if ($number > 9999) {
            $number = floor($number / 1000);
            $suffix = 'k';
        }
        $number = round($number, 2);
        return $number . $suffix;
    }

    public function get_total_logins($duration)
    {
        global $wpdb;

        $duration = absint($duration);

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}digits_login_logs WHERE time > now()-interval %d month ";
        $sql = $wpdb->prepare($sql, $duration);
        $total_users = $wpdb->get_var($sql);

        $sql = "SELECT COUNT(*) as total_users, DATE_FORMAT(time,'%Y-%m-%d') as duration FROM {$wpdb->prefix}digits_login_logs WHERE time > now()-interval $duration month GROUP BY DATE_FORMAT(time,'%Y-%m-%d') ";

        $records = $wpdb->get_results($sql);
        $data = array();
        foreach ($records as $record) {
            $data[$record->duration] = $record->total_users;
        }

        $start = new DateTime();
        $end = new DateTime();
        $start->modify("-$duration months");

        $range = $this->date_range($start, $end);

        $result = array();
        foreach ($range as $date_info) {
            $date = $date_info[0];
            $timestamp = $date_info[1];
            $day_logins = !empty($data[$date]) ? $data[$date] : 0;
            $result[] = [$timestamp, $day_logins];
        }
        return array(
            'total_data' => $this->format_number($total_users),
            'graph' => $result,
            'type' => 'logins'
        );
    }

    public function date_range($start, $end)
    {
        $dates = [];
        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);
        $current = $start->getTimestamp();
        $end_time = $end->getTimestamp();

        while ($current <= $end_time) {

            $time_stamp = $start->getTimestamp();
            $dates[] = [$start->format('Y-m-d'), $time_stamp * 1000];
            $start->modify('+1 day');
            $current = $time_stamp;
        }

        return $dates;
    }
}
